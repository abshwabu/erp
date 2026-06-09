# MODULE: Inventory Management

## Overview
The Inventory module is the central hub for all product and stock data. It maintains real-time stock levels across multiple locations, tracks every movement, and integrates with POS, Sales, Procurement, Warehouse, Manufacturing, and Accounting.

---

## 1. Product Catalog

### 1.1 Product Creation & Management
- Product name, description (short and long)
- SKU (auto-generated or manual, unique per tenant)
- Barcode (EAN-13, UPC-A, Code 128, QR — multiple barcodes per product)
- Product type: stockable, consumable, service (service products have no stock tracking)
- Product category (multi-level hierarchy)
- Product tags (free-form, for filtering and grouping)
- Status: active, inactive, archived
- Internal notes (visible only to staff)

### 1.2 Pricing
- Cost price (purchase price, used for margin calculation and COGS)
- Selling price (default retail price)
- Multiple price lists per product (see Sales module)
- Price history log (when price changed, by whom)
- Min/max selling price (guards against underpricing at POS)

### 1.3 Product Images
- Multiple images per product
- Primary image displayed at POS and in catalog
- Image resize and optimization on upload
- Alt text for accessibility

### 1.4 Units of Measure (UOM)
- Purchase UOM (e.g., carton of 24)
- Stock UOM (e.g., each)
- Sales UOM (e.g., each or 6-pack)
- Conversion factors between UOMs (1 carton = 24 each)
- Sales and purchase documents use their respective UOM; stock always tracked in stock UOM

### 1.5 Product Variants
- Variant attributes: size, color, material, flavor, etc.
- Attribute values: S/M/L/XL, Red/Blue/Green
- Variant combinations auto-generated (or manually curated)
- Each variant has its own SKU, barcode, price (optional override), and stock level
- Variant matrix view (size × color grid)

### 1.6 Bundles & Kits
- Bundle definition: list of component products + quantities
- Bundle pricing: fixed price or sum of components
- Stock check: bundle available quantity = minimum available quantity across all components
- On sale: components deducted from stock individually

---

## 2. Stock Control

### 2.1 Real-Time Stock Levels
- Current quantity on hand per product per location
- Committed quantity (reserved for open sales orders)
- Available quantity = on hand − committed
- On-order quantity (from open purchase orders)
- Forecasted quantity = on hand + on-order − committed

### 2.2 Stock Adjustments
- Manual adjustment with quantity and reason
- Reasons: found, lost, damaged, theft, correction, quality-reject
- Adjustment requires manager approval above configurable quantity threshold
- Bulk adjustment via CSV import
- All adjustments create accounting entries (inventory shrinkage / surplus)

### 2.3 Lot / Batch Tracking
- Assign lot/batch number on goods receipt
- All movements tracked by lot
- FEFO (First Expired, First Out) picking rule for perishables
- FIFO (First In, First Out) for batch-tracked items
- Lot information on receipts and invoices (for traceability)

### 2.4 Serial Number Tracking
- Assign serial numbers on goods receipt (scan or auto-generate)
- One serial number per unit (1:1 product-to-serial)
- Serial number tracked through: receipt → stock → sale → customer
- Warranty expiry date per serial number
- Serial number lookup: find which customer bought a specific unit

### 2.5 Expiry Date Management
- Expiry date assigned per lot on goods receipt
- Alerts when products are N days before expiry (configurable per product/category)
- Expired stock flagged and quarantined from sale
- Report: stock expiring within 30/60/90 days

### 2.6 Reorder Management
- Minimum stock level (reorder point) per product per location
- Maximum stock level (order up-to level)
- Reorder quantity (Economic Order Quantity or fixed amount)
- Alert when stock falls below minimum
- Auto-generate purchase requisition when minimum reached (optional)
- Demand forecasting: average daily usage × lead time = suggested reorder point

---

## 3. Stock Movements

### 3.1 Movement Types
| Type | Triggered By | Direction |
|------|-------------|-----------|
| Goods Received | Purchase Order / GRN | In |
| Sale | POS / Sales Order | Out |
| Return from Customer | Refund / Return | In |
| Return to Supplier | Supplier Return | Out |
| Internal Transfer | Transfer Order | Out (source) / In (destination) |
| Production Output | Work Order completion | In |
| Production Consumption | Work Order materials | Out |
| Stock Adjustment | Manual / Cycle Count | In or Out |
| Opening Balance | Initial setup | In |

### 3.2 Movement Record
Each movement records:
- Product, variant, lot/serial
- Quantity and UOM
- Source and destination location/warehouse
- Reference document (PO number, SO number, etc.)
- Unit cost at time of movement (for valuation)
- User who created it
- Timestamp

### 3.3 Movement History
- Full movement history per product (filterable by type, location, date)
- Traceability: trace a serial/lot from receipt to current location or sale
- Print movement history report

---

## 4. Multi-Location Stock

### 4.1 Locations
- Multiple warehouses / branches
- Each with its own stock levels
- Virtual locations: transit (stock in transfer), damaged, quarantine

### 4.2 Internal Transfers
- Create transfer order: from location, to location, products and quantities
- Transfer goes through workflow: draft → confirmed → in-transit → received
- Partial transfer supported
- Transit stock shown separately (not available at destination until received)
- Auto-deduct at source on dispatch, auto-add at destination on receive

### 4.3 Transfer Approval
- Configurable approval for transfers above value/quantity threshold
- Emergency transfer (bypass approval, logged as exception)

---

## 5. Inventory Valuation

### 5.1 Valuation Methods
| Method | Description | Best For |
|--------|-------------|---------|
| FIFO | First In, First Out — oldest cost used first | Most businesses, required by IFRS |
| LIFO | Last In, First Out | Some US tax scenarios (deprecated in IFRS) |
| Weighted Average | Running average of all purchase costs | High-volume, frequently changing costs |

- Valuation method selected per tenant and locked after first stock movement
- Changing method requires a period-close and revaluation journal entry

### 5.2 Inventory Valuation Reports
- Total inventory value by product, category, location
- COGS (Cost of Goods Sold) for any period
- Inventory aging (stock held > 30/60/90/180 days)
- Stock movement value (total value of receipts and issues per period)
- Variance report: book value vs physical count

---

## 6. Physical Inventory / Cycle Counting

### 6.1 Full Physical Count
- Create a count order for all products in a location
- Snapshot expected quantities at count start
- Blind count mode: counters enter quantities without seeing expected values
- Discrepancies highlighted on review
- Manager approves adjustments
- Adjustments posted automatically to accounting

### 6.2 Cycle Counting
- Count a subset of products on a rotating schedule
- Schedule by category, location zone, or ABC classification
- ABC classification: A-items (high value/volume) counted weekly, B monthly, C quarterly
- Cycle count worksheet generated (printable or mobile)

### 6.3 Count Workflows
- Assign count tasks to specific users
- Count on mobile app (scan barcode + enter quantity)
- Dual count: two counters count independently, discrepancies flagged for recount
- Count history: all historical counts per product

---

## 7. Product Classification & Organization

### 7.1 Categories
- Hierarchical category tree (unlimited depth)
- Category-level tax settings, cost of sales account, and revenue account
- Move products between categories
- Category-level reorder rules

### 7.2 Tags
- Free-form tagging (e.g., "seasonal", "promotional", "high-margin")
- Products can have multiple tags
- Filter, report, and create price rules by tag

### 7.3 ABC Analysis
- Automatic classification based on revenue contribution
- A: top 20% of products contributing 80% of revenue
- B: next 30%
- C: bottom 50%
- Recalculated on schedule or on demand
- Used for cycle count frequency and min/max settings

### 7.4 Product Import & Export
- Bulk import via CSV/Excel
- Import creates, updates, or deactivates products
- Mapping wizard to match CSV columns to product fields
- Validation report before import (shows errors without committing)
- Export full catalog as CSV/Excel

---

## 8. Alerts & Notifications

| Alert | Trigger | Recipients |
|-------|---------|-----------|
| Low stock | Stock falls below minimum level | Purchasing, Store Manager |
| Overstock | Stock exceeds maximum level | Store Manager |
| Expiry warning | Items expiring in N days | Quality, Store Manager |
| Negative stock | Stock goes below zero | Admin, Inventory Manager |
| Reorder due | Reorder point reached | Purchasing |
| Slow mover | No movement in N days | Store Manager |
| Count variance | Physical count differs from book by > X% | Inventory Manager |

---

## 9. Integrations

- **POS:** Real-time stock deduction on sale; stock shown at cart level
- **Sales Orders:** Stock reserved (committed) on order confirmation; deducted on shipment
- **Procurement:** Stock increased on goods receipt; cost price updated
- **Warehouse:** Bin-level movements within warehouse; inventory total aggregated
- **Manufacturing:** Components consumed on production; finished goods added on completion
- **Accounting:** Every stock movement with a value creates an automatic journal entry
- **E-Commerce:** Stock levels synced to online store; overselling prevented
