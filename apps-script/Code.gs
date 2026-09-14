/**
 * Backend für die Helferplanung der Freiwilligen Feuerwehr Lorsbach.
 *
 * Einrichtung: siehe README.md im Projekt-Wurzelverzeichnis, Abschnitt
 * "Helferplanung einrichten". Dieses Skript wird als eigenständiges
 * Apps-Script-Projekt an ein Google Sheet gebunden und als Web-App
 * bereitgestellt (Zugriff: "Jeder").
 *
 * Datenmodell:
 *  - Tabellenblatt "Events":     eine Zeile je Helferplanung
 *  - Tabellenblatt "Responses":  eine Zeile je Zusage/Absage eines Helfers
 *
 * API (immer JSON, Form {ok:true,data:...} bzw. {ok:false,error:"..."}):
 *  GET  ?action=ping
 *  GET  ?action=getEvent&eventId=...
 *  POST {action:"createEvent", title, description, location, shifts:[...]}
 *  POST {action:"updateEvent", eventId, adminToken, title, description, location, shifts, active}
 *  POST {action:"deleteEvent", eventId, adminToken}
 *  POST {action:"submitResponse", eventId, name, contact, comment, answers:{shiftId:"yes|no|maybe"}, editToken?}
 *  POST {action:"deleteResponse", eventId, responseId, editToken?, adminToken?}
 *
 * POST-Requests werden vom Frontend absichtlich als text/plain gesendet
 * (statt application/json), damit der Browser keinen CORS-Preflight
 * (OPTIONS-Request) auslöst – Apps-Script-Web-Apps beantworten OPTIONS
 * nicht zuverlässig. Der Body ist trotzdem gültiges JSON und wird hier
 * manuell geparst.
 */

var SHEET_EVENTS = 'Events';
var SHEET_RESPONSES = 'Responses';
var EVENTS_HEADERS = ['eventId', 'adminToken', 'title', 'description', 'location', 'shifts', 'active', 'createdAt', 'updatedAt'];
var RESPONSES_HEADERS = ['responseId', 'eventId', 'editToken', 'name', 'contact', 'comment', 'answers', 'createdAt', 'updatedAt'];

function doGet(e) {
  try {
    var action = (e.parameter.action || 'getEvent');
    if (action === 'ping') return ok_({ pong: true });
    if (action === 'getEvent') {
      var eventId = e.parameter.eventId;
      if (!eventId) return fail_('eventId fehlt');
      var found = findEventRow_(eventId);
      if (!found) return fail_('Planung nicht gefunden');
      var responses = listResponses_(eventId).map(stripResponseSecrets_);
      return ok_({ event: stripEventSecrets_(found.record), responses: responses });
    }
    return fail_('Unbekannte Aktion: ' + action);
  } catch (err) {
    return fail_(err && err.message ? err.message : err);
  }
}

function doPost(e) {
  var lock = LockService.getScriptLock();
  lock.waitLock(10000);
  try {
    var body = JSON.parse((e.postData && e.postData.contents) || '{}');
    switch (body.action) {
      case 'createEvent': return ok_(createEvent_(body));
      case 'updateEvent': return ok_(updateEvent_(body));
      case 'deleteEvent': return ok_(deleteEvent_(body));
      case 'submitResponse': return ok_(submitResponse_(body));
      case 'deleteResponse': return ok_(deleteResponse_(body));
      default: return fail_('Unbekannte Aktion: ' + body.action);
    }
  } catch (err) {
    return fail_(err && err.message ? err.message : err);
  } finally {
    lock.releaseLock();
  }
}

/* ---------------- Aktionen ---------------- */

function createEvent_(body) {
  var title = String(body.title || '').trim();
  if (!title) throw new Error('Titel fehlt');
  var shifts = normalizeShifts_(body.shifts);
  if (!shifts.length) throw new Error('Mindestens eine Schicht wird benötigt');

  var now = nowIso_();
  var eventId = Utilities.getUuid();
  var adminToken = Utilities.getUuid();
  var record = {
    eventId: eventId,
    adminToken: adminToken,
    title: title,
    description: String(body.description || ''),
    location: String(body.location || ''),
    shifts: shifts,
    active: true,
    createdAt: now,
    updatedAt: now
  };
  appendRecord_(eventsSheet_(), EVENTS_HEADERS, record);
  return { eventId: eventId, adminToken: adminToken, event: stripEventSecrets_(record) };
}

function updateEvent_(body) {
  var found = requireEvent_(body.eventId);
  if (found.record.adminToken !== body.adminToken) throw new Error('Kein Zugriff (ungültiger Admin-Link)');

  var oldShiftsById = {};
  found.record.shifts.forEach(function (s) { oldShiftsById[s.id] = s; });
  var shifts = normalizeShifts_(body.shifts, oldShiftsById);
  if (!shifts.length) throw new Error('Mindestens eine Schicht wird benötigt');

  var updated = {
    title: String(body.title || found.record.title).trim(),
    description: body.description !== undefined ? String(body.description) : found.record.description,
    location: body.location !== undefined ? String(body.location) : found.record.location,
    shifts: shifts,
    active: body.active !== undefined ? !!body.active : found.record.active,
    updatedAt: nowIso_()
  };
  writeRecord_(eventsSheet_(), EVENTS_HEADERS, found.rowIndex, Object.assign({}, found.record, updated));
  return { event: stripEventSecrets_(Object.assign({}, found.record, updated)) };
}

function deleteEvent_(body) {
  var found = requireEvent_(body.eventId);
  if (found.record.adminToken !== body.adminToken) throw new Error('Kein Zugriff (ungültiger Admin-Link)');
  eventsSheet_().deleteRow(found.rowIndex);

  var sheet = responsesSheet_();
  var records = readAllRecords_(sheet, RESPONSES_HEADERS);
  for (var i = records.length - 1; i >= 0; i--) {
    if (records[i].record.eventId === body.eventId) sheet.deleteRow(records[i].rowIndex);
  }
  return { deleted: true };
}

function submitResponse_(body) {
  var found = requireEvent_(body.eventId);
  if (found.record.active === false) throw new Error('Diese Helferplanung ist geschlossen – es werden keine neuen Zusagen mehr angenommen.');
  var name = String(body.name || '').trim();
  if (!name) throw new Error('Name fehlt');

  var sheet = responsesSheet_();
  var existing = null;
  if (body.editToken) {
    var all = readAllRecords_(sheet, RESPONSES_HEADERS);
    for (var i = 0; i < all.length; i++) {
      if (all[i].record.eventId === body.eventId && all[i].record.editToken === body.editToken) {
        existing = all[i];
        break;
      }
    }
  }

  var now = nowIso_();
  var answers = (body.answers && typeof body.answers === 'object') ? body.answers : {};

  if (existing) {
    var updated = Object.assign({}, existing.record, {
      name: name,
      contact: String(body.contact || ''),
      comment: String(body.comment || ''),
      answers: answers,
      updatedAt: now
    });
    writeRecord_(sheet, RESPONSES_HEADERS, existing.rowIndex, updated);
    return { responseId: updated.responseId, editToken: updated.editToken };
  }

  var record = {
    responseId: Utilities.getUuid(),
    eventId: body.eventId,
    editToken: Utilities.getUuid(),
    name: name,
    contact: String(body.contact || ''),
    comment: String(body.comment || ''),
    answers: answers,
    createdAt: now,
    updatedAt: now
  };
  appendRecord_(sheet, RESPONSES_HEADERS, record);
  return { responseId: record.responseId, editToken: record.editToken };
}

function deleteResponse_(body) {
  var sheet = responsesSheet_();
  var all = readAllRecords_(sheet, RESPONSES_HEADERS);
  var target = null;
  for (var i = 0; i < all.length; i++) {
    if (all[i].record.responseId === body.responseId && all[i].record.eventId === body.eventId) {
      target = all[i];
      break;
    }
  }
  if (!target) return { deleted: true };

  var isOwner = body.editToken && target.record.editToken === body.editToken;
  var isAdmin = false;
  if (body.adminToken) {
    var ev = findEventRow_(body.eventId);
    isAdmin = !!ev && ev.record.adminToken === body.adminToken;
  }
  if (!isOwner && !isAdmin) throw new Error('Kein Zugriff');

  sheet.deleteRow(target.rowIndex);
  return { deleted: true };
}

/* ---------------- Hilfsfunktionen ---------------- */

function normalizeShifts_(rawShifts, oldShiftsById) {
  oldShiftsById = oldShiftsById || {};
  return (rawShifts || []).map(function (s) {
    var id = (s.id && oldShiftsById[s.id]) ? s.id : (s.id || Utilities.getUuid());
    return {
      id: id,
      title: String(s.title || '').trim(),
      date: String(s.date || ''),
      timeFrom: String(s.timeFrom || ''),
      timeTo: String(s.timeTo || ''),
      location: String(s.location || ''),
      neededHelpers: Math.max(1, parseInt(s.neededHelpers, 10) || 1),
      notes: String(s.notes || '')
    };
  }).filter(function (s) { return s.title; });
}

function requireEvent_(eventId) {
  var found = findEventRow_(eventId);
  if (!found) throw new Error('Planung nicht gefunden');
  return found;
}

function findEventRow_(eventId) {
  var all = readAllRecords_(eventsSheet_(), EVENTS_HEADERS);
  for (var i = 0; i < all.length; i++) {
    if (all[i].record.eventId === eventId) return all[i];
  }
  return null;
}

function listResponses_(eventId) {
  return readAllRecords_(responsesSheet_(), RESPONSES_HEADERS)
    .map(function (r) { return r.record; })
    .filter(function (r) { return r.eventId === eventId; });
}

function stripEventSecrets_(record) {
  var c = Object.assign({}, record);
  delete c.adminToken;
  return c;
}

function stripResponseSecrets_(record) {
  var c = Object.assign({}, record);
  delete c.editToken;
  return c;
}

function eventsSheet_() { return getOrCreateSheet_(SHEET_EVENTS, EVENTS_HEADERS); }
function responsesSheet_() { return getOrCreateSheet_(SHEET_RESPONSES, RESPONSES_HEADERS); }

function getOrCreateSheet_(name, headers) {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(name);
  if (!sheet) {
    sheet = ss.insertSheet(name);
    sheet.appendRow(headers);
    sheet.setFrozenRows(1);
  }
  return sheet;
}

/** Liest alle Datenzeilen eines Sheets und wandelt JSON-Spalten (shifts/answers) automatisch um. */
function readAllRecords_(sheet, headers) {
  var lastRow = sheet.getLastRow();
  if (lastRow < 2) return [];
  var values = sheet.getRange(2, 1, lastRow - 1, headers.length).getValues();
  var out = [];
  for (var i = 0; i < values.length; i++) {
    var row = values[i];
    if (!row[0]) continue; // leere Zeile überspringen
    var record = {};
    headers.forEach(function (h, idx) { record[h] = row[idx]; });
    if (typeof record.shifts === 'string') { try { record.shifts = JSON.parse(record.shifts); } catch (e) { record.shifts = []; } }
    if (typeof record.answers === 'string') { try { record.answers = JSON.parse(record.answers); } catch (e) { record.answers = {}; } }
    if (typeof record.active === 'boolean') { /* ok */ } else { record.active = record.active !== false && record.active !== 'FALSE'; }
    out.push({ rowIndex: i + 2, record: record });
  }
  return out;
}

function appendRecord_(sheet, headers, record) {
  sheet.appendRow(headers.map(function (h) { return serializeField_(record[h]); }));
}

function writeRecord_(sheet, headers, rowIndex, record) {
  var row = headers.map(function (h) { return serializeField_(record[h]); });
  sheet.getRange(rowIndex, 1, 1, headers.length).setValues([row]);
}

function serializeField_(value) {
  if (value && typeof value === 'object') return JSON.stringify(value);
  return value === undefined || value === null ? '' : value;
}

function nowIso_() { return new Date().toISOString(); }

function jsonOut_(obj) {
  return ContentService.createTextOutput(JSON.stringify(obj)).setMimeType(ContentService.MimeType.JSON);
}
function ok_(data) { return jsonOut_({ ok: true, data: data === undefined ? null : data }); }
function fail_(msg) { return jsonOut_({ ok: false, error: String(msg) }); }
