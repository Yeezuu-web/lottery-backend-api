<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Auth\Models\Permission;
use Illuminate\Database\Seeder;

final class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Agent Management
            [
                'name' => 'manage_all_agents',
                'display_name' => 'Manage All Agents',
                'description' => 'Can manage any agent in the system',
                'category' => 'agent_management',
                'agent_types' => ['company'],
            ],
            [
                'name' => 'manage_sub_agents',
                'display_name' => 'Manage Sub Agents',
                'description' => 'Can manage direct and indirect downline agents',
                'category' => 'agent_management',
                'agent_types' => ['company', 'super senior', 'senior', 'master', 'agent'],
            ],
            [
                'name' => 'view_agent_details',
                'display_name' => 'View Agent Details',
                'description' => 'Can view detailed information about agents',
                'category' => 'agent_management',
                'agent_types' => ['company', 'super senior', 'senior', 'master', 'agent'],
            ],
            [
                'name' => 'create_agents',
                'display_name' => 'Create Agents',
                'description' => 'Can create new agents',
                'category' => 'agent_management',
                'agent_types' => ['company', 'super senior', 'senior', 'master', 'agent'],
            ],

            // Reports & Analytics
            [
                'name' => 'view_bal_reports',
                'display_name' => 'View Bal Reports',
                'description' => 'Can view bal reports for all agents',
                'category' => 'reports',
                'agent_types' => ['company', 'super senior', 'senior', 'master', 'agent'],
            ],
            [
                'name' => 'view_daily_reports',
                'display_name' => 'View All Reports',
                'description' => 'Can view reports for all agents',
                'category' => 'reports',
                'agent_types' => ['company', 'super senior', 'senior', 'master', 'agent'],
            ],
            [
                'name' => 'export_reports',
                'display_name' => 'Export Reports',
                'description' => 'Can export reports to various formats',
                'category' => 'reports',
                'agent_types' => ['company', 'super senior'],
            ],

            // Financial Management
            [
                'name' => 'manage_financial_settings',
                'display_name' => 'Manage Financial Settings',
                'description' => 'Can manage commission rates and financial settings',
                'category' => 'financial',
                'agent_types' => ['company', 'super senior'],
            ],
            [
                'name' => 'manage_all_wallets',
                'display_name' => 'Manage All Wallets',
                'description' => 'Can manage wallets for all agents',
                'category' => 'financial',
                'agent_types' => ['company'],
            ],
            [
                'name' => 'manage_sub_wallets',
                'display_name' => 'Manage Sub-Agent Wallets',
                'description' => 'Can manage wallets for downline agents',
                'category' => 'financial',
                'agent_types' => ['company', 'super senior', 'senior', 'master'],
            ],
            [
                'name' => 'manage_own_wallet',
                'display_name' => 'Manage Own Wallet',
                'description' => 'Can manage own wallet transactions',
                'category' => 'financial',
                'agent_types' => ['company', 'super senior', 'senior', 'master', 'agent'],
            ],
            [
                'name' => 'transfer_funds',
                'display_name' => 'Transfer Funds',
                'description' => 'Can transfer funds between wallets',
                'category' => 'financial',
                'agent_types' => ['company', 'super senior', 'senior', 'master', 'agent'],
            ],
            [
                'name' => 'view_transaction_history',
                'display_name' => 'View Transaction History',
                'description' => 'Can view transaction history',
                'category' => 'financial',
                'agent_types' => ['company', 'super senior', 'senior', 'master', 'agent'],
            ],

            // System Management
            [
                'name' => 'manage_system_settings',
                'display_name' => 'Manage System Settings',
                'description' => 'Can manage system-wide settings',
                'category' => 'system',
                'agent_types' => ['company'],
            ],
            [
                'name' => 'manage_permissions',
                'display_name' => 'Manage Permissions',
                'description' => 'Can grant and revoke permissions',
                'category' => 'system',
                'agent_types' => ['company'],
            ],
            [
                'name' => 'grant_permissions',
                'display_name' => 'Grant Permissions',
                'description' => 'Can grant permissions to other agents',
                'category' => 'system',
                'agent_types' => ['company'],
            ],
            [
                'name' => 'revoke_permissions',
                'display_name' => 'Revoke Permissions',
                'description' => 'Can revoke permissions from other agents',
                'category' => 'system',
                'agent_types' => ['company'],
            ],
            [
                'name' => 'view_audit_logs',
                'display_name' => 'View Audit Logs',
                'description' => 'Can view system audit logs',
                'category' => 'system',
                'agent_types' => ['company'],
            ],

            // Betting & Orders (Member-specific)
            [
                'name' => 'place_bets',
                'display_name' => 'Place Bets',
                'description' => 'Can place betting orders',
                'category' => 'betting',
                'agent_types' => ['member'],
            ],
            [
                'name' => 'view_own_bets',
                'display_name' => 'View Own Bets',
                'description' => 'Can view own betting history',
                'category' => 'betting',
                'agent_types' => ['member'],
            ],
            [
                'name' => 'cancel_own_bets',
                'display_name' => 'Cancel Own Bets',
                'description' => 'Can cancel own pending bets',
                'category' => 'betting',
                'agent_types' => ['member'],
            ],

            // Profile Management
            [
                'name' => 'manage_profile',
                'display_name' => 'Manage Profile',
                'description' => 'Can manage own profile information',
                'category' => 'profile',
                'agent_types' => ['company', 'super senior', 'senior', 'master', 'agent', 'member'],
            ],
            [
                'name' => 'change_password',
                'display_name' => 'Change Password',
                'description' => 'Can change own password',
                'category' => 'profile',
                'agent_types' => ['company', 'super senior', 'senior', 'master', 'agent', 'member'],
            ],

            // Dashboard Access
            [
                'name' => 'view_dashboard',
                'display_name' => 'View Dashboard',
                'description' => 'Can access main dashboard',
                'category' => 'dashboard',
                'agent_types' => ['company', 'super senior', 'senior', 'master', 'agent'],
            ],
            [
                'name' => 'view_member_interface',
                'display_name' => 'View Member Interface',
                'description' => 'Can access member betting interface',
                'category' => 'dashboard',
                'agent_types' => ['member'],
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }
    }
}
