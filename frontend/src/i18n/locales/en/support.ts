export default {
  common: {
    title: 'Support Desk',
    subtitle: 'Customer support inquiries, tickets queue, and knowledge base.',
    loading: 'Loading support tickets…',
    noData: 'No tickets opened',
    noDataSubtitle: 'Customer support inquiries and requests will appear here.',
    selectTicketToView: 'Select a ticket to view the conversation history and reply.',
    from: 'From',
    internalNoteCheckbox: 'Internal note (hidden from customer)',
    replyPlaceholder: 'Type your response or internal note…',
    save: 'Save',
    cancel: 'Cancel',
    delete: 'Delete',
    edit: 'Edit',
    close: 'Close',
    statuses: {
      open: 'Open',
      in_progress: 'In Progress',
      pending: 'Pending Client',
      resolved: 'Resolved 🎉',
      closed: 'Closed'
    },
    priorities: {
      low: 'Low',
      normal: 'Normal',
      high: 'High',
      urgent: 'Urgent (SLA)'
    },
    channels: {
      web: 'Web Helpdesk',
      email: 'Email',
      phone: 'Phone Call',
      portal: 'Client Portal'
    },
    categories: {
      all: 'All Articles',
      general: 'General Documentation',
      billing: 'Billing & Invoices',
      technical: 'Technical Guide',
      account: 'Account & Security',
      faq: 'Frequently Asked Questions (FAQ)'
    }
  },
  dashboard: {
    title: 'Helpdesk & Support Center',
    subtitle: 'Manage customer tickets, SLA resolution rates, communication logs, and knowledge base resources.',
    buttons: {
      knowledgeBase: 'Knowledge Base',
      allTickets: 'All Tickets Queue',
      viewQueue: 'View Ticket Queue',
      browseArticles: 'Browse Articles'
    },
    kpis: {
      openInquiries: 'Open Inquiries',
      inProgressTickets: 'In Progress Tickets',
      resolutionRate: 'Resolution Rate',
      urgentSla: 'Urgent SLA Inquiries'
    },
    recentTicketsTitle: 'Recent Support Tickets',
    repliesCount: 'replies',
    emptyRecent: 'No customer tickets logged yet.',
    ticketsByChannel: 'Tickets by Channel',
    webInquiries: 'Web Inquiries',
    emailTickets: 'Email Tickets',
    phoneSupport: 'Phone Support',
    clientPortal: 'Client Portal',
    knowledgeBaseCard: {
      title: 'Knowledge Base & FAQs',
      text: 'Empower your support team with standard operating procedures and FAQs to resolve client issues faster.'
    }
  },
  tickets: {
    title: 'Support Ticket Queue',
    subtitle: 'Track and respond to client inquiries, communicate through thread histories, and post internal agent notes.',
    newTicket: 'New Support Ticket',
    kpis: {
      openInquiries: 'Open Inquiries',
      inProgress: 'In Progress',
      resolvedInquiries: 'Resolved Inquiries',
      urgentPriority: 'Urgent Priority'
    },
    filters: {
      all: 'All',
      searchPlaceholder: 'Search tickets, customers, emails...',
      emptyList: 'No tickets found matching your filter.'
    },
    thread: {
      noSelectedTitle: 'Select a ticket from the queue',
      noSelectedText: 'Click any ticket on the left to inspect conversation history, send replies, or add internal notes.',
      resolveBtn: 'Resolve',
      reopenBtn: 'Re-open Ticket',
      deleteTicketTitle: 'Delete Ticket',
      internalNoteBadge: 'INTERNAL NOTE',
      supportAgentBadge: 'SUPPORT AGENT',
      publicReply: 'Public Reply',
      internalAgentNote: 'Internal Agent Note',
      pressSendHint: 'Press send to post',
      publicPlaceholder: 'Type your reply to the customer...',
      internalPlaceholder: 'Write an internal note visible only to your support team...',
      postInternalNoteBtn: 'Post Internal Note',
      sendReplyBtn: 'Send Reply',
      customerFallback: 'Customer'
    },
    modal: {
      createTitle: 'Create Customer Support Ticket',
      subjectLabel: 'Subject / Problem Summary',
      subjectPlaceholder: 'e.g. Invoicing calculation mismatch',
      clientLabel: 'Linked Client Account (Optional)',
      directInquirer: 'None (Direct Inquirer)',
      priorityLabel: 'Priority Level',
      contactNameLabel: 'Contact Name',
      contactEmailLabel: 'Contact Email',
      channelLabel: 'Inbound Channel',
      messageLabel: 'Initial Inquiry / Message',
      messagePlaceholder: 'Detailed description of the customer inquiry, error logs, or request...',
      createBtn: 'Create Ticket'
    },
    messages: {
      created: 'Support ticket created',
      internalNoteAdded: 'Internal agent note added',
      replySent: 'Reply sent to customer',
      statusUpdated: 'Ticket status updated',
      deleted: 'Ticket deleted',
      requiredFields: 'Subject and message description are required',
      failedCreate: 'Failed to create ticket',
      failedReply: 'Failed to send message'
    }
  },
  knowledgeBase: {
    title: 'Knowledge Base & FAQ Library',
    subtitle: 'Standard operating guidelines, troubleshooting documentation, and customer self-service runbooks.',
    writeArticle: 'Write Article',
    writeFirst: 'Write First Article',
    kpis: {
      publishedArticles: 'Published Articles',
      articleCategories: 'Article Categories',
      totalResourceViews: 'Total Resource Views',
      resolutionEffectiveness: 'Resolution Effectiveness'
    },
    searchPlaceholder: 'Search articles & FAQs...',
    emptyTitle: 'No knowledge base articles found',
    emptyText: 'Create help guides, FAQs, and technical troubleshooting articles for your team and clients.',
    views: 'views',
    byAuthor: 'By',
    defaultAuthor: 'Support Team',
    modal: {
      createTitle: 'Publish Knowledge Article',
      editTitle: 'Edit Knowledge Article',
      viewTitle: 'Knowledge Base Article',
      articleTitle: 'Article Title',
      titlePlaceholder: 'e.g. How to reconcile multi-currency invoices',
      categoryLabel: 'Category',
      summaryLabel: 'Summary / Preview Snippet',
      summaryPlaceholder: 'Short description for search results...',
      contentLabel: 'Article Content (Markdown Supported)',
      contentPlaceholder: 'Step 1: Navigate to the billing section...\nStep 2: Click reconcile transactions...',
      publishBtn: 'Publish Article',
      saveBtn: 'Save Changes',
      authorLabel: 'Author',
      defaultAdmin: 'Support Admin'
    },
    messages: {
      published: 'Article published',
      updated: 'Article updated',
      deleted: 'Article removed',
      requiredFields: 'Article title and content are required',
      failedSave: 'Failed to save article'
    }
  }
}
