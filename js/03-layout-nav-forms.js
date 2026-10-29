/* ================= SHELL, NAV, OVERLAYS, FORMS ================= */
const ICON = {
  home:'M3 11l9-7 9 7v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z', inbox:'M3 13h5l2 3h4l2-3h5M5 5h14l2 8v6H3v-6z',
  users:'M16 20v-1a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v1M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M22 20v-1a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8',
  user:'M20 21a8 8 0 0 0-16 0M12 13a5 5 0 1 0 0-10 5 5 0 0 0 0 10', clock:'M12 7v5l3 2M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18',
  cal:'M8 3v4M16 3v4M3 9h18M5 5h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z', star:'M12 3l2.8 5.7 6.2.9-4.5 4.4 1 6.2L12 17.3 6.5 20.2l1-6.2L3 9.6l6.2-.9z',
  flag:'M5 21V4M5 4h11l-2 4 2 4H5', book:'M4 19V5a2 2 0 0 1 2-2h14v16H6a2 2 0 0 0-2 2M20 19v2H6', cap:'M2 9l10-5 10 5-10 5zM6 11v5c3 2 9 2 12 0v-5',
  brief:'M3 8h18v12H3zM8 8V5h8v3M3 13h18', swap:'M7 4L3 8l4 4M3 8h14M17 20l4-4-4-4M21 16H7', cash:'M2 6h20v12H2zM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6M6 9v.01M18 15v.01',
  gift:'M4 11h16v10H4zM2 7h20v4H2zM12 7v14M12 7s-2-4-5-4-3 4 0 4M12 7s2-4 5-4 3 4 0 4', sliders:'M4 6h10M18 6h2M4 12h4M12 12h8M4 18h12M20 18h0M14 4v4M8 10v4M16 16v4',
  gavel:'M14 4l6 6M4 20l7-7M10 8l6 6M12 6l6 6-2 2-6-6z', door:'M14 3H5v18h9M14 3l6 2v14l-6 2zM11 12h.01', chart:'M4 20V10M10 20V4M16 20v-7M22 20H2',
  shield:'M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z', flow:'M6 3v12M6 15a3 3 0 1 0 0 6 3 3 0 0 0 0-6M18 9a3 3 0 1 0 0-6 3 3 0 0 0 0 6M18 9c0 6-12 3-12 6',
  plug:'M9 2v6M15 2v6M6 8h12v4a6 6 0 0 1-12 0zM12 18v4', list:'M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01', img:'M3 5h18v14H3zM3 16l5-5 4 4 3-3 6 6M15 9h.01',
  plus:'M12 5v14M5 12h14', x:'M6 6l12 12M18 6 6 18', file:'M14 3H6v18h12V7zM14 3v4h4'
};
const ic = k => `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="${ICON[k]}"/></svg>`;
const NAV = {
  hr:[['Overview',[['dashboard','Dashboard','home'],['approvals','Approvals','inbox']]],
      ['People',[['employees','Employees','users'],['attendance','Attendance','clock'],['leave','Leave management','cal']]],
      ['Talent',[['performance','Performance','star'],['probation','Probation and contracts','flag'],['training','Training and development','cap'],['recruitment','Recruitment','brief'],['movements','Transfers, promotions, salary','swap']]],
      ['Pay',[['payroll','Payroll','cash'],['gratuity','Gratuity and leave pay','gift'],['paysetup','Payroll setup','sliders']]],
      ['Relations',[['cases','Disciplinary and grievance','gavel'],['exits','Exit management','door']]],
      ['Resources',[['library','HR library','book'],['reports','HR reports','chart']]],
      ['Administration',[['roles','Roles and permissions','shield'],['workflow','Approval workflow','flow'],['systems','Connected systems','plug'],['audit','Audit trail','list'],['brand','Logos and branding','img']]]],
  staff:[['My workspace',[['home','My dashboard','home'],['myprofile','My employee file','user'],['myleave','Apply for leave','cal'],['myrequests','My requests','inbox']]],
         ['Pay and time',[['payslips','My payslips','cash'],['myattendance','My attendance','clock']]],
         ['Growth and support',[['mytraining','My trainings','cap'],['grievance','Raise a grievance','gavel'],['library','HR library','book'],['resign','Resignation','door']]]]
};
NAV.md = JSON.parse(JSON.stringify(NAV.hr)).map(([g, items]) => [g, items.filter(i => !['brand','workflow','paysetup'].includes(i[0]))]).filter(g => g[1].length);

const S = {role:'hr', view:'dashboard', param:null, q:'', tab:{}, logos:{ps:null, pp:null}, form:null};
const VIEWS = {};
const A = {}; /* actions by data-act */

function go(view, param = null) { S.view = view; S.param = param; render(); $('#side').classList.remove('open'); $('#view').scrollTop = 0; }
function render() {
  renderSide(); renderTop();
  const v = VIEWS[S.view] || VIEWS[S.role === 'staff' ? 'home' : 'dashboard'];
  $('#view').innerHTML = v(S.param);
  if (S.after) { const f = S.after; S.after = null; f(); }
}
function renderSide() {
  const count = awaiting().length;
  $('#side').innerHTML = `<div class="side-brand"><div><div class="t">PlusSave HR</div><div class="s">People system</div></div></div>` +
    NAV[S.role].map(([g, items]) => `<nav class="nav-group" aria-label="${esc(g)}"><h4>${esc(g)}</h4>${items.map(([k, l, i]) =>
      `<button class="nav-item ${S.view === k || (k === 'employees' && S.view === 'profile' && S.role !== 'staff') ? 'on' : ''}" data-go="${k}">${ic(i)}<span>${esc(l)}</span>${k === 'approvals' && count ? `<span class="badge">${count}</span>` : ''}${k === 'myrequests' && REQS.some(r => r.maker === USERS.staff.emp && r.status === 'returned') ? '<span class="badge">!</span>' : ''}</button>`).join('')}</nav>`).join('');
}
function myNotes() { const u = USERS[S.role]; return NOTES.filter(n => n.to === S.role || n.to === u.emp); }
function renderTop() {
  document.querySelectorAll('#roleseg button').forEach(b => b.classList.toggle('on', b.dataset.role === S.role));
  const e = me(); $('#me').innerHTML = `${av(e)}<div class="txt"><div class="n">${esc(nm(e))}</div><div class="r">${esc(e.title)}</div></div>`;
  const un = myNotes().filter(n => !n.read).length; const d = $('#belldot'); d.hidden = !un; d.textContent = un;
}
function psMark(s = 38, onDark = false) {
  return `<svg width="${s}" height="${s}" viewBox="0 0 40 40" aria-hidden="true"><rect width="40" height="40" rx="10" fill="${onDark ? '#ffffff' : 'var(--brand)'}"/><path d="M20 11v18M11 20h18" stroke="${onDark ? '#0b5a3a' : 'var(--brand-ink)'}" stroke-width="5" stroke-linecap="round"/><circle cx="31" cy="9" r="4" fill="var(--accent)"/></svg>`;
}
function ppMark(s = 38) {
  return `<svg width="${s}" height="${s}" viewBox="0 0 40 40" aria-hidden="true"><circle cx="20" cy="20" r="20" fill="var(--brand-strong)"/><path d="M14 29V11h7a6 6 0 0 1 0 12h-7" fill="none" stroke="var(--surface)" stroke-width="4" stroke-linejoin="round"/><path d="M27 25v8M23 29h8" stroke="var(--accent)" stroke-width="3" stroke-linecap="round"/></svg>`;
}
function logos() {
  return S.logos.ps ? `<div class="logos"><div class="logo"><img src="${S.logos.ps}" alt="PlusSave Financial Solutions"></div></div>` :
    `<div class="logos"><div class="logo" title="Placeholder: upload the official logo under Logos and branding"><div class="wm"><b>PlusSave</b><small>Financial Solutions</small></div></div></div>`;
}
function page({crumb, title, sub, actions = '', body}) {
  return `<div class="wrap"><div class="brandstrip">${logos()}<div class="strip-meta"><span>Human Resource Management System</span><span class="chip">Thu, 29 Oct 2026</span><span class="chip">Sample data</span></div></div>
  <div class="pagehead"><div style="min-width:0"><div class="crumbs">${esc(crumb)}</div><h1>${esc(title)}</h1>${sub ? `<p>${sub}</p>` : ''}</div><div class="actions">${actions}</div></div>${body}</div>`;
}
const card = (title, body, sub = '') => `<section class="card"><h3>${esc(title)}${sub ? `<span class="sub">${sub}</span>` : ''}</h3>${body}</section>`;
function table(cols, rows, {click, foot, empty = 'Nothing to show yet.'} = {}) {
  if (!rows.length) return `<div class="tw"><div class="empty">${empty}</div></div>`;
  return `<div class="tw"><table><thead><tr>${cols.map(c => `<th class="${c.r ? 'r' : ''}">${esc(c.h)}</th>`).join('')}</tr></thead><tbody>${rows.map(r =>
    `<tr ${click ? `class="click" ${click(r)}` : ''}>${cols.map(c => `<td class="${c.r ? 'r num' : ''}">${c.f(r)}</td>`).join('')}</tr>`).join('')}</tbody>${foot ? `<tfoot><tr>${foot}</tr></tfoot>` : ''}</table></div>`;
}
function bars(pairs) {
  const max = Math.max(1, ...pairs.map(p => p[1]));
  return `<div class="bars">${pairs.map(([l, v, go]) => `<div class="bar-row" title="${esc(l)}: ${v}" ${go ? `data-go="employees" data-filter="${esc(go)}" style="cursor:pointer"` : ''}><span class="lb">${esc(l)}</span><span class="bar-track"><span class="bar-fill" style="width:${v / max * 100}%"></span></span><span class="vv">${v}</span></div>`).join('')}</div>`;
}
const tabs = (key, list) => { const cur = S.tab[key] || list[0][0]; return `<div class="tabs" role="tablist">${list.map(([k, l]) => `<button class="${cur === k ? 'on' : ''}" data-tab="${key}" data-v="${k}">${esc(l)}</button>`).join('')}</div>`; };
const curTab = (key, def) => S.tab[key] || def;

/* overlays */
function openDrawer(html) { $('#drawer').innerHTML = html; $('#drawerScrim').hidden = false; }
function closeDrawer() { $('#drawerScrim').hidden = true; }
function openModal(html) { $('#modal').innerHTML = html; $('#modalScrim').hidden = false; }
function closeModal() { $('#modalScrim').hidden = true; S.form = null; }
const ovHead = (t, sub = '', closer = 'closeDrawer') => `<div class="ov-head"><div><h2>${esc(t)}</h2>${sub ? `<div class="muted" style="font-size:12.5px">${sub}</div>` : ''}</div><button class="iconbtn" data-act="${closer}" aria-label="Close">${ic('x').replace('<svg', '<svg width="16" height="16"')}</button></div>`;

/* forms: fields = [{id,label,type,opts,val,req,full,hint,ro,sec}] */
function fieldHtml(f) {
  if (f.sec) return `<div class="sec-title">${esc(f.sec)}</div>`;
  if (f.type === 'calc') return `<div class="calc" id="calc">${f.val || ''}</div>`;
  const id = 'f_' + f.id, req = f.req ? ' required' : '', ro = f.ro ? ' readonly' : '';
  let input;
  if (f.type === 'select') input = `<select id="${id}"${req}>${(f.opts || []).map(o => { const [v, l] = Array.isArray(o) ? o : [o, o]; return `<option value="${esc(v)}" ${String(v) === String(f.val ?? '') ? 'selected' : ''}>${esc(l)}</option>`; }).join('')}</select>`;
  else if (f.type === 'textarea') input = `<textarea id="${id}"${req}${ro}>${esc(f.val || '')}</textarea>`;
  else if (f.type === 'file') input = `<input type="file" id="${id}" ${f.multiple ? 'multiple' : ''} accept="${f.accept || ''}">`;
  else input = `<input type="${f.type || 'text'}" id="${id}" value="${esc(f.val ?? '')}"${req}${ro} ${f.min != null ? `min="${f.min}"` : ''}>`;
  return `<div class="field ${f.full ? 'full' : ''}"><label for="${id}">${esc(f.label)}${f.req ? ' <span class="req">*</span>' : ''}</label>${input}${f.hint ? `<span class="hint">${f.hint}</span>` : ''}</div>`;
}
function formModal({title, sub = '', fields, submit = 'Submit for approval', onSubmit, onInput, flow}) {
  S.form = {fields, onSubmit, onInput};
  openModal(`${ovHead(title, sub, 'closeModal')}<form id="theform" novalidate><div class="ov-body">${flow ? flowHtml(flow, 0) : ''}<div class="form">${fields.map(fieldHtml).join('')}</div></div>
  <div class="ov-foot"><button type="button" class="btn" data-act="closeModal">Cancel</button><button type="submit" class="btn primary">${esc(submit)}</button></div></form>`);
  if (onInput) onInput(readForm());
}
function readForm() {
  const v = {}; (S.form?.fields || []).forEach(f => { if (!f.id) return; const el = document.getElementById('f_' + f.id); if (!el) return;
    v[f.id] = f.type === 'file' ? [...el.files].map(x => x.name).join(', ') : f.type === 'number' ? (el.value === '' ? '' : +el.value) : el.value; });
  return v;
}
function formError(msg) { let n = document.getElementById('formerr'); if (!n) { n = document.createElement('div'); n.id = 'formerr'; n.className = 'note crit'; document.querySelector('#theform .ov-body').prepend(n); } n.innerHTML = `<b>Fix before submitting:</b> ${esc(msg)}`; n.scrollIntoView({block:'nearest'}); }

/* maker-checker visuals */
function flowHtml(r, _) {
  const chain = Array.isArray(r) ? r : null;
  const steps = chain ? chain : ['maker', ...r.chain];
  const labels = {maker:'Maker', staff:'Staff', hr:'HR review', md:'MD approval'};
  return `<div class="flow" style="--n:${steps.length + (chain ? 0 : 1)}">${steps.map((s, i) => {
    let cls = '', sub = '';
    if (!chain) {
      if (i === 0) { cls = 'done'; sub = nm(emp(r.maker)); }
      else { const si = i - 1;
        if (r.status === 'approved' || si < r.stage) cls = 'done';
        else if (si === r.stage && r.status === 'pending') { cls = 'cur'; sub = 'Waiting'; }
        else if (si === r.stage && r.status === 'rejected') { cls = 'bad'; sub = 'Rejected'; }
        else if (si === r.stage && r.status === 'returned') { cls = 'bad'; sub = 'Returned'; } }
    } else if (i === 0) cls = 'cur';
    return `<div class="step ${cls}"><div class="dotc">${cls === 'done' ? '✓' : i + 1}</div><div class="sl">${chain && i === 0 ? ROLE_NAME[s] + ' (maker)' : labels[s] || s}</div><div class="ss">${esc(sub)}</div></div>`;
  }).join('')}${chain ? '' : `<div class="step ${r.status === 'approved' ? 'done' : ''}"><div class="dotc">${r.status === 'approved' ? '✓' : steps.length + 1}</div><div class="sl">Applied</div><div class="ss">${r.status === 'approved' ? 'Record updated' : ''}</div></div>`}</div>`;
}
function reqDrawer(id) {
  const r = REQS.find(x => x.id === id); if (!r) return;
  const mine = isMine(r), can = canAct(r);
  let actions = '';
  if (can) actions = `<div class="field"><label for="actc">Comment ${S.role === 'hr' ? '(required to return or reject)' : '(required to return or reject)'}</label><textarea id="actc" placeholder="${S.role === 'hr' ? 'What did you verify? e.g. balance checked, reliever confirmed' : 'Approval note'}"></textarea></div>
    <div class="actions">${S.role === 'hr' && r.chain[r.stage] === 'hr' && r.stage < r.chain.length - 1 ? `<button class="btn primary" data-act="req" data-a="forward" data-id="${r.id}">Verify and forward to MD</button>` : `<button class="btn ok" data-act="req" data-a="approve" data-id="${r.id}">Approve</button>`}
    <button class="btn warn" data-act="req" data-a="return" data-id="${r.id}">Return for correction</button><button class="btn danger" data-act="req" data-a="reject" data-id="${r.id}">Reject</button></div>`;
  else if (stageRole(r) === S.role && mine) actions = `<div class="note warn"><b>Segregation of duties.</b> You created this request, so you cannot approve it. It has been routed to the next checker.</div>`;
  else if (r.status === 'returned' && mine) actions = `<div class="field"><label for="actc">What did you correct?</label><textarea id="actc" placeholder="e.g. Burial notice attached"></textarea></div><div class="field"><label for="actf">Attach document</label><input type="file" id="actf"></div><div class="actions"><button class="btn primary" data-act="resubmit" data-id="${r.id}">Resubmit</button></div>`;
  openDrawer(`${ovHead(r.type, `${r.id} · raised ${DT(r.created)}`)}<div class="ov-body">
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">${reqStatus(r)} ${sla(r)} <span class="chip">Maker: ${esc(nm(emp(r.maker)))} (${ROLE_NAME[r.makerRole]})</span></div>
    ${flowHtml(r)}
    <section class="card"><h3>${esc(r.subject)}</h3><dl class="kv">${r.fields.map(([k, v]) => `<dt>${esc(k)}</dt><dd>${esc(v)}</dd>`).join('')}</dl></section>
    <section><h3 style="font-size:14px;margin-bottom:10px">Approval history</h3><div class="hist">${r.hist.map(h => `<div class="h ${h.k || ''}"><div class="w1">${esc(h.act)}</div><div class="muted">${esc(nm(emp(h.by)))} · ${DT(h.at)}</div>${h.c ? `<div class="c">${esc(h.c)}</div>` : ''}</div>`).join('')}</div></section>
    ${actions}</div>`);
}
A.req = el => { const r = REQS.find(x => x.id === el.dataset.id); const ok = act(r, el.dataset.a, $('#actc').value || ''); if (ok) { closeDrawer(); render(); toast({forward:'Verified and sent to the MD', approve:'Approved. Records updated.', return:'Returned with your comment', reject:'Rejected. The maker has been notified.'}[el.dataset.a]); } };
A.resubmit = el => { const r = REQS.find(x => x.id === el.dataset.id); resubmit(r, $('#actc').value || ''); closeDrawer(); render(); toast('Resubmitted to HR'); };
A.closeDrawer = () => closeDrawer();
A.closeModal = () => closeModal();
A.open = el => reqDrawer(el.dataset.id);

/* notifications */
A.bell = () => { const list = myNotes(); openDrawer(`${ovHead('Notifications', 'Also sent by email and SMS')}<div class="ov-body"><div class="list">${list.length ? list.map(n => `<div class="li"><span class="pill ${n.read ? 'plain' : 'gold'}">${n.read ? 'Read' : 'New'}</span><div class="grow"><div class="t" style="font-weight:500">${esc(n.text)}</div><div class="s">${DT(n.at)}</div></div></div>`).join('') : '<div class="empty">No notifications.</div>'}</div></div>`); list.forEach(n => n.read = true); renderTop(); };

/* global events */
document.addEventListener('click', e => {
  const t = e.target.closest('[data-go],[data-act],[data-tab],[data-role]');
  if (!t) { if (e.target.id === 'drawerScrim') closeDrawer(); if (e.target.id === 'modalScrim') closeModal(); return; }
  if (t.dataset.role) { S.role = t.dataset.role; S.view = S.role === 'staff' ? 'home' : 'dashboard'; S.param = null; render(); toast(`Now viewing as ${USERS[S.role].label}: ${nm(me())}`); return; }
  if (t.dataset.tab) { S.tab[t.dataset.tab] = t.dataset.v; render(); return; }
  if (t.dataset.go) { if (t.dataset.filter) S.filter = t.dataset.filter; closeDrawer(); go(t.dataset.go, t.dataset.p || null); return; }
  if (t.dataset.act && A[t.dataset.act]) A[t.dataset.act](t, e);
});
document.addEventListener('submit', e => { if (e.target.id !== 'theform') return; e.preventDefault(); const v = readForm(); const r = S.form.onSubmit(v); if (r !== false) { closeModal(); render(); } });
document.addEventListener('input', e => { if (S.form && S.form.onInput && e.target.closest('#theform')) S.form.onInput(readForm()); });
document.addEventListener('change', e => { if (S.form && S.form.onInput && e.target.closest('#theform')) S.form.onInput(readForm()); if (e.target.dataset.change && A[e.target.dataset.change]) A[e.target.dataset.change](e.target); });
document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeDrawer(); closeModal(); } });
