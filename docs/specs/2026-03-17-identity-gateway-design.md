# Identity Gateway — Implementation Plan

## Context

Build a Laravel-based authentication platform as a **portfolio/resume showcase** demonstrating OAuth2, JWT, and stateless authentication. The system behaves like a mini identity provider (Auth0/Okta-style). Target directory: `/home/steve/repo/identity-gateway`.

**Decisions made:**
- Monorepo (single Laravel app, 3 logical route groups)
- Laravel Passport for OAuth2
- Inertia.js + React for frontend
- Full RSA key rotation from the start
- Spatie Permission for RBAC
- MySQL + Redis + Docker (with phpMyAdmin)

---

## Architecture

Single Laravel 12 app with 3 logical components:

| Component | Route Prefix | Auth | Purpose |
|-----------|-------------|------|---------|
| Auth Server | `/oauth/*`, `/auth/*`, `/.well-known/*` | Session (browser) | OAuth2 server, JWT issuer, consent UI |
| Resource API | `/api/v1/*` | JWT or API Key (stateless) | Protected REST endpoints |
| Demo App | `/demo/*` | Public | Interactive OAuth/JWT demos |

Three auth guards: `session` (browser), `jwt` (custom stateless), `api-key` (header-based).

Custom JWT validation middleware on Resource API validates signatures directly against rotating keys — not Passport's default token guard.

---

## Module / Directory Structure

```
/home/steve/repo/identity-gateway/
├── app/
│   ├── Console/Commands/
│   │   ├── RotateSigningKey.php              # php artisan jwt:rotate
│   │   ├── PurgeExpiredTokens.php            # php artisan tokens:purge
│   │   └── CreateApiKey.php                  # php artisan apikey:create
│   ├── Enums/
│   │   ├── OAuthGrantType.php
│   │   ├── TokenStatus.php
│   │   ├── AuditAction.php
│   │   └── KeyStatus.php
│   ├── Guards/
│   │   ├── JwtGuard.php                      # Stateless JWT signature validation
│   │   └── ApiKeyGuard.php                   # X-Api-Key header lookup
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   │   ├── AuthorizationController.php
│   │   │   │   ├── TokenController.php
│   │   │   │   ├── IntrospectionController.php   # RFC 7662
│   │   │   │   ├── RevocationController.php      # RFC 7009
│   │   │   │   ├── JwksController.php
│   │   │   │   ├── OpenIdConfigController.php
│   │   │   │   ├── LoginController.php
│   │   │   │   ├── RegisterController.php
│   │   │   │   ├── LogoutController.php
│   │   │   │   └── ConsentController.php
│   │   │   ├── Api/
│   │   │   │   ├── UserController.php
│   │   │   │   ├── ResourceController.php
│   │   │   │   └── ScopeController.php
│   │   │   ├── Dashboard/
│   │   │   │   ├── ClientController.php
│   │   │   │   ├── ApiKeyController.php
│   │   │   │   ├── TokenController.php
│   │   │   │   └── AuditLogController.php
│   │   │   └── Demo/
│   │   │       ├── PlaygroundController.php
│   │   │       ├── JwtInspectorController.php
│   │   │       └── FlowDemoController.php
│   │   ├── Middleware/
│   │   │   ├── ValidateJwtSignature.php
│   │   │   ├── RequireScope.php
│   │   │   ├── AuditRequest.php
│   │   │   └── ForceJsonResponse.php
│   │   └── Requests/
│   │       ├── StoreClientRequest.php
│   │       ├── UpdateClientRequest.php
│   │       ├── CreateApiKeyRequest.php
│   │       ├── IntrospectTokenRequest.php
│   │       └── RevokeTokenRequest.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── SigningKey.php
│   │   ├── ApiKey.php
│   │   ├── AuditLog.php
│   │   ├── OAuthScope.php
│   │   └── OAuthConsentApproval.php
│   ├── Passport/
│   │   └── CustomBearerTokenResponse.php     # Overrides JWT serialization to use JwtService
│   ├── Providers/
│   │   ├── AppServiceProvider.php
│   │   ├── AuthServiceProvider.php
│   │   └── EventServiceProvider.php
│   ├── Services/
│   │   ├── JwtService.php                    # Sign/verify JWTs with rotating keys
│   │   ├── SigningKeyService.php             # Key rotation, JWKS generation
│   │   ├── ApiKeyService.php
│   │   ├── AuditService.php
│   │   ├── TokenIntrospectionService.php     # RFC 7662 logic
│   │   └── TokenRevocationService.php        # RFC 7009 logic
│   ├── Events/
│   │   ├── TokenIssued.php
│   │   ├── TokenRevoked.php
│   │   ├── KeyRotated.php
│   │   ├── ClientCreated.php
│   │   └── UserAuthenticated.php
│   └── Listeners/
│       └── AuditEventSubscriber.php
├── config/
│   ├── auth.php                              # Guards: session, jwt, api-key
│   ├── passport.php
│   └── identity-gateway.php                  # JWT TTL, key rotation config, API key prefix
├── database/
│   ├── migrations/
│   │   ├── (Laravel defaults: users, cache, jobs)
│   │   ├── 2026_03_17_000001_create_signing_keys_table.php
│   │   ├── 2026_03_17_000002_create_api_keys_table.php
│   │   ├── 2026_03_17_000003_create_audit_logs_table.php
│   │   ├── 2026_03_17_000004_create_permission_tables.php  (Spatie)
│   │   ├── 2026_03_17_000005_create_oauth_scopes_table.php
│   │   ├── 2026_03_17_000006_create_oauth_consent_approvals_table.php
│   │   └── 2026_03_17_000007_create_resources_table.php
│   ├── factories/ (User, SigningKey, ApiKey)
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── RoleAndPermissionSeeder.php
│       ├── OAuthScopeSeeder.php
│       └── DemoUserSeeder.php
├── resources/js/
│   ├── app.tsx
│   ├── types/ (index.d.ts, auth.ts, api.ts)
│   ├── layouts/ (AuthLayout, DashboardLayout, DemoLayout)
│   ├── pages/
│   │   ├── Auth/ (Login, Register, Consent)
│   │   ├── Dashboard/
│   │   │   ├── Index.tsx
│   │   │   ├── Clients/ (Index, Create, Show)
│   │   │   ├── ApiKeys/Index.tsx
│   │   │   ├── Tokens/Index.tsx
│   │   │   └── AuditLogs/Index.tsx
│   │   └── Demo/
│   │       ├── Index.tsx, Playground.tsx, JwtInspector.tsx
│   │       ├── AuthCodeFlow.tsx, PkceFlow.tsx, ClientCredentialsFlow.tsx
│   │       ├── IntrospectionDemo.tsx, RevocationDemo.tsx
│   └── components/
│       ├── ui/ (Button, Card, Input, Badge, Table, Modal)
│       ├── dashboard/ (ClientForm, ApiKeyCreateModal, TokenInspectModal, AuditLogTable)
│       └── demo/ (FlowStepIndicator, RequestResponsePanel, JwtDecodePanel, CodeSnippetBlock, ScopeSelector)
├── routes/
│   ├── web.php          # Auth pages, Dashboard, Demo
│   ├── api.php          # /api/v1/* (jwt + api-key guards)
│   └── auth.php         # /oauth/*, /.well-known/*
├── tests/
│   ├── Feature/
│   │   ├── Auth/ (AuthorizationCodeFlowTest, PkceFlowTest, ClientCredentialsFlowTest, RefreshTokenTest, TokenIntrospectionTest, TokenRevocationTest, JwksEndpointTest)
│   │   ├── Api/ (UserEndpointTest, ResourceEndpointTest, ApiKeyAuthTest)
│   │   └── Dashboard/ (ClientManagementTest, ApiKeyManagementTest, AuditLogTest)
│   ├── Unit/
│   │   ├── Services/ (JwtServiceTest, SigningKeyServiceTest, ApiKeyServiceTest, AuditServiceTest)
│   │   ├── Guards/ (JwtGuardTest, ApiKeyGuardTest)
│   │   └── Middleware/ (ValidateJwtSignatureTest, RequireScopeTest)
│   └── Traits/ (CreatesOAuthClient, CreatesSigningKeys)
├── docker-compose.yml
├── Dockerfile
└── .env.example
```

---

## Database Schema

### `signing_keys`
| Column | Type | Notes |
|--------|------|-------|
| id | uuid (PK) | Also used as `kid` in JWT header |
| private_key | text | PEM RSA private key (encrypted via `encryptString`) |
| public_key | text | PEM RSA public key |
| algorithm | varchar(10) | Default `RS256` |
| status | varchar(20) | `active`, `retired`, `revoked` |
| activated_at | timestamp | When this key started signing |
| retired_at | timestamp | Nullable, when replaced |
| expires_at | timestamp | Retired keys kept for validation until this |
| created_at / updated_at | timestamps | |

### `api_keys`
| Column | Type | Notes |
|--------|------|-------|
| id | uuid (PK) | |
| user_id | FK -> users | |
| name | varchar(255) | Human label |
| key_hash | varchar(255) | SHA-256 hash, UNIQUE |
| key_prefix | varchar(12) | e.g. `igw_live_` for display |
| scopes | json | Nullable |
| last_used_at | timestamp | Nullable |
| expires_at | timestamp | Nullable |
| revoked_at | timestamp | Nullable |
| created_at / updated_at | timestamps | |

### `audit_logs`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint (PK) | |
| user_id | FK -> users | Nullable |
| action | varchar(50) | AuditAction enum |
| entity_type | varchar(100) | Nullable |
| entity_id | varchar(100) | Nullable |
| metadata | json | Nullable (IP, user-agent, client_id, etc.) |
| ip_address | varchar(45) | |
| user_agent | varchar(500) | |
| created_at | timestamp | |

### `oauth_scopes`
| Column | Type | Notes |
|--------|------|-------|
| id | varchar(100) (PK) | e.g. `user:read` |
| description | varchar(255) | |
| is_default | boolean | Default false |
| created_at / updated_at | timestamps | |

### `oauth_consent_approvals`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint (PK) | |
| user_id | FK -> users | |
| client_id | varchar(100) | Passport client ID |
| scopes | json | Approved scopes |
| created_at | timestamp | |
| updated_at | timestamp | |

Unique index on `(user_id, client_id)`. When user consents, store the approval. On subsequent authorization requests for the same client+scopes, skip the consent screen.

### `resources` (demo data)
| Column | Type | Notes |
|--------|------|-------|
| id | bigint (PK) | |
| user_id | FK -> users | |
| title | varchar(255) | |
| body | text | |
| created_at / updated_at | timestamps | |

Plus: Passport tables (`oauth_auth_codes`, `oauth_access_tokens`, `oauth_refresh_tokens`, `oauth_clients`, `oauth_personal_access_clients`) and Spatie tables (`permissions`, `roles`, `role_has_permissions`, `model_has_roles`, `model_has_permissions`).

**Default Roles:** `admin` (all perms), `developer` (clients/keys/tokens/audit-read), `viewer` (clients:read, audit-logs:read)

---

## API Endpoints

### Auth Server (`routes/auth.php`)
| Method | Path | Auth | Description |
|--------|------|------|-------------|
| GET | `/.well-known/openid-configuration` | None | Discovery document |
| GET | `/.well-known/jwks.json` | None | JWK Set (active + retired keys) |
| GET | `/oauth/authorize` | Session | Authorization consent screen |
| POST | `/oauth/authorize` | Session | Approve/deny consent |
| POST | `/oauth/token` | Client credentials | Token exchange |
| POST | `/oauth/introspect` | Client credentials | RFC 7662 introspection |
| POST | `/oauth/revoke` | Client credentials | RFC 7009 revocation |

### Auth Pages (`routes/web.php`)
| Method | Path | Auth | Description |
|--------|------|------|-------------|
| GET/POST | `/auth/login` | Guest | Login (Inertia) |
| GET/POST | `/auth/register` | Guest | Register (Inertia) |
| POST | `/auth/logout` | Session | Logout |
| GET/POST | `/auth/consent` | Session | OAuth consent screen |

### Resource API (`routes/api.php` → `/api/v1`)
| Method | Path | Auth | Scopes |
|--------|------|------|--------|
| GET | `/me` | jwt/api-key | `user:read` |
| GET | `/users` | jwt | `users:read` (admin) |
| GET | `/users/{id}` | jwt | `users:read` |
| GET/POST | `/resources` | jwt/api-key | `resources:read`/`resources:write` |
| GET/PUT/DELETE | `/resources/{id}` | jwt/api-key | `resources:read`/`resources:write` |
| GET | `/scopes` | jwt/api-key | — |

### Dashboard (`routes/web.php` → `/dashboard`)
All session auth + Spatie permission middleware.

| Path | Permission | Description |
|------|-----------|-------------|
| `/dashboard` | clients:read | Overview stats |
| `/dashboard/clients` (CRUD) | clients:* | OAuth client management |
| `/dashboard/api-keys` (CRD) | api-keys:* | API key management |
| `/dashboard/tokens` | tokens:inspect/revoke | Token inspection & revocation |
| `/dashboard/audit-logs` | audit-logs:read | Filterable audit log |

### Demo (`routes/web.php` → `/demo`)
All public, no auth.

| Path | Description |
|------|-------------|
| `/demo` | Demo home |
| `/demo/playground` | Interactive OAuth playground |
| `/demo/jwt-inspector` | JWT decode/verify tool |
| `/demo/flows/auth-code` | Auth Code flow walkthrough |
| `/demo/flows/pkce` | PKCE flow walkthrough |
| `/demo/flows/client-credentials` | Client Credentials walkthrough |
| `/demo/introspection` | Token introspection demo |
| `/demo/revocation` | Token revocation demo |

---

## Security Model

### JWT Signing (`JwtService::sign`)
1. `SigningKeyService::getActiveKey()` gets the active `signing_keys` row
2. Build claims: `iss`, `sub` (user ID), `aud`, `exp` (+1h), `iat`, `jti` (UUID), `scopes`, `roles`, `name`, `email`
3. Header: `{ alg: "RS256", typ: "JWT", kid: "<signing_key.id>" }`
4. Sign with `firebase/php-jwt` using the private key

### Stateless Validation (`ValidateJwtSignature` middleware)
1. Extract `Bearer <token>` from Authorization header
2. Decode JWT header → extract `kid`
3. Fetch public key: `SigningKey::where('id', $kid)->whereIn('status', ['active', 'retired'])`
4. Verify signature with `firebase/php-jwt`
5. Validate `exp`, `iss`, `aud`
6. Check revocation blacklist key: `revoked:<jti>` → reject if revoked
7. Hydrate user from `sub` claim

### Key Rotation (`php artisan jwt:rotate`)
1. Generate 3072-bit RSA key pair (NIST recommended minimum for new systems)
2. Insert with `status = 'active'`, `expires_at = now + 90 days`
3. Previous active key → `status = 'retired'`
4. Retired keys stay in JWKS until `expires_at`
5. Scheduled weekly

### API Key Auth (`ApiKeyGuard`)
- Header: `X-Api-Key: igw_live_<32 hex chars>`
- Alternative header: `Authorization: ApiKey igw_live_<32 hex chars>`
- Hash with SHA-256, lookup in `api_keys` table
- Check not revoked, not expired

### Rate Limiting
- `/oauth/*`: 30/min per IP
- `/api/v1/*`: 120/min per user (or IP if unauthenticated)
- `/auth/*`: 10/min per IP

### Custom Passport Integration (via BearerTokenResponse)
In Passport 12, token serialization happens in the League OAuth2 Server's `BearerTokenResponse`, not on the access token entity. The correct integration point:

1. Create `app/Passport/CustomBearerTokenResponse.php` extending `League\OAuth2\Server\ResponseTypes\BearerTokenResponse`
2. Override `generateHttpResponse()` to:
   - Let the parent generate the default response
   - Extract the user ID and scopes from the access token entity
   - Call `JwtService::sign()` to create a custom JWT with rotating keys, `kid` header, roles, and custom claims
   - Replace the `access_token` field in the response body with the custom JWT
3. Register in `AuthServiceProvider::boot()`:
   ```php
   app(AuthorizationServer::class)->setResponseType(new CustomBearerTokenResponse());
   ```

This approach is non-invasive — it doesn't touch Passport internals, only replaces the final token string in the HTTP response.

### Token Revocation Strategy
Since JWTs are validated statelessly (no DB lookup on every request), revoked tokens remain valid until expiry unless checked. Strategy:

1. **Short-lived access tokens** (15 minutes, not 1 hour) — limits exposure window
2. **Redis revocation blacklist** — on `POST /oauth/revoke`, add `jti` to Redis set `revoked_tokens` with TTL matching token expiry
3. **ValidateJwtSignature middleware** checks Redis: `Redis::sismember('revoked_tokens', $jti)` — single O(1) lookup, no SQL
4. **Cleanup** — Redis TTL auto-expires entries, no manual purge needed

This keeps validation stateless from the SQL perspective while providing near-instant revocation.

### Multi-Guard Resolution for API Routes
For endpoints accepting both JWT and API Key auth, configure a composite middleware:

```php
// In routes/api.php
Route::middleware(['auth:jwt,api-key'])->group(function () { ... });
```

Laravel's `auth` middleware accepts comma-separated guards and tries each in order. First successful auth wins. If all fail, returns 401.

### Middleware Groups for routes/auth.php
Split `auth.php` into two middleware groups:

```php
// Browser endpoints (need CSRF, session)
Route::middleware(['web'])->group(function () {
    Route::get('/oauth/authorize', [AuthorizationController::class, 'authorize']);
    Route::post('/oauth/authorize', [AuthorizationController::class, 'approve']);
});

// Machine endpoints (no CSRF, no session)
Route::middleware(['throttle:oauth'])->group(function () {
    Route::post('/oauth/token', [TokenController::class, 'issueToken']);
    Route::post('/oauth/introspect', [IntrospectionController::class, 'introspect']);
    Route::post('/oauth/revoke', [RevocationController::class, 'revoke']);
});

// Public discovery (no auth, cacheable)
Route::get('/.well-known/jwks.json', [JwksController::class, 'show']);
Route::get('/.well-known/openid-configuration', [OpenIdConfigController::class, 'show']);
```

### API Error Response Format
All API errors use a consistent JSON structure:
```json
{
  "error": "insufficient_scope",
  "error_description": "The access token does not have the required scope: resources:write",
  "status": 403
}
```
OAuth endpoints use RFC 6749 error codes (`invalid_grant`, `invalid_client`, etc.). Resource API uses HTTP-style codes (`unauthorized`, `forbidden`, `not_found`, `validation_error`).

### Config (`config/identity-gateway.php`)
```php
return [
    'jwt' => [
        'access_token_ttl' => env('IGW_JWT_ACCESS_TOKEN_TTL', 900),  // 15 min (short-lived for security)
        'refresh_token_ttl' => env('IGW_JWT_REFRESH_TOKEN_TTL', 604800),
        'issuer' => env('APP_URL'),
        'audience' => env('APP_URL') . '/api/v1',
    ],
    'signing_keys' => [
        'algorithm' => 'RS256',
        'key_size' => 3072,
        'rotation_interval_days' => env('IGW_KEY_ROTATION_INTERVAL_DAYS', 7),
        'key_lifetime_days' => 90,
    ],
    'api_key' => ['prefix' => 'igw_live_', 'length' => 32],
];
```

---

## OAuth Flow Details

### Authorization Code Flow
1. Client → `GET /oauth/authorize?response_type=code&client_id=...&redirect_uri=...&scope=...&state=...`
2. Auth Server → login if no session → consent screen
3. User approves → `302 redirect_uri?code=...&state=...`
4. Client → `POST /oauth/token` with `grant_type=authorization_code&code=...&client_id=...&client_secret=...`
5. Auth Server → `{ access_token: "<JWT>", refresh_token: "...", expires_in: 3600 }`

### PKCE Flow (Authorization Code + Proof Key)
PKCE uses a **public client** (no client_secret). In Passport, create with `--public` flag or set `secret` to null.

1. Client generates PKCE pair:
   ```
   code_verifier = random_string(128)  // [A-Za-z0-9-._~]{43,128}
   code_challenge = base64url(sha256(code_verifier))
   ```
2. Client → `GET /oauth/authorize?response_type=code&client_id=...&redirect_uri=...&scope=...&state=...&code_challenge=<challenge>&code_challenge_method=S256`
3. Auth Server → login → consent → `302 redirect_uri?code=...&state=...`
4. Client → `POST /oauth/token` with `grant_type=authorization_code&code=...&redirect_uri=...&client_id=...&code_verifier=<verifier>` (NO client_secret)
5. Passport validates `sha256(code_verifier) == stored code_challenge`, issues tokens

**Demo seeder** must create a public PKCE client: `Passport::client()->forceFill(['secret' => null, 'redirect' => '...'])->save()` or use `php artisan passport:client --public`.

**DemoUserSeeder** creates both:
- `demo-confidential-client` — standard client with secret (for Auth Code + Client Credentials demos)
- `demo-public-client` — public client without secret (for PKCE demo)

### Client Credentials Flow
1. Client → `POST /oauth/token` with `grant_type=client_credentials&client_id=...&client_secret=...&scope=...`
2. Auth Server → `{ access_token: "<JWT>", expires_in: 3600 }` (no refresh token)

### Token Introspection (RFC 7662)
`POST /oauth/introspect` with Basic auth → `{ active: true, scope: "...", exp: ..., sub: "..." }` or `{ active: false }`

### Refresh Token Rotation
When a refresh token is used, Passport issues a new refresh token and revokes the old one. This is the default behavior in Passport 12. Configure in `AuthServiceProvider`:
```php
Passport::refreshTokensExpireIn(now()->addDays(7));
```
If a revoked refresh token is reused (replay attack), revoke the entire token family — all access and refresh tokens for that grant.

### Token Revocation (RFC 7009)
`POST /oauth/revoke` with Basic auth → `200 OK` (always, per RFC). On revocation, also add the `jti` to Redis blacklist for immediate stateless enforcement.

---

## Dependencies

### PHP (composer)
| Package | Purpose |
|---------|---------|
| laravel/framework ^12.0 | Core |
| laravel/passport ^12.0 | OAuth2 server |
| inertiajs/inertia-laravel ^2.0 | Server-side Inertia |
| spatie/laravel-permission ^6.0 | RBAC |
| firebase/php-jwt ^6.10 | JWT encode/decode (used for custom signing; Passport uses lcobucci/jwt internally — no conflict) |
| phpseclib/phpseclib ^3.0 | RSA key generation, JWK conversion |
| tightenco/ziggy ^2.0 | Laravel routes in JS |
| pestphp/pest ^3.0 | Testing (dev) |
| pestphp/pest-plugin-laravel ^3.0 | Laravel test helpers (dev) |
| laravel/telescope ^5.0 | Debug (dev) |
| laravel/pint ^1.0 | Code formatting (dev) |

### JavaScript (npm)
| Package | Purpose |
|---------|---------|
| react ^19.0, react-dom ^19.0 | UI |
| @inertiajs/react ^2.0 | Inertia React adapter |
| typescript ^5.7 | TypeScript |
| tailwindcss ^4.0, @tailwindcss/vite ^4.0 | Styling |
| @headlessui/react ^2.0 | Accessible UI primitives |
| @heroicons/react ^2.0 | Icons |
| jose ^6.0 | Client-side JWT decode/verify |
| prismjs ^1.29 | Code highlighting |
| react-hot-toast ^2.0 | Toasts |
| date-fns ^4.0 | Date formatting |
| vite ^6.0, @vitejs/plugin-react ^4.0 | Build |

---

## Implementation Phases

### Phase 1: Project Scaffold + Auth Foundation
**Goal:** Working Laravel app with user auth, Docker, Inertia+React.

1. Create Laravel 12 project at `/home/steve/repo/identity-gateway`
2. Set up `docker-compose.yml` (MySQL 8.4, phpMyAdmin, Redis 7, Mailpit)
3. Create `Dockerfile` (PHP 8.4-cli, Node, pdo_mysql, extensions)
4. Configure `.env` for MySQL, `APP_URL=http://localhost:8000`
5. Install Inertia.js + React: `composer require inertiajs/inertia-laravel`, set up `app.tsx`, `vite.config.ts`, Tailwind v4
6. Install Ziggy
7. Create `AuthLayout.tsx` with minimal styling
8. Build `Login.tsx`, `Register.tsx` pages
9. Build `LoginController`, `RegisterController`, `LogoutController`
10. Set up route files (`web.php`, `api.php`, `auth.php`)
11. Write Pest tests: `LoginTest`, `RegisterTest`

**Verification:** User can register, log in, log out via browser. Docker stack starts with `docker compose up`.

### Phase 2: OAuth2 Server + JWT Signing
**Goal:** Passport working with custom JWT signing via rotating keys.

1. Install Passport, run `passport:install`
2. Remove Passport default key files — we use `signing_keys` table
3. Create `signing_keys` migration + `SigningKey` model
4. Build `SigningKeyService` (generateKeyPair, getActiveKey, getValidationKeys, rotateKeys)
5. Build `JwtService` (sign, decode, verify)
6. Create `CustomBearerTokenResponse` extending League's `BearerTokenResponse` — override `generateHttpResponse()` to replace the access_token with a custom JWT from `JwtService::sign()`. Register via `AuthorizationServer::setResponseType()` in `AuthServiceProvider`
7. Create `RotateSigningKey` Artisan command
8. Build `JwksController` + `OpenIdConfigController`
9. Register `/.well-known/*` routes
10. Create `oauth_scopes` migration/model/seeder, register scopes in `AuthServiceProvider`
11. Write tests: `JwtServiceTest`, `SigningKeyServiceTest`, `JwksEndpointTest`

**Verification:** `GET /.well-known/jwks.json` returns valid JWKS. `php artisan jwt:rotate` creates new key and retires old.

### Phase 3: OAuth Flows + Token Endpoints
**Goal:** All grant types, consent screen, introspection, revocation.

1. Configure Passport grants (Auth Code, Client Credentials, Refresh Token, PKCE)
2. Build `AuthorizationController` wrapping Passport's authorization
3. Build `ConsentController` + `Consent.tsx` page
4. Build `TokenController` delegating to Passport
5. Build `TokenIntrospectionService` + `IntrospectionController` (RFC 7662)
6. Build `TokenRevocationService` + `RevocationController` (RFC 7009)
7. Create `oauth_consent_approvals` migration + model — store user-client-scope approvals to skip repeat consent
8. Create demo user + demo OAuth client seeders (confidential + public PKCE client)
9. Build `PurgeExpiredTokens` Artisan command + schedule in `routes/console.php` (daily purge of expired tokens, weekly key rotation)
10. Write tests: `AuthorizationCodeFlowTest`, `PkceFlowTest`, `ClientCredentialsFlowTest`, `RefreshTokenTest`, `TokenIntrospectionTest`, `TokenRevocationTest`

**Verification:** Complete Auth Code flow works end-to-end. PKCE flow works without client_secret. Tokens can be introspected and revoked. Repeat consent for same client/scopes is auto-approved.

### Phase 4: Custom Guards + Resource API
**Goal:** Stateless JWT validation, API key auth, protected endpoints.

1. Build `JwtGuard` implementing `Illuminate\Contracts\Auth\Guard`
2. Build `ApiKeyGuard`
3. Create `api_keys` migration + `ApiKey` model
4. Build `ApiKeyService` (generate, validate, revoke)
5. Register guards in `config/auth.php`
6. Build middleware: `ValidateJwtSignature` (includes Redis revocation blacklist check on `jti`), `RequireScope`, `ForceJsonResponse`
7. Build `UserController`, `ResourceController`, `ScopeController`
8. Create `resources` migration
9. Wire `routes/api.php` with middleware
10. Write tests: `JwtGuardTest`, `ApiKeyGuardTest`, `UserEndpointTest`, `ResourceEndpointTest`, `ApiKeyAuthTest`

**Verification:** `GET /api/v1/me` with valid JWT returns user. `X-Api-Key` header works. Missing/invalid tokens return 401. Wrong scopes return 403.

### Phase 5: RBAC + Dashboard + Audit Logging
**Goal:** Developer dashboard, role-based access, audit trail.

1. Install Spatie Permission, publish migration, create `RoleAndPermissionSeeder`
2. Add `HasRoles` to User model, include roles in JWT claims
3. Create `audit_logs` migration + `AuditLog` model
4. Build `AuditService` + events + `AuditEventSubscriber`
5. Build `AuditRequest` middleware
6. Create `DashboardLayout.tsx`
7. Build Dashboard pages: Index, Clients (CRUD), ApiKeys, Tokens, AuditLogs
8. Build React components: `ClientForm`, `ApiKeyCreateModal`, `TokenInspectModal`, `AuditLogTable`
9. Protect routes with session auth + Spatie permission middleware
10. Write tests: `ClientManagementTest`, `ApiKeyManagementTest`, `AuditLogTest`

**Verification:** Dashboard shows OAuth clients. Creating/revoking API keys works. Audit log captures all auth events.

### Phase 6: Demo App + Polish
**Goal:** Interactive demo application, documentation.

1. Create `DemoLayout.tsx`
2. Build shared demo components: `FlowStepIndicator`, `RequestResponsePanel`, `JwtDecodePanel`, `CodeSnippetBlock`, `ScopeSelector`
3. Build `Demo/Index.tsx` (card grid)
4. Build `Demo/Playground.tsx` (interactive OAuth flow executor)
5. Build `Demo/JwtInspector.tsx` (client-side decode + JWKS verify)
6. Build `Demo/AuthCodeFlow.tsx` (step-by-step with raw HTTP)
7. Build `Demo/PkceFlow.tsx` (SHA-256 visualization)
8. Build `Demo/ClientCredentialsFlow.tsx`
9. Build `Demo/IntrospectionDemo.tsx` + `RevocationDemo.tsx`
10. Add Telescope for local debugging
11. Create `README.md` with setup instructions
12. Full integration tests

**Verification:** All demo pages work interactively. OAuth playground completes full flow. JWT inspector decodes and verifies tokens.

---

## Testing Strategy

**Framework:** Pest PHP

**Unit tests** (mocked, fast): JwtService, SigningKeyService, ApiKeyService, AuditService, JwtGuard, ApiKeyGuard, all middleware.

**Feature tests** (real DB, `RefreshDatabase`): Every OAuth flow end-to-end, all API endpoints, dashboard CRUD, audit logging.

**Test traits:**
- `CreatesOAuthClient` — helper to create clients, get auth codes, get tokens
- `CreatesSigningKeys` — seed active/retired keys

**JS tests** (optional, Vitest): `JwtDecodePanel`, `FlowStepIndicator`, `OAuthConsentForm`

---

## Docker Setup

**docker-compose.yml:** 5 services
- `app` (PHP 8.4-cli, port 8000)
- `mysql` (MySQL 8.4, port 3307 → 3306)
- `phpmyadmin` (phpMyAdmin, port 8080 → 80, linked to mysql)
- `redis` (v7-alpine, port 6380 → 6379)
- `mailpit` (ports 8025/1025)

**Dockerfile:** PHP 8.4-cli with pdo_mysql, zip, intl, bcmath, redis extensions. Composer + npm. Serves via `php artisan serve`.

**docker-compose.yml details:**
```yaml
services:
  app:
    build: .
    ports: ["8000:8000"]
    depends_on: [mysql, redis]
    environment:
      DB_CONNECTION: mysql
      DB_HOST: mysql
      DB_PORT: 3306
      DB_DATABASE: identity_gateway
      DB_USERNAME: gateway_user
      DB_PASSWORD: secret
      REDIS_HOST: redis
    volumes: [".:/var/www/html"]

  mysql:
    image: mysql:8.4
    ports: ["3307:3306"]
    environment:
      MYSQL_ROOT_PASSWORD: root_secret
      MYSQL_DATABASE: identity_gateway
      MYSQL_USER: gateway_user
      MYSQL_PASSWORD: secret
    volumes: [mysql_data:/var/lib/mysql]
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]

  phpmyadmin:
    image: phpmyadmin:latest
    ports: ["8080:80"]
    depends_on: [mysql]
    environment:
      PMA_HOST: mysql
      PMA_USER: root
      PMA_PASSWORD: root_secret

  redis:
    image: redis:7-alpine
    ports: ["6380:6379"]
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]

  mailpit:
    image: axllent/mailpit:latest
    ports: ["8025:8025", "1025:1025"]

volumes:
  mysql_data:
```

**Dockerfile:**
```dockerfile
FROM php:8.4-cli
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libicu-dev nodejs npm \
    && docker-php-ext-install pdo_mysql zip intl bcmath \
    && pecl install redis && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN composer dump-autoload --optimize && npm run build
EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
```

---

## Verification (End-to-End)

After full implementation, verify:
1. `docker compose up` starts all services
2. `php artisan migrate --seed` sets up DB with roles, scopes, demo user/client
3. Register at `/auth/register`, log in at `/auth/login`
4. Create OAuth client at `/dashboard/clients`
5. Run Auth Code flow at `/demo/flows/auth-code` — get JWT
6. Paste JWT into `/demo/jwt-inspector` — decode and verify against JWKS
7. Use JWT to `GET /api/v1/me` — returns user data
8. Introspect token at `/demo/introspection` — returns active + claims
9. Revoke token at `/demo/revocation` — introspect confirms inactive
10. `php artisan jwt:rotate` — JWKS endpoint shows new + old key
11. `php artisan test` — all tests pass

---

## First Execution Step

Create the project directory and initialize Laravel:
```bash
mkdir -p /home/steve/repo/identity-gateway
cd /home/steve/repo/identity-gateway
composer create-project laravel/laravel .
```

Then write the spec document to `docs/specs/2026-03-17-identity-gateway-design.md` within the project.
