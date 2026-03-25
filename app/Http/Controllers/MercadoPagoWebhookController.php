<?php

namespace App\Http\Controllers;

use App\Services\MercadoPago\MercadoPagoCheckoutService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class MercadoPagoWebhookController extends Controller
{
    /**
     * Mercado Pago payment notifications (IPN / webhooks).
     *
     * Configure the same URL in the preference's notification_url or in your Mercado Pago app settings.
     *
     * @see https://www.mercadopago.com.br/developers/pt/reference
     */
    public function __invoke(Request $request, MercadoPagoCheckoutService $checkoutService): Response
    {
        $paymentId = $this->resolvePaymentId($request);

        if ($paymentId === null) {
            return response()->noContent();
        }

        $token = config('mercadopago.access_token');
        if (! is_string($token) || $token === '') {
            Log::warning('Mercado Pago webhook received but access token is not configured.');

            return response()->noContent();
        }

        try {
            $checkoutService->processPaymentNotification($paymentId);
        } catch (\Throwable $e) {
            Log::error('Mercado Pago webhook processing failed.', [
                'payment_id' => $paymentId,
                'exception' => $e->getMessage(),
            ]);

            return response('Error', 500);
        }

        return response()->noContent();
    }

    private function resolvePaymentId(Request $request): ?int
    {
        if ($request->query('topic') === 'payment' && $request->query('id') !== null && $request->query('id') !== '') {
            return (int) $request->query('id');
        }

        $data = $request->all();

        if (($data['type'] ?? null) === 'payment' && isset($data['data']['id'])) {
            return (int) $data['data']['id'];
        }

        if (($data['topic'] ?? null) === 'payment' && isset($data['id'])) {
            return (int) $data['id'];
        }

        if (($data['action'] ?? null) === 'payment.updated' && isset($data['data']['id'])) {
            return (int) $data['data']['id'];
        }

        $resource = $data['resource'] ?? null;
        if (is_string($resource) && preg_match('/\/payments\/(\d+)/', $resource, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }
}
