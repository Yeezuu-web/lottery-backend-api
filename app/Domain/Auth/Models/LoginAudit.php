<?php

declare(strict_types=1);

namespace App\Domain\Auth\Models;

use App\Domain\Auth\ValueObjects\DeviceInfo;
use App\Domain\Auth\ValueObjects\JWTToken;
use App\Domain\Auth\ValueObjects\LoginAuditStatus;
use DateTimeImmutable;

final readonly class LoginAudit
{
    public function __construct(
        private int $id,
        private ?int $agentId,
        private string $username,
        private ?string $agentType,
        private string $audience,
        private LoginAuditStatus $status,
        private ?string $failureReason,
        private DateTimeImmutable $attemptedAt,
        private ?DateTimeImmutable $succeededAt,
        private ?string $sessionId,
        private ?string $jwtTokenId,
        private ?DateTimeImmutable $tokenExpiresAt,
        private ?DateTimeImmutable $sessionEndedAt,
        private ?string $logoutReason,
        private DeviceInfo $deviceInfo,
        private bool $isSuspicious,
        private array $riskFactors,
        private int $failedAttemptsCount,
        private ?DateTimeImmutable $lastFailedAttemptAt,
        private ?string $referer,
        private array $headers,
        private array $metadata,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt
    ) {}

    public static function createAttempt(
        ?int $agentId,
        string $username,
        string $audience,
        DeviceInfo $deviceInfo,
        ?string $referer = null,
        array $headers = [],
        array $metadata = []
    ): self {
        $now = new DateTimeImmutable();

        return new self(
            id: 0, // Will be set by repository
            agentId: $agentId,
            username: $username,
            agentType: null,
            audience: $audience,
            status: LoginAuditStatus::failed(), // Default to failed, will be updated on success
            failureReason: null,
            attemptedAt: $now,
            succeededAt: null,
            sessionId: null,
            jwtTokenId: null,
            tokenExpiresAt: null,
            sessionEndedAt: null,
            logoutReason: null,
            deviceInfo: $deviceInfo,
            isSuspicious: false,
            riskFactors: [],
            failedAttemptsCount: 0,
            lastFailedAttemptAt: null,
            referer: $referer,
            headers: $headers,
            metadata: $metadata,
            createdAt: $now,
            updatedAt: $now
        );
    }

    public function markAsSuccessful(
        int $agentId,
        string $agentType,
        JWTToken $jwtToken,
        string $sessionId
    ): self {
        return new self(
            $this->id,
            $agentId,
            $this->username,
            $agentType,
            $this->audience,
            LoginAuditStatus::success(),
            null,
            $this->attemptedAt,
            new DateTimeImmutable(),
            $sessionId,
            $jwtToken->getJti(),
            $jwtToken->expiresAt(),
            null,
            null,
            $this->deviceInfo,
            $this->isSuspicious,
            $this->riskFactors,
            0, // Reset failed attempts count on success
            null,
            $this->referer,
            $this->headers,
            $this->metadata,
            $this->createdAt,
            new DateTimeImmutable()
        );
    }

    public function markAsFailed(string $failureReason, array $riskFactors = []): self
    {
        $isSuspicious = $this->determineIfSuspicious($riskFactors);

        return new self(
            $this->id,
            $this->agentId,
            $this->username,
            $this->agentType,
            $this->audience,
            LoginAuditStatus::failed(),
            $failureReason,
            $this->attemptedAt,
            null,
            null,
            null,
            null,
            null,
            null,
            $this->deviceInfo,
            $isSuspicious,
            array_merge($this->riskFactors, $riskFactors),
            $this->failedAttemptsCount + 1,
            new DateTimeImmutable(),
            $this->referer,
            $this->headers,
            $this->metadata,
            $this->createdAt,
            new DateTimeImmutable()
        );
    }

    public function markAsLoggedOut(string $logoutReason = 'manual'): self
    {
        return new self(
            $this->id,
            $this->agentId,
            $this->username,
            $this->agentType,
            $this->audience,
            $this->status,
            $this->failureReason,
            $this->attemptedAt,
            $this->succeededAt,
            $this->sessionId,
            $this->jwtTokenId,
            $this->tokenExpiresAt,
            new DateTimeImmutable(),
            $logoutReason,
            $this->deviceInfo,
            $this->isSuspicious,
            $this->riskFactors,
            $this->failedAttemptsCount,
            $this->lastFailedAttemptAt,
            $this->referer,
            $this->headers,
            $this->metadata,
            $this->createdAt,
            new DateTimeImmutable()
        );
    }

    public function id(): int
    {
        return $this->id;
    }

    public function agentId(): ?int
    {
        return $this->agentId;
    }

    public function username(): string
    {
        return $this->username;
    }

    public function agentType(): ?string
    {
        return $this->agentType;
    }

    public function audience(): string
    {
        return $this->audience;
    }

    public function status(): LoginAuditStatus
    {
        return $this->status;
    }

    public function failureReason(): ?string
    {
        return $this->failureReason;
    }

    public function attemptedAt(): DateTimeImmutable
    {
        return $this->attemptedAt;
    }

    public function succeededAt(): ?DateTimeImmutable
    {
        return $this->succeededAt;
    }

    public function sessionId(): ?string
    {
        return $this->sessionId;
    }

    public function jwtTokenId(): ?string
    {
        return $this->jwtTokenId;
    }

    public function tokenExpiresAt(): ?DateTimeImmutable
    {
        return $this->tokenExpiresAt;
    }

    public function sessionEndedAt(): ?DateTimeImmutable
    {
        return $this->sessionEndedAt;
    }

    public function logoutReason(): ?string
    {
        return $this->logoutReason;
    }

    public function deviceInfo(): DeviceInfo
    {
        return $this->deviceInfo;
    }

    public function isSuspicious(): bool
    {
        return $this->isSuspicious;
    }

    public function riskFactors(): array
    {
        return $this->riskFactors;
    }

    public function failedAttemptsCount(): int
    {
        return $this->failedAttemptsCount;
    }

    public function lastFailedAttemptAt(): ?DateTimeImmutable
    {
        return $this->lastFailedAttemptAt;
    }

    public function referer(): ?string
    {
        return $this->referer;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function metadata(): array
    {
        return $this->metadata;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isSuccessful(): bool
    {
        return $this->status->isSuccess();
    }

    public function isActive(): bool
    {
        return $this->isSuccessful() && $this->sessionEndedAt === null;
    }

    public function getSessionDuration(): ?int
    {
        if ($this->succeededAt === null) {
            return null;
        }

        $endTime = $this->sessionEndedAt ?? new DateTimeImmutable();

        return $endTime->getTimestamp() - $this->succeededAt->getTimestamp();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'agent_id' => $this->agentId,
            'username' => $this->username,
            'agent_type' => $this->agentType,
            'audience' => $this->audience,
            'status' => $this->status->value(),
            'failure_reason' => $this->failureReason,
            'attempted_at' => $this->attemptedAt->format('Y-m-d H:i:s'),
            'succeeded_at' => $this->succeededAt?->format('Y-m-d H:i:s'),
            'session_id' => $this->sessionId,
            'jwt_token_id' => $this->jwtTokenId,
            'token_expires_at' => $this->tokenExpiresAt?->format('Y-m-d H:i:s'),
            'session_ended_at' => $this->sessionEndedAt?->format('Y-m-d H:i:s'),
            'logout_reason' => $this->logoutReason,
            'device_info' => $this->deviceInfo->toArray(),
            'is_suspicious' => $this->isSuspicious,
            'risk_factors' => $this->riskFactors,
            'failed_attempts_count' => $this->failedAttemptsCount,
            'last_failed_attempt_at' => $this->lastFailedAttemptAt?->format('Y-m-d H:i:s'),
            'referer' => $this->referer,
            'headers' => $this->headers,
            'metadata' => $this->metadata,
            'session_duration' => $this->getSessionDuration(),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }

    private function determineIfSuspicious(array $riskFactors): bool
    {
        // Mark as suspicious if there are any risk factors
        if (! empty($riskFactors)) {
            return true;
        }

        // Mark as suspicious if there are multiple recent failures
        if ($this->failedAttemptsCount >= 3) {
            return true;
        }

        // Add more suspicious behavior detection logic here
        return false;
    }
}
