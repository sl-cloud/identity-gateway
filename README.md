# Identity Gateway

[![Tests](https://github.com/sl-cloud/identity-gateway/actions/workflows/tests.yml/badge.svg)](https://github.com/sl-cloud/identity-gateway/actions/workflows/tests.yml)

An identity provider built with Laravel 12, React, and Inertia.js. Supports OAuth2 flows, JWT tokens, and API key authentication.

## Overview

This is a single Laravel application that serves three purposes:

1. **Auth Server** (`/oauth/*`, `/auth/*`, `/.well-known/*`) - Handles OAuth2 flows and JWT issuance
2. **Resource API** (`/api/v1/*`) - Protected REST endpoints using JWT or API key auth
3. **Demo App** (`/demo/*`) - Interactive OAuth demonstrations and debugging tools

## Screenshots

See [docs/SCREENSHOTS.md](docs/SCREENSHOTS.md) for application screenshots.

## Stack

| Component | Technology |
|-----------|------------|
| Backend | Laravel 12, PHP 8.4 |
| Frontend | React 19, Inertia.js |
| Styling | Tailwind CSS 4 |
| OAuth2 | Laravel Passport |
| Database | MySQL 8.4 |
| Cache | Redis 7 |
| Email | Mailpit |
| Monitoring | Laravel Telescope |
| Containers | Docker Compose |

## Requirements

- Docker and Docker Compose

## Quick Start (Docker)

```bash
# Clone and setup
git clone https://github.com/sl-cloud/identity-gateway.git
cd identity-gateway
cp .env.example .env

# Build and start services
docker compose up -d --build

docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

Access the app at http://localhost:8000

**Services:**
- Web app: http://localhost:8000
- phpMyAdmin: http://localhost:8080 (root / secret)
- Mailpit: http://localhost:8025
- Telescope: http://localhost:8000/telescope (local debugging)

### Common Commands (Run On Node/Host)

```bash
# View logs
docker compose logs -f app

# Run tests
docker compose exec app php artisan test

# Code style check
docker compose exec app ./vendor/bin/pint

# Stop everything
docker compose down -v  # -v removes volumes too
```

## Development Workflow (Docker Exec)

```bash
docker compose exec app composer install
docker compose exec app npm install
docker compose exec app php artisan key:generate

# Run migrations, frontend dev server, and app server
docker compose exec app php artisan migrate
docker compose exec app npm run dev
docker compose exec app php artisan serve
```

## Authentication

### Session Auth (Implemented)

- Login: `/auth/login`
- Register: `/auth/register`
- Dashboard: `/dashboard` (requires login)

### RBAC (Role-Based Access Control)

Spatie Laravel Permission provides granular access control:

**Roles:** `admin`, `developer`, `viewer`

**Permissions by Domain:**
- **OAuth Clients:** `clients:read`, `clients:create`, `clients:update`, `clients:revoke`
- **API Keys:** `api-keys:read`, `api-keys:create`, `api-keys:revoke`
- **Tokens:** `tokens:read`, `tokens:revoke`, `tokens:introspect`
- **Audit Logs:** `audit-logs:read`
- **System:** `system:manage-keys`

Role inheritance: Admin > Developer > Viewer (admins have all permissions)

### OAuth2 (Complete)

**JWT Signing with Rotating Keys:**
- Custom JWT tokens issued via Laravel Passport
- RSA-3072 key pairs stored in database
- Active key for signing, retired keys for validation
- Automatic weekly key rotation via scheduler
- JWKS endpoint for public key distribution

**Implemented Features:**
- ✅ Database-backed signing keys with encryption
- ✅ JWT signing service with rotating keys
- ✅ Custom Passport bearer token response
- ✅ JWKS endpoint (`/.well-known/jwks.json`)
- ✅ OpenID Connect discovery endpoint
- ✅ Key rotation command (`docker compose exec app php artisan jwt:rotate`)
- ✅ OAuth scope management
- ✅ Authorization Code flow
- ✅ PKCE (Proof Key for Code Exchange)
- ✅ Client Credentials flow
- ✅ Consent screen with approval management
- ✅ Token introspection (RFC 7662)
- ✅ Token revocation (RFC 7009)

### JWT Features

- 15-minute access tokens (configurable)
- RSA-3072 key signing with automatic rotation
- Token introspection (RFC 7662)
- Token revocation (RFC 7009)
- Redis-backed revocation list
- Stateless validation via JWKS

## Dashboard

Developer dashboard for managing OAuth clients, API keys, tokens, and viewing audit logs.

### Features

- **OAuth Clients:** Create, view, update, and revoke OAuth clients
- **API Keys:** Generate and revoke API keys (shown once at creation)
- **Token Inspector:** Decode and inspect JWT claims
- **Audit Logs:** Filterable view of all system actions with 26 audit event types
- **RBAC enforcement:** All routes protected by Spatie permissions

### Dashboard Routes

| Route | Description | Permission Required |
|-------|-------------|---------------------|
| `/dashboard` | Overview with stats | `auth` |
| `/dashboard/clients` | OAuth client list | `clients:read` |
| `/dashboard/clients/create` | Create new client | `clients:create` |
| `/dashboard/api-keys` | API key management | `api-keys:read` |
| `/dashboard/tokens` | Token list & inspector | `tokens:read` |
| `/dashboard/audit-logs` | Audit log viewer | `audit-logs:read` |

## Demo App

> **Note:** Demo routes are only available in `local` and `testing` environments. Access at [http://localhost:8000/demo](http://localhost:8000/demo).

**Documentation:** For a complete walkthrough of all OAuth2 demo features, see the [Demo Manual](docs/demo-manual/manual.md).

Public demo routes are available under `/demo`:

- `/demo` - Demo landing page with quick links
- `/demo/playground` - Interactive OAuth playground (Auth Code, PKCE, Client Credentials)
- `/demo/jwt-inspector` - JWT decode + JOSE signature verification against live JWKS
- `/demo/flows/auth-code` - Authorization Code walkthrough
- `/demo/flows/pkce` - PKCE walkthrough
- `/demo/flows/client-credentials` - Client Credentials walkthrough
- `/demo/introspection` - RFC 7662 token introspection demo
- `/demo/revocation` - RFC 7009 token revocation demo

### Demo Credentials

Seed demo data and use this account for browser-based OAuth flows:

- Email: `demo@identitygateway.test`
- Password: `password`

Run:

```bash
docker compose exec app php artisan migrate --seed
```

## API

### Resource Endpoints

| Endpoint | Method | Auth | Scope |
|----------|--------|------|-------|
| `/api/v1/me` | GET | JWT/API Key | `user:read` |
| `/api/v1/users` | GET | JWT/API Key | `users:read` |
| `/api/v1/resources` | GET/POST | JWT/API Key | `resources:read/write` |

### Auth Endpoints

| Endpoint | Description |
|----------|-------------|
| `GET /.well-known/openid-configuration` | OpenID Connect Discovery |
| `GET /.well-known/jwks.json` | JWKS endpoint |
| `POST /oauth/token` | Token issuance |
| `POST /oauth/introspect` | Token introspection |
| `POST /oauth/revoke` | Token revocation |
| `GET/POST /oauth/authorize` | Authorization endpoint |

## Testing

```bash
# Run all tests
docker compose exec app php artisan test

# Specific test file
docker compose exec app php artisan test tests/Feature/Auth/LoginTest.php

# Demo pages / routes coverage
docker compose exec app php artisan test --filter=DemoPagesTest

# Assign user role (admin/developer/viewer)
docker compose exec app php artisan user:assign-role user@example.com admin

# Code style
docker compose exec app ./vendor/bin/pint
```

## CI/CD

GitHub Actions runs tests on every push and PR to `main` and `develop`:

- Tests against MySQL 8.4 and Redis 7
- Laravel Pint code style checks
- Frontend build verification

## Project Structure

```
identity-gateway/
├── app/
│   ├── Console/Commands/       # Artisan commands
│   ├── Enums/                  # AuditAction and other enums
│   ├── Guards/                 # JWT and API key guards
│   ├── Http/Controllers/       # Request handlers
│   │   ├── Auth/               # OAuth2, login/logout
│   │   ├── Api/                # Resource API endpoints
│   │   ├── Dashboard/          # Dashboard management
│   │   └── Demo/               # Demo playground controllers
│   ├── Models/                 # Eloquent models
│   ├── Passport/               # Custom Passport classes
│   └── Services/               # Business logic
│       ├── AuditService.php    # Audit logging with redaction
│       ├── JwtService.php      # JWT signing/verification
│       └── ...
├── resources/js/
│   ├── components/ui/          # Reusable UI components
│   ├── layouts/                # Dashboard and auth layouts
│   └── pages/
│       ├── Auth/               # Login, register, consent
│       ├── Dashboard/          # Client, API key, token, audit pages
│       └── Demo/               # Playground, JWT inspector, flow walkthroughs
├── routes/                     # Route definitions
└── tests/                      # Feature and unit tests
```

## Security

- RSA-3072 keys for JWT signing
- 15-minute access tokens
- Automatic key rotation support
- Session, JWT, and API key guards
- Stateless JWT validation via Redis
- Comprehensive audit logging with secret redaction
- RBAC with Spatie Laravel Permission
- Cross-tenant isolation (users only see their own resources)

## Commands (Run From Node/Host Shell Using Docker Compose Exec)

```bash
# All commands below are executed from the node/host shell.
# App commands run in the app container via `docker compose exec app ...`.

# Key management
docker compose exec app php artisan jwt:rotate              # Rotate JWT signing keys (weekly via scheduler)

# Database
docker compose exec app php artisan db:seed --class=OAuthScopeSeeder          # Seed OAuth scopes
docker compose exec app php artisan db:seed --class=RoleAndPermissionSeeder   # Seed RBAC roles/permissions

# OAuth setup
docker compose exec app php artisan passport:install        # Setup Passport (one-time)
docker compose exec app php artisan passport:client         # Create OAuth client

# RBAC / permissions
docker compose exec app php artisan permission:show
docker compose exec app php artisan user:assign-role demo@identitygateway.test admin
```

## Contributing

1. Create a feature branch
2. Add tests for new features
3. Run `docker compose exec app ./vendor/bin/pint` before committing
4. Ensure all tests pass
5. Open a pull request

## License

MIT
