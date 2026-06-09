# MODULE: Procurement

## Overview
The Procurement module manages the full purchase cycle: from internal requests for goods, through supplier selection, purchase orders, goods receiving, and supplier invoice matching. It ensures controlled spending and maintains supplier relationships.

---

## 1. Supplier Management

### 1.1 Supplier Profiles
- Company name, trading name, registration number
- Primary address and multiple shipping/billing addresses
- Contact persons (multiple, with role: sales rep, accounts, logistics)
- Supplier type: goods, services, both
- Approved supplier status (can only create POs to approved suppliers)
- Supplier rating (auto-calculated from delivery performance and quality metrics)
- Blacklist flag with reason (blocks PO creation)

### 1.2 Commercial Terms
- Default payment terms: Net 30, Net 60, COD, prepayment, etc.
- Credit limit (maximum outstanding payable balance)
- Currency (supplier invoices in their currency, converted to base)
- Preferred delivery terms: EXW, FOB, CIF, DDP, etc.
- Lead time (days from PO to delivery)
- Minimum order value / minimum order quantity

### 1.3 Banking & Payment Details
- Bank name, account number, IBAN/SWIFT/routing number
- Multiple bank accounts per supplier
- Payment method preference: bank transfer, cheque, mobile money
- Remittance advice email address

### 1.4 Supplier Product Catalog
- Map supplier's part number/code to internal product SKU
- Supplier's price per product (for comparison vs current PO price)
- Supplier-specific UOM and conversion factor
- Last purchase price per supplier per product

### 1.5 Supplier Performance
- On-time delivery rate (% of PO lines delivered by requested date)
- Quality rejection rate (% of received items failing inspection)
- Invoice accuracy rate (% of invoices matching PO 3-way match)
- Trend over 3/6/12 months
- Performance report exportable for supplier review meetings

---

## 2. Purchase Requisition

### 2.1 Requisition Creation
- Any authorized user can raise a purchase requisition (PR)
- Products requested (with description, quantity, UOM, needed-by date)
- Budget / cost center allocation
- Business justification (free text)
- Suggested supplier (optional)
- Estimated unit price (for budget check)

### 2.2 Requisition Approval Workflow
- Configurable multi-level approval based on:
  - Requisition total value (>$500: manager; >$2000: director; >$10,000: CFO)
  - Cost center / department budget availability
  - Product category requiring special approval
- Approver receives email + in-app notification
- Approver can: approve, reject (with reason), or request modification
- Approved PR converts to PO (single step or buyer review)
- Rejected PR returned to requester with reason

### 2.3 Requisition Tracking
- Requester sees status: draft → pending approval → approved → PO raised → delivered
- Notify requester when goods are received

---

## 3. Request for Quotation (RFQ)

### 3.1 RFQ Creation
- Created from approved PR or independently
- Select products and quantities
- Select multiple suppliers to quote (min 1, no max)
- Set quotation deadline
- Add special requirements (delivery terms, quality certificates needed)
- Customizable RFQ template with company branding

### 3.2 RFQ Distribution
- Send RFQ to suppliers by email (PDF attachment + portal link)
- Supplier portal: supplier can submit quote without logging into your ERP
- Manual entry of quote received by phone/email

### 3.3 Quote Comparison
- Side-by-side comparison table: supplier × product × price × lead time × validity
- Total cost comparison (price × quantity + delivery charges)
- Highlight lowest price per line item
- Weighted scoring: price (50%), lead time (30%), quality history (20%)
- Notes field per supplier quote for negotiation context

### 3.4 Quote Award
- Select winning supplier per line (can split order across suppliers)
- One-click conversion to Purchase Order
- Rejected suppliers notified automatically (optional)
- Saved quote history for auditing

---

## 4. Purchase Orders

### 4.1 PO Creation
- Create from: approved PR, RFQ award, direct creation, auto-reorder trigger
- Supplier, delivery address, PO date, required delivery date
- Line items: product, description, quantity, UOM, unit price, discount, tax
- Additional charges: shipping, customs, handling (added as separate lines)
- Internal notes and supplier-facing notes
- Attach documents: specification sheets, drawings, terms

### 4.2 PO Approval
- Configurable approval workflow based on PO total value
- Approval by: department manager, finance, CFO
- Parallel approval (multiple approvers simultaneously) or sequential
- Auto-approve below configurable threshold (e.g., POs under $200)
- Approved PO locked for editing (amendment process required)

### 4.3 PO Amendment
- Changes after approval require a new amendment version (PO v1, v2, v3)
- Amended PO re-enters approval workflow
- Full version history with diff view (what changed between versions)
- Supplier notified of amendment

### 4.4 PO Sending
- Send PO to supplier by email as PDF
- Optional: supplier portal acknowledgment (supplier confirms receipt and delivery date)
- Acknowledge by phone → manual acknowledgment logged

### 4.5 PO Status Tracking
- Draft → Submitted for approval → Approved → Sent to supplier
  → Partially received → Fully received → Closed
- Overdue PO alerts (expected delivery date passed, no receipt)
- Follow-up reminder system (automated email to supplier N days before due)

### 4.6 Blanket Purchase Orders
- Long-term agreement with supplier for a product at a fixed price
- Release call-offs against blanket PO as needed
- Track consumed vs total blanket quantity and value
- Expiry date on blanket PO; alerts when approaching

---

## 5. Goods Receiving (GRN)

### 5.1 Goods Received Note Creation
- Created by warehouse team (see Warehouse module for detailed receiving process)
- Each GRN linked to one or more POs
- Records: product, quantity received, lot/batch, condition
- Receiving notes and photos attachable

### 5.2 Partial Receipts
- Receive partial quantities; PO stays open for balance
- Multiple GRNs against a single PO
- Backorder: supplier notifies expected date for remaining items

### 5.3 Over-Receipt
- Quantity received exceeds PO quantity
- System flags over-receipt and requires manager approval
- Options: accept (PO amended retroactively) or reject (return excess)

### 5.4 GRN → Inventory Update
- Confirmed GRN immediately updates inventory stock levels
- Lot/serial/expiry information recorded in inventory
- Cost price from PO used for inventory valuation

---

## 6. Supplier Invoices & 3-Way Matching

### 6.1 Invoice Entry
- Enter supplier invoice manually or import from email (OCR parsing optional)
- Invoice number, date, due date, line items, taxes, total
- Link invoice to one or more POs and GRNs

### 6.2 Three-Way Match
- System automatically matches: PO quantity/price → GRN quantity → Invoice quantity/price
- Match statuses:
  - **Full match:** quantities and prices agree — auto-approve for payment
  - **Price variance:** invoice price differs from PO price by more than tolerance (e.g., 2%)
  - **Quantity variance:** invoice quantity doesn't match received quantity
  - **No match:** invoice items not found in any open PO

### 6.3 Variance Handling
- Price variance: route to purchasing team for approval or dispute
- Quantity variance: hold pending receipt of remaining goods or issue debit note
- Disputes logged against supplier with resolution tracking

### 6.4 Invoice Approval
- Matched invoices auto-approved (below threshold)
- Unmatched or over-threshold invoices require human review and approval
- Invoice approved → posted to Accounts Payable (Accounting module)
- Invoice due date calculated from invoice date + payment terms

---

## 7. Payment Processing (AP side — links to Accounting)

### 7.1 Payment Scheduling
- View all outstanding supplier invoices with due dates
- Filter: overdue, due this week, due this month
- Select invoices for payment run
- Payment run creates bank transfer batch

### 7.2 Payment Run
- Aggregate multiple invoices to same supplier into single payment
- Generate bank transfer file (SWIFT MT101, SEPA, local bank format)
- Payment amount in supplier's currency
- Payment reference includes invoice numbers for reconciliation

### 7.3 Remittance Advice
- Auto-generate remittance advice per supplier per payment
- Email to supplier's accounts email address
- Remittance lists all invoices settled in that payment

### 7.4 Early Payment Discounts
- Record early payment discount terms (e.g., 2/10 Net 30 = 2% if paid within 10 days)
- System highlights invoices eligible for early payment discount
- Discount calculated and deducted automatically on payment

---

## 8. Reporting & Analytics

### 8.1 Spend Analysis
- Total spend by supplier, category, department, cost center
- Spend trend over time
- Top 10 suppliers by spend
- Spend vs budget by category

### 8.2 Procurement Efficiency
- Average PR to PO time (cycle time)
- Average PO to delivery time vs lead time
- On-time delivery rate per supplier
- PO amendment frequency (indicator of poor initial requirements)
- Maverick spend: purchases made without a PO (for policy enforcement)

### 8.3 Cost Savings Tracking
- Negotiated savings: invoice price vs prior period price
- Early payment discounts captured
- RFQ savings: selected quote vs average of all quotes

### 8.4 Open Orders Report
- All open POs with expected delivery dates
- Overdue POs
- POs pending goods receipt
- Expected spend in next 30/60/90 days (cash flow planning)

---

## 9. Integrations

- **Inventory:** GRN confirmation triggers stock increase and lot/serial recording
- **Accounting:** Approved supplier invoice posted to AP; payment run posts to GL
- **Warehouse:** Inbound receipt workflow linked to expected PO arrivals
- **HR/Expense:** Employee expense claims use a simplified version of the procurement workflow
- **Email:** RFQ and PO sent via email; supplier responses parsed back in
