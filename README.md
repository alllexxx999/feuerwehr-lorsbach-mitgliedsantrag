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
- **Drei Links pro Planung**: Helfer-Link (eintragen), Ergebnis-Link (nur ansehen, z. B. für die Gruppen-Chat-Nachricht) und ein privater Admin-Link (verwalten)
- **Doodle-artige Übersicht** als Tabelle oder nach Schicht gruppiert, inkl. Bedarfsanzeige (grün/gelb/rot)
- **Helfer können ihre Zusage später ändern** – über einen persönlichen Link oder auf demselben Gerät automatisch wiedererkannt
- **Admin-Funktionen**: Schichten nachträglich bearbeiten, einzelne Rückmeldungen löschen, Planung schließen/wieder öffnen, CSV-Export, Planung endgültig löschen
- **Mehrere Planungen parallel möglich** – wiederverwendbar für jede künftige Veranstaltung
- **Mobile responsive**, keine Registrierung/Anmeldung für Helfer nötig
- **Self-contained** als einzelne HTML-Datei, wie der Mitgliedsantrag

### Wichtig: Backend für geräteübergreifende Nutzung nötig

Damit alle Helfer von unterschiedlichen Geräten dieselbe Planung sehen und ausfüllen können, braucht die Seite einen einfachen Speicherort für die Antworten. Da der Verein die Seite ohne eigenen Server betreibt, nutzt `helferplanung.html` dafür ein **Google Sheet** als Datenbank, angesprochen über ein kleines, kostenloses **Google-Apps-Script**.

Solange kein Backend eingetragen ist, läuft die Seite automatisch im **Demo-Modus**: Alles funktioniert, aber Planungen und Zusagen werden nur lokal im jeweiligen Browser gespeichert (nicht geräteübergreifend sichtbar) – gut zum Ausprobieren, aber nicht für den echten Einsatz mit mehreren Helfern.

### Helferplanung einrichten (einmalig, ca. 10 Minuten)

1. Ein neues, leeres **Google Sheet** anlegen (z. B. „Helferplanung Feuerwehr Lorsbach – Daten“).
2. Im Sheet: **Erweiterungen → Apps Script** öffnen.
3. Den kompletten Inhalt von [`apps-script/Code.gs`](apps-script/Code.gs) aus diesem Repository in den Apps-Script-Editor einfügen (vorhandenen Beispielcode ersetzen) und speichern.
4. Oben rechts auf **Bereitstellen → Neue Bereitstellung** klicken.
   - Typ: **Web-App**
   - Ausführen als: **Ich** (eigenes Google-Konto)
   - Zugriff: **Alle** *(wichtig – sonst können Helfer nicht antworten)*
5. Bereitstellen klicken. Google zeigt ggf. eine Warnung „Diese App wurde nicht verifiziert“ – das ist normal bei eigenen Skripten: auf **Erweitert** und dann **„Zu … (unsicher) wechseln“** klicken, danach Zugriff erlauben.
6. Die angezeigte **Web-App-URL** (endet auf `/exec`) kopieren.
7. In `helferplanung.html` ganz am Anfang des `<script>`-Bereichs die Zeile `var SCRIPT_URL = '';` finden und die kopierte URL eintragen, z. B.:
   ```js
   var SCRIPT_URL = 'https://script.google.com/macros/s/XXXXXXXX/exec';
   ```
8. Datei speichern und hochladen/committen – die Helferplanung ist jetzt einsatzbereit und synchronisiert automatisch über das Google Sheet.

**Hinweis zu späteren Code-Änderungen:** Wird `Code.gs` im Apps-Script-Editor geändert, muss unter **Bereitstellen → Bereitstellungen verwalten** die bestehende Web-App-Bereitstellung mit „Neue Version“ aktualisiert werden, damit die (gleichbleibende) `/exec`-URL die neue Logik verwendet.

Alle Antworten landen automatisch in den Tabellenblättern **„Events“** und **„Responses“** des Google Sheets und können dort jederzeit eingesehen oder exportiert werden.

### Workflow

1. Organisator öffnet `helferplanung.html`, legt Titel, Beschreibung und Schichten an (optional per Vorlage vorausgefüllt) und erstellt die Planung.
2. Die Seite zeigt sofort den **Helfer-Link** – dieser wird z. B. per WhatsApp/E-Mail an die Mannschaft verteilt.
3. Jeder Helfer öffnet den Link, trägt Name und pro Schicht Ja/Nein/Vielleicht ein und speichert.
4. Alle sehen live den Stand über den **Ergebnis-Link**; der Organisator verwaltet über den privaten **Admin-Link** (Schichten anpassen, Rückmeldungen löschen, Planung schließen, CSV-Export).

---

## Kontakt

**Freiwillige Feuerwehr Lorsbach e.V.**
Im Lorsbachtal 13
65719 Hofheim am Taunus
E-Mail: verein@feuerwehr-lorsbach.de
Telefon: 06192 / 9869957
Web: https://www.feuerwehr-lorsbach.de

Gläubiger-Identifikationsnummer: DE35ZZZ00000281997
