/* ================= DASHBOARD ================= */
function metrics() {
  const act = activeEmp(), t = ISO(TODAY);
  const onLeave = act.filter(e => e.status === 'On Leave');
  const absent = act.filter(e => !ATT[e.id] && e.status === 'Active');
  const late = act.filter(e => ATT[e.id] && ATT[e.id][0] > SHIFT_START);
  const contracts = act.filter(e => e.cEnd && fromToday(e.cEnd) <= 90).sort((a, b) => a.cEnd.localeCompare(b.cEnd));
  const probation = act.filter(e => e.type === 'Probationary' && e.pEnd && fromToday(e.pEnd) <= 45).sort((a, b) => a.pEnd.localeCompare(b.pEnd));
  const appraisal = APPRAISALS.filter(a => a.status !== 'Completed' && fromToday(a.due) <= 30).sort((a, b) => a.due.localeCompare(b.due));
  const newStaff = act.filter(e => days(e.joined, t) <= 90);
  const exits = EXITS.filter(x => days(x.lwd || x.notice, t) <= 90);
  const trainDue = TRAININGS.filter(x => x.status === 'Scheduled' && fromToday(x.date) <= 30 && fromToday(x.date) >= 0);
  const cases = CASES.filter(c => c.stage !== 'Closed');
  return {act, onLeave, absent, late, contracts, probation, appraisal, newStaff, exits, trainDue, cases};
}
const countBy = (arr, k, keys) => (keys || [...new Set(arr.map(e => e[k]))]).map(v => [v, arr.filter(e => e[k] === v).length]);
VIEWS.dashboard = () => {
  const m = metrics(), reg = payRegister(), pend = awaiting();
  const kpi = (l, v, d, go, cls = '') => `<button class="kpi ${cls}" data-go="${go}"><span class="l">${l}</span><span class="v">${v}</span><span class="d">${d}</span></button>`;
  const upcoming = LEAVES.filter(l => l.status === 'Approved' && l.to >= ISO(TODAY)).sort((a, b) => a.from.localeCompare(b.from));
  const body = `
  ${S.role === 'md' && pend.length ? `<div class="note warn"><b>${pend.length} item${pend.length > 1 ? 's' : ''} waiting for your approval.</b> HR has verified them. <button class="linkbtn" data-go="approvals">Open approvals</button></div>` : ''}
  <div class="kpis">
    ${kpi('Employee count', m.act.length, `${m.act.filter(e => e.status === 'Active').length} active, ${m.onLeave.length} on leave`, 'employees')}
    ${kpi('Waiting for me', pend.length, S.role === 'hr' ? 'Staff requests to verify' : 'Verified by HR', 'approvals', pend.length ? 'alert' : '')}
    ${kpi('Staff absent today', m.absent.length, `${m.late.length} late after ${SHIFT_START}`, 'attendance', m.absent.length ? 'alert' : '')}
    ${kpi('Contracts expiring', m.contracts.length, 'Next 90 days', 'probation', m.contracts.some(e => fromToday(e.cEnd) <= 30) ? 'critical' : 'alert')}
    ${kpi('Probation ending', m.probation.length, 'Next 45 days', 'probation', m.probation.length ? 'alert' : '')}
    ${kpi('Due for appraisal', m.appraisal.length, 'Next 30 days', 'performance')}
    ${kpi('Vacant positions', VACANCIES.length, `${VACANCIES.reduce((s, v) => s + v.applicants, 0)} applicants`, 'recruitment')}
    ${kpi('Trainings due', m.trainDue.length, 'Next 30 days', 'training')}
    ${kpi('Cases due action', m.cases.length, 'Disciplinary and grievance', 'cases', m.cases.length ? 'alert' : '')}
    ${kpi('October payroll', 'UGX ' + (reg.tot.gross / 1e6).toFixed(1) + 'M', `${reg.lines.length} staff · ${PAY.status}`, 'payroll')}
  </div>
  <div class="grid g3">
    ${card('Employees per branch', bars(countBy(m.act, 'branch', BRANCHES).map(([k, v]) => [k, v, 'branch:' + k])), 'Tap a bar to filter')}
    ${card('Employees per department', bars(countBy(m.act, 'dept', DEPTS).filter(x => x[1]).map(([k, v]) => [k, v, 'dept:' + k])))}
    ${card('Employment type', bars(countBy(m.act, 'type', TYPES).map(([k, v]) => [k, v, 'type:' + k])), 'Permanent, contract, probationary, part-time, intern')}
  </div>
  <div class="grid g3">
    ${card('Leave tracker', `<div class="list">${upcoming.slice(0, 5).map(l => `<div class="li">${av(emp(l.emp))}<div class="grow"><div class="t">${esc(nm(emp(l.emp)))}</div><div class="s">${l.type} · ${D(l.from)} to ${D(l.to)}</div></div>${l.from <= ISO(TODAY) ? pill('On leave', 'info') : pill('In ' + fromToday(l.from) + 'd', 'plain')}</div>`).join('') || '<div class="empty">No one on leave.</div>'}</div>`, `<button class="linkbtn" data-go="leave">All leave</button>`)}
    ${card('Contract expiry and probation end', `<div class="list">${[...m.contracts.map(e => [e, 'Contract ends', e.cEnd]), ...m.probation.map(e => [e, 'Probation ends', e.pEnd])].sort((a, b) => a[2].localeCompare(b[2])).map(([e, l, d]) => { const n = fromToday(d); return `<div class="li" data-go="profile" data-p="${e.id}" style="cursor:pointer">${av(e)}<div class="grow"><div class="t">${esc(nm(e))}</div><div class="s">${l} ${D(d)}</div></div>${pill(n + ' days', n <= 30 ? 'crit' : 'warn')}</div>`; }).join('') || '<div class="empty">Nothing due.</div>'}</div>`, 'Alerts at 90, 60 and 30 days')}
    ${card('Staff due for appraisal', `<div class="list">${m.appraisal.map(a => `<div class="li">${av(emp(a.emp))}<div class="grow"><div class="t">${esc(nm(emp(a.emp)))}</div><div class="s">${a.cycle} · ${a.status}</div></div>${pill(D(a.due), fromToday(a.due) <= 7 ? 'warn' : 'plain')}</div>`).join('')}</div>`)}
  </div>
  <div class="grid g3">
    ${card('Staff absent today', `<div class="list">${m.absent.map(e => `<div class="li">${av(e)}<div class="grow"><div class="t">${esc(nm(e))}</div><div class="s">${esc(e.branch)} · no clock-in, no approved leave</div></div>${pill('Absent', 'crit')}</div>`).join('') || '<div class="empty">Everyone is in.</div>'}${m.late.map(e => `<div class="li">${av(e)}<div class="grow"><div class="t">${esc(nm(e))}</div><div class="s">Clocked in ${ATT[e.id][0]}</div></div>${pill('Late', 'warn')}</div>`).join('')}</div>`)}
    ${card('New staff and employee exits', `<div class="list">${m.newStaff.map(e => `<div class="li">${av(e)}<div class="grow"><div class="t">${esc(nm(e))}</div><div class="s">${esc(e.title)} · joined ${D(e.joined)}</div></div>${pill('New', 'ok')}</div>`).join('')}${m.exits.map(x => `<div class="li">${av(emp(x.emp))}<div class="grow"><div class="t">${esc(nm(emp(x.emp)))}</div><div class="s">${x.type} · last day ${D(x.lwd)}</div></div>${pill('Exit', '')}</div>`).join('')}</div>`, 'Last 90 days')}
    ${card('Recruitment: vacant positions', `<div class="list">${VACANCIES.map(v => `<div class="li"><div class="grow"><div class="t">${esc(v.title)}</div><div class="s">${esc(v.branch)} · ${v.applicants} applicants · closes ${D(v.closes)}</div></div>${pill(v.stage, 'info')}</div>`).join('')}</div>`)}
  </div>
  <div class="grid g3">
    ${card('Trainings due', `<div class="list">${m.trainDue.map(t => `<div class="li"><div class="grow"><div class="t">${esc(t.title)}</div><div class="s">${esc(t.audience)} · ${D(t.date)}</div></div>${t.mandatory ? pill('Mandatory', 'warn') : pill('Optional', 'plain')}</div>`).join('') || '<div class="empty">None in the next 30 days.</div>'}</div>`)}
    ${card('Disciplinary cases due action', `<div class="list">${m.cases.map(c => `<div class="li" data-go="cases" style="cursor:pointer"><div class="grow"><div class="t">${c.id} · ${esc(nm(emp(c.emp)))}</div><div class="s">${esc(c.category)}</div></div>${pill(c.stage + (c.due ? ' · ' + D(c.due) : ''), 'warn')}</div>`).join('')}</div>`)}
    ${card('Payroll and headcount summary', table([{h:'Period', f:p => p.period}, {h:'Staff', r:1, f:p => p.staff}, {h:'Gross (UGX)', r:1, f:p => N(p.gross)}, {h:'Status', f:p => pill(p.status, p.status === 'Posted' ? 'ok' : 'gold')}],
       [...PAYROLL_HIST, {period:'2026-10', staff:reg.lines.length, gross:reg.tot.gross, status:PAY.status}].reverse()))}
  </div>`;
  return page({crumb:'Overview', title:S.role === 'md' ? 'Managing Director dashboard' : 'HR dashboard', sub:'Headcount, alerts and approvals across Saasa Plaza, Old Taxi Park and Kikuubo.', actions:S.role === 'hr' ? `<button class="btn primary" data-act="addEmp">${ic('plus').replace('<svg', '<svg width="15" height="15"')} Add employee</button><button class="btn" data-go="reports">HR reports</button>` : `<button class="btn primary" data-go="approvals">Review approvals (${pend.length})</button>`, body});
};

/* ================= APPROVALS ================= */
VIEWS.approvals = () => {
  const tab = curTab('appr', 'wait');
  const ft = S.tab.apprType || '';
  let rows = tab === 'wait' ? awaiting() : tab === 'mine' ? REQS.filter(r => isMine(r)) : REQS;
  if (ft) rows = rows.filter(r => r.type === ft);
  const types = [...new Set(REQS.map(r => r.type))];
  const body = `<div class="note"><b>How approvals work.</b> Staff requests go Staff → HR (verify) → MD (approve). Requests HR raises go HR → MD. Nobody can approve a request they created. Returned items go back one step with a comment; every action is written to the audit trail.</div>
  ${tabs('appr', [['wait', `Waiting for me (${awaiting().length})`], ['mine', 'Submitted by me'], ['all', 'All requests']])}
  <div class="toolbar"><select id="apprType" data-change="apprType" aria-label="Filter by type"><option value="">All request types</option>${types.map(t => `<option ${t === ft ? 'selected' : ''}>${esc(t)}</option>`).join('')}</select></div>
  ${table([{h:'Ref', f:r => `<span class="mono">${r.id}</span>`}, {h:'Request', f:r => `<b>${esc(r.type)}</b><div class="muted" style="font-size:12px">${esc(r.subject)}</div>`},
    {h:'Maker', f:r => person(emp(r.maker), ROLE_NAME[r.makerRole])}, {h:'Raised', f:r => DT(r.created)}, {h:'Stage', f:r => reqStatus(r)}, {h:'SLA', f:r => sla(r)}],
    rows, {click:r => `data-act="open" data-id="${r.id}"`, empty:tab === 'wait' ? 'Nothing is waiting for you. New requests will appear here.' : 'No requests yet.'})}`;
  return page({crumb:'Overview', title:'Approvals', sub:'Maker-checker queue. Open a row to see details, history and actions.', body});
};
A.apprType = el => { S.tab.apprType = el.value; render(); };

/* ================= EMPLOYEES ================= */
VIEWS.employees = () => {
  const f = S.ef || (S.ef = {branch:'', dept:'', type:'', status:'Current'});
  if (S.filter) { const [k, v] = S.filter.split(':'); f[k] = v; S.filter = null; }
  const q = S.q.toLowerCase();
  const rows = EMP.filter(e => (!f.branch || e.branch === f.branch) && (!f.dept || e.dept === f.dept) && (!f.type || e.type === f.type) &&
    (f.status === 'All' || (f.status === 'Current' ? !['Resigned','Terminated'].includes(e.status) : e.status === f.status)) &&
    (!q || [nm(e), e.id, e.title, e.branch, e.dept].join(' ').toLowerCase().includes(q)));
  const sel = (id, opts, v, all) => `<select data-change="ef" data-k="${id}" aria-label="${id}"><option value="">${all}</option>${opts.map(o => `<option ${o === v ? 'selected' : ''}>${esc(o)}</option>`).join('')}</select>`;
  const body = `<div class="toolbar">${sel('branch', BRANCHES, f.branch, 'All branches')}${sel('dept', DEPTS, f.dept, 'All departments')}${sel('type', TYPES, f.type, 'All employment types')}
    <select data-change="ef" data-k="status" aria-label="status">${['Current','All',...STATUSES].map(o => `<option ${o === f.status ? 'selected' : ''}>${o}</option>`).join('')}</select>
    ${q ? `<span class="chip">Search: “${esc(S.q)}”</span> <button class="linkbtn" data-act="clearq">Clear</button>` : ''}<span class="muted" style="margin-left:auto">${rows.length} employees</span></div>
  ${table([{h:'Employee', f:e => person(e)}, {h:'ID', f:e => `<span class="mono">${e.id}</span>`}, {h:'Department', f:e => esc(e.dept)}, {h:'Branch', f:e => esc(e.branch)}, {h:'Type', f:e => esc(e.type)}, {h:'Status', f:e => empStatusPill(e.status)}, {h:'Joined', f:e => D(e.joined)}],
    rows, {click:e => `data-go="profile" data-p="${e.id}"`, empty:'No employees match these filters.'})}`;
  return page({crumb:'People', title:'Employees', sub:'Every employee file, with job details, documents and history in one place.', actions:S.role === 'hr' ? `<button class="btn primary" data-act="addEmp">Add employee</button>` : '', body});
};
A.ef = el => { S.ef[el.dataset.k] = el.value; render(); };
A.clearq = () => { S.q = ''; $('#q').value = ''; render(); };

/* ================= PROFILE ================= */
VIEWS.myprofile = () => VIEWS.profile(USERS.staff.emp);
VIEWS.profile = id => {
  const e = emp(id) || me(), self = S.role === 'staff', hr = S.role === 'hr';
  const tab = curTab('prof', 'file');
  const sup = emp(e.sup);
  const kv = pairs => `<dl class="kv">${pairs.map(([k, v]) => `<dt>${esc(k)}</dt><dd>${v}</dd>`).join('')}</dl>`;
  let content = '';
  if (tab === 'file') content = card('Employee file', kv([['Employee ID', `<span class="mono">${e.id}</span>`], ['Name', esc(nm(e))], ['Gender', e.g === 'F' ? 'Female' : 'Male'], ['Date of birth', `${D(e.dob)} (${age(e)} years)`], ['National ID (NIN)', `<span class="mono">${esc(e.nin)}</span> <span class="faint">masked</span>`], ['Phone', esc(e.phone)], ['Email', esc(e.email)], ['Address', esc(e.addr)], ['Next of kin', `${esc(e.nok.n)} (${esc(e.nok.rel)})`], ['Next of kin contact', esc(e.nok.p)], ['Photograph', e.photo ? 'Uploaded' : `<label class="btn sm" for="photoUp">Upload photo</label><input type="file" id="photoUp" accept="image/*" data-change="photo" data-id="${e.id}" hidden>`]]));
  if (tab === 'job') content = card('Employee information', kv([['Job title', esc(e.title)], ['Department', esc(e.dept)], ['Branch', esc(e.branch)], ['Supervisor', sup ? esc(nm(sup) + ', ' + sup.title) : 'Board of Directors'], ['Employment type', esc(e.type)], ['Date joined / contract start', D(e.joined)], ['Contract end', e.cEnd ? `${D(e.cEnd)} ${pill(fromToday(e.cEnd) + ' days left', fromToday(e.cEnd) <= 30 ? 'crit' : 'warn')}` : 'Not applicable'], ['Probation', e.pStart ? `${D(e.pStart)} to ${D(e.pEnd)}` : 'Completed or not applicable'], ['Grade', `${e.grade} · ${GRADES[e.grade].name}`], ['Basic salary', UGX(e.basic)], ['Allowances', (() => { const a = allw(e); return `Transport ${N(a.tr)} · Housing ${N(a.hs)} · Airtime ${N(a.at)}`; })()], ['Salary account (core banking)', `<span class="mono">${e.acct}</span>`], ['Status', empStatusPill(e.status) + ' <span class="faint">Active, On Leave, Suspended, Resigned, Terminated</span>']]));
  if (tab === 'docs') content = card('Documents', `<div class="list">${Object.entries(e.docs).map(([k, v]) => `<div class="li">${ic('file').replace('<svg', '<svg width="18" height="18"')}<div class="grow"><div class="t">${esc(k)}</div><div class="s">${v ? 'PDF on file · uploaded by HR' : 'Not uploaded'}</div></div>${v ? pill('On file', 'ok') : pill('Missing', k === 'Disciplinary letters' || k === 'Exit documents' ? 'plain' : 'warn')}${hr || self ? `<label class="btn sm" for="up_${k.replace(/\W/g, '')}">${v ? 'Replace' : 'Upload'}</label><input type="file" id="up_${k.replace(/\W/g, '')}" hidden data-change="docUp" data-id="${e.id}" data-k="${esc(k)}">` : ''}</div>`).join('')}</div>`, 'CV, academic documents, appointment letter, contract, NDA, JD, appraisals, disciplinary and exit documents');
  if (tab === 'leave') content = `<div class="kpis">${Object.entries(LEAVE_TYPES).map(([k, v]) => `<div class="kpi" style="cursor:default"><span class="l">${k}</span><span class="v">${e.bal[k] ?? v.days}</span><span class="d">of ${v.days} days</span></div>`).filter((_, i) => !(['Maternity'].includes(Object.keys(LEAVE_TYPES)[i]) && e.g === 'M') && !(['Paternity'].includes(Object.keys(LEAVE_TYPES)[i]) && e.g === 'F')).join('')}</div>` +
    table([{h:'Ref', f:l => l.id}, {h:'Type', f:l => l.type}, {h:'From', f:l => D(l.from)}, {h:'To', f:l => D(l.to)}, {h:'Days', r:1, f:l => l.days}, {h:'Status', f:l => pill(l.status, 'ok')}], LEAVES.filter(l => l.emp === e.id), {empty:'No leave taken this year.'});
  if (tab === 'att') { const a = ATT[e.id]; content = card('Attendance', kv([['Today', a ? `In ${a[0]} · ${esc(a[2])} ${a[0] > SHIFT_START ? pill('Late', 'warn') : pill('On time', 'ok')}` : e.status === 'On Leave' ? pill('On leave', 'info') : pill('Absent', 'crit')], ['Shift', `Starts ${SHIFT_START}, ends 17:00`], ['This month', `${18 - (e.id.charCodeAt(7) % 3)} days present · ${e.id.charCodeAt(8) % 3} late arrivals`]])); }
  if (tab === 'perf') content = table([{h:'Cycle', f:a => a.cycle}, {h:'Due', f:a => D(a.due)}, {h:'Status', f:a => pill(a.status, a.status === 'Completed' ? 'ok' : 'gold')}, {h:'Score', r:1, f:a => a.score ?? '-'}], APPRAISALS.filter(a => a.emp === e.id), {empty:'No appraisals recorded.'});
  if (tab === 'move') content = table([{h:'Effective', f:m => D(m.date)}, {h:'Change', f:m => esc(m.kind)}, {h:'Previous position', f:m => esc(m.prev)}, {h:'New position', f:m => esc(m.next)}, {h:'Reason', f:m => esc(m.reason)}], e.movements, {empty:'No transfers, promotions or salary adjustments yet.'});
  if (tab === 'disc') content = table([{h:'Case', f:c => c.id}, {h:'Type', f:c => c.kind}, {h:'Category', f:c => esc(c.category)}, {h:'Stage', f:c => pill(c.stage, c.stage === 'Closed' ? 'ok' : 'warn')}, {h:'Decision', f:c => esc(c.decision || 'Pending')}], CASES.filter(c => c.emp === e.id), {empty:'No disciplinary or grievance records.'});
  if (tab === 'train') content = table([{h:'Training', f:t => esc(t.title)}, {h:'Provider', f:t => esc(t.provider)}, {h:'Date', f:t => D(t.date)}, {h:'Status', f:t => pill(t.done.includes(e.id) ? 'Attended' : t.status, t.done.includes(e.id) ? 'ok' : 'gold')}], TRAININGS.filter(t => t.done.includes(e.id) || (t.status === 'Scheduled' && (t.audience === 'All staff' || t.audience.includes(e.dept)))), {empty:'No training history.'});
  if (tab === 'pay') content = payslipHtml(e);
  if (tab === 'exit') { const x = EXITS.find(x => x.emp === e.id); content = x ? exitCard(x) : `<div class="empty">No exit record. ${hr ? '<button class="linkbtn" data-act="startExit" data-id="' + e.id + '">Start an exit</button>' : ''}</div>`; }
  const head = `<section class="card" style="display:flex;gap:16px;align-items:center;flex-wrap:wrap">${av(e, 'lg')}<div style="flex:1;min-width:220px"><h2 style="font-size:22px">${esc(nm(e))}</h2><div class="muted">${esc(e.title)} · ${esc(e.dept)} · ${esc(e.branch)}</div><div style="display:flex;gap:6px;margin-top:8px;flex-wrap:wrap">${empStatusPill(e.status)}<span class="chip mono">${e.id}</span><span class="chip">${esc(e.type)}</span><span class="chip">${tenure(e).toFixed(1)} yrs service</span></div></div>
    <div class="actions">${hr ? `<button class="btn" data-act="movement" data-id="${e.id}">Transfer, promote or adjust salary</button><button class="btn" data-act="newCase" data-id="${e.id}">Open disciplinary case</button>` : ''}${self ? `<button class="btn primary" data-act="detailsChange">Request a change to my details</button>` : ''}</div></section>`;
  return page({crumb:self ? 'My workspace' : 'People / Employees', title:self ? 'My employee file' : 'Employee profile', body:head + tabs('prof', [['file','Employee file'],['job','Employee information'],['docs','Documents'],['leave','Leave'],['att','Attendance'],['perf','Performance'],['move','Movements'],['disc','Disciplinary'],['train','Training'],['pay','Pay'],['exit','Exit']]) + content});
};
A.photo = el => { const f = el.files[0]; if (!f) return; const rd = new FileReader(); rd.onload = () => { emp(el.dataset.id).photo = rd.result; audit('Uploaded photo', el.dataset.id, f.name); render(); toast('Photo uploaded'); }; rd.readAsDataURL(f); };
A.docUp = el => { if (!el.files[0]) return; emp(el.dataset.id).docs[el.dataset.k] = 1; audit('Uploaded document', el.dataset.id, `${el.dataset.k}: ${el.files[0].name}`); render(); toast(`${el.dataset.k} uploaded`); };

/* Add employee (HR maker → MD) */
A.addEmp = () => {
  const nextId = 'PSF-' + String(Math.max(...EMP.map(e => +e.id.slice(4))) + 1).padStart(4, '0');
  formModal({title:'Add employee', sub:'HR is the maker. The new file goes live after the MD approves.', flow:['hr','md'], fields:[
    {sec:'1. Employee file'}, {id:'id', label:'Employee ID', val:nextId, ro:1, hint:'Generated automatically'}, {id:'photo', label:'Photograph', type:'file', accept:'image/*'},
    {id:'fn', label:'First name', req:1}, {id:'ln', label:'Last name', req:1}, {id:'g', label:'Gender', type:'select', opts:[['F','Female'],['M','Male']]}, {id:'dob', label:'Date of birth', type:'date', req:1},
    {id:'nin', label:'National ID (NIN)', req:1, hint:'Stored encrypted; shown masked'}, {id:'phone', label:'Phone', req:1}, {id:'email', label:'Email'}, {id:'addr', label:'Address'},
    {id:'nokn', label:'Next of kin name', req:1}, {id:'nokp', label:'Next of kin contact', req:1},
    {sec:'2. Employee information'}, {id:'title', label:'Job title', req:1}, {id:'dept', label:'Department', type:'select', opts:DEPTS}, {id:'branch', label:'Branch', type:'select', opts:BRANCHES},
    {id:'sup', label:'Supervisor', type:'select', opts:activeEmp().filter(e => ['G1','G2','G3'].includes(e.grade)).map(e => [e.id, nm(e) + ', ' + e.title])},
    {id:'type', label:'Employment type', type:'select', opts:TYPES, val:'Probationary'}, {id:'joined', label:'Date joined / contract start', type:'date', req:1},
    {id:'pEnd', label:'Probation end', type:'date', hint:'Default 6 months from start'}, {id:'cEnd', label:'Contract end', type:'date', hint:'For contract and intern staff'},
    {id:'grade', label:'Grade', type:'select', opts:Object.entries(GRADES).map(([k, g]) => [k, k + ' · ' + g.name]), val:'G4'}, {id:'basic', label:'Basic salary (UGX)', type:'number', req:1},
    {sec:'3. Documents'}, {id:'docs', label:'CV, academic documents, appointment letter, contract, NDA, JD, ID copy', type:'file', multiple:1, full:1},
    {sec:'4. Accounts in other systems'}, {id:'sys', label:'Create on approval', type:'select', opts:['Core banking user + PlusPay login + email','Core banking user + email','Email only'], full:1, hint:'Sent to IT automatically once the MD approves'}],
    onSubmit:v => {
      const miss = ['fn','ln','dob','nin','phone','nokn','nokp','title','joined','basic'].filter(k => !v[k]); if (miss.length) return formError('Fill all required fields marked *.'), false;
      const pStart = v.type === 'Probationary' ? v.joined : null; const pEnd = v.pEnd || (pStart ? ISO(new Date(new Date(v.joined).setMonth(new Date(v.joined).getMonth() + 6))) : null);
      const e = E(v.id, v.fn, v.ln, v.g, v.dob, v.title, v.dept, v.branch, v.sup, v.type, v.joined, v.grade, +v.basic, {pStart, pEnd, cEnd:v.cEnd || null, phone:v.phone, nin:v.nin.slice(0, 3) + '••••••' + v.nin.slice(-3), addr:v.addr || '-', nok:{n:v.nokn, rel:'Next of kin', p:v.nokp}, bal:{Annual:0, Sick:30, Other:6}});
      if (v.email) e.email = v.email;
      newReq({type:'New employee', subject:`${v.fn} ${v.ln}, ${v.title}`, maker:USERS.hr.emp, note:'', fields:[['Employee ID', v.id], ['Name', `${v.fn} ${v.ln}`], ['Job title', v.title], ['Department', v.dept], ['Branch', v.branch], ['Employment type', v.type], ['Start date', D(v.joined)], ['Basic salary', UGX(v.basic)], ['Documents', v.docs || 'None attached'], ['Other systems', v.sys]], payload:{e}});
      toast('Sent to the MD for approval');
    }});
};

/* ================= ATTENDANCE ================= */
VIEWS.attendance = () => {
  const m = metrics();
  const rows = m.act.map(e => { const a = ATT[e.id]; const st = a ? (a[0] > SHIFT_START ? 'Late' : 'Present') : e.status === 'On Leave' ? 'On leave' : 'Absent'; return {e, a, st}; });
  const body = `<div class="kpis">${[['Present', rows.filter(r => r.st === 'Present').length], ['Late', rows.filter(r => r.st === 'Late').length], ['Absent', rows.filter(r => r.st === 'Absent').length], ['On leave', rows.filter(r => r.st === 'On leave').length]].map(([l, v]) => `<div class="kpi" style="cursor:default"><span class="l">${l}</span><span class="v">${v}</span><span class="d">Today</span></div>`).join('')}</div>
  <div class="note"><b>Clock in and out.</b> Biometric devices at each branch sync every 15 minutes. Mobile clock-in works only inside a branch geo-fence. Missed punches are fixed through an attendance correction (Staff → HR → MD).</div>
  ${table([{h:'Employee', f:r => person(r.e)}, {h:'Branch', f:r => esc(r.e.branch)}, {h:'Clock in', f:r => r.a ? `<span class="mono">${r.a[0]}</span>` : '-'}, {h:'Clock out', f:r => r.a ? '<span class="faint">Expected 17:00</span>' : '-'}, {h:'Source', f:r => r.a ? esc(r.a[2]) : '-'}, {h:'Status', f:r => pill(r.st, {Present:'ok', Late:'warn', Absent:'crit', 'On leave':'info'}[r.st])}], rows)}`;
  return page({crumb:'People', title:'Attendance', sub:`Thursday 29 October 2026 · shift starts ${SHIFT_START}`, actions:`<button class="btn" data-act="sync">Sync devices now</button>`, body});
};
A.sync = () => { audit('Synced attendance devices', 'ATT', '3 branch devices'); toast('3 branch devices synced. No new punches.'); };

/* ================= LEAVE ================= */
VIEWS.leave = () => {
  const tab = curTab('lv', 'track');
  let content;
  if (tab === 'track') content = table([{h:'Employee', f:l => person(emp(l.emp))}, {h:'Type', f:l => l.type}, {h:'From', f:l => D(l.from)}, {h:'To', f:l => D(l.to)}, {h:'Days', r:1, f:l => l.days}, {h:'Status', f:l => l.from <= ISO(TODAY) && l.to >= ISO(TODAY) ? pill('On leave now', 'info') : l.to < ISO(TODAY) ? pill('Taken', 'plain') : pill('Upcoming', 'ok')}], LEAVES)
    + `<h3 style="font-size:15px;margin-top:6px">Leave requests in the approval queue</h3>` + table([{h:'Ref', f:r => r.id}, {h:'Employee', f:r => person(emp(r.payload.emp))}, {h:'Request', f:r => esc(r.subject)}, {h:'Stage', f:r => reqStatus(r)}], REQS.filter(r => r.type === 'Leave application' && r.status !== 'approved'), {click:r => `data-act="open" data-id="${r.id}"`});
  if (tab === 'bal') content = table([{h:'Employee', f:e => person(e)}, {h:'Annual', r:1, f:e => e.bal.Annual}, {h:'Sick', r:1, f:e => e.bal.Sick}, {h:'Other', r:1, f:e => e.bal.Other}, {h:'Unused annual leave value', r:1, f:e => UGX(leavePay(e, e.bal.Annual))}], activeEmp());
  if (tab === 'rules') content = table([{h:'Leave type', f:([k]) => `<b>${k}</b>`}, {h:'Entitlement', f:([, v]) => v.days + ' days'}, {h:'Rule', f:([, v]) => esc(v.note)}, {h:'Approval', f:() => 'Staff → HR → MD'}], Object.entries(LEAVE_TYPES))
    + `<div class="note warn"><b>Leave pay when leave is not taken.</b> Unused annual leave is paid on exit as part of final dues. Paying cash instead of leave while still employed needs a written PlusSave policy and MD approval; legal should confirm this against the Employment Act, which treats annual leave as a right. Formula: basic salary ÷ ${CONFIG.leaveDivisor} × unused days.</div>`;
  if (tab === 'hol') content = table([{h:'Date', f:d => D(d)}, {h:'Day', f:d => new Date(d).toLocaleDateString('en-GB', {weekday:'long'})}], HOLIDAYS) + '<div class="note">Public holidays and weekends are excluded when counting leave days. HR updates this list when Government gazettes Eid dates.</div>';
  return page({crumb:'People', title:'Leave management', sub:'Annual, sick, maternity, paternity, unpaid and other leave.', body:tabs('lv', [['track','Leave tracker'],['bal','Balances'],['rules','Leave types and rules'],['hol','Public holidays']]) + content});
};

/* Staff: apply for leave */
VIEWS.myleave = () => {
  const e = me();
  const body = `<div class="kpis">${Object.entries(LEAVE_TYPES).filter(([, v]) => !v.g || v.g === e.g).map(([k, v]) => `<div class="kpi" style="cursor:default"><span class="l">${k}</span><span class="v">${e.bal[k] ?? v.days}</span><span class="d">days available</span></div>`).join('')}</div>
  ${card('How your leave is approved', flowHtml(['staff','hr','md']) + '<p class="muted" style="margin:10px 0 0">HR checks your balance and reliever, then the MD approves. You get an email and SMS at each step.</p>')}
  ${card('My leave history', table([{h:'Type', f:l => l.type}, {h:'From', f:l => D(l.from)}, {h:'To', f:l => D(l.to)}, {h:'Days', r:1, f:l => l.days}, {h:'Status', f:l => pill(l.status, 'ok')}], LEAVES.filter(l => l.emp === e.id)))}`;
  return page({crumb:'My workspace', title:'Apply for leave', sub:'Working days are counted for you, skipping weekends and public holidays.', actions:`<button class="btn primary" data-act="applyLeave">Apply for leave</button>`, body});
};
A.applyLeave = () => {
  const e = me();
  const types = Object.entries(LEAVE_TYPES).filter(([, v]) => !v.g || v.g === e.g).map(([k]) => k);
  formModal({title:'Apply for leave', sub:`${nm(e)} · ${e.title}`, flow:['staff','hr','md'], fields:[
    {id:'type', label:'Leave type', type:'select', opts:types}, {id:'reliever', label:'Reliever (who covers your desk)', type:'select', opts:activeEmp().filter(x => x.id !== e.id && x.branch === e.branch).map(x => [x.id, nm(x)])},
    {id:'from', label:'From', type:'date', req:1, val:'2026-11-09'}, {id:'to', label:'To', type:'date', req:1, val:'2026-11-13'},
    {type:'calc'}, {id:'reason', label:'Reason', type:'textarea', full:1}, {id:'att', label:'Attachment (medical form, burial notice, etc.)', type:'file', full:1}],
    onInput:v => { const d = workingDays(v.from, v.to), bal = e.bal[v.type] ?? LEAVE_TYPES[v.type].days, after = bal - d, notice = v.from ? fromToday(v.from) : 0;
      $('#calc').innerHTML = `<b>${d} working day${d === 1 ? '' : 's'}</b> · balance ${bal} → ${after}${after < 0 ? ' <span class="pill crit">Not enough balance</span>' : ''}${v.type === 'Annual' && notice < 7 ? ' <span class="pill warn">Less than 7 days notice</span>' : ''}${v.type === 'Sick' && d > 2 ? ' <span class="pill warn">Medical form required</span>' : ''}<div class="muted" style="font-size:12px;margin-top:4px">${esc(LEAVE_TYPES[v.type].note)}</div>`; },
    onSubmit:v => { const d = workingDays(v.from, v.to); const bal = e.bal[v.type] ?? LEAVE_TYPES[v.type].days;
      if (!v.from || !v.to || d < 1) return formError('Choose a start and end date that include at least one working day.'), false;
      if (d > bal && v.type !== 'Unpaid') return formError(`You have ${bal} ${v.type.toLowerCase()} days left. Shorten the dates or choose Unpaid leave.`), false;
      if (v.type === 'Sick' && d > 2 && !v.att) return formError('Attach a medical form for sick leave longer than 2 days.'), false;
      newReq({type:'Leave application', subject:`${v.type} leave, ${D(v.from)} to ${D(v.to)} (${d} days)`, maker:e.id, note:v.reason,
        fields:[['Leave type', v.type], ['Dates', `${D(v.from)} to ${D(v.to)}`], ['Working days', String(d)], ['Balance before', `${bal} days`], ['Reliever', nm(emp(v.reliever))], ['Attachment', v.att || 'None']],
        payload:{emp:e.id, type:v.type, from:v.from, to:v.to, days:d}});
      toast('Sent to HR for review'); }});
};
