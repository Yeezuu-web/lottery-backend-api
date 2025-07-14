<?php

namespace App\Http\Controllers\Auth;

use App\Application\Auth\DTOs\AuthenticateUserCommand;
use App\Application\Auth\DTOs\LogoutUserCommand;
use App\Application\Auth\DTOs\RefreshTokenCommand;
use App\Application\Auth\UseCases\AuthenticateUserUseCase;
use App\Application\Auth\UseCases\GetUserProfileUseCase;
use App\Application\Auth\UseCases\LogoutUserUseCase;
use App\Application\Auth\UseCases\RefreshTokenUseCase;
use App\Domain\Auth\Exceptions\AuthenticationException;
use App\Domain\Auth\ValueObjects\JWTToken;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RefreshTokenRequest;
use App\Infrastructure\Auth\Services\AuthenticationService;
use App\Traits\HttpApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class MemberAuthController extends Controller
{
    use HttpApiResponse;

    private readonly AuthenticateUserUseCase $authenticateUserUseCase;

    private readonly RefreshTokenUseCase $refreshTokenUseCase;

    private readonly LogoutUserUseCase $logoutUserUseCase;

    private readonly GetUserProfileUseCase $getUserProfileUseCase;

    private readonly AuthenticationService $authService;

    public function __construct(
        AuthenticateUserUseCase $authenticateUserUseCase,
        RefreshTokenUseCase $refreshTokenUseCase,
        LogoutUserUseCase $logoutUserUseCase,
        GetUserProfileUseCase $getUserProfileUseCase,
        AuthenticationService $authService
    ) {
        $this->authenticateUserUseCase = $authenticateUserUseCase;
        $this->refreshTokenUseCase = $refreshTokenUseCase;
        $this->logoutUserUseCase = $logoutUserUseCase;
        $this->getUserProfileUseCase = $getUserProfileUseCase;
        $this->authService = $authService;
    }

    /**
     * Authenticate member user
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $command = new AuthenticateUserCommand(
                $request->username,
                $request->password,
                'member'
            );

            $result = $this->authenticateUserUseCase->execute($command);

            $response = $this->success($result->toArray(), 'Authentication successful');

            // Set HTTP-only cookie for additional security
            $accessToken = $result->tokenPair->accessToken();
            $refreshToken = $result->tokenPair->refreshToken();

            return $response->withCookie(
                Cookie::make(
                    'member_token',
                    $accessToken->token(),
                    $accessToken->expiresAt()->getTimestamp() / 60, // Convert to minutes
                    '/',
                    null,
                    true, // secure
                    true, // httpOnly
                    false,
                    'strict'
                )
            )->withCookie(
                Cookie::make(
                    'member_refresh_token',
                    $refreshToken->token(),
                    $refreshToken->expiresAt()->getTimestamp() / 60, // Convert to minutes
                    '/',
                    null,
                    true, // secure
                    true, // httpOnly
                    false,
                    'strict'
                )
            );

        } catch (AuthenticationException $e) {
            return $this->error($e->getMessage(), 401);
        } catch (\Exception $e) {
            logger()->error('Authentication failed: '.$e->getMessage());

            return $this->error('Authentication failed', 500);
        }
    }

    /**
     * Refresh access token
     */
    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        try {
            $refreshTokenString = $request->refresh_token ?? $request->cookie('member_refresh_token');

            if (! $refreshTokenString) {
                return $this->error('Refresh token not provided', 401);
            }

            $command = new RefreshTokenCommand($refreshTokenString, 'member');
            $result = $this->refreshTokenUseCase->execute($command);

            $response = $this->success($result->toArray(), 'Token refreshed successfully');

            // Update cookies
            $accessToken = $result->tokenPair->accessToken();
            $newRefreshToken = $result->tokenPair->refreshToken();

            return $response->withCookie(
                Cookie::make(
                    'member_token',
                    $accessToken->token(),
                    $accessToken->expiresAt()->getTimestamp() / 60,
                    '/',
                    null,
                    true,
                    true,
                    false,
                    'strict'
                )
            )->withCookie(
                Cookie::make(
                    'member_refresh_token',
                    $newRefreshToken->token(),
                    $newRefreshToken->expiresAt()->getTimestamp() / 60,
                    '/',
                    null,
                    true,
                    true,
                    false,
                    'strict'
                )
            );

        } catch (AuthenticationException $e) {
            return $this->error($e->getMessage(), 401);
        } catch (\Exception $e) {
            logger()->error('Token refresh failed: '.$e->getMessage());

            return $this->error('Token refresh failed', 500);
        }
    }

    /**
     * Logout user
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $jwtToken = $request->attributes->get('jwt_token');

            if ($jwtToken instanceof JWTToken) {
                $command = new LogoutUserCommand($jwtToken, 'member');
                $this->logoutUserUseCase->execute($command);
            }

            $response = $this->success([], 'Logged out successfully');

            // Clear cookies
            return $response->withCookie(
                Cookie::forget('member_token')
            )->withCookie(
                Cookie::forget('member_refresh_token')
            );

        } catch (AuthenticationException $e) {
            return $this->error($e->getMessage(), 401);
        } catch (\Exception $e) {
            logger()->error('Logout failed: '.$e->getMessage());

            return $this->error('Logout failed', 500);
        }
    }

    /**
     * Get current user profile
     */
    public function profile(Request $request): JsonResponse
    {
        try {
            $jwtToken = $request->attributes->get('jwt_token');

            if (! $jwtToken instanceof JWTToken) {
                return $this->error('Authentication required', 401);
            }

            $result = $this->getUserProfileUseCase->execute($jwtToken, 'member');

            // Add token info to the response
            $responseData = $result->toArray();
            $payload = $jwtToken->payload();

            $responseData['data']['token_info'] = [
                'audience' => $jwtToken->audience(),
                'expires_at' => $jwtToken->expiresAt()->format('c'),
                'issued_at' => date('c', $payload['iat']),
            ];

            return $this->success($responseData['data'], $responseData['message']);

        } catch (AuthenticationException $e) {
            return $this->error($e->getMessage(), 401);
        } catch (\Exception $e) {
            logger()->error('Profile retrieval failed: '.$e->getMessage());

            return $this->error('Profile retrieval failed', 500);
        }
    }
}
