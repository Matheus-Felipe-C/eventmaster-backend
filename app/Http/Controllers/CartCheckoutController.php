<?php

namespace App\Http\Controllers;

use App\Services\MercadoPago\MercadoPagoCheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MercadoPago\Exceptions\MPApiException;

class CartCheckoutController extends Controller
{
    /**
     * Create a Mercado Pago Checkout Pro preference for the current user's cart.
     *
     * @see https://www.mercadopago.com.br/developers/pt/reference/checkout-pro/preferences/create
     */
    public function __invoke(Request $request, MercadoPagoCheckoutService $checkoutService): JsonResponse
    {
        $token = config('mercadopago.access_token');
        if (! is_string($token) || $token === '') {
            return response()->json([
                'message' => __('Mercado Pago is not configured.'),
            ], 503);
        }

        try {
            $result = $checkoutService->createCheckoutProPreference($request->user());
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
                'message' => __('Could not create Mercado Pago checkout.'),
                'details' => $e->getApiResponse()->getContent(),
            ], 502);
        }

        return response()->json([
            'message' => __('Checkout created. Send the buyer to init_point (or sandbox_init_point in tests).'),
            'init_point' => $result['init_point'],
            'sandbox_init_point' => $result['sandbox_init_point'],
            'preference_id' => $result['preference_id'],
            'external_reference' => $result['session']->external_reference,
            'debug' => [
                'back_urls' => $result['debug_back_urls'],
                'auto_return' => $result['debug_auto_return'],
            ],
        ]);
    }
}
