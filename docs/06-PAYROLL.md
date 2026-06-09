# MODULE: Payroll

## Overview
The Payroll module handles salary computation, statutory deductions, payslip generation, and bank payment processing. It pulls data automatically from HR (timesheets, leave, salary records) and posts entries to Accounting.

---

## 1. Payroll Configuration

### 1.1 Pay Period Setup
- Pay frequency per employee group: monthly, bi-monthly, bi-weekly, weekly
- Pay period start and end dates
- Payment date (e.g., last working day of month, 25th of month)
- Payroll calendar: full year calendar of pay periods and payment dates

### 1.2 Salary Components
- Configurable component library per tenant
- Component types:
  - **Earnings:** Basic salary, housing allowance, transport allowance, responsibility allowance, sales commission, overtime pay, bonus
  - **Deductions:** Income tax (PAYE), pension (employee contribution), health insurance, union dues, loan repayment, advance recovery
  - **Employer Contributions:** Pension (employer), health insurance (employer), social security contributions
- Each component: name, type, calculation method, taxability, GL account

### 1.3 Calculation Methods
| Method | Description |
|--------|-------------|
| Fixed Amount | Same amount every period |
| % of Basic | e.g., Housing = 20% of basic salary |
| % of Gross | e.g., Pension = 5% of gross salary |
| Tiered/Bracket | Tax calculated on income bands |
| Formula | Custom formula using other components |
| Manual Entry | Amount entered per employee per run |
| From Timesheet | Hourly rate × approved hours |

### 1.4 Tax Tables
- Income tax brackets per country
- Tax relief / personal allowance per employee
- Tax code per employee
- Pluggable per country (Ethiopia PAYE, Kenya PAYE, UK PAYE, etc.)
- Tax tables updated from DB (no code deploy for rate changes)

---

## 2. Employee Payroll Profile

### 2.1 Salary Structure Assignment
- Each employee assigned a salary structure template
- Template defines which components apply
- Override individual component amounts at employee level
- Effective dates for all changes (salary changes never overwrite history)

### 2.2 Banking Details
- Bank name, branch, account number, account name
- Multiple bank accounts (split payment: 70% to account A, 30% to account B)
- Mobile money number (for mobile money disbursement)
- Payment method preference per employee

### 2.3 Tax Information
- Tax identification number (TIN)
- Tax code / tax class
- Dependants (for tax relief calculations, where applicable)
- Exempt from certain deductions (e.g., non-citizen not liable for local pension)

### 2.4 Loans & Advances
- Record salary advance (recovered over N months)
- Record employee loan (amount, interest rate if applicable, repayment schedule)
- Automated monthly deduction from payroll
- Balance tracking and completion flag when fully recovered

---

## 3. Payroll Run

### 3.1 Initiating a Payroll Run
- Select pay period and employee group
- System pre-populates all permanent components automatically
- Flag summary: employees with pending changes, missing data, timesheet not approved

### 3.2 Variable Pay Input
- Enter or import: commissions, bonuses, irregular allowances
- Import variable pay via CSV (bulk entry for large teams)
- Timesheet-based pay automatically calculated from approved hours

### 3.3 Pre-Calculation Review
- Draft calculation for all employees
- Warning flags:
  - Employee new this period (pro-rated salary)
  - Employee leaving this period (pro-rated + terminal benefits)
  - Salary change effective this month
  - Unusual amount variance vs last period (>20% — investigation flag)
  - Negative net pay (deductions exceed earnings)
- Manager and HR review draft before finalizing

### 3.4 Finalization & Approval
- HR manager reviews and approves payroll run
- CFO / finance approval for above-threshold total payroll
- Once finalized: no edits allowed — corrections via supplementary run

### 3.5 Supplementary Payroll Run
- Off-cycle run for: corrections, missed bonus, termination payout
- Same process as regular run but for specific employees

---

## 4. Payslip Generation

### 4.1 Payslip Content
- Employee name, ID, position, department
- Pay period
- Earnings: each component with name and amount
- Deductions: each component with name and amount
- Employer contributions (informational)
- Gross pay, total deductions, net pay
- Year-to-date (YTD) totals for each component
- Tax summary: taxable income, tax calculated, tax YTD
- Leave balance (informational)
- Bank account paid to (masked: ****1234)
- Payroll run reference number

### 4.2 Payslip Distribution
- Auto-generated as password-protected PDF (password: employee date of birth or last 4 of ID)
- Delivered via email to personal email address
- Available in employee self-service portal
- SMS notification when payslip is ready
- Bulk download for HR (all payslips as ZIP)

### 4.3 Payslip Template
- Company logo and branding
- Configurable layout (components shown/hidden)
- Multi-language support (component names in local language)
- Complies with statutory payslip requirements per country

---

## 5. Payment Disbursement

### 5.1 Bank Transfer File
- Generate bank transfer file after payroll approval
- Supported formats: SWIFT MT103, SEPA XML, bank-specific formats (CBE format for Ethiopia, RTGS formats, etc.)
- Single file per bank (aggregated if multiple employees use same bank)
- File includes: employee bank account, amount, payment reference (payroll period)
- Upload to internet banking portal (manual process — ERP generates the file)

### 5.2 Direct Bank API Integration (Advanced)
- Integration with banking APIs (where available) to submit payment directly
- Payment status tracking (initiated → processing → completed → failed)
- Retry failed payments

### 5.3 Mobile Money Disbursement
- M-Pesa, Airtel Money, MTN MoMo API integration
- Bulk disbursement via API
- Transaction confirmation and status tracking

### 5.4 Payment Confirmation
- Mark payments as disbursed (manually or via bank confirmation)
- Failed payment: flag employee, reschedule, notify HR
- Disbursement date recorded per employee

---

## 6. Statutory Compliance

### 6.1 Country-Specific Deductions
Ethiopia example:
- PAYE: tiered income tax (0% up to ETB 600, 10% up to 1650, etc.)
- Pension: 7% employee + 11% employer

Kenya example:
- PAYE: tiered
- NSSF (National Social Security Fund): employee + employer contribution
- NHIF (National Health Insurance Fund): fixed by income band
- Housing Levy: 1.5% employee + 1.5% employer

UK example:
- PAYE income tax
- National Insurance (NI): employee + employer
- Pension auto-enrollment (minimum 5% employee, 3% employer)

- All rates configurable in DB (no code change for rate updates)

### 6.2 Statutory Reports
- P9 (Kenya) / P60 (UK): annual employee tax summary
- PAYE return: monthly tax liability report for tax authority
- Pension schedule: contribution file for pension fund administrator
- NHIF/NSSF schedule: contribution file for social security authority
- Formats match statutory filing requirements

### 6.3 Year-End Processing
- Annual reconciliation report: total earnings, deductions, and tax per employee
- Generate P9/P60/W2 equivalent
- Reset YTD balances for new tax year
- Carry forward unused reliefs (where applicable)

---

## 7. Leave & Overtime in Payroll

### 7.1 Leave Impact
- Unpaid leave days deducted from salary (daily rate × unpaid days)
- Leave taken beyond entitlement: deducted at daily rate
- Pro-ration for joiners/leavers: salary × (working days / total working days in period)

### 7.2 Overtime Calculation
- Overtime hours from approved timesheets
- Overtime rate applied per policy (1.25x, 1.5x, 2x)
- Overtime pay added as separate earnings line

### 7.3 Terminal Benefit Calculation (on exit)
- Notice pay (if not served)
- Accrued annual leave payout
- Severance pay (per statutory formula by country and years of service)
- Gratuity (where applicable)

---

## 8. Payroll Reporting

### 8.1 Payroll Summary Report
- Total payroll cost per period (gross, net, employer contributions)
- By department, cost center, location
- Comparison vs prior period (variance analysis)
- Comparison vs budget

### 8.2 Employee Earnings Report
- Per-employee earnings and deductions for a period or date range
- YTD cumulative report

### 8.3 Cost Center Allocation
- Payroll cost allocated to departments and cost centers
- Posted to Accounting GL accounts per cost center

### 8.4 Payroll Variance Report
- Employees whose pay changed > X% vs last period
- New hires, leavers, pay changes — all flagged

---

## 9. Integrations

- **HR:** Salary structures, employee records, approved timesheets, leave records, loans
- **Accounting:** Journal entries posted per payroll run:
  - Dr: Salary Expense (by cost center)
  - Cr: Payroll Payable (net pay)
  - Cr: Tax Payable (PAYE)
  - Cr: Pension Payable (employer + employee)
- **Banking:** Bank transfer file export; optional API for direct payment
- **Tax Authority:** Statutory return files generated in required format
