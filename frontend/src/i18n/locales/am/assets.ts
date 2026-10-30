export default {
  title: 'ቋሚ ንብረቶች (Fixed Assets)',
  subtitle: 'የካፒታል ዕቃዎችን፣ ተሽከርካሪዎችን ይከታተሉ እና የእርጅና ቅነሳ ሰሌዳዎችን በራስ-ሰር ያሰሉ።',
  newAsset: 'አዲስ ንብረት መዝግብ',
  editAsset: 'ንብረት አስተካክል',
  deleteAsset: 'ንብረት ሰርዝ',
  loading: 'ንብረቶች በመጫን ላይ ናቸው...',
  emptyState: {
    title: 'ምንም የተመዘገበ ንብረት የለም',
    subtitle: 'የካፒታል መሣሪያዎችን፣ ተሽከርካሪዎችን ይመዝግቡ እና የዋጋ ቅነሳን በራስ-ሰር ያከናውኑ።',
    action: 'የመጀመሪያ ንብረት መዝግብ'
  },
  stats: {
    totalAssets: 'ጠቅላላ ንብረቶች',
    totalValue: 'ጠቅላላ የንብረት ዋጋ',
    activeAssets: 'በአገልግሎት ላይ ያሉ',
    maintenance: 'በጥገና ላይ ያሉ'
  },
  filters: {
    searchPlaceholder: 'በመለያ ቁጥር ወይም በንብረት ስም ፈልግ...',
    allCategories: 'ሁሉም ምድቦች',
    allStatuses: 'ሁሉም ሁኔታዎች',
    category: 'ምድብ',
    status: 'ሁኔታ'
  },
  categories: {
    equipment: 'መሣሪያ (Equipment)',
    machinery: 'ማሽነሪ (Machinery)',
    vehicle: 'ተሽከርካሪ (Vehicle)',
    electronics: 'ኤሌክትሮኒክስ (Electronics)',
    furniture: 'የቢሮ ዕቃዎች (Furniture)',
    building: 'ሕንፃ/ህንፃ (Building)'
  },
  statuses: {
    active: 'ንቁ (Active)',
    maintenance: 'ጥገና (Maintenance)',
    disposed: 'የተወገደ (Disposed)',
    retired: 'ጡረታ የወጣ (Retired)'
  },
  methods: {
    straight_line: 'ቀጥተኛ መስመር (Straight Line)',
    declining_balance: 'እየቀነሰ የሚሄድ ቀሪ (Declining Balance)'
  },
  details: {
    selectAssetPrompt: 'ዝርዝር መግለጫዎችን እና የእርጅና ቅነሳ ሰንጠረዦችን ለማየት ከዝርዝሩ ውስጥ ንብረት ይምረጡ።',
    categoryLabel: 'ምድብ፡ {category}',
    specifications: 'ዝርዝር መግለጫዎች',
    purchaseCost: 'የግዢ ዋጋ',
    salvageValue: 'የመጨረሻ ቀሪ ዋጋ (Salvage)',
    usefulLife: 'የአገልግሎት ዘመን',
    usefulLifeYears: '{years} ዓመታት',
    method: 'የቅነሳ ስልት',
    serialNumber: 'የሴሪያል ቁጥር',
    purchaseDate: 'የተገዛበት ቀን',
    notes: 'ማስታወሻዎች',
    notSpecified: 'አልተገለጸም',
    depreciationSchedule: 'የእርጅና ቅነሳ ሰሌዳ (Depreciation Schedule)',
    calculateSchedule: 'ሰሌዳውን አስላ',
    calculating: 'በማስላት ላይ...',
    noSchedule: 'እስካሁን ምንም የእርጅና ቅነሳ ሰሌዳ አልተሰላም። ዓመታዊ ቅነሳን ለማስላት "ሰሌዳውን አስላ" የሚለውን ይጫኑ።',
    table: {
      fiscalYear: 'የበጀት ዓመት',
      depreciation: 'ዓመታዊ ቅነሳ',
      accumulated: 'የተጠራቀመ ቅነሳ',
      bookValue: 'የመዝገብ ዋጋ (Book Value)'
    }
  },
  modal: {
    createTitle: 'አዲስ ንብረት መመዝገቢያ',
    editTitle: 'ንብረት አስተካክል፡ {name}',
    nameLabel: 'የንብረቱ ስም',
    namePlaceholder: 'ምሳሌ፡ ማክቡክ ፕሮ 16"፣ የጭነት መኪና',
    categoryLabel: 'ምድብ',
    statusLabel: 'ሁኔታ',
    serialNumberLabel: 'የሴሪያል ቁጥር',
    serialNumberPlaceholder: 'አማራጭ የሴሪያል ወይም የሻሲ ቁጥር',
    purchaseDateLabel: 'የተገዛበት ቀን',
    purchaseCostLabel: 'የግዢ ዋጋ',
    salvageValueLabel: 'የመጨረሻ ቀሪ ዋጋ (Salvage)',
    usefulLifeLabel: 'የአገልግሎት ዘመን (በዓመታት)',
    methodLabel: 'የእርጅና ቅነሳ ዘዴ',
    notesLabel: 'ማስታወሻ / መግለጫ',
    notesPlaceholder: 'ስለ ንብረቱ ተጨማሪ ማስታወሻ ወይም መረጃ',
    cancel: 'ይቅር',
    save: 'ለውጦችን መዝግብ',
    create: 'ንብረቱን መዝግብ',
    saving: 'በማስቀመጥ ላይ...'
  },
  deleteModal: {
    title: 'ንብረት መሰረዣ',
    confirmText: 'ንብረት {tag} - {name}ን በእርግጠኝነት መሰረዝ ይፈልጋሉ?',
    warning: 'ይህ እርምጃ ሊመለስ አይችልም። የተመዘገቡ የእርጅና ቅነሳ ሰሌዳዎች በሙሉ አብረው ይሰረዛሉ።',
    cancel: 'ይቅር',
    confirm: 'ንብረቱን ሰርዝ',
    deleting: 'በመሰረዝ ላይ...'
  },
  notifications: {
    created: 'ንብረቱ በተሳካ ሁኔታ ተመዝግቧል',
    updated: 'ንብረቱ በተሳካ ሁኔታ ተስተካክሏል',
    deleted: 'ንብረቱ በተሳካ ሁኔታ ተሰርዟል',
    scheduleCalculated: 'የእርጅና ቅነሳ ሰሌዳው በተሳካ ሁኔታ ተሰልቷል',
    error: 'ስህተት ተከስቷል። እባክዎ እንደገና ይሞክሩ።'
  }
}
