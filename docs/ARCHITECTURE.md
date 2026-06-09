# ARCHITECTURE.md
# ERP SaaS — System Architecture

---

## Table of Contents

1. [Overview](#overview)
2. [Architectural Style](#architectural-style)
3. [High-Level System Diagram](#high-level-system-diagram)
4. [Layer Breakdown](#layer-breakdown)
5. [Multi-Tenancy Design](#multi-tenancy-design)
6. [Backend Architecture](#backend-architecture)
7. [Frontend Architecture](#frontend-architecture)
8. [Database Architecture](#database-architecture)
9. [Caching Strategy](#caching-strategy)
10. [Queue & Background Jobs](#queue--background-jobs)
11. [File Storage](#file-storage)
12. [Search Architecture](#search-architecture)
13. [Authentication & Authorization](#authentication--authorization)
14. [API Design](#api-design)
15. [Real-Time & WebSockets](#real-time--websockets)
16. [Notification System](#notification-system)
17. [Reporting & Analytics Architecture](#reporting--analytics-architecture)
18. [Offline & Mobile Architecture](#offline--mobile-architecture)
19. [Integration Architecture](#integration-architecture)
20. [Infrastructure & Deployment](#infrastructure--deployment)
21. [Security Architecture](#security-architecture)
22. [Observability & Monitoring](#observability--monitoring)
23. [Disaster Recovery & Backup](#disaster-recovery--backup)
24. [Scalability Plan](#scalability-plan)

---

## Overview

This document describes the complete system architecture for the ERP SaaS platform targeting small and medium businesses. The system is designed as an **API-first, multi-tenant SaaS** with a modular monolith backend that can be selectively decomposed into services as load demands it.

**Design Principles:**
- **Tenant isolation first** — no cross-tenant data leakage under any circumstance
- **Offline-capable** — critical workflows (POS, warehouse picking) function without internet
- **Event-driven internally** — modules communicate via domain events, not direct coupling
- **Boring technology** — choose proven, well-supported tools over cutting-edge ones
- **Progressive scalability** — start simple, scale specific bottlenecks as needed

---

## Architectural Style

### Primary: Modular Monolith

The backend is organized as a **modular monolith** — a single deployable application divided into well-defined internal modules (domains). Each module owns its routes, controllers, services, models, and database migrations.

```
Monolith (single process) but with:
  ├── Hard module boundaries (no cross-module model imports)
  ├── Module-to-module communication via Events or Service Interfaces
  ├── Each module has its own database table prefix/schema
  └── Any module can be extracted to a microservice later if needed
```

**Why not microservices from day one?**
- Premature distributed systems add 10x operational complexity
- A modular monolith is refactorable; a poorly designed microservice mesh is not
- Single deployment unit is easier to test, deploy, and debug

**Future extraction candidates (when traffic demands it):**
- POS (high-frequency transactions, offline sync)
- Reporting (heavy read queries, separate DB replica)
- Notification Service (high volume, independent scaling)

---

## High-Level System Diagram

```
┌─────────────────────────────────────────────────────────────────────────┐
│                          CLIENTS                                         │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌─────────────┐ │
│  │  Web App     │  │ Mobile App   │  │  POS PWA     │  │  Public API │ │
│  │ (React/Vue)  │  │(React Native)│  │  (Offline)   │  │  Consumers  │ │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘  └──────┬──────┘ │
└─────────┼─────────────────┼─────────────────┼─────────────────┼────────┘
          │                 │                 │                 │
          ▼                 ▼                 ▼                 ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                         CDN / Edge (Cloudflare)                          │
│              Static assets · DDoS protection · WAF · SSL                │
└─────────────────────────────────────┬───────────────────────────────────┘
                                      │
                                      ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                         Load Balancer (Nginx / ALB)                      │
│                    Health checks · SSL termination                       │
└───────────┬──────────────────────────────────────────┬──────────────────┘
            │                                          │
            ▼                                          ▼
┌───────────────────────┐               ┌──────────────────────────┐
│   API Server          │               │   Queue Workers           │
│   (Laravel / PHP)     │               │   (Laravel Horizon)       │
│   Stateless · N pods  │               │   Email · SMS · Reports   │
│                       │               │   Sync · Notifications    │
│  ┌─────────────────┐  │               └──────────────────────────┘
│  │  Module Router  │  │
│  │  Auth Middleware│  │               ┌──────────────────────────┐
│  │  Tenant Context │  │               │   Scheduler               │
│  └────────┬────────┘  │               │   (Laravel Schedule)      │
│           │           │               │   Payroll · Reports       │
│  ┌────────▼────────┐  │               │   Reminders · Cleanup     │
│  │  Domain Modules │  │               └──────────────────────────┘
│  │  (see below)    │  │
│  └────────┬────────┘  │
└───────────┼───────────┘
            │
            ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                         DATA LAYER                                       │
│                                                                          │
│  ┌────────────────┐  ┌────────────┐  ┌──────────────┐  ┌─────────────┐ │
│  │  PostgreSQL    │  │   Redis    │  │Elasticsearch │  │  S3 Storage │ │
│  │  (Primary DB)  │  │(Cache/Queue│  │  (Search)    │  │  (Files)    │ │
│  │  + Read Replica│  │  /Sessions)│  │              │  │             │ │
│  └────────────────┘  └────────────┘  └──────────────┘  └─────────────┘ │
└─────────────────────────────────────────────────────────────────────────┘
            │
            ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                      EXTERNAL SERVICES                                   │
│  Stripe  │  Twilio  │  SendGrid  │  Plaid  │  DHL  │  WhatsApp API      │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## Layer Breakdown

### Request Lifecycle

```
HTTP Request
  → Cloudflare (WAF, DDoS, cache static)
  → Load Balancer (SSL termination, health routing)
  → Nginx (reverse proxy, gzip, rate limiting)
  → PHP-FPM (Laravel application)
    → Middleware stack:
        TrustProxies
        → CorsMiddleware
        → ThrottleRequests (rate limiting per tenant)
        → AuthenticateJWT
        → IdentifyTenant (sets tenant context)
        → ValidateSubscription (checks plan limits)
        → RouteServiceProvider
    → Controller
    → FormRequest (validation)
    → Service Layer (business logic)
      → Repository / Eloquent (data access)
      → Events (side effects dispatched async)
    → API Resource (response transformation)
  → JSON Response
```

---

## Multi-Tenancy Design

### Strategy: Single Database, Schema-per-Tenant

Each tenant gets their own PostgreSQL **schema** within a single database cluster. The public schema holds shared tables (plans, system config). Every tenant schema has identical structure.

```sql
-- Public schema (shared)
public.tenants
public.plans
public.plan_features

-- Tenant schemas (isolated per business)
tenant_abc123.users
tenant_abc123.products
tenant_abc123.orders
tenant_abc123.employees
...

tenant_xyz789.users
tenant_xyz789.products
...
```

**Tenant identification:**
```
Subdomain:  acme.yourerp.com  → tenant slug = "acme"
Custom domain: erp.acme.com   → resolved via tenants.custom_domain
Header:     X-Tenant-ID: abc123 (for API consumers)
```

**Tenant context middleware:**
```php
// Automatically set for every request
IdentifyTenant::class → sets DB schema search_path
                      → boots tenant-scoped cache prefix
                      → sets tenant config (locale, currency, timezone)
```

**Connection switching (using stancl/tenancy):**
```php
tenancy()->initialize($tenant);
// All subsequent DB queries use tenant schema
// All cache keys prefixed with tenant ID
// All storage paths scoped to tenant
```

---

## Backend Architecture

### Technology: Laravel (PHP 8.3+)

```
app/
├── Modules/                    ← Domain modules (the core)
│   ├── Core/                   ← Auth, Users, Tenants, Plans
│   ├── POS/                    ← Point of Sale
│   ├── Inventory/              ← Stock, Products, Movements
│   ├── Warehouse/              ← Bins, Picking, Receiving
│   ├── Procurement/            ← PO, Suppliers, RFQ
│   ├── HR/                     ← Employees, Leave, Attendance
│   ├── Payroll/                ← Salary, Payslips, Tax
│   ├── Accounting/             ← GL, AP, AR, Bank Recon
│   ├── Sales/                  ← Quotes, Orders, Invoices
│   ├── CRM/                    ← Contacts, Pipeline, Leads
│   ├── Ecommerce/              ← Store, Products, Checkout
│   ├── Manufacturing/          ← BOM, Work Orders, MRP
│   ├── Projects/               ← Projects, Tasks, Timesheets
│   ├── Assets/                 ← Fixed Assets, Depreciation
│   ├── Support/                ← Tickets, SLA, Agents
│   ├── Documents/              ← Files, Templates, Signatures
│   ├── Reporting/              ← Reports, Dashboards, KPIs
│   ├── Notifications/          ← Channels, Templates, Dispatching
│   └── Integrations/           ← API connectors, Webhooks
│
├── Http/
│   ├── Middleware/
│   └── Kernel.php
│
├── Providers/
│   └── ModuleServiceProvider.php   ← Auto-discovers and registers modules
│
├── Infrastructure/
│   ├── Events/                 ← Base event classes
│   ├── Jobs/                   ← Base job classes
│   ├── Repositories/           ← Base repository interfaces
│   └── Services/               ← Cross-cutting services (Currency, Tax, Address)
│
└── Console/
    └── Commands/               ← Artisan commands per module
```

### Module Internal Structure

```
Modules/Inventory/
├── Controllers/
│   ├── ProductController.php
│   ├── StockMovementController.php
│   └── StockAdjustmentController.php
├── Models/
│   ├── Product.php
│   ├── ProductVariant.php
│   ├── StockLevel.php
│   └── StockMovement.php
├── Services/
│   ├── StockService.php         ← Business logic
│   └── ReorderService.php
├── Repositories/
│   └── ProductRepository.php
├── Events/
│   ├── StockLevelChanged.php
│   └── LowStockDetected.php
├── Listeners/
│   └── NotifyLowStock.php
├── Jobs/
│   └── ProcessReorderAlert.php
├── Requests/
│   ├── CreateProductRequest.php
│   └── AdjustStockRequest.php
├── Resources/
│   └── ProductResource.php
├── Policies/
│   └── ProductPolicy.php
├── database/
│   └── migrations/
└── routes/
    └── api.php
```

### Cross-Module Communication Rules

```
ALLOWED:
  Module A → fires Event → Module B listens
  Module A → calls Interface (injected) → Module B implements

FORBIDDEN:
  Module A → imports Model from Module B directly
  Module A → calls Module B Controller
  Module A → queries Module B table directly

EXAMPLE:
  // POS fires event after sale
  event(new SaleCompleted($sale));

  // Inventory listens and deducts stock
  class DeductStockOnSale implements ShouldQueue {
    public function handle(SaleCompleted $event) { ... }
  }

  // Accounting listens and posts journal entries
  class PostSaleToGL implements ShouldQueue {
    public function handle(SaleCompleted $event) { ... }
  }
```

---

## Frontend Architecture

### Technology: React 18 + TypeScript

```
frontend/
├── src/
│   ├── app/                    ← App shell, routing, providers
│   ├── modules/                ← Mirrors backend module structure
│   │   ├── pos/
│   │   │   ├── components/
│   │   │   ├── pages/
│   │   │   ├── hooks/
│   │   │   ├── store/          ← Zustand slice
│   │   │   └── api/            ← API call functions
│   │   ├── inventory/
│   │   ├── hr/
│   │   └── ...
│   ├── shared/
│   │   ├── components/         ← Design system components
│   │   ├── hooks/              ← useAuth, useTenant, usePermission
│   │   ├── layouts/            ← AppLayout, AuthLayout, PrintLayout
│   │   └── utils/
│   └── config/
│
├── public/
└── vite.config.ts
```

**State management:**
- **Zustand** for global UI state (current user, tenant, permissions, sidebar)
- **TanStack Query (React Query)** for all server state — caching, background refetch, optimistic updates
- **Local component state** (useState) for ephemeral UI state

**POS as a PWA (Progressive Web App):**
```
Separate build target: /pos
Service Worker caches:
  - Product catalog (full local copy, updated on sync)
  - Pending sales queue (transactions while offline)
  - Customer list (for loyalty lookup)
Background Sync API: flushes pending queue when connection restored
IndexedDB: local storage for offline data
```

---

## Database Architecture

### Primary: PostgreSQL 16

**Connection pooling:** PgBouncer in transaction mode (pool per tenant schema recommended).

**Schema design principles:**
- All tables have `id UUID PRIMARY KEY DEFAULT gen_random_uuid()`
- All tables have `created_at TIMESTAMPTZ`, `updated_at TIMESTAMPTZ`
- Soft deletes via `deleted_at TIMESTAMPTZ` (never hard delete business records)
- Money stored as `INTEGER` (cents) or `NUMERIC(20,6)` — never FLOAT
- All financial amounts have a `currency_code CHAR(3)` companion column
- Foreign keys enforced at DB level, not just application level
- Indexes created for every foreign key and common query filter

**Read replica:**
- All reports and analytics queries routed to read replica
- Write operations always go to primary
- Replica lag monitored — reports degrade gracefully if lag > 30s

**Key table groups by module:**

```sql
-- Core
tenants, users, roles, permissions, role_user, permission_role
audit_logs, notifications, notification_templates, media

-- Inventory
products, product_variants, product_categories
stock_levels, stock_movements, stock_adjustments
warehouses, locations (bins)

-- POS
pos_sessions, pos_transactions, pos_transaction_items
pos_payments, pos_shifts

-- Accounting (double-entry)
accounts (chart of accounts), journals, journal_entries
tax_rates, tax_groups

-- HR
employees, departments, positions
leave_types, leave_requests, leave_balances
attendance_logs, payroll_runs, payslips
```

---

## Caching Strategy

### Redis (via Laravel Cache)

```
Cache layers:

L1 — Application cache (Redis):
  - Tenant config: cache for 1 hour, bust on settings change
  - User permissions: cache for 15 min, bust on role change
  - Product catalog (POS): cache for 5 min
  - Exchange rates: cache for 1 hour
  - Tax rates: cache for 1 day
  - Report results: cache for 10 min (stale-while-revalidate)

L2 — HTTP cache (Cloudflare):
  - Static assets: 1 year (content-hashed filenames)
  - Public API docs: 1 day

Cache invalidation strategy:
  - Tag-based invalidation (Laravel cache tags)
  - Event-driven: CacheInvalidator listens to domain events
    e.g., ProductUpdated → bust product cache for that tenant
```

---

## Queue & Background Jobs

### Laravel Horizon + Redis

**Queue configuration:**

```php
Queues (in priority order):
  critical    → payment processing, fraud alerts
  high        → notifications, email, SMS
  default     → report generation, data sync
  low         → bulk imports, export jobs, cleanup tasks
  long-running → MRP calculations, payroll runs, bulk reports
```

**Key background jobs:**

| Job | Queue | Trigger |
|-----|-------|---------|
| SendEmailNotification | high | Event |
| SendSMSNotification | high | Event |
| GeneratePayslip | default | Payroll run |
| SyncPOSTransactions | default | Offline sync |
| RunMRPCalculation | long-running | Scheduled/Manual |
| GenerateBankTransferFile | default | Payment run |
| ProcessWebhookDelivery | high | API event |
| GenerateFinancialReport | default | Scheduled |
| ImportCSVProducts | low | Manual upload |
| CleanupExpiredSessions | low | Scheduled |

**Job failure handling:**
- All jobs implement `ShouldBeEncrypted` for sensitive payloads
- Failed jobs stored in `failed_jobs` table, retried up to 3 times with exponential backoff
- Dead letter jobs alert ops team via Slack
- Long-running jobs report progress via `$this->job->setProgress($percent)`

---

## File Storage

### S3-Compatible Object Storage (AWS S3 / Wasabi / MinIO)

```
Bucket structure:
  {env}-erp-storage/
  └── tenants/
      └── {tenant_id}/
          ├── products/          ← Product images
          ├── receipts/          ← POS receipt PDFs
          ├── invoices/          ← Invoice PDFs
          ├── payslips/          ← Payslip PDFs (encrypted at rest)
          ├── documents/         ← HR and company documents
          ├── imports/           ← CSV import files
          └── exports/           ← Generated report exports

Access control:
  - All files private by default
  - Time-limited signed URLs (15 min) for file access
  - Never expose S3 URLs directly to clients
  - Application proxies download requests through /files/{id} endpoint

Quotas:
  Starter plan:    5 GB per tenant
  Growth plan:    25 GB per tenant
  Enterprise plan: 100 GB per tenant
  (enforced on upload via StorageQuotaMiddleware)
```

---

## Search Architecture

### Elasticsearch / OpenSearch

Used for full-text and faceted search across large catalogs.

```
Indexed collections:
  - products          (name, SKU, barcode, description, category, tags)
  - customers         (name, email, phone, company)
  - suppliers         (name, contact, products supplied)
  - employees         (name, position, department)
  - transactions      (reference, customer, amounts)

Sync strategy:
  - Write to PostgreSQL first (source of truth)
  - Dispatch SearchIndexJob after write
  - Job updates Elasticsearch index asynchronously
  - Index rebuilds scheduled nightly for consistency

Fallback:
  - If Elasticsearch is down, fall back to PostgreSQL ILIKE queries
  - Degraded mode is slower but never breaks functionality
```

---

## Authentication & Authorization

### Authentication

```
Mechanisms:
  - Session-based auth for web app (cookie + CSRF)
  - JWT Bearer tokens for API and mobile
  - API Keys for machine-to-machine (hashed in DB, shown once)
  - OAuth2 (PKCE flow) for third-party app access

Token lifecycle:
  - Access token: 15 minutes
  - Refresh token: 30 days (rotated on use)
  - Remember me: 90 days

MFA (Multi-Factor Authentication):
  - TOTP (Google Authenticator compatible)
  - SMS OTP via Twilio
  - Backup codes (10 single-use codes, bcrypt hashed)
```

### Authorization (RBAC + ABAC hybrid)

```
Role hierarchy:
  Super Admin (Anthropic/ops level)
    └── Tenant Owner (full access within tenant)
         └── Tenant Admin (manages users/settings)
              └── Module Managers (e.g., HR Manager, Warehouse Manager)
                   └── Module Users (e.g., Cashier, Picker, Accountant)
                        └── Read-Only Viewer

Permission structure:
  {module}.{resource}.{action}

  Examples:
    inventory.products.view
    inventory.products.create
    inventory.products.edit
    inventory.products.delete
    inventory.stock.adjust
    hr.employees.view_salary    ← sensitive permission
    accounting.reports.export
    pos.discounts.apply

Custom roles:
  Tenant admins can create custom roles combining any permissions
  Permissions cached per user for performance
```

---

## API Design

### REST API

```
Base URL: https://api.yourerp.com/v1

Conventions:
  - JSON:API specification for response envelope
  - Snake_case for field names
  - ISO 8601 dates (2024-01-15T10:30:00Z)
  - Cursor-based pagination (not offset — stable for large sets)
  - Sparse fieldsets: ?fields[products]=id,name,price
  - Includes: ?include=variants,category
  - Filters: ?filter[status]=active&filter[category_id]=uuid
  - Sorting: ?sort=-created_at,name

Rate limiting:
  - 1000 requests/hour for API key consumers
  - 10000 requests/hour for internal web app calls
  - Custom limits per plan
  - Headers: X-RateLimit-Limit, X-RateLimit-Remaining, X-RateLimit-Reset

Versioning:
  - URL versioning: /v1/, /v2/
  - Old versions deprecated with 6-month sunset notice
  - Deprecation header: Deprecation: date="2025-06-01"
```

### Webhook System

```
Webhook events (examples):
  sale.completed
  invoice.paid
  stock.low_level_reached
  employee.payslip_generated
  purchase_order.approved
  customer.created

Delivery:
  - HTTPS POST to configured URL
  - Signed with HMAC-SHA256 (secret per webhook endpoint)
  - Retry: 5 attempts with exponential backoff (5s, 25s, 125s, 10m, 1h)
  - Delivery log: last 100 attempts per webhook
  - Paused automatically after 50 consecutive failures
```

---

## Real-Time & WebSockets

### Laravel Reverb (or Pusher-compatible)

```
Channels:
  Private channels (tenant-scoped):
    private-tenant.{tenant_id}        ← system-wide tenant events
    private-user.{user_id}            ← per-user notifications
    private-pos.{session_id}          ← POS session events
    private-warehouse.{warehouse_id}  ← live picking updates

Events pushed in real-time:
  - New notification (bell badge update)
  - Stock level change (dashboard widget)
  - New order received (sales dashboard)
  - POS sync status (offline → online)
  - Approval required (workflow notifications)
  - Report generation complete
```

---

## Notification System

### Centralized Notification Service

```
Channels supported:
  - In-app (database + WebSocket push)
  - Email (via SendGrid/Mailgun)
  - SMS (via Twilio/Africa's Talking)
  - WhatsApp (via WhatsApp Business API / 360Dialog)
  - Push notifications (mobile via FCM/APNs)

Template engine:
  - Templates stored in DB per tenant (customizable)
  - Variables resolved at send time
  - Multi-language support per tenant locale
  - HTML email with plain-text fallback

Notification preferences:
  Each user configures per-notification-type preferences:
    StockAlert    → Email + In-app
    LeaveApproval → In-app only
    PayslipReady  → Email + SMS
    InvoicePaid   → Email

Delivery tracking:
  - Sent timestamp
  - Delivered/Bounced status (via webhook from provider)
  - Opened (email open tracking pixel)
  - Clicked (link tracking)
```

---

## Reporting & Analytics Architecture

```
Three-tier architecture:

Tier 1 — Pre-built reports (fast, cached):
  - Pre-defined SQL queries with parameter injection
  - Results cached in Redis for 10 min
  - Background refresh on schedule or data change event
  - PDF/Excel export via server-side rendering

Tier 2 — Custom report builder (medium):
  - Visual query builder (no SQL needed)
  - Generates safe parameterized SQL
  - Runs against read replica
  - Results materialized for large datasets

Tier 3 — Raw BI (advanced):
  - Apache Superset or Metabase embedded via iframe + SSO
  - Read-only connection to dedicated analytics replica
  - Available on Enterprise plan only

Data pipeline (for large tenants):
  PostgreSQL → CDC (Debezium) → Kafka → ClickHouse (OLAP)
  Reports run on ClickHouse for sub-second performance on millions of rows
  (This layer is introduced only when PostgreSQL reports become slow)
```

---

## Offline & Mobile Architecture

### POS Offline Strategy

```
Technology: PWA with Service Worker + IndexedDB

Offline data synced to device:
  - Full product catalog (with prices and tax rates)
  - Customer list (for loyalty lookup)
  - Active discount/promotion rules
  - Last 200 sales (for reference)

Offline operations supported:
  - Process sales (cash and card via offline reader)
  - Apply discounts
  - Loyalty point accumulation (queued)
  - Receipt printing (local printer via USB/Bluetooth)

Sync mechanism:
  1. All offline transactions stored in IndexedDB with local UUID
  2. When connection restored, Background Sync API fires
  3. Client sends all pending transactions to /pos/sync endpoint
  4. Server processes idempotently (UUID prevents duplicates)
  5. Conflicts resolved: server wins for price/tax, client wins for quantity sold
  6. Sync status shown in POS UI (last synced: 2 min ago)
```

### Mobile App (React Native)

```
Architecture: React Native + Expo
State: Redux Toolkit + Redux Persist (AsyncStorage)
API: React Query with offline mutations queue
Navigation: React Navigation v6

Offline-capable screens:
  - Warehouse picking (scan barcodes, confirm picks)
  - Stock count (cycle counting)
  - Leave requests (queue for sync)
  - Expense claims (with photo receipts)
  - Approval actions (queue for sync)
```

---

## Integration Architecture

### Integration Patterns

```
Pattern 1 — Direct API Call (synchronous, user-triggered):
  Our system → External API
  Example: Trigger SMS, charge card, create shipment label

Pattern 2 — Webhook Receiver (external pushes to us):
  External system → Our /webhooks/{provider} endpoint
  Example: Stripe payment confirmed, bank statement available

Pattern 3 — Scheduled Sync (polling):
  Background job → External API → Upsert local records
  Example: Import bank transactions, sync exchange rates

Pattern 4 — Event-driven Push (we push on change):
  Internal event → WebhookDispatcher → Customer's endpoint
  Example: New invoice created, order shipped

Integration middleware layer:
  All external API calls go through IntegrationGateway:
    - Credentials stored encrypted (per tenant)
    - Request/response logged (for debugging)
    - Circuit breaker (stop calling failing services)
    - Rate limit aware (respects provider limits)
    - Retry with backoff
```

---

## Infrastructure & Deployment

### Cloud: AWS (recommended) or DigitalOcean

```
Production environment:

  Compute:
    - ECS Fargate (containers) or EC2 Auto Scaling Group
    - Min 2 web app instances (HA)
    - Min 2 queue worker instances
    - Scheduled tasks on dedicated ECS task

  Database:
    - RDS PostgreSQL Multi-AZ (primary)
    - RDS Read Replica (reports, analytics)
    - ElastiCache Redis Cluster (cache + queues)

  Storage:
    - S3 Standard (active files)
    - S3 Glacier (archived files > 1 year)

  Networking:
    - VPC with private subnets for DB and workers
    - Public subnets only for load balancer
    - Security groups: DB only accepts traffic from app servers
    - NAT Gateway for outbound traffic from private subnets

  CDN:
    - Cloudflare (DNS, WAF, DDoS, static asset caching)

CI/CD Pipeline (GitHub Actions):
  PR opened  → lint, type-check, unit tests, PHPStan
  PR merged  → integration tests, build Docker image, push to ECR
  Tag v*.*.*  → deploy to staging → smoke tests → deploy to production
               → Slack notification with changelog

Infrastructure as Code:
  - Terraform for all AWS resources
  - Docker Compose for local development
  - .env.example for all required environment variables
```

### Docker Setup

```dockerfile
# Multi-stage build
FROM php:8.3-fpm AS base
  ← install extensions, composer dependencies

FROM base AS production
  ← copy app, set permissions, no dev dependencies

FROM base AS development
  ← include Xdebug, dev tools
```

---

## Security Architecture

```
Transport:
  - TLS 1.2+ enforced everywhere
  - HSTS header with 1-year max-age
  - Certificate pinning in mobile apps

Application:
  - All inputs validated via FormRequest before touching business logic
  - SQL injection: Eloquent ORM with parameterized queries only; raw queries banned
  - XSS: all output escaped; Content-Security-Policy header
  - CSRF: token validation on all state-changing web requests
  - Mass assignment: all models use $fillable (never $guarded = [])
  - File uploads: MIME type verified (not just extension), virus scan via ClamAV
  - Rate limiting: per IP and per user on all authentication endpoints
  - Account lockout: 5 failed attempts → 15 min lockout

Data:
  - Passwords: bcrypt (cost 12) minimum
  - API keys: stored as SHA-256 hash, shown in plaintext only on creation
  - PII fields: encrypted at rest using AES-256 (name, NIN, bank account)
  - Payslips: encrypted PDFs stored in S3
  - Audit log: immutable append-only (no UPDATE or DELETE on audit_logs)

Compliance:
  - GDPR: data export endpoint, right-to-erasure flow (anonymization, not deletion)
  - PCI DSS: no card numbers stored; all payment tokenized via Stripe
  - Data residency: tenant can choose region (EU, US, Africa) on signup
```

---

## Observability & Monitoring

```
Metrics (Prometheus + Grafana):
  - Request rate, error rate, P50/P95/P99 latency per endpoint
  - Queue depth and processing rate per queue
  - Database connection pool utilization
  - Cache hit rate
  - Active tenant count, requests per tenant

Logging (structured JSON → CloudWatch / Loki):
  - Every request logged with tenant_id, user_id, duration, status
  - All exceptions with full stack trace and request context
  - Sensitive fields redacted (passwords, tokens, card numbers)
  - Log levels: DEBUG (dev), INFO (prod), WARNING, ERROR, CRITICAL

Tracing (OpenTelemetry → Jaeger):
  - Distributed traces across web, queue, and external calls
  - Slow query detection (>100ms)

Error tracking:
  - Sentry for real-time exception alerting
  - Alerts: PagerDuty for P1 (5xx rate > 1%), Slack for P2

Uptime monitoring:
  - Synthetic checks from 3 regions every 60 seconds
  - Status page at status.yourerp.com (Cachet or Instatus)
```

---

## Disaster Recovery & Backup

```
Backup strategy:
  - PostgreSQL: continuous WAL archiving to S3 + daily snapshots
  - Redis: daily RDB snapshot to S3
  - S3 files: cross-region replication enabled
  - Retention: daily 30 days, weekly 12 weeks, monthly 12 months

Recovery targets:
  - RTO (Recovery Time Objective): < 1 hour
  - RPO (Recovery Point Objective): < 5 minutes (WAL shipping)

DR drill: quarterly restore test to staging environment, documented

Tenant data export:
  - Any tenant can export all their data as ZIP (GDPR compliance)
  - Export includes: all records as JSON, all files
  - Generated asynchronously, download link valid 24h
```

---

## Scalability Plan

```
Stage 1: 0–100 tenants
  Single region, 2 web servers, 1 DB primary + 1 replica, 2 workers
  Cost: ~$300–500/month

Stage 2: 100–1000 tenants
  Auto-scaling web tier, PgBouncer, Redis cluster, dedicated worker pools
  Elasticsearch for search, CDN for all static assets
  Cost: ~$1,000–3,000/month

Stage 3: 1000–10,000 tenants
  ClickHouse for analytics, separate search cluster, read replicas per region
  Extract notification service, consider tenant sharding across DB clusters
  Cost: ~$5,000–15,000/month

Stage 4: 10,000+ tenants
  Multi-region active-active, extract heavy modules to microservices,
  dedicated DB cluster per tenant tier (high-volume tenants get isolated)
  Cost: $20,000+/month, engineering team dedicated to infrastructure
```
