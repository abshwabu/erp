# MODULE: Point of Sale (POS)

## Overview
The POS module is the primary customer-facing transaction interface for retail businesses. It supports in-store sales, manages cash drawers, handles payments, and integrates tightly with Inventory, Accounting, CRM, and the Loyalty module. Built as a PWA with full offline capability.

---

## 1. Session & Shift Management

### 1.1 Shift Opening
- Cashier selects their user account and enters PIN
- Opening cash count entry (denominations by coin/note)
- Expected vs actual opening float reconciliation
- Session ID generated, linked to register/terminal
- Shift start time logged

### 1.2 Shift Closing (Z-Report)
- End-of-day cash count by denomination
- Expected cash calculated from opening float + cash sales - paid-outs
- Over/short amount highlighted
- Z-Report generated: total sales by payment method, voids, refunds, discounts, tax collected
- Cash drawer sealed and session closed
- Z-Report archived as PDF

### 1.3 X-Report (Mid-Shift Snapshot)
- Same data as Z-Report but does not close the session
- Used for mid-shift manager checks
- Can be run multiple times per shift

### 1.4 Cash Management
- Cash in (manager adds float to drawer)
- Cash out (petty cash removal with reason and approval)
- All movements logged with user, amount, reason, and timestamp
- Running cash balance maintained per session

### 1.5 Multi-Terminal Support
- Multiple registers per location, each with independent session
- Consolidated location-level reports across all terminals
- Terminal-specific configuration (receipt printer, cash drawer, card reader)

---

## 2. Product & Catalog

### 2.1 Product Search
- Real-time search by product name, SKU, barcode
- Fuzzy matching (typo-tolerant search)
- Search result ranked by relevance and frequency of sale
- Recent items shown before search begins

### 2.2 Product Grid (Quick-Select)
- Configurable grid of product tiles with images
- Tiles organized into tabs/categories
- Drag-and-drop tile arrangement by manager
- Color-coded tiles by category
- Support for variable-price products (price entered at sale time)
- Favorite items pinned to top

### 2.3 Barcode Scanning
- USB, Bluetooth, and camera-based barcode scanning
- EAN-13, EAN-8, UPC-A, UPC-E, Code 128, QR Code supported
- Scan directly adds item to cart
- Unknown barcode prompts product creation shortcut (manager only)
- Continuous scan mode for fast checkout

### 2.4 Product Variants at POS
- Select variant (size, color) via modal after product selection
- Variant-specific pricing and stock displayed
- Variant stock level shown in real-time

### 2.5 Bundle / Kit Products
- Bundle products sell component items together at a combined price
- Components deducted from stock individually
- Bundle price can differ from sum of component prices

---

## 3. Cart Management

### 3.1 Cart Operations
- Add, remove, change quantity of items
- Item-level notes (e.g., "no sugar", "gift wrap")
- Merge multiple product scans into single line (configurable: merge same item or keep separate)
- Maximum line item quantity configurable per product

### 3.2 Hold & Retrieve
- Park current cart (hold) with an optional name/note
- Retrieve held cart by name or from hold list
- Unlimited held carts per terminal
- Held carts auto-expire after configurable period (e.g., 4 hours)
- Held items remain reserved in stock during hold

### 3.3 Price Override
- Manager/supervisor can override price on a line item
- Override requires manager PIN + reason entry
- Override logged in audit trail and Z-Report

### 3.4 Item Deletion and Void
- Remove individual line items (requires manager PIN if configurable)
- Void entire transaction before payment
- All voids logged with reason

---

## 4. Discounts & Promotions

### 4.1 Manual Discounts
- Percentage discount on single item or entire cart
- Fixed-amount discount on single item or entire cart
- Requires manager approval above configurable threshold
- Discount reason entry (optional/required, configurable)

### 4.2 Coupon / Voucher Codes
- Enter coupon code manually or scan barcode
- Coupon validated in real-time (validity, usage limits, minimum spend)
- Single-use and multi-use coupons
- Customer-specific vs global coupons

### 4.3 Automatic Promotions
- Buy X get Y free (BOGO)
- Spend over $X get Y% off
- Time-based promotions (happy hour, weekend sale)
- Quantity break pricing (buy 3, get 10% off)
- Promotions applied automatically — no cashier action required
- Multiple promotions can stack (configurable: stackable vs mutually exclusive)

### 4.4 Loyalty Points Redemption
- Look up customer by phone/card/QR scan
- Display available points balance
- Redeem points as partial or full payment
- Points conversion rate configurable (100 points = $1)

---

## 5. Payment Processing

### 5.1 Payment Methods
- Cash (change calculation automatic)
- Credit / debit card (via integrated card reader: Stripe Terminal, SumUp)
- Mobile money (M-Pesa, Airtel Money — region-specific)
- QR code payments (local providers)
- Gift card / store credit
- Customer account credit (charge to account)
- Cheque (with cheque number and bank entry)

### 5.2 Split Payment
- Split transaction across any combination of payment methods
- Example: $50 cash + $30 card + $20 gift card for a $100 total
- Each payment method tracked separately in shift report

### 5.3 Card Payment Integration
- Terminal communicates directly with card reader (no manual entry)
- Tip prompt option on card terminal (configurable)
- Contactless (NFC) and chip support
- Offline card capture mode (if card network down — transaction risky, configurable limit)
- Declined card handling with graceful UI

### 5.4 Change Calculation
- Automatic change due calculation for cash payments
- Change displayed prominently for cashier
- Configurable smallest coin denomination (round up logic)

### 5.5 Partial Payment
- Accept partial payment (balance becomes account receivable on customer record)
- Deposit/down payment for layaways

---

## 6. Receipt Management

### 6.1 Thermal Receipt Printing
- ESC/POS protocol for thermal printers (Epson, Star, Citizen)
- Connection via USB, Bluetooth, or Ethernet
- Customizable receipt layout (logo, business info, footer message, social links, QR code)
- Font size and item truncation configurable
- Print receipt automatically or on demand
- Reprint last receipt button

### 6.2 Digital Receipts
- Email receipt to customer (looks up email from customer profile)
- SMS receipt (link to hosted receipt page)
- QR code on paper receipt links to digital version
- Whatsapp receipt option (where available)

### 6.3 Receipt Customization
- Business logo (uploaded as PNG)
- Custom header and footer text
- Receipt number format (configurable prefix and sequence)
- Show/hide: tax breakdown, loyalty points, cashier name, store address

---

## 7. Refunds & Returns

### 7.1 Refund Process
- Look up original transaction (by receipt number, customer, or date)
- Select items to return (full or partial return)
- Return reason entry (required)
- Refund to original payment method or store credit

### 7.2 Exchange
- Return items + purchase new items in single transaction
- Net payment or refund calculated automatically

### 7.3 Without Original Receipt
- Manager override to process no-receipt return
- Selects reason from list (customer lost receipt, gift, etc.)
- Full audit trail maintained

### 7.4 Restocking Logic
- Returned items automatically added back to stock (configurable)
- Defective returns can be routed to separate "return/quarantine" stock location

---

## 8. Customer Management at POS

### 8.1 Customer Lookup
- Search by name, phone, email, or loyalty card number
- Quick customer creation from POS (name + phone minimum)
- Link transaction to customer for history and loyalty

### 8.2 Customer Display
- Loyalty points balance shown
- Outstanding balance (credit account)
- Last visit date
- VIP tier badge

### 8.3 Customer-Facing Display
- On secondary screen: item list, subtotal, promotions applied, loyalty points earned
- Digital signature capture for card payments

---

## 9. Offline Mode

### 9.1 Offline Capability
- Full product catalog cached locally (IndexedDB)
- Pending transactions queued locally
- Card payments limited to offline authorization amount (configurable)
- Cash payments fully functional offline

### 9.2 Sync on Reconnect
- Background Sync API triggers upload of pending queue
- Duplicate prevention via transaction UUID
- Sync status indicator in POS header ("Synced 2 min ago" / "Offline — 3 pending")
- Conflict resolution: server price wins, local quantity wins

### 9.3 Catalog Refresh
- Catalog synced every 5 minutes when online
- Manual "force refresh" option for managers
- Sync includes: products, prices, stock levels, promotions, tax rates

---

## 10. Reporting & Analytics (POS-Specific)

### 10.1 Sales Summary
- Sales by date range, location, cashier, payment method, product category
- Hourly sales heatmap
- Average transaction value
- Items per transaction

### 10.2 Product Performance
- Best-selling products by quantity and revenue
- Slow movers
- Gross margin per product at POS

### 10.3 Cashier Performance
- Sales per cashier
- Average transaction time
- Discount frequency and amounts per cashier
- Void/refund frequency per cashier

### 10.4 Payment Method Mix
- Breakdown of sales by payment type
- Cash vs digital trend over time

---

## 11. Hardware Integrations

| Device | Connection | Notes |
|--------|-----------|-------|
| Thermal receipt printer | USB, Ethernet, Bluetooth | ESC/POS protocol |
| Barcode scanner | USB HID, Bluetooth | Works like keyboard input |
| Cash drawer | Via printer port or USB | Triggered on successful payment |
| Card reader | Bluetooth / USB | Stripe Terminal, SumUp |
| Customer display | USB / HDMI | Secondary screen |
| Label printer | USB | For price tags and shelf labels |
| Weight scale | USB RS-232 | For sold-by-weight products |

---

## 12. Manager Controls

- Manager PIN override for price changes, discounts, voids, no-sale drawer open
- Remote manager approval via mobile app (manager approves on phone, cashier proceeds)
- Lock/unlock terminal without closing session
- View any cashier's open session in real-time
- Force close another cashier's session in emergency
