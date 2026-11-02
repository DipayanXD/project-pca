# TECHNO MAIN SALT LAKE
## Department of Computer Application (BCA)
### BCAC591 — PHP with MySQL | Semester V
# PROJECT DETAILS REPORT

---

## 1. Project Title
**Campus Resolve — Student Complaint Management System**

---

## 2. Problem Statement
Students frequently encounter facility, infrastructure, and equipment-related problems across campus — such as malfunctioning classroom fans, non-functional lab computers, water cooler breakdowns, and damaged lab equipment — but lack a structured, transparent mechanism to report them and track resolution. Traditional verbal or manual complaint processes often result in untracked tickets, lack of accountability, and zero visibility for students regarding repair timelines. Campus Resolve provides a centralized, role-based platform enabling students to report grievances with location details and monitor progress through an audit timeline, while campus administrators efficiently prioritize, investigate, update, and resolve complaints with verifiable accountability.

---

## 3. User Roles
The system defines two distinct roles, fulfilling the BCAC591 project specification:

* **Student (Complainant):** Registers with student credentials and department; signs in to access a personal workspace dashboard; submits categorized complaints specifying location, description, and file attachments (images/PDF); edits or withdraws pending grievances; monitors the real-time status and chronological audit timeline of their complaints; and manages their profile and credentials.
* **Administrator (Campus Operations / Authority):** Authenticates via a dedicated administrative portal; oversees campus-wide operations through an analytics dashboard (reviewing pending queues, active tickets, resolution rates, and 7-day activity intake); reviews all submitted grievances across departments; transitions ticket statuses (`Pending` &rarr; `In Progress` &rarr; `Resolved` or `Rejected`) with official remarks and audit history; exports reports to CSV; and monitors registered student and administrator accounts.

---

## 4. Module List & Implementation Status

### Mandatory Modules Mapping
| # | Mandatory Module | How It Applies to Campus Resolve | Implementation Status |
|---|---|---|---|
| **1** | **Authentication** | Student registration (`auth/register.php`) with 12 academic departments, separate Student Login (`auth/login.php`), separate Admin Login (`auth/admin_login.php`), and secure Sign Out (`auth/logout.php`). Passwords securely hashed using Bcrypt via `password_hash()`. | **Fully Implemented** |
| **2** | **Role-Based Access** | Two distinct roles (`student`, `admin`) enforced via session authentication and middleware guards (`includes/auth_check_student.php`, `includes/auth_check_admin.php`). Each role has its own dedicated dashboard, navigation sidebar (`includes/student-sidebar.php`, `includes/admin-sidebar.php`), and isolated permissions. | **Fully Implemented** |
| **3** | **Dashboard** | **Student Dashboard** (`student/dashboard.php`) shows real-time personal metrics (total, pending, in-progress, resolved, resolution rate %), time-aware greeting, and recent complaints table. **Admin Dashboard** (`admin/dashboard.php`) displays campus totals, pending action counts, registered student count, a 7-day complaint activity bar chart, and a "Needs Attention" queue. | **Fully Implemented** |
| **4** | **Core Entity CRUD** | The core entity is **Complaint** (Grievance Ticket). Students can lodge new complaints (`student/submit_complaint.php`), view detailed information (`student/complaint_details.php`), edit pending complaints, and delete unreviewed requests. Admins inspect complaints (`admin/complaint_details.php`) and update ticket statuses with official remarks. | **Fully Implemented** |
| **5** | **Listing Page** | **Student Complaints List** (`student/complaints.php`) displays all tickets submitted by the logged-in student with live search and category/status filtering. **Admin Complaints Register** (`admin/complaints.php`) displays all institutional tickets with keyword search, category, status, and sorting filters. | **Fully Implemented** |
| **6** | **Detail View** | Dedicated screen for individual grievances (`student/complaint_details.php` and `admin/complaint_details.php`) displaying complaint code, student metadata, category, location, full description, file attachment preview modal, and chronological event history from `complaint_updates`. | **Fully Implemented** |
| **7** | **Status Workflow** | Every complaint moves through distinct sequential states: `Pending` &rarr; `In Progress` &rarr; `Resolved` (or `Rejected`). State transitions create audit log records in `complaint_updates` along with administrator remarks. Only administrators can transition status (`admin/update_status.php`). | **Fully Implemented** |
| **8** | **Admin Panel** | Dedicated administrative workspace featuring admin authentication, institutional KPIs, 7-day activity intake visual chart, high-priority ticket queue, all-complaints register, CSV export, and user directory oversight (`admin/users.php`). | **Fully Implemented** |
| **9** | **Form Validation** | Comprehensive server-side PHP validation verifies required fields, email formatting, department verification against `get_departments()`, password confirmation match, file MIME types, and CSRF token integrity via `validate_csrf()`. Client-side validation includes interactive password strength metering and error highlighting. | **Fully Implemented** |
| **10** | **Flash Messages** | Centralized session-based flash messaging (`set_flash()`, `get_flash()`) rendered dynamically as client-side animated toast notifications (`window.showToast()`) on sign-in, profile updates, status changes, and complaint creation. | **Fully Implemented** |
| **11** | **Profile Management** | Redesigned Student Profile (`student/profile.php`) and Admin Profile (`admin/profile.php`) interfaces with elevated cover art, avatar badges, live complaint statistics, metadata lists with active indicators, personal information editing, and password security cards. | **Fully Implemented** |

### Optional Modules Included
* **Basic Reports / Counts & Analytics:** Implemented on Admin and Student dashboards via dynamic SQL aggregations calculating complaint volumes by status, resolution percentage rates, student user totals, and a 7-day intake activity breakdown.
* **Audit Trail / Activity Timeline:** Implemented via `complaint_updates` table tracking historical status transitions, timestamped administrator remarks, and responsible admin IDs for complete transparency.
* **File & Evidence Uploads:** Supported by database schema (`attachment` column in `complaints`), server storage (`uploads/complaints/` with direct-execution `.htaccess` protection), and an interactive file preview modal (`includes/attachment-preview.php`, `attachment.php`) for PDF and images up to 10 MB.
* **CSV Data Export:** Implemented via [`admin/export.php`](file:///c:/xampp/htdocs/project-pca/admin/export.php) and [`admin/complaints/export.php`](file:///c:/xampp/htdocs/project-pca/admin/complaints/export.php) generating UTF-8 CSV downloads with full complaint records for institutional reporting.

---

## 5. Database Schema (`campus_resolve`)

The database `campus_resolve` is configured using MySQL with foreign key constraints, cascaded deletions, and `utf8mb4` encoding:

### Table: `users`
Stores student complainants and campus administrators.

| Column | Type / Notes |
|---|---|
| `id` | `INT`, Primary Key, Auto Increment |
| `user_code` | `VARCHAR(20)`, Unique *(e.g. 'ST-2016', 'AD-001')* |
| `name` | `VARCHAR(100)`, Not Null |
| `email` | `VARCHAR(120)`, Unique, Not Null |
| `password` | `VARCHAR(255)`, Not Null — hashed using `password_hash()` (Bcrypt) |
| `role` | `ENUM('student', 'admin')`, Not Null, Default `'student'` |
| `department` | `VARCHAR(100)`, Default `NULL` *(verified against institutional department roster)* |
| `status` | `ENUM('active', 'inactive')`, Not Null, Default `'active'` |
| `created_at` | `DATETIME`, Default `CURRENT_TIMESTAMP` |

### Table: `complaints`
Stores all grievance records submitted across campus.

| Column | Type / Notes |
|---|---|
| `id` | `INT`, Primary Key, Auto Increment |
| `complaint_code` | `VARCHAR(20)`, Unique *(e.g. 'CR-1042')* |
| `user_id` | `INT`, Foreign Key &rarr; `users.id` (ON DELETE CASCADE) |
| `title` | `VARCHAR(200)`, Not Null |
| `category` | `ENUM('Infrastructure', 'IT', 'Facilities', 'Laboratory', 'Other')`, Not Null |
| `location` | `VARCHAR(150)`, Not Null *(e.g. 'Block B · Room 204')* |
| `description` | `TEXT`, Not Null |
| `attachment` | `VARCHAR(255)`, Nullable *(relative path to uploaded evidence file in `uploads/complaints/`)* |
| `status` | `ENUM('Pending', 'In Progress', 'Resolved', 'Rejected')`, Default `'Pending'` |
| `created_at` | `DATETIME`, Default `CURRENT_TIMESTAMP` |
| `updated_at` | `DATETIME`, Default `CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` |

### Table: `complaint_updates`
Stores the chronological progress log and administrator remarks for each complaint.

| Column | Type / Notes |
|---|---|
| `id` | `INT`, Primary Key, Auto Increment |
| `complaint_id` | `INT`, Foreign Key &rarr; `complaints.id` (ON DELETE CASCADE) |
| `admin_id` | `INT`, Nullable, Foreign Key &rarr; `users.id` (ON DELETE SET NULL) |
| `status` | `VARCHAR(50)`, Not Null *(e.g. 'Submitted', 'Under Review', 'In Progress', 'Resolved', 'Rejected')* |
| `remark` | `TEXT`, Not Null |
| `created_at` | `DATETIME`, Default `CURRENT_TIMESTAMP` |

---

## 6. Page List & Implementation Status

| # | Page / Screen | Accessible By | Purpose | Status |
|---|---|---|---|---|
| **1** | **Public Landing Page** (`index.php`) | Public | Introduces the system, explains the 3-step workflow, displays live tracking demo, and directs users to role sign-in. | **Implemented** |
| **2** | **Student Sign In** (`auth/login.php`) | Public | Student credential authentication with CSRF protection, password visibility toggle, and session creation. | **Implemented** |
| **3** | **Admin Sign In** (`auth/admin_login.php`) | Public | Dedicated administrative authentication portal restricted strictly to accounts with `role = 'admin'`. | **Implemented** |
| **4** | **Student Registration** (`auth/register.php`) | Public | New student onboarding with academic department selection (12 departments), Bcrypt password hashing, and auto-generated user code. | **Implemented** |
| **5** | **Sign Out Handler** (`auth/logout.php`) | Authenticated | Safely destroys session, unsets session cookies, and redirects to login with flash confirmation. | **Implemented** |
| **6** | **Student Dashboard** (`student/dashboard.php`) | Student | Student workspace displaying personal KPI counters (total, pending, in-progress, resolved), resolution rate %, and recent complaints table. | **Implemented** |
| **7** | **Submit Complaint** (`student/submit_complaint.php`) | Student | Grievance lodging form with category selection, location, description, draft auto-restore, and secure file attachment upload. | **Implemented** |
| **8** | **My Complaints Listing** (`student/complaints.php`) | Student | Filterable, sortable, and searchable register of complaints lodged by the student with pending deletion action. | **Implemented** |
| **9** | **Complaint Detail View** (`student/complaint_details.php`) | Student | Single complaint view showing issue breakdown, location, evidence modal preview, and chronological audit timeline. | **Implemented** |
| **10** | **Student Profile** (`student/profile.php`) | Student | Redesigned profile management interface with metric boxes, department selection, and password change security card. | **Implemented** |
| **11** | **Admin Dashboard** (`admin/dashboard.php`) | Admin | Centralized administrative analytics hub with campus-wide statistics, resolution rate, 7-day intake chart, and "Needs Attention" queue. | **Implemented** |
| **12** | **All Complaints Register** (`admin/complaints.php`) | Admin | Institutional complaints management table with real-time search, category filtering, and status filtering. | **Implemented** |
| **13** | **Admin Complaint Review** (`admin/complaint_details.php`) | Admin | Detailed ticket inspection screen with controls to transition status, enter official administrative remarks, and view attachments. | **Implemented** |
| **14** | **Update Status Action** (`admin/update_status.php`) | Admin | POST handler validating CSRF, updating complaint status, and appending an audit record in `complaint_updates`. | **Implemented** |
| **15** | **User Directory** (`admin/users.php`) | Admin | Overview of registered students and administrators across departments with role filters and account statuses. | **Implemented** |
| **16** | **Admin Profile** (`admin/profile.php`) | Admin | Professional administrator profile view and credential update interface. | **Implemented** |
| **17** | **CSV Export Endpoint** (`admin/export.php`) | Admin | Downloads CSV spreadsheet report of complaints for institutional recordkeeping. | **Implemented** |

---

## 7. Project File Structure

```text
project-pca/
├── admin/
│   ├── complaint_details.php       # Admin complaint inspection & status transition view
│   ├── complaints.php              # Institutional complaints register with search & filters
│   ├── dashboard.php               # Operations dashboard: campus KPIs, 7-day chart, active queue
│   ├── export.php                  # CSV export endpoint
│   ├── profile.php                 # Administrator profile view & credential management
│   ├── update_status.php           # Status transition POST handler with audit trail tracking
│   └── users.php                   # Registered student & administrator directory
│
├── assets/
│   ├── css/
│   │   ├── attachment-preview.css  # Styles for evidence attachment modal
│   │   └── styles.css              # Application design system: tokens, typography, layouts, badges
│   └── js/
│       ├── app.js                  # Client utilities: mobile nav, dropdowns, toast alerts, password toggle
│       └── attachment-preview.js   # Attachment modal viewer script
│
├── auth/
│   ├── admin_login.php             # Dedicated administrative sign-in portal
│   ├── login.php                   # Student sign-in portal with session initialization
│   ├── logout.php                  # Secure session destruction and redirect
│   └── register.php                # Student account registration with 12-department dropdown
│
├── config/
│   └── db_connect.php              # PDO MySQL connection with error handling & character set setup
│
├── database/
│   ├── schema.sql                  # DDL: users, complaints, complaint_updates tables
│   └── seed.sql                    # Initial seed data: campus administrators, sample students & complaints
│
├── documentation/
│   ├── BCAC591 Semester Project.pdf# BCA Semester V project syllabus & guidelines
│   ├── PROJECT_DETAILS_REPORT.html # Formatted project documentation report
│   └── PROJECT_DETAILS_REPORT.pdf  # Final compiled PDF project report
│
├── includes/
│   ├── admin-sidebar.php           # Navigation sidebar for administrator workspace
│   ├── attachment-preview.php      # Reusable attachment preview component
│   ├── attachments.php             # Attachment validation and storage helper
│   ├── auth.php                    # Session handling, role helpers, and CSRF utilities
│   ├── auth_check_admin.php        # Middleware: enforces authenticated administrator role
│   ├── auth_check_student.php      # Middleware: enforces authenticated student role
│   ├── footer.php                  # Shared app footer and dynamic toast notification trigger
│   ├── header.php                  # Shared top app header with user dropdown & role badge
│   ├── helpers.php                 # Utilities: escaping (e()), status badges, get_departments()
│   └── student-sidebar.php         # Navigation sidebar for student workspace
│
├── student/
│   ├── complaint_details.php       # Detailed single complaint view with audit timeline
│   ├── complaints.php              # Student's personal complaints listing, search, and delete
│   ├── dashboard.php               # Student home: personal metrics, recent tickets, quick links
│   ├── profile.php                 # Student profile, department assignment, and password change
│   └── submit_complaint.php        # Grievance lodging & editing form with evidence upload
│
├── tests/
│   └── attachment-access.php       # Attachment access and permission test suite
│
├── uploads/
│   ├── .htaccess                   # Execution block prevention for uploaded files
│   └── complaints/                 # Secure storage directory for complaint evidence
│
├── attachment.php                  # Secure attachment delivery endpoint
├── icons.svg                       # Shared SVG icon symbol sprite
├── index.php                       # Public landing page with system walkthrough & role entry
└── README.md                       # Project documentation & current status report
```

---

## 8. Credentials & Testing Guide

### Administrator Accounts (Password for all: `admin` / Priya: `Admin@123`)
| Name | Admin Code | Email Format (`name@admin.com`) | Alias Format | Password |
| :--- | :--- | :--- | :--- | :--- |
| **Dipayan Das** | `AD-002` | `dipayan@admin.com` | `dipayan.das@admin.com` | `admin` |
| **Enakshi Addhya** | `AD-003` | `enakshi@admin.com` | `enakshi.addhya@admin.com` | `admin` |
| **Himesh Paul** | `AD-004` | `himesh@admin.com` | `himesh.paul@admin.com` | `admin` |
| **Priya Sharma** | `AD-001` | `admin@campus.edu` | — | `Admin@123` |

### Student Account (Password: `Student@123`)
| Name | Student Code | Email | Password |
| :--- | :--- | :--- | :--- |
| **Alex Johnson** | `ST-2016` | `alex.johnson@campus.edu` | `Student@123` |

---

## 9. How to Run the Application

1. **Environment Setup**:
   * Start **Apache** and **MySQL** in the XAMPP Control Panel.
2. **Database Initialization**:
   * Import [`database/schema.sql`](file:///c:/xampp/htdocs/project-pca/database/schema.sql) in phpMyAdmin or via MySQL CLI.
   * Import [`database/seed.sql`](file:///c:/xampp/htdocs/project-pca/database/seed.sql) to populate initial administrators, students, and sample complaints.
3. **Accessing the Portal**:
   * Open your browser and navigate to:
     * Public Homepage: `http://localhost/project-pca/index.php`
     * Student Sign In: `http://localhost/project-pca/auth/login.php`
     * Administrator Sign In: `http://localhost/project-pca/auth/admin_login.php`
