/**
 * JOB TRACKING SYSTEM - Google Apps Script
 *
 * Builds five tabs:
 *   1. All Jobs                 (the ONLY place you type data)
 *   2. Jobs Ready for Delivery  (auto: 1st Available Date within 30 days, not In Transit / Delivered)
 *   3. Jobs More Than 30 Days   (auto: 1st Available Date more than 30 days away, not In Transit / Delivered)
 *   4. Jobs in Transit          (auto: Status = In Transit)
 *   5. Jobs Delivered           (auto: Status = Delivered)
 *
 * HOW TO USE: run setupJobTracker() once (see the step-by-step instructions).
 * Safe to run again: it never deletes the data you typed into "All Jobs".
 *
 * @OnlyCurrentDoc
 */

// ----------------------------- SETTINGS -----------------------------
var SHEET = {
  ALL: 'All Jobs',
  READY: 'Jobs Ready for Delivery',
  LONG: 'Jobs More Than 30 Days',
  TRANSIT: 'Jobs in Transit',
  DELIVERED: 'Jobs Delivered'
};

var HEADERS = [
  'Job #', 'Customer First Name', 'Customer Last Name', 'Pickup Date',
  '1st Available Date', 'From', 'To', 'CF', 'Balance', 'Job Location',
  'Status', 'Carrier Name', 'Driver Name', 'Phone Number', 'Storage Fee'
];

var COL = {
  JOB: 1, FIRST: 2, LAST: 3, PICKUP: 4, AVAIL: 5, FROM: 6, TO: 7, CF: 8,
  BALANCE: 9, LOCATION: 10, STATUS: 11, CARRIER: 12, DRIVER: 13, PHONE: 14, STORAGE: 15
};

var STATUSES = ['Pending', 'Ready', 'In Transit', 'Delivered'];
var WIDTHS = [80, 130, 130, 105, 120, 150, 150, 70, 100, 180, 110, 150, 140, 130, 100];

// Number of rows the system supports (row 1 = headers). Increase if you ever need more jobs.
var LAST_ROW = 2000;

var COLORS = {
  header: '#1F3864',
  band: '#F3F6FB',
  dupBg: '#F4CCCC',
  dupFont: '#990000'
};

// ------------------------------ MENU --------------------------------
function onOpen() {
  SpreadsheetApp.getUi().createMenu('Job Tracker')
    .addItem('Set up / rebuild system', 'setupJobTracker')
    .addSeparator()
    .addItem('Add 6 sample jobs (for testing)', 'addSampleJobs')
    .addItem('Remove sample jobs', 'removeSampleJobs')
    .addToUi();
}

// ------------------------------ SETUP -------------------------------
function setupJobTracker() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var ui = SpreadsheetApp.getUi();

  var answer = ui.alert(
    'Job Tracker setup',
    'This creates or refreshes the five Job Tracker tabs.\n' +
    'Any jobs you already typed into "All Jobs" are NOT deleted.\n\nContinue?',
    ui.ButtonSet.YES_NO);
  if (answer !== ui.Button.YES) return;

  var found = getAllJobsSheet_(ss);
  buildAllJobs_(found.sheet);

  var d = function (c) { return "'" + SHEET.ALL + "'!" + c + '2:' + c + LAST_ROW; };
  var base = [d('A') + '<>""'];

  buildView_(ss, SHEET.READY, '#1565C0',
    base.concat([d('E') + '<>""', d('E') + '<=TODAY()+30',
                 d('K') + '<>"In Transit"', d('K') + '<>"Delivered"']), 5, true);

  buildView_(ss, SHEET.LONG, '#6A1B9A',
    base.concat([d('E') + '<>""', d('E') + '>TODAY()+30',
                 d('K') + '<>"In Transit"', d('K') + '<>"Delivered"']), 5, true);

  buildView_(ss, SHEET.TRANSIT, '#EF6C00',
    base.concat([d('K') + '="In Transit"']), 1, true);

  buildView_(ss, SHEET.DELIVERED, '#2E7D32',
    base.concat([d('K') + '="Delivered"']), 1, true);

  // Put the tabs in order and land on All Jobs
  var order = [SHEET.ALL, SHEET.READY, SHEET.LONG, SHEET.TRANSIT, SHEET.DELIVERED];
  for (var i = 0; i < order.length; i++) {
    ss.setActiveSheet(ss.getSheetByName(order[i]));
    ss.moveActiveSheet(i + 1);
  }
  ss.setActiveSheet(ss.getSheetByName(SHEET.ALL));

  ui.alert('Done!',
    'Your Job Tracking System is ready.\n\n' + found.note +
    '\nType jobs ONLY in the "All Jobs" tab. The other four tabs fill in by themselves.',
    ui.ButtonSet.OK);
}

// Finds (or creates) the All Jobs tab without touching existing data.
function getAllJobsSheet_(ss) {
  var existing = ss.getSheetByName(SHEET.ALL);
  if (existing) return { sheet: existing, note: 'Existing "All Jobs" tab kept (data untouched).\n' };

  var first = ss.getSheets()[0];
  if (first.getLastRow() === 0 && first.getLastColumn() === 0) {
    first.setName(SHEET.ALL);
    return { sheet: first, note: 'Your empty first tab was renamed to "All Jobs".\n' };
  }
  return {
    sheet: ss.insertSheet(SHEET.ALL),
    note: 'Your original tab "' + first.getName() + '" has data, so it was left untouched.\n' +
          'A new "All Jobs" tab was created. Copy your old data into it if needed.\n'
  };
}

function buildAllJobs_(sheet) {
  styleSheet_(sheet, COLORS.header, true);
  var n = LAST_ROW - 1;

  // Duplicate Job # blocker (typing) - red highlight + onEdit backup handle pasting.
  sheet.getRange(2, COL.JOB, n, 1).setDataValidation(
    SpreadsheetApp.newDataValidation()
      .requireFormulaSatisfied('=COUNTIF($A$2:$A$' + LAST_ROW + ',A2)=1')
      .setAllowInvalid(false)
      .setHelpText('This Job # already exists. Each job can only be entered once.')
      .build());

  var dateRule = SpreadsheetApp.newDataValidation().requireDate()
    .setAllowInvalid(false).setHelpText('Enter a valid date, for example 03/15/2026.').build();
  sheet.getRange(2, COL.PICKUP, n, 2).setDataValidation(dateRule); // Pickup + 1st Available

  sheet.getRange(2, COL.STATUS, n, 1).setDataValidation(
    SpreadsheetApp.newDataValidation().requireValueInList(STATUSES, true)
      .setAllowInvalid(false).setHelpText('Choose: ' + STATUSES.join(', ')).build());

  var positive = SpreadsheetApp.newDataValidation().requireNumberGreaterThanOrEqualTo(0)
    .setAllowInvalid(false).setHelpText('Enter a number (0 or more).').build();
  sheet.getRange(2, COL.CF, n, 1).setDataValidation(positive);
  sheet.getRange(2, COL.STORAGE, n, 1).setDataValidation(positive);

  sheet.getRange(2, COL.BALANCE, n, 1).setDataValidation(
    SpreadsheetApp.newDataValidation().requireNumberBetween(-10000000, 10000000)
      .setAllowInvalid(false).setHelpText('Enter a number, for example 1250.50').build());

  // Filter buttons on the header row
  var f = sheet.getFilter();
  if (f) f.remove();
  sheet.getRange(1, 1, LAST_ROW, HEADERS.length).createFilter();
}

function buildView_(ss, name, tabColor, conditions, sortCol, ascending) {
  var sh = ss.getSheetByName(name) || ss.insertSheet(name);
  sh.getProtections(SpreadsheetApp.ProtectionType.SHEET).forEach(function (p) { p.remove(); });
  sh.clear();
  sh.setConditionalFormatRules([]);
  styleSheet_(sh, tabColor, false);

  var formula = '=IFERROR(SORT(FILTER(\'' + SHEET.ALL + '\'!A2:O' + LAST_ROW + ',' +
    conditions.join(',') + '),' + sortCol + ',' + (ascending ? 'TRUE' : 'FALSE') +
    '),"No jobs to show")';
  sh.getRange('A2').setFormula(formula);

  var p = sh.protect();
  p.setDescription('Automatic tab - edit jobs in "All Jobs" instead');
  p.setWarningOnly(true);
}

// Header, widths, number formats, colours (shared by all five tabs).
function styleSheet_(sheet, tabColor, isAllJobs) {
  if (sheet.getMaxRows() < LAST_ROW) {
    sheet.insertRowsAfter(sheet.getMaxRows(), LAST_ROW - sheet.getMaxRows());
  }
  var cols = HEADERS.length;
  var n = LAST_ROW - 1;
  sheet.setTabColor(tabColor);

  var head = sheet.getRange(1, 1, 1, cols);
  head.setValues([HEADERS])
    .setBackground(COLORS.header).setFontColor('#FFFFFF').setFontWeight('bold')
    .setFontFamily('Arial').setFontSize(10)
    .setHorizontalAlignment('center').setVerticalAlignment('middle').setWrap(true);
  sheet.setRowHeight(1, 36);
  sheet.setFrozenRows(1);
  sheet.setFrozenColumns(1);

  var body = sheet.getRange(2, 1, n, cols);
  body.setFontFamily('Arial').setFontSize(10).setVerticalAlignment('middle');

  sheet.getRange(2, COL.PICKUP, n, 2).setNumberFormat('MM/dd/yyyy').setHorizontalAlignment('center');
  sheet.getRange(2, COL.CF, n, 1).setNumberFormat('#,##0.##');
  sheet.getRange(2, COL.BALANCE, n, 1).setNumberFormat('$#,##0.00');
  sheet.getRange(2, COL.STORAGE, n, 1).setNumberFormat('$#,##0.00');
  sheet.getRange(2, COL.PHONE, n, 1).setNumberFormat('@');
  sheet.getRange(2, COL.STATUS, n, 1).setHorizontalAlignment('center');

  for (var i = 0; i < cols; i++) sheet.setColumnWidth(i + 1, WIDTHS[i]);

  // Conditional formatting (order = priority)
  var rules = [];
  if (isAllJobs) {
    rules.push(SpreadsheetApp.newConditionalFormatRule()
      .whenFormulaSatisfied('=AND($A2<>"",COUNTIF($A$2:$A$' + LAST_ROW + ',$A2)>1)')
      .setBackground(COLORS.dupBg).setFontColor(COLORS.dupFont).setBold(true)
      .setRanges([sheet.getRange(2, COL.JOB, n, 1)]).build());
  }
  var statusColors = {
    'Pending':    ['#FFF2CC', '#7F6000'],
    'Ready':      ['#DDEBF7', '#1F4E79'],
    'In Transit': ['#FCE4D6', '#C55A11'],
    'Delivered':  ['#E2F0D9', '#375623']
  };
  var statusRange = sheet.getRange(2, COL.STATUS, n, 1);
  Object.keys(statusColors).forEach(function (s) {
    rules.push(SpreadsheetApp.newConditionalFormatRule()
      .whenTextEqualTo(s)
      .setBackground(statusColors[s][0]).setFontColor(statusColors[s][1]).setBold(true)
      .setRanges([statusRange]).build());
  });
  rules.push(SpreadsheetApp.newConditionalFormatRule()
    .whenFormulaSatisfied('=AND($A2<>"",ISEVEN(ROW()))')
    .setBackground(COLORS.band).setRanges([body]).build());
  sheet.setConditionalFormatRules(rules);
}

// ------------------- DUPLICATE JOB # BACKUP + AUTO STATUS -------------------
// Runs automatically on every edit (no authorization needed).
function onEdit(e) {
  if (!e || !e.range) return;
  var sh = e.range.getSheet();
  if (sh.getName() !== SHEET.ALL) return;

  var r = e.range;
  if (r.getColumn() !== COL.JOB || r.getRow() < 2) return;
  if (r.getNumRows() !== 1 || r.getNumColumns() !== 1) return; // pastes: red highlight shows duplicates

  var val = String(r.getValue()).trim();
  if (val === '') return;
  if (val !== String(r.getValue())) r.setValue(val);

  var all = sh.getRange(2, COL.JOB, LAST_ROW - 1, 1).getValues();
  var count = 0;
  for (var i = 0; i < all.length; i++) {
    if (String(all[i][0]).trim().toLowerCase() === val.toLowerCase()) count++;
  }

  if (count > 1) {
    if (e.oldValue === undefined) r.clearContent(); else r.setValue(e.oldValue);
    SpreadsheetApp.getActive().toast('Job # "' + val + '" already exists. Entry cancelled.', 'Duplicate Job #', 8);
    return;
  }

  // New job with no status yet -> default to Pending
  var statusCell = sh.getRange(r.getRow(), COL.STATUS);
  if (statusCell.getValue() === '') statusCell.setValue('Pending');
}

// ----------------------------- TEST DATA -----------------------------
function addSampleJobs() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sh = ss.getSheetByName(SHEET.ALL);
  if (!sh) { SpreadsheetApp.getUi().alert('Run "Set up / rebuild system" first.'); return; }

  var day = function (offset) {
    var x = new Date(); x.setHours(0, 0, 0, 0); x.setDate(x.getDate() + offset); return x;
  };
  var rows = [
    ['TEST-1001', 'John',  'Smith',   day(-2), day(10), 'Dallas, TX',  'Miami, FL',   850,  1200.50, 'Warehouse A', 'Pending',    '', '', '', 0],
    ['TEST-1002', 'Maria', 'Garcia',  day(-5), day(25), 'Austin, TX',  'Denver, CO',  600,   900.00, 'Warehouse B', 'Ready',      '', '', '', 75],
    ['TEST-1003', 'David', 'Brown',   day(1),  day(45), 'Chicago, IL', 'Seattle, WA', 1100, 2100.00, 'Warehouse A', 'Pending',    '', '', '', 0],
    ['TEST-1004', 'Emma',  'Wilson',  day(3),  day(90), 'Boston, MA',  'Phoenix, AZ', 700,  1500.75, 'Warehouse C', 'Ready',      '', '', '', 120],
    ['TEST-1005', 'Liam',  'Johnson', day(-10), day(-3), 'Atlanta, GA', 'Orlando, FL', 500,   650.00, 'Atlanta Yard', 'In Transit', 'FastHaul LLC', 'Mike Roberts', '(555) 010-0101', 0],
    ['TEST-1006', 'Sofia', 'Martinez', day(-30), day(-20), 'Houston, TX', 'Tampa, FL', 900,     0.00, 'Houston Yard', 'Delivered',  'SafeMove Inc', 'Anna Lee', '(555) 010-0202', 0]
  ];

  var ids = sh.getRange(2, COL.JOB, LAST_ROW - 1, 1).getValues();
  var start = -1;
  for (var i = 0; i < ids.length; i++) {
    var empty = true;
    for (var k = 0; k < rows.length; k++) {
      if (String(ids[i + k] ? ids[i + k][0] : 'x') !== '') { empty = false; break; }
    }
    if (empty) { start = i + 2; break; }
  }
  if (start === -1) { SpreadsheetApp.getUi().alert('Not enough free rows.'); return; }

  var existing = ids.map(function (r) { return String(r[0]); });
  if (existing.indexOf('TEST-1001') !== -1) {
    SpreadsheetApp.getUi().alert('Sample jobs are already there. Use "Remove sample jobs" first.');
    return;
  }
  sh.getRange(start, 1, rows.length, HEADERS.length).setValues(rows);
}

function removeSampleJobs() {
  var sh = SpreadsheetApp.getActiveSpreadsheet().getSheetByName(SHEET.ALL);
  if (!sh) return;
  var ids = sh.getRange(2, COL.JOB, LAST_ROW - 1, 1).getValues();
  for (var i = 0; i < ids.length; i++) {
    if (String(ids[i][0]).indexOf('TEST-') === 0) {
      sh.getRange(i + 2, 1, 1, HEADERS.length).clearContent();
    }
  }
}
