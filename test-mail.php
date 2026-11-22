<?php
/**
 * SMTP-Diagnose-Skript für Freiwillige Feuerwehr Lorsbach
 * =========================================================
 * SICHERHEIT: Diese Datei SOFORT nach dem Debugging löschen!
 * Enthält Passwort-Felder und Serverinformationen.
 *
 * Zugriff nur mit korrektem Token:
 *   https://www.feuerwehr-lorsbach.de/test-mail.php?token=fflorsbach-debug
 */

define('ACCESS_TOKEN', 'fflorsbach-debug');

// --- Zugriffschutz ---
if (!isset($_GET['token']) || $_GET['token'] !== ACCESS_TOKEN) {
    http_response_code(403);
    die('<h1>403 Forbidden</h1>');
}

$submitted  = $_SERVER['REQUEST_METHOD'] === 'POST';
$smtpPass   = trim($_POST['smtp_pass']   ?? '');
$toEmail    = trim($_POST['to_email']    ?? 'verein@feuerwehr-lorsbach.de');
$smtpUser   = trim($_POST['smtp_user']   ?? 'antrag@feuerwehr-lorsbach.de');
$smtpHost   = trim($_POST['smtp_host']   ?? 'smtp.ionos.de');
$smtpPort   = (int)($_POST['smtp_port']  ?? 587);
$encryption = $_POST['encryption']       ?? 'tls';

// --- Hilfsfunktionen ---
function ok($msg)   { return '<span class="ok">✓ ' . htmlspecialchars($msg) . '</span>'; }
function fail($msg) { return '<span class="fail">✗ ' . htmlspecialchars($msg) . '</span>'; }
function warn($msg) { return '<span class="warn">⚠ ' . htmlspecialchars($msg) . '</span>'; }
function info($msg) { return '<span class="info">ℹ ' . htmlspecialchars($msg) . '</span>'; }

function checkExt($ext) {
    return extension_loaded($ext) ? ok($ext . ' geladen') : fail($ext . ' FEHLT');
}

?><!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>SMTP-Diagnose – FF Lorsbach</title>
<style>
  body { font-family: monospace; background: #1a1a2e; color: #e0e0e0; margin: 0; padding: 20px; }
  h1   { color: #e94560; }
  h2   { color: #0f3460; background: #16213e; padding: 8px 12px; border-left: 4px solid #e94560; }
  .box { background: #16213e; border: 1px solid #0f3460; padding: 16px; margin: 12px 0; border-radius: 6px; }
  .ok   { color: #4caf50; }
  .fail { color: #f44336; font-weight: bold; }
  .warn { color: #ff9800; }
  .info { color: #2196f3; }
  pre  { background: #0d0d1a; padding: 12px; overflow-x: auto; border-radius: 4px; font-size: 12px; white-space: pre-wrap; }
  form { background: #16213e; padding: 20px; border-radius: 6px; }
  label { display: block; margin: 8px 0 4px; color: #aaa; }
  input, select { width: 100%; padding: 8px; background: #0d0d1a; border: 1px solid #0f3460;
                  color: #e0e0e0; border-radius: 4px; box-sizing: border-box; }
  button { margin-top: 16px; padding: 10px 24px; background: #e94560; color: #fff;
           border: none; border-radius: 4px; cursor: pointer; font-size: 15px; }
  button:hover { background: #c73652; }
  .warning-box { background: #3e1a00; border: 2px solid #e94560; padding: 12px; margin: 12px 0; border-radius: 6px; color: #ffcc80; }
  .section { margin: 20px 0; }
</style>
</head>
<body>

<h1>🔧 SMTP-Diagnose – Freiwillige Feuerwehr Lorsbach</h1>

<div class="warning-box">
  <strong>⚠ SICHERHEITSHINWEIS:</strong> Diese Datei enthält sensible Server-Informationen.
  Bitte nach dem Debugging sofort löschen! Zugang nur über direkten Link mit Token.
</div>

<!-- ===== FORMULAR ===== -->
<form method="POST" action="?token=<?= htmlspecialchars(ACCESS_TOKEN) ?>">
<h2>SMTP-Einstellungen</h2>
  <label>SMTP-Benutzer (From):</label>
  <input type="text" name="smtp_user" value="<?= htmlspecialchars($smtpUser) ?>">

  <label>SMTP-Passwort:</label>
  <input type="password" name="smtp_pass" value="<?= htmlspecialchars($smtpPass) ?>"
         placeholder="Ionos E-Mail-Passwort eingeben">

  <label>SMTP-Host:</label>
  <input type="text" name="smtp_host" value="<?= htmlspecialchars($smtpHost) ?>">

  <label>SMTP-Port:</label>
  <select name="smtp_port">
    <option value="587" <?= $smtpPort === 587 ? 'selected' : '' ?>>587 (STARTTLS – empfohlen bei Ionos)</option>
    <option value="465" <?= $smtpPort === 465 ? 'selected' : '' ?>>465 (SSL/TLS)</option>
    <option value="25"  <?= $smtpPort === 25  ? 'selected' : '' ?>>25 (kein TLS)</option>
  </select>

  <label>Verschlüsselung:</label>
  <select name="encryption">
    <option value="tls" <?= $encryption === 'tls' ? 'selected' : '' ?>>tls (STARTTLS)</option>
    <option value="ssl" <?= $encryption === 'ssl' ? 'selected' : '' ?>>ssl (Direktes SSL)</option>
    <option value=""    <?= $encryption === ''    ? 'selected' : '' ?>>keine</option>
  </select>

  <label>Test-E-Mail senden an:</label>
  <input type="text" name="to_email" value="<?= htmlspecialchars($toEmail) ?>">

  <button type="submit">▶ Diagnose starten</button>
</form>

<?php if ($submitted): ?>

<!-- ===== SECTION 1: PHP-UMGEBUNG ===== -->
<div class="section">
<h2>1. PHP-Umgebung</h2>
<div class="box">
<?php
$phpVersion = phpversion();
echo 'PHP-Version: ';
echo version_compare($phpVersion, '7.4', '>=') ? ok($phpVersion) : fail($phpVersion . ' (mind. 7.4 empfohlen)');
echo '<br>';
echo checkExt('openssl')  . '<br>';
echo checkExt('curl')     . '<br>';
echo checkExt('mbstring') . '<br>';

$sslEnabled = extension_loaded('openssl');
echo '<br>';
echo 'allow_url_fopen: ' . (ini_get('allow_url_fopen') ? ok('an') : warn('aus')) . '<br>';
echo 'SMTP (php.ini): '  . info(ini_get('SMTP') ?: '(leer)') . '<br>';
echo 'sendmail_path: '   . info(ini_get('sendmail_path') ?: '(leer)') . '<br>';
?>
</div>
</div>

<!-- ===== SECTION 2: PHPMAILER ===== -->
<div class="section">
<h2>2. PHPMailer-Pfade</h2>
<div class="box">
<?php
$paths = [
    'phpmailer/src/PHPMailer.php',
    'phpmailer/src/SMTP.php',
    'phpmailer/src/Exception.php',
    '../phpmailer/src/PHPMailer.php',
    'vendor/phpmailer/phpmailer/src/PHPMailer.php',
];
$phpmailerFound = false;
$phpmailerBase  = null;
foreach ($paths as $p) {
    $full = __DIR__ . '/' . $p;
    if (file_exists($full)) {
        echo ok($p) . '<br>';
        $phpmailerFound = true;
        $phpmailerBase  = dirname($full);
    } else {
        echo fail($p . ' – nicht gefunden') . '<br>';
    }
}
if (!$phpmailerFound) {
    echo '<br>' . fail('PHPMailer NICHT gefunden! Bitte herunterladen:') . '<br>';
    echo info('https://github.com/PHPMailer/PHPMailer/releases') . '<br>';
    echo info('Ordner "phpmailer/src/" neben dieser Datei ablegen') . '<br>';
}
?>
</div>
</div>

<!-- ===== SECTION 3: NETZWERK / DNS ===== -->
<div class="section">
<h2>3. Netzwerk &amp; DNS</h2>
<div class="box">
<?php
// DNS-Check
$dnsResult = gethostbyname($smtpHost);
if ($dnsResult !== $smtpHost) {
    echo ok('DNS-Auflösung ' . htmlspecialchars($smtpHost) . ' → ' . $dnsResult) . '<br>';
} else {
    echo fail('DNS-Auflösung fehlgeschlagen für ' . htmlspecialchars($smtpHost)) . '<br>';
}

// TCP-Verbindung testen (ohne TLS)
echo '<br>TCP-Verbindungstest auf Port ' . $smtpPort . ':<br>';
$errno  = 0;
$errstr = '';
$socket = @fsockopen($smtpHost, $smtpPort, $errno, $errstr, 5);
if ($socket) {
    $banner = fgets($socket, 1024);
    fclose($socket);
    echo ok('TCP-Verbindung erfolgreich') . '<br>';
    echo info('Server-Banner: ' . trim($banner)) . '<br>';
} else {
    echo fail('TCP-Verbindung FEHLGESCHLAGEN: ' . $errstr . ' (errno ' . $errno . ')') . '<br>';
    echo warn('→ Mögliche Ursache: Firewall des Webhosts blockiert Port ' . $smtpPort) . '<br>';
    echo warn('→ Bitte beim Webhoster prüfen ob ausgehende SMTP-Ports erlaubt sind') . '<br>';
}
?>
</div>
</div>

<!-- ===== SECTION 4: PHPMAILER SMTP-TEST ===== -->
<div class="section">
<h2>4. PHPMailer SMTP-Test (vollständiger Debug-Output)</h2>
<div class="box">
<?php
if (!$phpmailerFound) {
    echo fail('PHPMailer nicht gefunden – Test nicht möglich') . '<br>';
} elseif (empty($smtpPass)) {
    echo warn('Kein SMTP-Passwort eingegeben – Test übersprungen') . '<br>';
} else {
    // PHPMailer laden
    require $phpmailerBase . '/Exception.php';
    require $phpmailerBase . '/PHPMailer.php';
    require $phpmailerBase . '/SMTP.php';

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;
    use PHPMailer\PHPMailer\Exception;

    // Debug-Output in Buffer umleiten
    ob_start();

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $smtpHost;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpUser;
        $mail->Password   = $smtpPass;
        $mail->SMTPSecure = $encryption === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS
                          : ($encryption === 'ssl' ? PHPMailer::ENCRYPTION_SMIME : '');
        $mail->Port       = $smtpPort;
        $mail->CharSet    = 'UTF-8';

        // Vollständiger Debug (zeigt alle SMTP-Kommunikation)
        $mail->SMTPDebug  = SMTP::DEBUG_LOWLEVEL; // = 4, zeigt alles

        // E-Mail zusammenbauen
        $mail->setFrom($smtpUser, 'FF Lorsbach Diagnose');
        $mail->addAddress($toEmail);
        $mail->Subject = 'SMTP-Test FF Lorsbach ' . date('Y-m-d H:i:s');
        $mail->Body    = "Dies ist eine Diagnose-E-Mail vom SMTP-Test-Skript.\n\n"
                       . "Gesendet: " . date('d.m.Y H:i:s') . "\n"
                       . "Host: " . $_SERVER['HTTP_HOST'] . "\n"
                       . "PHP: " . phpversion();

        $mail->send();
        $debugOutput = ob_get_clean();

        echo ok('E-MAIL ERFOLGREICH GESENDET an ' . htmlspecialchars($toEmail)) . '<br><br>';
        echo '<strong>SMTP-Kommunikation (Debug-Level 4):</strong>';
        echo '<pre>' . htmlspecialchars($debugOutput) . '</pre>';

    } catch (Exception $e) {
        $debugOutput = ob_get_clean();

        echo fail('FEHLER beim Senden:') . '<br>';
        echo '<pre class="fail">' . htmlspecialchars($mail->ErrorInfo) . '</pre>';
        echo '<br><strong>SMTP-Kommunikation (Debug-Level 4):</strong>';
        echo '<pre>' . htmlspecialchars($debugOutput) . '</pre>';

        // Fehler-Analyse
        echo '<br><strong>Fehler-Analyse:</strong><br>';
        $err = $mail->ErrorInfo;
        if (strpos($err, 'SMTP connect() failed') !== false) {
            echo warn('→ Verbindung fehlgeschlagen. Mögliche Ursachen:') . '<br>';
            echo info('  1. Firewall blockiert Port ' . $smtpPort) . '<br>';
            echo info('  2. Falscher SMTP-Host oder Port') . '<br>';
            echo info('  3. SSL/TLS-Zertifikat-Problem') . '<br>';
        }
        if (strpos($err, 'Username and Password not accepted') !== false || strpos($err, 'authentication') !== false) {
            echo warn('→ Authentifizierung fehlgeschlagen:') . '<br>';
            echo info('  1. Passwort prüfen (Groß-/Kleinschreibung)') . '<br>';
            echo info('  2. Bei Ionos: SMTP muss in den E-Mail-Einstellungen aktiviert sein') . '<br>';
            echo info('  3. 2-Faktor-Authentifizierung kann SMTP blockieren') . '<br>';
        }
        if (strpos($err, 'certificate') !== false || strpos($err, 'SSL') !== false) {
            echo warn('→ TLS/SSL-Zertifikat-Problem:') . '<br>';
            echo info('  Versuche Port 465 mit Verschlüsselung "ssl"') . '<br>';
        }
    }
}
?>
</div>
</div>

<!-- ===== SECTION 5: ALTERNATIVER TEST MIT mail() ===== -->
<div class="section">
<h2>5. Fallback: PHP mail() Test</h2>
<div class="box">
<?php
if (function_exists('mail')) {
    echo info('mail()-Funktion ist verfügbar') . '<br>';
    if (!empty($toEmail)) {
        $result = @mail(
            $toEmail,
            'mail()-Test FF Lorsbach ' . date('H:i:s'),
            "Test mit nativer PHP mail()-Funktion.\n\n" . date('d.m.Y H:i:s'),
            'From: ' . $smtpUser . "\r\nContent-Type: text/plain; charset=UTF-8"
        );
        if ($result) {
            echo ok('mail() hat TRUE zurückgegeben') . '<br>';
            echo warn('(TRUE bedeutet nur, dass die E-Mail in die Queue übergeben wurde, nicht dass sie ankam)') . '<br>';
        } else {
            echo fail('mail() hat FALSE zurückgegeben – wahrscheinlich kein lokaler MTA konfiguriert') . '<br>';
        }
    }
} else {
    echo fail('mail()-Funktion ist deaktiviert') . '<br>';
}
?>
</div>
</div>

<!-- ===== SECTION 6: SERVER-INFO ===== -->
<div class="section">
<h2>6. Server-Informationen</h2>
<div class="box">
<?php
echo 'Server-Software: ' . info($_SERVER['SERVER_SOFTWARE'] ?? 'unbekannt') . '<br>';
echo 'Server-Name: '     . info($_SERVER['SERVER_NAME']     ?? 'unbekannt') . '<br>';
echo 'PHP SAPI: '        . info(php_sapi_name()) . '<br>';
echo 'Zeitzone: '        . info(date_default_timezone_get()) . '<br>';
echo 'Aktueller Pfad: '  . info(__DIR__) . '<br>';

$iniFile = php_ini_loaded_file();
echo 'php.ini: '         . info($iniFile ?: '(nicht gefunden)') . '<br>';

// OpenSSL-Info
if (extension_loaded('openssl')) {
    $ssl = openssl_get_cert_locations();
    echo '<br>OpenSSL-Version: ' . info(OPENSSL_VERSION_TEXT) . '<br>';
    echo 'CA-Bundle: '           . info($ssl['default_cert_file'] ?? 'unbekannt') . '<br>';
}
?>
</div>
</div>

<div class="warning-box">
  <strong>✓ Diagnose abgeschlossen.</strong><br>
  Bitte diese Datei jetzt vom Server löschen: <code>test-mail.php</code>
</div>

<?php endif; // $submitted ?>

</body>
</html>
