# KI Social — KI-gestützter Social-Media-Workflow

**Assessment prototype · fiktiver Optiker · keine externen Dienste**

## Ziel

Ein nachvollziehbarer Laravel-Prototyp für Wochenplanung, Content-Briefing, menschliche Freigabe und simulierte Übergabe an eine Publishing-Schnittstelle. Ausschließlich synthetische Daten und example.com-Adressen.

## Technologieentscheidungen

- **PHP 8.4 / Laravel 12:** schnelle, testbare Umsetzung mit klarer Trennung von HTTP, Anwendung und Infrastruktur.
- **SQLite:** keine zusätzliche Datenbankinstallation; für den Prototyp ausreichend. PostgreSQL wäre eine sinnvolle Produktionsoption.
- **Contract-orientierte Services:** Der AI-Provider ist über `AiContentGenerator` austauschbar. Die Geschäftslogik kennt keinen konkreten KI-Anbieter.
- **Deterministischer Fake-AI-Provider:** reproduzierbare Antworten ohne API-Key, Kosten oder externe Datenübertragung.
- **SHA-256-Freigabefingerprint:** Freigabe gilt nur für exakt die Version inklusive Text, Medienreferenz, Kanälen und Termin.
- **Datenbank-Idempotenz:** Unique Key verhindert doppelte Übergaben bei Wiederholungsaufrufen.
- **Docker Compose:** reproduzierbarer lokaler Start; kein produktives Hosting.

## Architektur

```text
HTTP / Artisan
  +-- WeeklyPlanningService --> weekly-plan.json + weekly-report.txt
  +-- BriefingService -------> AiContentGenerator (FakeAiContentGenerator)
  +-- WorkflowController
       +-- ApprovalService --> ContentFingerprint (SHA-256)
       +-- PublishingService -> PublishingHandoff (unique idempotency key)
                               -> local JSON payload (SIMULATED)
```

Controller validieren und delegieren. Services enthalten Anwendungsregeln. Eloquent-Modelle bilden persistente Datensätze ab. Der Fake-Provider ist über den Laravel-Container gebunden und kann durch einen echten Adapter ersetzt werden.

## Start

Voraussetzungen: Docker und Docker Compose.

```bash
git clone https://github.com/rami-aouinti/ki-social.git
cd ki-social
cp .env.example .env
docker compose up --build
```

Danach: http://localhost:8000. Beim Start werden Composer-Abhängigkeiten installiert, SQLite angelegt und Migrationen ausgeführt. Der Compose-Fallback-Schlüssel ist ausschließlich für diese lokale Demo gedacht; für jede echte Umgebung muss ein eigener Schlüssel verwendet werden.

Ohne Docker (PHP 8.2+, Composer, SQLite-Erweiterung):

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan serve
```

## Demo

```bash
docker compose exec app php artisan social:plan
docker compose exec app php artisan social:briefing IDEA-001
```

Erzeugte Artefakte:
- `storage/app/results/weekly-plan.json`
- `storage/app/results/weekly-report.txt`
- `storage/app/results/briefing-IDEA-001.json`
- `storage/app/publishing/handoff-{id}.json` (erst nach Freigabe und Übergabe)

Synthetische Beispielausgaben liegen unter `examples/weekly-plan.sample.json`, `examples/briefing.sample.json` und `examples/handoff-payload.sample.json`.

## Workflow-API

Alle Beispiele sind synthetisch.

1. Entwurf anlegen: `POST /api/posts`

```json
{
  "idea_id": "IDEA-001",
  "title": "Welche Brillenform passt zu dir?",
  "body": "Lass dich persönlich beraten und entdecke passende Fassungen.",
  "media_reference": "media/demo-reel-v1.mp4",
  "channels": ["instagram", "linkedin"],
  "scheduled_at": "2026-11-24T10:00:00+01:00",
  "assigned_person": "A"
}
```

2. Prüfung anfordern: `POST /api/posts/{id}/submit`
3. Freigeben: `POST /api/posts/{id}/approve` mit `{"reviewer":"Person B"}`
4. Simuliert übergeben: `POST /api/posts/{id}/handoff`
5. Ändern: `POST /api/posts/{id}/edit`. Jede Änderung erhöht die Version und setzt den Status auf `draft`.

Die Übergabe speichert ausschließlich lokales JSON. Keine Instagram-, Facebook-, TikTok- oder LinkedIn-Anbindung.

## Kapazitätsplanung

Die synthetischen Eingaben enthalten 90 Minuten für A und 150 Minuten für B. Aufwand umfasst Produktion, Varianten, Abstimmung und Freigabe. Nur die drei priorisierten Ideen belasten zunächst das feste Wochenbudget; zwei Reserven bleiben unverplant. Ein negatives Restbudget erzeugt eine Warnung. Verfügbare Zeit wird nie automatisch erhöht.

## Tests

```bash
composer test
# oder
php artisan test
```

Automatisierte Nachweise:
- vollständiger freigegebener Inhalt wird bei erneutem Übergabeaufruf nur einmal gespeichert;
- fehlende Freigabe verhindert Übergabe;
- fehlende Medienreferenz verhindert Einreichung zur Prüfung;
- Textänderung nach Freigabe erzeugt eine neue Version und entwertet die alte Freigabe;
- Wochenplan enthält drei Prioritäten und zwei Reserven; Budgetüberschreitung wird sichtbar;
- fehlende Inspirationskennzahlen bleiben `null`, Quellen sind als unbestätigt markiert.

Siehe `examples/test-evidence.md`. Terminalausgabe/Screenshots nach einem tatsächlichen lokalen Testlauf ergänzen.

## Status: implementiert, simuliert, konzipiert

| Bereich | Status |
|---|---|
| 5 Ideen, 3 Prioritäten, 2 Reserven | Implementiert |
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

- **Messung:** UTM je Kanal/Kampagne; Events mit eindeutiger Event-ID; fehlende Werte sichtbar lassen; Deduplizierung über Event-ID. Attribution ist kein Kausalitätsnachweis.
- **Wirtschaftlichkeit:** Umsatz und Deckungsbeitrag trennen. Kosten je Anfrage/Kunde nur bei ausreichender Datenbasis; bei Nenner 0 keine Quote.
- **Kommentare:** Antwortentwürfe mit menschlicher Prüfung. Beschwerden, sensible oder unklare Anliegen eskalieren; keine automatische Antwort.
- **Betrieb:** Laravel Scheduler, verschlüsselte Secret-Verwaltung, strukturierte Logs, Fehlerbenachrichtigung und manueller Ersatzweg.
- **Kapazität:** Produktionsaufwand getrennt von tatsächlich verfügbaren Beratungsterminen behandeln.

## Annahmen und Grenzen

- Alle Personen, Inhalte, Kennzahlen, Termine und Quellenkennungen sind fiktiv.
- Inspirationsfunde sind keine Trendnachweise; fehlende Kennzahlen werden nicht erfunden.
- Medienreferenzen sind synthetische Strings, keine echten Uploads oder Datei-Scans.
- Publishing nimmt nur lokales JSON entgegen.
- Für produktiven Parallelbetrieb wäre zusätzlich ein Outbox-/Retry-Mechanismus mit Statusmodell und Überwachung vorzusehen. Die Unique-Constraint bleibt die Idempotenzsicherung.
- Die Demo ist bewusst klein und kein produktionsreifes Social-Media-Management-System.

## KI-Werkzeuge und Bearbeitungszeit

ChatGPT wurde zur Anforderungsstrukturierung, Architekturabwägung, Erstellung von Codeentwürfen und Review-Checklisten verwendet. Der KI-Provider im Prototyp ist ein deterministischer Fake und führt keine externen Requests aus.

**Tatsächliche Bearbeitungszeit:** vor Abgabe anhand der eigenen Arbeitszeit eintragen. Die vorgegebene Zeitbox nicht überschreiten. Offene Punkte sind oben ausdrücklich gekennzeichnet.
