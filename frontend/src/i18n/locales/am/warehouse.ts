export default {
  // Page Header
  warehouse: 'ዋና መጋዘን',
  warehouseSubtitle: 'የመጋዘን ቦታዎችን ያስተዳድሩ፣ አዳዲስ ዕቃዎችን ይቀበሉ፣ እና በመጋዘን ክፍሎች መካከል የዕቃ ዝውውር ያከናውኑ።',

  // Tabs
  tabs: {
    locations: 'የመጋዘን ቦታዎች',
    receive: 'ዕቃ ተቀበል',
    transfer: 'ዕቃ አዘዋውር',
  },

  // Locations Tab
  createLocation: 'አዲስ የመጋዘን ቦታ ፍጠር',
  nameLabel: 'የቦታው ስም',
  namePlaceholder: 'ለምሳሌ፡ የዕቃ መቀበያ መድረክ',
  codeLabel: 'የቦታው መለያ (Code)',
  codePlaceholder: 'ለምሳሌ፡ RECV-01',
  typeLabel: 'የቦታው ዓይነት',
  addLocationBtn: 'ቦታ ጨምር',
  noLocationsTitle: 'እስካሁን የተመዘገበ የመጋዘን ቦታ የለም',
  noLocationsDescription: 'ለመጀመር ከላይ አዲስ የመጋዘን ቦታ ይመዝግቡ።',
  active: 'ንቁ',
  inactive: 'የማይንቀሳቀስ',

  locationTypes: {
    storage: 'ዕቃ ማስቀመጫ (Storage)',
    receive: 'ዕቃ መቀበያ (Receive)',
    pick: 'ዕቃ መምረጫ (Pick)',
    pack: 'ማሸጊያ (Pack)',
    stage: 'መጠባበቂያ (Stage)',
    ship: 'መጫኛ / መላኪያ (Ship)',
  },

  tableColumns: {
    name: 'ስም',
    code: 'መለያ ኮድ',
    type: 'ዓይነት',
    warehouse: 'መጋዘን',
    status: 'ሁኔታ',
  },

  // Receive Tab
  receiveStockTitle: 'ዕቃ ወደ መጋዘን ተቀበል',
  productLabel: 'ምርት',
  variantLabel: 'ዓይነት (Variant)',
  locationLabel: 'የሚቀመጥበት ቦታ',
  quantityLabel: 'መጠን',
  unitCostLabel: 'የአንድ ፍሬ ወጪ (በሳንቲም)',
  unitCostHelp: 'አማራጭ፤ ካልተሞላ ከመደበኛ የምርት ወጪ ይወሰዳል',
  receiveStockBtn: 'ዕቃ ተቀበል',

  // Transfer Tab
  transferTitle: 'በመጋዘን ቦታዎች መካከል ዕቃ አዘዋውር',
  fromLocationLabel: 'መነሻ ቦታ',
  toLocationLabel: 'መድረሻ ቦታ',
  availableAtSource: 'በመነሻ ቦታ ያለ ክምችት፡',
  unitsCount: '{count} ፍሬ',
  transferStockBtn: 'ዕቃ አዘዋውር',

  // Messages & Validations
  nameAndCodeRequired: 'ስምና መለያ ኮድ መሞላት አለባቸው',
  locationCreatedSuccess: 'የመጋዘኑ ቦታ በተሳካ ሁኔታ ተፈጥሯል',
  locationCreatedFailed: 'የመጋዘኑን ቦታ መፍጠር አልተቻለም',
  productAndLocationRequired: 'ምርትና የመጋዘን ቦታ መመረጥ አለባቸው',
  selectVariantRequired: 'እባክዎ የምርት ዓይነት ይምረጡ',
  quantityMinOne: 'መጠኑ ቢያንስ 1 መሆን አለበት',
  stockReceivedSuccess: 'ዕቃው በተሳካ ሁኔታ ተቀብሏል',
  stockReceivedFailed: 'ዕቃውን መቀበል አልተቻለም',
  productAndBothLocationsRequired: 'ምርትና ሁለቱም የመጋዘን ቦታዎች መመረጥ አለባቸው',
  locationsMustDiffer: 'መነሻና መድረሻ የመጋዘን ቦታዎች የተለያዩ መሆን አለባቸው',
  insufficientStockAtSource: 'በመነሻ ቦታው ላይ በቂ ክምችት የለም። ያለው መጠን፡ {count}',
  stockTransferredSuccess: 'ዕቃው በተሳካ ሁኔታ ተዘዋውሯል',
  stockTransferredFailed: 'ዕቃውን ማዘዋወር አልተቻለም',
}
