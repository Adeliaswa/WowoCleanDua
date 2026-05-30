<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use OpenApi\Attributes as OA;

#[OA\Info(
    title: "WowoClean API",
    version: "1.0.0",
    description: "API untuk sistem manajemen kontainer limbah B3 WowoClean"
)]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT"
)]
#[OA\Server(url: "http://127.0.0.1:8000")]
class AuthController extends Controller
{
    #[OA\Post(
        path: "/api/v1/login",
        summary: "Login dan dapatkan JWT token",
        tags: ["Auth"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email", "password"],
                properties: [
                    new OA\Property(property: "email",    type: "string", example: "admin@wowoclean.com"),
                    new OA\Property(property: "password", type: "string", example: "password123"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Login berhasil, token dikembalikan"),
            new OA\Response(response: 401, description: "Kredensial salah"),
        ]
    )]
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = auth('api')->user();

        return response()->json([
            'message' => 'Login successful',
            'token'   => $token,
            'user'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
            ],
        ]);
    }

    #[OA\Get(
        path: "/api/v1/profile",
        summary: "Lihat profil user yang sedang login",
        tags: ["Auth"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Data profil user"),
            new OA\Response(response: 401, description: "Unauthenticated"),
        ]
    )]
    public function profile()
    {
        return response()->json(auth('api')->user());
    }

    #[OA\Post(
        path: "/api/v1/logout",
        summary: "Logout dan invalidate token",
        tags: ["Auth"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Logout berhasil"),
        ]
    )]
    public function logout()
    {
        auth('api')->logout();
        return response()->json(['message' => 'Logged out successfully']);
    }
}