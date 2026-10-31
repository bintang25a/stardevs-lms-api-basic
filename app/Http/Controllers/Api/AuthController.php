<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
  public function register(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'name' => 'required|string|max:255',
      'nim' => 'required|string|unique:users,nim',
      'phone_number' => 'nullable|string|unique:users,phone_number',
      'email' => 'required|email|unique:users,email',
      'role' => 'nullable|in:admin,asisten,praktikan',
      'password' => 'required|string|min:6',
      'institution_code' => [
        'required',
        'string',
        'exists:global_settings,value,key,institution_code'
      ],
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation Errors',
        'errors' => $validator->errors()
      ], 422);
    }

    $user = User::create([
      'name' => $request->name,
      'nim' => $request->nim,
      'phone_number' => $request->phone_number,
      'email' => $request->email,
      'role' => $request->role ?? 'praktikan',
      'password' => Hash::make($request->password),
    ]);

    if ($user->email) {
      event(new Registered($user));
    }

    return response()->json([
      'success' => true,
      'message' => 'Registration successful. Please check your email for confirmation.',
    ], 201);
  }

  public function verify(Request $request, string $id)
  {
    $user = User::findOrFail($id);

    if (! hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
      return response()->json([
        'success' => false,
        'message' => 'Invalid verification link.'
      ], 403);
    }

    if ($user->hasVerifiedEmail()) {
      return response()->json([
        'success' => true,
        'message' => 'Email already verified.'
      ], 200);
    }

    if ($user->markEmailAsVerified()) {
      event(new Verified($user));
    }

    return response()->json([
      'success' => true,
      'message' => 'Email successfully verified!'
    ], 200);
  }

  public function login(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'login' => 'required|string',
      'password' => 'required|string',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation Errors',
        'errors' => $validator->errors()
      ], 422);
    }

    $user = User::where('email', $request->login)
      ->orWhere('nim', $request->login)
      ->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
      return response()->json([
        'success' => false,
        'message' => 'Invalid login credentials',
      ], 401);
    }

    if (!$user->email_verified_at) {
      return response()->json([
        'success' => false,
        'message' => 'Check your email to verifiy account',
      ], 401);
    }

    $user->tokens()->delete();
    $token = $user->createToken('auth_token')->plainTextToken;

    $user = [
      'name' => $user->name,
      'email' => $user->email,
      'role' => $user->role
    ];

    return response()->json([
      'success' => true,
      'message' => 'Login successful',
      'data' => $user,
      'access_token' => $token,
      'token_type' => 'Bearer'
    ], 200);
  }

  public function profile(Request $request)
  {
    return response()->json([
      'success' => true,
      'message' => 'User profile data',
      'data' => $request->user()
    ], 200);
  }

  public function logout(Request $request)
  {
    $request->user()->currentAccessToken()->delete();

    return response()->json([
      'success' => true,
      'message' => 'Logout successful'
    ], 200);
  }
}
