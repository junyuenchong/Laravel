<?php

namespace App\Http\Controllers;

use App\CQRS\Auth\Commands\LoginCommand;
use App\CQRS\Auth\Commands\LogoutCommand;
use App\CQRS\Auth\Commands\RegisterCommand;
use App\CQRS\Auth\Commands\UpdateMeCommand;
use App\CQRS\Auth\Handlers\LoginHandler;
use App\CQRS\Auth\Handlers\LogoutHandler;
use App\CQRS\Auth\Handlers\MeHandler;
use App\CQRS\Auth\Handlers\RegisterHandler;
use App\CQRS\Auth\Handlers\UpdateMeHandler;
use App\CQRS\Auth\Queries\MeQuery;
use App\DTO\Auth\LoginDTO;
use App\DTO\Auth\RegisterDTO;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\UpdateMeRequest;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Authentication endpoints for the SPA.
 *
 * Uses a JWT stored in an HttpOnly cookie (`access_token`).
 */
class AuthController extends Controller
{
    public function csrfCookie()
    {
        // The CSRF middleware attaches `XSRF-TOKEN` automatically.
        return response()->noContent();
    }

    /** Log in with email/password and set the JWT cookie. */
    public function login(LoginRequest $request)
    {
        $dto = new LoginDTO(
            email: (string) $request->validated('email'),
            password: (string) $request->validated('password'),
        );

        return app(LoginHandler::class)->handle(new LoginCommand($dto));
    }

    /** Create account and set the JWT cookie. */
    public function register(RegisterRequest $request)
    {
        $dto = new RegisterDTO(
            name: (string) $request->validated('name'),
            email: (string) $request->validated('email'),
            password: (string) $request->validated('password'),
        );

        return app(RegisterHandler::class)->handle(new RegisterCommand($dto));
    }

    /** Return the current user (cached briefly). */
    public function me(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $payload = app(MeHandler::class)->handle(new MeQuery($user));
        return response()->json(['data' => $payload]);
    }

    /** Update the current user's profile and invalidate the `me` cache. */
    public function updateMe(UpdateMeRequest $request)
    {
        /** @var User $user */
        $user = $request->user();
        $payload = app(UpdateMeHandler::class)->handle(new UpdateMeCommand($user, $request->validated()));
        return response()->json(['data' => $payload]);
    }

    /** Clear auth session and the JWT cookie. */
    public function logout()
    {
        return app(LogoutHandler::class)->handle(new LogoutCommand());
    }
}

