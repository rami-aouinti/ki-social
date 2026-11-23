# KI Social — KI-gestützter Social-Media-Workflow

**Assessment prototype · fiktiver Optiker · keine externen Dienste**

## Ziel

Ein nachvollziehbarer Laravel-Prototyp für Wochenplanung, Content-Briefing, menschliche Freigabe und simulierte Übergabe an eine Publishing-Schnittstelle. Ausschließlich synthetische Daten und example.com-Adressen werden verwendet.

## Technologieentscheidungen

- PHP 8.4 / Laravel 12: schnelle, testbare Umsetzung mit klarer Trennung von HTTP, Anwendung und Infrastruktur.
- SQLite: keine zusätzliche Datenbankinstallation; für den Prototyp ausreichend.
- Contract-orientierte Services: Der AI-Provider ist über AiContentGenerator austauschbar.
- Deterministischer Fake-AI-Provider: reproduzierbare Antworten ohne API-Key, Kosten oder externe Datenübertragung.
- SHA-256-Freigabefingerprint: Freigabe gilt nur für die exakte Version einschließlich Titel, Text, Medienreferenz, Kanälen und Termin.
- Datenbank-Idempotenz: Ein eindeutiger Schlüssel verhindert doppelte lokale Übergabe-Datensätze bei Wiederholungsaufrufen.
- Docker Compose: reproduzierbarer lokaler Start; kein produktives Hosting.

## Architektur

    HTTP / Artisan
      +-- WeeklyPlanningService --> weekly-plan.json + weekly-report.txt
      +-- BriefingService -------> AiContentGenerator (FakeAiContentGenerator)
      +-- WorkflowController
           +-- ApprovalService --> ContentFingerprint (SHA-256)
           +-- PublishingService -> PublishingGateway (FakePublishingGateway)
                          -> PublishingHandoff (unique idempotency key)
                          -> local JSON payload (SIMULATED)

Controller validieren Eingaben und delegieren. Services enthalten Anwendungsregeln. Eloquent-Modelle bilden persistente Datensätze ab. Der Fake-Provider ist über den Laravel-Container gebunden und kann durch einen echten Adapter ersetzt werden.

## Start

Voraussetzungen: Docker und Docker Compose.

    git clone https://github.com/rami-aouinti/ki-social.git
    cd ki-social
    cp .env.example .env
    docker compose up --build

Danach: http://localhost:8000. Beim Start werden Composer-Abhängigkeiten installiert, SQLite angelegt und Migrationen ausgeführt. Der Compose-Fallback-Schlüssel ist ausschließlich für diese lokale Demo gedacht.

Ohne Docker (PHP 8.2+, Composer, SQLite-Erweiterung):

    composer install
    cp .env.example .env
    php artisan key:generate
    touch database/database.sqlite
    php artisan migrate
    php artisan serve

## Demo

    docker compose exec app php artisan social:plan
    docker compose exec app php artisan social:briefing IDEA-001

Erzeugte Artefakte:
- storage/app/results/weekly-plan.json
- storage/app/results/weekly-report.txt
- storage/app/results/briefing-IDEA-001.json
- storage/app/publishing/handoff-{id}.json (erst nach Freigabe und Übergabe)

Synthetische Beispielausgaben liegen unter examples/weekly-plan.sample.json, examples/briefing.sample.json und examples/handoff-payload.sample.json.

## Workflow-API

Alle Beispiele sind synthetisch.

1. Entwurf anlegen: POST /api/posts mit idea_id, title, body, media_reference, channels, scheduled_at und assigned_person.
2. Prüfung anfordern: POST /api/posts/{id}/submit.
3. Freigeben: POST /api/posts/{id}/approve mit reviewer.
4. Simuliert übergeben: POST /api/posts/{id}/handoff.
5. Ändern: POST /api/posts/{id}/edit. Jede Änderung erhöht die Version und setzt den Status auf draft; eine neue Prüfung und Freigabe sind erforderlich.

Die Übergabe speichert ausschließlich lokales JSON. Es gibt keine Instagram-, Facebook-, TikTok- oder LinkedIn-Anbindung. Der Reviewer ist im Prototyp nur ein übermittelter Anzeigename, keine authentifizierte Benutzeridentität.

## Wochenplanung und Kapazität

Die synthetischen Eingaben enthalten 90 Minuten für A und 150 Minuten für B. Aufwand umfasst Produktion, Varianten, Abstimmung und Freigabe. Nur die drei priorisierten Ideen belasten zunächst das feste Wochenbudget; zwei Reserven bleiben unverplant. Ein negatives Restbudget erzeugt eine Warnung. Verfügbare Zeit wird nie automatisch erhöht.

Bewusste Prototyp-Grenze: Die fünf Ideen, ihre Zuordnung, Termine und Aufwände sind derzeit deterministische Beispieldaten im WeeklyPlanningService. Die Eingabedatei liefert Kontext, Zielgruppen, Tonalität, Kapazitäten und Inspirationsquellen; sie steuert die Themenauswahl noch nicht dynamisch. Inspirationsquellen sind keine Trendnachweise. Für den begrenzten Prototyp ist die feste, reproduzierbare Planung transparent; als nächster Schritt könnte die Ideenliste aus validierten Eingabedaten oder einem austauschbaren Planungs-Provider kommen.

## Tests

    composer validate --no-check-publish
    php artisan test

Abgedeckt werden:
- vollständiger freigegebener Inhalt wird bei erneutem Übergabeaufruf nur einmal gespeichert;
- fehlende Freigabe verhindert Übergabe;
- fehlende Medienreferenz verhindert bereits die Einreichung zur Prüfung;
- Textänderung nach Freigabe entwertet die alte Freigabe;
- abgelehnte Übergabe durch das Gateway wird nicht als erfolgreich gespeichert;
- Wochenplan enthält drei Prioritäten und zwei Reserven; Budgetüberschreitung wird sichtbar;
- Textbericht enthält Termine, Status, Begründungen, Quellen und Kapazitätshinweise;
- fehlende Inspirationskennzahlen bleiben unbekannt, Quellen sind als unbestätigt markiert.

Siehe examples/test-evidence.md für die Testmatrix und die Anleitung zum Festhalten der tatsächlichen Testausgabe.

## Status: implementiert, simuliert, konzipiert

| Bereich | Status |
|---|---|
| 5 Ideen, 3 Prioritäten, 2 Reserven | Implementiert; Themenauswahl derzeit deterministisch |
| Kapazitätsprüfung und Warnung | Implementiert |
| Vollständiges Briefing und zwei Kanalvarianten | Implementiert; KI-Antwort simuliert |
| Draft / Pending Review / Approved / Handed Over | Implementiert |
| Versionierung und SHA-256-Fingerprint | Implementiert |
| Publishing-Payload und Idempotenz | Implementiert; lokale Übergabe simuliert |
| Tatsächliche Social-Media-Veröffentlichung | Ausgeschlossen |
| E-Mail-Versand | Nicht implementiert; TXT-Bericht lokal erzeugt |
| Analytics, UTM, ROI, Kommentarentwürfe | Nur konzipiert |
| Scheduler, Secret Manager, Monitoring | Nur konzipiert |

## Ausbauideen

- Messung: UTM je Kanal/Kampagne; Events mit eindeutiger Event-ID; fehlende Werte sichtbar lassen; Deduplizierung über Event-ID. Attribution ist kein Kausalitätsnachweis.
- Wirtschaftlichkeit: Umsatz und Deckungsbeitrag trennen. Kosten je Anfrage/Kunde nur bei ausreichender Datenbasis; bei Nenner 0 keine Quote.
- Kommentare: Antwortentwürfe mit menschlicher Prüfung. Beschwerden, sensible oder unklare Anliegen eskalieren; keine automatische Antwort.
- Betrieb: Laravel Scheduler, verschlüsselte Secret-Verwaltung, strukturierte Logs, Fehlerbenachrichtigung und manueller Ersatzweg.
- Kapazität: Produktionsaufwand getrennt von tatsächlich verfügbaren Beratungsterminen behandeln.

## Annahmen und technische Grenzen

- Alle Personen, Inhalte, Kennzahlen, Termine und Quellenkennungen sind fiktiv.
- Inspirationsfunde sind keine Trendnachweise; fehlende Kennzahlen werden nicht erfunden.
- Medienreferenzen sind synthetische Strings, keine echten Uploads oder Datei-Scans.
- Publishing nimmt nur lokales JSON entgegen.
- Der Prototyp ruft das Gateway innerhalb einer Datenbanktransaktion auf. Das ist für den lokalen Fake-Adapter ausreichend, aber kein belastbares Muster für einen externen Dienst: Ein externer Dienst könnte die Anfrage akzeptieren, während anschließend der lokale Datenbank-Commit scheitert. Vor einem echten Adapter wäre eine Outbox mit separatem Worker, Retry-Status und Überwachung nötig. Der eindeutige Schlüssel schützt gegen doppelte lokale Datensätze, ersetzt aber keine verteilte Transaktionsstrategie.
- Die Demo ist bewusst klein und kein produktionsreifes Social-Media-Management-System.

## KI-Werkzeuge und Bearbeitungszeit

ChatGPT wurde zur Anforderungsstrukturierung, Architekturabwägung, Erstellung von Codeentwürfen und Review-Checklisten verwendet. Der KI-Provider im Prototyp ist ein deterministischer Fake und führt keine externen Requests aus.

Tatsächliche Bearbeitungszeit: Bitte vor Abgabe selbst anhand deiner tatsächlich aufgewendeten Zeit eintragen. Dieser Wert wird bewusst nicht geschätzt oder erfunden.
