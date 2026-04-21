# MISO360 User Manual

This manual describes how to use MISO360 from the perspective of:

- **End Users** (standard accounts, including request filing and tracking)
- **Admins** (staff who manage requests, inventory, QR codes, users, and reference values)
- **Superadmins** (admins with access to restricted reports/exports)

---

## Table of contents

- [Access, accounts, and roles](#access-accounts-and-roles)
- [End User guide](#end-user-guide)
  - [Register and sign in](#register-and-sign-in)
  - [Email verification](#email-verification)
  - [Pending administrator approval](#pending-administrator-approval)
  - [User Dashboard](#user-dashboard)
  - [Submit a Ticket Request](#submit-a-ticket-request)
  - [Track requests (“My Requests”)](#track-requests-my-requests)
  - [Archive](#archive)
  - [Notifications](#notifications)
  - [Profile and appearance](#profile-and-appearance)
- [Admin guide](#admin-guide)
  - [Admin access and 2FA re-verification](#admin-access-and-2fa-re-verification)
  - [Admin Dashboard](#admin-dashboard)
  - [Requests (admin view)](#requests-admin-view)
  - [Archived requests and exports](#archived-requests-and-exports)
  - [Request detail pages](#request-detail-pages)
  - [QR Code Generator](#qr-code-generator)
  - [Enrollments and Inventory](#enrollments-and-inventory)
  - [QR Scan / lookup / assignment](#qr-scan--lookup--assignment)
  - [Status Management (reference values)](#status-management-reference-values)
  - [Nature of Request](#nature-of-request)
  - [User management](#user-management)
  - [Audit logs](#audit-logs)
  - [Post management (homepage slides)](#post-management-homepage-slides)
- [Superadmin guide](#superadmin-guide)
  - [Nature of Requests report](#nature-of-requests-report)
  - [Consolidated report export](#consolidated-report-export)

---

## Access, accounts, and roles

### Core access rules

Most pages require the following before you can use the app:

- **Signed in**
- **Email verified**
- **Account active**
- **Admin-verified** (for accounts that require manual approval)

If your account requires manual approval and is not yet approved, you will be redirected to **Pending administrator approval**.

### Roles (high-level)

- **User**: Can file and track their requests, view their dashboard, manage profile/appearance, and see notifications.
- **Admin**: Has access to admin tools (requests queue, QR generation, enrollments/inventory, user management, reference data, posts).
- **Superadmin**: Has all Admin capabilities plus access to restricted reporting/export endpoints.
- **Submit-only (special account)**: A restricted account intended for ticket submission only (access limited to `/submit-request` and `/pending-approval`).

---

## End User guide

### Register and sign in

- **Register**: Go to `/register`, fill in the required fields, and submit.
  - Note: registration is rate-limited to reduce abuse.
- **Sign in**: Go to `/login`.
- **Sign out**: Use the sign out action in the app (or the button on the pending-approval page).

### Email verification

After registering, MISO360 may require you to verify your email address before you can use the app.

If you see an email verification screen, follow the instructions provided and check your inbox/spam folder.

### Pending administrator approval

If your email is verified but your account needs manual approval, you’ll see **Pending administrator approval** at `/pending-approval`.

- **What it means**: An administrator must verify your account before you can use the application.
- **What to do**: Contact your MISO administrator if approval is taking too long.

### User Dashboard

After successful sign in, standard users can access the **User Dashboard** at `/dashboard`.

The dashboard shows:

- **Queued tickets**: Your requests that are pending or ongoing.
- **Total requests**: Your all-time request count.
- **Current Queue**: A FIFO list of pending requests (oldest first). You can open a request using its **Control Ticket No.** link.

### Submit a Ticket Request

Submit a new request at `/submit-request`.

#### 1) Review the Control Ticket Number

The form includes a **Control Ticket Number** (read-only). This helps track your request.

#### 2) (If available) Submit on behalf of someone

Some users can submit for others (for example, a privileged requester workflow). If you see these fields:

- **Office Designation**: search and select an office/division
- **Requesting For**: select the user under that office

If you are a submit-only user, you may not need to select office/user and can submit for your own account.

#### 3) Select the Nature of Request

Choose the **Nature of Request** (request category). This selection can change what the form requires.

Common behavior:

- **System Development**:
  - Download the **Systems Development Survey Form (PDF)**.
  - Complete it offline.
  - Upload the completed form (required) before submitting.
- **System Modification** or **Request for new system module or enhancement**:
  - Download the **System Change Request Form (PDF)**.
  - Complete it offline.
  - Upload the completed form (required) before submitting.
- **Data Release Request and Approval**:
  - Download the **Data Request and Approval Form (DOCX)**.
  - Complete it offline.
  - Upload the completed form (required) before submitting.
- **Password reset / account recovery**:
  - A **Personal Email** field is required.
- **System account creation**:
  - An **Office Email** field is required.

#### 4) (Optional) Link the request to a unit via QR (MIS-UID)

If the request is related to a specific unit:

- Enable **QR Code for Unit**
- Enter the **QR Code Number (MIS-UID)** in the format `MIS-UID-00001`

Only issued codes should be used.

#### 5) Provide a request description

- **Description of Request** is required in most cases.
- Minimum length applies (the form will prompt you if it’s too short).
- Be specific: context, affected systems, deadlines, expected outcome.

#### 6) (Optional) Upload attachments

You can attach supporting files (photos, videos, documents) when available for the selected request type.

If you see “Upload Photo/Videos Here (optional)”:

- Drag-and-drop files, or click **Browse Files**
- File types include common images, video, PDF, Office docs, and text formats
- Each file has a maximum size limit, and there is a maximum number of attachments

#### 7) Submit

Click **Submit Ticket**. If there are missing required fields, the form will show validation messages.

After submission, you can track status and updates from your request list.

### Track requests (“My Requests”)

Open `/requests` to view your request list.

What you can do:

- **Search**: filter your visible requests by ticket number, nature, office, status, category, remarks, etc.
- **Open a request**: click its **Control Ticket No.**
- **See key fields**: status, category, assigned IT staff (if applicable), estimated completion date (if provided), and linked QR code (if any).

### Archive

The archive is available from `/requests/archive` (also linked from the requests page).

Use the archive to review older/completed requests.

### Notifications

Open `/notifications` to view in-app notifications.

Common actions:

- View notification details
- Mark a notification as read
- Mark all notifications as read

### Profile and appearance

Profile settings:

- `/settings/profile`: update your profile details and avatar (if enabled in your account)

Appearance:

- `/settings/appearance`: adjust display preferences (e.g., theme) if available

---

## Admin guide

### Admin access and 2FA re-verification

Admins use the same sign-in flow as users, but sensitive admin areas require additional checks:

- **Admin role** is required to access admin pages.
- Some admin sections require **two-factor verification** (2FA challenge). If required, you’ll be redirected to:
  - `/two-factor/challenge`

Note: 2FA verification can expire after a configured time window, and you may be prompted again.

### Admin Dashboard

Admin dashboard: `/admin/dashboard`

This page provides:

- **Stats**:
  - Active in queue (Pending + Ongoing)
  - Assigned to me
  - Total received
- **Quick actions**:
  - QR Generator (`/admin/qr-generator`)
  - Post Management (`/admin/posts`)
  - Enroll ticket (`/admin/enrollments/create`)
  - Superadmin-only: Download consolidated report (see Superadmin section)
- **Requests Queue**:
  - FIFO pending queue (oldest first)
  - Sorting and filtering (control ticket, requester, date filed)
- **Archived Requests** panel:
  - Search archived requests
  - Download report export (completed requests) with optional filters (staff, date range)

### Requests (admin view)

Requests list: `/requests`

When you are an admin, this page shows the **Requests** table with:

- Control ticket number, requester, office, nature, description, status, category, QR code, and an **Action** link.
- A link to **Archive**: `/requests/archive`

Admin “Action” behavior:

- Equipment-and-network request types open the dedicated admin page at:
  - `/requests/{id}/equipment-and-network`
- Other requests open:
  - `/requests/{id}`

### Archived requests and exports

From the Admin Dashboard **Archived Requests** panel, you can export completed requests:

- Export endpoint: `/admin/dashboard/archive-export`
- Optional filters include:
  - archived search text (control ticket or requester)
  - date range (max 365 days)
  - assigned MIS staff

### Request detail pages

Admin detail pages vary by request type:

- **General request details**: `/requests/{ticketRequest}`
- **IT governance (admin-only)**: `/requests/{ticketRequest}/it-governance`
  - Includes a QR-generation action for the request (admin-only).
- **Equipment and network**: `/requests/{ticketRequest}/equipment-and-network`

On these pages, admins typically:

- Review request details and attachments
- Update request handling fields (status, category, remarks, staff assignment, etc.) based on the available UI
- Generate QR (where applicable)

### QR Code Generator

Tool page: `/admin/qr-generator`

Purpose: generate print-ready QR labels with unique IDs in the format `MIS-UID-00001`.

Key behavior:

- **Quantity**: generate 1–500 codes in a batch.
- **Starting number**: cannot be edited; it continues from the last issued UID.
- **Printing**:
  - Use **Print A4 Batch** to print the current batch.
  - Use **Download PDF** to download a print-ready PDF.
- **Safety**: you must print the current batch before generating a new one.
- **Reprints**: use **View previous batches** to recall a batch from the database and reprint/download it.

### Enrollments and Inventory

Admins can manage equipment inventory and enrollment workflows.

Common navigation:

- **Enrollments create**: `/admin/enrollments/create`
- **Inventory list**: `/inventory`
- **Inventory lookup**: `/inventory/lookup/{uniqueId}`
- **Inventory item**: `/inventory/{uniqueId}`
- **Edit inventory item**: `/inventory/{uniqueId}/edit`

Notes:

- Unique IDs follow the pattern `MIS-UID-xxxxx`.
- Inventory items can be archived via `/inventory/{uniqueId}/archive` (admin-only).

### QR Scan / lookup / assignment

Scanner page: `/scan`

Use cases:

- **Scan a QR label** to quickly open the linked inventory record.
- **Manual lookup** if camera scan is unavailable: enter `MIS-UID-00001`.

Important requirement:

- Camera access requires a **secure context** (HTTPS or localhost). If you open the app over plain HTTP (non-localhost), camera scanning may be blocked by the browser.

System endpoints involved:

- Lookup: `/scan/lookup/{uniqueId}`
- Show: `/scan/{uniqueId}`
- Review: `POST /scan/{uniqueId}/review`
- Assign: `PUT /scan/{uniqueId}/assign`

### Status Management (reference values)

Page: `/admin/status`

Admins can centrally manage reference values used across the platform, including:

- Status labels (e.g., Pending, Ongoing, Completed)
- Equipment types (used in enrollment)
- Request categories (e.g., Simple, Complex, Urgent)
- Office designations
- Standard remarks

For each group you can:

- Add a new value
- Edit an existing value
- Remove (soft-remove) or restore a value

### Nature of Request

Page: `/admin/nature-of-request`

Admins can manage the list of request types shown on the ticket submission form.

Actions:

- Add request type
- Edit request type
- Remove / restore request type

Tip: keep labels consistent to avoid confusing historical transactions and reporting.

### User management

User list: `/admin/users`

Use this to manage:

- Account verification for standard users:
  - Toggle **Verified** / **Unverified** (blocks access until approved again)
  - Note: elevated roles (Admin/Superadmin) are always treated as verified.
- Search and filter:
  - Filter by verification status
  - Search by user name or office
- Access individual user management:
  - Open a user and click **Manage**

User detail page: `/admin/users/{id}`

Admins can:

- Update profile details (name, email, phone)
- Update work details (office designation, position title)
- Update role
- Activate/deactivate account
- Set a new password
- Force-verify email (if needed)
- Review the user’s audit trail
- Permanently delete an account (irreversible)

### Audit logs

Page: `/admin/audit-logs`

Use this to review actions recorded by the system (user changes, role updates, verification toggles, and related admin operations).

### Post management (homepage slides)

Page: `/admin/posts`

Purpose: manage homepage profile slides shown on the public landing page (`/`).

Common actions:

- Create a slide: `/admin/posts/create`
- Edit a slide: `/admin/posts/{profileSlide}/edit`
- Archive a slide
- Delete a slide

---

## Superadmin guide

Superadmin-only features are restricted to accounts with the **super_admin** role.

### Nature of Requests report

Page/route: `/admin/reports/nature-of-requests`

Use this report for deeper insights into request types and trends.

### Consolidated report export

Export endpoint (linked from Admin Dashboard quick actions): `/admin/dashboard/nature-monthly-summary-export?year=YYYY`

This generates an Excel summary for **Nature of Requests — monthly summary** (year-based).