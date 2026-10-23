/**
 * JOB TRACKING SYSTEM - Google Apps Script (v2, safety-reviewed)
 *
 * Tabs:
 *   1. All Jobs                 - the ONLY place you type data
 *   2. Jobs Ready for Delivery  - 1st Available Date within the next 30 days (and any past date)
 *   3. Jobs More Than 30 Days   - 1st Available Date more than 30 days away
 *   4. Jobs in Transit          - Status = In Transit
 *   5. Jobs Delivered           - Status = Delivered
 *
 * SAFETY RULES THIS SCRIPT FOLLOWS
 *   - It never deletes or overwrites job rows in "All Jobs".
 *   - It never clears a tab it did not create. If a tab with one of the five names
 *     already contains data that is not ours, setup STOPS before changing anything.
 *   - It only replaces its own conditional-formatting rules, never yours.
 *   - It is safe to run again at any time.
 *
 * HOW TO USE: run setupJobTracker() (or use the "Job Tracker" menu).
 *
 * @OnlyCurrentDoc
 */

// ------------------------------ SETTINGS ------------------------------
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

// Statuses that are EXCLUDED from the two date-based tabs.
// Pending and Ready jobs (and jobs with a blank status) ARE shown there.
var EXCLUDED_FROM_DATE_TABS = ['In Transit', 'Delivered'];

// "Ready for Delivery" = first available date is today or earlier, up to this many days ahead.
var DAYS_WINDOW = 30;

// Last row the system reads (row 1 = headers). 2000 = up to 1,999 jobs.
// To support more jobs, raise this number and run "Set up / rebuild system" again.
var LAST_ROW = 2000;

// Currency symbol shown before Balance and Storage Fee. '' = plain numbers (no symbol).
// You are asked for this during setup; it is saved in the spreadsheet.
// Change it any time from the menu: Job Tracker > Change currency format.
var DEFAULT_CURRENCY_PREFIX = '';

var WIDTHS = [80, 130, 130, 105, 120, 150, 150, 70, 100, 180, 110, 150, 140, 130, 100];
var COLORS = { header: '#1F3864', band: '#F3F6FB', dupBg: '#F4CCCC', dupFont: '#990000' };

var PROTECTION_NOTE = 'Automatic tab - edit jobs in "All Jobs" instead';
var VIEW_MARKER = 'jobTrackerView';
var CURRENCY_PROP = 'CURRENCY_PREFIX';

// Old (v1) rule formulas, so re-running removes them cleanly.
var LEGACY_RULE_FORMULAS = ['=AND($A2<>"",COUNTIF($A$2:$A$2000,$A2)>1)'];

// -------------------------------- MENU --------------------------------
function onOpen() {
  SpreadsheetApp.getUi().createMenu('Job Tracker')
    .addItem('Set up / rebuild system', 'setupJobTracker')
    .addItem('Change currency format', 'changeCurrency')
    .addSeparator()
    .addItem('Add 6 sample jobs (for testing)', 'addSampleJobs')
    .addItem('Remove sample jobs (TEST- only)', 'removeSampleJobs')
    .addToUi();
}

// ------------------------------- SETUP --------------------------------
function setupJobTracker() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var ui = SpreadsheetApp.getUi();

  // 1. Check everything BEFORE changing anything.
  var problems = preflight_(ss);
  if (problems.length) {
    ui.alert('Setup stopped - nothing was changed',
      problems.join('\n\n') + '\n\nFix the above (or run on a blank copy of the sheet) and run setup again.',
      ui.ButtonSet.OK);
    return;
  }

  var answer = ui.alert('Job Tracker setup',
    'This will:\n' +
    ' - create or refresh the five Job Tracker tabs,\n' +
    ' - add dropdowns, date/duplicate checks, filter buttons and formatting to "All Jobs".\n\n' +
    'It will NOT delete or change any job data you already entered.\n\nContinue?',
    ui.ButtonSet.YES_NO);
  if (answer !== ui.Button.YES) return;

  // 2. Currency (asked once, remembered in this spreadsheet).
  if (getCurrencyPrefix_() === null) promptCurrency_(ui);

  // 3. Build.
  var found = getAllJobsSheet_(ss);
  buildAllJobs_(found.sheet);

  var d = function (c) { return "'" + SHEET.ALL + "'!" + c + '2:' + c + LAST_ROW; };
  var base = [d('A') + '<>""'];
  var notExcluded = EXCLUDED_FROM_DATE_TABS.map(function (s) { return d('K') + '<>"' + s + '"'; });
  var cutoff = 'TODAY()+' + (DAYS_WINDOW + 1); // "< cutoff" = through the end of day N, even if a time is stored

  buildView_(ss, SHEET.READY, '#1565C0',
    base.concat(['ISNUMBER(' + d('E') + ')', d('E') + '<' + cutoff]).concat(notExcluded), 5, true);
  buildView_(ss, SHEET.LONG, '#6A1B9A',
    base.concat(['ISNUMBER(' + d('E') + ')', d('E') + '>=' + cutoff]).concat(notExcluded), 5, true);
  buildView_(ss, SHEET.TRANSIT, '#EF6C00', base.concat([d('K') + '="In Transit"']), 1, true);
  buildView_(ss, SHEET.DELIVERED, '#2E7D32', base.concat([d('K') + '="Delivered"']), 1, true);

  // Order the five tabs first (other tabs keep their relative order after them).
  var order = [SHEET.ALL, SHEET.READY, SHEET.LONG, SHEET.TRANSIT, SHEET.DELIVERED];
  for (var i = 0; i < order.length; i++) {
    ss.setActiveSheet(ss.getSheetByName(order[i]));
    ss.moveActiveSheet(i + 1);
  }
  ss.setActiveSheet(ss.getSheetByName(SHEET.ALL));

  // 4. Report on data that was already there.
  var warnings = checkExistingData_(found.sheet);
  ui.alert('Done!',
    'Your Job Tracking System is ready.\n\n' + found.note +
    '\nType jobs ONLY in "All Jobs". The other four tabs fill in by themselves.' +
    (warnings.length ? '\n\nPlease review your existing data:\n- ' + warnings.join('\n- ') : ''),
    ui.ButtonSet.OK);
}

// ---------------------------- PRE-FLIGHT -------------------------------
// Returns a list of problems. If non-empty, setup changes nothing.
function preflight_(ss) {
  var problems = [];
  var all = ss.getSheetByName(SHEET.ALL);

  if (all) {
    var lastCol = Math.max(all.getLastColumn(), 1);
    var row1 = all.getRange(1, 1, 1, lastCol).getValues()[0];
    var row1Empty = row1.every(function (v) { return String(v).trim() === ''; });

    if (row1Empty && all.getLastRow() > 1) {
      problems.push('"' + SHEET.ALL + '" has data but no header row in row 1.');
    }
    if (!row1Empty) {
      for (var i = 0; i < HEADERS.length; i++) {
        var have = i < row1.length ? String(row1[i]).trim().toLowerCase() : '';
        if (have !== HEADERS[i].toLowerCase()) {
          problems.push('"' + SHEET.ALL + '" row 1 does not match the required columns.\n' +
            'Column ' + (i + 1) + ' should be "' + HEADERS[i] + '" but is "' + (have === '' ? '(empty)' : row1[i]) + '".\n' +
            'Rename/move the columns to this order: ' + HEADERS.join(', ') + '.');
          break;
        }
      }
    }
    if (all.getLastRow() > LAST_ROW) {
      problems.push('"' + SHEET.ALL + '" has ' + (all.getLastRow() - 1) + ' rows, but the system reads only up to row ' +
        LAST_ROW + '. Increase LAST_ROW at the top of the script first.');
    }
  }

  [SHEET.READY, SHEET.LONG, SHEET.TRANSIT, SHEET.DELIVERED].forEach(function (name) {
    var sh = ss.getSheetByName(name);
    if (sh && hasContent_(sh) && !isOurView_(sh)) {
      problems.push('A tab named "' + name + '" already exists and contains data that this script did not create. ' +
        'Rename it (or move its data) so it is not overwritten.');
    }
  });
  return problems;
}

function hasContent_(sh) { return sh.getLastRow() > 0 && sh.getLastColumn() > 0; }

function isOurView_(sh) {
  if (sh.createDeveloperMetadataFinder().withKey(VIEW_MARKER).find().length > 0) return true;
  // Tabs created by the first version of this script (no marker): recognise by their formula.
  var f = sh.getRange('A2').getFormula();
  return f.indexOf("'" + SHEET.ALL + "'!A2:O") !== -1;
}

// ------------------------- ALL JOBS TAB --------------------------------
function getAllJobsSheet_(ss) {
  var existing = ss.getSheetByName(SHEET.ALL);
  if (existing) return { sheet: existing, note: 'Existing "All Jobs" tab kept - your data was not touched.\n' };

  var first = ss.getSheets()[0];
  if (!hasContent_(first)) {
    first.setName(SHEET.ALL);
    return { sheet: first, note: 'Your empty first tab was renamed to "All Jobs".\n' };
  }
  return {
    sheet: ss.insertSheet(SHEET.ALL),
    note: 'Your original tab "' + first.getName() + '" contains data, so it was left untouched.\n' +
          'A new "All Jobs" tab was created. Copy your jobs into it (same column order).\n'
  };
}

function buildAllJobs_(sheet) {
  styleSheet_(sheet, COLORS.header, true);
  var n = LAST_ROW - 1;

  // Duplicate Job # blocker. TRIM + "=" is an exact, case-insensitive text match
  // (COUNTIF would treat * ? < > = in a Job # as wildcards/operators).
  sheet.getRange(2, COL.JOB, n, 1).setDataValidation(
    SpreadsheetApp.newDataValidation()
      .requireFormulaSatisfied('=AND(TRIM(A2)<>"",SUMPRODUCT(--(TRIM($A$2:$A$' + LAST_ROW + ')=TRIM(A2)))=1)')
      .setAllowInvalid(false)
      .setHelpText('Job # is required and must be unique - this Job # is blank or already exists.')
      .build());

  var dateRule = SpreadsheetApp.newDataValidation().requireDate()
    .setAllowInvalid(false).setHelpText('Enter a valid date, for example 03/15/2026.').build();
  sheet.getRange(2, COL.PICKUP, n, 2).setDataValidation(dateRule);

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

  // Only add filter buttons if the sheet has no filter already (never replace yours).
  if (!sheet.getFilter()) sheet.getRange(1, 1, LAST_ROW, HEADERS.length).createFilter();
}

// --------------------------- AUTOMATIC TABS ----------------------------
function buildView_(ss, name, tabColor, conditions, sortCol, ascending) {
  var sh = ss.getSheetByName(name);
  if (!sh) sh = ss.insertSheet(name);
  if (sh.createDeveloperMetadataFinder().withKey(VIEW_MARKER).find().length === 0) {
    sh.addDeveloperMetadata(VIEW_MARKER);
  }

  // Remove only OUR protection (never anyone else's), then re-add it.
  sh.getProtections(SpreadsheetApp.ProtectionType.SHEET).forEach(function (p) {
    if (p.getDescription() === PROTECTION_NOTE) p.remove();
  });

  styleSheet_(sh, tabColor, false);

  // IFNA: only "no matching jobs" shows the friendly message. Real problems
  // (e.g. something blocking the result area) stay visible as an error.
  var formula = '=IFNA(SORT(FILTER(\'' + SHEET.ALL + '\'!A2:O' + LAST_ROW + ',' +
    conditions.join(',') + '),' + sortCol + ',' + (ascending ? 'TRUE' : 'FALSE') +
    '),"No jobs to show")';
  sh.getRange('A2').setFormula(formula);

  var p = sh.protect();
  p.setDescription(PROTECTION_NOTE);
  p.setWarningOnly(true);
}

// ------------------------------ STYLING --------------------------------
function currencyFormat_() {
  var p = getCurrencyPrefix_();
  if (p === null) p = DEFAULT_CURRENCY_PREFIX;
  p = String(p).replace(/["\\]/g, '');
  return p === '' ? '#,##0.00' : '"' + p + '"#,##0.00';
}

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
  sheet.getRange(2, COL.BALANCE, n, 1).setNumberFormat(currencyFormat_());
  sheet.getRange(2, COL.STORAGE, n, 1).setNumberFormat(currencyFormat_());
  sheet.getRange(2, COL.PHONE, n, 1).setNumberFormat('@');
  sheet.getRange(2, COL.STATUS, n, 1).setHorizontalAlignment('center');

  for (var i = 0; i < cols; i++) sheet.setColumnWidth(i + 1, WIDTHS[i]);

  var rules = [];
  if (isAllJobs) {
    rules.push(SpreadsheetApp.newConditionalFormatRule()
      .whenFormulaSatisfied('=AND(TRIM($A2)<>"",SUMPRODUCT(--(TRIM($A$2:$A$' + LAST_ROW + ')=TRIM($A2)))>1)')
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
  STATUSES.forEach(function (s) {
    rules.push(SpreadsheetApp.newConditionalFormatRule()
      .whenTextEqualTo(s)
      .setBackground(statusColors[s][0]).setFontColor(statusColors[s][1]).setBold(true)
      .setRanges([statusRange]).build());
  });
  rules.push(SpreadsheetApp.newConditionalFormatRule()
    .whenFormulaSatisfied('=AND($A2<>"",ISEVEN(ROW()))')
    .setBackground(COLORS.band).setRanges([body]).build());

  mergeRules_(sheet, rules);
}

// Replaces only the rules this script owns; any rule you added yourself is kept.
function mergeRules_(sheet, ourRules) {
  var ours = {};
  ourRules.forEach(function (r) { ours[ruleSignature_(r)] = true; });
  var kept = sheet.getConditionalFormatRules().filter(function (r) {
    var sig = ruleSignature_(r);
    if (sig === null) return true;
    if (ours[sig]) return false;
    var c = r.getBooleanCondition();
    return !(c && LEGACY_RULE_FORMULAS.indexOf(String(c.getCriteriaValues()[0])) !== -1);
  });
  sheet.setConditionalFormatRules(ourRules.concat(kept));
}

function ruleSignature_(rule) {
  var c = rule.getBooleanCondition();
  var ranges = rule.getRanges();
  if (!c || ranges.length !== 1) return null;
  return String(c.getCriteriaType()) + '|' + c.getCriteriaValues().join('|') + '|' + ranges[0].getA1Notation();
}

// ------------------------------ CURRENCY --------------------------------
function getCurrencyPrefix_() {
  return PropertiesService.getDocumentProperties().getProperty(CURRENCY_PROP);
}

// Returns true if the user entered a value (even an empty one), false if they cancelled.
function promptCurrency_(ui) {
  var resp = ui.prompt('Currency format',
    'Type the currency symbol or code to show before Balance and Storage Fee amounts.\n' +
    'Examples:  $   €   £   ৳   AED   (for a space after the code, type e.g. "AED " with a space)\n' +
    'Leave empty to show plain numbers with no symbol.',
    ui.ButtonSet.OK_CANCEL);
  if (resp.getSelectedButton() !== ui.Button.OK) return false;
  PropertiesService.getDocumentProperties().setProperty(CURRENCY_PROP, resp.getResponseText().replace(/["\\]/g, ''));
  return true;
}

function changeCurrency() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  if (!promptCurrency_(SpreadsheetApp.getUi())) return;
  var fmt = currencyFormat_();
  var n = LAST_ROW - 1;
  Object.keys(SHEET).forEach(function (k) {
    var sh = ss.getSheetByName(SHEET[k]);
    if (!sh) return;
    sh.getRange(2, COL.BALANCE, n, 1).setNumberFormat(fmt);
    sh.getRange(2, COL.STORAGE, n, 1).setNumberFormat(fmt);
  });
  ss.toast('Currency format updated.', 'Job Tracker', 5);
}

// ---------------------- REPORT ON EXISTING DATA -------------------------
// Read-only. Tells you about rows that the automatic tabs cannot place.
function checkExistingData_(sheet) {
  var last = sheet.getLastRow();
  if (last < 2) return [];
  var data = sheet.getRange(2, 1, last - 1, HEADERS.length).getValues();
  var validStatus = STATUSES.map(function (s) { return s.toLowerCase(); });
  var counts = {};
  data.forEach(function (r) { var k = normalizeId_(r[COL.JOB - 1]); if (k) counts[k] = (counts[k] || 0) + 1; });

  var noId = 0, dup = 0, badStatus = 0, badDate = 0, noDate = 0;
  data.forEach(function (r) {
    var id = normalizeId_(r[COL.JOB - 1]);
    var any = r.some(function (v) { return String(v).trim() !== ''; });
    if (!id) { if (any) noId++; return; }
    if (counts[id] > 1) dup++;
    var status = String(r[COL.STATUS - 1]);
    if (status !== '' && validStatus.indexOf(status.toLowerCase()) === -1) badStatus++;
    var avail = r[COL.AVAIL - 1];
    if (typeof avail === 'string' && avail.trim() !== '') badDate++;
    var excluded = EXCLUDED_FROM_DATE_TABS.map(function (s) { return s.toLowerCase(); });
    if (avail === '' && excluded.indexOf(status.toLowerCase()) === -1) noDate++;
  });

  var w = [];
  if (noId) w.push(noId + ' row(s) have no Job # - they appear in NO tab.');
  if (dup) w.push(dup + ' row(s) share a Job # with another row (highlighted red in "All Jobs"). Fix them.');
  if (badStatus) w.push(badStatus + ' row(s) have a Status that is not one of: ' + STATUSES.join(', ') + '.');
  if (badDate) w.push(badDate + ' row(s) have a 1st Available Date stored as text, not a real date - they appear in neither date tab.');
  if (noDate) w.push(noDate + ' Pending/Ready row(s) have no 1st Available Date - they appear in neither date tab.');
  return w;
}

function normalizeId_(v) { return String(v).trim().toLowerCase(); }

// ---------------- DUPLICATE JOB # BACKUP + DEFAULT STATUS ----------------
// Runs automatically on every edit (a "simple trigger": needs no authorization).
// It must never block typing, so every error is swallowed.
function onEdit(e) {
  try { handleEdit_(e); } catch (err) { /* ignore */ }
}

function handleEdit_(e) {
  if (!e || !e.range) return;
  var range = e.range;
  var sh = range.getSheet();
  if (sh.getName() !== SHEET.ALL) return;
  if (range.getColumn() > COL.JOB || range.getLastColumn() < COL.JOB) return; // Job # column not touched

  var firstRow = Math.max(2, range.getRow());
  var lastRow = Math.min(range.getLastRow(), LAST_ROW);
  if (lastRow < firstRow) return;
  var single = range.getNumRows() === 1 && range.getNumColumns() === 1;

  var ids = sh.getRange(2, COL.JOB, LAST_ROW - 1, 1).getValues();
  var counts = {};
  ids.forEach(function (r) { var k = normalizeId_(r[0]); if (k) counts[k] = (counts[k] || 0) + 1; });

  if (single) {
    var cell = sh.getRange(firstRow, COL.JOB);
    var raw = ids[firstRow - 2][0];
    var key = normalizeId_(raw);
    if (key === '') return;
    if (typeof raw === 'string' && raw !== raw.trim()) cell.setValue(raw.trim());
    if (counts[key] > 1) {
      if (e.oldValue === undefined) cell.clearContent(); else cell.setValue(e.oldValue);
      SpreadsheetApp.getActive().toast('Job # "' + raw.toString().trim() + '" already exists. Entry cancelled.', 'Duplicate Job #', 8);
      return;
    }
  } else {
    var dups = 0;
    for (var r = firstRow; r <= lastRow; r++) {
      var k2 = normalizeId_(ids[r - 2][0]);
      if (k2 && counts[k2] > 1) dups++;
    }
    if (dups) {
      SpreadsheetApp.getActive().toast(dups + ' pasted/changed row(s) have a duplicate Job # - they are highlighted red. Please fix them.', 'Duplicate Job #', 10);
    }
  }

  // New job with no status yet -> Pending (only fills blanks; never changes a status).
  var n = lastRow - firstRow + 1;
  var statusRange = sh.getRange(firstRow, COL.STATUS, n, 1);
  var statuses = statusRange.getValues();
  var changed = false;
  for (var i = 0; i < n; i++) {
    var id = normalizeId_(ids[firstRow - 2 + i][0]);
    if (id !== '' && statuses[i][0] === '') { statuses[i][0] = 'Pending'; changed = true; }
  }
  if (changed) statusRange.setValues(statuses);
}

// ------------------------------ TEST DATA --------------------------------
function addSampleJobs() {
  var ui = SpreadsheetApp.getUi();
  var sh = SpreadsheetApp.getActiveSpreadsheet().getSheetByName(SHEET.ALL);
  if (!sh) { ui.alert('Run "Set up / rebuild system" first.'); return; }

  var day = function (offset) {
    var x = new Date(); x.setHours(0, 0, 0, 0); x.setDate(x.getDate() + offset); return x;
  };
  var rows = [
    ['TEST-1001', 'John',  'Smith',    day(-2),  day(10),  'Dallas, TX',  'Miami, FL',   850,  1200.50, 'Warehouse A',  'Pending',    '', '', '', 0],
    ['TEST-1002', 'Maria', 'Garcia',   day(-5),  day(25),  'Austin, TX',  'Denver, CO',  600,   900.00, 'Warehouse B',  'Ready',      '', '', '', 75],
    ['TEST-1003', 'David', 'Brown',    day(1),   day(45),  'Chicago, IL', 'Seattle, WA', 1100, 2100.00, 'Warehouse A',  'Pending',    '', '', '', 0],
    ['TEST-1004', 'Emma',  'Wilson',   day(3),   day(90),  'Boston, MA',  'Phoenix, AZ', 700,  1500.75, 'Warehouse C',  'Ready',      '', '', '', 120],
    ['TEST-1005', 'Liam',  'Johnson',  day(-10), day(-3),  'Atlanta, GA', 'Orlando, FL', 500,   650.00, 'Atlanta Yard', 'In Transit', 'FastHaul LLC', 'Mike Roberts', '(555) 010-0101', 0],
    ['TEST-1006', 'Sofia', 'Martinez', day(-30), day(-20), 'Houston, TX', 'Tampa, FL',   900,     0.00, 'Houston Yard', 'Delivered',  'SafeMove Inc', 'Anna Lee',     '(555) 010-0202', 0]
  ];

  var ids = sh.getRange(2, COL.JOB, LAST_ROW - 1, 1).getValues();
  var lastUsed = -1;
  for (var i = 0; i < ids.length; i++) {
    if (normalizeId_(ids[i][0]) === 'test-1001') { ui.alert('Sample jobs are already there. Remove them first.'); return; }
    if (String(ids[i][0]).trim() !== '') lastUsed = i;
  }
  var start = lastUsed + 3; // first empty row after the last used row
  if (start + rows.length - 1 > LAST_ROW) { ui.alert('Not enough free rows below your data.'); return; }
  sh.getRange(start, 1, rows.length, HEADERS.length).setValues(rows);
}

// Clears (does not delete) only rows whose Job # starts with TEST-, after asking.
function removeSampleJobs() {
  var ui = SpreadsheetApp.getUi();
  var sh = SpreadsheetApp.getActiveSpreadsheet().getSheetByName(SHEET.ALL);
  if (!sh) return;
  var ids = sh.getRange(2, COL.JOB, LAST_ROW - 1, 1).getValues();
  var targets = [];
  for (var i = 0; i < ids.length; i++) {
    if (String(ids[i][0]).trim().toUpperCase().indexOf('TEST-') === 0) targets.push(i + 2);
  }
  if (!targets.length) { ui.alert('No TEST- jobs found.'); return; }
  var ok = ui.alert('Remove sample jobs',
    targets.length + ' row(s) with a Job # starting with "TEST-" will be cleared. Real jobs are not affected.\n\nContinue?',
    ui.ButtonSet.YES_NO);
  if (ok !== ui.Button.YES) return;
  targets.forEach(function (row) { sh.getRange(row, 1, 1, HEADERS.length).clearContent(); });
}
