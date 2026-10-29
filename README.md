# PlusSave HR System

Clickable prototype of the PlusSave HR system. All names and figures are sample data, and refreshing the page resets everything.

## Live version

https://gorretgolden.github.io/PlussaveHRSystem/

You can also open the single file version, which works offline and is easy to send by email or WhatsApp:

https://gorretgolden.github.io/PlussaveHRSystem/PlusSave-HR-System-single-file.html

## What is in this repository

| Path | Purpose |
| --- | --- |
| `index.html` | Page layout, loads the CSS and JS files below |
| `PlusSave-HR-System-single-file.html` | The same prototype in one file, works with no internet |
| `css/styles.css` | Colours, fonts, layout, components |
| `js/01-sample-data.js` | Sample staff, leave, cases, settings |
| `js/02-core-payroll-and-approvals.js` | Helpers, PAYE/NSSF/LST payroll engine, maker-checker approval engine |
| `js/03-layout-nav-forms.js` | Menus, page header with logo, forms, approval drawer, notifications |
| `js/04-dashboard-people-leave.js` | Dashboard, Approvals, Employees, Employee profile, Attendance, Leave |
| `js/05-talent-movements-payroll.js` | Performance, Probation and contracts, Training, Recruitment, Transfers and promotions, Payroll |
| `js/06-gratuity-cases-exits.js` | Gratuity and leave pay, Payroll setup, Disciplinary and grievance, Exits |
| `js/07-library-reports-admin-staff.js` | HR library, Reports, Roles and permissions, Workflow, Connected systems, Audit trail, Branding, Staff self-service screens |

The human readable notes are also in `README.txt`.

## How to use it

1. Use the Staff / HR / MD switch at the top to change who is signed in.
2. Try the maker-checker flow. As Staff, apply for leave. Switch to HR, open Approvals, verify and forward. Switch to MD and approve. The leave balance and records update.
3. Open Logos and branding (HR menu, Administration) to upload the official PlusSave Financial Solutions logo for the session.

## Running locally

Unzip or clone the repository, then double click `index.html`. No installation, server or login is needed.

## Hosting

This site is served by GitHub Pages from the `main` branch root. The `.nojekyll` file tells GitHub Pages to serve the files exactly as they are.
