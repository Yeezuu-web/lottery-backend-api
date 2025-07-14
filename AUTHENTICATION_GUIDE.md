# 🔐 **Dual Authentication System Guide**

## 📋 **Overview**

This authentication system provides **separate login flows** for **Upline** and **Member** users with JWT-based authentication, ensuring complete isolation between the two user types.

## 🎯 **Key Features**

### 🔄 **Dual Authentication**

-   **Upline Authentication** - Dashboard access for agents (company, super senior, senior, master, agent)
-   **Member Authentication** - Betting interface access for members only

### 🛡️ **Security Features**

-   **JWT-based** with separate secrets for each audience
-   **Refresh token** rotation for enhanced security
-   **Token blacklisting** for secure logout
-   **Rate limiting** on authentication endpoints
-   **HTTP-only cookies** for additional security

### 🌐 **Browser Isolation**

-   **Separate cookies** (`upline_token` vs `member_token`)
-   **Different JWT audiences** (`upline` vs `member`)
-   **Simultaneous login** in same browser without conflicts

## 🔧 **Environment Setup**

Add these variables to your `.env` file:

```env
# JWT Authentication Configuration
JWT_UPLINE_SECRET=your-upline-secret-key-here-min-32-characters
JWT_MEMBER_SECRET=your-member-secret-key-here-min-32-characters

# JWT Token TTL (in minutes)
JWT_UPLINE_ACCESS_TTL=60      # 1 hour
JWT_UPLINE_REFRESH_TTL=10080  # 7 days
JWT_MEMBER_ACCESS_TTL=30      # 30 minutes
JWT_MEMBER_REFRESH_TTL=1440   # 1 day

# JWT Blacklist Configuration
JWT_BLACKLIST_ENABLED=true
JWT_BLACKLIST_GRACE_PERIOD=5

# JWT Security Configuration
JWT_REQUIRE_HTTPS=false
JWT_MAX_ATTEMPTS=5
JWT_DECAY_MINUTES=15
```

## 🚀 **API Endpoints**

### 🔷 **Upline Authentication**

#### **Login**

```http
POST /api/auth/upline/login
Content-Type: application/json

{
  "username": "agent1",
  "password": "password123"
}
```

**Response:**

```json
{
    "success": true,
    "message": "Authentication successful",
    "data": {
        "success": true,
        "agent": {
            "id": 1,
            "username": "agent1",
            "email": "agent1@example.com",
            "name": "Agent One",
            "agent_type": "agent",
            "status": "active"
        },
        "tokens": {
            "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
            "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
            "token_type": "Bearer",
            "expires_at": "2024-01-01T12:00:00+00:00",
            "audience": "upline"
        }
    }
}
```

#### **Refresh Token**

```http
POST /api/auth/upline/refresh
Content-Type: application/json

{
  "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
}
```

#### **Profile**

```http
GET /api/auth/upline/profile
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

#### **Logout**

```http
POST /api/auth/upline/logout
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

### 🔶 **Member Authentication**

#### **Login**

```http
POST /api/auth/member/login
Content-Type: application/json

{
  "username": "member1",
  "password": "password123"
}
```

**Response:**

```json
{
    "success": true,
    "message": "Authentication successful",
    "data": {
        "success": true,
        "agent": {
            "id": 2,
            "username": "member1",
            "email": "member1@example.com",
            "name": "Member One",
            "agent_type": "member",
            "status": "active"
        },
        "tokens": {
            "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
            "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
            "token_type": "Bearer",
            "expires_at": "2024-01-01T12:00:00+00:00",
            "audience": "member"
        }
    }
}
```

#### **Refresh Token**

```http
POST /api/auth/member/refresh
Content-Type: application/json

{
  "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
}
```

#### **Profile**

```http
GET /api/auth/member/profile
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

#### **Logout**

```http
POST /api/auth/member/logout
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

## 🔐 **Authentication Flow**

### 📋 **Agent Type Restrictions**

| Agent Type   | Upline Access | Member Access |
| ------------ | ------------- | ------------- |
| Company      | ✅ Yes        | ❌ No         |
| Super Senior | ✅ Yes        | ❌ No         |
| Senior       | ✅ Yes        | ❌ No         |
| Master       | ✅ Yes        | ❌ No         |
| Agent        | ✅ Yes        | ❌ No         |
| Member       | ❌ No         | ✅ Yes        |

### 🔑 **Token Structure**

#### **Upline Token Payload**

```json
{
    "iss": "http://localhost",
    "aud": "upline",
    "iat": 1640995200,
    "exp": 1640998800,
    "sub": "1",
    "agent_id": 1,
    "agent_type": "agent",
    "username": "agent1",
    "email": "agent1@example.com",
    "permissions": [
        "view_dashboard",
        "view_reports",
        "manage_profile",
        "view_own_reports"
    ],
    "token_type": "access"
}
```

#### **Member Token Payload**

```json
{
    "iss": "http://localhost",
    "aud": "member",
    "iat": 1640995200,
    "exp": 1640997000,
    "sub": "2",
    "agent_id": 2,
    "agent_type": "member",
    "username": "member1",
    "email": "member1@example.com",
    "permissions": [
        "place_bets",
        "view_orders",
        "view_balance",
        "manage_profile"
    ],
    "token_type": "access"
}
```

## 🛡️ **Middleware Usage**

### **Upline Routes**

```php
Route::middleware(['auth.upline'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/reports', [ReportsController::class, 'index']);
    Route::get('/agents', [AgentController::class, 'index']);
});
```

### **Member Routes**

```php
Route::middleware(['auth.member'])->group(function () {
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/balance', [WalletController::class, 'balance']);
    Route::get('/orders', [OrderController::class, 'index']);
});
```

## 🔧 **Frontend Integration**

### **React Example - Upline Login**

```typescript
// api/auth.ts
export const loginUpline = async (credentials: LoginCredentials) => {
    const response = await fetch("/api/auth/upline/login", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify(credentials),
        credentials: "include", // Important for cookies
    });

    return response.json();
};

// hooks/useUplineAuth.ts
export const useUplineAuth = () => {
    const login = async (username: string, password: string) => {
        const result = await loginUpline({ username, password });

        if (result.success) {
            // Token is automatically stored in HTTP-only cookie
            localStorage.setItem(
                "upline_user",
                JSON.stringify(result.data.agent)
            );
            return result.data;
        }

        throw new Error(result.message);
    };

    const logout = async () => {
        await fetch("/api/auth/upline/logout", {
            method: "POST",
            credentials: "include",
        });

        localStorage.removeItem("upline_user");
    };

    return { login, logout };
};
```

### **React Example - Member Login**

```typescript
// api/auth.ts
export const loginMember = async (credentials: LoginCredentials) => {
    const response = await fetch("/api/auth/member/login", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify(credentials),
        credentials: "include", // Important for cookies
    });

    return response.json();
};

// hooks/useMemberAuth.ts
export const useMemberAuth = () => {
    const login = async (username: string, password: string) => {
        const result = await loginMember({ username, password });

        if (result.success) {
            // Token is automatically stored in HTTP-only cookie
            localStorage.setItem(
                "member_user",
                JSON.stringify(result.data.agent)
            );
            return result.data;
        }

        throw new Error(result.message);
    };

    const logout = async () => {
        await fetch("/api/auth/member/logout", {
            method: "POST",
            credentials: "include",
        });

        localStorage.removeItem("member_user");
    };

    return { login, logout };
};
```

## 📊 **Token Management**

### **Automatic Token Refresh**

```typescript
// utils/tokenRefresh.ts
export const setupTokenRefresh = (audience: "upline" | "member") => {
    const refreshEndpoint = `/api/auth/${audience}/refresh`;

    // Refresh token 5 minutes before expiry
    const refreshInterval = setInterval(async () => {
        try {
            const response = await fetch(refreshEndpoint, {
                method: "POST",
                credentials: "include",
            });

            const result = await response.json();

            if (!result.success) {
                // Redirect to login
                window.location.href = `/${audience}/login`;
            }
        } catch (error) {
            console.error("Token refresh failed:", error);
        }
    }, 5 * 60 * 1000); // 5 minutes

    return () => clearInterval(refreshInterval);
};
```

## 🔍 **Testing**

### **Generate JWT Secrets**

```bash
# Generate secure secrets
php artisan key:generate --show | base64
```

### **Test Authentication**

```bash
# Test upline login
curl -X POST http://localhost:8000/api/auth/upline/login \
  -H "Content-Type: application/json" \
  -d '{"username":"agent1","password":"password123"}'

# Test member login
curl -X POST http://localhost:8000/api/auth/member/login \
  -H "Content-Type: application/json" \
  -d '{"username":"member1","password":"password123"}'
```

## 🐛 **Troubleshooting**

### **Common Issues**

1. **"Token not provided"**

    - Check Authorization header format: `Bearer <token>`
    - Ensure cookies are being sent with `credentials: 'include'`

2. **"Invalid audience"**

    - Verify agent type matches the authentication endpoint
    - Members can only use `/member/` endpoints
    - Agents can only use `/upline/` endpoints

3. **"Refresh token expired"**

    - User needs to log in again
    - Check JWT\_\*\_REFRESH_TTL configuration

4. **CORS Issues**
    - Configure CORS to allow credentials
    - Set proper domain configuration

## 🔄 **Migration Path**

If you're upgrading from Sanctum:

1. **Update Frontend**

    - Replace Sanctum token handling with JWT
    - Update API calls to use new endpoints

2. **Update Backend**

    - Replace Sanctum middleware with JWT middleware
    - Update route protection

3. **Database**
    - No changes needed to agents table
    - Remove personal_access_tokens table if not needed

## 📈 **Performance Considerations**

-   **Token Validation**: O(1) - No database lookup required
-   **Token Refresh**: Minimal overhead with blacklist caching
-   **Session Storage**: Redis recommended for token blacklist
-   **Rate Limiting**: Configured for authentication endpoints

## 🔒 **Security Best Practices**

1. **Environment Variables**

    - Use strong, unique secrets for each environment
    - Rotate secrets regularly

2. **HTTPS**

    - Always use HTTPS in production
    - Set `JWT_REQUIRE_HTTPS=true`

3. **Token Expiry**

    - Keep access tokens short-lived
    - Use longer refresh tokens

4. **Monitoring**
    - Log authentication attempts
    - Monitor for unusual patterns

---

## 🎉 **Ready to Use!**

Your dual authentication system is now ready! Both upline and member users can authenticate independently with complete isolation. 🚀

# 🔐 Postman Testing Guide - JWT Blacklisting

## 🚨 **Important: Test the RIGHT Authentication System**

You have **two different authentication systems**. Only the **JWT system** supports blacklisting!

---

## 🟢 **JWT System (WITH Blacklisting)**

### **Step 1: Login**

```
POST http://localhost:8000/api/v1/auth/upline/login
Content-Type: application/json

{
    "username": "ABAAAAAA",
    "password": "your-password"
}
```

**Response:**

```json
{
    "success": true,
    "message": "Authentication successful",
    "data": {
        "agent": {...},
        "tokens": {
            "access_token": "eyJ0eXAiOiJKV1Q...",
            "refresh_token": "eyJ0eXAiOiJKV1Q..."
        }
    }
}
```

### **Step 2: Test Protected Route**

```
GET http://localhost:8000/api/v1/agents
Authorization: Bearer eyJ0eXAiOiJKV1Q...
```

**Should return:** Agent list (200 OK)

### **Step 3: Logout (Blacklist Tokens)**

```
POST http://localhost:8000/api/v1/auth/upline/logout
Authorization: Bearer eyJ0eXAiOiJKV1Q...
```

**Should return:** Success message

### **Step 4: Test Blacklisted Token**

```
GET http://localhost:8000/api/v1/agents
Authorization: Bearer eyJ0eXAiOiJKV1Q...
```

**Should return:** 401 Unauthorized (Token blacklisted)

---

## 🔴 **Sanctum System (WITHOUT Blacklisting)**

### **❌ DON'T TEST THESE ENDPOINTS**

```
POST http://localhost:8000/api/v1/agents/auth/login  ❌
GET http://localhost:8000/api/v1/agents/sub-accounts  ❌
```

These use Laravel Sanctum and **don't support blacklisting**!

---

## 📋 **Complete Postman Test Collection**

### **Collection: JWT Blacklisting Test**

#### **1. Login (JWT)**

-   **Method**: POST
-   **URL**: `{{base_url}}/api/v1/auth/upline/login`
-   **Body**:

```json
{
    "username": "ABAAAAAA",
    "password": "your-password"
}
```

-   **Tests Script**:

```javascript
pm.test("Login successful", function () {
    pm.response.to.have.status(200);
    const response = pm.response.json();
    pm.expect(response.success).to.be.true;

    // Save tokens for next requests
    const tokens = response.data.tokens;
    pm.environment.set("access_token", tokens.access_token);
    pm.environment.set("refresh_token", tokens.refresh_token);
});
```

#### **2. Get Agents (With Valid Token)**

-   **Method**: GET
-   **URL**: `{{base_url}}/api/v1/agents`
-   **Headers**: `Authorization: Bearer {{access_token}}`
-   **Tests Script**:

```javascript
pm.test("Valid token works", function () {
    pm.response.to.have.status(200);
    const response = pm.response.json();
    pm.expect(response.success).to.be.true;
});
```

#### **3. Logout (Blacklist Tokens)**

-   **Method**: POST
-   **URL**: `{{base_url}}/api/v1/auth/upline/logout`
-   **Headers**: `Authorization: Bearer {{access_token}}`
-   **Tests Script**:

```javascript
pm.test("Logout successful", function () {
    pm.response.to.have.status(200);
    const response = pm.response.json();
    pm.expect(response.success).to.be.true;
});
```

#### **4. Get Agents (With Blacklisted Token)**

-   **Method**: GET
-   **URL**: `{{base_url}}/api/v1/agents`
-   **Headers**: `Authorization: Bearer {{access_token}}`
-   **Tests Script**:

```javascript
pm.test("Blacklisted token rejected", function () {
    pm.response.to.have.status(401);
    const response = pm.response.json();
    pm.expect(response.success).to.be.false;
    pm.expect(response.message).to.include("Invalid or expired token");
});
```

---

## 🎯 **Environment Variables**

Create a Postman environment with:

```
base_url: http://localhost:8000
access_token: (will be set by tests)
refresh_token: (will be set by tests)
```

---

## 🔧 **Manual Testing Steps**

### **Step 1: Login**

1. Open Postman
2. Create request: `POST http://localhost:8000/api/v1/auth/upline/login`
3. Set body to JSON:
    ```json
    {
        "username": "ABAAAAAA",
        "password": "your-password"
    }
    ```
4. Send request
5. **Copy the `access_token`** from response

### **Step 2: Test Valid Token**

1. Create request: `GET http://localhost:8000/api/v1/agents`
2. Add header: `Authorization: Bearer YOUR_ACCESS_TOKEN`
3. Send request
4. **Should get 200 OK** with agent list

### **Step 3: Logout (Blacklist)**

1. Create request: `POST http://localhost:8000/api/v1/auth/upline/logout`
2. Add header: `Authorization: Bearer YOUR_ACCESS_TOKEN`
3. Send request
4. **Should get 200 OK** with success message

### **Step 4: Test Blacklisted Token**

1. Use same request: `GET http://localhost:8000/api/v1/agents`
2. Keep same header: `Authorization: Bearer YOUR_ACCESS_TOKEN`
3. Send request
4. **Should get 401 Unauthorized** (token blacklisted!)

---

## 🛠️ **Troubleshooting**

### **If blacklisting doesn't work:**

1. **Check you're using the right URL**:

    ```
    ✅ http://localhost:8000/api/v1/agents
    ❌ http://localhost:8000/api/v1/agents/auth/login
    ```

2. **Verify the token format**:

    ```
    ✅ Authorization: Bearer eyJ0eXAiOiJKV1Q...
    ❌ Authorization: Bearer sanctum-token
    ```

3. **Check the login endpoint**:

    ```
    ✅ POST /api/v1/auth/upline/login
    ❌ POST /api/v1/agents/auth/login
    ```

4. **Verify environment**:
    ```bash
    # Check if JWT blacklisting is enabled
    grep JWT_BLACKLIST_ENABLED .env
    # Should show: JWT_BLACKLIST_ENABLED=true
    ```

---

## 📊 **Expected Results**

| Action                   | Endpoint                          | Status | Response                   |
| ------------------------ | --------------------------------- | ------ | -------------------------- |
| Login                    | `POST /api/v1/auth/upline/login`  | 200    | Success with tokens        |
| Get Agents (Valid)       | `GET /api/v1/agents`              | 200    | Agent list                 |
| Logout                   | `POST /api/v1/auth/upline/logout` | 200    | Success                    |
| Get Agents (Blacklisted) | `GET /api/v1/agents`              | 401    | "Invalid or expired token" |

---

## 🎉 **Success Indicators**

You'll know blacklisting is working when:

1. ✅ **Login works** - Get tokens in response
2. ✅ **Valid token works** - Can access protected routes
3. ✅ **Logout works** - Success message returned
4. ✅ **Blacklisted token fails** - 401 error with "Invalid or expired token"

If you see **401 "Invalid or expired token"** after logout, **blacklisting is working correctly!** 🎉

---

## 🔄 **Quick Test Script**

Save this as a bash script for quick testing:

```bash
#!/bin/bash

BASE_URL="http://localhost:8000"
USERNAME="ABAAAAAA"
PASSWORD="your-password"

echo "🔐 Testing JWT Blacklisting..."

# Step 1: Login
echo "1. Logging in..."
LOGIN_RESPONSE=$(curl -s -X POST "${BASE_URL}/api/v1/auth/upline/login" \
  -H "Content-Type: application/json" \
  -d "{\"username\":\"${USERNAME}\",\"password\":\"${PASSWORD}\"}")

ACCESS_TOKEN=$(echo $LOGIN_RESPONSE | jq -r '.data.tokens.access_token')
echo "Access token: ${ACCESS_TOKEN:0:50}..."

# Step 2: Test valid token
echo "2. Testing valid token..."
VALID_RESPONSE=$(curl -s -X GET "${BASE_URL}/api/v1/agents" \
  -H "Authorization: Bearer ${ACCESS_TOKEN}")
echo "Valid token response: $(echo $VALID_RESPONSE | jq -r '.success')"

# Step 3: Logout (blacklist)
echo "3. Logging out (blacklisting token)..."
LOGOUT_RESPONSE=$(curl -s -X POST "${BASE_URL}/api/v1/auth/upline/logout" \
  -H "Authorization: Bearer ${ACCESS_TOKEN}")
echo "Logout response: $(echo $LOGOUT_RESPONSE | jq -r '.success')"

# Step 4: Test blacklisted token
echo "4. Testing blacklisted token..."
BLACKLISTED_RESPONSE=$(curl -s -X GET "${BASE_URL}/api/v1/agents" \
  -H "Authorization: Bearer ${ACCESS_TOKEN}")
echo "Blacklisted token response: $(echo $BLACKLISTED_RESPONSE | jq -r '.success')"

if [ "$(echo $BLACKLISTED_RESPONSE | jq -r '.success')" = "false" ]; then
    echo "🎉 SUCCESS: Blacklisting is working!"
else
    echo "❌ ERROR: Blacklisting is not working!"
fi
```

---

**Test the JWT system endpoints, not the Sanctum ones!** 🎯
