<?php
/**
 * Konfiguration für die Helferplanung.
 *
 * Diese Datei zu "config.php" kopieren (im selben Ordner, "api/") und die
 * Platzhalter unten mit den eigenen Daten füllen. "config.php" wird
 * absichtlich NICHT ins Git-Repository übernommen (siehe .gitignore), damit
 * echte Zugangsdaten nie versehentlich veröffentlicht werden.
 *
 * Die Datenbank-Zugangsdaten (Host/Name/Benutzer/Passwort) findet man im
 * IONOS-Kundenbereich unter "MySQL-Datenbanken".
 *
 * Das Verwalter-Passwort ist frei wählbar und schützt alle
 * Verwaltungsfunktionen (Planung erstellen/bearbeiten/löschen,
 * Rückmeldungen löschen) auf dieser Seite. Es ist EIN gemeinsames Passwort
 * für den ganzen Verein, kein Login pro Person.
 */
return [
  'db_host' => 'localhost',
  'db_name' => 'DEIN_DATENBANKNAME',
  'db_user' => 'DEIN_DATENBANK_BENUTZER',
  'db_pass' => 'DEIN_DATENBANK_PASSWORT',

  'admin_password' => 'EIN-SICHERES-VEREINS-PASSWORT',
];
