/* ================= SAMPLE DATA (demo only) ================= */
const TODAY = new Date(2026, 9, 29);
const ISO = d => { const x = new Date(d); return x.getFullYear()+'-'+String(x.getMonth()+1).padStart(2,'0')+'-'+String(x.getDate()).padStart(2,'0'); };
const BRANCHES = ['Saasa Plaza (HQ)', 'Old Taxi Park', 'Kikuubo'];
const DEPTS = ['Executive','HR & Admin','Finance','Credit & Loans','Operations','PlusPay Remittances','Customer Service','Business Development & Marketing','ICT','Risk & Compliance'];
const TYPES = ['Permanent','Contract','Probationary','Part-time','Intern'];
const STATUSES = ['Active','On Leave','Suspended','Resigned','Terminated'];
const LEAVE_TYPES = {
  Annual:{days:21, note:'21 working days a year (7 days per 4 months of service)'},
  Sick:{days:30, note:'Up to 2 months a year; first month paid. Medical form needed beyond 2 days'},
  Maternity:{days:60, note:'60 working days, full pay', g:'F'},
  Paternity:{days:4, note:'4 working days, full pay', g:'M'},
  Unpaid:{days:30, note:'Deducted from payroll; MD approval required'},
  Other:{days:6, note:'Compassionate, study or exam leave as per HR policy'}
};
const HOLIDAYS = ['2026-10-09','2026-12-25','2026-12-26','2027-01-01','2027-01-26','2027-02-16','2027-03-08'];
const GRADES = {
  G1:{name:'Executive', tr:800000, hs:1500000, at:150000},
  G2:{name:'Senior Management', tr:500000, hs:800000, at:100000},
  G3:{name:'Management', tr:350000, hs:500000, at:80000},
  G4:{name:'Officer', tr:200000, hs:250000, at:50000},
  G5:{name:'Support', tr:120000, hs:0, at:30000},
  G6:{name:'Intern', tr:100000, hs:0, at:20000}
};
const NOK = [['Sarah Mugisha','Spouse'],['Peter Achieng','Brother'],['Agnes Kato','Mother'],['John Namubiru','Spouse'],['Mary Ssekandi','Mother'],['Paul Atim','Father'],['Ruth Opio','Spouse'],['Jane Akello','Sister'],['Moses Mutebi','Father'],['Joan Nansubuga','Mother'],['Faith Tumusiime','Spouse'],['Tom Nakimuli','Father'],['Lydia Wanyama','Spouse'],['Ronald Nakato','Brother'],['Grace Auma','Mother'],['Esther Ochieng','Spouse'],['Ivan Kyomuhendo','Brother']];
const ADDR = ['Ntinda, Kampala','Kira, Wakiso','Najjera, Wakiso','Mengo, Kampala','Bukoto, Kampala','Kisaasi, Kampala','Nansana, Wakiso','Kawempe, Kampala','Muyenga, Kampala','Kansanga, Kampala','Mukono','Seeta, Mukono','Bweyogerere, Wakiso','Kyebando, Kampala','Entebbe','Kireka, Wakiso','Makindye, Kampala'];

let _n = 0;
function E(id, fn, ln, g, dob, title, dept, branch, sup, type, joined, grade, basic, x = {}) {
  const n = _n++;
  return Object.assign({
    id, fn, ln, g, dob, title, dept, branch, sup, type, joined, grade, basic,
    status:'Active', nin:`CM${g}${dob.slice(2,4)}••••••${id.slice(-3)}K`,
    phone:`07${['72','01','53','89','74','00'][n%6]} ${String(n*137%900+100)} ${String(n*419%900+100)}`,
    email:`${fn}.${ln}`.toLowerCase()+'@plussavefinancialsolutions.com',
    addr:ADDR[n%ADDR.length], nok:{n:NOK[n%NOK.length][0], rel:NOK[n%NOK.length][1], p:`07${['01','72','74'][n%3]} ${String(n*251%900+100)} ${String(n*613%900+100)}`},
    cEnd:null, pStart:null, pEnd:null, appr:'2026-12-15', bal:{Annual:21-(n%9), Sick:30, Other:6},
    loan:0, sav:0, photo:null, acct:`••••${String(4400+n*37).slice(-4)}`,
    docs:{CV:1,'Academic documents':1,'Appointment letter':1,Contract:type==='Contract'?1:0,NDA:1,'Job description':1,'National ID copy':1,Appraisals:n%3?1:0,'Disciplinary letters':0,'Exit documents':0},
    trainings:[], movements:[]
  }, x);
}
const EMP = [
  E('PSF-0001','Daniel','Mugisha','M','1978-03-14','Managing Director','Executive',BRANCHES[0],null,'Permanent','2015-02-01','G1',9500000,{appr:'2027-01-31'}),
  E('PSF-0007','Ruth','Achieng','F','1986-07-22','HR & Admin Manager','HR & Admin',BRANCHES[0],'PSF-0001','Permanent','2017-05-02','G2',4200000),
  E('PSF-0015','Joseph','Kato','M','1983-11-02','Finance Manager','Finance',BRANCHES[0],'PSF-0001','Permanent','2016-08-15','G2',4500000,{loan:350000}),
  E('PSF-0021','Grace','Namubiru','F','1985-01-19','Credit Manager','Credit & Loans',BRANCHES[0],'PSF-0001','Permanent','2018-03-01','G2',4000000,{status:'On Leave',appr:'2026-11-10'}),
  E('PSF-0034','Ivan','Ssekandi','M','1992-06-30','ICT Officer','ICT',BRANCHES[0],'PSF-0015','Contract','2024-12-16','G4',2600000,{cEnd:'2026-12-15',appr:'2026-11-20'}),
  E('PSF-0048','Sharon','Atim','F','1994-09-12','Accountant','Finance',BRANCHES[0],'PSF-0015','Permanent','2021-01-11','G4',2200000,{sav:100000}),
  E('PSF-0056','Moses','Opio','M','1993-04-05','Loans Officer','Credit & Loans',BRANCHES[1],'PSF-0021','Permanent','2020-06-01','G4',1600000,{loan:180000,appr:'2026-11-05'}),
  E('PSF-0063','Patience','Akello','F','1996-12-08','PlusPay Remittance Officer','PlusPay Remittances',BRANCHES[1],'PSF-0071','Contract','2025-01-21','G4',1800000,{cEnd:'2027-01-20'}),
  E('PSF-0071','Allan','Mutebi','M','1988-02-27','Branch Manager','Operations',BRANCHES[1],'PSF-0001','Permanent','2019-09-02','G3',3200000,{loan:250000}),
  E('PSF-0088','Esther','Nansubuga','F','1998-05-17','Marketing Officer','Business Development & Marketing',BRANCHES[0],'PSF-0001','Probationary','2026-05-12','G4',1500000,{pStart:'2026-05-12',pEnd:'2026-11-12',bal:{Annual:7,Sick:30,Other:6}}),
  E('PSF-0102','Brian','Tumusiime','M','1995-08-23','Teller','Operations',BRANCHES[2],'PSF-0071','Permanent','2022-02-14','G5',1100000,{sav:50000,appr:'2026-11-08'}),
  E('PSF-0117','Juliet','Nakimuli','F','1999-10-03','Customer Service Officer','Customer Service',BRANCHES[2],'PSF-0071','Probationary','2026-06-01','G5',1000000,{pStart:'2026-06-01',pEnd:'2026-11-30',bal:{Annual:7,Sick:30,Other:6}}),
  E('PSF-0129','Collins','Wanyama','M','1991-03-29','Recoveries Officer','Credit & Loans',BRANCHES[2],'PSF-0021','Contract','2025-11-29','G4',1300000,{cEnd:'2026-11-28'}),
  E('PSF-0142','Brenda','Nakato','F','1997-01-15','Customer Service Officer','Customer Service',BRANCHES[1],'PSF-0071','Permanent','2023-04-03','G5',1050000,{bal:{Annual:14,Sick:28,Other:6},appr:'2026-11-18'}),
  E('PSF-0150','Hilda','Auma','F','2002-07-07','Accounts Intern','Finance',BRANCHES[0],'PSF-0048','Intern','2026-08-01','G6',400000,{cEnd:'2027-01-31',bal:{Annual:0,Sick:10,Other:2}}),
  E('PSF-0153','Peter','Ochieng','M','1990-12-01','Office Messenger','HR & Admin',BRANCHES[0],'PSF-0007','Part-time','2025-03-03','G5',450000),
  E('PSF-0160','Sylvia','Kyomugisha','F','1989-05-09','Compliance Officer','Risk & Compliance',BRANCHES[0],'PSF-0001','Permanent','2026-10-05','G4',2400000,{bal:{Annual:2,Sick:30,Other:6}}),
  E('PSF-0098','Rose','Kyomuhendo','F','1995-02-11','Teller','Operations',BRANCHES[1],'PSF-0071','Permanent','2021-07-05','G5',1100000,{status:'Resigned'})
];
EMP.forEach(e => { if (e.status === 'Resigned') e.docs['Exit documents'] = 1; });
EMP.find(e=>e.id==='PSF-0102').docs['Disciplinary letters'] = 1;

const USERS = {
  staff:{emp:'PSF-0142', role:'staff', label:'Staff'},
  hr:{emp:'PSF-0007', role:'hr', label:'HR Manager'},
  md:{emp:'PSF-0001', role:'md', label:'Managing Director'}
};

const ATT = { /* today's clock-in/out */
  'PSF-0001':['08:12',null,'Biometric'],'PSF-0007':['07:54',null,'Biometric'],'PSF-0015':['08:05',null,'Biometric'],
  'PSF-0034':['08:31',null,'Biometric'],'PSF-0048':['07:58',null,'Biometric'],'PSF-0056':['08:47',null,'Biometric'],
  'PSF-0063':['08:02',null,'Mobile (geo-fenced)'],'PSF-0071':['07:40',null,'Biometric'],'PSF-0088':['08:15',null,'Biometric'],
  'PSF-0102':['08:09',null,'Biometric'],'PSF-0117':['08:58',null,'Biometric'],'PSF-0142':['07:59',null,'Biometric'],
  'PSF-0150':['08:20',null,'Biometric'],'PSF-0160':['08:03',null,'Biometric']
};
const SHIFT_START = '08:30';

const LEAVES = [
  {id:'LV-311', emp:'PSF-0021', type:'Annual', from:'2026-10-27', to:'2026-11-06', days:9, status:'Approved'},
  {id:'LV-305', emp:'PSF-0048', type:'Sick', from:'2026-10-12', to:'2026-10-13', days:2, status:'Approved'},
  {id:'LV-298', emp:'PSF-0015', type:'Annual', from:'2026-12-21', to:'2027-01-08', days:12, status:'Approved'},
  {id:'LV-290', emp:'PSF-0142', type:'Annual', from:'2026-08-10', to:'2026-08-14', days:5, status:'Approved'},
  {id:'LV-284', emp:'PSF-0071', type:'Paternity', from:'2026-09-01', to:'2026-09-04', days:4, status:'Approved'}
];

const APPRAISALS = [
  {emp:'PSF-0056', cycle:'H2 2026', due:'2026-11-05', status:'Self-appraisal submitted', score:null},
  {emp:'PSF-0102', cycle:'H2 2026', due:'2026-11-08', status:'Not started', score:null},
  {emp:'PSF-0021', cycle:'H2 2026', due:'2026-11-10', status:'Not started', score:null},
  {emp:'PSF-0088', cycle:'Probation review', due:'2026-11-05', status:'Supervisor review', score:null},
  {emp:'PSF-0142', cycle:'H2 2026', due:'2026-11-18', status:'Not started', score:null},
  {emp:'PSF-0034', cycle:'Contract review', due:'2026-11-20', status:'Not started', score:null},
  {emp:'PSF-0048', cycle:'H1 2026', due:'2026-06-30', status:'Completed', score:4.2},
  {emp:'PSF-0071', cycle:'H1 2026', due:'2026-06-30', status:'Completed', score:4.5},
  {emp:'PSF-0063', cycle:'H1 2026', due:'2026-06-30', status:'Completed', score:3.8}
];

const TRAININGS = [
  {id:'TR-21', title:'AML/CFT and KYC refresher', provider:'Risk & Compliance (internal)', date:'2026-11-20', mandatory:true, status:'Scheduled', audience:'All staff', done:[]},
  {id:'TR-20', title:'Interswitch agency banking and Verve card operations', provider:'Interswitch', date:'2026-11-12', mandatory:false, status:'Scheduled', audience:'Operations, Customer Service', done:[]},
  {id:'TR-19', title:'PlusPay remittance compliance (China corridor)', provider:'PlusPay team', date:'2026-09-18', mandatory:false, status:'Completed', audience:'PlusPay, Operations', done:['PSF-0063','PSF-0071','PSF-0102','PSF-0142']},
  {id:'TR-18', title:'Customer service excellence', provider:'External facilitator', date:'2026-07-24', mandatory:false, status:'Completed', audience:'Customer Service', done:['PSF-0117','PSF-0142','PSF-0098']},
  {id:'TR-17', title:'Fire safety and first aid', provider:'Uganda Red Cross', date:'2026-05-15', mandatory:true, status:'Completed', audience:'All staff', done:['PSF-0001','PSF-0007','PSF-0015','PSF-0021','PSF-0048','PSF-0056','PSF-0071','PSF-0102','PSF-0142','PSF-0153']},
  {id:'TR-16', title:'Credit appraisal and loan recovery', provider:'Credit department', date:'2026-03-10', mandatory:false, status:'Completed', audience:'Credit & Loans', done:['PSF-0021','PSF-0056','PSF-0129']}
];

const VACANCIES = [
  {id:'VC-09', title:'Agency Banking Supervisor', dept:'Operations', branch:BRANCHES[0], opened:'2026-10-06', closes:'2026-11-10', applicants:23, stage:'Shortlisting', reason:'New role for Interswitch agency banking rollout'},
  {id:'VC-08', title:'Internal Auditor', dept:'Risk & Compliance', branch:BRANCHES[0], opened:'2026-09-21', closes:'2026-10-31', applicants:14, stage:'Interviews', reason:'Regulatory requirement'},
  {id:'VC-07', title:'Teller', dept:'Operations', branch:BRANCHES[1], opened:'2026-10-12', closes:'2026-11-05', applicants:41, stage:'Advertised', reason:'Replacement: Rose Kyomuhendo (resigned)'}
];

const CASES = [
  {id:'DC-014', kind:'Disciplinary', emp:'PSF-0129', category:'Absenteeism and late reporting', incident:'2026-10-14', reportedBy:'PSF-0021', involved:['PSF-0129'], witnesses:['PSF-0102'], desc:'Absent without permission on 14 and 15 Oct; late on 6 of the last 10 working days.', stage:'Hearing', hearing:{date:'2026-11-04', panel:['PSF-0007','PSF-0071'], minutes:''}, decision:null, due:'2026-11-04'},
  {id:'DC-013', kind:'Disciplinary', emp:'PSF-0102', category:'Cash shortage (UGX 150,000)', incident:'2026-08-19', reportedBy:'PSF-0071', involved:['PSF-0102'], witnesses:[], desc:'Till shortage at Kikuubo on close of business.', stage:'Closed', hearing:{date:'2026-08-27', panel:['PSF-0007','PSF-0071'], minutes:'Employee admitted counting error. Refunded in full.'}, decision:'Written warning (valid 6 months) and refund', due:null},
  {id:'GR-006', kind:'Grievance', emp:'PSF-0063', category:'Pay and overtime', incident:'2026-10-20', reportedBy:'PSF-0063', involved:['PSF-0063'], witnesses:[], desc:'Weekend remittance shifts in September not reflected as overtime.', stage:'Investigation', hearing:null, decision:null, due:'2026-11-03', confidential:false}
];
const CASE_STAGES = {Disciplinary:['Incident','People involved','Hearing','Decision','Closed'], Grievance:['Lodged','Investigation','Meeting','Resolution','Closed']};

const EXITS = [
  {id:'EX-07', emp:'PSF-0098', type:'Resignation', notice:'2026-09-10', lwd:'2026-10-10', reason:'Further studies abroad', interview:'Would return. Rated supervisor support 4/5; suggested clearer career path for tellers.', status:'Completed',
   clear:{'Handover notes submitted':1,'Laptop, keys and ID card returned':1,'Core banking user disabled':1,'PlusPay user disabled':1,'Email account closed':1,'Staff loan balance settled':1,'Final dues computed':1,'Exit interview done':1,'Certificate of service issued':1}}
];

const LIBRARY = [
  {id:'DOC-01', title:'HR Policy Manual', ver:'3.2', cat:'Manual', owner:'HR & Admin', approved:'2026-02-10', ack:15},
  {id:'DOC-02', title:'Code of Conduct and Ethics', ver:'2.0', cat:'Policy', owner:'HR & Admin', approved:'2025-11-03', ack:17},
  {id:'DOC-03', title:'Leave Policy', ver:'1.4', cat:'Policy', owner:'HR & Admin', approved:'2026-07-01', ack:12},
  {id:'DOC-04', title:'Disciplinary and Grievance Procedure', ver:'1.1', cat:'Procedure', owner:'HR & Admin', approved:'2025-06-20', ack:16},
  {id:'DOC-05', title:'Staff Loans and Advances Policy', ver:'1.3', cat:'Policy', owner:'Finance', approved:'2026-04-14', ack:11},
  {id:'DOC-06', title:'AML/CFT and KYC Policy', ver:'4.0', cat:'Policy', owner:'Risk & Compliance', approved:'2026-09-01', ack:9},
  {id:'DOC-07', title:'Data Protection and Privacy Policy', ver:'1.0', cat:'Policy', owner:'ICT', approved:'2026-03-02', ack:14},
  {id:'DOC-08', title:'Staff Induction Handbook', ver:'2.1', cat:'Handbook', owner:'HR & Admin', approved:'2026-01-15', ack:17},
  {id:'DOC-09', title:'Appointment letter template', ver:'1.2', cat:'Template', owner:'HR & Admin', approved:'2025-10-01', ack:null},
  {id:'DOC-10', title:'Exit clearance form', ver:'1.0', cat:'Form', owner:'HR & Admin', approved:'2025-10-01', ack:null}
];

const PAYROLL_HIST = [
  {period:'2026-07', staff:16, gross:57140000, net:40112000, status:'Posted'},
  {period:'2026-08', staff:17, gross:57540000, net:40391000, status:'Posted'},
  {period:'2026-09', staff:17, gross:57540000, net:40394000, status:'Posted'}
];
let PAY = {period:'2026-10', status:'Draft', reqId:null, inputs:[
  {emp:'PSF-0063', kind:'Overtime', amt:240000, note:'Weekend remittance shifts, 4 Sat'},
  {emp:'PSF-0056', kind:'Commission', amt:150000, note:'Loan book growth target met'},
  {emp:'PSF-0098', kind:'Leave pay', amt:211538, note:'5 unused annual leave days on exit'},
  {emp:'PSF-0102', kind:'Deduction', amt:50000, note:'Salary advance recovery (2 of 3)'}
]};

const CONFIG = {
  paye:[[335000,0],[410000,.20],[485000,.25],[Infinity,.30]], surtaxOver:10000000, surtax:.10,
  nssfEe:.05, nssfEr:.10, lstMonths:[7,8,9,10], lstDeductible:true,
  lst:[[100000,0],[200000,5000],[300000,10000],[400000,20000],[500000,30000],[600000,40000],[700000,60000],[800000,70000],[900000,80000],[1000000,90000],[Infinity,100000]],
  leaveDivisor:26, gratuity:{basis:'pct', pct:25, months:1},
  sla:{hr:48, md:48}, weekend:[0,6]
};

const WORKFLOWS = [
  ['Leave application','Staff','HR verifies balance and cover','MD approves'],
  ['Attendance correction','Staff','HR checks device log','MD approves'],
  ['Personal details change','Staff','HR checks supporting document','MD approves'],
  ['Training request','Staff','HR checks plan and budget','MD approves'],
  ['Grievance','Staff','HR investigates','MD approves resolution'],
  ['Resignation','Staff','HR runs exit clearance','MD approves final dues'],
  ['New employee','HR','','MD approves'],
  ['Transfer, promotion or salary change','HR','','MD approves'],
  ['Contract renewal or probation confirmation','HR','','MD approves'],
  ['Disciplinary decision','HR','','MD approves'],
  ['Monthly payroll','HR','','MD approves, then posts to core banking'],
  ['Gratuity or leave pay','HR','','MD approves'],
  ['Policy or document published to HR Library','HR','','MD approves']
];

const PERMS = {
  'Employee records':['ADD EMPLOYEE','EDIT EMPLOYEE FILE','VIEW EMPLOYEE FILE','EMPLOYEE POSITIONS','UPLOAD EMPLOYEE DOCUMENTS','DEACTIVATE EMPLOYEE'],
  'Payroll':['BASIC SALARY SETUP','EMPLOYEE GRADE SETUP','EMPLOYEE GRADE ALLOWANCES','EMPLOYEE GRADE DEDUCTIONS','DEDUCTION SETUP','EMPLOYEE SALARY','ADJUST SALARY ITEM','PAYROLL INPUT','RUN PAYROLL','POST PAYROLL TO CORE BANKING','PAYSLIPS'],
  'Time and leave':['ATTENDANCE','ATTENDANCE CORRECTION','LEAVE SETUP','APPLY LEAVE','LEAVE APPROVAL'],
  'Talent':['PERFORMANCE APPRAISAL','PROBATION AND CONTRACTS','TRAINING AND DEVELOPMENT','RECRUITMENT','TRANSFERS AND PROMOTIONS'],
  'Relations and exits':['DISCIPLINARY CASES','GRIEVANCES','EXIT MANAGEMENT','GRATUITY CALCULATION'],
  'Library and reports':['HR LIBRARY UPLOAD','HR LIBRARY VIEW','HR REPORTS','HR DASHBOARD','AUDIT TRAIL'],
  'Administration':['USER ROLES AND PERMISSIONS','WORKFLOW SETUP','INTEGRATION SETTINGS']
};
const ROLE_PERMS = {
  'Staff (self-service)':{p:['APPLY LEAVE','HR LIBRARY VIEW','PAYSLIPS','ATTENDANCE CORRECTION','GRIEVANCES'], a:['Initiate']},
  'HR Manager':{p:['ADD EMPLOYEE','EDIT EMPLOYEE FILE','VIEW EMPLOYEE FILE','EMPLOYEE POSITIONS','UPLOAD EMPLOYEE DOCUMENTS','BASIC SALARY SETUP','EMPLOYEE GRADE SETUP','EMPLOYEE GRADE ALLOWANCES','EMPLOYEE GRADE DEDUCTIONS','DEDUCTION SETUP','EMPLOYEE SALARY','ADJUST SALARY ITEM','PAYROLL INPUT','RUN PAYROLL','PAYSLIPS','ATTENDANCE','ATTENDANCE CORRECTION','LEAVE SETUP','LEAVE APPROVAL','PERFORMANCE APPRAISAL','PROBATION AND CONTRACTS','TRAINING AND DEVELOPMENT','RECRUITMENT','TRANSFERS AND PROMOTIONS','DISCIPLINARY CASES','GRIEVANCES','EXIT MANAGEMENT','GRATUITY CALCULATION','HR LIBRARY UPLOAD','HR LIBRARY VIEW','HR REPORTS','HR DASHBOARD'], a:['Initiate','Verify','Export reports']},
  'Managing Director':{p:['VIEW EMPLOYEE FILE','LEAVE APPROVAL','RUN PAYROLL','POST PAYROLL TO CORE BANKING','TRANSFERS AND PROMOTIONS','DISCIPLINARY CASES','GRIEVANCES','EXIT MANAGEMENT','GRATUITY CALCULATION','HR LIBRARY VIEW','HR REPORTS','HR DASHBOARD','AUDIT TRAIL'], a:['Approve','Export reports','Delegate approvals']},
  'Finance (payroll posting)':{p:['PAYSLIPS','HR REPORTS','POST PAYROLL TO CORE BANKING'], a:['View only','Export reports']},
  'Internal Auditor':{p:['VIEW EMPLOYEE FILE','HR REPORTS','AUDIT TRAIL','HR LIBRARY VIEW'], a:['View only','Export reports']},
  'System Admin (IT)':{p:['USER ROLES AND PERMISSIONS','WORKFLOW SETUP','INTEGRATION SETTINGS','AUDIT TRAIL'], a:['Initiate']}
};
const PRIVS = ['Initiate','Verify','Approve','View only','Export reports','Delegate approvals'];

const SYSTEMS = [
  {name:'Core banking (Interswitch)', state:'ok', flows:['Salary credited to each staff account after MD approves payroll','Staff loan repayments pulled from the loan schedule into payroll deductions','New staff get a core banking user on onboarding; disabled on exit']},
  {name:'PlusPay', state:'ok', flows:['Creates Agent or Branch Manager logins from the employee record','Disables PlusPay access the day an exit is approved','Remittance overtime and commissions feed payroll input']},
  {name:'Biometric clock-in devices', state:'ok', flows:['Clock-in and clock-out synced every 15 minutes per branch','Mobile clock-in allowed only inside branch geo-fence']},
  {name:'URA, NSSF and KCCA returns', state:'warn', flows:['PAYE return file for URA e-filing by the 15th','NSSF contribution schedule (5% + 10%)','Local Service Tax schedule, July to October']},
  {name:'Accounting / general ledger', state:'ok', flows:['Payroll journal by cost centre (branch and department)','Gratuity and leave-pay provisions']},
  {name:'Email and SMS (company mail server)', state:'ok', flows:['Approval requests and outcomes by email and SMS','Alerts for contract expiry, probation end and appraisals']}
];

let REQS = [];
let AUDIT = [];
let NOTES = [];
