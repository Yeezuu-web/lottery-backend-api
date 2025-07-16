# 🔐 **Database-Driven Authorization System**

## 📋 **Overview**

This database-driven authorization system provides a robust, flexible, and scalable permission management system for the lottery application. Unlike role-based systems, this implementation uses **individual permissions** stored in the database with support for **hierarchical inheritance**, **temporary permissions**, and **audit trails**.

## 🎯 **Key Features**

### ✅ **Advantages over Previous System**

-   **Dynamic permissions** - Add/remove permissions without code changes
-   **Per-agent customization** - Individual agents can have unique permissions
-   **Audit trail** - Track who granted/revoked permissions and when
-   **Hierarchical inheritance** - Agents can inherit permissions from upline
-   **Temporary permissions** - Set expiration dates for permissions
-   **Performance optimized** - Cached database queries for fast permission checks
-   **Flexible metadata** - Store additional context with permissions

### 🚀 **Core Components**

1. **Permission Model** - Defines available permissions
2. **AgentPermission Model** - Tracks individual permission grants
3. **DatabaseAuthorizationService** - Core permission logic
4. **DatabaseAuthorizationMiddleware** - Route-level protection
5. **Permission Seeder** - Default permissions setup

## 🗄️ **Database Structure**

### **`permissions` Table**

```sql
CREATE TABLE permissions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) UNIQUE NOT NULL,         -- e.g., 'manage_agents'
    display_name VARCHAR(255) NOT NULL,        -- e.g., 'Manage Agents'
    description TEXT,                          -- Human-readable description
    category VARCHAR(255) NOT NULL,            -- e.g., 'agent_management'
    agent_types JSON,                          -- Which agent types can have this
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### **`agent_permissions` Table**

```sql
CREATE TABLE agent_permissions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    agent_id BIGINT NOT NULL,                  -- Who has the permission
    permission_id BIGINT NOT NULL,             -- What permission
    granted_by BIGINT,                         -- Who granted it
    granted_at TIMESTAMP,                      -- When granted
    expires_at TIMESTAMP,                      -- Optional expiration
    is_active BOOLEAN DEFAULT TRUE,
    metadata JSON,                             -- Additional context
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    UNIQUE KEY unique_agent_permission (agent_id, permission_id),
    FOREIGN KEY (agent_id) REFERENCES agents(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    FOREIGN KEY (granted_by) REFERENCES agents(id) ON DELETE SET NULL
);
```

## 🔧 **Installation & Setup**

### **1. Run Migrations**

```bash
php artisan migrate
```

### **2. Seed Default Permissions**

```bash
php artisan db:seed --class=PermissionsSeeder
```

### **3. Sync Agent Permissions**

```bash
# This will assign default permissions to existing agents
php artisan tinker
>> app(\App\Domain\Auth\Services\DatabaseAuthorizationService::class)->syncDefaultPermissions(1); // For each agent
```

## 🎛️ **Permission Categories**

### **Agent Management**

-   `manage_all_agents` - Manage any agent (Company only)
-   `manage_sub_agents` - Manage downline agents
-   `view_agent_details` - View agent information
-   `create_agents` - Create new agents

### **Reports & Analytics**

-   `view_all_reports` - View all reports (Company only)
-   `view_sub_reports` - View downline reports
-   `view_own_reports` - View own reports
-   `export_reports` - Export reports to files

### **Financial Management**

-   `manage_financial_settings` - Manage commission rates
-   `manage_all_wallets` - Manage all wallets (Company only)
-   `manage_sub_wallets` - Manage downline wallets
-   `manage_own_wallet` - Manage own wallet
-   `transfer_funds` - Transfer funds between wallets

### **System Management**

-   `manage_system_settings` - System-wide settings (Company only)
-   `manage_permissions` - Grant/revoke permissions
-   `view_audit_logs` - View system audit logs

### **Betting & Orders**

-   `place_bets` - Place betting orders (Members only)
-   `view_own_bets` - View own betting history
-   `cancel_own_bets` - Cancel pending bets

## 💻 **Usage Examples**

### **1. Check Permissions in Controllers**

```php
// app/Http/Controllers/Agent/AgentController.php
use App\Domain\Auth\Services\DatabaseAuthorizationService;

class AgentController extends Controller
{
    public function __construct(
        private DatabaseAuthorizationService $authService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $agentId = $request->attributes->get('agent_id');

        // Check if agent can manage agents
        if (!$this->authService->hasPermission($agentId, 'manage_sub_agents')) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        // Check multiple permissions
        if (!$this->authService->hasAnyPermission($agentId, ['manage_all_agents', 'manage_sub_agents'])) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        // ... rest of controller logic
    }
}
```

### **2. Route Protection with Middleware**

```php
// routes/api.php
Route::middleware(['upline.auth', 'authorize:manage_sub_agents'])->group(function () {
    Route::get('/agents', [AgentController::class, 'index']);
    Route::post('/agents', [AgentController::class, 'store']);
});

// Multiple permissions (agent needs ANY of these)
Route::middleware(['upline.auth', 'authorize:manage_all_agents,manage_sub_agents'])->group(function () {
    Route::get('/agents/{id}', [AgentController::class, 'show']);
});

// Financial operations
Route::middleware(['upline.auth', 'authorize:manage_financial_settings'])->group(function () {
    Route::post('/agent-settings', [AgentSettingsController::class, 'store']);
    Route::patch('/agent-settings/{id}/commission', [AgentSettingsController::class, 'updateCommission']);
});
```

### **3. Grant/Revoke Permissions**

```php
// Grant permission to an agent
$authService = app(\App\Domain\Auth\Services\DatabaseAuthorizationService::class);

// Grant permanent permission
$authService->grantPermission(
    agentId: 5,
    permissionName: 'manage_sub_agents',
    grantedBy: 1 // Company agent ID
);

// Grant temporary permission (expires in 30 days)
$authService->grantPermission(
    agentId: 5,
    permissionName: 'view_all_reports',
    grantedBy: 1,
    expiresAt: now()->addDays(30)
);

// Grant with metadata
$authService->grantPermission(
    agentId: 5,
    permissionName: 'manage_sub_wallets',
    grantedBy: 1,
    metadata: ['max_amount' => 10000, 'restricted_types' => ['bonus']]
);

// Revoke permission
$authService->revokePermission(
    agentId: 5,
    permissionName: 'manage_sub_agents',
    revokedBy: 1
);
```

### **4. Advanced Permission Checking**

```php
$authService = app(\App\Domain\Auth\Services\DatabaseAuthorizationService::class);

// Check if agent can manage another specific agent
$canManage = $authService->canManageAgent(
    managerId: 2,
    targetAgentId: 5
);

// Check if agent can manage wallets
$canManageWallets = $authService->canManageWallets(2);

// Check if agent can view reports for specific agent
$canViewReports = $authService->canViewReports(
    agentId: 2,
    targetAgentId: 5
);

// Get all permissions for an agent
$permissions = $authService->getAgentPermissions(2);
// Returns: ['manage_sub_agents', 'view_sub_reports', 'manage_sub_wallets']
```

### **5. Permission Inheritance Chain**

```php
// Get detailed permission information
$chain = $authService->getPermissionInheritanceChain(5);

// Returns:
// [
//     'direct' => ['manage_sub_agents', 'view_own_reports'],
//     'inherited' => ['view_sub_reports'] // From upline
// ]
```

### **6. Bulk Permission Management**

```php
// Grant multiple permissions to multiple agents
$authService->bulkUpdatePermissions(
    agentIds: [5, 6, 7],
    permissions: ['manage_sub_agents', 'view_sub_reports'],
    grantedBy: 1
);
```

## 🎯 **Frontend Integration**

### **1. Permission-Based UI (React)**

```typescript
// hooks/usePermissions.ts
interface Permission {
    name: string;
    display_name: string;
    category: string;
}

export const usePermissions = () => {
    const { user } = useAuth();

    const hasPermission = (permission: string): boolean => {
        return user?.permissions?.includes(permission) ?? false;
    };

    const hasAnyPermission = (permissions: string[]): boolean => {
        return permissions.some((p) => hasPermission(p));
    };

    const canManageAgents = (): boolean => {
        return hasAnyPermission(["manage_all_agents", "manage_sub_agents"]);
    };

    const canManageFinancials = (): boolean => {
        return hasPermission("manage_financial_settings");
    };

    return {
        hasPermission,
        hasAnyPermission,
        canManageAgents,
        canManageFinancials,
    };
};
```

### **2. Conditional Rendering**

```typescript
// components/Navigation.tsx
export const Navigation: React.FC = () => {
    const { hasPermission, canManageAgents } = usePermissions();

    return (
        <nav>
            {hasPermission("view_dashboard") && (
                <NavItem href="/dashboard">Dashboard</NavItem>
            )}

            {canManageAgents() && (
                <NavItem href="/agents">Agent Management</NavItem>
            )}

            {hasPermission("manage_financial_settings") && (
                <NavItem href="/financial-settings">Financial Settings</NavItem>
            )}

            {hasPermission("view_all_reports") && (
                <NavItem href="/reports">All Reports</NavItem>
            )}
        </nav>
    );
};
```

### **3. Permission Management UI**

```typescript
// components/PermissionManager.tsx
export const PermissionManager: React.FC<{ agentId: number }> = ({
    agentId,
}) => {
    const [permissions, setPermissions] = useState<Permission[]>([]);
    const [availablePermissions, setAvailablePermissions] = useState<
        Permission[]
    >([]);

    const grantPermission = async (
        permissionName: string,
        expiresAt?: string
    ) => {
        const response = await fetch(`/api/v1/permissions/grant`, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                agent_id: agentId,
                permission_name: permissionName,
                expires_at: expiresAt,
            }),
            credentials: "include",
        });

        if (response.ok) {
            // Refresh permissions list
            fetchPermissions();
        }
    };

    const revokePermission = async (permissionName: string) => {
        const response = await fetch(`/api/v1/permissions/revoke`, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                agent_id: agentId,
                permission_name: permissionName,
            }),
            credentials: "include",
        });

        if (response.ok) {
            fetchPermissions();
        }
    };

    return (
        <div className="permission-manager">
            <h3>Current Permissions</h3>
            {permissions.map((permission) => (
                <div key={permission.name} className="permission-item">
                    <span>{permission.display_name}</span>
                    <button onClick={() => revokePermission(permission.name)}>
                        Revoke
                    </button>
                </div>
            ))}

            <h3>Available Permissions</h3>
            {availablePermissions.map((permission) => (
                <div key={permission.name} className="permission-item">
                    <span>{permission.display_name}</span>
                    <button onClick={() => grantPermission(permission.name)}>
                        Grant
                    </button>
                </div>
            ))}
        </div>
    );
};
```

## 🔧 **Permission Management Commands**

### **Create Custom Artisan Commands**

```php
// app/Console/Commands/GrantPermissionCommand.php
<?php

namespace App\Console\Commands;

use App\Domain\Auth\Services\DatabaseAuthorizationService;
use Illuminate\Console\Command;

class GrantPermissionCommand extends Command
{
    protected $signature = 'permission:grant {agent_id} {permission} {--granted-by=1} {--expires=}';
    protected $description = 'Grant permission to an agent';

    public function handle(DatabaseAuthorizationService $authService): void
    {
        $agentId = $this->argument('agent_id');
        $permission = $this->argument('permission');
        $grantedBy = $this->option('granted-by');
        $expires = $this->option('expires');

        try {
            $authService->grantPermission(
                $agentId,
                $permission,
                $grantedBy,
                $expires ? now()->parse($expires) : null
            );

            $this->info("Permission '{$permission}' granted to agent {$agentId}");
        } catch (\Exception $e) {
            $this->error("Failed to grant permission: {$e->getMessage()}");
        }
    }
}

// Usage:
// php artisan permission:grant 5 manage_sub_agents
// php artisan permission:grant 5 view_all_reports --expires="2024-12-31"
```

## 🔍 **Debugging & Monitoring**

### **1. Check Agent Permissions**

```bash
php artisan tinker
>> $service = app(\App\Domain\Auth\Services\DatabaseAuthorizationService::class);
>> $service->getAgentPermissions(5); // Get all permissions for agent 5
>> $service->hasPermission(5, 'manage_agents'); // Check specific permission
```

### **2. Permission Audit**

```php
// Get permission grant history
$history = \App\Domain\Auth\Models\AgentPermission::with(['agent', 'permission', 'grantedBy'])
    ->where('agent_id', 5)
    ->orderBy('granted_at', 'desc')
    ->get();

// Get expired permissions
$expired = \App\Domain\Auth\Models\AgentPermission::expired()->get();
```

### **3. Performance Monitoring**

```php
// Clear permission cache for specific agent
Cache::forget("agent_permissions_5");

// Clear all permission caches
Cache::flush();
```

## 🚀 **Best Practices**

### **1. Permission Naming**

-   Use descriptive names: `manage_sub_agents` not `manage_agents`
-   Use consistent prefixes: `view_`, `manage_`, `create_`, `delete_`
-   Be specific: `view_own_reports` vs `view_all_reports`

### **2. Permission Granularity**

-   ✅ **Good**: `manage_sub_wallets`, `view_sub_reports`
-   ❌ **Bad**: `admin_access`, `super_user`

### **3. Default Permissions**

-   Always assign default permissions when creating agents
-   Use `syncDefaultPermissions()` method for existing agents
-   Review default permissions regularly

### **4. Security Considerations**

-   Always validate permission grants in business logic
-   Use temporary permissions for sensitive operations
-   Regularly audit permission assignments
-   Implement proper logging for permission changes

## 🔄 **Migration from Previous System**

### **1. Update Existing Code**

```php
// Before (hardcoded permissions)
if ($user->agent_type === 'company') {
    // Allow access
}

// After (database permissions)
if ($authService->hasPermission($user->id, 'manage_all_agents')) {
    // Allow access
}
```

### **2. Route Updates**

```php
// Before
Route::middleware(['upline.auth'])->group(function () {
    // Routes were protected only by agent type
});

// After
Route::middleware(['upline.auth', 'authorize:manage_sub_agents'])->group(function () {
    // Routes are protected by specific permissions
});
```

## 📊 **Summary**

The database-driven authorization system provides:

### **Key Benefits:**

-   ✅ **Flexibility** - Permissions can be customized per agent
-   ✅ **Scalability** - Easy to add new permissions without code changes
-   ✅ **Auditability** - Complete history of permission changes
-   ✅ **Performance** - Cached permission checks
-   ✅ **Security** - Granular permission control

### **Use Cases:**

-   **Agent Management** - Control who can manage which agents
-   **Financial Operations** - Restrict access to financial features
-   **Reporting** - Control access to different report levels
-   **System Administration** - Manage system-wide permissions

This system provides a robust foundation for managing complex permission hierarchies in your lottery application, ensuring users only have access to features they're authorized to use.
