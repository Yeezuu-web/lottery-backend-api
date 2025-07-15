<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Domain\Agent\Models\Agent;
use App\Domain\Auth\Contracts\LoginAuditRepositoryInterface;
use App\Domain\Auth\Models\LoginAudit;
use App\Domain\Auth\ValueObjects\DeviceInfo;
use App\Domain\Auth\ValueObjects\JWTToken;
use DateTimeImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final readonly class LoginAuditService
{
    public function __construct(
        private LoginAuditRepositoryInterface $loginAuditRepository
    ) {}

    /**
     * Record a login attempt
     */
    public function recordAttempt(
        string $username,
        string $audience,
        Request $request,
        array $metadata = []
    ): LoginAudit {
        $deviceInfo = DeviceInfo::fromHttpRequest($request);
        $referer = $request->header('Referer');
        $headers = $this->extractRelevantHeaders($request);

        $loginAudit = LoginAudit::createAttempt(
            agentId: null, // Will be set on successful login
            username: $username,
            audience: $audience,
            deviceInfo: $deviceInfo,
            referer: $referer,
            headers: $headers,
            metadata: $metadata
        );

        return $this->loginAuditRepository->save($loginAudit);
    }

    /**
     * Mark a login attempt as successful
     */
    public function markAsSuccessful(
        LoginAudit $loginAudit,
        Agent $agent,
        JWTToken $jwtToken
    ): LoginAudit {
        $sessionId = $this->generateSessionId($agent, $jwtToken);

        $successfulAudit = $loginAudit->markAsSuccessful(
            $agent->id(),
            $agent->agentType()->value(),
            $jwtToken,
            $sessionId
        );

        return $this->loginAuditRepository->save($successfulAudit);
    }

    /**
     * Mark a login attempt as failed
     */
    public function markAsFailed(
        LoginAudit $loginAudit,
        string $failureReason,
        string $username,
        string $audience,
        DeviceInfo $deviceInfo
    ): LoginAudit {
        $riskFactors = $this->assessRiskFactors($username, $audience, $deviceInfo);

        $failedAudit = $loginAudit->markAsFailed($failureReason, $riskFactors);

        return $this->loginAuditRepository->save($failedAudit);
    }

    /**
     * Record a logout event
     */
    public function recordLogout(
        string $sessionId,
        string $logoutReason = 'manual'
    ): bool {
        return $this->loginAuditRepository->markSessionEnded($sessionId, $logoutReason);
    }

    /**
     * Force logout all sessions for an agent
     */
    public function forceLogoutAgent(int $agentId, string $reason = 'forced'): int
    {
        return $this->loginAuditRepository->markAllSessionsEndedForAgent($agentId, $reason);
    }

    /**
     * Check if login should be blocked due to too many failed attempts
     */
    public function shouldBlockLogin(string $username, string $audience, string $ipAddress): bool
    {
        $timeWindow = new DateTimeImmutable('-15 minutes');

        // Check failed attempts by username
        $usernameFailures = $this->loginAuditRepository->countFailedAttempts(
            $username,
            $audience,
            $timeWindow
        );

        // Check failed attempts by IP
        $ipFailures = $this->loginAuditRepository->countFailedAttemptsFromIp(
            $ipAddress,
            $timeWindow
        );

        // Block if too many failures from username or IP
        return $usernameFailures >= 5 || $ipFailures >= 10;
    }

    /**
     * Get login history for an agent
     */
    public function getAgentLoginHistory(int $agentId, int $limit = 50, int $offset = 0): array
    {
        return $this->loginAuditRepository->findByAgentId($agentId, $limit, $offset);
    }

    /**
     * Get active sessions for an agent
     */
    public function getActiveSessions(int $agentId): array
    {
        return $this->loginAuditRepository->findActiveSessions($agentId);
    }

    /**
     * Get suspicious login attempts
     */
    public function getSuspiciousAttempts(int $limit = 100, int $offset = 0): array
    {
        return $this->loginAuditRepository->findSuspiciousAttempts($limit, $offset);
    }

    /**
     * Get login statistics for a date range
     */
    public function getLoginStatistics(
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        ?string $audience = null
    ): array {
        return $this->loginAuditRepository->getLoginStatistics($startDate, $endDate, $audience);
    }

    /**
     * Get login trends
     */
    public function getLoginTrends(
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        string $groupBy = 'day'
    ): array {
        return $this->loginAuditRepository->getLoginTrends($startDate, $endDate, $groupBy);
    }

    /**
     * Get top IP addresses by login attempts
     */
    public function getTopIpAddresses(
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        int $limit = 10
    ): array {
        return $this->loginAuditRepository->getTopIpAddresses($startDate, $endDate, $limit);
    }

    /**
     * Clean up old audit records
     */
    public function cleanupOldRecords(int $daysToKeep = 90): int
    {
        $cutoffDate = new DateTimeImmutable(sprintf('-%d days', $daysToKeep));

        return $this->loginAuditRepository->cleanupOldRecords($cutoffDate);
    }

    /**
     * Generate a unique session ID
     */
    private function generateSessionId(Agent $agent, JWTToken $jwtToken): string
    {
        return 'sess_'.$agent->id().'_'.$jwtToken->getJti().'_'.Str::random(8);
    }

    /**
     * Extract relevant headers for auditing
     */
    private function extractRelevantHeaders(Request $request): array
    {
        $relevantHeaders = [
            'User-Agent',
            'Accept',
            'Accept-Language',
            'Accept-Encoding',
            'X-Forwarded-For',
            'X-Real-IP',
            'X-Forwarded-Proto',
            'Origin',
        ];

        $headers = [];
        foreach ($relevantHeaders as $header) {
            if ($request->hasHeader($header)) {
                $headers[$header] = $request->header($header);
            }
        }

        return $headers;
    }

    /**
     * Assess risk factors for a login attempt
     */
    private function assessRiskFactors(
        string $username,
        string $audience,
        DeviceInfo $deviceInfo
    ): array {
        $riskFactors = [];

        // Check for multiple IP addresses for the same user
        $recentLogins = $this->loginAuditRepository->findByUsername($username, 10);
        $uniqueIps = array_unique(array_map(
            fn ($login) => $login->deviceInfo()->ipAddress(),
            $recentLogins
        ));

        if (count($uniqueIps) > 3) {
            $riskFactors[] = 'multiple_ip_addresses';
        }

        // Check for unusual device type
        $recentDeviceTypes = array_unique(array_map(
            fn ($login) => $login->deviceInfo()->deviceType(),
            $recentLogins
        ));

        if (count($recentDeviceTypes) > 2) {
            $riskFactors[] = 'multiple_device_types';
        }

        // Check for rapid login attempts
        $recentAttempts = $this->loginAuditRepository->countFailedAttempts(
            $username,
            $audience,
            new DateTimeImmutable('-5 minutes')
        );

        if ($recentAttempts >= 3) {
            $riskFactors[] = 'rapid_attempts';
        }

        // Check for unusual time patterns (e.g., login at 3 AM)
        $currentHour = (int) date('H');
        if ($currentHour >= 2 && $currentHour <= 5) {
            $riskFactors[] = 'unusual_time';
        }

        // Check for suspicious IP ranges (could be expanded with IP blacklists)
        $ipAddress = $deviceInfo->ipAddress();
        if ($this->isSuspiciousIp($ipAddress)) {
            $riskFactors[] = 'suspicious_ip';
        }

        return $riskFactors;
    }

    /**
     * Check if an IP address is suspicious
     */
    private function isSuspiciousIp(string $ipAddress): bool
    {
        // Check recent failures from this IP
        $recentFailures = $this->loginAuditRepository->countFailedAttemptsFromIp(
            $ipAddress,
            new DateTimeImmutable('-1 hour')
        );

        return $recentFailures >= 20;
    }
}
