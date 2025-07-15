<?php

declare(strict_types=1);

namespace App\Domain\Auth\Contracts;

use App\Domain\Auth\Models\LoginAudit;
use DateTimeImmutable;

interface LoginAuditRepositoryInterface
{
    /**
     * Save a login audit record
     */
    public function save(LoginAudit $loginAudit): LoginAudit;

    /**
     * Find a login audit record by ID
     */
    public function findById(int $id): ?LoginAudit;

    /**
     * Find the latest login audit record for a username and audience
     */
    public function findLatestAttempt(string $username, string $audience): ?LoginAudit;

    /**
     * Find login audit records by agent ID
     */
    public function findByAgentId(int $agentId, int $limit = 50, int $offset = 0): array;

    /**
     * Find login audit records by username
     */
    public function findByUsername(string $username, int $limit = 50, int $offset = 0): array;

    /**
     * Find login audit records by IP address
     */
    public function findByIpAddress(string $ipAddress, int $limit = 50, int $offset = 0): array;

    /**
     * Find login audit records by session ID
     */
    public function findBySessionId(string $sessionId): ?LoginAudit;

    /**
     * Find login audit records by JWT token ID
     */
    public function findByJwtTokenId(string $jwtTokenId): ?LoginAudit;

    /**
     * Find login audit records within a date range
     */
    public function findByDateRange(
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        int $limit = 100,
        int $offset = 0
    ): array;

    /**
     * Count failed attempts for a username within a time period
     */
    public function countFailedAttempts(string $username, string $audience, DateTimeImmutable $since): int;

    /**
     * Count failed attempts for an IP address within a time period
     */
    public function countFailedAttemptsFromIp(string $ipAddress, DateTimeImmutable $since): int;

    /**
     * Find active sessions for an agent
     */
    public function findActiveSessions(int $agentId): array;

    /**
     * Find suspicious login attempts
     */
    public function findSuspiciousAttempts(int $limit = 100, int $offset = 0): array;

    /**
     * Get login statistics for a specific period
     */
    public function getLoginStatistics(
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        ?string $audience = null
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
     * Get login trends by day
     */
    public function getLoginTrends(
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        string $groupBy = 'day'
    ): array;

    /**
     * Mark session as ended
     */
    public function markSessionEnded(string $sessionId, string $logoutReason = 'manual'): bool;

    /**
     * Mark all sessions for an agent as ended
     */
    public function markAllSessionsEndedForAgent(int $agentId, string $logoutReason = 'forced'): int;

    /**
     * Clean up old audit records
     */
    public function cleanupOldRecords(DateTimeImmutable $olderThan): int;
}
