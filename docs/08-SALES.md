# MODULE: Sales & Invoicing

## Overview
The Sales module manages the complete outbound sales cycle: quotations, sales orders, fulfillment, invoicing, and payment collection. It integrates with Inventory (stock reservation), Warehouse (picking/shipping), Accounting (AR and revenue), and CRM (customer history).

---

## 1. Customer Management

### 1.1 Customer Profiles
- Company name, trading name, registration/VAT number
- Customer type: individual, business
- Billing address and multiple shipping addresses
- Primary contact and multiple contacts per customer
- Customer category/segment (retail, wholesale, VIP, government)
- Assigned sales representative
- Source (how they found you: referral, online, walk-in)

### 1.2 Customer Financials
- Credit limit and current outstanding balance
- Payment terms (Net 30, COD, prepayment, custom)
- Default currency
- Default price list
- Default tax status (taxable, exempt, reverse charge)
- Discount profile (default line-item discount %)

### 1.3 Customer History
- All quotes, orders, invoices, and payments in one view
- Total revenue from customer (lifetime and per period)
- Last order date and average order value
- Outstanding balance and overdue amounts
- Complaints/disputes log

---

## 2. Price Lists

### 2.1 Price List Configuration
- Multiple price lists per tenant
- Price list types: fixed price, % discount off standard price
- Applicable currency
- Validity dates (seasonal pricing)
- Assignment: per customer, per customer segment, per sales channel

### 2.2 Pricing Rules
- Product-level price override per list
- Category-level discount (all products in category get X% off)
- Volume/quantity break pricing: 1–9 units = $10; 10–99 = $9; 100+ = $8
- Minimum margin enforcement (system warns if selling below cost + minimum margin %)
- Customer-contract pricing (specific prices agreed for a specific customer)

### 2.3 Price List Import
- Bulk update prices via CSV upload
- Effective date for bulk price changes
- Preview changes before applying

---

## 3. Quotations

### 3.1 Quote Creation
- Customer selection (or create new on the fly)
- Line items: product, quantity, UOM, unit price (from price list or manual), discount, tax
- Subtotal, discount total, tax total, grand total
- Quote validity date
- Quote terms and conditions (template-based, editable)
- Delivery terms and estimated delivery date
- Internal notes (not visible to customer)

### 3.2 Quote Numbering & Versions
- Auto-generated quote number (configurable format: Q-2024-0001)
- Quote versioning: V1, V2, V3 when revised
- Each version is immutable after being sent
- Active version clearly indicated

### 3.3 Sending Quotes
- Generate professional PDF quote with company branding
- Email to customer with PDF attachment
- Online quote link: customer views and accepts via browser (no login required)
- Quote status tracking: sent → viewed → accepted / rejected / expired

### 3.4 Quote Acceptance
- Customer accepts via online link (e-signature or click-to-accept)
- Internal team marks as accepted (after phone/email confirmation)
- Accepted quote converts to Sales Order with one click
- Rejected quote: reason recorded

### 3.5 Quote Analytics
- Quote-to-order conversion rate
- Average quote value
- Quotes expiring in next 7/14/30 days (follow-up alerts)
- Win/loss analysis by product, customer, competitor (reason for loss recorded)

---

## 4. Sales Orders

### 4.1 Order Creation
- From accepted quote (one click) or directly
- Same structure as quote: customer, lines, pricing, terms
- Delivery address selection (from customer's saved addresses or new)
- Requested delivery date
- Customer PO reference number
- Payment terms confirmed

### 4.2 Order Approval
- Orders above configurable value require manager approval
- Special price override requires approval
- Approved order locked for editing (amendment process for changes)

### 4.3 Stock Reservation
- On order confirmation, ordered quantities are reserved (committed) in inventory
- Available stock = on-hand minus all committed quantities
- POS and other orders see reduced available stock in real-time
- Reservation released if order is cancelled

### 4.4 Order Fulfillment Status
- Fulfillment status per line: unfulfilled, partially fulfilled, fulfilled
- Overall order status: confirmed → picking → packed → shipped → delivered → invoiced → paid
- Trigger picking task in Warehouse module automatically on order confirmation (or manually)

### 4.5 Backorders
- If stock insufficient for full order, options:
  - Create backorder for unfulfilled quantity
  - Wait until all in stock before fulfilling
  - Partial fulfillment allowed (configurable per customer/order)
- Backorder linked to original order for customer reference

### 4.6 Order Amendments
- After confirmation, amendments require re-approval
- Add/remove lines, change quantities, change delivery date
- Amendment version history maintained
- Customer notified of confirmed amendments

### 4.7 Order Cancellation
- Cancel with reason
- Stock reservation released
- If partially fulfilled: cancel only unfulfilled portion or full recall

---

## 5. Delivery Notes

### 5.1 Delivery Note Creation
- Auto-generated on order dispatch (from Warehouse shipment confirmation)
- Lists products, quantities, and lot/serial numbers dispatched
- Delivery note number separate from order number
- Print or email to customer/driver

### 5.2 Partial Deliveries
- One order can have multiple delivery notes (partial shipments)
- Each delivery note shows which order lines it covers
- Remaining-to-deliver quantity tracked per order line

### 5.3 Proof of Delivery
- Driver app (mobile): captures recipient signature and delivery timestamp
- Photo of delivered goods (optional)
- GPS coordinates at delivery
- POD linked to delivery note and invoice

---

## 6. Invoicing

### 6.1 Invoice Creation
- From sales order or delivery note (one click)
- Manual invoice (for service billing or ad-hoc)
- Invoice lines: description, quantity, unit price, discount, tax
- Invoice date, due date (from payment terms)
- Billing address, shipping address

### 6.2 Invoice Numbering
- Sequential, gap-free numbering (required for tax compliance)
- Configurable prefix and format (INV-2024-000001)
- Year-based restart optional (country-specific requirement)

### 6.3 Invoice Sending
- PDF via email (customizable template with logo and branding)
- E-invoice formats: UBL XML, Peppol (for countries requiring electronic invoicing)
- Customer self-service portal: customer logs in to view/download invoices
- WhatsApp delivery option

### 6.4 Recurring Invoices
- Template-based recurring billing (subscriptions, retainers, rent)
- Frequency: weekly, monthly, quarterly, annually
- Auto-generate and auto-send on schedule
- Auto-post to AR on generation

### 6.5 Credit Notes
- Full or partial credit note against original invoice
- Reason: goods returned, pricing error, dispute resolution
- Credit note reduces customer outstanding balance
- Apply credit note against future invoice or refund to customer

### 6.6 Proforma Invoices
- Non-posted invoice for customs purposes or advance payment request
- Clearly marked "PROFORMA — NOT A TAX INVOICE"
- Convert to final invoice on confirmation

### 6.7 Invoice Disputes
- Customer raises dispute on specific invoice
- Dispute notes and correspondence logged
- Invoice put on hold (excluded from automated reminders)
- Resolution: credit note, price adjustment, or dispute closed/rejected

---

## 7. Payment Collection

### 7.1 Payment Recording
- Record payment against specific invoice(s)
- Payment date, method (bank transfer, cash, card, cheque, mobile money)
- Bank reference number
- Partial payment allocation

### 7.2 Online Payment Portal
- Customer receives payment link in invoice email
- Hosted payment page (Stripe/PayPal)
- Customer pays with card or bank transfer (where supported)
- Automatic payment recording and invoice status update on successful payment
- Payment confirmation email sent automatically

### 7.3 Payment Reconciliation
- Bank feed imports daily transactions
- Auto-match payments to invoices (by amount + reference)
- Manual match for unmatched payments
- Unallocated receipts parked in clearing account

---

## 8. Returns & Refunds

### 8.1 Return Merchandise Authorization (RMA)
- Created from original invoice
- Line items selected for return
- Return reason per line
- Return instructions and label generation

### 8.2 Refund Processing
- Refund to original payment method (card refund via Stripe)
- Refund as store credit
- Refund as credit note (offset against future invoice)
- Cash refund (manual)

---

## 9. Sales Reporting

### 9.1 Sales Dashboard
- Revenue today, this week, this month (vs prior period)
- Orders by status count
- Top customers by revenue
- Top products by quantity and revenue
- Sales by representative

### 9.2 Standard Reports
- Sales by period (daily, weekly, monthly, quarterly, annually)
- Sales by customer, customer segment, sales rep, product, category
- Gross margin by product and customer
- Order fulfillment time (order to invoice, invoice to payment)
- Quote conversion report
- Aged receivables (customer balance aging)

### 9.3 Revenue Recognition
- Deferred revenue tracking (payment received before service delivered)
- Revenue recognized on delivery/service completion
- Deferred revenue schedule and release

---

## 10. Integrations

- **Inventory:** Stock reserved on order; deducted on delivery note
- **Warehouse:** Picking task triggered on order confirmation; delivery note created on shipment
- **Accounting:** Invoice posts to AR; payment posts to bank and clears AR
- **CRM:** All quotes, orders, and interactions recorded on customer timeline
- **E-Commerce:** Online orders flow in as sales orders
- **Loyalty:** Points earned on invoice payment; points visible on POS
