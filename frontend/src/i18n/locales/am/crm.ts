export default {
  common: {
    title: 'ደንበኞች ግንኙነት (CRM)',
    subtitle: 'እጩ ደንበኞች፣ ስምምነቶች፣ አድራሻዎች፣ ተግባራት እና የደንበኛ ቅጾች።',
    currency: 'USD',
    save: 'አስቀምጥ',
    cancel: 'ሰርዝ',
    delete: 'አስወግድ',
    edit: 'አርትዕ',
    close: 'ዝጋ',
    loading: 'በመጫን ላይ…',
    noData: 'ምንም መረጃ አልተገኘም።'
  },
  dashboard: {
    title: 'የደንበኞች ግንኙነት እና የሽያጭ መስመር አጠቃላይ እይታ',
    subtitle: 'የሽያጭ አፈጻጸምን፣ የእጩ ደንበኞች ሽግግርን፣ የስምምነት መስመርን እና የተሳትፎ ሁኔታን ይከታተሉ።',
    kpis: {
      activePipelineValue: 'በሂደት ላይ ያለ የስምምነት ዋጋ',
      closedWonRevenue: 'የተሳኩ ስምምነቶች ገቢ',
      winRate: 'የስኬት መጠን',
      activeLeads: 'ንቁ እጩ ደንበኞች'
    },
    stageDistribution: 'የስምምነቶች ደረጃ ክፍፍል',
    openKanban: 'የካንባን ሰሌዳ ክፈት',
    opportunities: 'እድሎች',
    topDeals: 'ዋና ዋና የሽያጭ እድሎች',
    viewAllDeals: 'ሁሉንም ስምምነቶች ይመልከቱ →',
    winProbability: 'የማሸነፍ እድል',
    noDeals: 'እስካሁን በሽያጭ መስመር ውስጥ ምንም ንቁ ስምምነቶች የሉም።',
    recentActivities: 'የቅርብ ጊዜ ተግባራት እና ግንኙነቶች',
    viewAllActivities: 'ሁሉንም ይመልከቱ →',
    noActivities: 'እስካሁን የተመዘገበ እንቅስቃሴ የለም።',
    stages: {
      qualification: 'ማጣሪያ',
      proposal: 'የቀረበ የዋጋ ሀሳብ',
      negotiation: 'ድርድር',
      won: 'የተሳካ ስምምነት',
      lost: 'ያልተሳካ'
    },
    buttons: {
      newLead: 'አዲስ እጩ ደንበኛ',
      newDeal: 'አዲስ ስምምነት'
    }
  },
  leads: {
    title: 'እጩ ደንበኞች እና ገቢ ጥያቄዎች',
    subtitle: 'እጩ ደንበኞችን ይመዝግቡ፣ ብቃትን ያረጋግጡ፣ የቅጽ ምላሾችን ይገምግሙ እና ወደ ሽያጭ መስመር ያሸጋግሩ።',
    captureNewLead: 'አዲስ እጩ ደንበኛ መዝግብ',
    kpis: {
      totalInbound: 'ጠቅላላ እጩ ደንበኞች',
      qualified: 'ብቁ እጩዎች',
      converted: 'ወደ ሽያጭ የተሸጋገሩ',
      estValue: 'ጠቅላላ የተገመተ ዋጋ'
    },
    statusFilters: {
      all: 'ሁሉም',
      new: 'አዲስ',
      contacted: 'የተገናኘ',
      qualified: 'ብቁ',
      unqualified: 'ያልበቃ',
      converted: 'የተሸጋገረ'
    },
    table: {
      contactCompany: 'እጩ ደንበኛ እና ድርጅት',
      status: 'ሁኔታ',
      priority: 'ቅድሚያ',
      channel: 'የመጣበት መንገድ / ምንጭ',
      estValue: 'የተገመተ ዋጋ (በጀት)',
      actions: 'እርምጃዎች',
      formResponsesBadge: 'የቅጽ ምላሾች',
      noContactInfo: 'የመገናኛ መረጃ የለም',
      convert: 'አሸጋግር',
      emptyTitle: 'ምንም እጩ ደንበኞች አልተገኙም',
      emptyText: 'የደንበኛ ጥያቄዎችን ይመዝግቡ ወይም የሽያጭ መስመርዎን ለመገንባት የቅጽ ማስተዋወቂያ አገናኝዎን ያጋሩ።',
      captureFirst: 'የመጀመሪያ እጩ ደንበኛ መዝግብ'
    },
    statuses: {
      new: 'አዲስ እጩ',
      contacted: 'የተገናኘ',
      qualified: 'ብቁ',
      unqualified: 'ያልበቃ',
      converted: 'የተሸጋገረ 🎉'
    },
    priorities: {
      urgent: 'አጣዳፊ',
      high: 'ከፍተኛ',
      medium: 'መካከለኛ',
      low: 'ዝቅተኛ'
    },
    sources: {
      website: 'ድረ-ገጽ',
      agency: 'የኤጀንሲ ጥቆማ',
      social_media: 'ማህበራዊ ሚዲያ',
      google_ads: 'የጉግል / የሚከፈልበት ማስታወቂያ',
      referral: 'የደንበኛ ጥቆማ',
      outreach: 'ቀጥታ ግንኙነት',
      event: 'ኤግዚቢሽን / ሁነት',
      other: 'ሌላ'
    },
    detailsModal: {
      title: 'የእጩ ደንበኛ ዝርዝር መረጃ',
      estBudget: 'የተገመተ ዋጋ / በጀት',
      channel: 'የመጣበት መንገድ',
      email: 'ኢሜይል',
      phone: 'ስልክ',
      questionnaireResponses: 'የቀረቡ የጥያቄ ምላሾች',
      notes: 'ተጨማሪ ማስታወሻዎች',
      individualProspect: 'ግለሰብ እጩ ደንበኛ',
      convertToDeal: 'ወደ ሽያጭ ስምምነት አሸጋግር'
    },
    formModal: {
      createTitle: 'አዲስ እጩ ደንበኛ መዝግብ',
      editTitle: 'የእጩ ደንበኛ መረጃ አርትዕ',
      contactName: 'የደንበኛ ስም',
      company: 'ድርጅት / ተቋም',
      jobTitle: 'የስራ መደብ',
      email: 'የኢሜይል አድራሻ',
      phone: 'የስልክ ቁጥር',
      source: 'የመጣበት ምንጭ',
      status: 'ሁኔታ',
      priority: 'ቅድሚያ',
      estValue: 'የተገመተ ዋጋ / በጀት ($)',
      notesLabel: 'ማስታወሻ እና ተጨማሪ መረጃ',
      notesPlaceholder: 'ዋና ዋና የውይይት ነጥቦች፣ የደንበኛ ፍላጎቶች፣ የጊዜ ገደብ...',
      saveBtn: 'እጩ ደንበኛ መዝግብ',
      updateBtn: 'መረጃውን አዘምን'
    },
    convertModal: {
      title: 'እጩ ደንበኛን ወደ ደንበኛ እና የሽያጭ ስምምነት አሸጋግር',
      leadToBeConverted: 'የሚሸጋገረው እጩ ደንበኛ',
      conversionNotice: 'ማሸጋገር በራስ-ሰር ንቁ ደንበኛን ይመዘግባል እንዲሁም በሽያጭ መስመርዎ ውስጥ አዲስ ስምምነት ይፈጥራል።',
      dealTitle: 'የስምምነቱ ርዕስ',
      dealTitlePlaceholder: 'ለምሳሌ፡ የድርጅት ፍቃድ ሽያጭ',
      dealAmount: 'የስምምነቱ ዋጋ ($)',
      initialStage: 'የመጀመሪያ ደረጃ',
      confirmBtn: 'ሽግግሩን አረጋግጥ'
    },
    messages: {
      created: 'አዲስ እጩ ደንበኛ ተመዝግቧል',
      updated: 'የእጩ ደንበኛ መረጃ በተሳካ ሁኔታ ተሻሽሏል',
      converted: 'እጩ ደንበኛ ወደ ደንበኛ እና የሽያጭ ስምምነት ተሸጋግሯል! 🎉',
      deleted: 'እጩ ደንበኛ ተሰርዟል',
      nameRequired: 'እባክዎ የእጩ ደንበኛውን ስም ያስገቡ',
      failedSave: 'እጩ ደንበኛውን መመዝገብ አልተቻለም',
      failedConvert: 'እጩ ደንበኛውን ማሸጋገር አልተቻለም'
    }
  },
  deals: {
    title: 'የሽያጭ ስምምነቶች እና የሽያጭ መስመር',
    subtitle: 'ከማጣሪያ እስከ ስምምነት መደምደሚያ ድረስ ከፍተኛ ዋጋ ያላቸውን የንግድ እድሎች ይከታተሉ እና ያሳድጉ።',
    newDeal: 'አዲስ ስምምነት',
    viewKanban: 'ካንባን',
    viewTable: 'ሰንጠረዥ',
    searchPlaceholder: 'ስምምነቶችን በርዕስ፣ በድርጅት ወይም በደንበኛ ይፈልጉ...',
    opportunitiesCount: 'እድሎች',
    kpis: {
      activeOpportunities: 'በሂደት ላይ ያሉ እድሎች',
      activePipelineValue: 'በሂደት ላይ ያለ ዋጋ',
      closedWonDeals: 'የተሳኩ ስምምነቶች',
      totalWonRevenue: 'ጠቅላላ የተሳካ ገቢ'
    },
    kanban: {
      noDealsInStage: 'ምንም ስምምነት የለም በ',
      back: 'ወደ ኋላ',
      lost: 'ያልተሳካ',
      advance: 'ቀጣይ ደረጃ',
      winProb: 'እድል'
    },
    table: {
      dealTitle: 'የስምምነት ርዕስ',
      customerCompany: 'ደንበኛ / ድርጅት',
      stage: 'ደረጃ',
      dealValue: 'የስምምነት ዋጋ',
      probability: 'የስኬት እድል',
      expectedClose: 'የሚጠናቀቅበት ቀን',
      actions: 'እርምጃዎች',
      emptySearch: 'ከፍለጋው ጋር የሚዛመድ ስምምነት አልተገኘም።'
    },
    stages: {
      qualification: 'ማጣሪያ',
      proposal: 'የቀረበ የዋጋ ሀሳብ',
      negotiation: 'ድርድር',
      won: 'የተሳካ ስምምነት 🎉',
      lost: 'ያልተሳካ ❌'
    },
    modal: {
      createTitle: 'አዲስ ስምምነት ወደ ሽያጭ መስመር ጨምር',
      editTitle: 'ስምምነቱን አርትዕ',
      titleLabel: 'የስምምነቱ ርዕስ',
      titlePlaceholder: 'ለምሳሌ፡ የድርጅት ፍቃድ ማሻሻያ',
      customerLabel: 'ተዛማጅ ደንበኛ / ድርጅት',
      selectCustomerOptional: 'ደንበኛ ይምረጡ (አማራጭ)',
      amountLabel: 'የስምምነት ዋጋ ($)',
      stageLabel: 'የስምምነት ደረጃ',
      probabilityLabel: 'የማሸነፍ እድል (%)',
      expectedCloseLabel: 'የሚጠናቀቅበት ቀን',
      notesLabel: 'የስምምነት ማስታወሻ',
      notesPlaceholder: 'የውል ውሎች፣ የባለድርሻ አካላት ማስታወሻዎች፣ የጊዜ ሰሌዳ...',
      createBtn: 'ስምምነት ፍጠር',
      saveBtn: 'ለውጦችን መዝግብ'
    },
    messages: {
      created: 'ስምምነቱ ወደ ሽያጭ መስመር ተጨምሯል',
      updated: 'ስምምነቱ ተሻሽሏል',
      stageUpdated: 'የስምምነቱ ደረጃ ተዘምኗል',
      deleted: 'ስምምነቱ ተሰርዟል',
      titleRequired: 'እባክዎ የስምምነቱን ርዕስ ያስገቡ',
      failedSave: 'ስምምነቱን ማስቀመጥ አልተቻለም'
    }
  },
  contacts: {
    title: 'አድራሻዎች እና ደንበኞች',
    subtitle: 'የድርጅት ደንበኞች፣ እጩዎች እና ቁልፍ ባለድርሻ አካላት ማውጫ።',
    addContact: 'አዲስ አድራሻ መዝግብ',
    kpis: {
      totalContacts: 'ጠቅላላ የተመዘገቡ አድራሻዎች',
      activeCustomers: 'ንቁ ደንበኞች',
      leads: 'እጩ ደንበኞች',
      partners: 'ስልታዊ አጋሮች'
    },
    statusFilters: {
      all: 'ሁሉም አድራሻዎች',
      customer: 'ደንበኛ',
      lead: 'እጩ ደንበኛ',
      partner: 'አጋር',
      churned: 'ያቋረጠ'
    },
    table: {
      contactOrg: 'የግለሰብ እና የድርጅት ስም',
      contactDetails: 'የመገናኛ ዝርዝር',
      status: 'ሁኔታ',
      location: 'አድራሻ / ቦታ',
      deals: 'ስምምነቶች',
      actions: 'እርምጃዎች',
      individual: 'ግለሰብ',
      emptySearch: 'ከመስፈርቱ ጋር የሚዛመድ አድራሻ አልተገኘም።'
    },
    statuses: {
      customer: 'ደንበኛ',
      lead: 'እጩ',
      partner: 'አጋር',
      churned: 'ያቋረጠ'
    },
    modal: {
      createTitle: 'አዲስ አድራሻ መዝግብ',
      editTitle: 'አድራሻውን አርትዕ',
      fullName: 'ሙሉ ስም',
      companyName: 'የድርጅት ስም',
      jobTitle: 'የስራ መደብ',
      email: 'የኢሜይል አድራሻ',
      phone: 'የስልክ ቁጥር',
      relationshipStatus: 'የግንኙነት አይነት',
      city: 'ከተማ',
      country: 'ሀገር',
      website: 'የድረ-ገጽ አድራሻ',
      acquisitionChannel: 'የመጣበት መንገድ',
      notesLabel: 'የአካውንት ማስታወሻዎች',
      notesPlaceholder: 'የአካውንት ምርጫዎች፣ የክፍያ ማስታወሻዎች፣ ታሪክ...',
      createBtn: 'አድራሻ መዝግብ',
      saveBtn: 'ለውጦችን መዝግብ'
    },
    profileModal: {
      title: 'የደንበኛ እና አድራሻ ዝርዝር',
      individualAccount: 'የግል አካውንት',
      pipelineDeals: 'የሽያጭ ስምምነቶች',
      noDeals: 'እስካሁን ከዚህ አካውንት ጋር የተገናኘ ስምምነት የለም።',
      close: 'ዝጋ'
    },
    messages: {
      created: 'አድራሻው ተመዝግቧል',
      updated: 'የአድራሻው መረጃ ተሻሽሏል',
      deleted: 'አድራሻው ተሰርዟል',
      nameRequired: 'እባክዎ የአድራሻውን ስም ያስገቡ',
      failedSave: 'አድራሻውን ማስቀመጥ አልተቻለም'
    }
  },
  activities: {
    title: 'ተግባራት እና የደንበኛ ግንኙነቶች',
    subtitle: 'የደንበኛ ስብሰባዎችን ያቅዱ፣ የሽያጭ ጥሪዎችን ይመዝግቡ እና የክትትል ስራዎችን ይከታተሉ።',
    scheduleActivity: 'አዲስ ተግባር ያቅዱ',
    kpis: {
      pendingTasks: 'በመጠባበቅ ላይ ያሉ ተግባራት',
      completedActivities: 'የተጠናቀቁ ተግባራት',
      loggedCalls: 'የተደረጉ የደንበኛ ጥሪዎች',
      totalHistory: 'ጠቅላላ የተግባራት ታሪክ'
    },
    filters: {
      all: 'ሁሉም',
      pending: 'በመጠባበቅ ላይ',
      completed: 'የተጠናቀቁ',
      searchPlaceholder: 'የተግባር ርዕስ፣ ደንበኛ ይፈልጉ...'
    },
    emptyText: 'ምንም ተግባራት አልተገኙም። የሽያጭ መስመርዎን ንቁ ለማድረግ ተግባር ያቅዱ ወይም ጥሪ ይመዝግቡ።',
    types: {
      call: 'የሽያጭ ጥሪ',
      meeting: 'የደንበኛ ስብሰባ',
      email: 'የኢሜይል ክትትል',
      task: 'የስራ ተግባር',
      follow_up: 'የውል ክትትል',
      note: 'የውስጥ ማስታወሻ'
    },
    priorities: {
      low: 'ዝቅተኛ',
      medium: 'መካከለኛ',
      high: 'ከፍተኛ ቅድሚያ'
    },
    highPriorityBadge: 'ከፍተኛ ቅድሚያ',
    due: 'የሚጠናቀቅበት',
    dealPrefix: 'ስምምነት',
    modal: {
      createTitle: 'ተግባር ያቅዱ / ጥሪ ይመዝግቡ',
      editTitle: 'ተግባሩን አርትዕ',
      activityType: 'የተግባሩ አይነት',
      priority: 'ቅድሚያ',
      activityTitle: 'የተግባሩ ርዕስ',
      titlePlaceholder: 'ለምሳሌ፡ የዋጋ ሀሳብ ግብረመልስን በተመለከተ ለደንበኛ መደወል',
      relatedCustomer: 'ተዛማጅ ደንበኛ (አማራጭ)',
      linkedDeal: 'ተዛማጅ ስምምነት (አማራጭ)',
      none: 'ምንም',
      dueDateTime: 'የሚጠናቀቅበት ቀን እና ሰዓት',
      descriptionAgenda: 'መግለጫ እና አጀንዳ',
      descriptionPlaceholder: 'የስራ ነጥቦች፣ የስብሰባ አጀንዳ፣ ዋና ዋና ግኝቶች...',
      scheduleBtn: 'ተግባሩን መዝግብ',
      saveBtn: 'ለውጦችን መዝግብ'
    },
    messages: {
      scheduled: 'ተግባሩ ተመዝግቧል',
      updated: 'ተግባሩ ተሻሽሏል',
      statusUpdated: 'የተግባሩ ሁኔታ ተዘምኗል',
      removed: 'ተግባሩ ተሰርዟል',
      titleRequired: 'እባክዎ የተግባሩን ርዕስ ያስገቡ',
      failedSave: 'ተግባሩን ማስቀመጥ አልተቻለም'
    }
  },
  leadForms: {
    title: 'የእጩ ደንበኛ ቅጾች እና የመግቢያ መንገዶች',
    subtitle: 'ለኤጀንሲ እና ማህበራዊ ሚዲያ ዘመቻዎች ደረጃ በደረጃ የሚሞሉ ቅጾችን እንዲሁም ለድረ-ገጾች የሚካተቱ ቅጾችን ይገንቡ።',
    createForm: 'የእጩ ቅጽ ፍጠር',
    kpis: {
      activeForms: 'ንቁ የደንበኛ ቅጾች',
      totalSubmissions: 'ጠቅላላ የቀረቡ ማመልከቻዎች',
      totalViews: 'ጠቅላላ የገጽ እይታዎች',
      overallConversion: 'አጠቃላይ የለውጥ ምጣኔ'
    },
    filterChannels: 'ሁሉም መንገዶች',
    filterPlaceholder: 'ቅጾችን ይፈልጉ...',
    emptyTitle: 'ምንም የእጩ ደንበኛ ቅጾች አልተገኙም',
    emptyText: 'በተለያዩ መንገዶች እጩ ደንበኞችን በራስ-ሰር ለመሰብሰብ ደረጃ በደረጃ የሚሞሉ ወይም በድረ-ገጽ የሚካተቱ ቅጾችን ይፍጠሩ።',
    createFirst: 'የመጀመሪያ ቅጽ ይፍጠሩ',
    card: {
      wizardType: 'ደረጃ በደረጃ የሚሞላ',
      embedType: 'የሚካተት ቅጽ',
      views: 'እይታዎች',
      submissions: 'የቀረቡ',
      conversion: 'የለውጥ ምጣኔ',
      shareLink: 'የቅጹን አገናኝ አጋራ',
      embedCode: 'የማካተቻ ኮድ',
      testLive: 'ቅጹን በቅጽበት ይሞክሩ'
    },
    modal: {
      createTitle: 'አዲስ የእጩ ደንበኛ ቅጽ ፍጠር',
      editTitle: 'የእጩ ደንበኛ ቅጹን አርትዕ',
      tab1: '1. የመግቢያ መንገድ እና ቅርጸት',
      tab2: '2. ተለዋዋጭ ጥያቄዎች',
      tab3: '3. የምስጋና መልእክት እና ቀጣይ እርምጃዎች',
      internalTitle: 'የውስጥ የቅጽ ርዕስ',
      internalPlaceholder: 'ለምሳሌ፡ የኢንስታግራም ማስታወቂያ ቅጽ',
      channelSource: 'የእጩ ደንበኛ መግቢያ መንገድ',
      formType: 'የቅጹ የአሰራር አይነት',
      wizardTitle: 'ተስተጋባሪ ደረጃ በደረጃ የሚሞላ ቅጽ',
      wizardDesc: 'ማራኪ የሙሉ ስክሪን ተሞክሮ። ጥያቄዎች በደረጃ በደረጃ ይመጣሉ። ለማህበራዊ ሚዲያ እና ኤጀንሲዎች ተስማሚ ነው።',
      embedTitle: 'መደበኛ / በድረ-ገጽ የሚካተት ቅጽ',
      embedDesc: 'የሚቀዳ የማካተቻ ኮድ (iframe) ያለው መደበኛ ቅጽ። በቀጥታ በሌሎች ድረ-ገጾች ላይ ማስቀመጥ ይቻላል።',
      candidateHeading: 'ለደንበኛው የሚታይ ርዕስ',
      headingPlaceholder: 'ስለ ፕሮጀክትዎ ይንገሩን',
      defaultEstValue: 'ነባሪ የተገመተ ዋጋ ($)',
      introSubtitle: 'መግቢያ ንዑስ ርዕስ / መመሪያዎች',
      introPlaceholder: 'አጭር የእንኳን ደህና መጡ መልእክት ወይም መመሪያዎችን ያስገቡ...',
      basicFieldsNote: 'መሰረታዊ የመገናኛ መረጃዎች (ሙሉ ስም፣ ኢሜይል፣ ስልክ፣ ድርጅት) በራስ-ሰር ይሰበሰባሉ። ተጨማሪ ጥያቄዎችን ከታች ያክሉ።',
      addQuestionLabel: 'ጥያቄ ያክሉ:',
      requiredCheckbox: 'አስገዳጅ',
      questionPrompt: 'የጥያቄው ጽሁፍ',
      promptPlaceholder: 'ለምሳሌ፡ ፕሮጀክቱን ለማጠናቀቅ ምን ያህል ጊዜ ይወስዳል?',
      hintPlaceholder: 'ፍንጭ / አጋዥ ጽሁፍ',
      choiceOptions: 'የምርጫ አማራጮች',
      addOption: 'አማራጭ ጨምር',
      noCustomQuestions: 'እስካሁን ምንም ተጨማሪ ጥያቄዎች አልተጨመሩም።',
      thankYouTitle: 'የምስጋና ርዕስ',
      thankYouMessage: 'የምስጋና መልእክት',
      redirectUrl: 'ቀጥታ የማስተላለፊያ ድረ-ገጽ (አማራጭ)',
      activeCheckbox: 'ቅጹ ንቁ ነው እና አዳዲስ ጥያቄዎችን ይቀበላል',
      prevBtn: '← ወደ ኋላ',
      nextBtn: 'ቀጣይ ደረጃ →',
      publishBtn: 'ቅጹን ይፋ አድርግ',
      saveBtn: 'ለውጦችን መዝግብ'
    },
    embedModal: {
      title: 'የድረ-ገጽ ማካተቻ ኮድ',
      text: 'ይህን የኤችቲኤምኤል (HTML) ኮድ በድረ-ገጽዎ ላይ በማስቀመጥ ቅጹን በቀላሉ ማካተት ይችላሉ።',
      copyBtn: 'የማካተቻ ኮዱን ቅዳ',
      close: 'ዝጋ'
    },
    questionTypes: {
      text: 'አጭር ጽሁፍ',
      textarea: 'ረጅም አንቀጽ',
      number: 'ቁጥር',
      select: 'ተቆልቋይ ምርጫ',
      radio: 'አንድ ምርጫ (ሬዲዮ)',
      checkbox: 'ባለብዙ ምርጫ (ቼክቦክስ)',
      date: 'ቀን'
    },
    messages: {
      created: 'አዲስ የእጩ ደንበኛ ቅጽ ተፈጥሯል',
      updated: 'የእጩ ደንበኛ ቅጹ ተሻሽሏል',
      deleted: 'የእጩ ደንበኛ ቅጹ ተሰርዟል',
      linkCopied: 'የቅጹ አገናኝ ተቀድቷል!',
      embedCopied: 'የኤችቲኤምኤል ማካተቻ ኮድ ተቀድቷል!',
      titleRequired: 'እባክዎ የቅጹን ርዕስ ያስገቡ',
      failedSave: 'ቅጹን ማስቀመጥ አልተቻለም'
    }
  },
  publicLead: {
    welcome: 'እንኳን ደህና መጡ',
    getStarted: 'ጀምር',
    next: 'ቀጣይ',
    back: 'ወደ ኋላ',
    submit: 'ጥያቄውን ላክ',
    name: 'ሙሉ ስም',
    company: 'ድርጅት / ተቋም',
    email: 'የኢሜይል አድራሻ',
    phone: 'የስልክ ቁጥር',
    notes: 'ተጨማሪ ማስታወሻዎች እና ዝርዝሮች',
    successTitle: 'ጥያቄዎ ደርሶናል!',
    successMessage: 'ስላነጋገሩን እናመሰግናለን። ፍላጎትዎን መርምረን በአፋጣኝ ምላሽ እንሰጣለን።',
    closeWindow: 'ይህን ገጽ መዝጋት ይችላሉ።'
  }
}
