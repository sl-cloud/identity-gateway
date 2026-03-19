# Identity Gateway Roadmap

Project plan and implementation status.

## Phase 1: Foundation - DONE

Laravel app with session auth, Docker setup, React frontend.

- [x] Laravel 12 project setup
- [x] Docker environment (MySQL, Redis, Mailpit, phpMyAdmin)
- [x] React + Inertia + Tailwind frontend
- [x] Session-based login/register
- [x] Dashboard page
- [x] Tests (12 passing)
- [x] GitHub Actions CI/CD

## Phase 2: OAuth2 + JWT - DONE ✅

Passport integration with custom JWT signing and key rotation.

- [x] Install and configure Passport
- [x] Database signing_keys table with encrypted private keys
- [x] SigningKey model and service
- [x] JwtService for token signing/verification
- [x] Custom BearerTokenResponse for Passport integration
- [x] JWKS and OpenID discovery endpoints
- [x] Key rotation command (`php artisan jwt:rotate`)
- [x] OAuth scopes table and seeder
- [x] Tests: 80+ passing with comprehensive coverage

## Phase 3: OAuth Flows - DONE ✅

Full OAuth2 implementation with consent and token management.

- [x] Authorization Code flow
- [x] PKCE support
- [x] Client Credentials flow
- [x] Consent screen and approvals
- [x] Token introspection (RFC 7662)
- [x] Token revocation (RFC 7009)
- [x] Comprehensive test coverage (80+ tests)

## Phase 4: Resource API - DONE ✅

Protected API with custom guards and scope-based access control.

- [x] JwtGuard (stateless validation with multi-guard support)
- [x] API key authentication with hashed storage
- [x] ApiKeyGuard with expiration and revocation
- [x] RequireScope middleware for OAuth2-style scopes
- [x] UserController and ResourceController
- [x] API routes with JWT/API key dual auth setup
- [x] ForceJsonResponse and ValidateJwtSignature middleware

## Phase 5: Dashboard - DONE ✅

User dashboard for managing OAuth clients and viewing OAuth information.

- [x] Dashboard layout and navigation
- [x] OAuth statistics (clients, consents, signing keys)
- [x] OAuth 2.0 / OIDC endpoints display with copy-to-clipboard
- [x] Supported OAuth flows showcase
- [x] User's OAuth clients listing
- [x] Active consent approvals with scopes
- [x] Available scopes display

### Phase 5b: RBAC + Audit - DONE ✅

- [x] Spatie Permission setup with roles (admin, developer, viewer)
- [x] Audit logging with 26 action types and secret redaction
- [x] API key generation UI (show once pattern)
- [x] Token inspection UI with JWT decoding
- [x] Audit log viewer with filtering
- [x] Cross-tenant isolation (owner-scoped queries)
- [x] RBAC enforcement on all dashboard routes
- [x] Comprehensive test coverage (authorization, isolation, audit records)

## Phase 6: Demo App - IN PROGRESS

Interactive OAuth playground.

- [ ] Demo layout and navigation
- [ ] OAuth playground page
- [ ] JWT inspector tool
- [ ] Flow walkthroughs (Auth Code, PKCE, Client Credentials)
- [ ] Introspection demo
- [ ] Revocation demo

---

## Current Test Count

- **Unit Tests:** JWT service, guards, middleware, audit redaction
- **Feature Tests:** OAuth flows, API endpoints, dashboard authorization
- **Total:** 80+ tests passing

---

## Running Tests

```bash
docker compose exec app php artisan test
```

## Development Notes

### Starting Phase 2

```bash
docker compose exec app php artisan migrate
docker compose exec app composer require laravel/passport
```

### Testing Checklist

After each phase:
- All tests pass
- Docker builds successfully
- Frontend compiles
- Login/logout works

Final verification:
- OAuth flows complete end-to-end
- JWTs verify against JWKS
- Token introspection returns correct data
- Revoked tokens fail validation
- Key rotation updates JWKS
