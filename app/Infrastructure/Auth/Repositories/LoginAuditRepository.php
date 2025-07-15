<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth\Repositories;

use App\Domain\Auth\Contracts\LoginAuditRepositoryInterface;
use App\Domain\Auth\Models\LoginAudit;
use App\Domain\Auth\ValueObjects\DeviceInfo;
use App\Domain\Auth\ValueObjects\LoginAuditStatus;
use App\Infrastructure\Auth\Models\EloquentLoginAudit;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final readonly class LoginAuditRepository implements LoginAuditRepositoryInterface
{
    public function __construct(private EloquentLoginAudit $model) {}

    public function save(LoginAudit $loginAudit): LoginAudit
    {
        $eloquentModel = $this->model->newInstance();

        // If ID is set, find existing record
        if ($loginAudit->id() > 0) {
            $eloquentModel = $this->model->find($loginAudit->id()) ?? $eloquentModel;
        }

        $eloquentModel->fill($this->toEloquentArray($loginAudit));
        $eloquentModel->save();

        return $this->toDomainModel($eloquentModel);
    }

    public function findById(int $id): ?LoginAudit
    {
        $eloquentModel = $this->model->find($id);

        return $eloquentModel ? $this->toDomainModel($eloquentModel) : null;
    }

    public function findLatestAttempt(string $username, string $audience): ?LoginAudit
    {
        $eloquentModel = $this->model
            ->forUsername($username)
            ->forAudience($audience)
            ->orderByDesc('attempted_at')
            ->first();

        return $eloquentModel ? $this->toDomainModel($eloquentModel) : null;
    }

    public function findByAgentId(int $agentId, int $limit = 50, int $offset = 0): array
    {
        $eloquentModels = $this->model
            ->forAgent($agentId)
            ->orderByDesc('attempted_at')
            ->limit($limit)
            ->offset($offset)
            ->get();

        return $eloquentModels->map(fn ($model): LoginAudit => $this->toDomainModel($model))->toArray();
    }

    public function findByUsername(string $username, int $limit = 50, int $offset = 0): array
    {
        $eloquentModels = $this->model
            ->forUsername($username)
            ->orderByDesc('attempted_at')
            ->limit($limit)
            ->offset($offset)
            ->get();

        return $eloquentModels->map(fn ($model): LoginAudit => $this->toDomainModel($model))->toArray();
    }

    public function findByIpAddress(string $ipAddress, int $limit = 50, int $offset = 0): array
    {
        $eloquentModels = $this->model
            ->forIpAddress($ipAddress)
            ->orderByDesc('attempted_at')
            ->limit($limit)
            ->offset($offset)
            ->get();

        return $eloquentModels->map(fn ($model): LoginAudit => $this->toDomainModel($model))->toArray();
    }

    public function findBySessionId(string $sessionId): ?LoginAudit
    {
        $eloquentModel = $this->model->where('session_id', $sessionId)->first();

        return $eloquentModel ? $this->toDomainModel($eloquentModel) : null;
    }

    public function findByJwtTokenId(string $jwtTokenId): ?LoginAudit
    {
        $eloquentModel = $this->model->where('jwt_token_id', $jwtTokenId)->first();

        return $eloquentModel ? $this->toDomainModel($eloquentModel) : null;
    }

    public function findByDateRange(
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        int $limit = 100,
        int $offset = 0
    ): array {
        $eloquentModels = $this->model
            ->inDateRange($startDate, $endDate)
            ->orderByDesc('attempted_at')
            ->limit($limit)
            ->offset($offset)
            ->get();

        return $eloquentModels->map(fn ($model): LoginAudit => $this->toDomainModel($model))->toArray();
    }

    public function countFailedAttempts(string $username, string $audience, DateTimeImmutable $since): int
    {
        return $this->model
            ->forUsername($username)
            ->forAudience($audience)
            ->failed()
            ->since($since)
            ->count();
    }

    public function countFailedAttemptsFromIp(string $ipAddress, DateTimeImmutable $since): int
    {
        return $this->model
            ->forIpAddress($ipAddress)
            ->failed()
            ->since($since)
            ->count();
    }

    public function findActiveSessions(int $agentId): array
    {
        $eloquentModels = $this->model
            ->forAgent($agentId)
            ->activeSessions()
            ->orderByDesc('succeeded_at')
            ->get();

        return $eloquentModels->map(fn ($model): LoginAudit => $this->toDomainModel($model))->toArray();
    }

    public function findSuspiciousAttempts(int $limit = 100, int $offset = 0): array
    {
        $eloquentModels = $this->model
            ->suspicious()
            ->orderByDesc('attempted_at')
            ->limit($limit)
            ->offset($offset)
            ->get();

        return $eloquentModels->map(fn ($model): LoginAudit => $this->toDomainModel($model))->toArray();
    }

    public function getLoginStatistics(
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        ?string $audience = null
    ): array {
        $query = $this->model->inDateRange($startDate, $endDate);

        if ($audience !== null && $audience !== '' && $audience !== '0') {
            $query = $query->forAudience($audience);
        }

        return [
            'total_attempts' => $query->count(),
            'successful_logins' => $query->successful()->count(),
            'failed_attempts' => $query->failed()->count(),
            'suspicious_attempts' => $query->suspicious()->count(),
            'unique_users' => $query->distinct('username')->count(),
            'unique_ips' => $query->distinct('ip_address')->count(),
        ];
    }

    public function getTopIpAddresses(
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        int $limit = 10
    ): array {
        return $this->model
            ->inDateRange($startDate, $endDate)
            ->select('ip_address', DB::raw('COUNT(*) as attempt_count'))
            ->groupBy('ip_address')
            ->orderByDesc('attempt_count')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function getLoginTrends(
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        string $groupBy = 'day'
    ): array {
        $dateFormat = match ($groupBy) {
            'hour' => '%Y-%m-%d %H:00:00',
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            default => '%Y-%m-%d'
        };

        return $this->model
            ->inDateRange($startDate, $endDate)
            ->select(
                DB::raw(sprintf("DATE_FORMAT(attempted_at, '%s') as period", $dateFormat)),
                DB::raw('COUNT(*) as total_attempts'),
                DB::raw('COUNT(CASE WHEN status = "success" THEN 1 END) as successful_logins'),
                DB::raw('COUNT(CASE WHEN status = "failed" THEN 1 END) as failed_attempts')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->toArray();
    }

    public function markSessionEnded(string $sessionId, string $logoutReason = 'manual'): bool
    {
        return $this->model
            ->where('session_id', $sessionId)
            ->update([
                'session_ended_at' => now(),
                'logout_reason' => $logoutReason,
                'updated_at' => now(),
            ]) > 0;
    }

    public function markAllSessionsEndedForAgent(int $agentId, string $logoutReason = 'forced'): int
    {
        return $this->model
            ->forAgent($agentId)
            ->activeSessions()
            ->update([
                'session_ended_at' => now(),
                'logout_reason' => $logoutReason,
                'updated_at' => now(),
            ]);
    }

    public function cleanupOldRecords(DateTimeImmutable $olderThan): int
    {
        return $this->model->where('attempted_at', '<', $olderThan)->delete();
    }

    private function toDomainModel(EloquentLoginAudit $eloquentModel): LoginAudit
    {
        $deviceInfo = new DeviceInfo(
            userAgent: $eloquentModel->user_agent ?? '',
            ipAddress: $eloquentModel->ip_address ?? '',
            deviceType: $eloquentModel->device_type,
            browser: $eloquentModel->browser,
            os: $eloquentModel->os,
            country: $eloquentModel->country,
            city: $eloquentModel->city
        );

        return new LoginAudit(
            id: $eloquentModel->id,
            agentId: $eloquentModel->agent_id,
            username: $eloquentModel->username,
            agentType: $eloquentModel->agent_type,
            audience: $eloquentModel->audience,
            status: LoginAuditStatus::fromString($eloquentModel->status),
            failureReason: $eloquentModel->failure_reason,
            attemptedAt: $eloquentModel->attempted_at->toDateTimeImmutable(),
            succeededAt: $eloquentModel->succeeded_at?->toDateTimeImmutable(),
            sessionId: $eloquentModel->session_id,
            jwtTokenId: $eloquentModel->jwt_token_id,
            tokenExpiresAt: $eloquentModel->token_expires_at?->toDateTimeImmutable(),
            sessionEndedAt: $eloquentModel->session_ended_at?->toDateTimeImmutable(),
            logoutReason: $eloquentModel->logout_reason,
            deviceInfo: $deviceInfo,
            isSuspicious: $eloquentModel->is_suspicious,
            riskFactors: $eloquentModel->risk_factors ?? [],
            failedAttemptsCount: $eloquentModel->failed_attempts_count,
            lastFailedAttemptAt: $eloquentModel->last_failed_attempt_at?->toDateTimeImmutable(),
            referer: $eloquentModel->referer,
            headers: $eloquentModel->headers ?? [],
            metadata: $eloquentModel->metadata ?? [],
            createdAt: $eloquentModel->created_at->toDateTimeImmutable(),
            updatedAt: $eloquentModel->updated_at->toDateTimeImmutable()
        );
    }

    private function toEloquentArray(LoginAudit $loginAudit): array
    {
        return [
            'agent_id' => $loginAudit->agentId(),
            'username' => $loginAudit->username(),
            'agent_type' => $loginAudit->agentType(),
            'audience' => $loginAudit->audience(),
            'status' => $loginAudit->status()->value(),
            'failure_reason' => $loginAudit->failureReason(),
            'attempted_at' => $loginAudit->attemptedAt()->format('Y-m-d H:i:s'),
            'succeeded_at' => $loginAudit->succeededAt()?->format('Y-m-d H:i:s'),
            'session_id' => $loginAudit->sessionId(),
            'jwt_token_id' => $loginAudit->jwtTokenId(),
            'token_expires_at' => $loginAudit->tokenExpiresAt()?->format('Y-m-d H:i:s'),
            'session_ended_at' => $loginAudit->sessionEndedAt()?->format('Y-m-d H:i:s'),
            'logout_reason' => $loginAudit->logoutReason(),
            'ip_address' => $loginAudit->deviceInfo()->ipAddress(),
            'user_agent' => $loginAudit->deviceInfo()->userAgent(),
            'device_type' => $loginAudit->deviceInfo()->deviceType(),
            'browser' => $loginAudit->deviceInfo()->browser(),
            'os' => $loginAudit->deviceInfo()->os(),
            'country' => $loginAudit->deviceInfo()->country(),
            'city' => $loginAudit->deviceInfo()->city(),
            'is_suspicious' => $loginAudit->isSuspicious(),
            'risk_factors' => $loginAudit->riskFactors(),
            'failed_attempts_count' => $loginAudit->failedAttemptsCount(),
            'last_failed_attempt_at' => $loginAudit->lastFailedAttemptAt()?->format('Y-m-d H:i:s'),
            'referer' => $loginAudit->referer(),
            'headers' => $loginAudit->headers(),
            'metadata' => $loginAudit->metadata(),
        ];
    }
}
