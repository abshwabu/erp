# MODULE: Accounting & Finance

## Overview
The Accounting module provides a complete double-entry bookkeeping system. It is the financial backbone of the ERP — every financial transaction in any module (sales, procurement, payroll, inventory adjustments) automatically generates the appropriate journal entries. The module produces all standard financial statements and tax reports.

---

## 1. Chart of Accounts

### 1.1 Account Structure
- Hierarchical: Account Group → Account Type → Account
- Standard account types: Asset, Liability, Equity, Revenue, Expense, Cost of Sales
- Account code (numbering scheme configurable: 4-digit, 6-digit)
- Account name and description
- Currency (base currency or specific foreign currency account)
- Active/inactive toggle

### 1.2 Default Templates
- Country-specific COA templates (Ethiopia, Kenya, UK, US, generic IFRS)
- Templates pre-load on company setup, fully editable afterwards
- Import COA via CSV

### 1.3 Account Mapping
- Each transaction type in the ERP maps to specific GL accounts
- Configurable mappings: POS cash sales → which revenue account; inventory purchase → which asset account
- Default mappings sensible for most businesses, customizable for complex entities
- Product category → revenue account mapping (for revenue by category reporting)
- Department → expense account mapping (for department P&L)

---

## 2. Journal Entries

### 2.1 Manual Journal Entries
- Debit and credit lines (must balance to zero)
- Date, reference, description per line
- Multiple lines per journal
- Attachment support (scanned document as evidence)
- Journal entry types: standard, adjusting, closing, reversing

### 2.2 Automatic Journal Entries (System-Generated)
Generated automatically by other modules — no manual input needed:

| Transaction | Debit | Credit |
|-------------|-------|--------|
| POS cash sale | Cash, Tax Payable | Revenue, COGS, Inventory |
| Invoice raised | Accounts Receivable | Revenue, Tax Payable |
| Invoice paid | Cash/Bank | Accounts Receivable |
| Purchase GRN | Inventory Asset | Goods Receipt Clearing |
| Supplier invoice | Goods Receipt Clearing | Accounts Payable |
| Supplier payment | Accounts Payable | Bank |
| Payroll run | Salary Expense (by dept) | Payroll Payable, Tax Payable |
| Inventory adjustment | Inventory Adjustment Expense | Inventory Asset |
| Fixed asset depreciation | Depreciation Expense | Accumulated Depreciation |

### 2.3 Recurring Journal Entries
- Templates for recurring entries (e.g., monthly rent, annual insurance prepayment amortization)
- Frequency: daily, weekly, monthly, quarterly, annually
- End date or number of occurrences
- Auto-post or require approval before posting

### 2.4 Reversing Entries
- Any journal entry can be reversed (creates equal and opposite entry)
- One-click reversal with automatic date (first day of next period)
- Used for accruals posted at period-end that reverse in new period

### 2.5 Journal Entry Approval
- Journal entries above a configurable threshold require approval before posting
- Prevents unauthorized large manual entries
- Approval by: Finance Manager, CFO (configurable by amount)

---

## 3. General Ledger

### 3.1 GL Views
- Account balance summary: all accounts with current balance
- Drill down from account balance → individual transactions → source document
- GL detail: all transactions for an account in a date range
- Comparative view: current period vs prior period, current YTD vs prior YTD

### 3.2 Period Management
- Accounting periods (months) opened and closed explicitly
- Closing a period prevents further posting to that period (except by finance admin)
- Soft close (warning only) vs hard close (error if posting attempted)
- Year-end closing: transfers P&L balances to retained earnings account

### 3.3 Suspense Accounts
- Unmatched items parked in suspense accounts
- Suspense account balance report (should always be near zero)
- Aging of suspense items (items older than X days flagged)

---

## 4. Accounts Receivable (AR)

### 4.1 Customer Invoice Management
- See Sales module for invoice creation
- AR module tracks outstanding balances
- Customer aging: 0–30 days, 31–60 days, 61–90 days, 90+ days
- Total AR balance and average days to collect (DSO)

### 4.2 Payment Recording
- Record customer payment against invoice(s)
- Partial payment: allocate to specific invoice lines
- Overpayment: record as credit note or customer credit balance
- Payment methods: bank transfer (match from bank feed), cash, cheque, card, mobile money

### 4.3 Bank Reconciliation
- Import bank statement (OFX, CSV, direct bank feed via Open Banking API)
- Auto-match bank transactions to recorded payments (by amount, reference, date proximity)
- Manual match for unmatched items
- Unreconciled items report
- Reconciliation sign-off by finance manager
- Reconciled statements archived as PDF

### 4.4 Credit Management
- Credit limit per customer
- Available credit = credit limit − outstanding AR balance
- POS and Sales module check credit limit before confirming transaction
- Credit hold flag (blocks new orders regardless of limit)
- Credit limit change requires finance manager approval

### 4.5 Collections & Reminders
- Automated payment reminder emails at: 3 days before due, on due date, 7 days overdue, 14 days overdue, 30 days overdue
- Reminder template customizable (escalating tone: friendly → formal → final notice)
- Collections notes: log calls, emails, promises to pay
- Dispute flag: put on hold while dispute is investigated
- Write-off: bad debt write-off workflow (requires finance + manager approval)

---

## 5. Accounts Payable (AP)

### 5.1 Supplier Invoice Management
- See Procurement module for invoice entry and 3-way matching
- AP tracks outstanding balances per supplier
- Supplier aging report: 0–30, 31–60, 61–90, 90+ days
- Total AP balance and average days to pay (DPO)

### 5.2 Payment Runs
- Schedule payment runs (weekly, bi-weekly, monthly)
- Filter invoices: by due date, by supplier, by value
- Cash flow consideration: show available bank balance vs proposed payment run total
- Generate bank transfer file (see Procurement module)
- Post payment entries automatically on disbursement confirmation

### 5.3 Debit Notes
- Issue debit note to supplier (for goods returned, price disputes, short shipments)
- Debit note offsets against next supplier invoice or triggers cash refund

---

## 6. Tax Management

### 6.1 Tax Configuration
- Tax types: VAT, GST, Sales Tax, Withholding Tax (WHT)
- Tax rates: standard rate, reduced rate, zero rate, exempt
- Tax groups (multiple taxes applying to same transaction)
- Tax applicability rules: by product category, customer type, location

### 6.2 Tax on Transactions
- Tax automatically applied on sales invoices and purchase invoices based on rules
- Tax-inclusive vs tax-exclusive pricing (configurable per transaction type)
- Tax exempt customers/suppliers (flag with exemption certificate upload)
- Reverse charge mechanism (B2B in EU — buyer accounts for VAT)

### 6.3 Tax Reports
- Output tax (collected on sales): by period, by rate
- Input tax (paid on purchases): by period, by rate
- Net VAT payable / recoverable
- Tax return summary: formatted for filing with tax authority
- Export in format required by local tax authority (e.g., Excel template, XML)

### 6.4 Withholding Tax (WHT)
- WHT deducted from supplier payment (e.g., 2% WHT on services)
- WHT certificate generation per supplier per period
- WHT payable to tax authority tracked separately
- Annual WHT summary report

---

## 7. Multi-Currency

### 7.1 Foreign Currency Transactions
- Invoice, payment, journal entries can be in any currency
- Exchange rate source: manual entry or auto-fetched from API (Fixer.io)
- Rate locked at transaction date
- Base currency equivalent calculated and stored alongside foreign currency amount

### 7.2 Exchange Rate Management
- Daily rate import from external API
- Manual rate override for specific transactions
- Historical rate archive (every rate ever used stored)
- Exchange rate report: rates used in any period

### 7.3 Foreign Currency Revaluation
- Month-end revaluation of outstanding AR/AP balances at current rates
- Unrealized FX gain/loss journal entries
- Reversal in next period (standard practice)
- Realized FX gain/loss on settlement (difference between invoice rate and payment rate)

---

## 8. Fixed Assets (Linked to Asset Management Module)

### 8.1 Asset Register in Accounting
- Each asset has an accounting record: purchase cost, accumulated depreciation, net book value
- Asset cost capitalized (Dr: Asset Account, Cr: AP or Cash)

### 8.2 Depreciation
- Methods: straight-line, declining balance (reducing balance), units of production
- Depreciation calculated monthly
- Automatic depreciation journal entry on schedule
- Partial period depreciation for assets purchased mid-month (configurable: full month, half month, pro-rated)

### 8.3 Asset Disposal
- Disposal proceeds received → Dr Cash, Cr Asset Disposal account
- Remove net book value → Dr Accumulated Depreciation, Dr/Cr Profit/Loss on Disposal
- Full audit trail of disposal

---

## 9. Budgeting & Forecasting

### 9.1 Budget Creation
- Annual budget by: department, cost center, account
- Import budget via Excel
- Top-down (total allocated to departments) or bottom-up (departments submit, consolidated)
- Budget version control (working budget, approved budget, revised budget)

### 9.2 Budget vs Actuals
- Real-time comparison: budget vs actual expenditure per account/department/period
- Variance: amount and percentage
- Drill-down to transactions causing variance
- Budget utilization: % spent of budget for the period

### 9.3 Rolling Forecasts
- Re-forecast remaining periods based on actuals to date
- Extrapolate based on run rate or manual input
- Forecast vs budget vs prior year comparison

---

## 10. Financial Statements

### 10.1 Profit & Loss (Income Statement)
- Revenue breakdown by category
- Cost of Goods Sold
- Gross Profit and Gross Margin %
- Operating Expenses by type
- EBITDA, EBIT, Net Profit
- Comparative: current period, prior period, YTD, prior YTD

### 10.2 Balance Sheet
- Assets: current (cash, AR, inventory) and non-current (fixed assets)
- Liabilities: current (AP, tax payable) and non-current (loans)
- Equity: share capital, retained earnings
- Must balance (Assets = Liabilities + Equity) — system validates this

### 10.3 Cash Flow Statement
- Operating, investing, and financing activities
- Indirect method (from net income, adjusting for non-cash items)
- Closing cash balance reconciles to bank accounts

### 10.4 Trial Balance
- All accounts with debit and credit totals for a period
- Opening balance + movements = closing balance
- Export to Excel/PDF

### 10.5 Custom Reports
- Report builder: select accounts, date ranges, comparison columns
- Segment reports: by department, cost center, project
- Consolidation: combine reports from multiple entities (future feature)

---

## 11. Audit & Compliance

- Complete audit trail on every journal entry (who created, approved, modified)
- Locked periods prevent retroactive changes
- Journal entry reason required for manual entries
- Auditor read-only access (view without ability to post)
- IFRS/GAAP compliance mode (additional validation rules)
- Data export for external auditors (filtered by period, account range)
