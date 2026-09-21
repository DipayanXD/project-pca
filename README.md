# TECHNO MAIN SALT LAKE
### Department of Computer Application (BCA)
**BCAC501 / BCAC591 — PHP with MySQL | Semester V**

---

# PROJECT DETAILS REPORT

---

### 1. Project Title
**Campus Resolve — Student Complaint Management System**

---

### 2. Problem Statement
In academic institutions, students frequently encounter campus-related issues ranging from faulty laboratory equipment and classroom electrical failures to IT infrastructure breakdowns and sanitary concerns. Currently, grievances are handled through unorganized channels such as informal verbal complaints, lost paper slips, or buried email threads, resulting in severe delays, lack of transparency, and zero accountability. **Campus Resolve** is a centralized, role-based web application developed using PHP and MySQL that enables students to formally lodge, track, and monitor complaints in real time, while campus administrators efficiently review, assign, update, and resolve issues with complete timeline accountability.

---

### 3. User Roles

The system is architected around two primary user roles, strictly adhering to role-based access control (RBAC):

*   **Student (Complainant / Grievant):**
    *   Registers and authenticates via secure login.
    *   Logs new complaints by selecting category (Infrastructure, IT, Facilities, Laboratory, Other), specific location, detailed description, and supporting attachments.
    *   Tracks the real-time status and timeline updates of their submitted complaints (`Pending` &rarr; `In Progress` / `Under Review` &rarr; `Resolved` / `Rejected`).
    *   Views their personalized dashboard with key metrics (total submitted, active complaints, resolved count, resolution rate).
    *   Manages their personal profile, department details, and account credentials.

*   **Admin (Campus Authority / Grievance Officer):**
    *   Authenticates securely through a dedicated administrative portal.
    *   Accesses a high-level command dashboard featuring campus-wide statistics (pending reviews, active investigations, resolved issues, student count, and resolution success rates).
    *   Views and manages all complaints across all departments and campus categories.
    *   Performs core entity management: reviews complaint details, updates status, and appends official administrative remarks/notes to the complaint timeline.
    *   Filters, searches, and monitors unresolved or high-priority complaints, and manages student records.

---

### 4. Module List — Mapped to This Project

Every mandatory module specified in the university project guidelines has been mapped specifically to **Campus Resolve**, along with relevant optional modules:

| # | Mandatory Module | How It Applies to This Project |
|---|---|---|
| **1** | **Authentication** | Student registration, student login, administrative login, and secure session termination (logout). Passwords are cryptographically hashed using PHP's native `password_hash()` with `PASSWORD_DEFAULT` (Bcrypt). |
| **2** | **Role-Based Access** | Strict session-based access control segregating `student` and `admin` roles via middleware guards (`auth_check_student.php`, `auth_check_admin.php`). Unauthorized access attempts automatically redirect to respective login portals. |
| **3** | **Dashboard** | **Student Dashboard:** Real-time metrics of personal grievances (total lodged, pending, in progress, resolved, resolution rate percentage) and recent ticket history.<br>**Admin Dashboard:** Campus-wide analytics including total complaints, pending review queue, active in-progress investigations, resolved rate, registered student count, and weekly activity distribution. |
| **4** | **Core Entity CRUD** | The primary entity is **Complaint**. Students can Create (lodge) and Read (view status and details). Administrators can Read (all records), Update (status transition and timeline remarks), and Delete / Moderate invalid entries. |
| **5** | **Listing Page** | Dedicated complaints listing interface featuring keyword search (by complaint code, title, student name), category filtering (Infrastructure, IT, Facilities, Laboratory, Other), and status filtering (`Pending`, `In Progress`, `Resolved`, `Rejected`). |
| **6** | **Detail View** | Comprehensive single-complaint inspection page (`complaint-details.php` / `admin-complaint-details.php`) displaying complaint code, student identity, timestamp, category, exact location, detailed narrative, uploaded proof, and a sequential chronological timeline of administrative actions. |
| **7** | **Status Workflow** | Formal multi-stage lifecycle: **Pending** &rarr; **In Progress / Under Review** &rarr; **Resolved** (or **Rejected** with stated cause). Every status change logs an entry in `complaint_updates` with the administrator ID and timestamp. |
| **8** | **Admin Panel** | Comprehensive control center allowing administrators to oversee campus grievances, filter tickets requiring immediate attention, post official remediation updates, and inspect user profiles. |
| **9** | **Form Validation** | Robust server-side PHP validation enforcing required fields, valid institutional email formats (`FILTER_VALIDATE_EMAIL`), minimum password complexity, file upload type/size checks, and Cross-Site Request Forgery (CSRF) token verification. Client-side HTML5 validation is implemented as an immediate visual aid. |
| **10** | **Flash Messages** | User feedback mechanism implemented using PHP `$_SESSION` flash storage (`set_flash()` / `get_flash()`) that dynamically renders dismissible success, error, and warning banners across requests. |
| **11** | **Profile Management** | Profile view and editing interface allowing students and administrators to inspect account details, change departmental affiliation, and update information. *(Developed and staged in `temp_profiles/` for subsequent release)* |

#### Optional Modules Included:
*   **File / Image Upload:** Complainants can attach image evidence (JPEG, PNG, WebP) or document proofs when lodging grievances to assist maintenance teams in assessing physical damage or technical issues.
*   **Timeline & Comment Threads (`complaint_updates`):** An auditable update log tracking every administrative response, inspection schedule, or status transition linked to each complaint.
*   **Basic Reports & Analytics Counts:** Real-time metric cards and analytical summaries (resolution percentages, category breakdowns, and weekly grievance velocity) rendered on dashboards.

---

### 5. Database Tables (Current State & Schema)

The database `campus_resolve` is configured with strict foreign key constraints, UTF8mb4 collation, and optimal indexes:

#### Table: `users`
| Column | Type / Constraints | Notes |
|---|---|---|
| `id` | `INT`, Primary Key, Auto Increment | Unique internal user identifier |
| `user_code` | `VARCHAR(20)`, Unique, Not Null | Institutional ID (e.g., `ST-2016`, `AD-001`) |
| `name` | `VARCHAR(100)`, Not Null | Full name of student or administrator |
| `email` | `VARCHAR(120)`, Unique, Not Null | Institutional email address |
| `password` | `VARCHAR(255)`, Not Null | Secure password hash (`password_hash()`) |
| `role` | `ENUM('student', 'admin')`, Default `'student'` | Access role for authorization |
| `department` | `VARCHAR(100)`, Nullable | Student's academic department (selected from 12 institutional departments via dropdown); `'Administration'` for administrators |
| `status` | `ENUM('active', 'inactive')`, Default `'active'` | Account state flag |
| `created_at` | `DATETIME`, Default `CURRENT_TIMESTAMP` | Account creation timestamp |

##### Supported Academic Departments:
Students select their department from a standardized dropdown list during registration (`auth/register.php`), strictly validated on the server against `get_departments()`:
1. Computer Science & Engineering (CSE)
2. Information Technology (IT)
3. Computer Applications (BCA)
4. Electronics & Communication Engineering (ECE)
5. Electrical Engineering (EE)
6. Mechanical Engineering (ME)
7. Civil Engineering
8. Electronics & Instrumentation Engineering (EIE)
9. Food Technology
10. Business Management
11. Media Science
12. Hospitality Management

#### Table: `complaints`
| Column | Type / Constraints | Notes |
|---|---|---|
| `id` | `INT`, Primary Key, Auto Increment | Unique internal complaint identifier |
| `complaint_code` | `VARCHAR(20)`, Unique, Not Null | Formatted tracking code (e.g., `CR-1042`) |
| `user_id` | `INT`, Foreign Key &rarr; `users.id` (ON DELETE CASCADE) | Student who lodged the grievance |
| `title` | `VARCHAR(200)`, Not Null | Summary title of the grievance |
| `category` | `ENUM('Infrastructure', 'IT', 'Facilities', 'Laboratory', 'Other')` | Operational category |
| `location` | `VARCHAR(150)`, Not Null | Physical location (e.g., *Block B · Room 204*) |
| `description` | `TEXT`, Not Null | Comprehensive description of the issue |
| `attachment` | `VARCHAR(255)`, Nullable | Stored file path for uploaded photo/document proof |
| `status` | `ENUM('Pending', 'In Progress', 'Resolved', 'Rejected')` | Current lifecycle state (Default: `'Pending'`) |
| `created_at` | `DATETIME`, Default `CURRENT_TIMESTAMP` | Submission timestamp |
| `updated_at` | `DATETIME`, Default `CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` | Last modification timestamp |

#### Table: `complaint_updates` (Timeline & History)
| Column | Type / Constraints | Notes |
|---|---|---|
| `id` | `INT`, Primary Key, Auto Increment | Unique update record identifier |
| `complaint_id` | `INT`, Foreign Key &rarr; `complaints.id` (ON DELETE CASCADE) | Referenced complaint |
| `admin_id` | `INT`, Nullable, Foreign Key &rarr; `users.id` (ON DELETE SET NULL) | Admin who posted the update |
| `status` | `VARCHAR(50)`, Not Null | Transitioned status label (e.g., *In Progress*) |
| `remark` | `TEXT`, Not Null | Administrative remark or inspection note |
| `created_at` | `DATETIME`, Default `CURRENT_TIMESTAMP` | Update timestamp |

---

### 6. Page List

| # | Page / Screen | Accessible By | Purpose |
|---|---|---|---|
| **1** | **Landing Page** (`index.php`) | Public | Modern introductory portal describing system features, workflow overview, and navigation entry points. |
| **2** | **Student Login** (`auth/login.php`) | Public | Secure credential authentication for enrolled students with CSRF verification and remember-me state. |
| **3** | **Admin Login** (`auth/admin_login.php`) | Public | Dedicated administrative portal for campus authorities and grievance officers. |
| **4** | **Student Registration** (`auth/register.php`) | Public | Account registration capturing institutional ID, full name, email, password, and department selection via a dropdown listing 12 academic departments. |
| **5** | **Logout Handler** (`auth/logout.php`) | Authenticated | Safely destroys sessions, clears auth cookies, and redirects with flash feedback. |
| **6** | **Student Dashboard** (`student/dashboard.php`) | Student | Personalized student hub showing metrics (open, in-progress, resolved complaints, resolution rate) and recent activity. |
| **7** | **Submit Complaint** (`student/submit_complaint.php`) | Student | Form to file a grievance with title, category, location, detailed description, and evidence upload. |
| **8** | **My Complaints Listing** (`student/complaints.php`) | Student | Searchable, filterable list of all complaints filed by the logged-in student with real-time status badges. |
| **9** | **Student Complaint Details** (`student/complaint_detail.php`) | Student | Detailed inspection of a specific grievance with issue background, attached media, and chronological progress timeline. |
| **10** | **Student Profile** (`temp_profiles/student/profile.php`) | Student | Staged module: Interface to view student credentials, update full name, and select department from dropdown. *(Staged for subsequent release)* |
| **11** | **Admin Dashboard** (`admin/dashboard.php`) | Admin | Centralized analytics center displaying institutional grievance totals, pending queues, active investigations, and student counts. |
| **12** | **Admin Complaints Manager** (`admin/complaints/list.php`) | Admin | Complete institutional complaints register with search, status filters, category filters, and direct action triggers. |
| **13** | **Admin Complaint Inspection & Action** (`admin/complaints/detail.php`) | Admin | Single complaint review screen with controls to transition statuses (`Under Review`, `In Progress`, `Resolved`, `Rejected`) and record official notes. |
| **14** | **User Management** (`admin/users/list.php`) | Admin | Directory of registered student accounts, departments, account statuses, and grievance history counts. |
| **15** | **Admin Profile** (`temp_profiles/admin/profile.php`) | Admin | Staged module: Administrative account management and credential configuration. *(Staged for subsequent release)* |

---

### 7. File Structure

```text
campus-resolve/
│
├── config/
│   └── db_connect.php              # PDO MySQL database connection with error handling & charset config
│
├── includes/
│   ├── auth.php                    # Core session management, authentication helpers, and CSRF utilities
│   ├── auth_check_student.php      # Guard middleware: restricts access to authenticated students
│   ├── auth_check_admin.php        # Guard middleware: restricts access to authenticated administrators
│   ├── helpers.php                 # Utility library: XSS sanitization (e()), flash messages, status badges, get_departments()
│   ├── header.php                  # Global HTML <head>, navigation bar, and user session header
│   ├── footer.php                  # Common HTML footer and script references
│   ├── student-sidebar.php         # Responsive sidebar navigation for student workspace
│   └── admin-sidebar.php           # Responsive sidebar navigation for administrative workspace
│
├── assets/
│   ├── css/
│   │   └── styles.css              # Unified design system: dark/light theme tokens, layout, typography, animations
│   ├── js/
│   │   └── app.js                  # Progressive enhancement: toasts, modal dialogs, search filters, form handling
│   └── icons.svg                   # Optimized SVG sprite system for UI icons
│
├── auth/
│   ├── register.php                # Student account registration with 12-department dropdown, validation & hashing
│   ├── login.php                   # Student sign-in portal with session initialization
│   ├── admin_login.php             # Dedicated administrative sign-in portal
│   └── logout.php                  # Destroys session, cleans cookies, and redirects to public portal
│
├── student/
│   ├── dashboard.php               # Student landing page with live personal statistics and recent tickets
│   ├── submit_complaint.php        # Grievance lodging form with category picker and file attachment
│   ├── complaints.php              # Filterable listing of all tickets submitted by student
│   └── complaint_detail.php        # Detailed view of single ticket with full administrative timeline
│
├── admin/
│   ├── dashboard.php               # High-level analytics dashboard with grievance counts and action queue
│   ├── complaints/
│   │   ├── list.php                # All campus grievances with category/status filters & search
│   │   ├── detail.php              # Detailed complaint review & status/remark update handler
│   │   └── update_status.php       # Backend POST endpoint processing status transitions & timeline logs
│   └── users/
│       └── list.php                # Student user directory and account oversight
│
├── temp_profiles/                  # Staged profile modules reserved for subsequent release
│   ├── student/
│   │   └── profile.php             # Student profile view, name modification, and department dropdown update
│   └── admin/
│       └── profile.php             # Administrator profile and credential management
│
├── database/
│   ├── schema.sql                  # Database definition: users (with department), complaints, complaint_updates
│   └── seed.sql                    # Initial seed data for test accounts (Admin & Student) and demo grievances
│
├── uploads/
│   └── complaints/                 # Secure storage directory for uploaded complaint evidence files
│
└── index.php                       # Public home / landing page with feature showcase and quick links
```

---

### 8. Implementation Highlights & Current Progress

1. **Database & Seeding Architecture:**
   * Fully configured MySQL database schema with foreign key cascades and referential integrity.
   * Standardized `department` field across the `users` table, pre-populated and migrated with recognized institutional department tags.
   * Pre-populated seed dataset (`database/seed.sql`) containing verified administrative accounts and multi-department student test accounts with secure Bcrypt password hashes.

2. **Department Management Architecture:**
   * Centralized `get_departments()` helper in `includes/helpers.php` establishing a single source of truth for the 12 institutional academic departments.
   * Student registration enforces dropdown selection rather than manual text input, accompanied by strict server-side validation against the authorized roster.
   * Profile management system built and safely staged in `temp_profiles/` for activation in an upcoming project milestone.

3. **Security & Data Integrity:**
   * **Password Hashing:** Passwords are never stored in plaintext; all entries leverage PHP `password_hash()` using default Bcrypt work factors.
   * **SQL Injection Prevention:** 100% prepared PDO statements (`$pdo->prepare(...)` and `$stmt->execute(...)`) used throughout queries.
   * **Cross-Site Scripting (XSS) Prevention:** Output sanitization helper `e($string)` wrapping `htmlspecialchars()` across all dynamic views.
   * **CSRF Protection:** Cryptographic session tokens generated via `csrf_token()` and verified with `validate_csrf()`.

4. **User Interface & Experience (Prototype 4 Integration):**
   * Premium design system using custom CSS custom properties (variables), high-contrast typography, responsive sidebars, micro-animations, and accessible color-coded status badges.
   * Fully responsive layouts tested across mobile, tablet, and desktop viewports.
