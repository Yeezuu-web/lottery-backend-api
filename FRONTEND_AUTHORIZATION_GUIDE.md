# 🔐 Frontend Authorization Integration Guide

## 📋 Overview

This guide explains how the frontend integrates with the authorization system I implemented, including the **AuthorizationService**, **AuthorizeMiddleware**, and **HasAuthorization** trait. The system works alongside the existing JWT authentication to provide granular permission control.

## 🔄 Authorization Flow

### 1. **Authentication First**

```typescript
// Frontend logs in and receives JWT token
const loginResponse = await fetch("/api/v1/auth/upline/login", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ username, password }),
    credentials: "include",
});

const { data } = await loginResponse.json();
// JWT token is stored in HTTP-only cookie automatically
// User data includes permissions in the token payload
```

### 2. **Permission-based Authorization**

```typescript
// Frontend makes API calls with automatic token
const response = await fetch("/api/v1/agents", {
    credentials: "include", // JWT token sent automatically
});

if (response.status === 403) {
    // User doesn't have permission for this action
    showErrorMessage("You don't have permission to view agents");
} else if (response.status === 401) {
    // Token invalid/expired - redirect to login
    window.location.href = "/login";
}
```

## 🛡️ Route Protection Examples

### **Backend Route Configuration**

```php
// In routes/api.php - Routes protected by authorization middleware
Route::middleware(['upline.auth', 'authorize:manage_agents'])->group(function () {
    Route::get('/agents', [AgentController::class, 'index']);
    Route::post('/agents', [AgentController::class, 'store']);
});

Route::middleware(['upline.auth', 'authorize:manage_financial_settings'])->group(function () {
    Route::post('/agent-settings', [AgentSettingsController::class, 'store']);
    Route::patch('/agent-settings/{id}/commission-rate', [AgentSettingsController::class, 'updateCommissionRate']);
});

Route::middleware(['upline.auth', 'authorize:view_reports'])->group(function () {
    Route::get('/reports', [ReportController::class, 'index']);
    Route::get('/reports/{id}', [ReportController::class, 'show']);
});
```

### **Frontend API Integration**

```typescript
// api/agents.ts
export class AgentAPI {
    // This will return 403 if user doesn't have 'manage_agents' permission
    static async getAgents(params: GetAgentsParams): Promise<Agent[]> {
        const response = await fetch(
            "/api/v1/agents?" + new URLSearchParams(params),
            {
                credentials: "include",
            }
        );

        if (!response.ok) {
            throw new APIError(response.status, await response.json());
        }

        return response.json();
    }

    // This will return 403 if user doesn't have 'manage_agents' permission
    static async createAgent(data: CreateAgentData): Promise<Agent> {
        const response = await fetch("/api/v1/agents", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data),
            credentials: "include",
        });

        if (!response.ok) {
            throw new APIError(response.status, await response.json());
        }

        return response.json();
    }
}
```

## 📊 Permission-Based UI Management

### **Getting User Permissions**

```typescript
// hooks/useAuth.ts
interface UserData {
    id: number;
    username: string;
    agent_type: "company" | "super senior" | "senior" | "master" | "agent";
    permissions: string[];
}

export const useAuth = () => {
    const [user, setUser] = useState<UserData | null>(null);

    const getUserProfile = async () => {
        try {
            const response = await fetch("/api/v1/auth/upline/profile", {
                credentials: "include",
            });

            if (response.ok) {
                const { data } = await response.json();
                setUser(data.agent);
                return data.agent;
            }
        } catch (error) {
            console.error("Failed to get user profile:", error);
        }
        return null;
    };

    const hasPermission = (permission: string): boolean => {
        return user?.permissions?.includes(permission) ?? false;
    };

    const hasAnyPermission = (permissions: string[]): boolean => {
        return permissions.some((permission) => hasPermission(permission));
    };

    const canManageAgents = (): boolean => {
        return (
            hasPermission("manage_agents") || hasPermission("manage_all_agents")
        );
    };

    const canViewReports = (): boolean => {
        return hasAnyPermission([
            "view_reports",
            "view_all_reports",
            "view_own_reports",
        ]);
    };

    return {
        user,
        getUserProfile,
        hasPermission,
        hasAnyPermission,
        canManageAgents,
        canViewReports,
    };
};
```

### **Conditional UI Rendering**

```typescript
// components/Navigation.tsx
export const Navigation: React.FC = () => {
    const { user, hasPermission, canManageAgents, canViewReports } = useAuth();

    if (!user) return <LoadingSpinner />;

    return (
        <nav className="sidebar">
            <div className="nav-section">
                <h3>Main Menu</h3>
                <NavItem href="/dashboard" icon="dashboard">
                    Dashboard
                </NavItem>

                {canManageAgents() && (
                    <NavItem href="/agents" icon="users">
                        Agent Management
                    </NavItem>
                )}

                {canViewReports() && (
                    <NavItem href="/reports" icon="chart">
                        Reports
                    </NavItem>
                )}

                {hasPermission("manage_financial_settings") && (
                    <NavItem href="/financial-settings" icon="money">
                        Financial Settings
                    </NavItem>
                )}

                {hasPermission("manage_system_settings") && (
                    <NavItem href="/system-settings" icon="settings">
                        System Settings
                    </NavItem>
                )}
            </div>

            <div className="nav-section">
                <h3>User Type: {user.agent_type}</h3>
                <p>Permissions: {user.permissions.length}</p>
            </div>
        </nav>
    );
};
```

### **Component-Level Authorization**

```typescript
// components/AgentManagement.tsx
export const AgentManagement: React.FC = () => {
    const { hasPermission, canManageAgents } = useAuth();

    // Redirect if no permission
    if (!canManageAgents()) {
        return (
            <AccessDenied message="You don't have permission to manage agents" />
        );
    }

    return (
        <div className="agent-management">
            <div className="header">
                <h1>Agent Management</h1>

                {hasPermission("manage_agents") && (
                    <Button onClick={() => setShowCreateForm(true)}>
                        Create New Agent
                    </Button>
                )}
            </div>

            <AgentList />

            {hasPermission("manage_agents") && showCreateForm && (
                <CreateAgentForm onClose={() => setShowCreateForm(false)} />
            )}
        </div>
    );
};
```

## 🎯 Permission Hierarchy Examples

### **Company Level (Highest)**

```typescript
// User with agent_type: 'company' gets these permissions:
const companyPermissions = [
    "view_dashboard",
    "view_reports",
    "manage_profile",
    "manage_all_agents",
    "view_all_reports",
    "manage_system_settings",
    "manage_financial_settings",
];

// UI shows all features
<Navigation>
    <NavItem href="/agents">Agent Management</NavItem>
    <NavItem href="/reports">All Reports</NavItem>
    <NavItem href="/financial-settings">Financial Settings</NavItem>
    <NavItem href="/system-settings">System Settings</NavItem>
</Navigation>;
```

### **Senior Level**

```typescript
// User with agent_type: 'senior' gets these permissions:
const seniorPermissions = [
    "view_dashboard",
    "view_reports",
    "manage_profile",
    "manage_sub_agents",
    "view_sub_reports",
];

// UI shows limited features
<Navigation>
    <NavItem href="/agents">My Downlines</NavItem>
    <NavItem href="/reports">My Reports</NavItem>
    {/* No financial/system settings */}
</Navigation>;
```

### **Agent Level (Basic)**

```typescript
// User with agent_type: 'agent' gets these permissions:
const agentPermissions = [
    "view_dashboard",
    "view_reports",
    "manage_profile",
    "view_own_reports",
];

// UI shows minimal features
<Navigation>
    <NavItem href="/dashboard">Dashboard</NavItem>
    <NavItem href="/reports">My Reports</NavItem>
    <NavItem href="/profile">Profile</NavItem>
    {/* No agent management */}
</Navigation>;
```

## 🔒 Advanced Authorization Patterns

### **Resource-Based Authorization**

```typescript
// components/AgentProfile.tsx
export const AgentProfile: React.FC<{ agentId: number }> = ({ agentId }) => {
    const { user, hasPermission } = useAuth();
    const [agent, setAgent] = useState<Agent | null>(null);
    const [canEdit, setCanEdit] = useState(false);

    useEffect(() => {
        const checkPermissions = async () => {
            try {
                // This endpoint uses AuthorizationService::canManageAgent() internally
                const response = await fetch(`/api/v1/agents/${agentId}`, {
                    credentials: "include",
                });

                if (response.ok) {
                    const { data } = await response.json();
                    setAgent(data);

                    // Check if user can edit this specific agent
                    setCanEdit(
                        hasPermission("manage_all_agents") ||
                            (hasPermission("manage_sub_agents") &&
                                data.created_by === user?.id)
                    );
                } else if (response.status === 403) {
                    // User doesn't have permission to view this agent
                    setCanEdit(false);
                }
            } catch (error) {
                console.error("Error checking agent permissions:", error);
            }
        };

        checkPermissions();
    }, [agentId, user]);

    if (!agent) return <LoadingSpinner />;

    return (
        <div className="agent-profile">
            <AgentInfo agent={agent} />

            {canEdit && (
                <div className="edit-actions">
                    <Button onClick={() => setShowEditForm(true)}>
                        Edit Agent
                    </Button>
                    <Button onClick={() => setShowSettingsForm(true)}>
                        Edit Settings
                    </Button>
                </div>
            )}
        </div>
    );
};
```

### **Dynamic Permission Checking**

```typescript
// hooks/usePermissions.ts
export const usePermissions = () => {
    const { user } = useAuth();

    const checkAgentAccess = async (agentId: number): Promise<boolean> => {
        try {
            const response = await fetch(
                `/api/v1/agents/${agentId}/permissions`,
                {
                    credentials: "include",
                }
            );

            if (response.ok) {
                const { data } = await response.json();
                return data.can_access;
            }

            return false;
        } catch {
            return false;
        }
    };

    const checkWalletAccess = async (walletId: number): Promise<boolean> => {
        try {
            const response = await fetch(
                `/api/v1/wallet/wallets/${walletId}/permissions`,
                {
                    credentials: "include",
                }
            );

            if (response.ok) {
                const { data } = await response.json();
                return data.can_access;
            }

            return false;
        } catch {
            return false;
        }
    };

    return {
        checkAgentAccess,
        checkWalletAccess,
    };
};
```

## 🚨 Error Handling

### **Authorization Error Component**

```typescript
// components/AuthorizationError.tsx
interface AuthorizationErrorProps {
    status: number;
    message: string;
    onRetry?: () => void;
}

export const AuthorizationError: React.FC<AuthorizationErrorProps> = ({
    status,
    message,
    onRetry,
}) => {
    const getErrorMessage = () => {
        switch (status) {
            case 401:
                return "Your session has expired. Please log in again.";
            case 403:
                return "You don't have permission to access this resource.";
            case 404:
                return "The requested resource was not found.";
            default:
                return message || "An unexpected error occurred.";
        }
    };

    const getErrorAction = () => {
        switch (status) {
            case 401:
                return (
                    <Button onClick={() => (window.location.href = "/login")}>
                        Log In Again
                    </Button>
                );
            case 403:
                return (
                    <Button onClick={() => window.history.back()}>
                        Go Back
                    </Button>
                );
            default:
                return onRetry && <Button onClick={onRetry}>Try Again</Button>;
        }
    };

    return (
        <div className="authorization-error">
            <div className="error-icon">🚫</div>
            <h2>Access {status === 401 ? "Required" : "Denied"}</h2>
            <p>{getErrorMessage()}</p>
            {getErrorAction()}
        </div>
    );
};
```

### **Global Error Handler**

```typescript
// utils/apiClient.ts
export class APIClient {
    static async request<T>(
        url: string,
        options: RequestInit = {}
    ): Promise<T> {
        try {
            const response = await fetch(url, {
                ...options,
                credentials: "include",
            });

            if (!response.ok) {
                const errorData = await response.json();

                switch (response.status) {
                    case 401:
                        // Token expired - redirect to login
                        window.location.href = "/login";
                        break;
                    case 403:
                        // Permission denied - show error message
                        throw new AuthorizationError(
                            response.status,
                            errorData.message
                        );
                    default:
                        throw new APIError(response.status, errorData.message);
                }
            }

            return response.json();
        } catch (error) {
            console.error("API request failed:", error);
            throw error;
        }
    }
}
```

## 🔄 Real-time Permission Updates

### **Token Refresh with Permission Updates**

```typescript
// utils/tokenManager.ts
export class TokenManager {
    private static refreshInterval: NodeJS.Timeout | null = null;

    static startTokenRefresh() {
        // Refresh token every 50 minutes (access token expires in 60 minutes)
        this.refreshInterval = setInterval(async () => {
            try {
                const response = await fetch("/api/v1/auth/upline/refresh", {
                    method: "POST",
                    credentials: "include",
                });

                if (response.ok) {
                    const { data } = await response.json();

                    // Update user data with new permissions
                    window.dispatchEvent(
                        new CustomEvent("user-updated", {
                            detail: { user: data.agent },
                        })
                    );
                } else {
                    // Refresh failed - redirect to login
                    window.location.href = "/login";
                }
            } catch (error) {
                console.error("Token refresh failed:", error);
            }
        }, 50 * 60 * 1000); // 50 minutes
    }

    static stopTokenRefresh() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            this.refreshInterval = null;
        }
    }
}
```

## 🎛️ Permission Testing in Frontend

### **Development Permission Toggle**

```typescript
// components/DevPermissionToggle.tsx (development only)
export const DevPermissionToggle: React.FC = () => {
    const { user } = useAuth();
    const [testPermissions, setTestPermissions] = useState<string[]>([]);

    if (process.env.NODE_ENV !== "development") return null;

    const allPermissions = [
        "manage_all_agents",
        "manage_sub_agents",
        "view_all_reports",
        "view_sub_reports",
        "view_own_reports",
        "manage_system_settings",
        "manage_financial_settings",
    ];

    return (
        <div className="dev-permission-toggle">
            <h3>Test Permissions (Dev Only)</h3>
            {allPermissions.map((permission) => (
                <label key={permission}>
                    <input
                        type="checkbox"
                        checked={testPermissions.includes(permission)}
                        onChange={(e) => {
                            if (e.target.checked) {
                                setTestPermissions([
                                    ...testPermissions,
                                    permission,
                                ]);
                            } else {
                                setTestPermissions(
                                    testPermissions.filter(
                                        (p) => p !== permission
                                    )
                                );
                            }
                        }}
                    />
                    {permission}
                </label>
            ))}
        </div>
    );
};
```

## 📋 Summary

### **Key Integration Points:**

1. **Authentication Flow**: Login → JWT token → Permissions in payload
2. **Route Protection**: Middleware checks permissions before reaching controllers
3. **Frontend Permission Checking**: Use JWT payload to show/hide UI elements
4. **Error Handling**: 401 = login required, 403 = permission denied
5. **Real-time Updates**: Token refresh updates permissions automatically

### **Best Practices:**

-   ✅ Always check permissions on both frontend and backend
-   ✅ Use HTTP-only cookies for security
-   ✅ Handle authorization errors gracefully
-   ✅ Implement permission-based UI rendering
-   ✅ Use type-safe permission checking
-   ✅ Test with different user types and permissions

The authorization system provides a robust foundation for managing complex permission hierarchies in your lottery system, ensuring users only see and can interact with features they're authorized to use.
