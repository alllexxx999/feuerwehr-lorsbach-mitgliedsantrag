<?php
/**
 * E-Mail-Backend für Mitgliedsantrag FF Lorsbach
 * Erfordert PHPMailer im Unterordner: phpmailer/src/
 * Download: https://github.com/PHPMailer/PHPMailer/releases
 */

// ============================================================
//  KONFIGURATION – hier anpassen
// ============================================================
define('SMTP_HOST',      'smtp.ionos.de');
define('SMTP_PORT',      587);
define('SMTP_USER',      'antrag@feuerwehr-lorsbach.de');
define('SMTP_PASS',      'HIER_PASSWORT_EINTRAGEN');
define('FROM_EMAIL',     'antrag@feuerwehr-lorsbach.de');
define('FROM_NAME',      'Mitgliedsantrag FF Lorsbach');
define('VEREIN_EMAIL',   'verein@feuerwehr-lorsbach.de');
define('VEREIN_NAME',    'Freiwillige Feuerwehr Lorsbach e.V.');
define('ALLOWED_ORIGIN', 'https://www.feuerwehr-lorsbach.de');
// ============================================================

header('Content-Type: application/json; charset=utf-8');

// CORS
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin === ALLOWED_ORIGIN) {
    header('Access-Control-Allow-Origin: ' . ALLOWED_ORIGIN);
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// JSON einlesen
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Ungültige JSON-Daten']);
    exit;
}

// Felder bereinigen
function clean($v) {
    return htmlspecialchars(strip_tags(trim((string)$v)), ENT_QUOTES, 'UTF-8');
}

$vorname       = clean($data['vorname']       ?? '');
$nachname      = clean($data['nachname']      ?? '');
$email         = filter_var(trim($data['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$strasse       = clean($data['strasse']       ?? '');
$plz           = clean($data['plz']           ?? '');
$ort           = clean($data['ort']           ?? '');
$geburtsdatum  = clean($data['geburtsdatum']  ?? '');
$beitrag       = clean($data['beitrag']       ?? '');
$iban          = clean($data['iban']          ?? '');
$bic           = clean($data['bic']           ?? '');
$kontoinhaber  = clean($data['kontoinhaber']  ?? '');
$mandatRef     = clean($data['mandatRef']     ?? '');
$datumHeute    = clean($data['datumHeute']    ?? date('d.m.Y'));

// Pflichtfelder prüfen
if (!$vorname || !$nachname || !$email || !$iban || !$mandatRef) {
    http_response_code(400);
    echo json_encode(['error' => 'Pflichtfelder fehlen']);
    exit;
}

// PHPMailer laden
$phpmailerBase = __DIR__ . '/phpmailer/src';
if (!file_exists($phpmailerBase . '/PHPMailer.php')) {
    http_response_code(500);
    echo json_encode(['error' => 'PHPMailer nicht gefunden. Bitte installieren.']);
    exit;
}
require $phpmailerBase . '/Exception.php';
require $phpmailerBase . '/PHPMailer.php';
require $phpmailerBase . '/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function createMailer() {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = SMTP_PORT;
    $mail->CharSet    = 'UTF-8';
    $mail->setFrom(FROM_EMAIL, FROM_NAME);
    return $mail;
}

// ---- E-MAIL 1: An den Verein (mit Vorstandsbeschluss-Abschnitt) ----
$vereinBody = "Sehr geehrter Vorstand der Freiwilligen Feuerwehr Lorsbach e.V.,\n\n"
    . "folgender Mitgliedsantrag ist über das digitale Antragsformular eingegangen:\n\n"
    . "═══════════════════════════════════════\n"
    . "ANTRAGSTELLER\n"
    . "═══════════════════════════════════════\n"
    . "Name:          $vorname $nachname\n"
    . "Geburtsdatum:  $geburtsdatum\n"
    . "Adresse:       $strasse, $plz $ort\n"
    . "E-Mail:        {$data['email']}\n\n"
    . "═══════════════════════════════════════\n"
    . "MITGLIEDSBEITRAG\n"
    . "═══════════════════════════════════════\n"
    . "Jährlicher Beitrag:  $beitrag €\n\n"
    . "═══════════════════════════════════════\n"
    . "SEPA-LASTSCHRIFTMANDAT\n"
    . "═══════════════════════════════════════\n"
    . "Kontoinhaber:     $kontoinhaber\n"
    . "IBAN:             $iban\n"
    . "BIC:              $bic\n"
    . "Mandatsreferenz:  $mandatRef\n"
    . "Datum Mandat:     $datumHeute\n\n"
    . "Der Antragsteller hat der Datenschutzerklärung und dem SEPA-Lastschriftmandat\n"
    . "elektronisch am $datumHeute zugestimmt.\n\n"
    . "═══════════════════════════════════════\n"
    . "VORSTANDSBESCHLUSS\n"
    . "═══════════════════════════════════════\n"
    . "Dieser Abschnitt dient als Nachweis für den Vereinsvorstand.\n"
    . "Bitte nach Beschlussfassung archivieren.\n\n"
    . "Beschluss über Aufnahmeantrag:\n\n"
    . "  □  Annahme des Antrags\n"
    . "  □  Ablehnung des Antrags\n\n"
    . "Datum der Beschlussfassung: ____________________\n\n"
    . "Unterschrift Vorsitzende/r: ____________________\n\n"
    . "Unterschrift stellv. Vorst: ____________________\n\n"
    . "═══════════════════════════════════════\n\n"
    . "Hinweis: Diese E-Mail wurde dem Antragsteller ($vorname $nachname) in Kopie (CC) zugestellt.\n"
    . "Der Antragsteller ist darüber informiert, dass er diese E-Mail als Kopie erhalten hat.\n\n"
    . "Mit freundlichen Grüßen\n"
    . "Digitales Antragsformular – Freiwillige Feuerwehr Lorsbach e.V.\n";

// ---- E-MAIL 2: Bestätigung an den Antragsteller ----
$antragBody = "Sehr geehrte/r $vorname $nachname,\n\n"
    . "vielen Dank für Ihren Mitgliedsantrag bei der Freiwilligen Feuerwehr Lorsbach e.V.\n\n"
    . "Ihr Antrag wurde erfolgreich übermittelt und wird zeitnah vom Vorstand bearbeitet.\n"
    . "Sie erhalten nach der Beschlussfassung eine gesonderte Benachrichtigung.\n\n"
    . "═══════════════════════════════════════\n"
    . "ZUSAMMENFASSUNG IHRES ANTRAGS\n"
    . "═══════════════════════════════════════\n"
    . "Name:             $vorname $nachname\n"
    . "Geburtsdatum:     $geburtsdatum\n"
    . "Adresse:          $strasse, $plz $ort\n"
    . "Jährl. Beitrag:   $beitrag €\n"
    . "IBAN:             $iban\n"
    . "Mandatsreferenz:  $mandatRef\n"
    . "Datum:            $datumHeute\n\n"
    . "═══════════════════════════════════════\n\n"
    . "Bei Rückfragen wenden Sie sich bitte an:\n"
    . VEREIN_EMAIL . "\n\n"
    . "Mit freundlichen Grüßen\n"
    . "Freiwillige Feuerwehr Lorsbach e.V.\n";

try {
    // Mail 1 an Verein (CC an Antragsteller)
    $mail1 = createMailer();
    $mail1->addAddress(VEREIN_EMAIL, VEREIN_NAME);
    $mail1->addCC($data['email'], "$vorname $nachname");
    $mail1->Subject = "Mitgliedsantrag: $vorname $nachname ($datumHeute)";
    $mail1->Body    = $vereinBody;
    $mail1->send();

    // Mail 2 Bestätigung an Antragsteller
    $mail2 = createMailer();
    $mail2->addAddress($data['email'], "$vorname $nachname");
    $mail2->Subject = 'Bestätigung Ihres Mitgliedsantrags – FF Lorsbach';
    $mail2->Body    = $antragBody;
    $mail2->send();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error'   => 'E-Mail konnte nicht gesendet werden.',
        'details' => $e->getMessage()
    ]);
}
