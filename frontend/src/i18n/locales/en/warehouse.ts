export default {
  // Page Header
  warehouse: 'Warehouse',
  warehouseSubtitle: 'Manage locations, receive stock, and transfer between bins.',

  // Tabs
  tabs: {
    locations: 'Locations',
    receive: 'Receive',
    transfer: 'Transfer',
  },

  // Locations Tab
  createLocation: 'Create location',
  nameLabel: 'Name',
  namePlaceholder: 'Receiving dock',
  codeLabel: 'Code',
  codePlaceholder: 'RECV-01',
  typeLabel: 'Type',
  addLocationBtn: 'Add location',
  noLocationsTitle: 'No locations yet',
  noLocationsDescription: 'Create a stock location above to get started.',
  active: 'Active',
  inactive: 'Inactive',

  locationTypes: {
    storage: 'Storage',
    receive: 'Receive',
    pick: 'Pick',
    pack: 'Pack',
    stage: 'Stage',
    ship: 'Ship',
  },

  tableColumns: {
    name: 'Name',
    code: 'Code',
    type: 'Type',
    warehouse: 'Warehouse',
    status: 'Status',
  },

  // Receive Tab
  receiveStockTitle: 'Receive stock',
  productLabel: 'Product',
  variantLabel: 'Variant',
  locationLabel: 'Location',
  quantityLabel: 'Quantity',
  unitCostLabel: 'Unit cost (cents)',
  unitCostHelp: 'Optional; defaults to product cost',
  receiveStockBtn: 'Receive stock',

  // Transfer Tab
  transferTitle: 'Transfer between locations',
  fromLocationLabel: 'From location',
  toLocationLabel: 'To location',
  availableAtSource: 'Available at Source:',
  unitsCount: '{count} units',
  transferStockBtn: 'Transfer stock',

  // Messages & Validations
  nameAndCodeRequired: 'Name and code are required',
  locationCreatedSuccess: 'Location created successfully',
  locationCreatedFailed: 'Failed to create location',
  productAndLocationRequired: 'Product and location are required',
  selectVariantRequired: 'Please select a product variant',
  quantityMinOne: 'Quantity must be at least 1',
  stockReceivedSuccess: 'Stock received successfully',
  stockReceivedFailed: 'Failed to receive stock',
  productAndBothLocationsRequired: 'Product and both locations are required',
  locationsMustDiffer: 'From and to locations must differ',
  insufficientStockAtSource: 'Insufficient stock at source location. Available: {count}',
  stockTransferredSuccess: 'Stock transferred successfully',
  stockTransferredFailed: 'Failed to transfer stock',
}
