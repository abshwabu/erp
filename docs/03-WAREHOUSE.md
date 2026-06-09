# MODULE: Warehouse Management System (WMS)

## Overview
The Warehouse module manages the physical operations within a warehouse: storage locations (bins), inbound receiving, outbound picking and packing, shipment, and stock counting. It operates below the Inventory module — inventory tracks totals, warehouse tracks exact physical locations.

---

## 1. Warehouse Configuration

### 1.1 Warehouse Setup
- Multiple warehouses per tenant
- Warehouse name, address, type (own, 3PL, consignment, dropship)
- Operating hours (for shipping SLA calculations)
- Default inbound and outbound routes
- Contact person and emergency contact

### 1.2 Location Structure
- Zone → Aisle → Rack → Shelf → Bin (configurable depth)
- Example: Zone A → Aisle 3 → Rack 2 → Shelf B → Bin 04 = "A-3-2-B-04"
- Location types: receive, storage, pick, pack, stage, ship, quality, damaged, quarantine, returns
- Active/inactive toggle per location
- Location capacity (maximum weight, volume, unit count)
- Location restrictions (temperature zone: ambient, chilled, frozen)

### 1.3 Putaway Rules
- Rules determine where incoming stock should be stored
- Rules based on: product category, product tag, supplier, product storage requirements
- Rule priority order (first matching rule wins)
- Default putaway location per product
- Overflow rule when primary location is full

---

## 2. Inbound (Receiving)

### 2.1 Expected Receipts
- Inbound receipts linked to Purchase Orders
- View expected receipts for today/this week
- ASN (Advanced Shipping Notice) from suppliers (optional EDI/API)
- Receive without PO (for ad-hoc receipts, with explanation)

### 2.2 Receiving Process
1. Dock arrival logged (truck/carrier, arrival time)
2. Receiver selects expected PO or creates unplanned receipt
3. Scan or manually confirm product + quantity received
4. System compares received vs PO quantity (over/under receipt handling)
5. Lot number and expiry date entered (if applicable)
6. Serial numbers scanned one-by-one (if applicable)
7. Quality inspection step (pass/fail/partial)
8. Putaway task generated (manual or suggested by putaway rules)
9. Stock confirmed at bin location after putaway
10. GRN (Goods Received Note) auto-generated and linked to PO

### 2.3 Quality Inspection on Receiving
- Inspection checklist per product category
- Inspect entire lot or sample (AQL sampling size calculator)
- Pass: stock moved to storage
- Fail: stock moved to quarantine location, supplier notified, RMA initiated
- Partial: acceptable quantity moved to storage, remainder quarantined

### 2.4 Discrepancy Handling
- Over-receipt: quantity exceeds PO — flag for manager approval or auto-accept up to X%
- Under-receipt: partial delivery logged, PO remains open for balance
- Wrong product received: flagged, moved to quarantine, supplier exception raised
- Damaged on arrival: photo evidence captured, moved to damaged location

### 2.5 Cross-Docking
- Identify inbound stock that has an open outbound order
- Route directly from receiving dock to staging/shipping without putaway
- Cross-dock tasks generated automatically when inbound receipt is confirmed

---

## 3. Putaway

### 3.1 Putaway Task Management
- Tasks generated automatically on receipt confirmation
- Tasks assigned to warehouse staff (by zone or manually)
- Task list on mobile scanner: product, quantity, suggested bin
- Staff scans product then scans bin to confirm
- Mismatch alert if wrong bin scanned

### 3.2 Directed Putaway
- System suggests optimal bin based on: putaway rules, bin proximity to pick zone, consolidation (put with existing stock of same product)
- Floor staff can override suggestion (logs override reason)
- Overrides reviewed by supervisor for pattern analysis

### 3.3 Bin Consolidation
- Alert when same product is in multiple bins (fragmented storage)
- Consolidation task generated to move stock into single bin
- Consolidation report for warehouse managers

---

## 4. Outbound (Picking)

### 4.1 Pick List Generation
- Pick lists generated from confirmed sales orders and transfer orders
- Release orders to warehouse: manually or automatically based on SLA
- Batch release: release all orders due in next 2 hours

### 4.2 Picking Strategies

#### Discrete Picking
- One picker, one order, one pick list
- Simplest method, best for small orders or urgent shipments

#### Batch Picking (Wave Picking)
- One picker picks items for multiple orders simultaneously
- System groups orders by pick zone to minimize travel
- Items sorted by bin location (travel-optimized path)
- Items separated into individual orders at sorting station

#### Zone Picking
- Warehouse divided into zones, each picker responsible for their zone
- Order moves through zones (conveyor or manually)
- Suitable for large warehouses with specialized zones (electronics, bulky, fragile)

#### Cluster Picking
- Picker carries multiple totes (one per order)
- Picks items for multiple orders in one pass, depositing in correct tote at each bin
- Efficient for small, similar orders

### 4.3 Pick Task Execution (Mobile)
- Task assigned to picker's mobile device
- Optimized pick path displayed (shortest route through bins)
- Scan-to-confirm: scan bin barcode, then scan product barcode to confirm
- Mismatch alerts (wrong product, wrong bin)
- Quantity confirmation for each pick
- Short-pick handling: if not enough stock in bin, picker reports shortage, system reroutes to alternate bin

### 4.4 Substitutions
- Pre-approved substitution list per product (e.g., Brand A is acceptable substitute for Brand B)
- Picker can apply substitution if primary product is unavailable
- Substitution logged and reported to sales team

### 4.5 Pick Exceptions
- Cannot find product: picker reports, task reassigned or flagged for investigation
- Damaged item in bin: flagged and alternative bin suggested
- Wrong lot pulled: correction scan required

---

## 5. Packing

### 5.1 Packing Station
- Dedicated packing station receives completed pick task
- Packer scans items against pick list to verify completeness
- Mismatch (missing or extra items) flagged before packing
- Items packed into box/envelope

### 5.2 Packing Materials
- Packing material catalog (box sizes, envelopes, padding)
- Select carton type and dimensions
- System suggests optimal carton based on item dimensions/weight (dim weight calculation)
- Packing material consumption tracked (used for reorder and cost allocation)

### 5.3 Weight & Dimensions
- Weigh packed carton on scale (connected via USB or manual entry)
- Carton dimensions entered
- Dimensional weight calculated for shipping cost comparison

### 5.4 Packing Slip
- Auto-generated packing slip per carton (product list, quantities, order reference)
- Multi-carton: each carton gets its own packing slip with carton number
- QR code on packing slip links to digital order record

### 5.5 Carton Contents Recording
- System records which items are in which carton
- Enables partial shipment tracking at carton level
- Useful for customer inquiries ("is my XYZ in the first or second box?")

---

## 6. Shipping

### 6.1 Carrier Management
- Carrier profiles: DHL, FedEx, Aramex, local couriers
- API integration with carrier for label generation and tracking
- Carrier account credentials stored per tenant
- Service level options per carrier (standard, express, overnight)

### 6.2 Shipping Label Generation
- Auto-generate shipping label via carrier API
- Print label on label printer (Zebra, Dymo)
- Multi-piece shipments: one label per carton
- Return label generation (for e-commerce and warranty)

### 6.3 Shipping Cost Calculation
- Live rate query to carrier API (based on weight, dimensions, origin, destination)
- Multi-carrier rate comparison
- Selected rate recorded on shipment
- Actual shipping cost vs estimated cost variance report

### 6.4 Shipment Confirmation
- Scan cartons at dispatch (confirms items have left warehouse)
- Shipment status: packed → dispatched → in-transit → delivered
- Tracking number recorded and linked to customer order
- Customer notification triggered (email/SMS with tracking link)

### 6.5 Proof of Delivery (POD)
- Delivery confirmation from carrier API
- Photo POD (where supported)
- Recipient signature (where supported)
- POD stored against shipment record

---

## 7. Returns Management (Inbound Returns)

### 7.1 Return Authorization (RMA)
- RMA created from original customer order
- Customer notified with return instructions and return label
- Expected return tracked in warehouse

### 7.2 Returns Receiving
- Receive returned items against RMA
- Condition assessment: resalable, damaged, defective
- Resalable: restock to standard location
- Damaged: move to damaged location, credit note triggered
- Defective: quarantine, RMA to supplier if under warranty

### 7.3 Supplier Returns (Outbound Returns)
- Create supplier return order
- Pick items from quarantine/damaged locations
- Generate carrier label for return to supplier
- Track supplier credit/replacement

---

## 8. Warehouse Reporting

### 8.1 Operational Reports
- Receiving performance: GRNs per day, average receiving time, discrepancy rate
- Picking performance: orders picked/hour per staff member, pick accuracy rate
- Packing performance: orders packed/hour, packing error rate
- Shipping performance: on-time dispatch %, carrier cost per order

### 8.2 Space Utilization
- Bin utilization (% of bins occupied)
- Zone utilization
- Overloaded locations (at or above capacity)
- Empty locations
- Bin turnover rate (how often each bin's contents change)

### 8.3 Staff Productivity
- Tasks completed per staff per shift
- Travel time analysis (identify inefficient pick paths)
- Exception frequency per staff member (errors, shorts)

### 8.4 KPI Dashboard
- Orders in queue (not yet released to warehouse)
- Orders being picked (in progress)
- Orders ready to ship (packed, awaiting dispatch)
- Average order fulfillment time (order confirmed → dispatched)
- Pick accuracy rate (%)
- On-time dispatch rate (%)

---

## 9. Mobile Warehouse App

- Designed for barcode scanner guns (Zebra, Honeywell) and smartphones
- Works offline (syncs when in Wi-Fi range within warehouse)
- Large text, simple navigation for gloved hands
- Screens: receive, putaway, pick, pack, count, move, lookup
- Task queue: staff sees only their assigned tasks
- Supervisor view: all tasks, assignment, progress

---

## 10. Integrations

- **Inventory:** Bin-level movements update inventory totals in real-time
- **Procurement:** Receiving workflow triggered by confirmed PO
- **Sales:** Pick/pack/ship workflow triggered by confirmed sales order
- **Accounting:** Goods dispatched triggers COGS journal entry
- **Carriers:** DHL, FedEx, Aramex API for labels and tracking
- **Barcode Hardware:** Scanner guns, label printers, weigh scales
