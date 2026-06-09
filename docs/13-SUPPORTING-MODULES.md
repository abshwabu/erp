# MODULE: Asset Management

## Overview
Tracks all fixed assets (machinery, vehicles, computers, furniture) from acquisition through depreciation to disposal. Feeds directly into Accounting for depreciation entries and balance sheet reporting.

---

## 1. Asset Register

### 1.1 Asset Record
- Asset name, description, asset code (auto-generated)
- Asset category (IT Equipment, Vehicle, Machinery, Furniture, Building Improvement)
- Location (branch, floor, room)
- Assigned to (department or individual employee)
- Serial number, model, manufacturer
- Purchase date, purchase price, purchase currency
- Supplier linked to procurement record (if purchased through ERP)
- Warranty expiry date
- Photo and document attachments (invoice, certificate)
- Status: active, under maintenance, disposed, stolen/lost

### 1.2 Asset Capitalization
- Capitalize from: supplier invoice (linked from AP), manual entry
- Capitalization threshold (items below threshold expensed directly)
- Component assets: one asset made of multiple sub-assets (e.g., server + RAM + storage)
- Improvement costs: add to asset value (upgrades, major repairs)
- Asset splitting and merging

### 1.3 Asset Tagging
- QR code / barcode label generated per asset
- Scan to look up asset details on mobile
- Physical audit: scan all assets in a location to confirm existence

---

## 2. Depreciation

### 2.1 Depreciation Methods
| Method | Formula | Use Case |
|--------|---------|----------|
| Straight Line | (Cost − Salvage) / Useful Life | Most assets |
| Declining Balance | Net Book Value × Rate % | Vehicles, computers |
| Double Declining | Net Book Value × (2/Useful Life) | Fast initial depreciation |
| Units of Production | (Cost / Total Units) × Units Used | Machinery |
| Sum of Years Digits | Regional statutory requirement |

### 2.2 Depreciation Settings per Asset
- Useful life (years or months)
- Salvage / residual value
- Depreciation start date (purchase date, first day of next month, or custom)
- Depreciation method
- GL accounts: depreciation expense account, accumulated depreciation account

### 2.3 Depreciation Run
- Monthly depreciation batch: calculates and posts depreciation for all active assets
- Preview before posting (review amounts per asset)
- Depreciation journal entry auto-posted: Dr Depreciation Expense / Cr Accumulated Depreciation
- Depreciation schedule report: full schedule for any asset from purchase to full depreciation

---

## 3. Maintenance Management

### 3.1 Maintenance Schedule
- Preventive maintenance schedule: interval-based (every 3 months, every 5,000 km)
- Scheduled maintenance tasks: oil change, calibration, inspection, filter replacement
- Auto-generate maintenance work order N days before due date
- Alert when maintenance is overdue

### 3.2 Maintenance Work Orders
- Scheduled or ad-hoc maintenance request
- Description of work, assigned technician
- Parts used (linked to inventory if spare parts tracked)
- Labor hours
- Internal or external service provider (linked to supplier)
- Cost of maintenance (parts + labor)
- Completion date and notes
- Asset downtime tracked (maintenance start to end)

### 3.3 Maintenance History
- Full maintenance log per asset
- Total maintenance cost per asset per year
- Mean time between failures (MTBF)
- Total cost of ownership = purchase price + cumulative maintenance − salvage value estimate

---

## 4. Asset Disposal

### 4.1 Disposal Types
- Sale: proceeds received, gain/loss on disposal calculated
- Write-off: fully depreciated, damaged beyond repair, obsolete
- Trade-in: new asset acquired in exchange
- Donation: asset given away (zero proceeds)

### 4.2 Disposal Process
- Manager approval required for disposal
- Disposal journal entry auto-posted:
  - Remove cost from asset account
  - Remove accumulated depreciation
  - Record proceeds (if any)
  - Post gain or loss to disposal P&L account
- Asset status changed to "Disposed"
- Physical label flagged for removal

### 4.3 Disposal Report
- All assets disposed in a period with gain/loss
- Total disposal proceeds

---

## 5. Asset Reporting
- Asset register with full details per asset
- Net book value report (by category, location, department)
- Depreciation schedule (projected future depreciation)
- Asset due for full depreciation (next 12 months)
- Assets due for maintenance (next 30/60 days)
- Insurance report (for renewal: list of assets by value and location)

---
---

# MODULE: Project Management

## Overview
Manage client projects and internal initiatives with tasks, timelines, resource allocation, and budget tracking. Billable hours flow to Sales for invoicing.

---

## 1. Projects

### 1.1 Project Setup
- Project name, code, description
- Project type: client project, internal initiative, capital project
- Customer (for client projects)
- Start date, target end date
- Status: planning, active, on hold, completed, cancelled
- Project manager assignment
- Project team (multiple members)
- Budget (in hours and/or monetary value)
- Billing type: fixed price, time & materials, not billable

### 1.2 Project Phases & Milestones
- Divide project into phases (e.g., Discovery, Design, Build, Testing, Deployment)
- Milestones: key delivery dates with deliverable description
- Milestone status tracking and deadline alerts
- Gantt view of phases and milestones

---

## 2. Task Management

### 2.1 Task Structure
- Tasks nested under project and phase
- Task name, description, assigned team member(s)
- Priority: critical, high, medium, low
- Start date, due date, estimated hours
- Status: todo, in progress, review, done, blocked
- Dependency: task B starts only after task A is done
- Tags and custom fields

### 2.2 Task Views
- List view (sortable, filterable)
- Kanban board (drag tasks between status columns)
- Gantt chart (tasks on timeline, dependencies shown as links)
- Calendar view (tasks by due date)
- My Tasks (personal view of all tasks assigned to me across all projects)

### 2.3 Subtasks & Checklists
- Subtasks (child tasks with full task fields)
- Checklist within a task (simple to-do list items)
- Progress bar auto-calculated from checklist completion %

---

## 3. Time Tracking

### 3.1 Timesheet Entry
- Log hours against project + task
- Date, hours, description of work
- Timer: start/stop while working (auto-calculates duration)
- Billable vs non-billable classification per entry
- Bulk entry (enter whole week at once)

### 3.2 Timesheet Approval
- Submit timesheet for manager approval
- Manager approves or rejects with comment
- Approved timesheets locked (no edits)
- Approved billable hours flow to Sales for invoice generation

### 3.3 Time Reports
- Time logged per project, task, team member, period
- Billable vs non-billable breakdown
- Actual vs estimated hours per task
- Utilization rate per employee (% of working hours logged as billable)

---

## 4. Budget & Cost Tracking

### 4.1 Project Budget
- Total budget (monetary and hours)
- Budget allocated per phase or task
- Budget source: internal budget or client contract value

### 4.2 Cost Tracking
- Labor cost: approved hours × employee rate
- Expense claims logged against project (see Expense module)
- Purchases allocated to project (from Procurement)
- Total actual cost vs budget in real-time

### 4.3 Profitability
- Client project: revenue (invoiced) − cost (labor + expenses) = gross profit
- Margin % per project
- Forecast at completion: if current burn rate continues, will we exceed budget?

---

## 5. Project Billing

### 5.1 Billing Methods
- Fixed price: invoice on milestone completion
- Time & materials: invoice from approved billable hours
- Retainer: recurring monthly invoice

### 5.2 Invoice Generation
- One-click invoice from approved billable hours (for T&M projects)
- Invoice shows hours breakdown by task and rate
- Milestone billing: invoice released on milestone sign-off
- Partial billing: bill percentage of fixed price at each phase

---

## 6. Project Reporting
- Project status report (traffic light: on track, at risk, delayed)
- Portfolio view: all active projects with status, budget utilization, and margin
- Resource utilization: team members' time across all projects
- Overdue tasks report
- Project profitability comparison

---
---

# MODULE: Customer Support (Help Desk)

## Overview
Manage customer service requests, track resolution, enforce SLAs, and measure team performance. Integrates with CRM for full customer context.

---

## 1. Ticket Management

### 1.1 Ticket Intake Channels
- Email (support@yourcompany.com → creates ticket automatically)
- Web form (embedded on website or customer portal)
- Phone (agent manually creates ticket)
- WhatsApp (via WhatsApp Business API)
- Live chat (optional chat widget integration)

### 1.2 Ticket Fields
- Ticket number (auto-generated), title, description
- Customer (linked to CRM contact/account)
- Related order or invoice
- Category: billing, technical, delivery, product defect, other
- Priority: critical, high, medium, low
- Status: new, open, pending customer, resolved, closed
- Assigned agent
- SLA deadline

### 1.3 Ticket Workflow
- New ticket → Auto-acknowledge to customer (within 5 min)
- Auto-assign based on category or round-robin
- Agent works ticket, posts internal notes and customer replies
- Mark pending customer (waiting for customer response)
- Auto-resolve if no reply in N days (configurable)
- Customer rates resolution (CSAT score)

---

## 2. SLA Management

### 2.1 SLA Policies
- Define SLA per priority:
  - Critical: first response 1 hour, resolution 4 hours
  - High: first response 4 hours, resolution 24 hours
  - Medium: first response 24 hours, resolution 72 hours
  - Low: first response 48 hours, resolution 7 days
- Business hours vs 24/7 SLA clock
- SLA paused when ticket is in "Pending Customer" status

### 2.2 SLA Alerts
- Agent notified when response due in 30 minutes
- Manager notified when SLA is breached
- Escalation: auto-reassign to senior agent or manager on breach
- SLA compliance rate in KPI dashboard

---

## 3. Knowledge Base

### 3.1 Article Management
- Create and publish help articles (rich text with images, videos, attachments)
- Categories and subcategories
- SEO title, description, and slug per article
- Version history per article
- Draft → Review → Published workflow
- Assign article owner and reviewer

### 3.2 Self-Service Portal
- Customer-facing knowledge base (public or login-required)
- Search across all articles
- Related articles shown on ticket submission form
- Suggest articles before ticket is submitted ("Did this answer your question?")
- Deflection metric: tickets avoided because customer found the article

### 3.3 Internal Knowledge Base
- Private articles visible only to support agents
- Troubleshooting guides, escalation procedures, internal SOPs
- Agents suggested relevant internal articles when viewing a ticket

---

## 4. Team Management

### 4.1 Agent Configuration
- Support team members, their categories, working hours
- Skills tagging (languages, product expertise)
- Max tickets per agent (overflow routing)

### 4.2 Canned Responses
- Pre-written responses for common questions
- Agent inserts with one click (fully editable before sending)
- Category-organized (billing responses, shipping responses, etc.)
- Track which canned responses are used most

---

## 5. Support Reporting
- Ticket volume by period and channel
- First response time (avg, median, 95th percentile)
- Resolution time (same metrics)
- SLA compliance rate by priority
- Agent performance: tickets handled, avg resolution time, CSAT per agent
- CSAT score trend (overall and per agent)
- Top ticket categories (what customers need most help with)
- Knowledge base deflection rate

---
---

# MODULE: Document Management

## Overview
Centralized storage, version control, and access management for all company documents — contracts, policies, certificates, templates, and more.

---

## 1. Document Storage & Organization

### 1.1 Folder Structure
- Hierarchical folder tree (unlimited depth)
- Department folders with default access permissions
- Shared folders (cross-department access)
- Personal workspace (private to each user)
- Project folders (linked to Project module)
- System folders: HR Documents, Contracts, Compliance, Policies

### 1.2 Document Upload
- Upload any file type (PDF, Word, Excel, images, video)
- Drag-and-drop upload
- Bulk upload with folder auto-detection
- Maximum file size configurable per plan
- Auto-thumbnail generation for images and PDFs

### 1.3 Metadata & Tagging
- Custom metadata fields per folder/document type
- Tags for cross-folder search
- Expiry date (for certificates, contracts, permits)
- Confidentiality level: public, internal, confidential, restricted

---

## 2. Version Control

### 2.1 Version History
- Every upload of an existing document creates a new version
- Version number, uploader, upload date per version
- View, download, or restore any previous version
- Compare versions (for text-based documents)

### 2.2 Check-Out / Check-In
- Check-out: lock document for editing (others see it as locked and by whom)
- Edit locally, then check-in with new version and change notes
- Force check-in by admin if user is unavailable

---

## 3. Access Control

### 3.1 Permissions
- Per-folder permissions: view, download, upload, edit, delete, manage permissions
- Permissions inherited from parent folder (overridable at child level)
- Share with: specific users, roles, or departments
- External sharing: time-limited link (view only, no login required)
- Revoke external link at any time

### 3.2 Watermarking
- Auto-watermark sensitive PDFs with "CONFIDENTIAL" or viewer's name + date
- Applied on download for confidential documents

---

## 4. Document Templates

### 4.1 Template Library
- Pre-built templates: Employment Contract, NDA, Purchase Agreement, Invoice, Leave Request Form
- Template editor: HTML-based (WYSIWYG)
- Merge fields: {employee_name}, {start_date}, {salary}, etc.

### 4.2 Document Generation
- Select template, fill merge fields (pre-populated from ERP records)
- Generate as PDF or Word document
- Automated generation: payslips, contracts, offer letters generated directly from HR module

---

## 5. E-Signature

### 5.1 Signature Requests
- Send document for e-signature to one or multiple signatories
- Order of signing (sequential): Signatory A must sign before B
- Parallel signing: all signatories receive simultaneously
- Reminder emails to pending signatories
- Expiry date on signature request

### 5.2 Signing Experience
- Signatory clicks link in email (no app download needed)
- Reviews document, draws or types signature
- OTP verification for high-security documents
- Decline with reason

### 5.3 Completed Documents
- Signed document stored with audit certificate
- Audit certificate includes: signatories, timestamps, IP addresses, email verification
- Tamper-evident: any modification after signing invalidates document

---

## 6. Document Alerts & Compliance
- Expiry alerts: certificates, permits, contracts expiring in 30/60/90 days
- Renewal workflow: trigger approval flow for renewal
- Mandatory document checklist: employees must upload specific documents (alerts until complete)
- Retention policy: auto-archive documents after N years; alert before permanent deletion
- Compliance report: which employees/assets have expired documents

---

## 7. Search
- Full-text search across document names, metadata, and document contents (OCR for scanned PDFs)
- Filter by: folder, type, date, tags, expiry status, uploader
- Search within a specific folder subtree
- Saved searches / smart folders (auto-populate based on filter criteria)
