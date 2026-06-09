# MODULE: Reporting & Analytics

## Overview
The Reporting module is the intelligence layer of the ERP. It provides pre-built dashboards, standard reports for every module, a self-service report builder for custom queries, KPI tracking, and scheduled report delivery — all without the user writing a line of SQL.

---

## 1. Executive Dashboard

### 1.1 Main KPI Tiles
- Revenue (today, this month, this year) with % change vs prior period
- Gross Profit and Gross Margin %
- Total Expenses
- Net Profit
- Cash Position (current bank balances combined)
- Outstanding AR (total owed by customers)
- Outstanding AP (total owed to suppliers)
- Inventory Value
- Open Sales Orders (count and value)
- Employees (headcount)

### 1.2 Revenue Trend Chart
- Line chart: revenue by day/week/month (configurable period)
- Overlay prior period for comparison
- Hover tooltip with exact values

### 1.3 Top Performers
- Top 5 products by revenue this month
- Top 5 customers by revenue this month
- Top 5 sales reps by revenue this month

### 1.4 Alerts & Action Items
- Overdue invoices (count and value)
- Low stock items
- Pending approvals across all modules
- Contracts/documents expiring soon
- Payroll run due

### 1.5 Dashboard Customization
- Drag-and-drop widget reordering
- Show/hide widgets
- Date range selection (today, this week, this month, this quarter, custom)
- Role-based default layouts (CEO dashboard vs Warehouse Manager dashboard)

---

## 2. Module-Specific Dashboards

### 2.1 Sales Dashboard
- Revenue pipeline: quote → order → invoice → paid funnel
- Sales by rep (bar chart comparison)
- Sales by product category (pie/donut chart)
- Quote conversion rate trend
- Average order value trend
- Outstanding customer balances

### 2.2 Inventory Dashboard
- Stock value by category
- Low stock items table (product, current stock, minimum level, shortfall)
- Stock movement activity (value in vs value out per day)
- Dead stock (no movement in 90+ days)
- Top 10 products by stock value
- Expiry alerts (products expiring in 30/60/90 days)

### 2.3 Warehouse Operations Dashboard
- Orders in queue / picking / packed / dispatched
- Fulfillment time (avg hours from order to dispatch)
- Pick accuracy rate %
- On-time dispatch rate %
- Receiving backlog
- Staff productivity (tasks completed per worker today)

### 2.4 Finance Dashboard
- P&L snapshot (current month vs budget)
- Cash flow waterfall (starting balance + inflows − outflows = ending)
- Accounts receivable aging (stacked bar: 0-30, 31-60, 61-90, 90+)
- Accounts payable aging
- Budget utilization by department
- Tax liability (VAT collected vs paid)

### 2.5 HR Dashboard
- Headcount by department (donut chart)
- Leave utilization (days taken vs entitled, by department)
- Attendance rate today (% clocked in vs expected)
- Open positions (vacancies)
- Upcoming contract renewals
- Performance review completion rate

### 2.6 Procurement Dashboard
- Open POs by status and value
- Spend by category (this month, this year)
- Overdue deliveries (POs past expected date)
- Supplier performance scores
- Pending invoice approvals

---

## 3. Standard Reports Library

### 3.1 Financial Reports

| Report | Description |
|--------|-------------|
| Profit & Loss | Revenue, COGS, expenses, net profit by period |
| Balance Sheet | Assets, liabilities, equity at a point in time |
| Cash Flow Statement | Operating, investing, financing cash movements |
| Trial Balance | All GL accounts with debit/credit totals |
| General Ledger Detail | All transactions per account in a period |
| AR Aging | Customer outstanding balances by age bucket |
| AP Aging | Supplier outstanding balances by age bucket |
| Budget vs Actuals | Actual spend vs budget per account/department |
| Bank Reconciliation | Reconciled and unreconciled bank items |
| Tax Summary | VAT/GST input, output, and net payable |

### 3.2 Sales Reports

| Report | Description |
|--------|-------------|
| Sales Summary | Total sales by period, product, customer, rep |
| Sales by Product | Revenue, quantity, margin per product |
| Sales by Customer | Revenue, order count, average order value per customer |
| Quote Pipeline | All quotes by status and value |
| Win/Loss Report | Closed won vs lost with reasons |
| Invoice Aging | Outstanding invoices by age |
| Top Customers | Ranked by revenue, margin, order frequency |

### 3.3 Inventory Reports

| Report | Description |
|--------|-------------|
| Stock Summary | Current on-hand, committed, available per product |
| Stock Valuation | Total inventory value by product/category |
| Stock Movement | All movements with quantities and values per period |
| Low Stock Report | Products at or below minimum level |
| Dead Stock | Products with no movement in N days |
| Inventory Aging | Stock held by how long it's been in warehouse |
| Expiry Report | Items expiring within specified time window |
| Lot/Serial Traceability | Full trace from receipt to sale per lot/serial |

### 3.4 Procurement Reports

| Report | Description |
|--------|-------------|
| Spend Analysis | Total spend by supplier, category, period |
| Open PO Report | All unfulfilled POs with expected dates |
| Supplier Performance | On-time delivery and quality rates |
| Purchase Price Variance | Actual PO price vs standard/prior price |
| PO Cycle Time | Average time from PR to PO approval |
| 3-Way Match Exceptions | Invoices with price or quantity discrepancies |

### 3.5 HR & Payroll Reports

| Report | Description |
|--------|-------------|
| Headcount Report | Employees by department, type, location |
| Payroll Summary | Total payroll cost by department/period |
| Leave Balance Report | Remaining leave entitlements per employee |
| Attendance Summary | Hours worked, late, absent per employee |
| Turnover Report | Leavers by department and reason |
| Statutory PAYE Report | Monthly tax liability per employee |

### 3.6 Warehouse Reports

| Report | Description |
|--------|-------------|
| GRN Summary | Goods received by period and supplier |
| Pick Accuracy | Correct vs total picks per staff member |
| Dispatch Performance | On-time vs late dispatches |
| Bin Utilization | Space usage per zone/aisle/bin |
| Return Analysis | Returns by reason and product |

---

## 4. Custom Report Builder

### 4.1 Report Builder Interface
- Visual, drag-and-drop — no SQL needed
- Select data source (module/table group): Sales, Inventory, Finance, HR, etc.
- Select columns to include (from all available fields in the data source)
- Add calculated fields: sum, count, average, min, max, % of total, running total
- Add filters: field operator value (e.g., "Date is after 2024-01-01")
- Group by: aggregate rows by category, product, month, rep, etc.
- Sort by any column (ascending/descending)
- Limit rows

### 4.2 Visualization Types
- Table (default)
- Bar chart (vertical or horizontal)
- Line chart
- Pie / Donut chart
- Area chart
- Combo chart (bars + line)
- Number (single KPI tile)
- Funnel chart

### 4.3 Report Saving & Sharing
- Save custom report with a name
- Add to personal dashboard or share with team
- Organize in folders
- Export any time: CSV, Excel, PDF
- Embed chart/table in dashboard

---

## 5. KPI Management

### 5.1 KPI Definition
- KPI name, description, category (financial, operational, HR, etc.)
- Data source: report field or manual entry
- Target value and period (monthly, quarterly, annual)
- Measurement direction: higher is better / lower is better
- Owner (person responsible)

### 5.2 KPI Dashboard
- RAG status per KPI: green (at/above target), amber (within 10% of target), red (below)
- Trend sparkline (last 6 periods)
- Actual vs target percentage
- KPIs grouped by category/department

### 5.3 KPI Alerts
- Alert when KPI goes red (below threshold)
- Alert when KPI trend is declining for 3 consecutive periods
- Alert recipients: configurable per KPI (email + in-app)

---

## 6. Scheduled Reports & Delivery

### 6.1 Report Scheduling
- Schedule any standard or custom report to run automatically
- Frequency: daily, weekly (pick day), monthly (pick day of month), quarterly
- Time of day for report generation
- Date range: last day, last week, last month, last quarter, custom rolling window

### 6.2 Delivery Options
- Email: attach as PDF, Excel, or CSV
- In-app notification with download link
- Save to shared folder

### 6.3 Report Subscriptions
- Users subscribe to reports they need regularly
- Manager subscribes their whole team to weekly performance report
- Unsubscribe without deleting the schedule

---

## 7. Data Export

### 7.1 Export Formats
- PDF (formatted, branded, ready to present or file)
- Excel (.xlsx) — full data, formulas preserved where applicable
- CSV — for import into other tools
- JSON — for developers

### 7.2 Bulk Data Export (GDPR / Audit)
- Export all data for a module per date range
- Full tenant data export (ZIP with all records as CSV files)
- Scheduled data backup export (sent to specified S3 bucket)

---

## 8. Advanced Analytics (Enterprise Tier)

### 8.1 Embedded BI (Apache Superset / Metabase)
- Available on Enterprise plan
- Read-only connection to analytics database
- Full SQL editor for technical users
- Advanced chart types: heatmap, geospatial map, pivot table, histogram
- Dashboards shareable via link (with or without login required)

### 8.2 Cohort Analysis
- Customer retention cohorts: % of customers from month X still buying in month X+N
- Revenue cohort: track revenue per acquisition cohort over time
- Helps understand customer lifetime value and churn patterns

### 8.3 ABC Analysis (automated)
- Inventory: products ranked by revenue contribution (A=top 20%, B=next 30%, C=bottom 50%)
- Customers: ranked by revenue/margin
- Recalculated monthly automatically
- Used in other modules (cycle count frequency, reorder priority)

### 8.4 Forecasting (Basic)
- Revenue forecast: apply growth % to last 3 months average
- Inventory demand forecast: moving average of sales per product
- Used to pre-populate MRP demand and suggest reorder quantities
- Manual override of forecast values

---

## 9. Integrations

- **All modules:** Every module feeds data into the reporting layer automatically — no configuration needed
- **Google Data Studio / Looker Studio:** Export API for connecting external BI tools
- **Excel:** Scheduled report delivery to email; also Excel Online connection (for live pivot tables) — Enterprise tier
- **Slack / Teams:** KPI alert notifications posted to configurable channels
- **Email:** Scheduled report delivery via SendGrid
