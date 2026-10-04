<?php
/**
 * API-Einstiegspunkt für die Helferplanung (helferplanung.html).
 *
 * Alle Anfragen (GET für Lesezugriffe, POST mit JSON-Body für Aktionen)
 * laufen über diese eine Datei, ähnlich einem einfachen REST-Dispatcher.
 * Da Frontend und API auf derselben Domain liegen, ist kein CORS nötig -
 * der Browser schickt den Session-Cookie automatisch mit.
 *
 * Verwaltungsrechte: EIN gemeinsames Vereins-Passwort (api/config.php)
 * schaltet per Login-Session alle Verwaltungsaktionen frei (Planung
 * erstellen/bearbeiten/löschen, beliebige Rückmeldung löschen). Eigene
 * Rückmeldungen können Helfer zusätzlich ohne Login über ihr persönliches
 * editToken ändern/löschen (wird beim ersten Absenden erzeugt und im
 * Browser gespeichert bzw. als persönlicher Link angezeigt).
 */

declare(strict_types=1);
require __DIR__ . '/common.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
  if ($method === 'GET') {
    $action = $_GET['action'] ?? 'getEvent';
    if ($action === 'session') {
      helfer_json_ok(['loggedIn' => helfer_is_admin()]);
    } elseif ($action === 'getEvent') {
      handle_get_event();
    } elseif ($action === 'listEvents') {
      handle_list_events();
    } else {
      helfer_fail('Unbekannte Aktion: ' . $action);
    }
  } elseif ($method === 'POST') {
    $body = helfer_read_json_body();
    $action = $body['action'] ?? '';
    switch ($action) {
      case 'login': handle_login($body); break;
      case 'logout': handle_logout(); break;
      case 'createEvent': handle_create_event($body); break;
      case 'updateEvent': handle_update_event($body); break;
      case 'deleteEvent': handle_delete_event($body); break;
      case 'submitResponse': handle_submit_response($body); break;
      case 'deleteResponse': handle_delete_response($body); break;
      default: helfer_fail('Unbekannte Aktion: ' . $action);
    }
  } else {
    helfer_fail('Methode nicht erlaubt', 405);
  }
} catch (Throwable $e) {
  helfer_fail('Serverfehler: ' . $e->getMessage(), 500);
}

/* ---------------- Handler ---------------- */

function handle_login(array $body): void {
  helfer_start_session();
  $cfg = helfer_config();
  $password = (string)($body['password'] ?? '');
  if ($password === '' || !hash_equals((string)$cfg['admin_password'], $password)) {
    helfer_fail('Falsches Passwort.', 401);
  }
  $_SESSION['helfer_admin'] = true;
  helfer_json_ok(['loggedIn' => true]);
}

function handle_logout(): void {
  helfer_start_session();
  unset($_SESSION['helfer_admin']);
  helfer_json_ok(['loggedIn' => false]);
}

function handle_get_event(): void {
  $eventId = (string)($_GET['id'] ?? '');
  if ($eventId === '') helfer_fail('id fehlt');
  $db = helfer_db();

  $stmt = $db->prepare('SELECT * FROM helfer_events WHERE id = ?');
  $stmt->execute([$eventId]);
  $eventRow = $stmt->fetch();
  if (!$eventRow) helfer_fail('Planung nicht gefunden', 404);

  $stmt = $db->prepare('SELECT * FROM helfer_responses WHERE event_id = ? ORDER BY created_at ASC');
  $stmt->execute([$eventId]);
  $responses = array_map('helfer_response_row_to_array', $stmt->fetchAll());

  helfer_json_ok(['event' => helfer_event_row_to_array($eventRow), 'responses' => $responses]);
}

function handle_list_events(): void {
  helfer_require_admin();
  $db = helfer_db();
  $stmt = $db->query('SELECT e.id, e.title, e.active, e.created_at,
      (SELECT COUNT(*) FROM helfer_responses r WHERE r.event_id = e.id) AS response_count
    FROM helfer_events e ORDER BY e.created_at DESC');
  $rows = $stmt->fetchAll();
  helfer_json_ok(array_map(function ($row) {
    return [
      'eventId' => $row['id'],
      'title' => $row['title'],
      'active' => ((int)$row['active']) === 1,
      'createdAt' => $row['created_at'],
      'responseCount' => (int)$row['response_count'],
    ];
  }, $rows));
}

function handle_create_event(array $body): void {
  helfer_require_admin();
  $title = trim((string)($body['title'] ?? ''));
  if ($title === '') helfer_fail('Titel fehlt');
  $shifts = helfer_normalize_shifts($body['shifts'] ?? []);
  if (!$shifts) helfer_fail('Mindestens eine Schicht wird benötigt');

  $db = helfer_db();
  $id = helfer_gen_id();
  $now = helfer_now();
  $stmt = $db->prepare('INSERT INTO helfer_events (id, title, description, location, shifts_json, active, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, 1, ?, ?)');
  $stmt->execute([
    $id, $title,
    (string)($body['description'] ?? ''),
    (string)($body['location'] ?? ''),
    json_encode($shifts, JSON_UNESCAPED_UNICODE),
    $now, $now,
  ]);

  helfer_json_ok(['eventId' => $id, 'event' => [
    'eventId' => $id, 'title' => $title,
    'description' => (string)($body['description'] ?? ''),
    'location' => (string)($body['location'] ?? ''),
    'shifts' => $shifts, 'active' => true, 'createdAt' => $now, 'updatedAt' => $now,
  ]]);
}

function handle_update_event(array $body): void {
  helfer_require_admin();
  $eventId = (string)($body['eventId'] ?? '');
  if ($eventId === '') helfer_fail('eventId fehlt');
  $title = trim((string)($body['title'] ?? ''));
  if ($title === '') helfer_fail('Titel fehlt');
  $shifts = helfer_normalize_shifts($body['shifts'] ?? []);
  if (!$shifts) helfer_fail('Mindestens eine Schicht wird benötigt');

  $db = helfer_db();
  $stmt = $db->prepare('SELECT id, active FROM helfer_events WHERE id = ?');
  $stmt->execute([$eventId]);
  $existing = $stmt->fetch();
  if (!$existing) helfer_fail('Planung nicht gefunden', 404);

  $active = array_key_exists('active', $body) ? (bool)$body['active'] : ((int)$existing['active'] === 1);

  $stmt = $db->prepare('UPDATE helfer_events SET title=?, description=?, location=?, shifts_json=?, active=?, updated_at=? WHERE id=?');
  $stmt->execute([
    $title,
    (string)($body['description'] ?? ''),
    (string)($body['location'] ?? ''),
    json_encode($shifts, JSON_UNESCAPED_UNICODE),
    $active ? 1 : 0,
    helfer_now(),
    $eventId,
  ]);

  helfer_json_ok(['ok' => true]);
}

function handle_delete_event(array $body): void {
  helfer_require_admin();
  $eventId = (string)($body['eventId'] ?? '');
  if ($eventId === '') helfer_fail('eventId fehlt');
  $db = helfer_db();
  // ON DELETE CASCADE auf helfer_responses.event_id räumt die Rückmeldungen mit ab.
  $stmt = $db->prepare('DELETE FROM helfer_events WHERE id = ?');
  $stmt->execute([$eventId]);
  helfer_json_ok(['deleted' => true]);
}

function handle_submit_response(array $body): void {
  $eventId = (string)($body['eventId'] ?? '');
  if ($eventId === '') helfer_fail('eventId fehlt');
  $name = trim((string)($body['name'] ?? ''));
  if ($name === '') helfer_fail('Name fehlt');

  $db = helfer_db();
  $stmt = $db->prepare('SELECT active FROM helfer_events WHERE id = ?');
  $stmt->execute([$eventId]);
  $event = $stmt->fetch();
  if (!$event) helfer_fail('Planung nicht gefunden', 404);
  if ((int)$event['active'] !== 1) helfer_fail('Diese Helferplanung ist geschlossen – es werden keine neuen Zusagen mehr angenommen.');

  $contact = (string)($body['contact'] ?? '');
  $comment = (string)($body['comment'] ?? '');
  $answers = is_array($body['answers'] ?? null) ? $body['answers'] : [];
  $answersJson = json_encode($answers, JSON_UNESCAPED_UNICODE);
  $now = helfer_now();

  $editToken = (string)($body['editToken'] ?? '');
  $existing = null;
  if ($editToken !== '') {
    $stmt = $db->prepare('SELECT id FROM helfer_responses WHERE event_id = ? AND edit_token = ?');
    $stmt->execute([$eventId, $editToken]);
    $existing = $stmt->fetch();
  }

  if ($existing) {
    $stmt = $db->prepare('UPDATE helfer_responses SET name=?, contact=?, comment=?, answers_json=?, updated_at=? WHERE id=?');
    $stmt->execute([$name, $contact, $comment, $answersJson, $now, $existing['id']]);
    helfer_json_ok(['responseId' => $existing['id'], 'editToken' => $editToken]);
  } else {
    $id = helfer_gen_id();
    $newToken = helfer_gen_token();
    $stmt = $db->prepare('INSERT INTO helfer_responses (id, event_id, edit_token, name, contact, comment, answers_json, created_at, updated_at)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$id, $eventId, $newToken, $name, $contact, $comment, $answersJson, $now, $now]);
    helfer_json_ok(['responseId' => $id, 'editToken' => $newToken]);
  }
}

function handle_delete_response(array $body): void {
  $eventId = (string)($body['eventId'] ?? '');
  $responseId = (string)($body['responseId'] ?? '');
  if ($eventId === '' || $responseId === '') helfer_fail('eventId/responseId fehlt');

  $db = helfer_db();
  $stmt = $db->prepare('SELECT edit_token FROM helfer_responses WHERE id = ? AND event_id = ?');
  $stmt->execute([$responseId, $eventId]);
  $row = $stmt->fetch();
  if (!$row) helfer_json_ok(['deleted' => true]); // bereits weg

  $editToken = (string)($body['editToken'] ?? '');
  $isOwner = $editToken !== '' && hash_equals($row['edit_token'], $editToken);
  if (!$isOwner && !helfer_is_admin()) {
    helfer_fail('Kein Zugriff', 403);
  }

  $stmt = $db->prepare('DELETE FROM helfer_responses WHERE id = ? AND event_id = ?');
  $stmt->execute([$responseId, $eventId]);
  helfer_json_ok(['deleted' => true]);
}
