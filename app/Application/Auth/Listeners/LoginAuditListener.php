<?php

declare(strict_types=1);

namespace App\Application\Auth\Listeners;

use App\Domain\Auth\Events\LoginAttempted;
use App\Domain\Auth\Events\LoginBlocked;
use App\Domain\Auth\Events\LoginFailed;
use App\Domain\Auth\Events\LoginSuccessful;
use App\Domain\Auth\Events\SessionEnded;
use App\Domain\Auth\Events\SuspiciousActivityDetected;
use App\Domain\Auth\Services\LoginAuditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

final readonly class LoginAuditListener implements ShouldQueue
{
    public function __construct(
        private LoginAuditService $loginAuditService
    ) {}

    /**
     * Handle login attempted event
     */
    public function handleLoginAttempted(LoginAttempted $event): void
    {
        try {
            $this->loginAuditService->recordLoginAttempt(
                $event->username(),
                $event->audience(),
                $event->deviceInfo()
            );
        } catch (\Exception $e) {
            Log::error('Failed to record login attempt', [
                'username' => $event->username(),
                'audience' => $event->audience(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle login successful event
     */
    public function handleLoginSuccessful(LoginSuccessful $event): void
    {
        try {
            // Find the login audit record by username and device info
            $loginAudit = $this->loginAuditService->findRecentLoginAttempt(
                $event->agent()->username()->value(),
                $event->audience(),
                $event->deviceInfo()
            );

            if ($loginAudit) {
                $this->loginAuditService->markAsSuccessful(
                    $loginAudit,
                    $event->agent(),
                    $event->accessToken(),
                    $event->sessionId()
                );
            }
        } catch (\Exception $e) {
            Log::error('Failed to record successful login', [
                'username' => $event->agent()->username()->value(),
                'audience' => $event->audience(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle login failed event
     */
    public function handleLoginFailed(LoginFailed $event): void
    {
        try {
            // Find the login audit record by username and device info
            $loginAudit = $this->loginAuditService->findRecentLoginAttempt(
                $event->username(),
                $event->audience(),
                $event->deviceInfo()
            );

            if ($loginAudit) {
                $this->loginAuditService->markAsFailed(
                    $loginAudit,
                    $event->failureReason(),
                    $event->username(),
                    $event->audience(),
                    $event->deviceInfo()
                );
            }
        } catch (\Exception $e) {
            Log::error('Failed to record failed login', [
                'username' => $event->username(),
                'audience' => $event->audience(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle login blocked event
     */
    public function handleLoginBlocked(LoginBlocked $event): void
    {
        try {
            // Find the login audit record by username and device info
            $loginAudit = $this->loginAuditService->findRecentLoginAttempt(
                $event->username(),
                $event->audience(),
                $event->deviceInfo()
            );

            if ($loginAudit) {
                $this->loginAuditService->markAsBlocked(
                    $loginAudit,
                    $event->blockReason(),
                    $event->username(),
                    $event->audience(),
                    $event->deviceInfo()
                );
            }
        } catch (\Exception $e) {
            Log::error('Failed to record blocked login', [
                'username' => $event->username(),
                'audience' => $event->audience(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle session ended event
     */
    public function handleSessionEnded(SessionEnded $event): void
    {
        try {
            $this->loginAuditService->recordSessionEnd(
                $event->agent(),
                $event->sessionId(),
                $event->logoutReason(),
                $event->deviceInfo()
            );
        } catch (\Exception $e) {
            Log::error('Failed to record session end', [
                'username' => $event->agent()->username()->value(),
                'session_id' => $event->sessionId(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle suspicious activity detected event
     */
    public function handleSuspiciousActivityDetected(SuspiciousActivityDetected $event): void
    {
        try {
            $this->loginAuditService->recordSuspiciousActivity(
                $event->username(),
                $event->audience(),
                $event->riskFactors(),
                $event->threatLevel(),
                $event->deviceInfo(),
                $event->metadata()
            );
        } catch (\Exception $e) {
            Log::error('Failed to record suspicious activity', [
                'username' => $event->username(),
                'audience' => $event->audience(),
                'threat_level' => $event->threatLevel(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
