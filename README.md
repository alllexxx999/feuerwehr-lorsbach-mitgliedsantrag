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
- **Live-Ergebnisse**: Alle sehen in Echtzeit den Stand ("3 von 5 Helfern für den Grillstand"), ohne die Seite neu zu laden
- **Zwei Links pro Planung**: ein Link zum Eintragen (öffnet ihn der Ersteller selbst, sieht er automatisch seine Verwaltungsfunktionen) und ein reiner Ergebnis-Link zum Teilen, z. B. in der Gruppen-Chat-Nachricht
- **Doodle-artige Übersicht** als Tabelle oder nach Schicht gruppiert, inkl. Bedarfsanzeige (grün/gelb/rot)
- **Helfer werden auf ihrem Gerät automatisch wiedererkannt** und können ihre Zusage jederzeit ändern
- **Admin-Funktionen**: Schichten nachträglich bearbeiten, einzelne Rückmeldungen löschen, Planung schließen/wieder öffnen, CSV-Export, Planung endgültig löschen
- **Mehrere Planungen parallel möglich** – wiederverwendbar für jede künftige Veranstaltung
- **Mobile responsive**, keine Registrierung/Anmeldung/Passwort für Helfer nötig
- **Self-contained** als einzelne HTML-Datei, wie der Mitgliedsantrag

### Wichtig: Backend für geräteübergreifende Nutzung nötig

Damit alle Helfer von unterschiedlichen Geräten dieselbe Planung sehen und ausfüllen können, braucht die Seite einen gemeinsamen Speicherort für die Antworten. Da der Verein die Seite ohne eigenen Server betreibt, nutzt `helferplanung.html` dafür **Firebase** (Google) mit der kostenlosen „Spark“-Stufe: **Cloud Firestore** als Datenbank und **Firebase Authentication** (anonyme Anmeldung, ganz ohne Login-Bildschirm) zur Zugriffssteuerung.

Solange kein Firebase-Projekt eingetragen ist, läuft die Seite automatisch im **Demo-Modus**: Alles funktioniert, aber Planungen und Zusagen werden nur lokal im jeweiligen Browser gespeichert (nicht geräteübergreifend sichtbar) – gut zum Ausprobieren, aber nicht für den echten Einsatz mit mehreren Helfern.

**Wie „Admin-Rechte“ funktionieren:** Es gibt kein Passwort und kein Geheim-Link mehr. Stattdessen merkt sich der Browser, der eine Planung erstellt hat, das automatisch (anonyme Firebase-Anmeldung) – wer die Planung angelegt hat, sieht beim Öffnen des ganz normalen Planungs-Links auf *demselben Gerät* automatisch die Verwaltungsfunktionen. Das bedeutet auch: Admin-Zugriff ist an das Gerät/den Browser gebunden, auf dem die Planung erstellt wurde (ähnlich einem automatisch gespeicherten Login) – löscht man dort die Website-Daten, geht der Verwaltungszugriff verloren (die Planung selbst bleibt erhalten). Für mehrere Verwalter empfiehlt es sich, die Planung z. B. am gemeinsamen Vereinslaptop zu erstellen und zu pflegen.

### Helferplanung einrichten (einmalig, ca. 10 Minuten)

1. Auf [console.firebase.google.com](https://console.firebase.google.com) ein neues, kostenloses Projekt anlegen (z. B. „Feuerwehr Lorsbach Helferplanung“). Google Analytics kann dabei deaktiviert werden, wird nicht benötigt.
2. Im Projekt links im Menü **Build → Firestore Database** öffnen, **Datenbank erstellen**, einen Standort in der Nähe wählen (z. B. `eur3 (europe-west)`) und im **Produktionsmodus** starten (die Standardregeln werden im nächsten Schritt ohnehin ersetzt).
3. Im Reiter **Regeln** der Firestore-Datenbank den kompletten Inhalt der Datei [`firebase/firestore.rules`](firebase/firestore.rules) aus diesem Repository einfügen (vorhandenen Text ersetzen) und **Veröffentlichen** klicken.
4. Links im Menü **Build → Authentication** öffnen, **Los geht's**, dann im Reiter **Sign-in method** den Anbieter **Anonym** auswählen und aktivieren (kein weiterer Login-Bildschirm nötig – Helfer merken davon nichts).
5. Links im Zahnrad-Menü **Projekteinstellungen** öffnen, ganz unten bei „Meine Apps“ auf das Web-Symbol (`</>`) klicken, der neuen Web-App einen beliebigen Namen geben (z. B. „Helferplanung“) und registrieren – Firebase-Hosting wird dabei **nicht** benötigt.
6. Firebase zeigt jetzt ein `firebaseConfig`-Objekt mit `apiKey`, `authDomain`, `projectId` usw. Diesen kompletten Block kopieren.
7. In `helferplanung.html` ganz am Anfang des `<script>`-Bereichs die Zeile `var FIREBASE_CONFIG = null;` finden und durch die kopierte Konfiguration ersetzen, z. B.:
   ```js
   var FIREBASE_CONFIG = {
     apiKey: "AIzaSy...", authDomain: "meinprojekt.firebaseapp.com",
     projectId: "meinprojekt", storageBucket: "meinprojekt.appspot.com",
     messagingSenderId: "1234567890", appId: "1:1234567890:web:abcdef"
   };
   ```
   Diese Werte sind bei Firebase bewusst öffentlich (sie stehen in jeder Firebase-Web-App und tauchen im Quelltext jeder Seite auf) – die eigentliche Absicherung übernehmen die Security-Rules aus Schritt 3, nicht die Geheimhaltung dieser Konfiguration.
8. Datei speichern und hochladen/committen – die Helferplanung ist jetzt einsatzbereit und synchronisiert automatisch (in Echtzeit) über Firestore.

Alle Planungen und Rückmeldungen landen in den Firestore-Sammlungen **„events“** und der jeweiligen Unter-Sammlung **„responses“** und können dort in der Firebase-Konsole jederzeit eingesehen werden.

### Workflow

1. Organisator öffnet `helferplanung.html`, legt Titel, Beschreibung und Schichten an (optional per Vorlage vorausgefüllt) und erstellt die Planung.
2. Die Seite zeigt sofort den **Planungs-Link** – dieser wird z. B. per WhatsApp/E-Mail an die Mannschaft verteilt. Da der Organisator die Planung selbst erstellt hat, sieht er auf seinem Gerät beim Öffnen desselben Links automatisch zusätzlich seine Verwaltungsfunktionen.
3. Jeder Helfer öffnet den Link, trägt Name und pro Schicht Ja/Nein/Vielleicht ein und speichert. Öffnet derselbe Helfer den Link später erneut (gleiches Gerät), wird seine Zusage automatisch wiedererkannt und lässt sich bearbeiten.
4. Alle sehen live den Stand über den **Ergebnis-Link**; der Organisator verwaltet direkt auf dem Planungs-Link (Schichten anpassen, Rückmeldungen löschen, Planung schließen, CSV-Export).

---

## Kontakt

**Freiwillige Feuerwehr Lorsbach e.V.**
Im Lorsbachtal 13
65719 Hofheim am Taunus
E-Mail: verein@feuerwehr-lorsbach.de
Telefon: 06192 / 9869957
Web: https://www.feuerwehr-lorsbach.de

Gläubiger-Identifikationsnummer: DE35ZZZ00000281997
