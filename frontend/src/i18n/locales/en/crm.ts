export default {
  common: {
    title: 'CRM',
    subtitle: 'Leads, deals, contacts, activities, and inbound channels.',
    currency: 'USD',
    save: 'Save',
    cancel: 'Cancel',
    delete: 'Delete',
    edit: 'Edit',
    close: 'Close',
    loading: 'Loading…',
    noData: 'No records found.'
  },
  dashboard: {
    title: 'CRM Overview & Pipeline',
    subtitle: 'Track sales performance, lead conversions, deal pipelines, and client engagement.',
    kpis: {
      activePipelineValue: 'Active Pipeline Value',
      closedWonRevenue: 'Closed Won Revenue',
      winRate: 'Win Rate',
      activeLeads: 'Active Leads'
    },
    stageDistribution: 'Pipeline Stage Distribution',
    openKanban: 'Open Kanban Board',
    opportunities: 'Opportunities',
    topDeals: 'Top Pipeline Opportunities',
    viewAllDeals: 'View All Deals →',
    winProbability: 'win probability',
    noDeals: 'No active deals in the pipeline yet.',
    recentActivities: 'Recent Interactions & Tasks',
    viewAllActivities: 'View All →',
    noActivities: 'No activities logged yet.',
    stages: {
      qualification: 'Qualification',
      proposal: 'Proposal Sent',
      negotiation: 'Negotiation',
      won: 'Closed Won',
      lost: 'Closed Lost'
    },
    buttons: {
      newLead: 'New Lead',
      newDeal: 'New Deal'
    }
  },
  leads: {
    title: 'Leads & Inbound Inquiries',
    subtitle: 'Capture inbound leads, qualify opportunities, review form responses, and convert to sales pipeline.',
    captureNewLead: 'Capture New Lead',
    kpis: {
      totalInbound: 'Total Inbound Leads',
      qualified: 'Qualified Prospects',
      converted: 'Converted to Pipeline',
      estValue: 'Total Inbound Est. Value'
    },
    statusFilters: {
      all: 'All',
      new: 'New',
      contacted: 'Contacted',
      qualified: 'Qualified',
      unqualified: 'Unqualified',
      converted: 'Converted'
    },
    table: {
      contactCompany: 'Lead Contact & Company',
      status: 'Status',
      priority: 'Priority',
      channel: 'Channel / Source',
      estValue: 'Est. Value (Budget)',
      actions: 'Actions',
      formResponsesBadge: 'Form Responses',
      noContactInfo: 'No contact info',
      convert: 'Convert',
      emptyTitle: 'No leads found',
      emptyText: 'Capture prospect inquiries or share your lead intake wizard to build your sales funnel.',
      captureFirst: 'Capture First Lead'
    },
    statuses: {
      new: 'New Lead',
      contacted: 'Contacted',
      qualified: 'Qualified',
      unqualified: 'Unqualified',
      converted: 'Converted 🎉'
    },
    priorities: {
      urgent: 'Urgent',
      high: 'High',
      medium: 'Medium',
      low: 'Low'
    },
    sources: {
      website: 'Website / Organic',
      agency: 'Agency Referral',
      social_media: 'Social Media',
      google_ads: 'Google / Paid Ads',
      referral: 'Client Referral',
      outreach: 'Cold Outreach',
      event: 'Event / Trade Show',
      other: 'Other'
    },
    detailsModal: {
      title: 'Lead Inquiry Details',
      estBudget: 'Est. Value / Budget',
      channel: 'Channel',
      email: 'Email',
      phone: 'Phone',
      questionnaireResponses: 'Submitted Questionnaire Responses',
      notes: 'Additional Inquiry Notes',
      individualProspect: 'Individual Prospect',
      convertToDeal: 'Convert to Pipeline Deal'
    },
    formModal: {
      createTitle: 'Capture New Lead',
      editTitle: 'Edit Lead Details',
      contactName: 'Contact Name',
      company: 'Company / Organization',
      jobTitle: 'Job Title',
      email: 'Email Address',
      phone: 'Phone Number',
      source: 'Lead Source',
      status: 'Status',
      priority: 'Priority',
      estValue: 'Est. Value / Budget ($)',
      notesLabel: 'Notes & Context',
      notesPlaceholder: 'Key discussion points, customer requirements, timeline...',
      saveBtn: 'Save Lead',
      updateBtn: 'Update Lead'
    },
    convertModal: {
      title: 'Convert Lead to Customer & Pipeline Deal',
      leadToBeConverted: 'Lead to be converted',
      conversionNotice: 'Converting will automatically register an active Customer and create an opportunity in your Deals pipeline.',
      dealTitle: 'Deal Title',
      dealTitlePlaceholder: 'e.g. Enterprise License',
      dealAmount: 'Deal Amount ($)',
      initialStage: 'Initial Pipeline Stage',
      confirmBtn: 'Confirm Conversion'
    },
    messages: {
      created: 'New lead captured',
      updated: 'Lead updated successfully',
      converted: 'Lead converted to Customer and Deal in pipeline! 🎉',
      deleted: 'Lead deleted',
      nameRequired: 'Please enter the lead name',
      failedSave: 'Failed to save lead',
      failedConvert: 'Failed to convert lead'
    }
  },
  deals: {
    title: 'Deals & Sales Pipeline',
    subtitle: 'Track and advance high-value commercial opportunities from qualification to close.',
    newDeal: 'New Deal',
    viewKanban: 'Kanban',
    viewTable: 'Table',
    searchPlaceholder: 'Filter deals by title, company, customer...',
    opportunitiesCount: 'Opportunities',
    kpis: {
      activeOpportunities: 'Active Opportunities',
      activePipelineValue: 'Active Pipeline Value',
      closedWonDeals: 'Closed Won Deals',
      totalWonRevenue: 'Total Won Revenue'
    },
    kanban: {
      noDealsInStage: 'No deals in',
      back: 'Back',
      lost: 'Lost',
      advance: 'Advance',
      winProb: 'prob'
    },
    table: {
      dealTitle: 'Deal Title',
      customerCompany: 'Customer / Company',
      stage: 'Stage',
      dealValue: 'Deal Value',
      probability: 'Probability',
      expectedClose: 'Expected Close',
      actions: 'Actions',
      emptySearch: 'No deals found matching your search.'
    },
    stages: {
      qualification: 'Qualification',
      proposal: 'Proposal Sent',
      negotiation: 'Negotiation',
      won: 'Closed Won 🎉',
      lost: 'Closed Lost ❌'
    },
    modal: {
      createTitle: 'Add Deal to Pipeline',
      editTitle: 'Edit Deal',
      titleLabel: 'Deal Title',
      titlePlaceholder: 'e.g. Enterprise License Expansion',
      customerLabel: 'Associated Customer / Company',
      selectCustomerOptional: 'Select Customer (Optional)',
      amountLabel: 'Deal Amount ($)',
      stageLabel: 'Pipeline Stage',
      probabilityLabel: 'Win Probability (%)',
      expectedCloseLabel: 'Expected Close Date',
      notesLabel: 'Deal Notes',
      notesPlaceholder: 'Contract terms, stakeholder notes, timeline...',
      createBtn: 'Create Deal',
      saveBtn: 'Save Changes'
    },
    messages: {
      created: 'Deal added to pipeline',
      updated: 'Deal updated',
      stageUpdated: 'Deal stage updated',
      deleted: 'Deal removed',
      titleRequired: 'Please enter a deal title',
      failedSave: 'Failed to save deal'
    }
  },
  contacts: {
    title: 'Contacts & Accounts',
    subtitle: 'Directory of enterprise accounts, prospective clients, and key stakeholders.',
    addContact: 'Add Contact',
    kpis: {
      totalContacts: 'Total Directory Contacts',
      activeCustomers: 'Active Customers',
      leads: 'Prospects / Leads',
      partners: 'Strategic Partners'
    },
    statusFilters: {
      all: 'All Contacts',
      customer: 'Customer',
      lead: 'Lead',
      partner: 'Partner',
      churned: 'Churned'
    },
    table: {
      contactOrg: 'Contact & Organization',
      contactDetails: 'Contact Details',
      status: 'Status',
      location: 'Location',
      deals: 'Deals',
      actions: 'Actions',
      individual: 'Individual',
      emptySearch: 'No contacts found matching your criteria.'
    },
    statuses: {
      customer: 'Customer',
      lead: 'Lead',
      partner: 'Partner',
      churned: 'Churned'
    },
    modal: {
      createTitle: 'Add New Contact',
      editTitle: 'Edit Contact',
      fullName: 'Full Name',
      companyName: 'Company Name',
      jobTitle: 'Job Title',
      email: 'Email Address',
      phone: 'Phone Number',
      relationshipStatus: 'Relationship Status',
      city: 'City',
      country: 'Country',
      website: 'Website URL',
      acquisitionChannel: 'Acquisition Channel',
      notesLabel: 'Account Notes',
      notesPlaceholder: 'Account preferences, billing notes, background...',
      createBtn: 'Create Contact',
      saveBtn: 'Save Changes'
    },
    profileModal: {
      title: 'Account & Contact Profile',
      individualAccount: 'Individual Account',
      pipelineDeals: 'Pipeline Deals',
      noDeals: 'No deals linked to this account yet.',
      close: 'Close'
    },
    messages: {
      created: 'Contact created',
      updated: 'Contact updated',
      deleted: 'Contact deleted',
      nameRequired: 'Please provide a contact name',
      failedSave: 'Failed to save contact'
    }
  },
  activities: {
    title: 'Activities & Tasks',
    subtitle: 'Plan client meetings, log sales calls, and track deal follow-up actions.',
    scheduleActivity: 'Schedule Activity',
    kpis: {
      pendingTasks: 'Pending Tasks & Follow-ups',
      completedActivities: 'Completed Activities',
      loggedCalls: 'Logged Client Calls',
      totalHistory: 'Total Activity History'
    },
    filters: {
      all: 'All',
      pending: 'Pending',
      completed: 'Completed',
      searchPlaceholder: 'Search activity title, customer...'
    },
    emptyText: 'No activities found. Schedule a task or log a call to keep your pipeline active.',
    types: {
      call: 'Sales Call',
      meeting: 'Client Meeting',
      email: 'Email Follow-up',
      task: 'Action Task',
      follow_up: 'Contract Follow-up',
      note: 'Internal Note'
    },
    priorities: {
      low: 'Low',
      medium: 'Medium',
      high: 'High Priority'
    },
    highPriorityBadge: 'High Priority',
    due: 'Due',
    dealPrefix: 'Deal',
    modal: {
      createTitle: 'Schedule Activity / Log Task',
      editTitle: 'Edit Activity',
      activityType: 'Activity Type',
      priority: 'Priority',
      activityTitle: 'Activity Title',
      titlePlaceholder: 'e.g. Call client regarding proposal feedback',
      relatedCustomer: 'Related Customer (Optional)',
      linkedDeal: 'Linked Deal (Optional)',
      none: 'None',
      dueDateTime: 'Due Date & Time',
      descriptionAgenda: 'Description & Agenda',
      descriptionPlaceholder: 'Action items, meeting agenda, key takeaways...',
      scheduleBtn: 'Schedule Activity',
      saveBtn: 'Save Changes'
    },
    messages: {
      scheduled: 'Activity scheduled',
      updated: 'Activity updated',
      statusUpdated: 'Task status updated',
      removed: 'Activity removed',
      titleRequired: 'Please enter a title for the activity',
      failedSave: 'Failed to save activity'
    }
  },
  leadForms: {
    title: 'Lead Forms & Inbound Channels',
    subtitle: 'Build interactive step-by-step lead wizards (for Agency & Social campaigns) and embeddable forms for websites.',
    createForm: 'Create Lead Form',
    kpis: {
      activeForms: 'Active Lead Forms',
      totalSubmissions: 'Total Inbound Submissions',
      totalViews: 'Total Page Views',
      overallConversion: 'Overall Conversion Rate'
    },
    filterChannels: 'All Channels',
    filterPlaceholder: 'Filter forms...',
    emptyTitle: 'No lead intake forms found',
    emptyText: 'Create customized step-by-step lead wizards or website embed forms to automate prospect capture across multiple channels.',
    createFirst: 'Create First Lead Form',
    card: {
      wizardType: 'Step-by-Step Wizard',
      embedType: 'Embed Form',
      views: 'Views',
      submissions: 'Submissions',
      conversion: 'Conversion',
      shareLink: 'Share Form Link',
      embedCode: 'Embed Code',
      testLive: 'Test Live Form'
    },
    modal: {
      createTitle: 'Create Lead Capture Form',
      editTitle: 'Edit Lead Intake Form',
      tab1: '1. Channel & Form Format',
      tab2: '2. Dynamic Questions',
      tab3: '3. Thank You & Next Steps',
      internalTitle: 'Internal Form Title',
      internalPlaceholder: 'e.g. Instagram Ad Campaign Intake',
      channelSource: 'Lead Channel / Source',
      formType: 'Form Experience Type',
      wizardTitle: 'Interactive Step-by-Step Wizard',
      wizardDesc: 'Engaging full-screen experience. Questions appear one-by-one with animated progress. Ideal for social media & agencies.',
      embedTitle: 'Standard / Embeddable Form',
      embedDesc: 'Classic structured form with copyable iframe embed code. Can be placed directly into external websites & landing pages.',
      candidateHeading: 'Candidate Heading / Title',
      headingPlaceholder: 'Tell us about your project',
      defaultEstValue: 'Default Est. Value ($)',
      introSubtitle: 'Introductory Subtitle / Instructions',
      introPlaceholder: 'Provide a brief welcome message or instructions...',
      basicFieldsNote: 'Basic contact fields (Full Name, Email, Phone, Company) are captured automatically. Add customized questions below.',
      addQuestionLabel: 'Add Question:',
      requiredCheckbox: 'Required',
      questionPrompt: 'Question Prompt',
      promptPlaceholder: 'e.g. What is your estimated timeline?',
      hintPlaceholder: 'Placeholder / Hint',
      choiceOptions: 'Choice Options',
      addOption: 'Add Option',
      noCustomQuestions: 'No custom questions added yet.',
      thankYouTitle: 'Thank You Title',
      thankYouMessage: 'Thank You Message',
      redirectUrl: 'Auto-Redirect URL (Optional)',
      activeCheckbox: 'Form is active and accepting new lead submissions',
      prevBtn: '← Previous',
      nextBtn: 'Next Step →',
      publishBtn: 'Publish Lead Form',
      saveBtn: 'Save Changes'
    },
    embedModal: {
      title: 'Website Embed Code',
      text: 'Paste the following HTML code snippet into your external website or landing page to embed this lead capture form seamlessly.',
      copyBtn: 'Copy HTML Snippet',
      close: 'Close'
    },
    questionTypes: {
      text: 'Short Text',
      textarea: 'Long Paragraph',
      number: 'Numeric Value',
      select: 'Dropdown Select',
      radio: 'Single Choice (Radio)',
      checkbox: 'Multi-Select (Checkbox)',
      date: 'Date'
    },
    messages: {
      created: 'New lead capture form created',
      updated: 'Lead form updated',
      deleted: 'Lead form deleted',
      linkCopied: 'Public lead form link copied to clipboard!',
      embedCopied: 'HTML embed snippet copied to clipboard!',
      titleRequired: 'Please provide a form title',
      failedSave: 'Failed to save lead form'
    }
  },
  publicLead: {
    welcome: 'Welcome',
    getStarted: 'Get Started',
    next: 'Next',
    back: 'Back',
    submit: 'Submit Inquiry',
    name: 'Full Name',
    company: 'Company / Organization',
    email: 'Email Address',
    phone: 'Phone Number',
    notes: 'Additional Notes & Details',
    successTitle: 'Inquiry Received!',
    successMessage: 'Thank you for reaching out. We will review your requirements and respond promptly.',
    closeWindow: 'You may now safely close this window.'
  }
}
