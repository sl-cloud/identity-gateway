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

## Phase 2: OAuth2 + JWT - TODO

Passport integration with custom JWT signing and key rotation.

- [ ] Install and configure Passport
- [ ] Database signing_keys table
- [ ] SigningKey model and service
- [ ] JwtService for token signing/verification
- [ ] Custom BearerTokenResponse
- [ ] JWKS and OpenID discovery endpoints
- [ ] Key rotation command
- [ ] OAuth scopes table and seeder

## Phase 3: OAuth Flows - TODO

Full OAuth2 implementation with consent and token management.

- [ ] Authorization Code flow
- [ ] PKCE support
- [ ] Client Credentials flow
- [ ] Consent screen and approvals
- [ ] Token introspection (RFC 7662)
- [ ] Token revocation (RFC 7009)
- [ ] Demo user and client seeders

## Phase 4: Resource API - TODO

Protected API with custom guards.

- [ ] JwtGuard (stateless validation)
- [ ] API key authentication
- [ ] ApiKeyGuard
- [ ] RequireScope middleware
- [ ] User and Resource controllers
- [ ] API routes setup

## Phase 5: Dashboard + RBAC - TODO

User dashboard for managing OAuth clients and API keys.

- [ ] Spatie Permission setup
- [ ] Audit logging
- [ ] Dashboard layout and navigation
- [ ] OAuth client management UI
- [ ] API key generation UI
- [ ] Token inspection UI
- [ ] Audit log viewer

## Phase 6: Demo App - TODO

Interactive OAuth playground.

- [ ] Demo layout and navigation
- [ ] OAuth playground page
- [ ] JWT inspector tool
- [ ] Flow walkthroughs
- [ ] Introspection demo
- [ ] Revocation demo

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
