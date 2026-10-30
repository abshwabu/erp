export default {
  title: 'Fixed Assets',
  subtitle: 'Track capital equipment, vehicles, and automate depreciation schedules.',
  newAsset: 'New Asset',
  editAsset: 'Edit Asset',
  deleteAsset: 'Delete Asset',
  loading: 'Loading assets...',
  emptyState: {
    title: 'No assets registered',
    subtitle: 'Track capital equipment, vehicles, and automate depreciation.',
    action: 'Register First Asset'
  },
  stats: {
    totalAssets: 'Total Assets',
    totalValue: 'Total Value',
    activeAssets: 'Active',
    maintenance: 'In Maintenance'
  },
  filters: {
    searchPlaceholder: 'Search by tag or asset name...',
    allCategories: 'All Categories',
    allStatuses: 'All Statuses',
    category: 'Category',
    status: 'Status'
  },
  categories: {
    equipment: 'Equipment',
    machinery: 'Machinery',
    vehicle: 'Vehicle',
    electronics: 'Electronics',
    furniture: 'Furniture',
    building: 'Building'
  },
  statuses: {
    active: 'Active',
    maintenance: 'Maintenance',
    disposed: 'Disposed',
    retired: 'Retired'
  },
  methods: {
    straight_line: 'Straight Line',
    declining_balance: 'Declining Balance'
  },
  details: {
    selectAssetPrompt: 'Select an asset from the list to view specifications and depreciation schedules.',
    categoryLabel: 'Category: {category}',
    specifications: 'Specifications',
    purchaseCost: 'Purchase Cost',
    salvageValue: 'Salvage Value',
    usefulLife: 'Useful Life',
    usefulLifeYears: '{years} years',
    method: 'Method',
    serialNumber: 'Serial Number',
    purchaseDate: 'Purchase Date',
    notes: 'Notes',
    notSpecified: 'Not specified',
    depreciationSchedule: 'Depreciation Schedule',
    calculateSchedule: 'Calculate Schedule',
    calculating: 'Calculating...',
    noSchedule: 'No schedule generated yet. Click "Calculate Schedule" to project annual depreciation.',
    table: {
      fiscalYear: 'Fiscal Year',
      depreciation: 'Depreciation',
      accumulated: 'Accumulated',
      bookValue: 'Book Value'
    }
  },
  modal: {
    createTitle: 'Register New Asset',
    editTitle: 'Edit Asset: {name}',
    nameLabel: 'Asset Name',
    namePlaceholder: 'e.g. MacBook Pro 16", Delivery Van',
    categoryLabel: 'Category',
    statusLabel: 'Status',
    serialNumberLabel: 'Serial Number',
    serialNumberPlaceholder: 'Optional serial or VIN',
    purchaseDateLabel: 'Purchase Date',
    purchaseCostLabel: 'Purchase Cost',
    salvageValueLabel: 'Salvage Value',
    usefulLifeLabel: 'Useful Life (Years)',
    methodLabel: 'Depreciation Method',
    notesLabel: 'Notes / Description',
    notesPlaceholder: 'Optional maintenance notes, location, or serial details',
    cancel: 'Cancel',
    save: 'Save Asset',
    create: 'Register Asset',
    saving: 'Saving...'
  },
  deleteModal: {
    title: 'Delete Asset',
    confirmText: 'Are you sure you want to delete asset {tag} - {name}?',
    warning: 'This action cannot be undone. All recorded depreciation schedules will be permanently removed.',
    cancel: 'Cancel',
    confirm: 'Delete Asset',
    deleting: 'Deleting...'
  },
  notifications: {
    created: 'Asset created successfully',
    updated: 'Asset updated successfully',
    deleted: 'Asset deleted successfully',
    scheduleCalculated: 'Depreciation schedule calculated successfully',
    error: 'An error occurred. Please try again.'
  }
}
