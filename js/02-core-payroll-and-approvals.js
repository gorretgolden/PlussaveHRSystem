/* ================= CORE HELPERS ================= */
const $ = s => document.querySelector(s);
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const N = n => Math.round(n || 0).toLocaleString('en-US');
const UGX = n => 'UGX ' + N(n);
const D = s => s ? new Date(s).toLocaleDateString('en-GB', {day:'2-digit', month:'short', year:'numeric'}) : '-';
const DT = s => s ? new Date(s).toLocaleString('en-GB', {day:'2-digit', month:'short', hour:'2-digit', minute:'2-digit'}) : '-';
const days = (a, b) => Math.round((new Date(b) - new Date(a)) / 864e5);
const fromToday = s => days(ISO(TODAY), s);
const now = () => { const t = new Date(); return new Date(2026, 9, 29, t.getHours(), t.getMinutes(), t.getSeconds()).toISOString(); };
function workingDays(a, b) {
  if (!a || !b) return 0; let n = 0; const d = new Date(a), end = new Date(b);
  if (end < d) return 0;
  while (d <= end) { if (!CONFIG.weekend.includes(d.getDay()) && !HOLIDAYS.includes(ISO(d))) n++; d.setDate(d.getDate() + 1); }
  return n;
}
const emp = id => EMP.find(e => e.id === id);
const nm = e => e ? `${e.fn} ${e.ln}` : '-';
const ini = e => e ? (e.fn[0] + e.ln[0]).toUpperCase() : '?';
const age = e => Math.floor(days(e.dob, ISO(TODAY)) / 365.25);
const tenure = e => days(e.joined, ISO(TODAY)) / 365.25;
const activeEmp = () => EMP.filter(e => !['Resigned','Terminated'].includes(e.status));
const av = (e, cls = '') => `<span class="av ${cls}">${e && e.photo ? `<img src="${e.photo}" alt="">` : ini(e)}</span>`;
const person = (e, sub) => `<div class="person">${av(e)}<div style="min-width:0"><div class="pn">${esc(nm(e))}</div><div class="ps">${esc(sub ?? (e ? e.title : ''))}</div></div></div>`;
const me = () => emp(USERS[S.role].emp);
const roleOfEmp = id => id === USERS.hr.emp ? 'hr' : id === USERS.md.emp ? 'md' : 'staff';
const ROLE_NAME = {staff:'Staff', hr:'HR', md:'MD'};
const pill = (t, k = '') => `<span class="pill ${k}">${esc(t)}</span>`;
const empStatusPill = s => pill(s, {Active:'ok','On Leave':'info',Suspended:'warn',Resigned:'',Terminated:'crit'}[s]);
function reqStatus(r) {
  if (r.status === 'pending') return r.chain[r.stage] === 'hr' ? pill('Pending HR review','gold') : pill('Pending MD approval','gold');
  return {approved:pill('Approved','ok'), rejected:pill('Rejected','crit'), returned:pill('Returned for correction','warn'), draft:pill('Draft')}[r.status];
}
function sla(r) {
  if (r.status !== 'pending') return '';
  const last = r.hist[r.hist.length - 1].at; const role = r.chain[r.stage];
  const left = CONFIG.sla[role] - (new Date(now()) - new Date(last)) / 36e5;
  return left < 0 ? pill(`Overdue ${Math.round(-left)}h`, 'crit') : pill(`${Math.round(left)}h left`, left < 12 ? 'warn' : 'plain');
}
function toast(m) { const t = $('#toast'); t.textContent = m; t.hidden = false; clearTimeout(toast._t); toast._t = setTimeout(() => t.hidden = true, 3200); }
function audit(action, ref, detail) { AUDIT.unshift({at:now(), by:USERS[S.role].emp, role:S.role, action, ref, detail}); }
function notify(to, text) { NOTES.unshift({to, text, at:now(), read:false}); }

/* ================= PAYROLL ENGINE ================= */
function payeOf(c) {
  let t = 0, prev = 0;
  for (const [cap, rate] of CONFIG.paye) { if (c > prev) t += (Math.min(c, cap) - prev) * rate; prev = cap; }
  if (c > CONFIG.surtaxOver) t += (c - CONFIG.surtaxOver) * CONFIG.surtax;
  return Math.round(t);
}
function lstAnnual(m) { for (const [cap, amt] of CONFIG.lst) if (m <= cap) return amt; return 0; }
const allw = e => { const g = GRADES[e.grade]; return e.type === 'Part-time' ? {tr:g.tr, hs:0, at:g.at} : {tr:g.tr, hs:g.hs, at:g.at}; };
function payLine(e, inputs, period) {
  const month = +period.slice(5, 7); const a = allw(e);
  let f = 1, final = false;
  const ex = EXITS.find(x => x.emp === e.id);
  if (ex && ex.lwd && ex.lwd.slice(0, 7) === period) { const dim = new Date(+period.slice(0,4), month, 0).getDate(); f = +ex.lwd.slice(8, 10) / dim; final = true; }
  const basic = Math.round(e.basic * f), allowances = Math.round((a.tr + a.hs + a.at) * f);
  const mine = inputs.filter(i => i.emp === e.id);
  const extra = mine.filter(i => i.kind !== 'Deduction').reduce((s, i) => s + i.amt, 0);
  const otherDed = mine.filter(i => i.kind === 'Deduction').reduce((s, i) => s + i.amt, 0);
  const gross = basic + allowances + extra;
  const lst = CONFIG.lstMonths.includes(month) && !final ? Math.round(lstAnnual(e.basic + a.tr + a.hs + a.at) / CONFIG.lstMonths.length) : 0;
  const chargeable = gross - (CONFIG.lstDeductible ? lst : 0);
  const paye = payeOf(chargeable);
  const nssfEe = Math.round(gross * CONFIG.nssfEe), nssfEr = Math.round(gross * CONFIG.nssfEr);
  const loan = final ? 0 : e.loan, sav = final ? 0 : e.sav;
  const ded = paye + nssfEe + lst + loan + sav + otherDed;
  return {e, basic, allowances, extra, gross, chargeable, paye, nssfEe, nssfEr, lst, loan, sav, otherDed, ded, net:gross - ded, final};
}
function payRegister(period = PAY.period) {
  const people = EMP.filter(e => !['Resigned','Terminated'].includes(e.status) || EXITS.some(x => x.emp === e.id && x.lwd && x.lwd.slice(0,7) === period));
  const lines = people.map(e => payLine(e, PAY.inputs, period));
  const tot = k => lines.reduce((s, l) => s + l[k], 0);
  return {lines, tot:{gross:tot('gross'), paye:tot('paye'), nssfEe:tot('nssfEe'), nssfEr:tot('nssfEr'), lst:tot('lst'), loan:tot('loan'), sav:tot('sav'), otherDed:tot('otherDed'), net:tot('net'), extra:tot('extra')}};
}
function gratuityFor(e, basis = CONFIG.gratuity.basis, rate) {
  const yrs = Math.floor(tenure(e) * 100) / 100;
  const amt = basis === 'pct' ? e.basic * 12 * ((rate ?? CONFIG.gratuity.pct) / 100) * yrs : e.basic * (rate ?? CONFIG.gratuity.months) * yrs;
  return {yrs, amt:Math.round(amt)};
}
const leavePay = (e, d) => Math.round(e.basic / CONFIG.leaveDivisor * d);

/* ================= MAKER-CHECKER ENGINE ================= */
let RID = 1031;
function newReq({type, subject, maker, fields, payload = {}, note = '', at}) {
  const makerRole = roleOfEmp(maker);
  const chain = makerRole === 'staff' ? ['hr','md'] : makerRole === 'hr' ? ['md'] : ['hr'];
  const r = {id:'R-' + (RID++), type, subject, maker, makerRole, created:at || now(), fields, payload, chain, stage:0, status:'pending',
             hist:[{by:maker, act:'Submitted', at:at || now(), c:note}]};
  REQS.unshift(r);
  if (!at) { notify(chain[0], `${r.id} ${type}: ${subject} is waiting for your ${chain[0]==='hr'?'review':'approval'}`); audit('Submitted', r.id, `${type}: ${subject}`); }
  return r;
}
const stageRole = r => r.status === 'pending' ? r.chain[r.stage] : null;
const isMine = (r, role = S.role) => r.maker === USERS[role].emp;
const canAct = (r, role = S.role) => stageRole(r) === role && !isMine(r, role);
const awaiting = (role = S.role) => REQS.filter(r => canAct(r, role));
function act(r, action, c) {
  const by = USERS[S.role].emp;
  if (!canAct(r)) { toast('You cannot act on this request at its current stage.'); return; }
  if ((action === 'reject' || action === 'return') && !c.trim()) { toast('Add a comment so the maker knows what to fix.'); return false; }
  if (action === 'forward') {
    r.hist.push({by, act:`Verified by ${ROLE_NAME[S.role]}, forwarded to MD`, at:now(), c}); r.stage++;
    notify('md', `${r.id} ${r.type}: ${r.subject} verified by HR, waiting for your approval`);
  } else if (action === 'approve') {
    r.hist.push({by, act:`Approved by ${ROLE_NAME[S.role]}`, at:now(), c}); r.status = 'approved';
    (EFFECTS[r.type] || (() => {}))(r);
    notify(r.maker, `${r.id} ${r.type} was approved`); if (r.makerRole === 'staff') notify('hr', `${r.id} ${r.type} for ${nm(emp(r.maker))} approved by MD`);
  } else if (action === 'return') {
    if (S.role === 'md' && r.chain[0] === 'hr' && r.stage > 0) { r.stage = 0; r.hist.push({by, act:'Returned to HR by MD', at:now(), c, k:'w'}); notify('hr', `${r.id} returned to you by MD`); }
    else { r.status = 'returned'; r.hist.push({by, act:`Returned to maker by ${ROLE_NAME[S.role]}`, at:now(), c, k:'w'}); notify(r.maker, `${r.id} ${r.type} was returned for correction`); }
  } else if (action === 'reject') {
    r.status = 'rejected'; r.hist.push({by, act:`Rejected by ${ROLE_NAME[S.role]}`, at:now(), c, k:'r'}); notify(r.maker, `${r.id} ${r.type} was rejected`);
    if (r.type === 'Monthly payroll') PAY.status = 'Draft';
  }
  audit({forward:'Verified and forwarded', approve:'Approved', return:'Returned', reject:'Rejected'}[action], r.id, `${r.type}: ${r.subject}${c ? ' | ' + c : ''}`);
  return true;
}
function resubmit(r, c) { r.status = 'pending'; r.stage = 0; r.hist.push({by:r.maker, act:'Corrected and resubmitted', at:now(), c}); notify(r.chain[0], `${r.id} resubmitted after correction`); audit('Resubmitted', r.id, r.subject); }

const EFFECTS = {
  'Leave application': r => { const e = emp(r.payload.emp), p = r.payload;
    if (['Annual','Sick','Other'].includes(p.type)) e.bal[p.type] = Math.max(0, (e.bal[p.type] || 0) - p.days);
    LEAVES.unshift({id:'LV-' + (320 + LEAVES.length), emp:e.id, type:p.type, from:p.from, to:p.to, days:p.days, status:'Approved'});
    if (p.from <= ISO(TODAY) && p.to >= ISO(TODAY)) e.status = 'On Leave'; },
  'Transfer, promotion or salary change': r => { const e = emp(r.payload.emp), p = r.payload;
    e.movements.unshift({date:p.eff, kind:p.kind, prev:`${e.title}, ${e.branch}, ${UGX(e.basic)}`, next:`${p.title}, ${p.branch}, ${UGX(p.salary)}`, reason:p.reason});
    Object.assign(e, {title:p.title, branch:p.branch, dept:p.dept, basic:+p.salary, grade:p.grade}); },
  'Contract renewal': r => { const e = emp(r.payload.emp); e.cEnd = r.payload.newEnd; e.movements.unshift({date:ISO(TODAY), kind:'Contract renewal', prev:'Ends ' + D(r.payload.oldEnd), next:'Ends ' + D(r.payload.newEnd), reason:r.payload.reason}); },
  'Probation confirmation': r => { const e = emp(r.payload.emp); e.type = 'Permanent'; e.movements.unshift({date:r.payload.eff, kind:'Confirmed in service', prev:'Probationary', next:'Permanent', reason:r.payload.reason}); e.pEnd = null; },
  'New employee': r => { EMP.push(r.payload.e); },
  'Personal details change': r => { const e = emp(r.payload.emp); e[r.payload.field] = r.payload.value; },
  'Monthly payroll': r => { PAY.status = 'Approved'; },
  'Disciplinary decision': r => { const k = CASES.find(c => c.id === r.payload.case); k.decision = r.payload.decision; k.stage = 'Closed'; k.due = null;
    const e = emp(k.emp); e.docs['Disciplinary letters'] = 1;
    if (/Suspension/.test(k.decision)) e.status = 'Suspended';
    if (/Dismissal/.test(k.decision)) { e.status = 'Terminated'; EXITS.unshift({id:'EX-' + (8 + EXITS.length), emp:e.id, type:'Dismissal', notice:ISO(TODAY), lwd:ISO(TODAY), reason:k.category, interview:'', status:'Clearance in progress', clear:clearList()}); } },
  'Grievance resolution': r => { const k = CASES.find(c => c.id === r.payload.case); k.decision = r.payload.decision; k.stage = 'Closed'; k.due = null; },
  'Resignation': r => { const p = r.payload; EXITS.unshift({id:'EX-' + (8 + EXITS.length), emp:p.emp, type:'Resignation', notice:ISO(TODAY), lwd:p.lwd, reason:p.reason, interview:'', status:'Clearance in progress', clear:clearList()}); },
  'Gratuity or leave pay': r => { PAY.inputs.push({emp:r.payload.emp, kind:r.payload.kind, amt:r.payload.amt, note:r.payload.note}); },
  'Training request': r => { const p = r.payload; TRAININGS.unshift({id:'TR-' + (22 + TRAININGS.length), title:p.title, provider:p.provider, date:p.date, mandatory:false, status:'Approved', audience:nm(emp(p.emp)), done:[]}); },
  'Attendance correction': r => { ATT[r.payload.emp] = [r.payload.time, null, 'Manual (approved correction)']; },
  'HR Library document': r => { LIBRARY.unshift({id:'DOC-' + (11 + LIBRARY.length), title:r.payload.title, ver:r.payload.ver, cat:r.payload.cat, owner:'HR & Admin', approved:ISO(TODAY), ack:0}); }
};
const clearList = () => ({'Handover notes submitted':0,'Laptop, keys and ID card returned':0,'Core banking user disabled':0,'PlusPay user disabled':0,'Email account closed':0,'Staff loan balance settled':0,'Final dues computed':0,'Exit interview done':0,'Certificate of service issued':0});

/* seeds */
(function seed() {
  const s = (o, hist) => { const r = newReq(o); if (hist) hist(r); return r; };
  s({type:'Leave application', subject:'Annual leave, 3 to 7 Nov (5 days)', maker:'PSF-0056', at:'2026-10-28T06:14:00.000Z', note:'Family function in Lira.',
     fields:[['Leave type','Annual'],['Dates','03 Nov 2026 to 07 Nov 2026'],['Working days','5'],['Balance before',`${emp('PSF-0056').bal.Annual} days`],['Reliever','Patience Akello'],['Attachment','None needed']],
     payload:{emp:'PSF-0056', type:'Annual', from:'2026-11-03', to:'2026-11-07', days:5}});
  s({type:'Personal details change', subject:'New phone number', maker:'PSF-0117', at:'2026-10-27T11:40:00.000Z', note:'Lost my old line.',
     fields:[['Field','Phone'],['Current value',emp('PSF-0117').phone],['New value','0759 214 880'],['Supporting document','Telecom SIM replacement form.pdf']],
     payload:{emp:'PSF-0117', field:'phone', value:'0759 214 880'}});
  s({type:'Leave application', subject:'Compassionate leave, 30 to 31 Oct (2 days)', maker:'PSF-0142', at:'2026-10-26T07:05:00.000Z', note:'Burial of my uncle in Masaka.',
     fields:[['Leave type','Other (compassionate)'],['Dates','30 Oct 2026 to 31 Oct 2026'],['Working days','2'],['Reliever','Juliet Nakimuli'],['Attachment','None']],
     payload:{emp:'PSF-0142', type:'Other', from:'2026-10-30', to:'2026-10-31', days:2}},
     r => { r.status = 'returned'; r.hist.push({by:'PSF-0007', act:'Returned to maker by HR', at:'2026-10-26T09:30:00.000Z', c:'Please attach the burial notice or a letter from the LC1 chairperson, then resubmit.', k:'w'}); });
  s({type:'Transfer, promotion or salary change', subject:'Brian Tumusiime: Teller to Senior Teller, Old Taxi Park', maker:'PSF-0007', at:'2026-10-27T08:00:00.000Z', note:'Fills the Senior Teller gap at Old Taxi Park. Note: the August written warning is valid until Feb 2027.',
     fields:[['Change type','Promotion and transfer'],['Previous position','Teller, Kikuubo, Grade G5'],['New position','Senior Teller, Old Taxi Park, Grade G4'],['Previous salary',UGX(1100000)],['New salary',UGX(1450000)],['Effective date','01 Nov 2026'],['Reason','Replacement for resigned teller; strong H1 targets']],
     payload:{emp:'PSF-0102', kind:'Promotion and transfer', title:'Senior Teller', branch:BRANCHES[1], dept:'Operations', salary:1450000, grade:'G4', eff:'2026-11-01', reason:'Replacement for resigned teller'}});
  s({type:'Contract renewal', subject:'Ivan Ssekandi: renew 2 years', maker:'PSF-0007', at:'2026-10-28T13:20:00.000Z', note:'Core banking integration work continues into 2027.',
     fields:[['Employee','Ivan Ssekandi, ICT Officer'],['Current contract ends','15 Dec 2026'],['Proposed new end date','15 Dec 2028'],['Salary','No change'],['Last appraisal','4.1 / 5']],
     payload:{emp:'PSF-0034', oldEnd:'2026-12-15', newEnd:'2028-12-15', reason:'Renewed for core banking integration'}});
  s({type:'Leave application', subject:'Annual leave, 23 to 27 Nov (5 days)', maker:'PSF-0007', at:'2026-10-28T15:02:00.000Z', note:'Staff requests that arrive while I am away will be routed to the MD as acting HR checker.',
     fields:[['Leave type','Annual'],['Dates','23 Nov 2026 to 27 Nov 2026'],['Working days','5'],['Routing','HR is the maker, so it goes straight to the MD']],
     payload:{emp:'PSF-0007', type:'Annual', from:'2026-11-23', to:'2026-11-27', days:5}});
  s({type:'Leave application', subject:'Sick leave, 12 to 13 Oct (2 days)', maker:'PSF-0048', at:'2026-10-12T05:50:00.000Z', note:'Malaria. Medical form attached.',
     fields:[['Leave type','Sick'],['Dates','12 Oct 2026 to 13 Oct 2026'],['Working days','2'],['Attachment','Medical form.pdf']], payload:{emp:'PSF-0048', type:'Sick', from:'2026-10-12', to:'2026-10-13', days:2}},
     r => { r.hist.push({by:'PSF-0007', act:'Verified by HR, forwarded to MD', at:'2026-10-12T07:10:00.000Z', c:'Form checked.'}); r.hist.push({by:'PSF-0001', act:'Approved by MD', at:'2026-10-12T09:45:00.000Z', c:'Get well soon.'}); r.stage = 1; r.status = 'approved'; });
  s({type:'Training request', subject:'Defensive driving course', maker:'PSF-0153', at:'2026-10-05T08:00:00.000Z', note:'To support deliveries to branches.',
     fields:[['Course','Defensive driving'],['Provider','External driving school'],['Cost',UGX(650000)]], payload:{emp:'PSF-0153', title:'Defensive driving', provider:'Driving school', date:'2026-11-15'}},
     r => { r.hist.push({by:'PSF-0007', act:'Verified by HR, forwarded to MD', at:'2026-10-06T07:00:00.000Z', c:'Useful, but not budgeted.'}); r.hist.push({by:'PSF-0001', act:'Rejected by MD', at:'2026-10-07T12:00:00.000Z', c:'Not in the 2026 budget. Re-apply in Q1 2027.', k:'r'}); r.stage = 1; r.status = 'rejected'; });
  AUDIT.push(
    {at:'2026-10-28T15:02:00.000Z', by:'PSF-0007', role:'hr', action:'Submitted', ref:'R-1036', detail:'Leave application: own annual leave'},
    {at:'2026-10-28T13:20:00.000Z', by:'PSF-0007', role:'hr', action:'Submitted', ref:'R-1035', detail:'Contract renewal: Ivan Ssekandi'},
    {at:'2026-10-28T06:14:00.000Z', by:'PSF-0056', role:'staff', action:'Submitted', ref:'R-1031', detail:'Leave application: annual leave'},
    {at:'2026-10-27T09:12:00.000Z', by:'PSF-0007', role:'hr', action:'Edited employee file', ref:'PSF-0160', detail:'Next of kin phone updated'},
    {at:'2026-10-26T09:30:00.000Z', by:'PSF-0007', role:'hr', action:'Returned', ref:'R-1033', detail:'Missing burial notice'},
    {at:'2026-10-10T16:40:00.000Z', by:'PSF-0001', role:'md', action:'Approved', ref:'EX-07', detail:'Final dues for Rose Kyomuhendo'},
    {at:'2026-10-02T10:05:00.000Z', by:'PSF-0001', role:'md', action:'Approved', ref:'PAY-2026-09', detail:'September payroll posted to core banking'});
  AUDIT.sort((a, b) => b.at.localeCompare(a.at));
  NOTES.push({to:'hr', text:'Probation for Esther Nansubuga ends 12 Nov. Raise a confirmation or extension.', at:'2026-10-29T05:00:00.000Z', read:false},
             {to:'hr', text:'Contract for Collins Wanyama ends 28 Nov (30 days).', at:'2026-10-29T05:00:00.000Z', read:false},
             {to:'md', text:'October payroll not yet submitted by HR. Cut-off is 25th of each month.', at:'2026-10-29T05:00:00.000Z', read:false},
             {to:'PSF-0142', text:'R-1033 was returned: attach burial notice or LC1 letter.', at:'2026-10-26T09:30:00.000Z', read:false},
             {to:'PSF-0142', text:'AML/CFT refresher on 20 Nov is mandatory for all staff.', at:'2026-10-25T09:30:00.000Z', read:true});
})();
