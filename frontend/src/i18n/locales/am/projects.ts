export default {
  common: {
    title: 'ፕሮጀክቶች',
    subtitle: 'የፕሮጀክት ፖርትፎሊዮ፣ ስራዎች፣ የስራ ሰዓት ምዝገባ እና ምዕራፎች።',
    save: 'አስቀምጥ',
    cancel: 'ሰርዝ',
    delete: 'አጥፋ',
    edit: 'አርትዕ',
    close: 'ዝጋ',
    loading: 'በመጫን ላይ…',
    noData: 'እስካሁን ምንም ፕሮጀክት አልተፈጠረም',
    noDataSubtitle: 'የደንበኛ ወይም የውስጥ ፕሮጀክት በመፍጠር ይጀምሩ።',
    manager: 'አስተዳዳሪ',
    budget: 'በጀት',
    startDate: 'የመጀመሪያ ቀን',
    dueDate: 'የማጠናቀቂያ ቀን',
    selectProjectToView: 'ዝርዝሩን እና የስራ ዝርዝሩን ለማየት ከግራ በኩል ፕሮጀክት ይምረጡ።',
    statuses: {
      planned: 'የታቀደ / ዝግጅት',
      in_progress: 'በሂደት ላይ / ንቁ',
      on_hold: 'የቆመ',
      completed: 'የተጠናቀቀ 🎉',
      cancelled: 'የተሰረዘ'
    },
    priorities: {
      low: 'ዝቅተኛ',
      medium: 'መካከለኛ',
      high: 'ከፍተኛ',
      urgent: 'አስቸኳይ'
    }
  },
  overview: {
    title: 'የፕሮጀክቶች ፖርትፎሊዮ',
    subtitle: 'አቅርቦቶችን ያቅዱ፣ የግብዓት ምደባን ይከታተሉ፣ ምዕራፎችን ያስተዳድሩ እና የፕሮጀክት በጀትን ይቆጣጠሩ።',
    buttons: {
      kanbanBoard: 'የካንባን ሰሌዳ',
      newProject: 'አዲስ ፕሮጀክት'
    },
    kpis: {
      activeProjects: 'ንቁ ፕሮጀክቶች',
      totalPortfolioBudget: 'አጠቃላይ የፖርትፎሊዮ በጀት',
      taskCompletionRate: 'የስራዎች ማጠናቀቂያ ምጣኔ',
      loggedWorkHours: 'የተመዘገቡ የስራ ሰዓታት'
    },
    filters: {
      all: 'ሁሉም',
      in_progress: 'በሂደት ላይ',
      planned: 'የታቀደ',
      on_hold: 'የቆመ',
      completed: 'የተጠናቀቀ',
      cancelled: 'የተሰረዘ',
      searchPlaceholder: 'በኮድ፣ ስም፣ ደንበኛ ይፈልጉ...'
    },
    card: {
      internalProject: 'የውስጥ ፕሮጀክት',
      taskProgress: 'የስራ ሂደት',
      tasksCount: 'ስራዎች'
    },
    emptyState: {
      title: 'ምንም ፕሮጀክት አልተገኘም',
      text: 'ስራዎችን ለማደራጀት እና ሰዓታትን ለመከታተል የደንበኛ ወይም የውስጥ ስራዎችን ይፍጠሩ።',
      createFirst: 'የመጀመሪያ ፕሮጀክት ፍጠር'
    },
    modal: {
      createTitle: 'አዲስ ፕሮጀክት መዝግብ',
      editTitle: 'ፕሮጀክት አርትዕ',
      projectName: 'የፕሮጀክት ስም',
      namePlaceholder: 'ለምሳሌ፡ የኢአርፒ ክላውድ ዝውውር እና የሞባይል መተግበሪያ',
      projectCode: 'የፕሮጀክት ኮድ',
      codePlaceholder: 'ራስ-ሰር (ለምሳሌ PRJ-001)',
      clientLabel: 'ደንበኛ / የደንበኛ አካውንት (አማራጭ)',
      internalInitiative: 'የውስጥ ተነሳሽነት (ምንም)',
      budgetLabel: 'የፕሮጀክት በጀት (ብር)',
      statusLabel: 'የፕሮጀክት ሁኔታ',
      priorityLabel: 'የቅድሚያ ደረጃ',
      deadlineLabel: 'የማጠናቀቂያ ቀን',
      scopeLabel: 'የፕሮጀክት ወሰን እና ማብራሪያ',
      scopePlaceholder: 'ዓላማዎች፣ ዋና ዋና ምዕራፎች፣ የስነ-ህንፃ ማስታወሻዎች...',
      createBtn: 'ፕሮጀክት ጀምር',
      saveBtn: 'ለውጦችን አስቀምጥ'
    },
    detailModal: {
      title: 'የፕሮጀክት አጠቃላይ እይታ እና የስራዎች ማህደር',
      totalBudget: 'አጠቃላይ በጀት',
      projectTasks: 'የፕሮጀክት ስራዎች',
      openKanban: 'ሙሉውን የካንባን ሰሌዳ ክፈት →',
      unassigned: 'ያልተመደበ',
      noTasks: 'በዚህ ፕሮጀክት ስር እስካሁን የተፈጠረ ስራ የለም።',
      close: 'ዝጋ'
    },
    messages: {
      created: 'አዲስ ፕሮጀክት ተጀምሯል',
      updated: 'ፕሮጀክት ተሻሽሏል',
      deleted: 'ፕሮጀክት ተሰርዟል',
      nameRequired: 'እባክዎ የፕሮጀክት ስም ያስገቡ',
      failedSave: 'ፕሮጀክትን ማስቀመጥ አልተሳካም'
    }
  },
  tasks: {
    title: 'የስራ ሰሌዳ እና ካንባን',
    subtitle: 'የስራ ፍሰትን በዝርዝር ስራዎች፣ ንቁ ስፕሪንቶች፣ ግምገማዎች እና በተጠናቀቁ ስራዎች ይከታተሉ።',
    viewKanban: 'ካንባን',
    viewTable: 'ሰንጠረዥ',
    createTask: 'ስራ ፍጠር',
    kpis: {
      backlog: 'የሚሰሩ / ወደፊት የሚሰሩ',
      inProgress: 'በሂደት ላይ ያሉ ስራዎች',
      completed: 'የተጠናቀቁ ስራዎች',
      totalHoursLogged: 'አጠቃላይ የተመዘገቡ ሰዓታት'
    },
    filterAllProjects: 'ሁሉም ፕሮጀክቶች',
    searchPlaceholder: 'የስራ ርዕስ፣ ማብራሪያ ይፈልጉ...'
    ,
    statuses: {
      todo: 'የሚሰራ',
      in_progress: 'በሂደት ላይ',
      review: 'በግምገማ ላይ',
      done: 'ተጠናቋል 🎉'
    },
    kanban: {
      logHoursTitle: 'ሰዓታት መዝግብ',
      back: 'ወደ ኋላ',
      next: 'ቀጣይ',
      noTasksInStatus: 'ምንም ስራ የለም በ'
    },
    table: {
      taskProject: 'ስራ እና ፕሮጀክት',
      status: 'ሁኔታ',
      priority: 'ቅድሚያ',
      loggedEstHours: 'የተመዘገበ / የሚገመት ሰዓት',
      targetDueDate: 'የማጠናቀቂያ ቀን',
      actions: 'እርምጃዎች',
      logTimeBtn: 'ሰዓት መዝግብ',
      emptySearch: 'ከፍለጋዎ ጋር የሚዛመድ ስራ አልተገኘም።'
    },
    modal: {
      createTitle: 'ስራ ወደ ፕሮጀክት አክል',
      editTitle: 'ስራ አርትዕ',
      projectLabel: 'ፕሮጀክት',
      titleLabel: 'የስራ ርዕስ',
      titlePlaceholder: 'ለምሳሌ፡ የOAuth2 አገልግሎት ማዘጋጀት',
      initialStatus: 'የመጀመሪያ ሁኔታ',
      priorityLabel: 'ቅድሚያ',
      estHoursLabel: 'የሚገመት ሰዓታት',
      dueDateLabel: 'የማጠናቀቂያ ቀን',
      descriptionLabel: 'ማብራሪያ እና የማረጋገጫ መስፈርቶች',
      descriptionPlaceholder: 'የአተገባበር መስፈርቶች፣ የፍተሻ ዝርዝር፣ ጥገኝነቶች...',
      createBtn: 'ስራ ፍጠር',
      saveBtn: 'ስራ አስቀምጥ'
    },
    logTimeModal: {
      title: 'በስራው ላይ የተሰራ ሰዓት መዝግብ',
      hoursSpent: 'የፈጀው ሰዓት',
      workDate: 'የተሰራበት ቀን',
      workDescription: 'የስራው ማብራሪያ',
      descriptionPlaceholder: 'የተከናወነው ስራ ማጠቃለያ፣ ለውጦች ወይም ማስተካከያዎች...',
      billableCheckbox: 'ለደንበኛ የሚከፈልበት ስራ',
      recordBtn: 'የስራ ሰዓት ሎግ መዝግብ'
    },
    messages: {
      created: 'አዲስ ስራ ወደ ፕሮጀክቱ ታክሏል',
      updated: 'ስራ ተሻሽሏል',
      statusUpdated: 'የስራው ሁኔታ ተሻሽሏል',
      deleted: 'ስራ ተሰርዟል',
      timeLogged: 'የስራ ሰዓታት ተመዝግበዋል! ⏱️',
      titleRequired: 'እባክዎ የስራ ርዕስ ያስገቡ',
      projectRequired: 'እባክዎ ለዚህ ስራ ፕሮጀክት ይምረጡ',
      failedSave: 'ስራ ማስቀመጥ አልተሳካም',
      failedLogTime: 'ሰዓት መመዝገብ አልተሳካም'
    }
  },
  timeLogs: {
    title: 'የስራ ሰዓት ምዝገባ እና ሎጎች',
    subtitle: 'የሰው ኃይል ወጪን፣ ለደንበኛ የሚከፈሉ አማካሪ ሰዓታትን እና የልማት ሰዓት ሉሆችን ይከታተሉ።',
    logWorkHours: 'የስራ ሰዓት መዝግብ',
    kpis: {
      totalHoursRecorded: 'አጠቃላይ የተመዘገቡ ሰዓታት',
      billableHours: 'የሚከፈልባቸው ሰዓታት',
      billableEfficiency: 'የክፍያ ቅልጥፍና',
      totalLogEntries: 'አጠቃላይ የሎግ ምዝገባዎች'
    },
    searchPlaceholder: 'ሎጎችን በማብራሪያ፣ አባል፣ ስራ ይፈልጉ...',
    table: {
      projectTask: 'ፕሮጀክት እና ስራ',
      teamMember: 'የቡድን አባል',
      workDescription: 'የስራ ማብራሪያ',
      workDate: 'የተሰራበት ቀን',
      hoursLogged: 'የተመዘገቡ ሰዓታት',
      billable: 'የሚከፈልበት',
      actions: 'እርምጃዎች',
      generalProjectWork: 'አጠቃላይ የፕሮጀክት ስራ',
      currentUser: 'የአሁኑ ተጠቃሚ',
      billableBadge: 'የሚከፈልበት',
      internalBadge: 'የውስጥ',
      emptyTitle: 'እስካሁን የተመዘገበ የስራ ሰዓት የለም።'
    },
    modal: {
      createTitle: 'የስራ ሰዓት ሎግ መዝግብ',
      editTitle: 'የሰዓት ሎግ አርትዕ',
      projectLabel: 'ፕሮጀክት',
      associatedTask: 'ተዛማጅ ስራ (አማራጭ)',
      noTaskOption: 'አጠቃላይ የፕሮጀክት ስራ (ያለ ተለየ ስራ)',
      hoursSpent: 'የፈጀው ሰዓት',
      workDate: 'የተሰራበት ቀን',
      workDelivered: 'የተከናወነ ስራ እና ማጠቃለያ',
      deliveredPlaceholder: 'የተጠናቀቁ ስራዎችን፣ የምዕራፍ ሂደቶችን ወይም የስብሰባ ማስታወሻዎችን ያብራሩ...',
      billableCheckbox: 'ለደንበኛ የሚከፈልበት ስራ',
      createBtn: 'የሰዓት ሎግ መዝግብ',
      saveBtn: 'ለውጦችን አስቀምጥ'
    },
    messages: {
      created: 'የስራ ሰዓት ተመዝግቧል',
      updated: 'የሰዓት ሎግ ተሻሽሏል',
      deleted: 'የሰዓት ሎግ ተሰርዟል',
      projectRequired: 'እባክዎ ፕሮጀክት ይምረጡ',
      hoursRequired: 'እባክዎ ትክክለኛ የፈጀ ሰዓት ያስገቡ',
      failedSave: 'የሰዓት ሎግ ማስቀመጥ አልተሳካም'
    }
  },
  milestones: {
    title: 'የፕሮጀክት ምዕራፎች እና አቅርቦቶች',
    subtitle: 'የእድገት መርሃ-ግብር ልቀቶችን፣ የደንበኛ ማረጋገጫ በሮችን እና የማስረከቢያ ቃሎችን ያቅዱ።',
    addMilestone: 'ምዕራፍ አክል',
    kpis: {
      totalMilestones: 'አጠቃላይ ምዕራፎች',
      inProgressTargets: 'በሂደት ላይ ያሉ ዒላማዎች',
      achievedDeliverables: 'የተሳኩ አቅርቦቶች',
      delayedBlocked: 'የዘገዩ / የታገዱ'
    },
    searchPlaceholder: 'ምዕራፎችን ይፈልጉ...',
    statuses: {
      pending: 'በመጠባበቅ ላይ',
      in_progress: 'በሂደት ላይ',
      achieved: 'የተሳካ 🎉',
      delayed: 'የዘገየ'
    },
    card: {
      target: 'ዒላማ'
    },
    emptyState: {
      title: 'ምንም ምዕራፎች አልተገኙም',
      text: 'ቡድንዎ በአንድ አቅጣጫ እንዲጓዝ ቁልፍ የፕሮጀክት ዒላማ ቀናትን እና የልቀት ምዕራፎችን ያዘጋጁ።',
      scheduleFirst: 'ምዕራፍ ያቅዱ'
    },
    modal: {
      createTitle: 'የፕሮጀክት ምዕራፍ ያቅዱ',
      editTitle: 'ምዕራፍ አርትዕ',
      projectLabel: 'ፕሮጀክት',
      milestoneTitle: 'የምዕራፍ ርዕስ',
      titlePlaceholder: 'ለምሳሌ፡ የቤታ ስሪት 1.0 መዘርጋት',
      statusLabel: 'ሁኔታ',
      dueDateLabel: 'የማጠናቀቂያ ቀን',
      scopeLabel: 'የምዕራፉ ወሰን እና አቅርቦቶች',
      scopePlaceholder: 'ቁልፍ አቅርቦቶች፣ የማረጋገጫ መስፈርቶች...',
      createBtn: 'ምዕራፍ መዝግብ',
      saveBtn: 'ለውጦችን አስቀምጥ'
    },
    messages: {
      scheduled: 'ምዕራፍ ተመዝግቧል',
      updated: 'ምዕራፍ ተሻሽሏል',
      deleted: 'ምዕራፍ ተሰርዟል',
      titleRequired: 'እባክዎ የምዕራፍ ርዕስ ያስገቡ',
      projectRequired: 'እባክዎ ፕሮጀክት ይምረጡ',
      failedSave: 'ምዕራፍ ማስቀመጥ አልተሳካም'
    }
  }
}
