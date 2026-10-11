<?php
/**
 * Gemeinsame Hilfsfunktionen für die Helferplanungs-API.
 * Wird von index.php eingebunden, nicht direkt aufgerufen.
 */

declare(strict_types=1);

function helfer_config(): array {
  static $config = null;
  if ($config === null) {
    // HELFER_CONFIG_PATH erlaubt automatisierte Tests mit einer eigenen
    // Konfiguration, ohne die normale config.php anzufassen. Im normalen
    // Betrieb ist diese Umgebungsvariable nicht gesetzt.
    $path = getenv('HELFER_CONFIG_PATH') ?: (__DIR__ . '/config.php');
    if (!file_exists($path)) {
      helfer_fail('Server ist noch nicht eingerichtet (api/config.php fehlt). Siehe README.', 500);
    }
    $config = require $path;
  }
  return $config;
}

function helfer_db(): PDO {
  static $pdo = null;
  if ($pdo === null) {
    $cfg = helfer_config();
    $dsn = 'mysql:host=' . $cfg['db_host'] . ';dbname=' . $cfg['db_name'] . ';charset=utf8mb4';
    try {
      $pdo = new PDO($dsn, $cfg['db_user'], $cfg['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
      ]);
    } catch (PDOException $e) {
      helfer_fail('Datenbankverbindung fehlgeschlagen. Zugangsdaten in api/config.php prüfen.', 500);
    }
  }
  return $pdo;
}

function helfer_start_session(): void {
  if (session_status() === PHP_SESSION_ACTIVE) return;
  session_set_cookie_params([
    'lifetime' => 60 * 60 * 24 * 30,
    'path' => '/',
    'secure' => !empty($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
  session_start();
}

function helfer_is_admin(): bool {
  helfer_start_session();
  return !empty($_SESSION['helfer_admin']);
}

function helfer_require_admin(): void {
  if (!helfer_is_admin()) {
    helfer_fail('Kein Admin-Zugriff. Bitte zuerst anmelden.', 401);
  }
}

function helfer_json_ok($data = null): void {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
  exit;
}

function helfer_fail(string $message, int $httpCode = 400): void {
  http_response_code($httpCode);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
  exit;
}

function helfer_read_json_body(): array {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw ?: '', true);
  return is_array($data) ? $data : [];
}

function helfer_gen_id(): string {
  return bin2hex(random_bytes(16));
}

function helfer_gen_token(): string {
  return bin2hex(random_bytes(24));
}

function helfer_now(): string {
  return (new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
}

/** Normalisiert/validiert die vom Client gesendeten Schichten. */
function helfer_normalize_shifts($rawShifts): array {
  if (!is_array($rawShifts)) return [];
  $out = [];
  foreach ($rawShifts as $s) {
    if (!is_array($s)) continue;
    $title = trim((string)($s['title'] ?? ''));
    if ($title === '') continue;
    $out[] = [
      'id' => (isset($s['id']) && is_string($s['id']) && $s['id'] !== '') ? $s['id'] : helfer_gen_id(),
      'title' => $title,
      'date' => (string)($s['date'] ?? ''),
      'timeFrom' => (string)($s['timeFrom'] ?? ''),
      'timeTo' => (string)($s['timeTo'] ?? ''),
      'location' => (string)($s['location'] ?? ''),
      'neededHelpers' => max(1, (int)($s['neededHelpers'] ?? 1)),
      'notes' => (string)($s['notes'] ?? ''),
    ];
  }
  return $out;
}

function helfer_event_row_to_array(array $row): array {
  return [
    'eventId' => $row['id'],
    'title' => $row['title'],
    'description' => $row['description'] ?? '',
    'location' => $row['location'] ?? '',
    'shifts' => json_decode($row['shifts_json'], true) ?: [],
    'active' => ((int)$row['active']) === 1,
    'createdAt' => $row['created_at'],
    'updatedAt' => $row['updated_at'],
  ];
}

function helfer_response_row_to_array(array $row): array {
  return [
    'responseId' => $row['id'],
    'eventId' => $row['event_id'],
    'name' => $row['name'],
    'contact' => $row['contact'] ?? '',
    'comment' => $row['comment'] ?? '',
    'answers' => json_decode($row['answers_json'], true) ?: [],
    'createdAt' => $row['created_at'],
    'updatedAt' => $row['updated_at'],
    // editToken wird absichtlich NICHT zurückgegeben, wenn Rückmeldungen
    // gemeinsam mit anderen Helfern gelesen werden (siehe index.php) - nur
    // beim eigenen Absenden kennt der Client sein Token.
  ];
}
