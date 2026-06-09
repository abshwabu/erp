# DECISIONS.md
# ERP SaaS — Architecture & Technology Decision Records (ADR)

All significant architectural and technology decisions are recorded here with context, options considered, and rationale. New decisions are appended in chronological order.

---

## Table of Contents

- [ADR-001: Modular Monolith over Microservices](#adr-001-modular-monolith-over-microservices)
- [ADR-002: Laravel as Backend Framework](#adr-002-laravel-as-backend-framework)
- [ADR-003: PostgreSQL as Primary Database](#adr-003-postgresql-as-primary-database)
- [ADR-004: Schema-per-Tenant Multi-Tenancy](#adr-004-schema-per-tenant-multi-tenancy)
- [ADR-005: React + TypeScript for Frontend](#adr-005-react--typescript-for-frontend)
- [ADR-006: REST over GraphQL for API](#adr-006-rest-over-graphql-for-api)
- [ADR-007: Redis for Caching and Queues](#adr-007-redis-for-caching-and-queues)
- [ADR-008: POS as PWA with Offline-First Design](#adr-008-pos-as-pwa-with-offline-first-design)
- [ADR-009: React Native for Mobile Apps](#adr-009-react-native-for-mobile-apps)
- [ADR-010: Stripe as Primary Payment Processor](#adr-010-stripe-as-primary-payment-processor)
- [ADR-011: Double-Entry Accounting Engine Built In-House](#adr-011-double-entry-accounting-engine-built-in-house)
- [ADR-012: Elasticsearch for Full-Text Search](#adr-012-elasticsearch-for-full-text-search)
- [ADR-013: S3-Compatible Object Storage for Files](#adr-013-s3-compatible-object-storage-for-files)
- [ADR-014: JWT + Session Hybrid Authentication](#adr-014-jwt--session-hybrid-authentication)
- [ADR-015: RBAC with Custom Roles](#adr-015-rbac-with-custom-roles)
- [ADR-016: Event-Driven Module Communication](#adr-016-event-driven-module-communication)
- [ADR-017: Soft Deletes on All Business Records](#adr-017-soft-deletes-on-all-business-records)
- [ADR-018: UUID Primary Keys](#adr-018-uuid-primary-keys)
- [ADR-019: Money as Integer (Cents) in Database](#adr-019-money-as-integer-cents-in-database)
- [ADR-020: Multi-Currency Support from Day One](#adr-020-multi-currency-support-from-day-one)
- [ADR-021: PDF Generation Strategy](#adr-021-pdf-generation-strategy)
- [ADR-022: Feature Flags for Progressive Rollout](#adr-022-feature-flags-for-progressive-rollout)
- [ADR-023: Separate Plans from Features](#adr-023-separate-plans-from-features)
- [ADR-024: Audit Log as Immutable Append-Only Table](#adr-024-audit-log-as-immutable-append-only-table)
- [ADR-025: No Hard Deletes on Financial Records](#adr-025-no-hard-deletes-on-financial-records)
- [ADR-026: Phased Module Rollout Strategy](#adr-026-phased-module-rollout-strategy)
- [ADR-027: Tax Engine Design](#adr-027-tax-engine-design)
- [ADR-028: Webhook Delivery with HMAC Signing](#adr-028-webhook-delivery-with-hmac-signing)
- [ADR-029: Reporting on Read Replica](#adr-029-reporting-on-read-replica)
- [ADR-030: Terraform for Infrastructure as Code](#adr-030-terraform-for-infrastructure-as-code)

---

## ADR-001: Modular Monolith over Microservices

**Status:** Accepted  
**Date:** Project Start

### Context
We need to choose how to structure the backend. The system has 15+ functional domains (POS, Inventory, HR, Accounting, etc.) that need to interact with each other. The team is small (2–5 engineers initially).

### Options Considered

| Option | Pros | Cons |
|--------|------|------|
| Microservices | Independent scaling, clear ownership | Massive operational complexity, distributed transactions, needs DevOps specialist, slow to build |
| Monolith (big ball of mud) | Fast to start | No boundaries, impossible to maintain at scale |
| Modular Monolith | Clear boundaries, single deployment, easy refactoring | Harder discipline required to maintain boundaries |

### Decision
**Modular Monolith.** Each domain is a self-contained module with strict no-cross-import rules. Modules communicate only via Events or published Service Interfaces. This gives us microservices-level boundaries with monolith-level operational simplicity.

### Consequences
- Must enforce boundary rules via code review and static analysis (PHPStan custom rules)
- Any module can be extracted to a service later if needed — the boundaries are already there
- Cannot independently deploy or scale individual modules until extraction

---

## ADR-002: Laravel as Backend Framework

**Status:** Accepted  
**Date:** Project Start

### Context
Need a backend framework for a complex SaaS application requiring: ORM, routing, queues, events, scheduler, authentication, validation, and a large ecosystem.

### Options Considered

| Option | Notes |
|--------|-------|
| Laravel (PHP) | Mature, batteries-included, large ecosystem, team expertise |
| NestJS (Node.js/TypeScript) | Strong TypeScript, but more boilerplate, smaller ERP ecosystem |
| Django (Python) | Good admin, but async story weaker, less suited for real-time |
| Rails (Ruby) | Productive but PHP team, smaller talent pool |

### Decision
**Laravel (PHP 8.3+).** The team has PHP/Laravel experience, the ecosystem has excellent packages for every need (Spatie for roles, permissions, media, tenancy; Livewire/Inertia for SSR; Horizon for queues), and PHP 8.x is significantly faster than older versions. The ERP space has a large pool of PHP developers.

### Consequences
- Locked into PHP ecosystem (acceptable — PHP is widely used and well-maintained)
- Must enforce PHP 8.3+ minimum for performance and type safety
- Use strict types everywhere: `declare(strict_types=1)`

---

## ADR-003: PostgreSQL as Primary Database

**Status:** Accepted  
**Date:** Project Start

### Context
Choosing a relational database. The application requires ACID transactions (financial data), complex joins (reporting), JSON support (flexible config), full-text search capabilities, and multi-schema support (tenancy).

### Options Considered

| Option | Notes |
|--------|-------|
| PostgreSQL | ACID, schemas (key for tenancy), JSONB, full-text search, row-level security |
| MySQL/MariaDB | No schema isolation, weaker JSONB, no row-level security |
| MongoDB | No joins, eventual consistency bad for financials |
| SQL Server | Expensive licensing, Windows-centric |

### Decision
**PostgreSQL 16.** Schema-per-tenant requires native schema support (MySQL lacks this). JSONB columns used for flexible metadata. Row-level security used as defense-in-depth layer. Full-text search via `tsvector` for basic search before Elasticsearch is needed.

### Consequences
- PostgreSQL-specific features used (schemas, JSONB, CTE, window functions) — not portable to MySQL
- PgBouncer required for connection pooling in production
- Must use `NUMERIC` for financial values, never `FLOAT`

---

## ADR-004: Schema-per-Tenant Multi-Tenancy

**Status:** Accepted  
**Date:** Project Start

### Context
Three common multi-tenancy strategies exist. The choice affects data isolation, performance, and migration complexity.

### Options Considered

| Strategy | Isolation | Performance | Complexity |
|----------|-----------|-------------|------------|
| Separate database per tenant | Perfect | Good (dedicated resources) | Very high ops cost at scale |
| Schema per tenant | Strong | Good (shared engine) | Medium |
| Shared tables with tenant_id | Weak (app-level only) | Best | Low, but risky |

### Decision
**Schema per tenant.** Uses PostgreSQL's native schema isolation. The `search_path` is set per connection to the tenant's schema, so all queries are automatically scoped. No risk of a missing WHERE clause leaking data across tenants. Uses the `stancl/tenancy` Laravel package.

### Consequences
- Schema migrations must run against all tenant schemas (handled by tenancy package)
- Cannot easily do cross-tenant analytics (requires superuser connection or dedicated analytics DB)
- Max practical limit ~10,000 schemas per PostgreSQL cluster — sufficient for SMB SaaS
- Tenant onboarding takes ~200ms to create and migrate new schema (acceptable)

---

## ADR-005: React + TypeScript for Frontend

**Status:** Accepted  
**Date:** Project Start

### Context
Frontend framework choice for a complex, data-heavy business application.

### Options Considered

| Option | Notes |
|--------|-------|
| React + TypeScript | Largest ecosystem, strong typing, most ERP-like apps use this |
| Vue 3 + TypeScript | Also good, Inertia.js works with Laravel natively |
| Inertia.js (Laravel + React/Vue) | Eliminates API layer for web app, but creates coupling |
| Angular | Strong typing but steep learning curve, large bundle |

### Decision
**React 18 + TypeScript with a decoupled SPA.** Although Inertia.js would reduce boilerplate, a decoupled SPA means the same API serves web, mobile, and third-party clients. TypeScript prevents an entire class of runtime errors in a complex codebase.

**TanStack Query** (React Query) for server state management — eliminates the need to manually manage loading/error/caching for API calls. **Zustand** for lightweight global UI state.

### Consequences
- Must maintain CORS configuration for the separate frontend origin
- API contract discipline required (API changes must be backward-compatible or versioned)
- TypeScript adds ~10% overhead to development but prevents many production bugs

---

## ADR-006: REST over GraphQL for API

**Status:** Accepted  
**Date:** Project Start

### Context
API design paradigm for both internal frontend consumption and public developer API.

### Options Considered

| Option | Pros | Cons |
|--------|------|------|
| REST | Well-understood, easy caching, great tooling, familiar to integrators | Over-fetching, multiple roundtrips |
| GraphQL | Flexible queries, single endpoint, strong typing | Complex caching, N+1 problems, steep learning curve for API consumers, harder to secure, overkill for most ERP operations |
| tRPC | Type-safe end-to-end if using TypeScript on both sides | Couples frontend/backend, not usable for public API |

### Decision
**REST with JSON:API specification.** The `?include=` and `?fields[]` parameters in JSON:API solve the over-fetching problem without GraphQL's complexity. REST APIs are more familiar to small business developers who will integrate with us. HTTP caching works naturally with REST.

### Consequences
- Some endpoints may need careful design to avoid N+1 queries (use eager loading)
- JSON:API spec requires specific envelope format — use a library, don't hand-roll

---

## ADR-007: Redis for Caching and Queues

**Status:** Accepted  
**Date:** Project Start

### Context
Need an in-memory data store for caching, session storage, queue backend, and real-time pub/sub.

### Options Considered

| Option | Notes |
|--------|-------|
| Redis | Industry standard, fast, supports lists/hashes/pub-sub, Laravel first-class support |
| Memcached | Simpler but no persistence, no data structures, no pub-sub |
| Database queues | Simple but slow, creates load on primary DB |
| SQS (AWS) | Good for queues but no caching, adds AWS dependency |

### Decision
**Redis for both caching and queues.** Laravel Horizon provides excellent visibility into queues. Using Redis for sessions means sessions survive app server restarts. Pub/sub used for WebSocket broadcasting.

Run Redis in **cluster mode** for production (3 nodes minimum) to avoid single point of failure.

### Consequences
- Redis is a stateful service — needs persistence (AOF + RDB) and backup
- Horizon dashboard must be secured (not publicly accessible)
- Redis key naming must include tenant ID prefix to avoid cross-tenant cache pollution

---

## ADR-008: POS as PWA with Offline-First Design

**Status:** Accepted  
**Date:** Module Design Phase

### Context
POS is the most critical module — downtime means lost sales. Internet connectivity in small retail environments is unreliable.

### Options Considered

| Option | Notes |
|--------|-------|
| Web app (online only) | Simple, but one internet drop stops all sales |
| Electron desktop app | Offline capable, but requires installation and updates |
| Native mobile app (iOS/Android) | Offline capable, but limited to mobile hardware |
| PWA (Progressive Web App) | Offline capable, works in browser, installable, cross-platform |

### Decision
**PWA with Service Worker + IndexedDB for offline storage.** Works on any device with a browser (tablets, dedicated POS hardware, desktops). Installable on device home screen. Service Worker intercepts all network requests, serving cached data when offline. Background Sync API queues transactions for upload when connection is restored.

### Consequences
- Complex offline sync logic, especially conflict resolution
- Service Worker updates require careful versioning (users can get stuck on old SW)
- Some hardware integrations (USB printers) require Web Serial API (limited browser support — provide a print server fallback)
- iOS has limitations on PWA background sync (use polling as fallback on iOS Safari)

---

## ADR-009: React Native for Mobile Apps

**Status:** Accepted  
**Date:** Mobile Phase

### Context
Mobile apps needed for warehouse workers (picking/counting), managers (approvals/dashboards), and field sales (CRM).

### Options Considered

| Option | Notes |
|--------|-------|
| React Native + Expo | Shared codebase with web (React), large community, Expo simplifies builds |
| Flutter | Good performance, single codebase, but Dart is a different language from web team |
| Native iOS + Android | Best performance, but 2x code, 2x team |
| Capacitor (web in shell) | Simple but poor performance for camera/barcode |

### Decision
**React Native with Expo.** The team already knows React. Sharing types and utility logic with the web frontend is a significant productivity win. Expo's EAS Build service handles CI/CD for app stores.

### Consequences
- Some native modules require ejecting from Expo managed workflow (e.g., advanced Bluetooth for POS printers)
- Performance-sensitive screens (barcode scanning) may need native modules
- App store review delays for updates — critical fixes need an OTA update path (Expo Updates)

---

## ADR-010: Stripe as Primary Payment Processor

**Status:** Accepted  
**Date:** Project Start

### Context
The POS module and online invoicing require payment processing. Different markets have different dominant payment methods.

### Decision
**Stripe as the default, with a pluggable payment adapter pattern.** Stripe has excellent APIs, good documentation, handles PCI compliance, and supports most countries. The integration layer uses a `PaymentGateway` interface so regional alternatives (Flutterwave for Africa, M-Pesa for East Africa, Razorpay for India) can be added without changing business logic.

```php
interface PaymentGateway {
    public function charge(PaymentRequest $request): PaymentResult;
    public function refund(RefundRequest $request): RefundResult;
    public function getStatus(string $transactionId): PaymentStatus;
}
```

### Consequences
- No raw card data ever touches our servers (tokenized via Stripe.js/Elements)
- PCI DSS SAQ A compliance only (the easiest tier)
- Stripe fees (~2.9% + 30¢) are the merchant's responsibility — clearly communicated
- Stripe Radar handles fraud detection

---

## ADR-011: Double-Entry Accounting Engine Built In-House

**Status:** Accepted  
**Date:** Accounting Module Design

### Context
The accounting module requires a proper double-entry bookkeeping engine. Options are to build it or integrate with an existing accounting package.

### Options Considered

| Option | Notes |
|--------|-------|
| Integrate with QuickBooks API | Complex sync, data lives outside our system, pricing issues, sync lag |
| Integrate with Xero API | Same problems as QuickBooks |
| Build in-house double-entry engine | Full control, real-time, no external dependency, complex to build correctly |

### Decision
**Build a double-entry accounting engine in-house.** The ERP's value is tight integration between operations (sales, procurement, payroll) and accounting. If accounting is in QuickBooks, real-time sync is fragile and always slightly out of date. Our accounting module is relatively straightforward — we are not building a full standalone accounting suite, just the GL, AP, AR, and tax reporting needed for SMB use.

**Core principle:** Every financial event in any module (sale, purchase, payroll, inventory adjustment) fires an event that the Accounting module handles by creating the appropriate journal entries automatically. The user never manually creates journal entries for operational transactions.

### Consequences
- Significant engineering investment upfront (~3 months for solid accounting module)
- Must handle: multi-currency, multi-period, period closing, reversals, adjustments
- Must be audited by an accountant before release to ensure correctness
- Provide QuickBooks/Xero export for customers who want to dual-run

---

## ADR-012: Elasticsearch for Full-Text Search

**Status:** Accepted  
**Date:** Inventory Module Design

### Context
Product catalogs can have 10,000+ SKUs. Customer lists can have 50,000+ records. PostgreSQL ILIKE queries become too slow for real-time typeahead search at this scale.

### Decision
**Elasticsearch (or OpenSearch) for search.** PostgreSQL remains the source of truth. Elasticsearch is a read-side index kept in sync via background jobs. Full-text search, fuzzy matching (typo tolerance), and faceted filtering (filter by category + price range simultaneously) are Elasticsearch's core strengths.

**Fallback:** If Elasticsearch is unavailable, fall back to PostgreSQL full-text search (`to_tsvector` + `to_tsquery`). Slower but functional.

### Consequences
- Data duplication between PostgreSQL and Elasticsearch — must handle sync failures gracefully
- Nightly full re-index as a safety net against sync drift
- Adds operational complexity — another service to monitor and scale
- OpenSearch is the Apache-licensed alternative if Elasticsearch licensing is a concern

---

## ADR-013: S3-Compatible Object Storage for Files

**Status:** Accepted  
**Date:** Project Start

### Context
Need to store receipts, invoices, payslips, product images, import files, and export files.

### Decision
**S3-compatible object storage (AWS S3 for production; MinIO for local development).** Laravel's Filesystem abstraction (via Flysystem) makes the storage backend swappable. All file access goes through the application — never direct S3 URLs. The application generates time-limited signed URLs.

**Regional options:** Wasabi (cheaper than S3 with no egress fees) is a viable alternative for cost optimization at scale.

### Consequences
- Never store direct S3 URLs in the database — store only the path/key, generate URLs at request time
- All files must be private — no public-access buckets
- Large file uploads (CSV imports) use pre-signed S3 upload URLs to avoid routing through the app server

---

## ADR-014: JWT + Session Hybrid Authentication

**Status:** Accepted  
**Date:** Auth Module Design

### Context
The web app, mobile app, and public API all need authentication, but their requirements differ.

### Decision

| Client | Mechanism | Reason |
|--------|-----------|--------|
| Web SPA | HttpOnly cookie session | CSRF-safe, no JS token exposure |
| Mobile app | JWT (Bearer token) | Stateless, works across app restarts |
| Public API | API Key (hashed) | Simple for integrators, no expiry handling |
| OAuth consumers | OAuth2 PKCE + JWT | Standard for third-party apps |

**Access token lifetime:** 15 minutes (JWT) / 2 hours (session)  
**Refresh token lifetime:** 30 days, rotated on each use  

### Consequences
- Two auth systems to maintain (session + JWT), but Laravel Sanctum handles both elegantly
- Refresh token rotation means a stolen refresh token can only be used once before it's invalidated
- API keys stored as bcrypt hash — compromise of DB doesn't expose working keys

---

## ADR-015: RBAC with Custom Roles

**Status:** Accepted  
**Date:** Auth Module Design

### Context
Different businesses have wildly different org structures. A 5-person retail shop needs simple roles (Owner, Cashier). A 50-person distribution company needs fine-grained permissions (Can approve POs under $500 but not over, can view HR but not salary data).

### Decision
**RBAC (Role-Based Access Control) with custom role creation per tenant, using `spatie/laravel-permission`.** 

- System provides default role templates (Owner, Manager, Accountant, Cashier, Warehouse Staff, HR Officer)
- Tenant admin can create custom roles by combining any set of permissions
- Permissions are atomic: `inventory.products.delete`, `hr.employees.view_salary`, etc.
- Permissions cached per user in Redis (15 min TTL) to avoid DB hits on every request

### Consequences
- ~200 permission strings to define and maintain
- Permission changes take up to 15 min to propagate (cache TTL) — acceptable for access control
- Must gate every API endpoint with appropriate policy check

---

## ADR-016: Event-Driven Module Communication

**Status:** Accepted  
**Date:** Architecture Design

### Context
Modules need to react to each other's actions. For example, completing a sale should: deduct inventory, post to accounting, update CRM, accumulate loyalty points, and send a receipt. These are cross-module concerns.

### Decision
**Laravel Events + Listeners for all cross-module side effects.** The module that owns the action fires an event. Other modules register listeners. Listeners that involve I/O (DB writes, emails) are queued (`ShouldQueue`).

**Rule:** No module imports a Model or calls a method directly from another module. The event is the contract.

### Consequences
- Debugging is harder (trace event → listener chain)
- Event contracts must be versioned carefully (adding fields OK, removing fields is a breaking change)
- Must document all events and their listeners in a central registry
- Circular event chains must be prevented (Sale fires StockChanged, StockChanged fires... nothing that eventually fires Sale again)

---

## ADR-017: Soft Deletes on All Business Records

**Status:** Accepted  
**Date:** Database Design

### Context
Users delete things by accident. Business records are also referenced in audit trails and financial history. Hard-deleting a product that appears on historical invoices would break those records.

### Decision
**All business records use soft deletes (`deleted_at` timestamp, via Laravel's `SoftDeletes` trait).** Deleted records are excluded from all normal queries automatically. They can be restored. Hard delete is only available to system admins via a separate, logged action.

**Exceptions:** Raw logs, analytics events, and audit entries are never deleted (not even soft-deleted).

### Consequences
- Database tables grow larger over time — must periodically archive old soft-deleted records to a separate archive table
- Unique constraints must account for soft-deleted records (e.g., SKU uniqueness should only apply to non-deleted products)
- Need to carefully handle cascade: deleting a Category soft-deletes the category, but products in that category are NOT auto-deleted

---

## ADR-018: UUID Primary Keys

**Status:** Accepted  
**Date:** Database Design

### Context
Should we use auto-incrementing integer IDs or UUIDs for primary keys?

### Options Considered

| Option | Pros | Cons |
|--------|------|------|
| Auto-increment INT | Small, fast index, human-friendly | Sequential = guessable (security), merge issues across tenants |
| UUID v4 | Non-guessable, globally unique, safe in URLs | Larger (16 bytes), slightly slower index, harder to debug |
| UUID v7 | Non-guessable, time-ordered (good for indexes), globally unique | Newer, less tooling support |

### Decision
**UUID v4 (PostgreSQL `gen_random_uuid()`) for all primary keys.** Non-sequential IDs prevent users from guessing other records' IDs. Multi-tenant data can be safely merged or migrated. IDs can be generated client-side before the DB insert (useful for optimistic UI).

Use **UUID v7** for high-write tables (audit_logs, stock_movements, pos_transactions) where index performance matters — v7's time-ordered nature reduces B-tree page splits.

### Consequences
- URLs look like `/invoices/550e8400-e29b-41d4-a716-446655440000` — acceptable for an API
- 16-byte PK vs 4-byte INT has measurable storage overhead on large tables (acceptable trade-off)
- Always index FK columns that reference UUIDs

---

## ADR-019: Money as Integer (Cents) in Database

**Status:** Accepted  
**Date:** Database Design

### Context
Floating-point arithmetic is dangerous for financial calculations (`0.1 + 0.2 = 0.30000000000000004`).

### Decision
**Store all monetary values as `BIGINT` (integer cents/smallest currency unit).** $12.99 is stored as `1299`. ETB 150.50 is stored as `15050`. The application layer converts to/from decimal for display.

**For currencies with no decimal places** (Japanese Yen, Ethiopian Birr in practice): the same pattern applies — 150 JPY stored as `15000` (using 2 implied decimal places uniformly).

Use the `Money` value object in PHP (via `moneyphp/money` library) throughout the application to prevent raw integer manipulation.

### Consequences
- All financial math is integer arithmetic — no floating-point errors
- Display layer must always divide by 100 (or currency-specific factor)
- Import/export must convert — document clearly in API docs
- NUMERIC(20,6) used for exchange rates (they need decimal precision)

---

## ADR-020: Multi-Currency Support from Day One

**Status:** Accepted  
**Date:** Project Start

### Context
Small businesses increasingly trade internationally. Adding multi-currency later to an ERP is extraordinarily painful — it touches every financial record.

### Decision
**Every monetary amount in the database has a companion `currency_code CHAR(3)` column.** The tenant has a "base currency" for reporting. All foreign currency amounts are also stored with the exchange rate used at transaction time (for historical accuracy — exchange rates change).

```sql
invoice_total_amount    BIGINT NOT NULL,
invoice_total_currency  CHAR(3) NOT NULL,
base_currency_amount    BIGINT NOT NULL,       -- converted to tenant's base currency
exchange_rate           NUMERIC(20,6) NOT NULL  -- rate used at time of transaction
```

Exchange rates are fetched from an external provider (Fixer.io or OpenExchangeRates) and cached for 1 hour.

### Consequences
- Every financial query that aggregates across currencies must convert using stored rates
- Reporting in base currency is always accurate to the time of transaction (not current rates)
- Adds complexity to every financial form (currency selector, rate display)
- Essential to get right upfront — retrofitting is a multi-month refactor

---

## ADR-021: PDF Generation Strategy

**Status:** Accepted  
**Date:** Documents Module Design

### Context
Need to generate PDFs for invoices, receipts, payslips, purchase orders, reports, etc.

### Options Considered

| Option | Notes |
|--------|-------|
| wkhtmltopdf | HTML→PDF, good fidelity, but unmaintained |
| Puppeteer (headless Chrome) | Best fidelity, but Node.js dependency in PHP app |
| DOMPDF (PHP) | Pure PHP, easy integration, limited CSS support |
| Gotenberg (Docker microservice) | Chrome-based, REST API, language-agnostic |
| LaTeX | Perfect typesetting but overkill and complex |

### Decision
**Gotenberg** — a Docker-based PDF microservice using headless Chrome. Called via REST API from Laravel. Templates are HTML/CSS (Blade templates rendered server-side, then sent to Gotenberg). This gives best-in-class PDF fidelity with no PHP-level rendering constraints.

**For receipts (thermal printing):** ESC/POS protocol directly from POS, not PDF.

### Consequences
- Additional Docker service to run and maintain
- PDF generation is async (queued job) for non-urgent documents
- Sync for on-demand downloads (user waits up to 3 seconds)
- Templates are standard HTML/CSS — frontend team can design them

---

## ADR-022: Feature Flags for Progressive Rollout

**Status:** Accepted  
**Date:** DevOps Design

### Context
New features need to be tested in production with a subset of tenants before full release. Some features should be enabled per plan or per tenant.

### Decision
**Feature flags using Laravel Pennant (built into Laravel 10+).** Flags can be:
- **Global:** on/off for all tenants
- **Plan-based:** on for Growth and Enterprise, off for Starter
- **Tenant-based:** on for specific tenant IDs (beta testers)
- **Percentage rollout:** on for 10% of tenants, ramping to 100%

Feature flags also gate plan-level restrictions: "Advanced Reporting" only available on Enterprise plan.

### Consequences
- Feature flag checks add minor overhead per request (cached in Redis)
- Technical debt: flags must be cleaned up after full rollout (add to sprint backlog)
- Product team can control rollout without a deployment

---

## ADR-023: Separate Plans from Features

**Status:** Accepted  
**Date:** Subscription Design

### Context
Plan limits and features change over time. Hard-coding plan logic ("if plan == 'starter' then max_users = 5") means a code deploy for every pricing change.

### Decision
**Plans and their feature limits stored in the database, not code.** 

```sql
plans: id, name, price, billing_period
plan_features: plan_id, feature_key, feature_value

-- Examples:
('starter', 'max_users', '5')
('starter', 'max_products', '500')
('starter', 'module_hr', 'false')
('growth', 'max_users', '25')
('growth', 'module_hr', 'true')
```

The application reads feature limits from the DB (cached in Redis). Changing a plan feature is a DB update, not a code deploy.

### Consequences
- Business team can adjust plan features without engineering
- Must validate plan limits at the right layer (service layer, not just UI)
- Plan cache must be busted when features change

---

## ADR-024: Audit Log as Immutable Append-Only Table

**Status:** Accepted  
**Date:** Compliance Design

### Context
Compliance and debugging require a full history of who did what and when. Users should not be able to cover their tracks.

### Decision
**`audit_logs` table is append-only.** No UPDATE or DELETE operations are ever permitted on this table — enforced via PostgreSQL row-level security (RLS) policy:

```sql
CREATE POLICY no_update ON audit_logs FOR UPDATE USING (false);
CREATE POLICY no_delete ON audit_logs FOR DELETE USING (false);
```

The application user does not have UPDATE/DELETE privileges on `audit_logs`. Only a superuser can modify audit logs (emergency ops use only, requiring two-person authorization).

Each log entry contains: `tenant_id, user_id, module, action, resource_type, resource_id, old_values (JSONB), new_values (JSONB), ip_address, user_agent, timestamp`.

### Consequences
- Audit log table grows without bound — partition by month, archive to cold storage after 2 years
- Cannot "fix" bad audit entries — intentional
- Must be careful about what goes in `old_values`/`new_values` (redact passwords, tokens, card numbers)

---

## ADR-025: No Hard Deletes on Financial Records

**Status:** Accepted  
**Date:** Accounting Module Design

### Context
Accounting regulations in most countries require that financial records be retained for 5–10 years. Hard-deleting invoices, journal entries, or transactions is illegal in many jurisdictions.

### Decision
**Financial records (invoices, payments, journal entries, payroll runs, purchase orders) can only be cancelled or reversed — never deleted.** A cancelled invoice gets a `status = 'cancelled'` and is hidden from normal views, but the record and its audit trail remain. An incorrect journal entry is corrected by posting a reversal entry (standard accounting practice).

This applies even if the user explicitly requests deletion — the UI offers "Cancel" not "Delete."

### Consequences
- Users may be confused why they "can't delete" an invoice — requires UX education
- Database never shrinks for financial tables (acceptable — financial records are relatively small)
- Consistent with how accountants and auditors expect systems to work

---

## ADR-026: Phased Module Rollout Strategy

**Status:** Accepted  
**Date:** Product Planning

### Context
Building all 15+ modules simultaneously is not feasible. Need a prioritized rollout that delivers value early and generates revenue to fund further development.

### Decision

| Phase | Duration | Modules | Target Customer |
|-------|----------|---------|-----------------|
| Phase 1 | Months 1–4 | Core Platform, POS, Inventory, Basic Invoicing, Basic Accounting (AR/AP/GL) | Retail shops |
| Phase 2 | Months 5–8 | Warehouse Management, Procurement, HR & Payroll, Full Sales Orders | Distribution companies |
| Phase 3 | Months 9–12 | CRM, E-Commerce, Advanced Reporting, Mobile Apps | Growth-stage SMBs |
| Phase 4 | Months 13+ | Manufacturing, Projects, Customer Support, Public API, 3rd Party Integrations | Manufacturing SMBs |

Phase 1 is a fully sellable product. Revenue from Phase 1 funds Phase 2.

### Consequences
- Must design database schema with future modules in mind (don't make breaking schema changes between phases)
- API versioning critical from Phase 1 — public-facing endpoints must not break between phases
- Marketing must communicate roadmap clearly to avoid churn from customers expecting unreleased features

---

## ADR-027: Tax Engine Design

**Status:** Accepted  
**Date:** Accounting Module Design

### Context
Tax rules vary enormously by country: VAT in Europe, GST in India/Australia, Sales Tax in the US (state by state), WHT (withholding tax) in many African countries. The ERP targets multiple countries.

### Decision
**Pluggable tax engine with country-specific implementations.** A `TaxCalculator` interface is defined. Country-specific implementations are registered per tenant's country. Tax rates are stored in the database and can be configured by the tenant.

```php
interface TaxCalculator {
    public function calculate(TaxableTransaction $transaction): TaxResult;
    public function getApplicableRates(Product $product, Customer $customer): array;
}

// Implementations:
EthiopiaTaxCalculator  (VAT 15%)
KenyaTaxCalculator     (VAT 16% + WHT)
UKTaxCalculator        (VAT 20%/5%/0%)
USATaxCalculator       (state + county + city rate lookup)
```

For complex jurisdictions (US multi-level sales tax): integrate with Avalara or TaxJar via their API rather than maintaining rate tables ourselves.

### Consequences
- Must validate tax calculations with accountants in each target country before launch
- US tax calculation is a specialist problem — outsource to Avalara/TaxJar for US market
- Tax rule changes (rate changes, exemptions) must be applied to the DB without code deploy

---

## ADR-028: Webhook Delivery with HMAC Signing

**Status:** Accepted  
**Date:** Integration Module Design

### Context
When delivering webhooks to customer endpoints, we need to ensure: (1) the customer can verify the payload came from us, (2) replay attacks are prevented, (3) failed deliveries are retried reliably.

### Decision
**HMAC-SHA256 signatures on all webhook payloads.** Each webhook endpoint has a unique secret. Every delivery includes:

```
X-ERP-Signature: sha256=<HMAC-SHA256 of payload with endpoint secret>
X-ERP-Timestamp: <Unix timestamp of delivery>
X-ERP-Delivery-ID: <UUID of this delivery attempt>
```

Customers validate the signature. The timestamp prevents replay attacks (reject if timestamp > 5 minutes old). Delivery is retried 5 times with exponential backoff. All delivery attempts logged.

### Consequences
- Customers must implement signature verification (document clearly with code examples in multiple languages)
- Secrets must be rotatable without downtime (support two simultaneous valid secrets during rotation window)

---

## ADR-029: Reporting on Read Replica

**Status:** Accepted  
**Date:** Performance Design

### Context
Complex reports (P&L across 2 years, inventory aging, payroll summaries) run long-running queries. These compete with transactional queries on the primary database, slowing down normal operations.

### Decision
**All reporting queries run on a PostgreSQL read replica.** The replica is typically 0–5 seconds behind the primary (acceptable for reports). The application automatically routes queries tagged `->onReadConnection()` to the replica.

For the future: when PostgreSQL reports become too slow (>5 seconds for typical reports), introduce ClickHouse as an OLAP layer fed by CDC (Change Data Capture) from PostgreSQL.

### Consequences
- Replica lag means reports may be slightly out of date — display "Data as of: [timestamp]" on reports
- Schema changes must be backward-compatible (replica runs same schema as primary during migrations)
- Replica can be scaled vertically independently from primary

---

## ADR-030: Terraform for Infrastructure as Code

**Status:** Accepted  
**Date:** DevOps Design

### Context
Infrastructure needs to be reproducible, version-controlled, and auditable. Manual cloud console changes lead to drift and undocumented configurations.

### Decision
**Terraform for all cloud infrastructure.** Every AWS/DigitalOcean resource (VPC, subnets, security groups, RDS instances, ElastiCache, ECS clusters, S3 buckets, IAM roles) is defined in Terraform. State stored in S3 with DynamoDB locking.

No manual changes to production infrastructure via cloud console — all changes go through Terraform PR review.

**Local development:** Docker Compose replicates the production stack locally (PostgreSQL, Redis, Elasticsearch, MinIO, Gotenberg, Mailhog).

### Consequences
- Terraform learning curve for the team (acceptable — industry standard)
- Terraform state file contains sensitive data — must be encrypted and access-controlled
- Infrastructure changes go through code review (security benefit: prevents unauthorized changes)
- Can spin up a complete staging environment in ~10 minutes from scratch
