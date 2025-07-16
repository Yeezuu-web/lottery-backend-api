<?php

declare(strict_types=1);

namespace App\Domain\Auth\Contracts;

use App\Domain\Agent\Models\Agent;
use App\Domain\Auth\Models\LoginAudit;
use App\Domain\Auth\ValueObjects\DeviceInfo;
use App\Domain\Auth\ValueObjects\JWTToken;
use DateTimeImmutable;
use Illuminate\Http\Request;

interface LoginAuditServiceInterface
{
    /**
     * Record a login attempt
     */
    public function recordAttempt(
        string $username,
        string $audience,
        Request $request,
        array $metadata = []
    ): LoginAudit;

    /**
     * Record a login attempt from event
     */
    public function recordLoginAttempt(
        string $username,
        string $audience,
        DeviceInfo $deviceInfo,
        array $metadata = []
    ): LoginAudit;

    /**
     * Find a recent login attempt
     */
    public function findRecentLoginAttempt(
        string $username,
        string $audience,
        DeviceInfo $deviceInfo
    ): ?LoginAudit;

    /**
     * Mark a login attempt as successful
     */
    public function markAsSuccessful(
        LoginAudit $loginAudit,
        Agent $agent,
        JWTToken $jwtToken,
        ?string $sessionId = null
    ): LoginAudit;

    /**
     * Mark a login attempt as failed
     */
    public function markAsFailed(
        LoginAudit $loginAudit,
        string $failureReason,
        string $username,
        string $audience,
        DeviceInfo $deviceInfo
    ): LoginAudit;

    /**
     * Mark a login attempt as blocked
     */
    public function markAsBlocked(
        LoginAudit $loginAudit,
        string $blockReason,
        string $username,
        string $audience,
        DeviceInfo $deviceInfo
    ): LoginAudit;

    /**
     * Record a logout event
     */
    public function recordLogout(
        string $sessionId,
        string $logoutReason = 'manual'
    ): bool;

    /**
     * Record a session end event
     */
    public function recordSessionEnd(
        Agent $agent,
        string $sessionId,
        string $logoutReason,
        DeviceInfo $deviceInfo
    ): bool;

    /**
     * Record suspicious activity
     */
    public function recordSuspiciousActivity(
        string $username,
        string $audience,
        array $riskFactors,
        string $threatLevel,
        DeviceInfo $deviceInfo,
        array $metadata = []
    ): LoginAudit;

    /**
     * Force logout all sessions for an agent
     */
    public function forceLogoutAgent(int $agentId, string $reason = 'forced'): int;

    /**
     * Check if login should be blocked due to too many failed attempts
     */
    public function shouldBlockLogin(string $username, string $audience, string $ipAddress): bool;

    /**
     * Get the count of failed attempts for a username and audience
     */
    public function getFailedAttemptCount(string $username, string $audience): int;

    /**
     * Get login history for an agent
     */
    public function getAgentLoginHistory(int $agentId, int $limit = 50, int $offset = 0): array;

    /**
     * Get active sessions for an agent
     */
    public function getActiveSessions(int $agentId): array;

    /**
     * Get suspicious login attempts
     */
    public function getSuspiciousAttempts(int $limit = 100, int $offset = 0): array;

    /**
     * Get login statistics for a date range
     */
    public function getLoginStatistics(
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        ?string $audience = null
    ): array;

    /**
     * Get login trends
     */
    public function getLoginTrends(
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        string $groupBy = 'day'
    ): array;

    /**
     * Get top IP addresses by login attempts
     */
    public function getTopIpAddresses(
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        int $limit = 10
    ): array;

    /**
     * Clean up old audit records
     */
    public function cleanupOldRecords(int $daysToKeep = 90): int;
}
