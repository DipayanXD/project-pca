# Campus Resolve — frontend prototype

This is a static, PHP-ready frontend for the Campus Resolve student complaint management system.

## Architecture

- `*.html` — complete page templates and semantic application structure. These files can become `.php` files directly.
- `styles.css` — shared visual design, responsive layout, and motion.
- `app.js` — progressive enhancement only: mobile navigation, password visibility, client validation, filters, modal and toast feedback, mock status updates.
- `mock-data.js` — isolated browser-only demo data, ready to be replaced with PHP/MySQL output.
- `icons.svg` — shared SVG symbol sprite.

## PHP integration map

The repeated header/sidebar/footer blocks in the HTML have `PHP include` comments showing the eventual extraction points:

```text
includes/header.php
includes/student-sidebar.php
includes/admin-sidebar.php
includes/footer.php
pages/dashboard.php
pages/complaints.php
pages/complaint-details.php
pages/submit-complaint.php
auth/login.php
auth/register.php
```

Keep the HTML elements, IDs, classes, and data attributes when PHP begins populating page content. PHP should render table rows and current role data; `app.js` does not generate page structure.

## Run locally

Open `index.html`, or run a static server such as `npx vite` and visit the shown address.
