export default {
  header: {
    title: 'Payroll & Compensation',
    subtitle: 'Run monthly payroll, generate itemized employee payslips, and review disbursement totals.',
    newRunBtn: 'New Payroll Run'
  },
  kpis: {
    totalProcessed: 'Total Processed Payout',
    latestNet: 'Latest Run Net Pay',
    processedPayrolls: 'Processed Payrolls',
    draftRunsPending: 'Draft Runs Pending'
  },
  runs: {
    title: 'Payroll Runs',
    filters: {
      all: 'All',
      processed: 'Processed',
      draft: 'Draft'
    },
    employeesCount: 'Employees',
    delete: 'Delete',
    recalculate: 'Recalculate',
    processNow: 'Process Now',
    emptyTitle: 'No payroll runs found.',
    createFirst: 'Create First Run',
    deleteConfirm: 'Are you sure you want to delete the payroll run for'
  },
  details: {
    processedOn: 'Processed on',
    recalculateSalaries: 'Recalculate Salaries',
    processPayrollRun: 'Process Payroll Run',
    totalNetDisbursed: 'Total Net Disbursed',
    totalGrossSalary: 'Total Gross Salary',
    employeesPaid: 'Employees Paid',
    itemizedPayslips: 'Itemized Employee Payslips',
    searchPlaceholder: 'Filter by employee name...',
    noPosition: 'No Position',
    noDept: 'No Dept',
    bankTransfer: 'Bank Transfer',
    emptyPayslips: 'No payslips generated for this run yet. Click "Process Payroll Run" to compute payslips.',
    selectRunTitle: 'Select a Payroll Run',
    selectRunSubtitle: 'Choose a payroll period from the left list or create a new run to view payslips and disbursement details.'
  },
  createModal: {
    title: 'New Payroll Run',
    quickPresets: 'Quick Period Preset',
    presets: {
      thisMonth: 'This Month',
      lastMonth: 'Last Month',
      last14Days: 'Last 14 Days'
    },
    periodStart: 'Period Start Date',
    periodEnd: 'Period End Date',
    workforcePreview: 'Workforce & Payout Preview',
    activeEmployees: 'Active Employees',
    estGross: 'Estimated Total Gross Salary:',
    estNet: 'Estimated Net Disbursement:',
    autoProcessCheckbox: 'Process immediately and generate payslips upon creation',
    saveDraftBtn: 'Save Draft Run',
    createProcessBtn: 'Create & Process',
    cancel: 'Cancel'
  },
  payslipModal: {
    title: 'Employee Payslip Details',
    voucherTitle: 'PAYSLIP VOUCHER',
    paidBadge: 'PAID',
    employee: 'Employee',
    deptRole: 'Department & Role',
    type: 'Type',
    description: 'Earnings & Deductions Description',
    amount: 'Amount',
    baseSalary: 'Base Salary',
    statutoryDeductions: 'Statutory Deductions & Taxes',
    netPayout: 'Net Payout',
    paymentMethod: 'Payment Method:',
    directBankTransfer: 'Direct Bank Transfer',
    account: 'Account:',
    printPayslip: 'Print Payslip',
    close: 'Close'
  },
  messages: {
    createdAndProcessed: 'Payroll created and processed successfully',
    draftCreated: 'Draft payroll run created',
    recalculated: 'Payroll recalculated with current employee salaries',
    deleted: 'Payroll run deleted',
    failedCreate: 'Failed to create payroll run',
    failedProcess: 'Failed to process payroll run',
    failedDelete: 'Failed to delete payroll run'
  }
}
