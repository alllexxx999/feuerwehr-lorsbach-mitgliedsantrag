-- Datenbankschema für die Helferplanung (helferplanung.html) der
-- Freiwilligen Feuerwehr Lorsbach e.V.
--
-- Einrichtung: In phpMyAdmin (im IONOS-Kundenbereich normalerweise unter
-- "Datenbanken" erreichbar) die eigene Datenbank öffnen, Reiter "Importieren"
-- und diese Datei hochladen. Siehe README.md, Abschnitt
-- "Helferplanung einrichten".
--
-- Die Tabellen sind absichtlich mit "helfer_" vorangestellt, damit sie nicht
-- mit anderen, bereits vorhandenen Tabellen in derselben Datenbank
-- kollidieren.

CREATE TABLE IF NOT EXISTS helfer_events (
  id VARCHAR(40) NOT NULL PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  description TEXT NULL,
  location VARCHAR(255) NULL,
  shifts_json MEDIUMTEXT NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS helfer_responses (
  id VARCHAR(40) NOT NULL PRIMARY KEY,
  event_id VARCHAR(40) NOT NULL,
  edit_token VARCHAR(64) NOT NULL,
  name VARCHAR(255) NOT NULL,
  contact VARCHAR(255) NULL,
  comment TEXT NULL,
  answers_json MEDIUMTEXT NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  CONSTRAINT fk_helfer_responses_event
    FOREIGN KEY (event_id) REFERENCES helfer_events(id) ON DELETE CASCADE,
  INDEX idx_helfer_responses_event (event_id),
  INDEX idx_helfer_responses_edit_token (edit_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
