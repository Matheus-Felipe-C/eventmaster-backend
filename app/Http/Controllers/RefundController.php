<?php

namespace App\Http\Controllers;

use App\Services\MercadoPago\MercadoPagoCheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MercadoPago\Exceptions\MPApiException;

class RefundController extends Controller
{
    /**
     * Full refund for a Mercado Pago payment tied to a paid checkout session.
     * Restores batch quantities and cancels issued tickets (when linked to the session).
     */
    public function store(Request $request, MercadoPagoCheckoutService $checkoutService): JsonResponse
    {
        $validated = $request->validate([
            'payment_id' => ['required', 'integer', 'min:1'],
            'amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $result = $checkoutService->refundMercadoPagoPayment(
                (int) $validated['payment_id'],
                isset($validated['amount']) ? (float) $validated['amount'] : null,
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 503);
        } catch (MPApiException $e) {
            return response()->json([
                'message' => __('Mercado Pago could not process the refund.'),
                'details' => $e->getApiResponse()->getContent(),
            ], 502);
        }

        if (($result['already_refunded'] ?? false) === true) {
            return response()->json([
                'message' => __('This payment was already refunded.'),
                'session' => $result['session'] ?? null,
            ]);
        }

        return response()->json([
            'message' => __('Refund processed successfully.'),
            'refund_id' => $result['refund_id'] ?? null,
            'session' => $result['session'] ?? null,
        ]);
    }
}
