<?php

namespace App\Services\MercadoPago;

use App\Models\CartItem;
use App\Models\MercadoPagoCheckoutSession;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use MercadoPago\Client\Common\RequestOptions;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\MercadoPagoConfig;
class MercadoPagoCheckoutService
{
    private function configureSdk(): void
    {
        $token = config('mercadopago.access_token');

        if (! is_string($token) || $token === '') {
            throw new \RuntimeException('Mercado Pago access token is not configured.');
        }

        MercadoPagoConfig::setAccessToken($token);

        if (config('mercadopago.runtime') === 'local') {
            MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::LOCAL);
        } else {
            MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::SERVER);
        }
    }

    /**
     * @return array{
     *   session: MercadoPagoCheckoutSession,
     *   init_point: string|null,
     *   sandbox_init_point: string|null,
     *   preference_id: string|null,
     *   debug_back_urls: array<string, string>,
     *   debug_auto_return: string|null
     * }
     */
    public function createCheckoutProPreference(User $user): array
    {
        $this->configureSdk();

        $cartItems = $user->cartItems()
            ->with(['event', 'batch', 'ticketType'])
            ->orderBy('created_at')
            ->get();

        if ($cartItems->isEmpty()) {
            throw new \InvalidArgumentException(__('Your cart is empty.'));
        }

        $lines = [];
        $preferenceItems = [];
        $cartItemIds = [];
        $total = 0.0;

        foreach ($cartItems as $item) {
            /** @var CartItem $item */
            $event = $item->event;
            $batch = $item->batch;
            $ticketType = $item->ticketType;

            $unitPrice = (float) $batch->price;
            $qty = (int) $item->quantity;
            $lineTotal = round($unitPrice * $qty, 2);
            $total = round($total + $lineTotal, 2);

            $title = $event->name.' — '.$ticketType->name;
            $itemId = (string) $event->id.'-'.$batch->id.'-'.$ticketType->id;

            $lines[] = [
                'id_event' => $event->id,
                'id_batch' => $batch->id,
                'id_ticket_type' => $ticketType->id,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
            ];

            $cartItemIds[] = $item->id;

            $prefItem = [
                'id' => $itemId,
                'title' => mb_substr($title, 0, 255),
                'description' => mb_substr($event->name.' ('.$event->date->format('Y-m-d').')', 0, 255),
                'quantity' => $qty,
                'currency_id' => 'BRL',
                'unit_price' => $unitPrice,
            ];

            if (! empty($event->banner_image_url)) {
                $prefItem['picture_url'] = $event->banner_image_url;
            }

            $preferenceItems[] = $prefItem;
        }

        $externalReference = (string) Str::uuid();

        $session = MercadoPagoCheckoutSession::create([
            'id_user' => $user->id,
            'external_reference' => $externalReference,
            'cart_snapshot' => [
                'cart_item_ids' => $cartItemIds,
                'lines' => $lines,
                'total' => $total,
            ],
            'total_amount' => $total,
            'currency_id' => 'BRL',
            'status' => 'pending',
        ]);

        [$payerName, $payerSurname] = $this->splitPayerName($user->name);

        $notificationUrl = config('mercadopago.notification_url');
        if (! is_string($notificationUrl) || $notificationUrl === '') {
            $notificationUrl = rtrim(config('app.url'), '/').'/api/webhooks/mercado-pago';
        }

        $backUrls = [
            'success' => $this->validUrlOrNull(config('mercadopago.back_urls.success')),
            'failure' => $this->validUrlOrNull(config('mercadopago.back_urls.failure')),
            'pending' => $this->validUrlOrNull(config('mercadopago.back_urls.pending')),
        ];
        $backUrls = array_filter($backUrls, fn ($url) => $url !== null);

        $request = [
            'items' => $preferenceItems,
            'payer' => [
                'name' => $payerName,
                'surname' => $payerSurname,
                'email' => $user->email,
            ],
            'external_reference' => $externalReference,
            'statement_descriptor' => mb_substr((string) config('mercadopago.statement_descriptor'), 0, 22),
            'notification_url' => $notificationUrl,
            'payment_methods' => [
                'installments' => 12,
                'default_installments' => 1,
            ],
        ];

        if ($backUrls !== []) {
            $request['back_urls'] = $backUrls;
        }

        // Temporary safe mode: avoid auto_return strict validation until frontend/back URLs are stable.
        // Mercado Pago will still redirect via back_urls when the user leaves Checkout Pro.

        $requestOptions = new RequestOptions;
        $requestOptions->setCustomHeaders(['X-Idempotency-Key: '.$externalReference]);

        try {
            $client = new PreferenceClient;
            $preference = $client->create($request, $requestOptions);
        } catch (MPApiException $e) {
            $session->delete();
            throw $e;
        }

        $session->update([
            'preference_id' => $preference->id,
        ]);

        return [
            'session' => $session->fresh(),
            'init_point' => $preference->init_point,
            'sandbox_init_point' => $preference->sandbox_init_point ?? null,
            'preference_id' => $preference->id,
            'debug_back_urls' => $backUrls,
            'debug_auto_return' => null,
        ];
    }

    /**
     * Fetch payment from Mercado Pago and fulfill checkout if approved.
     */
    public function processPaymentNotification(int $paymentId): void
    {
        $this->configureSdk();

        $client = new PaymentClient;
        $payment = $client->get($paymentId);

        if ($payment->status !== 'approved') {
            return;
        }

        $externalReference = $payment->external_reference;
        if (! is_string($externalReference) || $externalReference === '') {
            return;
        }

        DB::transaction(function () use ($payment, $externalReference) {
            /** @var MercadoPagoCheckoutSession|null $session */
            $session = MercadoPagoCheckoutSession::query()
                ->where('external_reference', $externalReference)
                ->lockForUpdate()
                ->first();

            if (! $session || $session->status === 'paid') {
                return;
            }

            $snapshot = $session->cart_snapshot;
            $expectedTotal = (float) ($snapshot['total'] ?? 0);
            $paid = (float) ($payment->transaction_amount ?? 0);

            if (abs($paid - $expectedTotal) > 0.02) {
                $session->update(['status' => 'failed']);

                return;
            }

            $userId = (int) $session->id_user;
            $lines = $snapshot['lines'] ?? [];
            $cartItemIds = $snapshot['cart_item_ids'] ?? [];

            foreach ($lines as $line) {
                $batchId = (int) $line['id_batch'];
                $maxSeat = (int) Ticket::query()->where('id_batch', $batchId)->max('seat_number');

                $qty = (int) ($line['quantity'] ?? 0);
                for ($i = 0; $i < $qty; $i++) {
                    $maxSeat++;
                    Ticket::create([
                        'id_user' => $userId,
                        'id_event' => (int) $line['id_event'],
                        'id_ticket_type' => (int) $line['id_ticket_type'],
                        'id_batch' => $batchId,
                        'status' => 'paid',
                        'seat_number' => $maxSeat,
                        'is_validated' => false,
                    ]);
                }
            }

            if ($cartItemIds !== []) {
                CartItem::query()
                    ->where('id_user', $userId)
                    ->whereIn('id', $cartItemIds)
                    ->delete();
            }

            $session->update([
                'status' => 'paid',
                'payment_id' => $payment->id,
            ]);
        });
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitPayerName(string $fullName): array
    {
        $fullName = trim($fullName);
        if ($fullName === '') {
            return ['Cliente', 'Eventmaster'];
        }

        $parts = preg_split('/\s+/u', $fullName, 2);

        $first = $parts[0] ?? 'Cliente';
        $rest = $parts[1] ?? $first;

        return [mb_substr($first, 0, 100), mb_substr($rest, 0, 100)];
    }

    private function validUrlOrNull(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $url = trim($value);
        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        return $url;
    }
}
