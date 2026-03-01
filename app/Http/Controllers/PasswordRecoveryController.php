<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

class PasswordRecoveryController extends Controller
{
    public function sendPasswordResetLink(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $validator->errors(),
            ], 422);
        }

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status !== Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => __($status),
            ], 400);
        }

        return response()->json([
            'message' => __('An email with instructions on how to reset your password has been sent.'),
        ], 200);
    }
}
