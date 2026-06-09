# MODULE: Integrations, Public API & Core Platform

---

# PART A: Public API & Webhooks

## Overview
A fully documented REST API enabling customers, partners, and developers to integrate the ERP with any external system. All functionality available in the UI is accessible via API.

---

## 1. API Design

### 1.1 General Conventions
- Base URL: `https://api.yourerp.com/v1`
- JSON:API specification compliant
- HTTPS required (HTTP rejected)
- Versioned by URL prefix: /v1/, /v2/ (old versions deprecated with 6-month notice)
- Idempotency keys supported on POST endpoints (safe to retry on network failure)
- Pagination: cursor-based (stable for large/changing data sets)

### 1.2 Authentication
- API Key (Bearer token): for server-to-server integrations
- OAuth 2.0 PKCE: for apps acting on behalf of a tenant user
- Scopes: read:inventory, write:orders, read:reports, etc.
- Keys created and revoked from Settings → API Keys
- Keys shown in plaintext only once on creation

### 1.3 Rate Limits
- Standard: 1,000 requests/hour per API key
- Burst: 100 requests/minute
- Response headers: `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset`
- 429 response with `Retry-After` header when exceeded
- Enterprise: custom rate limits

### 1.4 Error Handling
- Standardized error response: `{ "errors": [{ "status": "422", "title": "Validation Error", "detail": "..." }] }`
- Error codes documented in API reference
- HTTP status codes used correctly (200, 201, 204, 400, 401, 403, 404, 409, 422, 429, 500)

---

## 2. API Endpoints (Key Resources)

### Products & Inventory
- `GET /products` — List products (filter, sort, paginate, include variants)
- `GET /products/{id}` — Get single product with full details
- `POST /products` — Create product
- `PATCH /products/{id}` — Update product fields
- `GET /products/{id}/stock` — Get stock levels across all locations
- `POST /stock-adjustments` — Create a stock adjustment

### Orders & Sales
- `GET /sales-orders` — List orders (filter by status, customer, date)
- `POST /sales-orders` — Create a sales order
- `GET /sales-orders/{id}` — Get order with lines and fulfillment status
- `PATCH /sales-orders/{id}` — Update order (pre-fulfillment only)
- `POST /invoices` — Create invoice from order
- `GET /invoices/{id}` — Get invoice with PDF download URL

### Customers
- `GET /customers` — List customers
- `POST /customers` — Create customer
- `PATCH /customers/{id}` — Update customer
- `GET /customers/{id}/invoices` — All invoices for a customer
- `GET /customers/{id}/transactions` — Transaction history (POS + orders)

### Procurement
- `GET /purchase-orders` — List POs
- `POST /purchase-orders` — Create PO
- `GET /suppliers` — List suppliers
- `POST /suppliers` — Create supplier

### HR
- `GET /employees` — List employees (sensitive fields require elevated scope)
- `POST /employees` — Create employee
- `GET /employees/{id}/leave-balance` — Current leave balances

### Accounting
- `GET /journal-entries` — List journal entries
- `GET /accounts` — Chart of accounts
- `GET /reports/profit-loss` — P&L for a period (JSON format)
- `GET /reports/balance-sheet` — Balance sheet at a date

### Webhooks
- `GET /webhooks` — List configured webhooks
- `POST /webhooks` — Register a webhook endpoint
- `DELETE /webhooks/{id}` — Remove a webhook

---

## 3. Webhooks

### 3.1 Available Events
| Module | Event |
|--------|-------|
| Sales | `order.created`, `order.fulfilled`, `invoice.created`, `invoice.paid`, `invoice.overdue` |
| Inventory | `stock.low_level_reached`, `stock.level_changed`, `product.created` |
| Procurement | `purchase_order.approved`, `goods_received.created`, `supplier_invoice.matched` |
| HR | `employee.created`, `employee.offboarded`, `leave.approved`, `payslip.generated` |
| Accounting | `payment.recorded`, `bank_recon.completed` |
| E-Commerce | `online_order.placed`, `online_order.shipped` |
| CRM | `customer.created`, `lead.qualified` |
| POS | `sale.completed`, `shift.closed` |

### 3.2 Delivery Specification
- POST to configured HTTPS URL
- Body: JSON payload with event type, timestamp, and event data
- Headers: `X-ERP-Signature` (HMAC-SHA256), `X-ERP-Timestamp`, `X-ERP-Event`
- Retry: 5 attempts with exponential backoff (5s, 25s, 125s, 10m, 1h)
- Delivery log: last 100 delivery attempts per endpoint
- Test button: send sample payload to endpoint

---

## 4. Developer Portal
- Full API reference documentation (OpenAPI 3.0 spec)
- Interactive API explorer (try requests from the docs)
- Code snippets: PHP, JavaScript, Python, cURL
- Webhook signature verification guide
- Rate limit and pagination guide
- Changelog (API version history)
- Community forum / GitHub discussions

---
---

# PART B: Third-Party Integrations

---

## 1. Payment Gateways
| Integration | Use Case |
|-------------|---------|
| Stripe | Card payments (POS, online, invoices), Stripe Terminal for POS hardware |
| PayPal | Online checkout payment method |
| Flutterwave | Africa-focused card + mobile money |
| M-Pesa (Safaricom) | Kenya mobile money payments and disbursements |
| Airtel Money | East/Central Africa mobile money |
| MTN MoMo | West/Central Africa mobile money |

---

## 2. Shipping & Logistics
| Integration | Use Case |
|-------------|---------|
| DHL Express API | Label generation, rate quotes, tracking |
| FedEx Web Services | Label generation, rate quotes, tracking |
| Aramex | Middle East / Africa focused courier |
| UPS API | Label + tracking |
| Sendle | Australia / US local courier |
| Custom courier | Generic webhook integration for local couriers |

---

## 3. Communication
| Integration | Use Case |
|-------------|---------|
| SendGrid | Transactional email delivery (invoices, notifications, receipts) |
| Mailgun | Alternative transactional email |
| Twilio | SMS notifications, OTP, WhatsApp |
| Africa's Talking | SMS for Africa (cheaper rates) |
| WhatsApp Business API (360Dialog) | WhatsApp messages from CRM |
| Firebase Cloud Messaging (FCM) | Push notifications for Android mobile app |
| Apple Push Notifications (APNs) | Push notifications for iOS mobile app |

---

## 4. Accounting & Finance
| Integration | Use Case |
|-------------|---------|
| QuickBooks | Export journal entries for businesses using QBO in parallel |
| Xero | Export transactions for Xero users migrating to ERP |
| Plaid | Bank feed connection for bank reconciliation (US/UK) |
| Open Banking APIs | Bank statement feeds (country-specific) |
| Fixer.io / Open Exchange Rates | Live exchange rate feeds |

---

## 5. E-Commerce Channels
| Integration | Use Case |
|-------------|---------|
| Shopify | Bi-directional: sync products, prices, stock; import orders |
| WooCommerce | Bi-directional: sync products, prices, stock; import orders |
| Amazon Selling Partner API | Sync listings, stock; import FBA/FBM orders |

---

## 6. Productivity & HR Tech
| Integration | Use Case |
|-------------|---------|
| Google Workspace | SSO, calendar sync for HR interviews |
| Microsoft 365 / Azure AD | SSO, calendar sync |
| Slack | Notifications, KPI alerts, approval requests |
| Microsoft Teams | Same as Slack |
| DocuSign | E-signature for contracts (alternative to built-in) |

---
---

# PART C: Core Platform Module

## Overview
The Core Platform underpins every other module — authentication, tenant management, subscriptions, user management, audit logging, and system configuration.

---

## 1. Tenant (Company) Management

### 1.1 Tenant Onboarding
- Self-service signup: company name, email, country, industry, company size
- Tenant schema created and migrated automatically on signup
- Default COA, tax rates, and roles loaded based on country selection
- Welcome email with guided setup checklist
- Onboarding wizard: company settings → invite team → import products → configure POS

### 1.2 Company Settings
- Legal business name, trading name, registration number
- Company logo (used in documents, receipts, emails)
- Primary address and multiple branch addresses
- Base currency
- Timezone and date format
- Fiscal year start month
- Default language
- Contact details (displayed on documents)

### 1.3 Multi-Entity (Future/Enterprise)
- Multiple legal entities under one tenant (parent/subsidiary)
- Inter-company transactions
- Consolidated financial reporting across entities

---

## 2. User Management

### 2.1 User Accounts
- Email, full name, profile photo
- Role assignment (one or more roles)
- Department and manager (for HR-linked features)
- Language preference
- Notification preferences per notification type
- Active/inactive status (inactive users cannot log in, data preserved)
- Last login tracking
- Invited users: invitation link valid 48 hours

### 2.2 Role Management
- Pre-defined system roles (see ADR-015)
- Custom role creation: any combination of permissions
- Role hierarchy visualization
- Bulk user role assignment
- Permission audit: which users have access to which resources?

### 2.3 User Limits by Plan
- Starter: 5 users
- Growth: 25 users
- Enterprise: unlimited
- Configurable by plan table in DB

---

## 3. Subscription & Billing

### 3.1 Plans
- Plan selection on signup
- Plan features stored in DB (see ADR-023)
- Upgrade/downgrade flow with immediate effect
- Prorated billing on mid-cycle plan change

### 3.2 Billing
- Monthly or annual billing (annual = 2 months free)
- Credit card on file (Stripe)
- Automatic renewal
- Invoice generated for every payment
- Dunning: failed payment → retry → email warnings → grace period → account suspended → data held 30 days → deleted

### 3.3 Module Add-Ons
- Core modules included in all plans
- Premium modules (Manufacturing, Advanced Analytics, E-Commerce): paid add-ons
- Enable/disable add-on modules from billing settings

---

## 4. Audit Log

- Every create, update, delete action logged (see ADR-024)
- Viewable by admin: filter by user, module, action type, date
- Export audit log for compliance/audit purposes
- Immutable: no one can edit or delete audit entries
- Retention: 7 years (or per contractual requirement)

---

## 5. Notification Center

### 5.1 In-App Notifications
- Bell icon in header (badge count of unread)
- Notification feed: newest first
- Mark individual or all as read
- Click notification → navigate to related record
- Notification types: alert, info, reminder, approval request, system

### 5.2 Notification Preferences
- Per-user settings: choose which notification types you receive per channel (in-app, email, SMS, push)
- Quiet hours: no SMS/push during specified hours
- Digest mode: batch notifications into daily email digest instead of real-time

---

## 6. System Configuration

### 6.1 Localization
- Currency: symbol, decimal places, thousand separator, position
- Date format: DD/MM/YYYY, MM/DD/YYYY, YYYY-MM-DD
- Number format: decimal and thousand separators
- Language: UI language (AR, EN, FR, SW, AM — Amharic planned)
- RTL support for Arabic

### 6.2 Document Settings
- Document number sequences: per document type (invoice, PO, quote, etc.)
- Format: prefix + year + sequential number
- Reset annually or continue incrementing
- Minimum padding digits (INV-2024-00001)

### 6.3 Email Configuration
- Outbound email: SendGrid API key (custom) or use platform's shared sending domain
- Custom from-address: invoices@yourcompany.com
- Email footer with business address and unsubscribe link (for marketing)
- Test email send

### 6.4 Security Settings
- Enforce MFA for all users or specific roles
- Password policy: minimum length, complexity requirements, expiry
- Session timeout (idle session duration)
- IP allowlist (restrict access to known IP ranges — Enterprise)
- Login audit: all login attempts with IP and device

### 6.5 Data Management
- Import wizard for initial data load (customers, products, opening stock, opening balances)
- Full data export (GDPR right to portability)
- Account deletion: 30-day grace period, then permanent purge with confirmation
- Storage usage display and management

---

## 7. Integrations Settings Hub
- Central place to connect/disconnect all third-party integrations
- Status indicators per integration (connected, error, not configured)
- Credential storage (encrypted, per tenant)
- Integration health logs (last successful sync, last error)
- API keys management (create, rotate, revoke)
- Webhook management (registered endpoints, delivery logs)
