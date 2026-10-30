export default {
  common: {
    title: 'Projects',
    subtitle: 'Projects portfolio, tasks, timesheets, and milestones.',
    save: 'Save',
    cancel: 'Cancel',
    delete: 'Delete',
    edit: 'Edit',
    close: 'Close',
    loading: 'Loading…',
    noData: 'No projects created yet',
    noDataSubtitle: 'Start by creating your first client or internal project.',
    manager: 'Manager',
    budget: 'Budget',
    startDate: 'Start Date',
    dueDate: 'Due Date',
    selectProjectToView: 'Select a project from the left to view details and task breakdown.',
    statuses: {
      planned: 'Planned / Scoping',
      in_progress: 'In Progress / Active',
      on_hold: 'On Hold',
      completed: 'Completed 🎉',
      cancelled: 'Cancelled'
    },
    priorities: {
      low: 'Low',
      medium: 'Medium',
      high: 'High',
      urgent: 'Urgent'
    }
  },
  overview: {
    title: 'Projects Portfolio',
    subtitle: 'Plan deliverables, track resource allocation, manage milestones, and monitor project budgets.',
    buttons: {
      kanbanBoard: 'Kanban Board',
      newProject: 'New Project'
    },
    kpis: {
      activeProjects: 'Active Projects',
      totalPortfolioBudget: 'Total Portfolio Budget',
      taskCompletionRate: 'Task Completion Rate',
      loggedWorkHours: 'Logged Work Hours'
    },
    filters: {
      all: 'All',
      in_progress: 'In Progress',
      planned: 'Planned',
      on_hold: 'On Hold',
      completed: 'Completed',
      cancelled: 'Cancelled',
      searchPlaceholder: 'Search code, name, client...'
    },
    card: {
      internalProject: 'Internal Project',
      taskProgress: 'Task Progress',
      tasksCount: 'tasks'
    },
    emptyState: {
      title: 'No projects found',
      text: 'Create client engagements or internal operational projects to start organizing tasks and tracking hours.',
      createFirst: 'Create First Project'
    },
    modal: {
      createTitle: 'Initialize New Project',
      editTitle: 'Edit Project',
      projectName: 'Project Name',
      namePlaceholder: 'e.g. ERP Cloud Migration & Mobile App',
      projectCode: 'Project Code',
      codePlaceholder: 'Auto (e.g. PRJ-001)',
      clientLabel: 'Client / Customer Account (Optional)',
      internalInitiative: 'Internal Initiative (None)',
      budgetLabel: 'Project Budget ($)',
      statusLabel: 'Project Status',
      priorityLabel: 'Priority Level',
      deadlineLabel: 'Target Deadline',
      scopeLabel: 'Project Scope & Description',
      scopePlaceholder: 'Objectives, key deliverable milestones, architecture notes...',
      createBtn: 'Initialize Project',
      saveBtn: 'Save Project Changes'
    },
    detailModal: {
      title: 'Project Overview & Tasks Dossier',
      totalBudget: 'Total Budget',
      projectTasks: 'Project Tasks',
      openKanban: 'Open Full Kanban Board →',
      unassigned: 'Unassigned',
      noTasks: 'No tasks created under this project yet.',
      close: 'Close'
    },
    messages: {
      created: 'New project initialized',
      updated: 'Project updated',
      deleted: 'Project deleted',
      nameRequired: 'Please enter a project name',
      failedSave: 'Failed to save project'
    }
  },
  tasks: {
    title: 'Task Board & Kanban',
    subtitle: 'Track execution flow across project backlogs, active sprints, reviews, and completed items.',
    viewKanban: 'Kanban',
    viewTable: 'Table',
    createTask: 'Create Task',
    kpis: {
      backlog: 'Backlog / To Do',
      inProgress: 'In Progress Tasks',
      completed: 'Completed Tasks',
      totalHoursLogged: 'Total Hours Logged'
    },
    filterAllProjects: 'All Projects',
    searchPlaceholder: 'Filter task title, description...',
    statuses: {
      todo: 'To Do',
      in_progress: 'In Progress',
      review: 'Under Review',
      done: 'Done 🎉'
    },
    kanban: {
      logHoursTitle: 'Log Hours',
      back: 'Back',
      next: 'Next',
      noTasksInStatus: 'No tasks in'
    },
    table: {
      taskProject: 'Task & Project',
      status: 'Status',
      priority: 'Priority',
      loggedEstHours: 'Logged / Est. Hours',
      targetDueDate: 'Target Due Date',
      actions: 'Actions',
      logTimeBtn: 'Log Time',
      emptySearch: 'No tasks found matching your filters.'
    },
    modal: {
      createTitle: 'Add Task to Project',
      editTitle: 'Edit Task',
      projectLabel: 'Project',
      titleLabel: 'Task Title',
      titlePlaceholder: 'e.g. Implement OAuth2 client provider',
      initialStatus: 'Initial Status',
      priorityLabel: 'Priority',
      estHoursLabel: 'Estimated Hours',
      dueDateLabel: 'Due Date',
      descriptionLabel: 'Description & Acceptance Criteria',
      descriptionPlaceholder: 'Implementation requirements, checklists, dependencies...',
      createBtn: 'Create Task',
      saveBtn: 'Save Task'
    },
    logTimeModal: {
      title: 'Log Work Hours on Task',
      hoursSpent: 'Hours Spent',
      workDate: 'Work Date',
      workDescription: 'Work Description',
      descriptionPlaceholder: 'Summary of work delivered, commits, or bugfixes...',
      billableCheckbox: 'Billable client work',
      recordBtn: 'Record Timesheet Log'
    },
    messages: {
      created: 'New task added to project',
      updated: 'Task updated',
      statusUpdated: 'Task status updated',
      deleted: 'Task deleted',
      timeLogged: 'Work hours logged to task! ⏱️',
      titleRequired: 'Please enter a task title',
      projectRequired: 'Please select a project for this task',
      failedSave: 'Failed to save task',
      failedLogTime: 'Failed to log hours'
    }
  },
  timeLogs: {
    title: 'Timesheets & Work Logs',
    subtitle: 'Track labor expenditure, billable consultant hours, and development timesheets.',
    logWorkHours: 'Log Work Hours',
    kpis: {
      totalHoursRecorded: 'Total Hours Recorded',
      billableHours: 'Billable Hours',
      billableEfficiency: 'Billable Efficiency',
      totalLogEntries: 'Total Log Entries'
    },
    searchPlaceholder: 'Filter logs by description, member, task...',
    table: {
      projectTask: 'Project & Task',
      teamMember: 'Team Member',
      workDescription: 'Work Description',
      workDate: 'Work Date',
      hoursLogged: 'Hours Logged',
      billable: 'Billable',
      actions: 'Actions',
      generalProjectWork: 'General Project Work',
      currentUser: 'Current User',
      billableBadge: 'Billable',
      internalBadge: 'Internal',
      emptyTitle: 'No timesheet logs recorded yet.'
    },
    modal: {
      createTitle: 'Record Timesheet Work Hours',
      editTitle: 'Edit Time Log',
      projectLabel: 'Project',
      associatedTask: 'Associated Task (Optional)',
      noTaskOption: 'General Project Work (No Task)',
      hoursSpent: 'Hours Spent',
      workDate: 'Work Date',
      workDelivered: 'Work Delivered & Summary',
      deliveredPlaceholder: 'Describe completed tasks, milestone progress, or meeting notes...',
      billableCheckbox: 'Billable client work',
      createBtn: 'Record Time Log',
      saveBtn: 'Save Changes'
    },
    messages: {
      created: 'Work hours recorded',
      updated: 'Time log updated',
      deleted: 'Time log deleted',
      projectRequired: 'Please select a project',
      hoursRequired: 'Please enter valid hours spent',
      failedSave: 'Failed to save time log'
    }
  },
  milestones: {
    title: 'Project Milestones & Deliverables',
    subtitle: 'Plan roadmap releases, client approval gates, and delivery commitments.',
    addMilestone: 'Add Milestone',
    kpis: {
      totalMilestones: 'Total Milestones',
      inProgressTargets: 'In Progress Targets',
      achievedDeliverables: 'Achieved Deliverables',
      delayedBlocked: 'Delayed / Blocked'
    },
    searchPlaceholder: 'Filter milestones...',
    statuses: {
      pending: 'Pending',
      in_progress: 'In Progress',
      achieved: 'Achieved 🎉',
      delayed: 'Delayed'
    },
    card: {
      target: 'Target'
    },
    emptyState: {
      title: 'No milestones found',
      text: 'Set key project target deadlines and release milestones to keep your team aligned.',
      scheduleFirst: 'Schedule Milestone'
    },
    modal: {
      createTitle: 'Schedule Project Milestone',
      editTitle: 'Edit Milestone',
      projectLabel: 'Project',
      milestoneTitle: 'Milestone Title',
      titlePlaceholder: 'e.g. Beta Release 1.0 Deployment',
      statusLabel: 'Status',
      dueDateLabel: 'Target Due Date',
      scopeLabel: 'Milestone Scope & Deliverables',
      scopePlaceholder: 'Key deliverables, verification criteria...',
      createBtn: 'Schedule Milestone',
      saveBtn: 'Save Changes'
    },
    messages: {
      scheduled: 'Milestone scheduled',
      updated: 'Milestone updated',
      deleted: 'Milestone deleted',
      titleRequired: 'Please enter a milestone title',
      projectRequired: 'Please select a project',
      failedSave: 'Failed to save milestone'
    }
  }
}
