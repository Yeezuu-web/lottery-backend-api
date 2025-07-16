<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth\Models;

use App\Infrastructure\Agent\Models\EloquentAgent;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class EloquentLoginAudit extends Model
{
    public $timestamps = true;

    protected $table = 'login_audit';

    protected $fillable = [
        'agent_id',
        'username',
        'agent_type',
        'audience',
        'status',
        'failure_reason',
        'attempted_at',
        'succeeded_at',
        'session_id',
        'jwt_token_id',
        'token_expires_at',
        'session_ended_at',
        'logout_reason',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'os',
        'country',
        'city',
        'is_suspicious',
        'risk_factors',
        'failed_attempts_count',
        'last_failed_attempt_at',
        'referer',
        'headers',
        'metadata',
    ];

    protected $casts = [
        'agent_id' => 'integer',
        'attempted_at' => 'datetime',
        'succeeded_at' => 'datetime',
        'token_expires_at' => 'datetime',
        'session_ended_at' => 'datetime',
        'last_failed_attempt_at' => 'datetime',
        'is_suspicious' => 'boolean',
        'risk_factors' => 'array',
        'failed_attempts_count' => 'integer',
        'headers' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the agent that owns this login audit record
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(EloquentAgent::class, 'agent_id');
    }

    /**
     * Scope for successful logins
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope for failed logins
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for suspicious logins
     */
    public function scopeSuspicious($query)
    {
        return $query->where('is_suspicious', true);
    }

    /**
     * Scope for active sessions
     */
    public function scopeActiveSessions($query)
    {
        return $query->where('status', 'success')
            ->whereNull('session_ended_at');
    }

    /**
     * Scope for specific audience
     */
    public function scopeForAudience($query, string $audience)
    {
        return $query->where('audience', $audience);
    }

    /**
     * Scope for specific agent
     */
    public function scopeForAgent($query, int $agentId)
    {
        return $query->where('agent_id', $agentId);
    }

    /**
     * Scope for specific username
     */
    public function scopeForUsername($query, string $username)
    {
        return $query->where('username', $username);
    }

    /**
     * Scope for specific IP address
     */
    public function scopeForIpAddress($query, string $ipAddress)
    {
        return $query->where('ip_address', $ipAddress);
    }

    /**
     * Scope for date range
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('attempted_at', [$startDate, $endDate]);
    }

    /**
     * Scope for recent records
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('attempted_at', '>=', Carbon::now()->subHours($hours));
    }

    /**
     * Scope for records since a specific date
     */
    public function scopeSince($query, $date)
    {
        return $query->where('attempted_at', '>=', $date);
    }
}
