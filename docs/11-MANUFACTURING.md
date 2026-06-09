# MODULE: Manufacturing

## Overview
The Manufacturing module enables businesses that produce goods to manage their production processes: Bills of Materials, Work Orders, production scheduling, quality control, and cost tracking. It integrates with Inventory for raw material consumption and finished goods output.

---

## 1. Bill of Materials (BOM)

### 1.1 BOM Structure
- Finished product (parent item)
- Component items (children): raw materials, sub-assemblies, packaging
- Quantity per finished unit (can be fractional: 0.5 kg of ingredient per unit)
- Unit of Measure per component
- Component type: material, by-product, co-product, phantom (virtual grouping)
- BOM code and version number
- Validity dates per version

### 1.2 Multi-Level BOM
- Sub-assemblies nested inside main BOM
- Unlimited nesting depth
- Exploded BOM view: shows all raw materials required across all levels
- BOM where-used: "which BOMs use this component?"

### 1.3 BOM Management
- Create, edit, version, deprecate BOMs
- BOM comparison between versions (diff view)
- Copy BOM as starting point for new product
- Import BOM from Excel/CSV
- BOM cost estimate: total material cost at current prices

### 1.4 By-Products and Co-Products
- By-products: secondary outputs (e.g., sawdust from wood cutting)
- Co-products: joint products from same process (e.g., chicken breasts + thighs)
- By-products can be valued (positive or negative cost) and added to stock

### 1.5 Phantom Assemblies
- Virtual grouping of components (no actual work order needed)
- Used for planning purposes or grouping BOM components logically
- Components exploded through phantom to parent BOM

---

## 2. Work Centers

### 2.1 Work Center Definition
- Name and code
- Type: machine, labor, subcontract
- Location (which facility/building)
- Available capacity: hours per day, shifts per day, efficiency %
- Costing method: per hour rate, per piece rate

### 2.2 Work Center Capacity
- Define working hours per day and days per week
- Multiple shifts (shift name, start time, end time, capacity %)
- Maintenance windows (planned downtime)
- Available capacity = total capacity − planned downtime

### 2.3 Work Center Queues
- Real-time queue of work orders at each work center
- Estimated wait time per work order
- Throughput rate (units produced per hour)

---

## 3. Operations (Routing)

### 3.1 Production Routing
- Sequence of operations to produce a finished good
- Each operation: name, work center, standard time per unit, setup time
- Routing linked to BOM (each BOM version can have a routing)
- Parallel operations supported (two things happen simultaneously)

### 3.2 Operation Types
- Machine operation (automated, time-based)
- Labor operation (manual, tracked by worker clock-in/out)
- Subcontracting operation (sent to external party)
- Quality check operation (inspection step)

### 3.3 Standard Costing per Operation
- Labor cost: hours × hourly rate of work center
- Machine cost: hours × machine rate
- Overhead absorption: standard overhead rate per work center hour

---

## 4. Work Orders (Manufacturing Orders)

### 4.1 Work Order Creation
- Linked to: sales order demand, MRP suggestion, manual request
- Finished product and quantity to produce
- BOM version to use
- Routing (sequence of operations)
- Scheduled start and end date
- Priority level
- Work center assignments per operation

### 4.2 Work Order Status Lifecycle
Draft → Confirmed → Released (materials reserved) → In Progress → Quality Check → Done → Archived

### 4.3 Material Reservation
- On work order confirmation: components reserved in inventory
- Available quantity check before confirmation
- Missing materials: suggest purchase requisition or show shortage report
- Component substitution if primary material unavailable

### 4.4 Work Order Execution
- Worker selects assigned work order on mobile/shop floor terminal
- Clock in to operation (records actual labor time)
- Enter quantities produced (real-time partial quantity reporting)
- Report scrap / rejected units per operation with reason code
- Clock out of operation
- Move to next operation

### 4.5 Work Order Costing (Actual vs Standard)
- Actual material consumption vs BOM quantity
- Actual labor hours vs routing standard time
- Actual machine time vs standard
- Work order variance report: actual cost vs standard cost
- Variance breakdown: material, labor, overhead, efficiency

### 4.6 Work Order Completion
- Quantity produced confirmed
- Finished goods added to inventory (Dr: Finished Goods, Cr: WIP)
- Actual material quantities consumed posted to inventory
- WIP account cleared
- Work order closed

---

## 5. Material Requirements Planning (MRP)

### 5.1 MRP Inputs
- Demand: open sales orders, sales forecast, minimum stock levels
- Supply: current on-hand inventory, open purchase orders, open work orders
- BOM: component requirements per finished good
- Lead times: supplier lead time per component, production lead time per work order

### 5.2 MRP Calculation
- Explodes demand through all BOM levels
- Net requirements = demand − on-hand − scheduled receipts
- Calculates what to produce (work orders) and what to buy (purchase requisitions)
- Time-phased: takes into account when demand is needed and lead times
- Lot sizing: min order quantity, fixed order quantity, lot-for-lot

### 5.3 MRP Output
- Suggested work orders (product, quantity, start date, due date)
- Suggested purchase requisitions (component, quantity, due date, suggested supplier)
- Review screen: approve all, approve selected, ignore with reason
- MRP exception messages:
  - "Reschedule earlier" (supply available sooner than needed)
  - "Reschedule later" (supply scheduled after demand — delay PO)
  - "Cancel" (supply no longer needed)
  - "New order needed" (demand not covered by any supply)

### 5.4 MRP Settings
- Planning horizon (how many days/weeks into the future to plan)
- Include safety stock in requirements
- Include slow-moving inventory
- Exclude certain products or categories
- Run frequency: on demand, daily overnight, weekly

---

## 6. Production Scheduling

### 6.1 Scheduling Methods
- Forward scheduling: start from order date, calculate finish date
- Backward scheduling: start from due date, calculate latest start date
- Finite capacity scheduling: considers work center capacity before scheduling

### 6.2 Production Gantt Chart
- Visual timeline of all work orders across work centers
- Drag-and-drop rescheduling
- Capacity overload highlighted (red if work center over-booked)
- Filter by work center, product, date range

### 6.3 Capacity Planning
- Capacity load report: planned vs available hours per work center per week
- Identify bottlenecks (consistently overloaded work centers)
- "What if" analysis: impact of adding a work order on capacity

---

## 7. Quality Control

### 7.1 Quality Control Points
- Incoming inspection (on raw material receipt from supplier)
- In-process inspection (after specific operations in the routing)
- Final inspection (before finished goods enter stock)

### 7.2 Quality Control Orders
- Auto-generated at configured quality control points
- Assigned to QC inspector
- Sample size calculation (AQL sampling, or full 100% inspection)
- Inspection checklist: list of tests/checks per product

### 7.3 Inspection Results
- Pass/fail per test, with numeric results where applicable (e.g., weight, dimension)
- Non-conformance recording: type, severity, description, photos
- Disposition per lot:
  - **Accept:** moves to next step or stock
  - **Conditional Accept:** accept with deviation documented
  - **Rework:** send back to production for rework
  - **Reject/Scrap:** quarantine and dispose

### 7.4 Non-Conformance Reports (NCR)
- Formal NCR document created for significant defects
- Root cause analysis fields (5-Why, Fishbone)
- Corrective action plan (CAPA): action, responsible person, due date
- CAPA closure with evidence
- Trend analysis: recurring issues flagged

### 7.5 Quality KPIs
- First pass yield (% produced without rework)
- Scrap rate per product and work center
- Supplier rejection rate
- Customer return rate (linked from CRM)
- Cost of poor quality (scrap + rework costs)

---

## 8. Subcontracting

### 8.1 Subcontract Operations
- Specific operations in a routing can be flagged as "subcontract"
- System generates PO to subcontractor for that operation
- Raw materials can be sent to subcontractor (tracked as transfer to external location)
- Subcontractor returns finished/semi-finished goods (inbound receipt)

### 8.2 Subcontract Costing
- Subcontract cost = PO cost from supplier
- Added to work order cost alongside internal operations
- Landed cost allocation for subcontract (if includes freight)

---

## 9. Manufacturing Reporting

### 9.1 Production Reports
- Work orders completed per period
- Production efficiency: actual vs standard time
- Scrap and rework cost per period
- Work in progress (WIP) valuation
- Capacity utilization per work center

### 9.2 Cost Reports
- Production cost per product (material + labor + overhead)
- Standard vs actual cost variance
- Cost trend over time (identify cost creep)

### 9.3 Material Usage
- Material consumption report: actual vs BOM standard
- Excess consumption (waste tracking)
- Material yield report (for process industries: % of input material converted to output)

---

## 10. Integrations

- **Inventory:** Raw material reservation and consumption; finished goods receipt
- **Procurement:** MRP generates purchase requisitions automatically for raw material shortages
- **Sales:** Sales orders drive MRP demand; finished goods fulfil sales orders
- **Accounting:** WIP account, COGS, material consumption, labor costs all auto-posted
- **Quality:** Inspection results block or release inventory automatically
