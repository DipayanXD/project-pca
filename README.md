# TECHNO MAIN SALT LAKE
### Department of Computer Application (BCA)
#### BCAC591 — PHP with MySQL | Semester V
# PROJECT DETAILS REPORT

---

## 1. Project Title
**Campus Resolve — Student Complaint Management System**

---

## 2. Problem Statement
Students regularly encounter facility, infrastructure, and laboratory-related issues across campus — such as malfunctioning classroom fans, defective lab workstations, drinking water cooler breakdowns, and damaged experiment equipment — but there is currently no organized, transparent way to lodge grievances or track their resolution. Verbal or paper-based complaints frequently result in untracked tickets, lack of administrative accountability, and zero visibility for students regarding when repairs will occur. Campus Resolve provides a centralized, role-based platform where students can submit categorized complaints with precise campus locations and track progress through a transparent audit timeline, while campus administrators prioritize issues, record timestamped remarks, manage status transitions, and resolve grievances with verifiable accountability.

---

## 3. User Roles
The system has two roles, matching the minimum requirement set in the project notice:

* **Student (Complainant)** — Registers with verified student credentials and academic department; logs in to access a personal workspace dashboard; views personal complaint metrics and resolution rates; submits new complaints with category, campus location, description, and optional image attachment; tracks real-time status and historical timeline of their own complaints; and manages personal profile details.
* **Admin (Campus Operations / Authority)** — Logs in separately via dedicated administrator authentication; oversees campus-wide operations through an analytics dashboard (reviewing pending queues, active tickets, registered student count, and 7-day activity volume); reviews all submitted grievances across departments; updates ticket statuses (*Pending &rarr; In Progress &rarr; Resolved / Rejected*) with official remarks recorded in the audit history; and monitors student accounts.

---

## 4. Module List — Mapped to This Project

### Mandatory Modules Mapping
| # | Mandatory Module | How It Applies to This Project |
|---|---|---|
| 1 | **Authentication** | Student registration with validation (name, email, department dropdown, password match/strength) and login; separate Administrator login. Passwords securely hashed using `password_hash()`. |
| 2 | **Role-Based Access** | Two roles — Student and Administrator — each with a dedicated dashboard, distinct permission barriers, and session checks (`require_student()` and `require_admin()`). |
| 3 | **Dashboard** | Student dashboard displays personal complaint metrics (Total, Pending, In Progress, Resolved) and resolution rate %. Admin dashboard shows campus-wide counts, active triage queue, and 7-day activity chart. |
| 4 | **Core Entity CRUD** | The core entity is **Complaint** (supported by **Complaint Updates**). Students Create and View complaints. Admins View, Update status/remarks, and manage the full grievance lifecycle. |
| 5 | **Listing Page** | Complaints listing view — student personal complaints table and admin campus-wide registry with Search (by complaint code/title) and Filter (by category, department, and status). |
| 6 | **Detail View** | Clicking a complaint opens a comprehensive detail view displaying complaint code, student info, department, location, description, attached photo, and full chronological timeline of admin remarks. |
| 7 | **Status Workflow** | Each complaint moves through 4 states: *Pending &rarr; In Progress &rarr; Resolved* (or *Rejected*). Each transition creates a timestamped record in `complaint_updates`. Only Admin can update status. |
| 8 | **Admin Panel** | A separate Admin area providing centralized visibility over all complaints across departments, with action forms to assign remarks, advance status, and monitor student accounts. |
| 9 | **Form Validation** | Complaint submission form validates title, category whitelist, location, description length, and attachment format; registration validates email uniqueness, department list, and password complexity in PHP, not just HTML5. |
| 10 | **Flash Messages** | Session-based flash message system (`set_flash()`, `get_flash()`) displaying alert banners on actions: complaint logged, status transition saved, profile updated, login failure, etc. |
| 11 | **Profile Management** | Students and Admins can view and edit their own name, contact email, assigned department, and change account passwords. |

### Optional Modules Included
* **Chronological Audit Timeline / Event History Tracking** — Every status transition and administrative remark generates an immutable audit record in `complaint_updates`, giving both students and administrators complete, accountable transparency.
* **Campus Operations Analytics & Visual Trends** — Admin dashboard calculates real-time resolution rates, displays status breakdown metrics, and plots past 7-day complaint activity frequency.
* **File Handling & Evidence Attachment** — Students can attach image/document evidence of damaged infrastructure or equipment when submitting a complaint; files are validated, securely stored server-side in `uploads/complaints/`, and previewed in the detail view.

---

## 5. Database Tables (Schema)

The following is the database schema implemented in MySQL using InnoDB engine with foreign key constraints and UTF-8 encoding.

### Table: `users`
| Column | Type / Notes |
|---|---|
| `id` | INT, Primary Key, Auto Increment |
| `user_code` | VARCHAR(20), Unique — e.g. ST-2016, AD-001 |
| `name` | VARCHAR(100), Not Null |
| `email` | VARCHAR(120), Unique, Not Null |
| `password` | VARCHAR(255), Not Null — stored using `password_hash()` |
| `role` | ENUM('student', 'admin'), Not Null, Default 'student' |
| `department` | VARCHAR(100), Default Null — academic department affiliation |
| `status` | ENUM('active', 'inactive'), Not Null, Default 'active' |
| `created_at` | DATETIME, Not Null, Default CURRENT_TIMESTAMP |

### Table: `complaints`
| Column | Type / Notes |
|---|---|
| `id` | INT, Primary Key, Auto Increment |
| `complaint_code` | VARCHAR(20), Unique — e.g. CR-1042 |
| `user_id` | INT, Foreign Key &rarr; `users.id` (ON DELETE CASCADE) |
| `title` | VARCHAR(200), Not Null — short summary of grievance |
| `category` | ENUM('Infrastructure', 'IT', 'Facilities', 'Laboratory', 'Other'), Not Null |
| `location` | VARCHAR(150), Not Null — e.g. Block B Room 204, Central Library |
| `description` | TEXT, Not Null — comprehensive issue description |
| `attachment` | VARCHAR(255), Nullable — server storage path of uploaded evidence |
| `status` | ENUM('Pending', 'In Progress', 'Resolved', 'Rejected'), Default 'Pending' |
| `created_at` | DATETIME, Not Null, Default CURRENT_TIMESTAMP |
| `updated_at` | DATETIME, Not Null, Default CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP |

### Table: `complaint_updates`
| Column | Type / Notes |
|---|---|
| `id` | INT, Primary Key, Auto Increment |
| `complaint_id` | INT, Foreign Key &rarr; `complaints.id` (ON DELETE CASCADE) |
| `admin_id` | INT, Nullable, Foreign Key &rarr; `users.id` (ON DELETE SET NULL) |
| `status` | VARCHAR(50), Not Null — status state (Submitted, Under Review, In Progress, Resolved, Rejected) |
| `remark` | TEXT, Not Null — official administrative remark or resolution notes |
| `created_at` | DATETIME, Not Null, Default CURRENT_TIMESTAMP |

---

## 6. Page List

| # | Page / Screen | Accessible By | Purpose | Implementation Status |
|---|---|---|---|:---:|
| 1 | **Landing Page** (`index.php`) | Public | System introduction, workflow showcase, and role-based entry links | **Implemented** |
| 2 | **Student Sign In** (`auth/login.php`) | Public | Student authentication with email, password verification, and CSRF check | **Implemented** |
| 3 | **Admin Sign In** (`auth/admin_login.php`) | Public | Dedicated administrative portal sign in with admin role validation | **Implemented** |
| 4 | **Register** (`auth/register.php`) | Public | Account registration with department dropdown and password validation | **Implemented** |
| 5 | **Sign Out** (`auth/logout.php`) | Student / Admin | Terminates session, deletes cookie, and redirects to login | **Implemented** |
| 6 | **Student Dashboard** (`student/dashboard.php`) | Student | Personal metrics (Total, Pending, In Progress, Resolved), rate %, recent tickets | **Implemented** |
| 7 | **Admin Dashboard** (`admin/dashboard.php`) | Admin | Campus-wide KPI counters, active triage queue, 7-day activity chart | **Implemented** |
| 8 | **Submit Complaint** (`student/submit_complaint.php`) | Student | Grievance submission form with category, location, and attachment upload | **Partially Implemented** |
| 9 | **My Complaints** (`student/complaints.php`) | Student | Searchable, filterable list of student's own submitted complaints | **Planned** |
| 10 | **Complaint Detail** (`student/complaint_details.php`) | Student / Admin | Complete issue details and chronological timeline of administrative remarks | **Planned** |
| 11 | **Student Profile** (`student/profile.php`) | Student | View student ID, department info, and update password / contact info | **Partially Implemented** |
| 12 | **All Complaints** (`admin/complaints/list.php`) | Admin | Master campus grievances listing with category, department & status filters | **Planned** |
| 13 | **Admin Review** (`admin/complaints/detail.php`) | Admin | Inspect complaint details, review attached evidence, and enter remarks | **Planned** |
| 14 | **Update Status** (`admin/complaints/update_status.php`) | Admin | Backend handler saving status transition and logging timeline record | **Planned** |
| 15 | **User Management** (`admin/users.php`) | Admin | Directory of registered students across departments with status controls | **Planned** |
| 16 | **Admin Profile** (`admin/profile.php`) | Admin | View administrator account details and manage credentials | **Partially Implemented** |

---

## 7. File Structure

```
campus-resolve/
│
├── config/
│   └── db_connect.php            # MySQL database connection using PDO (prepared statements, utf8mb4)
│
├── includes/
│   ├── auth.php                  # Session management, role guards (require_student, require_admin), CSRF
│   ├── helpers.php               # HTML escaping, status badges, date formatters, departments list
│   ├── auth_check_student.php    # Guard middleware for student area
│   ├── auth_check_admin.php      # Guard middleware for admin area
│   ├── header.php                # Common top navigation bar
│   ├── footer.php                # Common document footer
│   ├── student-sidebar.php       # Navigation sidebar for student workspace
│   └── admin-sidebar.php         # Navigation sidebar for admin workspace
│
├── assets/
│   ├── css/
│   │   └── styles.css            # Central stylesheet (design tokens, components, responsive layout)
│   └── js/
│       └── app.js                # Client-side validation, UI interactions, and mobile menu toggle
│
├── auth/
│   ├── register.php              # Student account registration with input validation
│   ├── login.php                 # Student portal sign in
│   ├── admin_login.php           # Dedicated administrator sign in
│   └── logout.php                # Session destruction and sign out
│
├── student/
│   ├── dashboard.php             # Student landing: KPI cards, resolution rate %, recent 5 complaints
│   ├── submit_complaint.php      # Grievance submission form with evidence upload
│   ├── complaints.php            # [Planned] Personal complaints listing and status filter
│   ├── complaint_details.php     # [Planned] Detail view with chronological update timeline
│   └── profile.php               # Student profile and contact settings
│
├── admin/
│   ├── dashboard.php             # Operations dashboard: campus KPIs, 7-day chart, active queue
│   ├── complaints/
│   │   ├── list.php              # [Planned] Master campus complaints table with search & filters
│   │   ├── detail.php            # [Planned] Admin complaint inspection & remark entry
│   │   └── update_status.php     # [Planned] POST handler for status transitions & update timeline
│   ├── users.php                 # [Planned] Directory of registered students across departments
│   └── profile.php               # Administrator profile management
│
├── database/
│   ├── schema.sql                # DDL: users, complaints, complaint_updates tables
│   └── seed.sql                  # Initial dataset: default admin, test students & sample complaints
│
├── uploads/
│   └── complaints/               # [Planned] Storage directory for uploaded complaint evidence
│
└── index.php                     # Public landing page: system overview and role entry
```
