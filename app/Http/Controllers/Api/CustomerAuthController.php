<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CustomerAuthController extends Controller
{
    private function ensureCustomerAuthSchema()
    {
        $required = ['phone_number', 'phone_code', 'first_name', 'last_name', 'api_token', 'otp_code', 'otp_expires_at', 'is_phone_verified'];

        foreach ($required as $column) {
            if (!Schema::hasColumn('users', $column)) {
                return response()->json([
                    'success' => false,
                    'message' => "Customer auth schema is not ready. Please run: php artisan migrate",
                    'missing_column' => $column,
                ], 503);
            }
        }

        return null;
    }

    public function login(Request $request)
    {
        if ($schemaError = $this->ensureCustomerAuthSchema()) {
            return $schemaError;
        }

        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|min:8|max:15',
            'phone_code' => 'nullable|string|max:8',
            'customer_name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $phone = preg_replace('/\D+/', '', (string) $request->phone_number);
        $phoneCode = $request->phone_code ?: '+91';

        $user = User::where('phone_number', $phone)->first();

        if (!$user) {
            $name = trim((string) ($request->customer_name ?: 'Guest User'));
            $user = User::create([
                'name' => $name,
                'email' => null,
                'password' => Hash::make(Str::random(32)),
                'phone_code' => $phoneCode,
                'phone_number' => $phone,
            ]);
        }

        $otp = (string) random_int(100000, 999999);
        $user->otp_code = Hash::make($otp);
        $user->otp_expires_at = Carbon::now()->addMinutes(10);
        $user->save();

        $response = [
            'success' => true,
            'message' => 'OTP sent successfully',
        ];

        // Development-only helper for testing without SMS provider.
        if (config('app.debug')) {
            $response['debug_otp'] = $otp;
        }

        return response()->json($response);
    }

    public function verifyOtp(Request $request)
    {
        if ($schemaError = $this->ensureCustomerAuthSchema()) {
            return $schemaError;
        }

        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|min:8|max:15',
            'otp' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $phone = preg_replace('/\D+/', '', (string) $request->phone_number);
        $user = User::where('phone_number', $phone)->first();

        if (!$user || !$user->otp_code || !$user->otp_expires_at) {
            return response()->json([
                'success' => false,
                'message' => 'OTP not found. Please request OTP again.',
            ], 422);
        }

        if (Carbon::now()->greaterThan(Carbon::parse($user->otp_expires_at))) {
            return response()->json([
                'success' => false,
                'message' => 'OTP expired. Please request OTP again.',
            ], 422);
        }

        if (!Hash::check((string) $request->otp, $user->otp_code)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP',
            ], 422);
        }

        $isAlready = ($user->is_phone_verified && !empty($user->first_name) && !empty($user->last_name)) ? 1 : 0;

        $user->is_phone_verified = true;
        $user->otp_code = null;
        $user->otp_expires_at = null;
        $user->api_token = Str::random(80);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'OTP verified successfully',
            'token' => $user->api_token,
            'is_already' => $isAlready,
            'user_data' => [
                'id' => $user->id,
                'user_id' => $user->id,
                'first_name' => $user->first_name ?? '',
                'last_name' => $user->last_name ?? '',
                'email' => $user->email ?? '',
                'phone' => $user->phone_number ?? '',
                'phone_number' => $user->phone_number ?? '',
                'phone_code' => $user->phone_code ?? '+91',
            ],
        ]);
    }

    public function completeProfile(Request $request)
    {
        if ($schemaError = $this->ensureCustomerAuthSchema()) {
            return $schemaError;
        }

        $validator = Validator::make($request->all(), [
            'phone_number' => 'nullable|string|min:8|max:15',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'nullable|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $this->resolveUserFromRequest($request);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized user',
            ], 401);
        }

        $user->first_name = trim((string) $request->first_name);
        $user->last_name = trim((string) $request->last_name);
        $user->name = trim($user->first_name . ' ' . $user->last_name);

        if ($request->filled('email')) {
            $existing = User::where('email', $request->email)->where('id', '!=', $user->id)->first();
            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email is already in use',
                ], 422);
            }
            $user->email = $request->email;
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profile completed successfully',
            'user_data' => [
                'id' => $user->id,
                'user_id' => $user->id,
                'first_name' => $user->first_name ?? '',
                'last_name' => $user->last_name ?? '',
                'email' => $user->email ?? '',
                'phone' => $user->phone_number ?? '',
                'phone_number' => $user->phone_number ?? '',
                'phone_code' => $user->phone_code ?? '+91',
            ],
        ]);
    }

    private function resolveUserFromRequest(Request $request): ?User
    {
        $authHeader = $request->header('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            $token = trim(substr($authHeader, 7));
            if ($token !== '') {
                $byToken = User::where('api_token', $token)->first();
                if ($byToken) {
                    return $byToken;
                }
            }
        }

        if ($request->filled('phone_number')) {
            $phone = preg_replace('/\D+/', '', (string) $request->phone_number);
            return User::where('phone_number', $phone)->first();
        }

        return null;
    }
}
