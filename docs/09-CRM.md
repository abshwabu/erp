# MODULE: CRM (Customer Relationship Management)

## Overview
The CRM module manages leads, prospects, and the full customer relationship lifecycle. It tracks every interaction, manages the sales pipeline, and provides sales teams with the tools to close deals and retain customers.

---

## 1. Contact Management

### 1.1 Contacts (People)
- First name, last name, job title, department
- Company association (linked to an Account)
- Email addresses (multiple: work, personal)
- Phone numbers (multiple: mobile, office, WhatsApp)
- Mailing address
- Social profiles (LinkedIn, Twitter)
- Profile photo
- Birthday (for relationship touchpoints)
- Contact source (how acquired)
- Tags (free-form labeling)
- Opt-in/opt-out status for marketing emails

### 1.2 Accounts (Companies)
- Company name, industry, size (employees, revenue band)
- Website, headquarters address
- Multiple contacts linked to one account
- Parent/child account relationship (subsidiaries, franchises)
- Account owner (assigned sales rep)
- Account type: lead, prospect, customer, partner, competitor
- Annual revenue estimate
- Customer since date

### 1.3 Contact Timeline
- Chronological feed of all interactions per contact/account:
  - Emails sent and received
  - Calls logged
  - Meetings held
  - Notes added
  - Quotes sent
  - Orders placed
  - Invoices and payments
  - Tasks completed
- Full context visible to any team member without hunting through emails

### 1.4 Duplicate Detection
- System flags potential duplicates on creation (same name + email or phone)
- Manual merge tool: choose which fields to keep from each record
- Merge history preserved (merged-from records become aliases)

---

## 2. Lead Management

### 2.1 Lead Capture
- Manual entry by sales team
- Web-to-lead form (embed on website, auto-creates lead in CRM)
- Email-to-lead (dedicated lead capture email address)
- Import leads from CSV (trade show lists, purchased lists)
- API import from marketing tools, ads platforms

### 2.2 Lead Profile
- Contact information
- Lead source (website, referral, cold call, event, ad campaign)
- Product/service interest
- Estimated budget
- Geographic region
- Lead score (manual or calculated)
- Status: new, contacted, qualified, unqualified, nurturing

### 2.3 Lead Qualification
- Qualification framework: BANT (Budget, Authority, Need, Timeline)
- Qualification checklist per lead
- Qualified lead: converted to Opportunity (with contact and account created)
- Unqualified lead: reason recorded; can be revived later or archived

### 2.4 Lead Scoring
- Automatic score based on:
  - Demographic fit (company size, industry, location): +/− points
  - Behavioral (email opened: +5, link clicked: +10, page visited: +3, form filled: +20)
  - Engagement recency (decays over time if no activity)
- Score thresholds: cold (<20), warm (20–50), hot (50+)
- Hot leads auto-assigned to senior sales reps or auto-notified

### 2.5 Lead Assignment
- Round-robin auto-assignment across sales team
- Geographic or territory-based assignment
- Skill-based routing (lead interested in product X → rep specializing in X)
- Manual reassignment with reason

---

## 3. Sales Pipeline (Opportunities)

### 3.1 Opportunity Creation
- From qualified lead (one click) or standalone
- Opportunity name, associated account and contacts
- Estimated value (deal size)
- Expected close date
- Probability of close (% — can be manual or stage-based)
- Pipeline stage
- Product/service interest
- Competition (are we competing with others?)
- Source

### 3.2 Pipeline Stages (Configurable)
Default stages:
1. Discovery / Initial Contact
2. Needs Assessment
3. Demo / Presentation
4. Proposal Sent
5. Negotiation
6. Closed Won
7. Closed Lost

- Tenant can rename, add, remove, or reorder stages
- Each stage has: definition, checklist of required activities, probability %
- Stage-based probability auto-updates deal probability on stage change

### 3.3 Pipeline Views
- **Kanban Board:** Drag-and-drop deals between stages; deal value shown on card
- **List View:** Table with sortable columns; bulk actions
- **Forecast View:** Deals by expected close date and weighted value
- **Funnel View:** Count and value at each stage — where are deals dropping off?

### 3.4 Deal Weighting & Forecasting
- Weighted pipeline: deal value × probability = weighted value
- Forecast by month: sum of weighted values of deals closing that month
- Commit forecast: deals flagged as "committed" by rep (high confidence)
- Pipeline coverage ratio: pipeline / quota (healthy: 3× coverage recommended)

### 3.5 Win/Loss Analysis
- Close reason: price, competitor, budget cut, timing, product fit, no decision
- Lost-to-competitor tracking (which competitor won)
- Win rate by: product, stage, rep, industry, deal size
- Identify patterns in losses to improve process and product

---

## 4. Activities & Task Management

### 4.1 Activity Types
- **Call:** Log outbound/inbound calls with duration, outcome, notes
- **Email:** Tracked emails sent from CRM; inbound email recorded
- **Meeting:** Schedule and log in-person or virtual meetings
- **Task:** Follow-up items, to-dos linked to contact/deal
- **Note:** Free-form notes and observations

### 4.2 Activity Logging
- Log activity manually after the fact
- Schedule future activities with due date and reminder
- Activity linked to: contact, account, deal, lead
- Activity outcome: completed, no answer, left message, meeting rescheduled
- Next step: follow-up activity automatically suggested on completion

### 4.3 Task Management
- Personal task list for each sales rep
- Due date, priority (high/medium/low), related record
- Overdue task alerts (in-app + email)
- Manager view: team's overdue tasks
- Completed task history

### 4.4 Reminders
- System reminders: "Deal has been in Negotiation stage for 14 days with no activity"
- Follow-up reminders: set reminder N days after last contact
- Meeting reminders: 1 day before, 1 hour before
- Inactivity alert: deal with no activity in X days

---

## 5. Email Integration

### 5.1 Two-Way Email Sync
- Connect personal Gmail or Outlook inbox to CRM
- Emails sent to/from known contacts automatically logged on their timeline
- Manually associate email with deal or lead
- BCC-to-CRM address (forward any email to CRM for logging)

### 5.2 Email Templates
- Reusable templates for common emails (intro, follow-up, quote cover, thank you)
- Personalization tokens: {first_name}, {company}, {quote_amount}, {rep_name}
- HTML and plain text versions
- Template performance tracking: open rate, reply rate per template

### 5.3 Email Sequences (Drip)
- Multi-step automated email sequences for lead nurturing
- Step 1: Day 0 — intro email; Step 2: Day 3 — value email; Step 3: Day 7 — follow-up
- Sequence paused automatically if lead replies or unsubscribes
- A/B test subject lines within sequences

### 5.4 Email Tracking
- Open tracking (pixel)
- Link click tracking
- Real-time notification: "John from Acme just opened your proposal email"
- Open/click activity logged on contact timeline

---

## 6. Communication Channels

### 6.1 Phone / Call Integration
- Click-to-call from contact record (via VoIP integration: Twilio, RingCentral)
- Automatic call logging (duration, recording link if enabled)
- Call notes entered during or after call
- Missed call logging

### 6.2 WhatsApp Integration
- Send WhatsApp messages from CRM (via WhatsApp Business API)
- Messages logged on contact timeline
- Template messages for common responses (approved templates required for outbound)

### 6.3 SMS
- Send SMS from contact record (via Twilio/Africa's Talking)
- SMS logged on timeline
- Bulk SMS to a filtered segment (for campaigns)

---

## 7. Customer Segmentation

### 7.1 Segment Builder
- Dynamic segments: filter by any contact/account field combination
- Examples:
  - "All customers in Nairobi with purchase value > $5,000 in last 90 days"
  - "All leads who opened email but haven't replied in 7 days"
  - "All customers on monthly contract expiring in next 30 days"
- Segments update automatically as data changes

### 7.2 Segment Uses
- Target email campaigns
- Bulk task creation ("call all hot leads this week")
- Reports filtered by segment
- Export to CSV for external campaigns

---

## 8. Loyalty Program

### 8.1 Loyalty Configuration
- Points earning rules: 1 point per $1 spent, or per product category
- Points expiry: expire after N months of inactivity
- Tier structure:
  - Bronze (0–499 points): standard benefits
  - Silver (500–1,999 points): 5% bonus points on all purchases
  - Gold (2,000+ points): 10% bonus + priority service
- Tier recalculation: rolling 12 months or calendar year

### 8.2 Points Redemption
- Redemption rate: 100 points = $1 discount
- Redemption at POS, online checkout, or invoice credit
- Minimum redemption threshold (e.g., 500 points minimum)
- Maximum redemption per transaction (e.g., max 20% of transaction value)

### 8.3 Loyalty Card & Member ID
- Digital loyalty card (QR code in mobile app or email)
- Physical card with barcode (printable from system)
- Look up by phone, email, or card number at POS

### 8.4 Loyalty Reporting
- Total points issued and redeemed per period
- Points liability (unredeemed points × redemption value)
- Tier distribution of customers
- Top loyalty members by points
- Redemption rate and trend

---

## 9. CRM Reporting & Analytics

### 9.1 Sales Performance
- Revenue by sales rep vs target/quota
- Deal count and value by stage
- Activities per rep (calls, emails, meetings)
- Win rate per rep and per product

### 9.2 Pipeline Reports
- Pipeline value by stage and rep
- Average deal size by product/industry
- Average sales cycle length (days from lead to close)
- Pipeline velocity: how fast deals move through each stage

### 9.3 Customer Reports
- Customer acquisition cost (CAC) — when marketing spend is tracked
- Customer lifetime value (LTV)
- Churn risk scoring (customers with declining order frequency)
- New vs returning customer ratio

---

## 10. Integrations

- **Sales:** Opportunities convert to quotes and sales orders; customer data synced
- **Accounting:** Invoice and payment history visible on customer CRM record
- **POS:** Transaction history from POS linked to CRM customer record
- **Marketing Email (SendGrid/Mailchimp):** Sync segments for campaigns; email events logged back in CRM
- **VoIP (Twilio/RingCentral):** Click-to-call, call logging
- **WhatsApp Business API:** Message history on contact timeline
