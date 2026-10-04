# Freiwillige Feuerwehr Lorsbach e.V. – Online-Tools

Dieses Repository enthält zwei eigenständige, selbst-enthaltene HTML-Seiten für die **Freiwillige Feuerwehr Lorsbach e.V.**:

1. **`index.html`** – Online-Antrag auf fördernde Mitgliedschaft
2. **`helferplanung.html`** – Helferplanung für Vereinsveranstaltungen (z. B. 125-Jahr-Feier), angelehnt an Doodle

---

## 1. Antrag auf fördernde Mitgliedschaft

Interaktiver Online-Antrag zur Beantragung der fördernden Mitgliedschaft bei der **Freiwilligen Feuerwehr Lorsbach e.V.** Der Antrag führt Antragsteller in fünf einfachen Schritten durch das Formular und erzeugt am Ende ein vorausgefülltes PDF zum Drucken und Unterschreiben.

### Funktionen

- **Mehrstufiger Wizard** mit Fortschrittsanzeige (5 Schritte)
- **Vollständige Datenschutzerklärung** und **SEPA-Lastschriftmandat** im Wortlaut
- **IBAN-Validierung** mit echtem mod-97-Check (DSGVO/SEPA-konform)
- **Automatische PDF-Generierung** im Layout des Original-Antrags inkl. Datenschutzerklärung auf Seite 2
- **Mobile responsive** – funktioniert auf Smartphone, Tablet und Desktop
- **Keine externen Abhängigkeiten** außer jsPDF (CDN) und Google Fonts
- **Self-contained** als einzelne HTML-Datei – einfach auf jeden Webserver hochzuladen

### Lokale Verwendung

Einfach `index.html` im Browser öffnen. Keine Build-Schritte erforderlich.

### Workflow für Antragsteller

1. Online ausfüllen (~2 Minuten)
2. PDF öffnet sich in einem neuen Browser-Tab zur Ansicht
3. PDF ausdrucken und an zwei Stellen unterschreiben (Antrag + SEPA-Mandat)
4. Per Post oder als Scan per E-Mail an den Verein senden

### Rechtshinweis

Für die rechtsverbindliche Mitgliedschaft und das SEPA-Lastschriftmandat ist eine eigenhändige Unterschrift auf dem ausgedruckten PDF erforderlich. Die Online-Eingabe ersetzt nicht die schriftliche Unterschrift.

### Technologie

- HTML5 / CSS3 / Vanilla JavaScript
- [jsPDF](https://github.com/parallax/jsPDF) für PDF-Generierung
- Google Fonts (Roboto / Roboto Condensed)

---

## 2. Helferplanung

`helferplanung.html` ist eine Doodle-ähnliche Helferplanung: Ein Organisator legt Schichten/Aufgaben an (z. B. für die 125-Jahr-Feier), erhält einen Link zum Teilen, und Helfer tragen sich darüber selbst mit Ja/Nein/Vielleicht pro Schicht ein. Alle sehen live den Stand ("3 von 5 Helfern für den Grillstand").

### Funktionen

- **Planung in wenigen Minuten erstellt**, inkl. Vorlage für typische Fest-Schichten (Auf-/Abbau, Ausschank, Kuchentheke, Kasse, Kinderprogramm, Ordnungsdienst …)
- **Aktueller Stand für alle sichtbar** ("3 von 5 Helfern für den Grillstand"), aktualisiert sich automatisch alle 15 Sekunden ohne Neuladen
- **Zwei Links pro Planung**: ein Link zum Eintragen und ein reiner Ergebnis-Link zum Teilen, z. B. in der Gruppen-Chat-Nachricht
- **Doodle-artige Übersicht** als Tabelle oder nach Schicht gruppiert, inkl. Bedarfsanzeige (grün/gelb/rot)
- **Helfer werden auf ihrem Gerät automatisch wiedererkannt** und können ihre Zusage jederzeit ändern; ein persönlicher Link erlaubt das Ändern auch von einem anderen Gerät aus
- **Ein gemeinsames Verwalter-Login** (Vereins-Passwort) schaltet alle Admin-Funktionen frei: Schichten nachträglich bearbeiten, einzelne Rückmeldungen löschen, Planung schließen/wieder öffnen, CSV-Export, Planung endgültig löschen
- **Übersicht aller Planungen** nach dem Login – kein Zettel mit gesammelten Links nötig
- **Mehrere Planungen parallel möglich** – wiederverwendbar für jede künftige Veranstaltung
- **Mobile responsive**, keine Registrierung/Anmeldung für Helfer nötig
- **Läuft auf dem eigenen Webspace** (PHP + MySQL) – keine Drittanbieter-Konten, kein CORS, Daten bleiben vollständig beim Verein

### Wichtig: PHP + MySQL nötig für geräteübergreifende Nutzung

Damit alle Helfer von unterschiedlichen Geräten dieselbe Planung sehen und ausfüllen können, braucht die Seite einen gemeinsamen Speicherort für die Antworten. `helferplanung.html` nutzt dafür eine kleine **PHP-API** (`api/`-Ordner) mit einer **MySQL-Datenbank** – beides ist in den meisten IONOS-Webhosting-Paketen bereits enthalten, ganz ohne zusätzliche Kosten oder Drittanbieter-Konten.

Wird die Datei lokal ohne Server geöffnet (z. B. per Doppelklick, `file://`), läuft die Seite automatisch im **Demo-Modus**: Alles funktioniert zum Ausprobieren, Verwaltungsfunktionen sind dabei immer freigeschaltet, aber Daten werden nur lokal im Browser gespeichert. Nach dem Hochladen auf den echten Webserver wird automatisch die PHP-API verwendet – **ohne dass im Code irgendetwas eingetragen werden muss**, da Frontend und API auf derselben Domain liegen.

**Wie „Admin-Rechte“ funktionieren:** Ein gemeinsames Vereins-Passwort (in `api/config.php` festgelegt) schaltet per Login alle Verwaltungsfunktionen frei – für **alle** Planungen, nicht nur die selbst erstellten, und von **jedem Gerät** aus, auf dem man sich anmeldet. Helfer brauchen zum Eintragen kein Passwort.

### Helferplanung einrichten (einmalig, ca. 10–15 Minuten)

1. Im IONOS-Kundenbereich unter **MySQL-Datenbanken** eine Datenbank anlegen (falls noch keine vorhanden ist) und Host, Datenbankname, Benutzername und Passwort notieren.
2. Diese Datenbank in **phpMyAdmin** öffnen (ebenfalls im IONOS-Kundenbereich erreichbar), Reiter **Importieren**, die Datei [`schema.sql`](schema.sql) aus diesem Repository hochladen und importieren. Das legt die beiden Tabellen `helfer_events` und `helfer_responses` an.
3. Im Ordner `api/` die Datei `config.sample.php` kopieren und zu `config.php` umbenennen (im selben Ordner).
4. `config.php` öffnen und die Platzhalter füllen:
   ```php
   return [
     'db_host' => 'localhost',          // ggf. laut IONOS-Angabe anpassen
     'db_name' => 'DEIN_DATENBANKNAME',
     'db_user' => 'DEIN_DATENBANK_BENUTZER',
     'db_pass' => 'DEIN_DATENBANK_PASSWORT',
     'admin_password' => 'EIN-SICHERES-VEREINS-PASSWORT',
   ];
   ```
   `admin_password` ist frei wählbar – das ist das gemeinsame Passwort, mit dem sich die Verwaltung (Planungen erstellen/bearbeiten/löschen) anmeldet.
5. Alle Dateien per FTP/Datei-Manager auf den Webserver hochladen: `helferplanung.html` **und** den kompletten `api/`-Ordner (inkl. der frisch angelegten `config.php`) in denselben Ordner wie `index.html`.
6. `helferplanung.html` im Browser öffnen, mit dem Vereins-Passwort anmelden und die erste Planung erstellen.

**Wichtig:** `api/config.php` enthält echte Zugangsdaten und ist deshalb in `.gitignore` eingetragen – sie landet nie im Git-Repository, sondern existiert nur auf dem Webserver bzw. lokal beim Einrichten.

Alle Planungen und Rückmeldungen landen in den MySQL-Tabellen **`helfer_events`** und **`helfer_responses`** und können dort über phpMyAdmin jederzeit eingesehen werden; die Seite selbst bietet zusätzlich einen CSV-Export pro Planung.

### Workflow

1. Verwalter/in öffnet `helferplanung.html`, meldet sich mit dem Vereins-Passwort an, legt Titel, Beschreibung und Schichten an (optional per Vorlage vorausgefüllt) und erstellt die Planung.
2. Die Seite zeigt sofort den **Link zur Planung** – dieser wird z. B. per WhatsApp/E-Mail an die Mannschaft verteilt.
3. Jeder Helfer öffnet den Link (kein Login nötig), trägt Name und pro Schicht Ja/Nein/Vielleicht ein und speichert. Öffnet derselbe Helfer den Link später erneut (gleiches Gerät), wird seine Zusage automatisch wiedererkannt; über den nach dem Speichern angezeigten persönlichen Link lässt sie sich auch von einem anderen Gerät aus ändern.
4. Alle sehen den aktuellen Stand über den **Ergebnis-Link**; die Verwaltung erreicht ihre Funktionen (Schichten anpassen, Rückmeldungen löschen, Planung schließen, CSV-Export) direkt auf dem Planungs-Link, sobald sie angemeldet ist – von jedem Gerät aus.

---

## Kontakt

**Freiwillige Feuerwehr Lorsbach e.V.**
Im Lorsbachtal 13
65719 Hofheim am Taunus
E-Mail: verein@feuerwehr-lorsbach.de
Telefon: 06192 / 9869957
Web: https://www.feuerwehr-lorsbach.de

Gläubiger-Identifikationsnummer: DE35ZZZ00000281997
