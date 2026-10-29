/* ================= PERFORMANCE ================= */
VIEWS.performance = () => {
  const done = APPRAISALS.filter(a => a.score != null);
  const avg = done.length ? (done.reduce((s, a) => s + a.score, 0) / done.length).toFixed(1) : '-';
  const body = `<div class="kpis">${[['Due in 30 days', APPRAISALS.filter(a => a.status !== 'Completed' && fromToday(a.due) <= 30).length, 'Alerts sent to staff and supervisors'], ['Completed this year', done.length, 'H1 2026 cycle'], ['Average score', avg, 'Scale 1 to 5']].map(([l, v, d]) => `<div class="kpi" style="cursor:default"><span class="l">${l}</span><span class="v">${v}</span><span class="d">${d}</span></div>`).join('')}</div>
  ${card('Appraisal steps', `<div class="toolbar">${['Self-appraisal by staff','Supervisor scores KPIs','HR moderates','MD signs off ratings that change pay'].map((s, i) => `<div class="stage-card"><b>${i + 1}</b> ${s}</div>${i < 3 ? '<span class="arrow">→</span>' : ''}`).join('')}</div>`)}
  ${table([{h:'Employee', f:a => person(emp(a.emp))}, {h:'Cycle', f:a => a.cycle}, {h:'Due', f:a => D(a.due)}, {h:'Status', f:a => pill(a.status, a.status === 'Completed' ? 'ok' : fromToday(a.due) <= 7 ? 'warn' : 'gold')}, {h:'Score', r:1, f:a => a.score ?? '-'},
    {h:'', f:a => S.role === 'hr' && a.status !== 'Completed' ? `<button class="btn sm" data-act="remind" data-id="${a.emp}">Send reminder</button>` : ''}], APPRAISALS)}`;
  return page({crumb:'Talent', title:'Performance management', sub:'Appraisal cycles, KPI scores and due-date alerts.', actions:S.role === 'hr' ? '<button class="btn primary" data-act="cycle">Start H2 2026 cycle</button>' : '', body});
};
A.remind = el => { notify(el.dataset.id, 'Reminder: your appraisal is due. Complete your self-appraisal.'); audit('Sent appraisal reminder', el.dataset.id, ''); toast('Reminder sent by email and SMS'); };
A.cycle = () => toast('H2 2026 cycle is already open for 6 staff. Self-appraisal forms sent.');

/* ================= PROBATION AND CONTRACTS ================= */
VIEWS.probation = () => {
  const m = metrics(), hr = S.role === 'hr';
  const pend = id => REQS.find(r => r.payload.emp === id && ['Contract renewal','Probation confirmation'].includes(r.type) && r.status === 'pending');
  const body = `<div class="note warn"><b>Alerts.</b> HR and the supervisor are alerted 90, 60 and 30 days before a contract ends and 30 days before probation ends. If pay continues past probation without a decision, the employee may be treated as confirmed, so act before the end date.</div>
  ${card('Probation', table([{h:'Employee', f:e => person(e)}, {h:'Started', f:e => D(e.pStart)}, {h:'Ends', f:e => D(e.pEnd)}, {h:'Days left', r:1, f:e => pill(fromToday(e.pEnd) + ' days', fromToday(e.pEnd) <= 30 ? 'crit' : 'warn')}, {h:'Action', f:e => pend(e.id) ? reqStatus(pend(e.id)) : hr ? `<button class="btn sm primary" data-act="confirmProb" data-id="${e.id}">Confirm or extend</button>` : '-'}], activeEmp().filter(e => e.type === 'Probationary'), {empty:'No one on probation.'}))}
  ${card('Contracts', table([{h:'Employee', f:e => person(e)}, {h:'Type', f:e => e.type}, {h:'Contract end', f:e => D(e.cEnd)}, {h:'Days left', r:1, f:e => pill(fromToday(e.cEnd) + ' days', fromToday(e.cEnd) <= 30 ? 'crit' : fromToday(e.cEnd) <= 90 ? 'warn' : 'plain')}, {h:'Action', f:e => pend(e.id) ? reqStatus(pend(e.id)) : hr ? `<button class="btn sm" data-act="renew" data-id="${e.id}">Renew</button> <button class="btn sm danger" data-act="startExit" data-id="${e.id}" data-t="End of contract">Do not renew</button>` : '-'}], activeEmp().filter(e => e.cEnd).sort((a, b) => a.cEnd.localeCompare(b.cEnd))))}`;
  return page({crumb:'Talent', title:'Probation and contract management', sub:'Confirmations, extensions and renewals go HR → MD.', body});
};
A.confirmProb = el => { const e = emp(el.dataset.id);
  formModal({title:'Probation decision', sub:`${nm(e)} · ends ${D(e.pEnd)}`, flow:['hr','md'], fields:[{id:'dec', label:'Decision', type:'select', opts:['Confirm in service','Extend probation by 3 months','Terminate at end of probation']}, {id:'eff', label:'Effective date', type:'date', val:e.pEnd}, {id:'score', label:'Probation review score (1 to 5)', type:'number', val:4}, {id:'reason', label:'Reason', type:'textarea', full:1, req:1, val:'Met targets for social media campaign and customer onboarding.'}],
    onSubmit:v => { if (!v.reason) return formError('Give a reason for the decision.'), false;
      if (v.dec === 'Confirm in service') newReq({type:'Probation confirmation', subject:`${nm(e)}: confirm in service`, maker:USERS.hr.emp, note:v.reason, fields:[['Employee', nm(e) + ', ' + e.title], ['Probation', `${D(e.pStart)} to ${D(e.pEnd)}`], ['Decision', v.dec], ['Effective', D(v.eff)], ['Review score', v.score + ' / 5']], payload:{emp:e.id, eff:v.eff, reason:v.reason}});
      else newReq({type:'Contract renewal', subject:`${nm(e)}: ${v.dec.toLowerCase()}`, maker:USERS.hr.emp, note:v.reason, fields:[['Employee', nm(e)], ['Decision', v.dec], ['Effective', D(v.eff)]], payload:{emp:e.id, oldEnd:e.pEnd, newEnd:ISO(new Date(new Date(e.pEnd).setMonth(new Date(e.pEnd).getMonth() + 3))), reason:v.dec}});
      toast('Sent to the MD'); }}); };
A.renew = el => { const e = emp(el.dataset.id);
  formModal({title:'Renew contract', sub:`${nm(e)} · ends ${D(e.cEnd)}`, flow:['hr','md'], fields:[{id:'end', label:'New end date', type:'date', req:1, val:ISO(new Date(new Date(e.cEnd).setFullYear(new Date(e.cEnd).getFullYear() + 1)))}, {id:'sal', label:'Salary on renewal (UGX)', type:'number', val:e.basic}, {id:'reason', label:'Reason', type:'textarea', full:1, req:1}],
    onSubmit:v => { if (!v.reason) return formError('Give a reason.'), false; newReq({type:'Contract renewal', subject:`${nm(e)}: renew to ${D(v.end)}`, maker:USERS.hr.emp, note:v.reason, fields:[['Employee', nm(e) + ', ' + e.title], ['Current end', D(e.cEnd)], ['New end', D(v.end)], ['Salary', UGX(v.sal)]], payload:{emp:e.id, oldEnd:e.cEnd, newEnd:v.end, reason:v.reason}}); toast('Sent to the MD'); }}); };

/* ================= TRAINING ================= */
VIEWS.training = () => {
  const body = table([{h:'Training', f:t => `<b>${esc(t.title)}</b><div class="muted" style="font-size:12px">${esc(t.provider)}</div>`}, {h:'Audience', f:t => esc(t.audience)}, {h:'Date', f:t => D(t.date)}, {h:'Mandatory', f:t => t.mandatory ? pill('Yes', 'warn') : 'No'}, {h:'Attended', r:1, f:t => t.done.length || '-'}, {h:'Status', f:t => pill(t.status, t.status === 'Completed' ? 'ok' : 'gold')}], TRAININGS)
    + card('Training history by employee', table([{h:'Employee', f:e => person(e)}, {h:'Trainings attended', r:1, f:e => TRAININGS.filter(t => t.done.includes(e.id)).length}, {h:'Latest', f:e => esc((TRAININGS.find(t => t.done.includes(e.id)) || {}).title || 'None yet')}, {h:'AML/CFT refresher', f:e => pill('Due 20 Nov', 'warn')}], activeEmp()));
  return page({crumb:'Talent', title:'Training and development', sub:'What has been conducted, who attended, and what is due.', actions:S.role === 'hr' ? '<button class="btn primary" data-act="recTrain">Record a training</button>' : '', body});
};
A.recTrain = () => formModal({title:'Record a training', submit:'Save training', fields:[{id:'title', label:'Title', req:1, full:1}, {id:'prov', label:'Provider'}, {id:'date', label:'Date', type:'date', val:ISO(TODAY)}, {id:'aud', label:'Audience', val:'All staff'}, {id:'mand', label:'Mandatory', type:'select', opts:['No','Yes']}, {id:'att', label:'Attendance register', type:'file', full:1}],
  onSubmit:v => { if (!v.title) return formError('Enter a title.'), false; TRAININGS.unshift({id:'TR-' + (30 + TRAININGS.length), title:v.title, provider:v.prov || 'Internal', date:v.date, mandatory:v.mand === 'Yes', status:v.date <= ISO(TODAY) ? 'Completed' : 'Scheduled', audience:v.aud, done:[]}); audit('Recorded training', 'TRAINING', v.title); toast('Training saved'); }});

/* ================= RECRUITMENT ================= */
EFFECTS['Recruitment requisition'] = r => VACANCIES.unshift(Object.assign({id:'VC-' + (10 + VACANCIES.length), opened:ISO(TODAY), applicants:0, stage:'Advertised'}, r.payload));
VIEWS.recruitment = () => {
  const stages = ['Requisition','Advertised','Shortlisting','Interviews','Offer','Onboarding'];
  const body = card('Hiring pipeline', `<div class="toolbar">${stages.map((s, i) => `<div class="stage-card"><b>${VACANCIES.filter(v => v.stage === s).length}</b> ${s}</div>${i < stages.length - 1 ? '<span class="arrow">→</span>' : ''}`).join('')}</div><p class="muted" style="margin:10px 0 0">A requisition needs MD approval before a post is advertised. On offer acceptance HR uses Add employee, which carries the candidate's details into the new file.</p>`)
    + table([{h:'Vacancy', f:v => `<b>${esc(v.title)}</b><div class="muted" style="font-size:12px">${esc(v.reason)}</div>`}, {h:'Department', f:v => esc(v.dept)}, {h:'Branch', f:v => esc(v.branch)}, {h:'Opened', f:v => D(v.opened)}, {h:'Closes', f:v => D(v.closes)}, {h:'Applicants', r:1, f:v => v.applicants}, {h:'Stage', f:v => pill(v.stage, 'info')}], VACANCIES);
  return page({crumb:'Talent', title:'Recruitment', sub:'Vacant positions and where each one is in hiring.', actions:S.role === 'hr' ? '<button class="btn primary" data-act="newReq">New requisition</button>' : '', body});
};
A.newReq = () => formModal({title:'New staff requisition', flow:['hr','md'], fields:[{id:'title', label:'Position', req:1}, {id:'dept', label:'Department', type:'select', opts:DEPTS}, {id:'branch', label:'Branch', type:'select', opts:BRANCHES}, {id:'closes', label:'Advert closes', type:'date', val:'2026-11-30'}, {id:'reason', label:'Reason (new role or replacement)', full:1, req:1}, {id:'budget', label:'Budgeted salary (UGX)', type:'number'}],
  onSubmit:v => { if (!v.title || !v.reason) return formError('Enter the position and reason.'), false; newReq({type:'Recruitment requisition', subject:`${v.title}, ${v.branch}`, maker:USERS.hr.emp, fields:[['Position', v.title], ['Department', v.dept], ['Branch', v.branch], ['Reason', v.reason], ['Budget', v.budget ? UGX(v.budget) : 'Not set']], payload:{title:v.title, dept:v.dept, branch:v.branch, closes:v.closes, reason:v.reason}}); toast('Requisition sent to the MD'); }});

/* ================= TRANSFERS, PROMOTIONS, SALARY ================= */
VIEWS.movements = () => {
  const all = EMP.flatMap(e => e.movements.map(m => Object.assign({e}, m))).sort((a, b) => b.date.localeCompare(a.date));
  const pend = REQS.filter(r => ['Transfer, promotion or salary change','Contract renewal','Probation confirmation'].includes(r.type) && r.status !== 'approved');
  const body = (pend.length ? card('In approval', table([{h:'Ref', f:r => r.id}, {h:'Request', f:r => esc(r.subject)}, {h:'Stage', f:r => reqStatus(r)}, {h:'SLA', f:r => sla(r)}], pend, {click:r => `data-act="open" data-id="${r.id}"`})) : '')
    + card('History', table([{h:'Employee', f:m => person(m.e)}, {h:'Change', f:m => esc(m.kind)}, {h:'Previous position', f:m => esc(m.prev)}, {h:'New position', f:m => esc(m.next)}, {h:'Effective date', f:m => D(m.date)}, {h:'Reason', f:m => esc(m.reason)}], all, {empty:'Approved changes will be listed here with previous and new position, salary and effective date.'}));
  return page({crumb:'Talent', title:'Transfers, promotions and salary adjustments', sub:'Every change records the previous position, new position, effective date, new salary and reason.', actions:S.role === 'hr' ? '<button class="btn primary" data-act="movement">New change</button>' : '', body});
};
A.movement = el => {
  const pre = el && el.dataset.id;
  const opts = activeEmp().map(e => [e.id, nm(e) + ' · ' + e.title]);
  const first = emp(pre || opts[0][0]);
  S._mvEmp = null;
  formModal({title:'Transfer, promotion or salary adjustment', sub:'Takes effect on the effective date after MD approval', flow:['hr','md'], fields:[
    {id:'emp', label:'Employee', type:'select', opts, val:first.id, full:1}, {id:'kind', label:'Change type', type:'select', opts:['Transfer','Promotion','Promotion and transfer','Salary adjustment','Acting appointment','Demotion']},
    {id:'eff', label:'Effective date', type:'date', req:1, val:'2026-11-01'},
    {sec:'Previous position'}, {id:'pt', label:'Position', ro:1, val:first.title}, {id:'pb', label:'Branch', ro:1, val:first.branch}, {id:'pg', label:'Grade', ro:1, val:first.grade}, {id:'ps', label:'Salary (UGX)', ro:1, val:N(first.basic)},
    {sec:'New position'}, {id:'title', label:'New position', req:1, val:first.title}, {id:'branch', label:'New branch', type:'select', opts:BRANCHES, val:first.branch}, {id:'dept', label:'Department', type:'select', opts:DEPTS, val:first.dept},
    {id:'grade', label:'New grade', type:'select', opts:Object.keys(GRADES), val:first.grade}, {id:'salary', label:'New salary (UGX)', type:'number', req:1, val:first.basic}, {type:'calc'},
    {id:'reason', label:'Reason', type:'textarea', full:1, req:1}],
    onInput:v => { const e = emp(v.emp); if (S._mvEmp !== v.emp) { S._mvEmp = v.emp; ['pt','pb','pg'].forEach((k, i) => document.getElementById('f_' + k).value = [e.title, e.branch, e.grade][i]); document.getElementById('f_ps').value = N(e.basic);
        if (!pre || v.emp !== pre) { document.getElementById('f_title').value = e.title; document.getElementById('f_salary').value = e.basic; document.getElementById('f_branch').value = e.branch; document.getElementById('f_dept').value = e.dept; document.getElementById('f_grade').value = e.grade; } }
      const s = +document.getElementById('f_salary').value || 0, ch = (s - e.basic) / e.basic * 100;
      $('#calc').innerHTML = `Salary change: <b>${ch >= 0 ? '+' : ''}${ch.toFixed(1)}%</b> (${UGX(s - e.basic)} a month). Monthly cost incl. 10% employer NSSF: <b>${UGX((s - e.basic) * 1.1)}</b>`; },
    onSubmit:v => { const e = emp(v.emp); if (!v.reason || !v.title || !v.salary) return formError('Fill the new position, salary and reason.'), false;
      newReq({type:'Transfer, promotion or salary change', subject:`${nm(e)}: ${v.kind.toLowerCase()}`, maker:USERS.hr.emp, note:v.reason,
        fields:[['Change type', v.kind], ['Previous position', `${e.title}, ${e.branch}, Grade ${e.grade}`], ['New position', `${v.title}, ${v.branch}, Grade ${v.grade}`], ['Previous salary', UGX(e.basic)], ['New salary', UGX(v.salary)], ['Effective date', D(v.eff)], ['Reason', v.reason]],
        payload:{emp:e.id, kind:v.kind, title:v.title, branch:v.branch, dept:v.dept, salary:+v.salary, grade:v.grade, eff:v.eff, reason:v.reason}});
      S._mvEmp = null; toast('Sent to the MD'); }});
};

/* ================= PAYROLL ================= */
function payStepper() {
  const st = ['Draft','Submitted','Approved','Posted'], i = st.indexOf(PAY.status);
  return `<div class="flow" style="--n:4">${[['HR payroll input','Draft'], ['Submitted to MD','Submitted'], ['MD approved','Approved'], ['Posted to core banking','Posted']].map(([l], k) => `<div class="step ${k < i || PAY.status === 'Posted' ? 'done' : k === i ? 'cur' : ''}"><div class="dotc">${k < i || PAY.status === 'Posted' ? '✓' : k + 1}</div><div class="sl">${l}</div></div>`).join('')}</div>`;
}
VIEWS.payroll = () => {
  const reg = payRegister(), t = reg.tot, tab = curTab('pay', 'input'), hr = S.role === 'hr';
  const r = PAY.reqId && REQS.find(x => x.id === PAY.reqId);
  if (r && r.status === 'approved' && PAY.status === 'Submitted') PAY.status = 'Approved';
  let retNote = '';
  if (r && ['returned','rejected'].includes(r.status) && PAY.status !== 'Posted') { PAY.status = 'Draft'; const h = r.hist[r.hist.length - 1]; retNote = `<div class="note warn"><b>MD ${r.status} ${r.id}:</b> ${esc(h.c)} Fix the inputs and submit again.</div>`; }
  let content;
  if (tab === 'input') content = `<div class="note"><b>Payroll input for October 2026.</b> Salary and grade allowances load from each employee file. Staff loan repayments come from the core banking loan schedule. Add one-off items below: overtime, commission, bonus, arrears, leave pay or deductions.</div>`
    + table([{h:'Employee', f:i => person(emp(i.emp))}, {h:'Item', f:i => pill(i.kind, i.kind === 'Deduction' ? 'crit' : 'brand')}, {h:'Note', f:i => esc(i.note)}, {h:'Amount (UGX)', r:1, f:i => (i.kind === 'Deduction' ? '-' : '') + N(i.amt)}, {h:'', f:(i) => hr && PAY.status === 'Draft' ? `<button class="btn sm" data-act="delInput" data-i="${PAY.inputs.indexOf(i)}">Remove</button>` : ''}], PAY.inputs);
  if (tab === 'reg') content = table([{h:'Employee', f:l => person(l.e, l.final ? 'Final dues (exit)' : l.e.grade)}, {h:'Basic', r:1, f:l => N(l.basic)}, {h:'Allowances', r:1, f:l => N(l.allowances)}, {h:'Other earnings', r:1, f:l => N(l.extra)}, {h:'Gross', r:1, f:l => `<b>${N(l.gross)}</b>`}, {h:'NSSF 5%', r:1, f:l => N(l.nssfEe)}, {h:'PAYE', r:1, f:l => N(l.paye)}, {h:'LST', r:1, f:l => N(l.lst)}, {h:'Staff loan', r:1, f:l => N(l.loan)}, {h:'Savings', r:1, f:l => N(l.sav)}, {h:'Other ded.', r:1, f:l => N(l.otherDed)}, {h:'Net pay', r:1, f:l => `<b>${N(l.net)}</b>`}, {h:'Employer NSSF 10%', r:1, f:l => N(l.nssfEr)}],
    reg.lines, {foot:`<td>Total · ${reg.lines.length} staff</td><td class="r num">${N(reg.lines.reduce((s, l) => s + l.basic, 0))}</td><td class="r num">${N(reg.lines.reduce((s, l) => s + l.allowances, 0))}</td><td class="r num">${N(t.extra)}</td><td class="r num">${N(t.gross)}</td><td class="r num">${N(t.nssfEe)}</td><td class="r num">${N(t.paye)}</td><td class="r num">${N(t.lst)}</td><td class="r num">${N(t.loan)}</td><td class="r num">${N(t.sav)}</td><td class="r num">${N(t.otherDed)}</td><td class="r num">${N(t.net)}</td><td class="r num">${N(t.nssfEr)}</td>`});
  if (tab === 'report') { const by = k => [...new Set(reg.lines.map(l => l.e[k]))].map(v => { const ls = reg.lines.filter(l => l.e[k] === v); return {v, n:ls.length, g:ls.reduce((s, l) => s + l.gross, 0), net:ls.reduce((s, l) => s + l.net, 0)}; });
    content = `<div class="grid g2">${card('Statutory remittances', table([{h:'Item', f:x => x[0]}, {h:'Pay to', f:x => x[1]}, {h:'Due', f:x => x[2]}, {h:'Amount (UGX)', r:1, f:x => N(x[3])}], [['PAYE','URA','15 Nov 2026',t.paye], ['NSSF (5% staff + 10% employer)','NSSF','15 Nov 2026',t.nssfEe + t.nssfEr], ['Local Service Tax (instalment 4 of 4)','KCCA / local government','As set by council',t.lst]]))}
      ${card('Payroll cost', `<dl class="kv"><dt>Gross pay</dt><dd>${UGX(t.gross)}</dd><dt>Employer NSSF 10%</dt><dd>${UGX(t.nssfEr)}</dd><dt>Total employment cost</dt><dd><b>${UGX(t.gross + t.nssfEr)}</b></dd><dt>Net pay to staff accounts</dt><dd>${UGX(t.net)}</dd><dt>Staff loans to core banking</dt><dd>${UGX(t.loan)}</dd><dt>Savings to core banking</dt><dd>${UGX(t.sav)}</dd></dl>`)}</div>
      <div class="grid g2">${card('By branch', table([{h:'Branch', f:x => esc(x.v)}, {h:'Staff', r:1, f:x => x.n}, {h:'Gross', r:1, f:x => N(x.g)}, {h:'Net', r:1, f:x => N(x.net)}], by('branch')))}${card('By department', table([{h:'Department', f:x => esc(x.v)}, {h:'Staff', r:1, f:x => x.n}, {h:'Gross', r:1, f:x => N(x.g)}, {h:'Net', r:1, f:x => N(x.net)}], by('dept')))}</div>`; }
  if (tab === 'hist') content = table([{h:'Period', f:p => p.period}, {h:'Staff', r:1, f:p => p.staff}, {h:'Gross', r:1, f:p => N(p.gross)}, {h:'Net', r:1, f:p => N(p.net)}, {h:'Status', f:p => pill(p.status, 'ok')}], [...PAYROLL_HIST].reverse());
  let acts = '';
  if (hr && PAY.status === 'Draft') acts = `<button class="btn" data-act="addInput">Add payroll input</button><button class="btn primary" data-act="submitPay">Submit to MD for approval</button>`;
  if (PAY.status === 'Submitted') acts = S.role === 'md' && r ? `<button class="btn primary" data-act="open" data-id="${r.id}">Review and approve</button>` : `<span class="pill gold">Waiting for MD approval</span>`;
  if (PAY.status === 'Approved' && (hr || S.role === 'md')) acts = `<button class="btn primary" data-act="postPay">Post to core banking</button>`;
  if (PAY.status === 'Posted') acts = `<span class="pill ok">Posted · payslips sent</span>`;
  const body = `<div class="kpis">${[['Gross pay', UGX(t.gross)], ['PAYE', UGX(t.paye)], ['NSSF total (15%)', UGX(t.nssfEe + t.nssfEr)], ['Net pay', UGX(t.net)]].map(([l, v]) => `<div class="kpi" style="cursor:default"><span class="l">${l}</span><span class="v" style="font-size:20px">${v}</span><span class="d">October 2026</span></div>`).join('')}</div>
    ${retNote}${card('October 2026 run', payStepper())}${tabs('pay', [['input','Payroll input'], ['reg','Payroll register'], ['report','Payroll report'], ['hist','Past runs']])}${content}
    <div class="note"><b>Rules applied.</b> PAYE bands from 1 July 2026: nil to 335,000; 20% to 410,000; 25% to 485,000; 30% above, plus 10% on income above 10,000,000. NSSF 5% staff and 10% employer on gross. LST is deducted July to October and reduces chargeable income. All rates live in Payroll setup so HR can update them when the law changes.</div>`;
  return page({crumb:'Pay', title:'Payroll', sub:'Salary, deductions, leave pay and the payroll report. Maker: HR. Checker: MD.', actions:acts, body});
};
A.delInput = el => { PAY.inputs.splice(+el.dataset.i, 1); render(); toast('Input removed'); };
A.addInput = () => formModal({title:'Add payroll input', submit:'Add to October payroll', fields:[{id:'emp', label:'Employee', type:'select', opts:activeEmp().map(e => [e.id, nm(e)]), full:1}, {id:'kind', label:'Item', type:'select', opts:['Overtime','Commission','Bonus','Arrears','Leave pay','Deduction']}, {id:'amt', label:'Amount (UGX)', type:'number', req:1}, {id:'note', label:'Note', full:1, req:1}],
  onSubmit:v => { if (!v.amt || !v.note) return formError('Enter an amount and a note.'), false; PAY.inputs.push({emp:v.emp, kind:v.kind, amt:+v.amt, note:v.note}); audit('Added payroll input', 'PAY-2026-10', `${v.kind} ${N(v.amt)} for ${nm(emp(v.emp))}`); toast('Added'); }});
A.submitPay = () => { const reg = payRegister(), t = reg.tot;
  const r = newReq({type:'Monthly payroll', subject:'October 2026 payroll', maker:USERS.hr.emp, note:'Inputs checked against attendance and approved leave.', fields:[['Period', 'October 2026'], ['Staff paid', String(reg.lines.length)], ['Gross pay', UGX(t.gross)], ['PAYE', UGX(t.paye)], ['NSSF 5% + 10%', UGX(t.nssfEe + t.nssfEr)], ['LST', UGX(t.lst)], ['Staff loans and savings', UGX(t.loan + t.sav)], ['Net pay', UGX(t.net)], ['One-off inputs', PAY.inputs.length + ' items']], payload:{}});
  PAY.status = 'Submitted'; PAY.reqId = r.id; render(); toast('October payroll sent to the MD'); };
A.postPay = () => { const reg = payRegister(); PAY.status = 'Posted'; PAYROLL_HIST.push({period:'2026-10', staff:reg.lines.length, gross:reg.tot.gross, net:reg.tot.net, status:'Posted'}); audit('Posted payroll to core banking', 'PAY-2026-10', `${reg.lines.length} salary credits, ${UGX(reg.tot.net)}`); NOTES.unshift({to:'PSF-0142', text:'Your October 2026 payslip is ready.', at:now(), read:false}); render(); toast(`${reg.lines.length} salary credits posted. Payslips emailed.`); };
function payslipHtml(e) {
  const l = payLine(e, PAY.inputs, PAY.period);
  const row = (k, v, neg) => `<tr><td>${k}</td><td class="r num">${neg ? '-' : ''}${N(v)}</td></tr>`;
  return `<section class="card" style="max-width:640px">${logos()}<div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;margin:12px 0"><div><b>Payslip · October 2026</b><div class="muted">${esc(nm(e))} · ${e.id} · ${esc(e.title)}</div></div><div class="muted" style="text-align:right">${esc(e.branch)}<br>Account ${e.acct}</div></div>
    <div class="tw"><table><thead><tr><th>Earnings</th><th class="r">UGX</th></tr></thead><tbody>${row('Basic salary', l.basic)}${row('Allowances (transport, housing, airtime)', l.allowances)}${l.extra ? row('Other earnings', l.extra) : ''}</tbody><tfoot><tr><td>Gross pay</td><td class="r num">${N(l.gross)}</td></tr></tfoot></table></div>
    <div class="tw" style="margin-top:10px"><table><thead><tr><th>Deductions</th><th class="r">UGX</th></tr></thead><tbody>${row('PAYE', l.paye, 1)}${row('NSSF 5%', l.nssfEe, 1)}${l.lst ? row('Local Service Tax', l.lst, 1) : ''}${l.loan ? row('Staff loan repayment', l.loan, 1) : ''}${l.sav ? row('Savings', l.sav, 1) : ''}${l.otherDed ? row('Other deductions', l.otherDed, 1) : ''}</tbody><tfoot><tr><td>Net pay</td><td class="r num">${N(l.net)}</td></tr></tfoot></table></div>
    <p class="faint" style="font-size:12px;margin:10px 0 0">Employer NSSF contribution: ${UGX(l.nssfEr)}. ${PAY.status === 'Posted' ? 'Credited to your account.' : 'Draft until the MD approves and payroll is posted.'}</p></section>`;
}
