/* Campus Resolve prototype — static, intentionally framework-free for easy PHP integration later. */
const state = {
  route: "landing",
  role: "student",
  user: { name: "Alex Johnson", email: "alex.johnson@campus.edu" },
  filters: {
    search: "",
    status: "All statuses",
    category: "All categories",
    sort: "Newest first",
  },
  detailId: "CR-1042",
  showMenu: false,
  showMobileNav: false,
  showModal: false,
  modalType: "",
  loading: false,
};

const complaints = [
  {
    id: "CR-1042",
    title: "Classroom fan not working",
    category: "Infrastructure",
    location: "Block B · Room 204",
    date: "Sep 10, 2026",
    shortDate: "Sep 10",
    status: "In Progress",
    student: "Alex Johnson",
    email: "alex.johnson@campus.edu",
    description:
      "The ceiling fan nearest the windows has stopped working. The room becomes uncomfortable during the afternoon lecture.",
    updated: "Today, 9:20 AM",
    remark:
      "Facilities has scheduled an inspection for today. We will update this request after the repair visit.",
  },
  {
    id: "CR-1038",
    title: "Library computer issue",
    category: "IT",
    location: "Central Library · Workstation 12",
    date: "Sep 8, 2026",
    shortDate: "Sep 8",
    status: "Resolved",
    student: "Alex Johnson",
    email: "alex.johnson@campus.edu",
    description:
      "The library computer at workstation 12 restarts unexpectedly while opening project files.",
    updated: "Sep 9, 4:45 PM",
    remark:
      "The workstation has been tested, updated, and returned to service.",
  },
  {
    id: "CR-1031",
    title: "Drinking water problem",
    category: "Facilities",
    location: "Block A · Ground floor",
    date: "Sep 6, 2026",
    shortDate: "Sep 6",
    status: "Pending",
    student: "Alex Johnson",
    email: "alex.johnson@campus.edu",
    description:
      "The water dispenser beside the reception desk has not been dispensing cold water since Monday.",
    updated: "Sep 6, 11:05 AM",
    remark: "Your complaint has been received and is awaiting assignment.",
  },
  {
    id: "CR-1027",
    title: "Projector malfunction",
    category: "IT",
    location: "Seminar Hall",
    date: "Sep 4, 2026",
    shortDate: "Sep 4",
    status: "Resolved",
    student: "Maya Patel",
    email: "maya.patel@campus.edu",
    description: "The projector flickers intermittently during presentations.",
    updated: "Sep 5, 3:12 PM",
    remark:
      "A loose HDMI connection was replaced and the projector is working normally.",
  },
  {
    id: "CR-1024",
    title: "Wi-Fi connectivity problem",
    category: "IT",
    location: "Block C · Lab 3",
    date: "Sep 3, 2026",
    shortDate: "Sep 3",
    status: "In Progress",
    student: "Rohan Mehta",
    email: "rohan.mehta@campus.edu",
    description:
      "Wi-Fi drops repeatedly across the lab during practical sessions.",
    updated: "Today, 8:40 AM",
    remark: "Network Services is reviewing access-point logs.",
  },
  {
    id: "CR-1019",
    title: "Laboratory equipment problem",
    category: "Laboratory",
    location: "Physics Lab · Bench 6",
    date: "Aug 30, 2026",
    shortDate: "Aug 30",
    status: "Pending",
    student: "Sara Khan",
    email: "sara.khan@campus.edu",
    description: "The digital multimeter on bench 6 does not power on.",
    updated: "Aug 30, 1:30 PM",
    remark: "Your request is queued for laboratory maintenance.",
  },
];

const users = [
  [
    "ST-2016",
    "Alex Johnson",
    "alex.johnson@campus.edu",
    "Student",
    "Aug 12, 2026",
    "Active",
  ],
  [
    "ST-2017",
    "Maya Patel",
    "maya.patel@campus.edu",
    "Student",
    "Aug 14, 2026",
    "Active",
  ],
  [
    "ST-2018",
    "Rohan Mehta",
    "rohan.mehta@campus.edu",
    "Student",
    "Aug 14, 2026",
    "Active",
  ],
  [
    "ST-2019",
    "Sara Khan",
    "sara.khan@campus.edu",
    "Student",
    "Aug 18, 2026",
    "Active",
  ],
  [
    "AD-001",
    "Priya Sharma",
    "priya.sharma@campus.edu",
    "Administrator",
    "Jul 28, 2026",
    "Active",
  ],
];

const icon = (name, size = 18) =>
  `<svg class="icon" width="${size}" height="${size}" aria-hidden="true"><use href="#i-${name}"></use></svg>`;
const statusClass = (s) => s.toLowerCase().replace(" ", "-");
const badge = (s) =>
  `<span class="status ${statusClass(s)}"><span></span>${s}</span>`;
const empty = (title, copy, action = "Clear filters") =>
  `<div class="empty-state">${icon("search", 28)}<h3>${title}</h3><p>${copy}</p><button class="button secondary" data-action="clear-filters">${action}</button></div>`;

function icons() {
  return `<svg class="sprite" xmlns="http://www.w3.org/2000/svg"><symbol id="i-arrow" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></symbol><symbol id="i-arrow-left" viewBox="0 0 24 24"><path d="M19 12H5m6 6-6-6 6-6"/></symbol><symbol id="i-bell" viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></symbol><symbol id="i-calendar" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18"/></symbol><symbol id="i-check" viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"/></symbol><symbol id="i-chevron" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></symbol><symbol id="i-close" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></symbol><symbol id="i-dashboard" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></symbol><symbol id="i-doc" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h5"/></symbol><symbol id="i-file-plus" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M12 18v-6m-3 3h6"/></symbol><symbol id="i-home" viewBox="0 0 24 24"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z"/></symbol><symbol id="i-lock" viewBox="0 0 24 24"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></symbol><symbol id="i-mail" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></symbol><symbol id="i-menu" viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16"/></symbol><symbol id="i-more" viewBox="0 0 24 24"><circle cx="12" cy="5" r="1" fill="currentColor"/><circle cx="12" cy="12" r="1" fill="currentColor"/><circle cx="12" cy="19" r="1" fill="currentColor"/></symbol><symbol id="i-paperclip" viewBox="0 0 24 24"><path d="m20.5 11.5-8.8 8.8a6 6 0 0 1-8.5-8.5L12 3a4 4 0 1 1 5.7 5.7L8.8 17.6a2 2 0 0 1-2.8-2.8l8.1-8.1"/></symbol><symbol id="i-plus" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></symbol><symbol id="i-search" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></symbol><symbol id="i-settings" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2.1 2.1-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.5v.2h-3v-.2a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.9.3l-.1.1-2.1-2.1.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.5-1H5.3v-3h.2a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.9l-.1-.1 2.1-2.1.1.1a1.7 1.7 0 0 0 1.9.3 1.7 1.7 0 0 0 1-1.5v-.2h3v.2a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.9-.3l.1-.1 2.1 2.1-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.5 1h.2v3h-.2a1.7 1.7 0 0 0-1.4 1z"/></symbol><symbol id="i-sliders" viewBox="0 0 24 24"><path d="M4 7h16M4 17h16M8 4v6M16 14v6"/></symbol><symbol id="i-user" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></symbol><symbol id="i-users" viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><path d="M3 20a6 6 0 0 1 12 0M16 5a3 3 0 0 1 0 6M17 14a6 6 0 0 1 4 6"/></symbol><symbol id="i-eye" viewBox="0 0 24 24"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"/><circle cx="12" cy="12" r="2.5"/></symbol></svg>`;
}

function logo(dark = false) {
  return `<button class="brand ${dark ? "brand-dark" : ""}" data-route="landing" aria-label="Campus Resolve home"><span class="brand-mark">${icon("check", 15)}</span><span>Campus Resolve</span></button>`;
}
function button(label, action, kind = "primary", extra = "") {
  return `<button class="button ${kind}" data-action="${action}" ${extra}>${label}</button>`;
}

function landing() {
  return `<div class="landing">${publicNav()}<main id="main-content">
<section class="hero"><div class="hero-copy reveal"><p class="eyebrow">Student Complaint Management System</p><h1>Campus concerns,<br><em>clearly resolved.</em></h1><p class="hero-text">A calm, organized place for students to raise an issue and for campus teams to see it through—without losing track of what happens next.</p><div class="hero-actions">${button(`Submit a complaint ${icon("arrow")}`, "get-started")} ${button("Sign in", "login", "secondary")}</div><p class="quiet-note">Clear updates. Thoughtful follow-through.</p></div>
<div class="hero-product reveal delay"><div class="product-glow"></div><div class="preview-shell"><div class="preview-top"><div class="window-dots"><i></i><i></i><i></i></div><span>campusresolve.edu</span><span></span></div><div class="preview-grid"><aside><div class="mini-brand"><b>✓</b> Resolve</div><div class="mini-nav active"></div><div class="mini-nav"></div><div class="mini-nav"></div><div class="mini-nav"></div><div class="mini-profile"></div></aside><article><div class="preview-title"><div><small>Good morning, Alex</small><strong>Everything in one clear view.</strong></div><button>+ New complaint</button></div><div class="preview-stats"><div><small>Open</small><b>2</b><span>Being handled</span></div><div><small>Resolved</small><b>8</b><span>Since August</span></div></div><div class="preview-card"><div class="preview-card-head"><div><span class="mini-dot blue"></span><b>CR-1042</b><small>Classroom fan not working</small></div>${badge("In Progress")}</div><div class="preview-progress"><i></i><i></i><i class="active"></i><i></i></div><div class="preview-steps"><span>Submitted</span><span>Reviewed</span><span>In progress</span><span>Resolved</span></div></div></article></div></div></div></section>
<section class="trusted"><p>Designed around the moments that matter</p><div><span>Easy to begin</span><span>Clear accountability</span><span>Respectful communication</span></div></section>
<section id="how" class="section how"><div class="section-intro"><p class="eyebrow">How it works</p><h2>A straightforward path<br>to a better campus.</h2></div><div class="steps"><article><span>01</span>${icon("file-plus", 26)}<h3>Submit</h3><p>Describe the issue, where it is, and what needs attention.</p></article><article><span>02</span>${icon("eye", 26)}<h3>Track</h3><p>See exactly when your request has been reviewed and assigned.</p></article><article><span>03</span>${icon("check", 26)}<h3>Resolve</h3><p>Get a clear update when the work is completed.</p></article></div></section>
<section id="tracking" class="section tracking"><div class="tracking-card"><div><p class="eyebrow">Complaint tracking</p><h2>Every update,<br>in plain sight.</h2><p>Follow your concern from the moment it is submitted to the moment it is resolved.</p></div><ol class="public-timeline"><li class="done"><b>✓</b><div><strong>Submitted</strong><small>Sep 10, 10:42 AM</small></div></li><li class="done"><b>✓</b><div><strong>Reviewed by administration</strong><small>Sep 10, 2:15 PM</small></div></li><li class="current"><b></b><div><strong>In progress</strong><small>Facilities team assigned</small></div></li><li><b></b><div><strong>Resolved</strong><small>Awaiting completion</small></div></li></ol></div></section>
<section id="experience" class="section experiences"><div class="experience"><p class="eyebrow">For students</p><h2>Speak up,<br>then stay informed.</h2><p>Submit concerns in a few considered steps, keep a simple history, and know that your voice has a path forward.</p><button class="text-button" data-route="register">Create an account ${icon("arrow")}</button></div><div class="experience admin-exp"><p class="eyebrow">For administrators</p><h2>Turn reports into<br>real progress.</h2><p>Review what needs attention, communicate clearly, and give every complaint a visible outcome.</p><button class="text-button light" data-action="admin-demo">View admin workspace ${icon("arrow")}</button></div></section>
<section class="final-cta"><p class="eyebrow">Campus Resolve</p><h2>Have something<br>that needs attention?</h2>${button(`Submit a complaint ${icon("arrow")}`, "get-started")}<p>One clear place to start.</p></section>
</main>${footer()}</div>`;
}

function publicNav() {
  return `<header class="public-nav"><div class="nav-inner">${logo()}<nav aria-label="Primary"><a href="#how">How it works</a><a href="#tracking">Tracking</a><a href="#experience">The experience</a></nav><div>${button("Sign in", "login", "secondary small")} ${button("Get started", "get-started", "primary small")}</div></div></header>`;
}
function footer() {
  return `<footer><div>${logo(true)}<p>Student Complaint Management System</p></div><p>© 2026 Campus Resolve</p></footer>`;
}

function auth(type) {
  const isLogin = type === "login";
  return `<div class="auth-page"><div class="auth-top">${logo()}<button class="back-button" data-route="landing">${icon("arrow-left")} Back to home</button></div><main class="auth-main" id="main-content"><section class="auth-card reveal"><div class="auth-copy"><p class="eyebrow">${isLogin ? "Welcome back" : "Create your account"}</p><h1>${isLogin ? "Sign in to Campus Resolve." : "A clearer way to be heard."}</h1><p>${isLogin ? "Use your campus email to access your complaint workspace." : "Your complaint journey starts with a simple account."}</p></div><form id="auth-form" novalidate><div class="form-alert" hidden role="alert"></div>${!isLogin ? field("Full name", "name", "text", "e.g. Alex Johnson") : ""}${field("Email address", "email", "email", "you@campus.edu")}${passwordField("Password", "password")}${!isLogin ? passwordField("Confirm password", "confirmPassword") : ""}${!isLogin ? `<div class="strength"><span>Password strength</span><div><i></i><i></i><i></i><i></i></div></div>` : ""}<button class="button primary full" type="submit">${isLogin ? "Sign in" : "Create account"} ${icon("arrow")}</button></form><div class="auth-switch">${isLogin ? 'New to Campus Resolve? <button data-route="register">Create an account</button>' : 'Already have an account? <button data-route="login">Sign in</button>'}</div>${isLogin ? '<p class="demo-note">For the admin workspace, sign in with an email containing “admin”.</p>' : ""}</section></main></div>`;
}
function field(label, id, type, placeholder) {
  return `<label class="field"><span>${label}</span><input id="${id}" name="${id}" type="${type}" placeholder="${placeholder}" autocomplete="${type === "email" ? "email" : "name"}" required><small class="error"></small></label>`;
}
function passwordField(label, id) {
  return `<label class="field"><span>${label}</span><span class="password-wrap"><input id="${id}" name="${id}" type="password" autocomplete="current-password" required><button type="button" class="password-toggle" data-action="toggle-password" data-target="${id}" aria-label="Show ${label.toLowerCase()}">${icon("eye")}</button></span><small class="error"></small></label>`;
}

function appShell(content) {
  const nav =
    state.role === "student"
      ? [
          ["dashboard", "Dashboard", "dashboard"],
          ["complaints", "My Complaints", "doc"],
          ["submit", "Submit Complaint", "file-plus"],
          ["profile", "Profile", "user"],
        ]
      : [
          ["dashboard", "Dashboard", "dashboard"],
          ["complaints", "All Complaints", "doc"],
          ["users", "Users", "users"],
          ["profile", "Profile", "user"],
        ];
  return `<div class="app-shell ${state.showMobileNav ? "nav-open" : ""}"><aside class="sidebar"><div>${logo()}<p class="workspace-label">${state.role === "student" ? "Student workspace" : "Admin workspace"}</p><nav aria-label="Application navigation">${nav.map(([r, l, i]) => `<button class="side-link ${state.route === r ? "active" : ""}" data-route="${r}">${icon(i)}<span>${l}</span></button>`).join("")}</nav></div><div class="sidebar-bottom"><button class="side-link" data-action="switch-role">${icon("settings")}<span>Switch workspace</span></button><div class="sidebar-user"><span class="avatar">${initials(state.user.name)}</span><div><strong>${state.user.name}</strong><small>${state.role === "student" ? "Student" : "Administrator"}</small></div><button data-action="profile-menu" aria-label="Open profile menu">${icon("more")}</button></div></div></aside><div class="app-body"><header class="app-header"><button class="mobile-menu" data-action="mobile-menu" aria-label="Open navigation">${icon("menu")}</button><div class="crumb">Campus Resolve <span>/</span> ${pageName()}</div><div class="header-actions"><button class="icon-button" aria-label="Notifications" data-action="notification">${icon("bell")}<i></i></button><button class="user-menu" data-action="profile-menu"><span class="avatar">${initials(state.user.name)}</span><span>${state.user.name.split(" ")[0]}</span>${icon("chevron", 15)}</button></div></header><main id="main-content" class="app-main">${content}</main></div></div>${state.showModal ? modal() : ""}`;
}
function pageName() {
  return (
    {
      dashboard: "Dashboard",
      complaints: state.role === "student" ? "My Complaints" : "All Complaints",
      submit: "Submit Complaint",
      detail: "Complaint",
      users: "User Management",
      profile: "Profile",
    }[state.route] || "Dashboard"
  );
}
function initials(name) {
  return name
    .split(" ")
    .map((x) => x[0])
    .join("")
    .slice(0, 2);
}

function stat(label, value, kind, sub) {
  return `<article class="stat-card"><div><p>${label}</p><strong>${value}</strong><span>${sub}</span></div><span class="stat-icon ${kind}">${icon(kind === "total" ? "doc" : kind === "pending" ? "calendar" : kind === "progress" ? "settings" : "check")}</span></article>`;
}
function dashboard() {
  const admin = state.role === "admin";
  const stats = admin
    ? [
        ["Total complaints", "126", "total", "This semester"],
        ["Pending", "18", "pending", "Needs review"],
        ["In progress", "31", "progress", "Being handled"],
        ["Resolved", "77", "resolved", "61% resolution rate"],
        ["Total students", "840", "total", "Registered users"],
      ]
    : [
        ["Total complaints", "11", "total", "All submitted"],
        ["Pending", "1", "pending", "Awaiting review"],
        ["In progress", "2", "progress", "Being handled"],
        ["Resolved", "8", "resolved", "72% complete"],
      ];
  return appShell(
    `<section class="page-head dashboard-head"><div><p class="eyebrow">${admin ? "Operations overview" : "Your workspace"}</p><h1>Good morning, ${state.user.name.split(" ")[0]}.</h1><p>${admin ? "Here’s the current picture across campus." : "Here’s what’s happening with your complaints."}</p></div>${admin ? "" : button(`${icon("plus")} New complaint`, "new-complaint")}</section><section class="stats-grid ${admin ? "admin-stats" : ""}">${stats.map((s) => stat(...s)).join("")}</section>${admin ? adminDashboardContent() : studentDashboardContent()}`,
  );
}
function studentDashboardContent() {
  return `<section class="content-grid"><div class="panel recent-panel"><div class="panel-head"><div><h2>Recent complaints</h2><p>Your latest updates, all in one place.</p></div><button class="text-button" data-route="complaints">View all ${icon("arrow")}</button></div>${complaintTable(complaints.filter((c) => c.student === "Alex Johnson").slice(0, 3), false)}</div><aside class="help-card"><div class="help-icon">${icon("file-plus", 24)}</div><h2>Need to report something?</h2><p>Share the details and your campus team can take it from there.</p>${button("Submit a complaint", "new-complaint")}</aside></section>`;
}
function adminDashboardContent() {
  return `<section class="admin-dashboard"><div class="panel activity-panel"><div class="panel-head"><div><h2>Complaint activity</h2><p>Incoming requests over the past 7 days.</p></div><span class="legend"><i></i> Received</span></div><div class="chart" aria-label="Bar chart showing complaint activity"><div class="chart-lines"></div>${[38, 58, 34, 67, 51, 85, 61].map((v, i) => `<div class="bar"><i style="height:${v}%"></i><span>${["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"][i]}</span></div>`).join("")}</div></div><div class="panel recent-panel"><div class="panel-head"><div><h2>Needs attention</h2><p>Recent campus concerns.</p></div><button class="text-button" data-route="complaints">View all ${icon("arrow")}</button></div>${complaintTable(complaints.slice(0, 4), true)}</div></section>`;
}

function controls() {
  return `<div class="table-controls"><label class="search-input">${icon("search")}<input id="complaint-search" value="${state.filters.search}" placeholder="Search by title or ID" aria-label="Search complaints"></label><div class="filter-row"><select data-filter="status" aria-label="Filter by status"><option>All statuses</option><option>Pending</option><option>In Progress</option><option>Resolved</option></select><select data-filter="category" aria-label="Filter by category"><option>All categories</option><option>Infrastructure</option><option>IT</option><option>Facilities</option><option>Laboratory</option></select><select data-filter="sort" aria-label="Sort complaints"><option>Newest first</option><option>Oldest first</option><option>Status</option></select></div></div>`;
}
function filtered() {
  const f = state.filters;
  return complaints
    .filter(
      (c) =>
        (state.role === "admin" || c.student === "Alex Johnson") &&
        (!f.search ||
          `${c.id} ${c.title}`
            .toLowerCase()
            .includes(f.search.toLowerCase())) &&
        (f.status === "All statuses" || c.status === f.status) &&
        (f.category === "All categories" || c.category === f.category),
    )
    .sort((a, b) =>
      f.sort === "Oldest first"
        ? a.id.localeCompare(b.id)
        : f.sort === "Status"
          ? a.status.localeCompare(b.status)
          : b.id.localeCompare(a.id),
    );
}
function complaintsPage() {
  const rows = filtered();
  return appShell(
    `<section class="page-head list-head"><div><p class="eyebrow">${state.role === "student" ? "Your complaint history" : "Campus issues"}</p><h1>${state.role === "student" ? "My Complaints" : "All Complaints"}</h1><p>${state.role === "student" ? "Search, filter, and follow every concern you have raised." : "A clear view of what needs attention across campus."}</p></div>${state.role === "student" ? button(`${icon("plus")} New complaint`, "new-complaint") : ""}</section><section class="panel table-panel">${controls()}<div class="loading-line" hidden></div>${rows.length ? complaintTable(rows, state.role === "admin") : empty(state.filters.search ? "No matching complaints" : "Nothing here yet", state.filters.search ? "Try another search term or clear a filter." : "Your submitted complaints will appear here.", "Clear filters")}</section>`,
  );
}
function complaintTable(rows, admin) {
  return `<div class="responsive-table"><table><thead><tr><th>ID</th>${admin ? "<th>Student</th>" : ""}<th>Complaint</th><th>Category</th><th>Date</th><th>Status</th><th><span class="sr-only">Action</span></th></tr></thead><tbody>${rows.map((c) => `<tr><td data-label="ID"><button class="id-link" data-action="view-complaint" data-id="${c.id}">${c.id}</button></td>${admin ? `<td data-label="Student"><strong>${c.student}</strong><small>${c.email}</small></td>` : ""}<td data-label="Complaint"><strong>${c.title}</strong><small>${c.location}</small></td><td data-label="Category">${c.category}</td><td data-label="Date">${c.shortDate}</td><td data-label="Status">${badge(c.status)}</td><td><button class="view-link" data-action="view-complaint" data-id="${c.id}">View ${icon("arrow", 15)}</button></td></tr>`).join("")}</tbody></table></div>`;
}

function submitPage() {
  return appShell(
    `<section class="form-page"><div class="page-head"><p class="eyebrow">New complaint</p><h1>Tell us what needs attention.</h1><p>Provide a few details so the right campus team can help.</p></div><form class="complaint-form panel" id="complaint-form" novalidate><div class="form-alert" hidden role="alert"></div><div class="form-grid"><label class="field full"><span>Complaint title</span><input name="title" placeholder="e.g. Classroom fan not working" required><small class="error"></small></label><label class="field"><span>Category</span><select name="category" required><option value="">Select a category</option><option>Infrastructure</option><option>IT</option><option>Facilities</option><option>Laboratory</option><option>Other</option></select><small class="error"></small></label><label class="field"><span>Location / department</span><input name="location" placeholder="e.g. Block B · Room 204" required><small class="error"></small></label><label class="field full"><span>Description</span><textarea name="description" placeholder="What happened? Include anything that would help the team understand the issue." required></textarea><small class="error"></small></label><label class="attachment full"><input type="file" aria-label="Add attachment"><span>${icon("paperclip", 20)}</span><div><strong>Add an attachment</strong><small>Optional · PDF, JPG, or PNG up to 10 MB</small></div><em>Browse</em></label></div><div class="form-actions">${button("Save as draft", "save-draft", "secondary")}<button class="button primary" type="submit">Submit complaint ${icon("arrow")}</button></div></form></section>`,
  );
}
function detailPage() {
  const c = complaints.find((x) => x.id === state.detailId) || complaints[0];
  const isAdmin = state.role === "admin";
  return appShell(
    `<section class="detail-page"><button class="back-button" data-route="complaints">${icon("arrow-left")} Back to ${isAdmin ? "all complaints" : "my complaints"}</button><div class="detail-hero"><div><p class="eyebrow">${c.id}</p><h1>${c.title}</h1><p>${c.category} · ${c.location}</p></div><div class="detail-status">${badge(c.status)}<small>Last updated ${c.updated}</small></div></div><div class="detail-grid"><div class="detail-main"><section class="panel detail-section"><h2>Complaint details</h2><p class="detail-description">${c.description}</p><dl><div><dt>Category</dt><dd>${c.category}</dd></div><div><dt>Location</dt><dd>${c.location}</dd></div><div><dt>Submitted</dt><dd>${c.date}</dd></div><div><dt>Last updated</dt><dd>${c.updated}</dd></div></dl></section><section class="panel detail-section"><h2>Status timeline</h2>${timeline(c)}</section><section class="panel detail-section response"><div><span class="response-icon">${icon("check", 17)}</span><div><p class="eyebrow">Administrative response</p><h2>Update from the team</h2></div></div><p>${c.remark}</p><small>Campus Resolve team · ${c.updated}</small></section></div><aside class="detail-side">${isAdmin ? adminActions(c) : `<section class="panel contact-card"><h3>Need to add context?</h3><p>If details have changed, contact the administration office with your complaint ID.</p><button class="text-button" data-action="contact">Contact support ${icon("arrow")}</button></section>`}${isAdmin ? `<section class="panel student-card"><p class="eyebrow">Submitted by</p><span class="avatar large">${initials(c.student)}</span><h3>${c.student}</h3><p>${c.email}</p><small>Student account · Active</small></section>` : ""}</aside></div></section>`,
  );
}
function timeline(c) {
  const stages = [
    "Submitted",
    "Reviewed by administration",
    "In Progress",
    "Resolved",
  ];
  let current = stages.indexOf(c.status);
  if (c.status === "Pending") current = 0;
  return `<ol class="timeline">${stages.map((s, i) => `<li class="${i < current ? "complete" : i === current ? "current" : ""}"><span>${i < current ? "✓" : ""}</span><div><strong>${s}</strong><small>${i === 0 ? c.date : i === 1 ? "Sep 10, 2:15 PM" : i === current && s === "In Progress" ? "Currently being handled" : i === current && s === "Resolved" ? "Completed and confirmed" : i === current ? "Awaiting review" : "Awaiting completion"}</small></div></li>`).join("")}</ol>`;
}
function adminActions(c) {
  return `<section class="panel admin-actions"><p class="eyebrow">Manage complaint</p><h3>Update the status</h3><label class="field"><span class="sr-only">Complaint status</span><select id="status-select"><option ${c.status === "Pending" ? "selected" : ""}>Pending</option><option ${c.status === "In Progress" ? "selected" : ""}>In Progress</option><option ${c.status === "Resolved" ? "selected" : ""}>Resolved</option></select></label><label class="field"><span>Add a remark</span><textarea id="admin-remark" placeholder="Share a helpful update for the student.">${c.remark}</textarea></label><button class="button primary full" data-action="confirm-status">Save update</button></section>`;
}
function usersPage() {
  return appShell(
    `<section class="page-head list-head"><div><p class="eyebrow">Account directory</p><h1>User management</h1><p>View registered students and campus administrators.</p></div><button class="button primary" data-action="invite-user">${icon("plus")} Add user</button></section><section class="panel table-panel"><div class="table-controls"><label class="search-input">${icon("search")}<input placeholder="Search name or email" aria-label="Search users"></label><div class="filter-row"><select aria-label="Filter users"><option>All roles</option><option>Students</option><option>Administrators</option></select></div></div><div class="responsive-table"><table><thead><tr><th>User ID</th><th>Name</th><th>Email</th><th>Role</th><th>Registered</th><th>Status</th></tr></thead><tbody>${users.map((u) => `<tr><td>${u[0]}</td><td><strong>${u[1]}</strong></td><td>${u[2]}</td><td>${u[3]}</td><td>${u[4]}</td><td><span class="account-active">${u[5]}</span></td></tr>`).join("")}</tbody></table></div></section>`,
  );
}
function profilePage() {
  return appShell(
    `<section class="page-head"><p class="eyebrow">Account</p><h1>Your profile</h1><p>Keep your Campus Resolve account information current.</p></section><section class="profile-layout"><div class="panel profile-card"><span class="avatar profile-avatar">${initials(state.user.name)}</span><h2>${state.user.name}</h2><p>${state.user.email}</p><span class="role-chip">${state.role === "student" ? "Student" : "Administrator"}</span><hr><dl><div><dt>Member since</dt><dd>August 2026</dd></div><div><dt>Account status</dt><dd><span class="account-active">Active</span></dd></div></dl></div><form class="panel profile-form" id="profile-form"><div class="panel-head"><div><h2>Personal information</h2><p>Used only to identify your account.</p></div></div>${field("Full name", "profile-name", "text", state.user.name)}${field("Email address", "profile-email", "email", state.user.email)}<div class="form-actions"><button class="button primary" type="submit">Save changes</button></div></form></section>`,
  );
}
function modal() {
  if (state.modalType === "status") {
    return `<div class="modal-backdrop" data-action="close-modal"><section class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-title"><button class="modal-close" data-action="close-modal" aria-label="Close">${icon("close")}</button><span class="modal-symbol">${icon("check", 22)}</span><h2 id="modal-title">Save this update?</h2><p>The student will see the new status and your latest response in their complaint timeline.</p><div class="modal-actions">${button("Cancel", "close-modal", "secondary")} ${button("Save update", "save-status")}</div></section></div>`;
  }
  return "";
}

function render() {
  let page =
    state.route === "landing"
      ? landing()
      : state.route === "login" || state.route === "register"
        ? auth(state.route)
        : state.route === "dashboard"
          ? dashboard()
          : state.route === "complaints"
            ? complaintsPage()
            : state.route === "submit"
              ? submitPage()
              : state.route === "detail"
                ? detailPage()
                : state.route === "users"
                  ? usersPage()
                  : profilePage();
  document.getElementById("app").innerHTML = icons() + page;
  bind();
}
function bind() {
  document.querySelectorAll("[data-route]").forEach(
    (el) =>
      (el.onclick = () => {
        state.route = el.dataset.route;
        state.showMobileNav = false;
        render();
        window.scrollTo({ top: 0, behavior: "instant" });
      }),
  );
  document
    .querySelectorAll("[data-action]")
    .forEach((el) => (el.onclick = (e) => action(el.dataset.action, el, e)));
  const authForm = document.getElementById("auth-form");
  if (authForm) authForm.onsubmit = handleAuth;
  const cf = document.getElementById("complaint-form");
  if (cf) cf.onsubmit = submitComplaint;
  const pf = document.getElementById("profile-form");
  if (pf)
    pf.onsubmit = (e) => {
      e.preventDefault();
      state.user.name =
        document.getElementById("profile-name").value || state.user.name;
      state.user.email =
        document.getElementById("profile-email").value || state.user.email;
      toast("Profile updated", "success");
      render();
    };
  const search = document.getElementById("complaint-search");
  if (search)
    search.oninput = (e) => {
      state.filters.search = e.target.value;
      state.loading = true;
      render();
      setTimeout(() => {
        state.loading = false;
        render();
      }, 160);
    };
  document.querySelectorAll("[data-filter]").forEach((s) => {
    s.value = state.filters[s.dataset.filter];
    s.onchange = (e) => {
      state.filters[s.dataset.filter] = e.target.value;
      render();
    };
  });
}
function action(a, el, e) {
  if (a === "get-started") {
    state.route = "register";
  }
  if (a === "login") {
    state.route = "login";
  }
  if (a === "new-complaint") {
    state.route = "submit";
  }
  if (a === "admin-demo") {
    state.role = "admin";
    state.user = { name: "Priya Sharma", email: "priya.sharma@campus.edu" };
    state.route = "dashboard";
  }
  if (a === "switch-role") {
    state.role = state.role === "student" ? "admin" : "student";
    state.user =
      state.role === "admin"
        ? { name: "Priya Sharma", email: "priya.sharma@campus.edu" }
        : { name: "Alex Johnson", email: "alex.johnson@campus.edu" };
    state.route = "dashboard";
  }
  if (a === "mobile-menu") {
    state.showMobileNav = !state.showMobileNav;
  }
  if (a === "profile-menu") {
    toast("Profile menu is available in the full build.", "info");
  }
  if (a === "notification") {
    toast("You have one update on CR-1042.", "info");
  }
  if (a === "toggle-password") {
    const input = document.getElementById(el.dataset.target);
    const show = input.type === "password";
    input.type = show ? "text" : "password";
    el.setAttribute(
      "aria-label",
      (show ? "Hide " : "Show ") + el.dataset.target,
    );
  }
  if (a === "view-complaint") {
    state.detailId = el.dataset.id;
    state.route = "detail";
  }
  if (a === "clear-filters") {
    state.filters = {
      search: "",
      status: "All statuses",
      category: "All categories",
      sort: "Newest first",
    };
  }
  if (a === "save-draft") {
    toast("Draft saved on this device.", "info");
  }
  if (a === "confirm-status") {
    state.showModal = true;
    state.modalType = "status";
  }
  if (a === "close-modal") {
    state.showModal = false;
  }
  if (a === "save-status") {
    const c = complaints.find((x) => x.id === state.detailId);
    c.status = document.getElementById("status-select").value;
    c.remark = document.getElementById("admin-remark").value || c.remark;
    c.updated = "Just now";
    state.showModal = false;
    toast("Complaint update saved.", "success");
  }
  if (a === "contact") {
    toast("Support request prepared for the administration office.", "info");
  }
  if (a === "invite-user") {
    toast("User invitation flow is ready for backend integration.", "info");
  }
  render();
}
function handleAuth(e) {
  e.preventDefault();
  const f = e.target;
  const isLogin = state.route === "login";
  let valid = true;
  f.querySelectorAll("[required]").forEach((i) => {
    const err = i.closest(".field").querySelector(".error");
    if (!i.value.trim()) {
      err.textContent = "This field is required.";
      i.setAttribute("aria-invalid", "true");
      valid = false;
    } else {
      err.textContent = "";
      i.removeAttribute("aria-invalid");
    }
  });
  const email = f.email.value;
  if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    f.email.closest(".field").querySelector(".error").textContent =
      "Enter a valid email address.";
    valid = false;
  }
  if (!isLogin && f.password.value !== f.confirmPassword.value) {
    f.confirmPassword.closest(".field").querySelector(".error").textContent =
      "Passwords do not match.";
    valid = false;
  }
  if (!valid) return;
  if (isLogin && email.includes("error")) {
    const a = f.querySelector(".form-alert");
    a.textContent =
      "We could not sign you in with those details. Check your email and password.";
    a.hidden = false;
    return;
  }
  state.role = email.toLowerCase().includes("admin") ? "admin" : "student";
  state.user =
    state.role === "admin"
      ? { name: "Priya Sharma", email }
      : { name: isLogin ? "Alex Johnson" : f.name.value, email };
  state.route = "dashboard";
  toast(isLogin ? "Welcome back." : "Your account is ready.", "success");
  render();
}
function submitComplaint(e) {
  e.preventDefault();
  const f = e.target;
  let valid = true;
  f.querySelectorAll("[required]").forEach((i) => {
    const err = i.closest(".field").querySelector(".error");
    if (!i.value.trim()) {
      err.textContent = "Please complete this field.";
      valid = false;
    } else err.textContent = "";
  });
  if (!valid) return;
  const id = `CR-${1043 + complaints.length}`;
  complaints.unshift({
    id,
    title: f.title.value,
    category: f.category.value,
    location: f.location.value,
    date: "Sep 11, 2026",
    shortDate: "Today",
    status: "Pending",
    student: state.user.name,
    email: state.user.email,
    description: f.description.value,
    updated: "Just now",
    remark: "Your complaint has been received and is awaiting assignment.",
  });
  state.detailId = id;
  state.route = "detail";
  toast("Complaint submitted successfully.", "success");
  render();
}
function toast(message, type = "info") {
  const region = document.getElementById("toast-region");
  if (!region) return;
  const el = document.createElement("div");
  el.className = `toast ${type}`;
  el.innerHTML = `<span>${type === "success" ? "✓" : "i"}</span><p>${message}</p><button aria-label="Dismiss notification">${icon("close", 15)}</button>`;
  region.append(el);
  el.querySelector("button").onclick = () => el.remove();
  setTimeout(() => {
    el.style.opacity = "0";
    el.style.transform = "translateY(100%)";
    setTimeout(() => el.remove(), 220);
  }, 3600);
}
render();
