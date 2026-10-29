/* ================= HR LIBRARY ================= */
VIEWS.library = () => {
  const staff = S.role === 'staff', total = activeEmp().length;
  const acked = S.acked || (S.acked = new Set());
  const body = `<div class="note"><b>One place for HR documents.</b> HR manual, code of conduct, leave policy and every other policy, with version history. Staff confirm they have read each policy; HR sees who has not.</div>`
    + table([{h:'Document', f:d => `<b>${esc(d.title)}</b>`}, {h:'Category', f:d => pill(d.cat, 'brand')}, {h:'Version', f:d => `<span class="mono">v${d.ver}</span>`}, {h:'Owner', f:d => esc(d.owner)}, {h:'Approved by MD', f:d => D(d.approved)},
      {h:staff ? 'My status' : 'Read and acknowledged', f:d => d.ack == null ? '<span class="faint">Form or template</span>' : staff ? (acked.has(d.id) ? pill('Acknowledged', 'ok') : `<button class="btn sm" data-act="ack" data-id="${d.id}">I have read this</button>`) : `${d.ack} of ${total} ${d.ack < total ? pill((total - d.ack) + ' pending', 'warn') : pill('All', 'ok')}`}], LIBRARY);
  return page({crumb:'Resources', title:'HR library', sub:'Policies, manuals, procedures, forms and templates.', actions:S.role === 'hr' ? '<button class="btn primary" data-act="upDoc">Upload document</button>' : '', body});
};
A.ack = el => { S.acked.add(el.dataset.id); const d = LIBRARY.find(x => x.id === el.dataset.id); d.ack = Math.min(activeEmp().length, (d.ack || 0) + 1); audit('Acknowledged policy', d.id, d.title); render(); toast('Thanks. Your acknowledgement is recorded.'); };
A.upDoc = () => formModal({title:'Upload to HR library', flow:['hr','md'], fields:[{id:'title', label:'Title', req:1, full:1}, {id:'cat', label:'Category', type:'select', opts:['Policy','Manual','Procedure','Handbook','Form','Template']}, {id:'ver', label:'Version', val:'1.0'}, {id:'file', label:'File (PDF or Word)', type:'file', full:1}, {id:'ackreq', label:'Staff must acknowledge', type:'select', opts:['Yes','No']}],
  onSubmit:v => { if (!v.title) return formError('Enter a title.'), false; newReq({type:'HR Library document', subject:`${v.title} v${v.ver}`, maker:USERS.hr.emp, fields:[['Title', v.title], ['Category', v.cat], ['Version', v.ver], ['File', v.file || 'Not attached'], ['Acknowledgement', v.ackreq]], payload:{title:v.title, cat:v.cat, ver:v.ver}}); toast('Sent to the MD. It publishes once approved.'); }});

/* ================= HR REPORTS ================= */
const REPORTS = {
  'Headcount by branch, department and type':() => [['Branch','Department','Type','Staff'], ...BRANCHES.flatMap(b => DEPTS.flatMap(d => TYPES.map(t => [b, d, t, activeEmp().filter(e => e.branch === b && e.dept === d && e.type === t).length]))).filter(r => r[3])],
  'Staff master list':() => [['ID','Name','Gender','Job title','Department','Branch','Type','Joined','Status'], ...EMP.map(e => [e.id, nm(e), e.g, e.title, e.dept, e.branch, e.type, e.joined, e.status])],
  'Leave taken and balances':() => [['Employee','Annual left','Sick left','Leave taken (days)'], ...activeEmp().map(e => [nm(e), e.bal.Annual, e.bal.Sick, LEAVES.filter(l => l.emp === e.id).reduce((s, l) => s + l.days, 0)])],
  'Attendance today':() => [['Employee','Branch','Clock in','Status'], ...activeEmp().map(e => { const a = ATT[e.id]; return [nm(e), e.branch, a ? a[0] : '', a ? (a[0] > SHIFT_START ? 'Late' : 'Present') : e.status === 'On Leave' ? 'On leave' : 'Absent']; })],
  'Contract and probation expiry':() => [['Employee','Type','Ends','Days left'], ...activeEmp().filter(e => e.cEnd || e.pEnd).map(e => [nm(e), e.type, e.cEnd || e.pEnd, fromToday(e.cEnd || e.pEnd)])],
  'Appraisal status':() => [['Employee','Cycle','Due','Status','Score'], ...APPRAISALS.map(a => [nm(emp(a.emp)), a.cycle, a.due, a.status, a.score ?? ''])],
  'Training attendance':() => [['Training','Date','Status','Attended'], ...TRAININGS.map(t => [t.title, t.date, t.status, t.done.length])],
  'Transfers, promotions and salary changes':() => [['Employee','Change','Previous','New','Effective','Reason'], ...EMP.flatMap(e => e.movements.map(m => [nm(e), m.kind, m.prev, m.next, m.date, m.reason]))],
  'Disciplinary and grievance register':() => [['Case','Type','Employee','Category','Stage','Decision'], ...CASES.map(c => [c.id, c.kind, nm(emp(c.emp)), c.category, c.stage, c.decision || ''])],
  'Exits and staff turnover':() => [['Employee','Exit type','Last day','Reason','Status'], ...EXITS.map(x => [nm(emp(x.emp)), x.type, x.lwd, x.reason, x.status]), ['Turnover (last 12 months)', '', '', `${(EXITS.length / EMP.length * 100).toFixed(1)}% of headcount`, '']],
  'Payroll summary (October 2026)':() => { const r = payRegister(); return [['Employee','Gross','PAYE','NSSF 5%','LST','Net'], ...r.lines.map(l => [nm(l.e), l.gross, l.paye, l.nssfEe, l.lst, l.net])]; },
  'Statutory returns (PAYE, NSSF, LST)':() => { const t = payRegister().tot; return [['Return','Authority','Amount'], ['PAYE','URA',t.paye], ['NSSF 15%','NSSF',t.nssfEe + t.nssfEr], ['LST','Local government',t.lst]]; },
  'Gratuity provision':() => [['Employee','Service years','Accrued'], ...activeEmp().filter(e => e.type === 'Contract').map(e => [nm(e), gratuityFor(e).yrs, gratuityFor(e).amt])],
  'Approvals turnaround':() => [['Ref','Type','Status','Steps'], ...REQS.map(r => [r.id, r.type, r.status, r.hist.length])]
};
VIEWS.reports = () => page({crumb:'Resources', title:'HR reports', sub:'Every dashboard figure has a report behind it. Open one to preview, then copy it as CSV or export from the live system to Excel or PDF.',
  body:`<div class="grid g3">${Object.keys(REPORTS).map(k => `<button class="kpi" data-act="report" data-k="${esc(k)}"><span class="l">Report</span><span style="font-weight:600;font-size:14.5px">${esc(k)}</span><span class="d">${REPORTS[k]().length - 1} rows</span></button>`).join('')}</div>`});
A.report = el => { const k = el.dataset.k, rows = REPORTS[k](); S.lastReport = rows;
  openDrawer(`${ovHead(k, 'Preview · sample data')}<div class="ov-body"><div class="actions"><button class="btn primary" data-act="copyCsv">Copy as CSV</button><button class="btn" data-act="exportNote">Excel</button><button class="btn" data-act="exportNote">PDF</button></div>
  <div class="tw"><table><thead><tr>${rows[0].map(h => `<th>${esc(h)}</th>`).join('')}</tr></thead><tbody>${rows.slice(1).map(r => `<tr>${r.map(c => `<td class="${typeof c === 'number' ? 'r num' : ''}">${typeof c === 'number' && c > 999 ? N(c) : esc(c)}</td>`).join('')}</tr>`).join('')}</tbody></table></div></div>`); };
A.copyCsv = () => { const csv = S.lastReport.map(r => r.map(c => `"${String(c).replace(/"/g, '""')}"`).join(',')).join('\n');
  try { navigator.clipboard.writeText(csv).then(() => toast('CSV copied. Paste into Excel or Google Sheets.'), () => toast('Copy blocked here. Select the table and copy it instead.')); } catch (e) { toast('Copy blocked here. Select the table and copy it instead.'); } };
A.exportNote = () => toast('Excel and PDF export run on the live system. Use Copy as CSV in this prototype.');

/* ================= ROLES AND PERMISSIONS ================= */
VIEWS.roles = () => {
  const role = S.roleSel || (S.roleSel = 'HR Manager');
  if (!S.permDraft || S.permDraft.role !== role) S.permDraft = {role, p:new Set(ROLE_PERMS[role].p), a:new Set(ROLE_PERMS[role].a)};
  const d = S.permDraft, q = (S.permQ || '').toUpperCase(), all = Object.values(PERMS).flat();
  const conflict = d.a.has('Initiate') && d.a.has('Approve');
  const body = `<div class="grid" style="grid-template-columns:minmax(0,240px) minmax(0,1fr)">
    <section class="card"><h3>Roles</h3><div class="list">${Object.keys(ROLE_PERMS).map(r => `<button class="nav-item" style="color:var(--ink);${r === role ? 'background:var(--brand-soft);font-weight:700' : ''}" data-act="pickRole" data-r="${esc(r)}">${esc(r)}<span class="badge" style="background:var(--surface-2);color:var(--muted)">${ROLE_PERMS[r].p.length}</span></button>`).join('')}</div>
      <p class="muted" style="font-size:12px;margin:10px 0 0">Users get one role. Changes to a role go to the MD before they apply.</p></section>
    <section class="card" style="display:flex;flex-direction:column;gap:14px">
      <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-end">
        <label class="cb"><input type="checkbox" data-change="permAll" ${d.p.size === all.length ? 'checked' : ''}> Select all permissions</label>
        <div class="field" style="min-width:220px"><label for="copyFrom">Copy permission from</label><select id="copyFrom" data-change="permCopy"><option value="">Choose a role</option>${Object.keys(ROLE_PERMS).filter(r => r !== role).map(r => `<option>${esc(r)}</option>`).join('')}</select></div></div>
      <label class="search" for="permQ"><input id="permQ" placeholder="Search" value="${esc(S.permQ || '')}" data-change="permSearch"></label>
      ${Object.entries(PERMS).map(([g, list]) => { const vis = list.filter(p => !q || p.includes(q)); return vis.length ? `<div class="perm-mod"><h5>${esc(g)}</h5><div class="perm-grid">${vis.map(p => `<label class="cb"><input type="checkbox" data-change="permTog" data-p="${esc(p)}" ${d.p.has(p) ? 'checked' : ''}> ${esc(p)}</label>`).join('')}</div></div>` : ''; }).join('')}
      <div><h3 style="margin-bottom:2px">Authorisation privileges</h3><div class="muted" style="font-size:12.5px;margin-bottom:8px">Please select all that apply</div>
        <div class="perm-grid">${PRIVS.map(p => `<label class="cb lc"><input type="checkbox" data-change="privTog" data-p="${esc(p)}" ${d.a.has(p) ? 'checked' : ''}> ${esc(p)}</label>`).join('')}<label class="cb lc" title="Never allowed"><input type="checkbox" disabled> Approve own requests (blocked for every role)</label></div></div>
      ${conflict ? `<div class="note crit"><b>Conflict.</b> This role can both initiate and approve. The system will block saving: one person cannot be maker and checker on the same item.</div>` : ''}
      <div class="actions"><button class="btn primary" data-act="savePerms" ${conflict ? 'disabled' : ''}>Save changes for MD approval</button><button class="btn" data-act="pickRole" data-r="${esc(role)}">Discard changes</button></div>
    </section></div>
    <div class="note warn"><b>Fixing today's risk.</b> In the current back-office screen one user can tick BASIC SALARY SETUP, EMPLOYEE SALARY, ADJUST SALARY ITEM and RUN PAYROLL together with CUSTOMER INFO. Here, payroll setup and payroll runs need an Initiate role and a separate Approve role, and banking permissions (customer info, loan schedule, deal certificates, directors' mandates) stay in core banking.</div>`;
  return page({crumb:'Administration', title:'Roles and permissions', sub:'Who can see and do what. Built on the same checkbox pattern your team already uses.', body});
};
A.pickRole = el => { S.roleSel = el.dataset.r; S.permDraft = null; render(); };
A.permTog = el => { const d = S.permDraft; el.checked ? d.p.add(el.dataset.p) : d.p.delete(el.dataset.p); render(); };
A.privTog = el => { const d = S.permDraft; el.checked ? d.a.add(el.dataset.p) : d.a.delete(el.dataset.p); render(); };
A.permAll = el => { S.permDraft.p = el.checked ? new Set(Object.values(PERMS).flat()) : new Set(); render(); };
A.permCopy = el => { if (!el.value) return; S.permDraft.p = new Set(ROLE_PERMS[el.value].p); toast(`Copied ${ROLE_PERMS[el.value].p.length} permissions from ${el.value}`); render(); };
A.permSearch = el => { S.permQ = el.value; render(); };
A.savePerms = () => { const d = S.permDraft, old = ROLE_PERMS[d.role]; const add = [...d.p].filter(p => !old.p.includes(p)), rem = old.p.filter(p => !d.p.has(p));
  if (!add.length && !rem.length && [...d.a].sort().join() === [...old.a].sort().join()) return toast('No changes to save.');
  newReq({type:'Role permission change', subject:`${d.role}: ${add.length} added, ${rem.length} removed`, maker:USERS[S.role].emp, fields:[['Role', d.role], ['Added', add.join(', ') || 'None'], ['Removed', rem.join(', ') || 'None'], ['Privileges', [...d.a].join(', ')]], payload:{role:d.role, p:[...d.p], a:[...d.a]}}); render(); toast('Sent to the MD for approval'); };
EFFECTS['Role permission change'] = r => { ROLE_PERMS[r.payload.role] = {p:r.payload.p, a:r.payload.a}; };

/* ================= APPROVAL WORKFLOW ================= */
VIEWS.workflow = () => page({crumb:'Administration', title:'Approval workflow', sub:'Maker-checker routes for every request type.',
  body:table([{h:'Request type', f:w => `<b>${esc(w[0])}</b>`}, {h:'Maker', f:w => pill(w[1], w[1] === 'Staff' ? 'info' : 'brand')}, {h:'Checker (HR)', f:w => w[2] ? esc(w[2]) : '<span class="faint">HR is the maker</span>'}, {h:'Final approver', f:w => esc(w[3])}, {h:'Route', f:w => (w[1] === 'Staff' ? 'Staff → HR → MD' : 'HR → MD')}], WORKFLOWS)
   + `<div class="grid g2">${card('Rules every route follows', `<div class="list">${[['No self-approval','A maker can never check or approve their own request. HR\'s own requests go straight to the MD; the MD\'s own requests go to HR and are reported to the Board.'], ['Return or reject needs a reason','The comment is shown to the maker and kept in history.'], ['MD returns to HR','An MD return goes back to HR, who can fix it or send it back to staff.'], ['Time limits','HR has ' + CONFIG.sla.hr + 'h and MD ' + CONFIG.sla.md + 'h. Reminders at half time; overdue items are flagged in red on both dashboards.'], ['Delegation','When the MD is away, the MD names an acting approver with start and end dates. Every action shows "on behalf of".'], ['Audit','Every submit, verify, approve, return, reject and edit is logged with user, time, device and old and new values.']].map(([t, s]) => `<div class="li"><div class="grow"><div class="t">${t}</div><div class="s">${s}</div></div></div>`).join('')}</div>`)}
   ${card('Delegation of approval', `<div class="form"><div class="field"><label for="dg_who">Acting approver</label><select id="dg_who">${activeEmp().filter(e => ['G2'].includes(e.grade) && e.id !== USERS.hr.emp).map(e => `<option>${esc(nm(e))}, ${esc(e.title)}</option>`).join('')}</select></div><div class="field"><label for="dg_from">From</label><input type="date" id="dg_from" value="2026-11-16"></div><div class="field"><label for="dg_to">To</label><input type="date" id="dg_to" value="2026-11-20"></div><div class="field"><label for="dg_sc">Scope</label><select id="dg_sc"><option>All HR approvals except payroll</option><option>All HR approvals</option></select></div></div><div class="actions" style="margin-top:12px"><button class="btn" data-act="delegate">Save delegation</button></div>`, 'Set by the MD')}</div>`});
A.delegate = () => { audit('Set delegation', 'MD', `${$('#dg_who').value} ${$('#dg_from').value} to ${$('#dg_to').value}`); toast('Delegation saved and logged'); };

/* ================= CONNECTED SYSTEMS ================= */
VIEWS.systems = () => page({crumb:'Administration', title:'Connected systems', sub:'How the HR system shares data with PlusSave\'s other systems so nobody types the same thing twice.',
  body:`<div class="note"><b>One employee record, many systems.</b> When the MD approves a new hire, the HR system asks core banking and PlusPay to create the user. When an exit is approved, it disables both on the last working day. Payroll pulls staff loan instalments from core banking and posts net pay back.</div>
  <div class="grid g3">${SYSTEMS.map(s => `<div class="sysbox"><div class="hd"><span>${esc(s.name)}</span>${s.state === 'ok' ? pill('Connected', 'ok') : pill('File export', 'warn')}</div><ul>${s.flows.map(f => `<li>${esc(f)}</li>`).join('')}</ul></div>`).join('')}</div>`});

/* ================= AUDIT ================= */
VIEWS.audit = () => { const f = S.auditRole || '';
  return page({crumb:'Administration', title:'Audit trail', sub:'Read-only log of every action. Nobody can edit or delete entries, including system admins.',
    body:`<div class="toolbar"><select data-change="auditRole" aria-label="Filter by role"><option value="">All roles</option>${['staff','hr','md'].map(r => `<option value="${r}" ${r === f ? 'selected' : ''}>${ROLE_NAME[r]}</option>`).join('')}</select></div>`
      + table([{h:'Time', f:a => DT(a.at)}, {h:'User', f:a => person(emp(a.by), ROLE_NAME[a.role])}, {h:'Action', f:a => `<b>${esc(a.action)}</b>`}, {h:'Reference', f:a => `<span class="mono">${esc(a.ref)}</span>`}, {h:'Detail', f:a => esc(a.detail)}], AUDIT.filter(a => !f || a.role === f))}); };
A.auditRole = el => { S.auditRole = el.value; render(); };

/* ================= LOGOS AND BRANDING ================= */
VIEWS.brand = () => page({crumb:'Administration', title:'Logos and branding', sub:'The PlusSave Financial Solutions logo heads every menu. Upload the official file to replace the placeholder.',
  body:`<div class="grid">${[['ps','PlusSave Financial Solutions']].map(([k, l]) => card(l + ' logo', `<div style="display:flex;flex-direction:column;gap:12px">${S.logos[k] ? `<img src="${S.logos[k]}" alt="${l}" style="max-height:80px;width:auto;align-self:flex-start">` : `<div class="note warn">Showing the placeholder mark. Upload a PNG or SVG with a transparent background.</div>`}
    <div class="actions"><label class="btn primary" for="lg_${k}">Upload logo</label><input type="file" id="lg_${k}" accept="image/*" hidden data-change="logoUp" data-k="${k}">${S.logos[k] ? `<button class="btn" data-act="logoReset" data-k="${k}">Use placeholder</button>` : ''}</div></div>`)).join('')}</div>
    ${card('Preview', logos())}<div class="note">In this prototype, uploaded logos last for this session. In the live system they are stored in settings and also printed on payslips, letters and reports.</div>`});
A.logoUp = el => { const f = el.files[0]; if (!f) return; const r = new FileReader(); r.onload = () => { S.logos[el.dataset.k] = r.result; render(); toast('Logo updated on every menu'); }; r.readAsDataURL(f); };
A.logoReset = el => { S.logos[el.dataset.k] = null; render(); };

/* ================= STAFF SELF-SERVICE ================= */
VIEWS.home = () => {
  const e = me(), mine = REQS.filter(r => r.maker === e.id), ret = mine.filter(r => r.status === 'returned'), l = payLine(e, PAY.inputs, PAY.period);
  const trains = TRAININGS.filter(t => t.status === 'Scheduled' && (t.audience === 'All staff' || t.audience.includes(e.dept)));
  const body = `${ret.map(r => `<div class="note warn"><b>${r.id} needs your attention.</b> ${esc(r.hist[r.hist.length - 1].c)} <button class="linkbtn" data-act="open" data-id="${r.id}">Fix and resubmit</button></div>`).join('')}
  <div class="kpis">
    <button class="kpi" data-go="myleave"><span class="l">Annual leave left</span><span class="v">${e.bal.Annual}</span><span class="d">of 21 days</span></button>
    <button class="kpi" data-go="myrequests"><span class="l">Requests in progress</span><span class="v">${mine.filter(r => r.status === 'pending').length}</span><span class="d">${ret.length} returned to you</span></button>
    <button class="kpi" data-go="payslips"><span class="l">October net pay</span><span class="v" style="font-size:22px">${UGX(l.net)}</span><span class="d">${PAY.status === 'Posted' ? 'Paid' : 'Pending approval'}</span></button>
    <button class="kpi" data-go="mytraining"><span class="l">Trainings due</span><span class="v">${trains.length}</span><span class="d">${trains.some(t => t.mandatory) ? '1 mandatory' : 'Optional'}</span></button>
  </div>
  <div class="grid g2">${card('Quick actions', `<div class="toolbar"><button class="btn primary" data-act="applyLeave">Apply for leave</button><button class="btn" data-act="detailsChange">Update my details</button><button class="btn" data-act="attFix">Fix a missed clock-in</button><button class="btn" data-act="trainReq">Request training</button><button class="btn" data-act="newGriev">Raise a grievance</button></div>`)}
    ${card('Notices', `<div class="list">${myNotes().slice(0, 4).map(n => `<div class="li"><div class="grow"><div class="t" style="font-weight:500">${esc(n.text)}</div><div class="s">${DT(n.at)}</div></div></div>`).join('')}</div>`)}</div>
  ${card('My recent requests', table([{h:'Ref', f:r => r.id}, {h:'Request', f:r => `<b>${esc(r.type)}</b><div class="muted" style="font-size:12px">${esc(r.subject)}</div>`}, {h:'Stage', f:r => reqStatus(r)}], mine.slice(0, 5), {click:r => `data-act="open" data-id="${r.id}"`, empty:'No requests yet.'}))}`;
  return page({crumb:'My workspace', title:`Good afternoon, ${e.fn}`, sub:`${esc(e.title)} · ${esc(e.branch)} · supervisor ${esc(nm(emp(e.sup)))}`, body});
};
VIEWS.myrequests = () => page({crumb:'My workspace', title:'My requests', sub:'Track each request through HR and the MD.', body:table([{h:'Ref', f:r => `<span class="mono">${r.id}</span>`}, {h:'Request', f:r => `<b>${esc(r.type)}</b><div class="muted" style="font-size:12px">${esc(r.subject)}</div>`}, {h:'Raised', f:r => DT(r.created)}, {h:'Stage', f:r => reqStatus(r)}], REQS.filter(r => r.maker === USERS.staff.emp), {click:r => `data-act="open" data-id="${r.id}"`, empty:'No requests yet.'})});
VIEWS.payslips = () => { const e = me(); return page({crumb:'Pay and time', title:'My payslips', sub:'Payslips are released after the MD approves payroll and it is posted to your account.', body:payslipHtml(e) + table([{h:'Period', f:p => p.period}, {h:'Status', f:p => pill(p.status, 'ok')}], [...PAYROLL_HIST].reverse())}); };
VIEWS.myattendance = () => { const e = me(), a = ATT[e.id];
  return page({crumb:'Pay and time', title:'My attendance', sub:`Shift starts ${SHIFT_START}.`, actions:'<button class="btn primary" data-act="attFix">Fix a missed clock-in</button>',
    body:`<div class="kpis"><div class="kpi" style="cursor:default"><span class="l">Today</span><span class="v">${a ? a[0] : '-'}</span><span class="d">${a ? esc(a[2]) : 'Not clocked in'}</span></div><div class="kpi" style="cursor:default"><span class="l">Days present (Oct)</span><span class="v">20</span><span class="d">of 21 working days</span></div><div class="kpi" style="cursor:default"><span class="l">Late arrivals (Oct)</span><span class="v">1</span><span class="d">After ${SHIFT_START}</span></div></div>`}); };
A.attFix = () => { const e = me(); formModal({title:'Attendance correction', flow:['staff','hr','md'], fields:[{id:'date', label:'Date', type:'date', val:'2026-10-27'}, {id:'time', label:'Actual time in', type:'time', val:'07:55', req:1}, {id:'reason', label:'Reason', type:'textarea', full:1, req:1, val:'Biometric device at Old Taxi Park was offline in the morning.'}],
  onSubmit:v => { if (!v.reason) return formError('Give a reason.'), false; newReq({type:'Attendance correction', subject:`${D(v.date)}: clock-in ${v.time}`, maker:e.id, note:v.reason, fields:[['Date', D(v.date)], ['Time in', v.time], ['Reason', v.reason]], payload:{emp:e.id, time:v.time}}); toast('Sent to HR'); }}); };
VIEWS.mytraining = () => { const e = me(); return page({crumb:'Growth and support', title:'My trainings', sub:'Trainings you attended and those coming up.', actions:'<button class="btn primary" data-act="trainReq">Request training</button>',
  body:table([{h:'Training', f:t => esc(t.title)}, {h:'Date', f:t => D(t.date)}, {h:'Status', f:t => t.done.includes(e.id) ? pill('Attended', 'ok') : pill(t.status === 'Completed' ? 'Missed' : 'Upcoming', t.status === 'Completed' ? 'plain' : 'gold')}, {h:'Mandatory', f:t => t.mandatory ? pill('Yes', 'warn') : 'No'}], TRAININGS.filter(t => t.done.includes(e.id) || t.audience === 'All staff' || t.audience.includes(e.dept)))}); };
A.trainReq = () => { const e = me(); formModal({title:'Request training', flow:['staff','hr','md'], fields:[{id:'title', label:'Course', req:1, full:1}, {id:'prov', label:'Provider'}, {id:'date', label:'Preferred date', type:'date'}, {id:'cost', label:'Cost (UGX)', type:'number'}, {id:'why', label:'How it helps your work', type:'textarea', full:1, req:1}],
  onSubmit:v => { if (!v.title || !v.why) return formError('Enter the course and how it helps.'), false; newReq({type:'Training request', subject:v.title, maker:e.id, note:v.why, fields:[['Course', v.title], ['Provider', v.prov || '-'], ['Date', D(v.date)], ['Cost', v.cost ? UGX(v.cost) : '-']], payload:{emp:e.id, title:v.title, provider:v.prov || '-', date:v.date || ISO(TODAY)}}); toast('Sent to HR'); }}); };
A.detailsChange = () => { const e = me(); const map = {phone:['Phone', e.phone], email:['Email', e.email], addr:['Address', e.addr], 'nok.n':['Next of kin name', e.nok.n], 'nok.p':['Next of kin contact', e.nok.p]};
  formModal({title:'Request a change to my details', flow:['staff','hr','md'], fields:[{id:'field', label:'What to change', type:'select', opts:Object.entries(map).map(([k, v]) => [k, v[0]]), full:1}, {id:'val', label:'New value', req:1, full:1}, {id:'doc', label:'Supporting document', type:'file', full:1}, {type:'calc'}],
    onInput:v => { $('#calc').innerHTML = `Current value: <b>${esc(map[v.field][1])}</b>`; },
    onSubmit:v => { if (!v.val) return formError('Enter the new value.'), false; newReq({type:'Personal details change', subject:`${map[v.field][0]} change`, maker:e.id, fields:[['Field', map[v.field][0]], ['Current value', map[v.field][1]], ['New value', v.val], ['Supporting document', v.doc || 'None']], payload:{emp:e.id, field:v.field, value:v.val}}); toast('Sent to HR'); }}); };
EFFECTS['Personal details change'] = r => { const e = emp(r.payload.emp), f = r.payload.field; if (f.startsWith('nok.')) e.nok[f.slice(4)] = r.payload.value; else e[f] = r.payload.value; };

/* ================= START ================= */
$('#bell').addEventListener('click', A.bell);
$('#burger').addEventListener('click', () => $('#side').classList.toggle('open'));
$('#q').addEventListener('input', e => { S.q = e.target.value; if (S.role === 'staff') return; if (S.view !== 'employees') go('employees'); else render(); });
render();
