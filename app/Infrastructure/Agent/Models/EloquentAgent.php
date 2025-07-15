<?php

declare(strict_types=1);

namespace App\Infrastructure\Agent\Models;

use App\Infrastructure\AgentSettings\Models\EloquentAgentSettings;
use App\Infrastructure\Order\Models\EloquentCart;
use App\Infrastructure\Order\Models\EloquentOrder;
use App\Infrastructure\Wallet\Models\EloquentWallet;
use Database\Factories\Infrastructure\Agent\Models\EloquentAgentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

final class EloquentAgent extends Authenticatable
{
    /** @use HasFactory<EloquentAgentFactory> */
    use HasFactory;

    use Notifiable;

    protected $table = 'agents';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'username',
        'email',
        'name',
        'password',
        'agent_type',
        'status',
        'upline_id',
        'phone',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Agent type enum values
     */
    public function getAgentTypeOptions(): array
    {
        return [
            'company' => 'Company',
            'super_senior' => 'Super Senior',
            'senior' => 'Senior',
            'master' => 'Master',
            'agent' => 'Agent',
            'member' => 'Member',
        ];
    }

    /**
     * Check if agent is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get parent agent
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'upline_id');
    }

    /**
     * Get child agents
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'upline_id');
    }

    /**
     * Get all descendant agents (recursive)
     */
    public function descendants(): HasMany
    {
        return $this->hasMany(self::class, 'upline_id')
            ->with('descendants');
    }

    /**
     * Get direct downline agents
     */
    public function directDownlines(): HasMany
    {
        return $this->hasMany(self::class, 'upline_id');
    }

    /**
     * Get carts for this agent
     */
    public function carts(): HasMany
    {
        return $this->hasMany(EloquentCart::class, 'agent_id');
    }

    /**
     * Get orders for this agent
     */
    public function orders(): HasMany
    {
        return $this->hasMany(EloquentOrder::class, 'agent_id');
    }

    /**
     * Get wallets for this agent
     */
    public function wallets(): HasMany
    {
        return $this->hasMany(EloquentWallet::class, 'owner_id');
    }

    /**
     * Get the main wallet for this agent
     */
    public function mainWallet(): HasOne
    {
        return $this->hasOne(EloquentWallet::class, 'owner_id')
            ->where('wallet_type', 'main');
    }

    /**
     * Get the commission wallet for this agent
     */
    public function commissionWallet(): HasOne
    {
        return $this->hasOne(EloquentWallet::class, 'owner_id')
            ->where('wallet_type', 'commission');
    }

    /**
     * Get the bonus wallet for this agent
     */
    public function bonusWallet(): HasOne
    {
        return $this->hasOne(EloquentWallet::class, 'owner_id')
            ->where('wallet_type', 'bonus');
    }

    /**
     * Get agent settings
     */
    public function settings(): HasOne
    {
        return $this->hasOne(EloquentAgentSettings::class, 'agent_id');
    }

    /**
     * Scope for active agents
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for inactive agents
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    /**
     * Scope for suspended agents
     */
    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    /**
     * Scope for agent type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('agent_type', $type);
    }

    /**
     * Scope for agents with upline
     */
    public function scopeWithUpline($query, int $uplineId)
    {
        return $query->where('upline_id', $uplineId);
    }

    /**
     * Scope for company agents
     */
    public function scopeCompany($query)
    {
        return $query->where('agent_type', 'company');
    }

    /**
     * Scope for member agents
     */
    public function scopeMembers($query)
    {
        return $query->where('agent_type', 'member');
    }

    /**
     * Scope for agents with email verification
     */
    public function scopeEmailVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    /**
     * Scope for agents without email verification
     */
    public function scopeEmailUnverified($query)
    {
        return $query->whereNull('email_verified_at');
    }

    /**
     * Get the agent ID (for domain compatibility)
     */
    public function id(): int
    {
        return $this->id;
    }

    /**
     * Get agent display name
     */
    public function getDisplayNameAttribute(): string
    {
        return sprintf('%s (%s)', $this->name, $this->username);
    }

    /**
     * Get agent type display name
     */
    public function getAgentTypeDisplayAttribute(): string
    {
        return $this->getAgentTypeOptions()[$this->agent_type] ?? ucfirst($this->agent_type);
    }

    /**
     * Get agent status display name
     */
    public function getStatusDisplayAttribute(): string
    {
        return ucfirst($this->status);
    }

    /**
     * Check if agent is a company
     */
    public function isCompany(): bool
    {
        return $this->agent_type === 'company';
    }

    /**
     * Check if agent is a member
     */
    public function isMember(): bool
    {
        return $this->agent_type === 'member';
    }

    /**
     * Check if agent can manage another agent
     */
    public function canManage(self $agent): bool
    {
        // Company can manage everyone
        if ($this->isCompany()) {
            return true;
        }

        // Check if the agent is in this agent's downline
        return $this->descendants->contains('id', $agent->id);
    }

    /**
     * Get total wallet balance
     */
    public function getTotalBalanceAttribute(): float
    {
        return $this->wallets->sum('balance');
    }

    /**
     * Get main wallet balance
     */
    public function getMainWalletBalanceAttribute(): float
    {
        return $this->mainWallet?->balance ?? 0;
    }

    /**
     * Get commission wallet balance
     */
    public function getCommissionWalletBalanceAttribute(): float
    {
        return $this->commissionWallet?->balance ?? 0;
    }

    /**
     * Get bonus wallet balance
     */
    public function getBonusWalletBalanceAttribute(): float
    {
        return $this->bonusWallet?->balance ?? 0;
    }

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
