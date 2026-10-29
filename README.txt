PlusSave HR System - clickable prototype
==========================================

HOW TO OPEN
1. Unzip this folder anywhere on your PC.
2. Double-click index.html. It opens in your browser (Chrome, Edge or Firefox).
   Or open PlusSave-HR-System-single-file.html: the same prototype in one file,
   handy for emailing or sharing on WhatsApp.

No installation, server or login is needed. With internet the page uses its
proper fonts; offline it falls back to system fonts and still works.

HOW TO USE IT
- Use the Staff / HR / MD switch at the top to change who is signed in.
- Try the maker-checker flow: as Staff, Apply for leave. Switch to HR, open
  Approvals, verify and forward. Switch to MD, approve. The leave balance
  and records update.
- Logos and branding (HR menu, Administration) lets you upload the official
  PlusSave Financial Solutions logo for this session.
- All names and figures are sample data. Refreshing the page resets it.

WHAT IS IN THE FOLDER (for the IT team)
index.html                                  Page layout, loads the files below
PlusSave-HR-System-single-file.html         Everything in one file
css/styles.css                              Colours, fonts, layout, components
js/01-sample-data.js                        Sample staff, leave, cases, settings
js/02-core-payroll-and-approvals.js         Helpers, PAYE/NSSF/LST payroll engine,
                                            maker-checker approval engine
js/03-layout-nav-forms.js                   Menus, page header with logo, forms,
                                            approval drawer, notifications
js/04-dashboard-people-leave.js             Dashboard, Approvals, Employees,
                                            Employee profile, Attendance, Leave
js/05-talent-movements-payroll.js           Performance, Probation and contracts,
                                            Training, Recruitment, Transfers and
                                            promotions, Payroll
js/06-gratuity-cases-exits.js               Gratuity and leave pay, Payroll setup,
                                            Disciplinary and grievance, Exits
js/07-library-reports-admin-staff.js        HR library, Reports, Roles and
                                            permissions, Workflow, Connected
                                            systems, Audit trail, Branding,
                                            Staff self-service screens

The written functional specification for developers is a separate document.
