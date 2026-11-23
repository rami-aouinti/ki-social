# KI Social — KI-gestützter Social-Media-Workflow

**Assessment-Prototyp · fiktiver Optiker · keine externen Dienste**

## Ziel

Ein kleiner, nachvollziehbarer Laravel-Prototyp für Wochenplanung, Content-Briefing, menschliche Freigabe und die simulierte Übergabe von Social-Media-Beiträgen. Verwendet werden ausschließlich synthetische Daten und `example.com`-Adressen.

## Technologie und Architektur

- **PHP 8.4 / Laravel 12**: schnelle Umsetzung mit testbaren Anwendungsservices.
- **SQLite**: keine zusätzliche Datenbankinstallation für die lokale Demo.
- **Contract-orientierte Adapter**: `AiContentGenerator` und `PublishingGateway` trennen Anwendungslogik von externen Diensten.
- **Fake AI Provider**: deterministische Testantworten ohne API-Key, Kosten oder externe Requests.
- **SHA-256-Fingerprint**: Freigabe gilt nur für die exakte Version von Titel, Text, Medienreferenz, Kanälen und Termin.
- **Idempotency Key**: Wiederholte Übergabe derselben freigegebenen Version erzeugt keinen zweiten lokalen Übergabedatensatz.
- **Docker Compose**: reproduzierbarer lokaler Start.

Vereinfachter Ablauf:

```text
weekly-input.json
      |
      v
WeeklyPlanningService ---> weekly-plan.json + weekly-report.txt
      |
      v
BriefingService ---------> AiContentGenerator (Fake)
      |
      v
WorkflowController
      |-- ApprovalService -> SHA-256-Freigabe
      |-- PublishingService -> PublishingGateway (Fake)
                               |
                               +--> lokaler Handoff-Datensatz
                               +--> lokales JSON-Payload
```

Controller übernehmen HTTP-Validierung und delegieren an Services. Die fachlichen Regeln liegen in den Services; die Fake-Adapter sind über Laravel-Contracts austauschbar.

## Start

Voraussetzungen: Docker und Docker Compose.

```bash
git clone https://github.com/rami-aouinti/ki-social.git
cd ki-social
cp .env.example .env
docker compose up --build
```

Die Anwendung ist danach unter http://localhost:8000 erreichbar. Docker installiert Composer-Abhängigkeiten, erstellt die SQLite-Datei und führt Migrationen aus. Der in Compose hinterlegte Fallback-App-Key ist nur für diese lokale Demo gedacht.

Ohne Docker (PHP 8.2+, Composer und SQLite-Erweiterung):

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan serve
```

## Drei reproduzierbare Beispiele

### 1. Wochenplanung

Die Eingabe liegt in `data/weekly-input.json`. Die fünf Themen werden aus dieser Datei gelesen und validiert: drei Prioritäten und zwei Reserven. Die Kapazitätsrechnung berücksichtigt zunächst nur die drei fest eingeplanten Prioritäten.

```bash
docker compose exec app php artisan social:plan
```

Ohne Docker: `php artisan social:plan`.

Erzeugt:

- `storage/app/results/weekly-plan.json`
- `storage/app/results/weekly-report.txt`

Beispielausgaben im Repository:

- [examples/weekly-plan.sample.json](examples/weekly-plan.sample.json)
- [examples/weekly-report.sample.txt](examples/weekly-report.sample.txt)

### 2. Content-Briefing

Das Briefing wird aus einer Idee des zuvor erzeugten Wochenplans erstellt:

```bash
docker compose exec app php artisan social:briefing IDEA-001
```

Ohne Docker: `php artisan social:briefing IDEA-001`.

Erzeugt `storage/app/results/briefing-IDEA-001.json`. Das Briefing enthält Hook, Caption, Zielgruppe, Tonalität, Produktionsshots, Text-Overlays, Cover-Idee, CTA, zwei unterschiedliche Kanalvarianten und Aufwand. Die KI-Antwort ist ausdrücklich simuliert.

Beispiel: [examples/briefing.sample.json](examples/briefing.sample.json).

### 3. Freigabe und simulierte Übergabe

Die API bildet den Workflow ab:

1. `POST /api/posts` — Entwurf mit Titel, Text, Medienreferenz, Kanälen, Termin und zuständiger Person anlegen.
2. `POST /api/posts/{id}/submit` — vollständigen Entwurf zur Prüfung einreichen.
3. `POST /api/posts/{id}/approve` — aktuelle Version mit Reviewer-Anzeigename freigeben.
4. `POST /api/posts/{id}/handoff` — freigegebenen Inhalt an das Fake-Gateway übergeben.
5. Derselbe Handoff-Aufruf kann wiederholt werden; der vorhandene Übergabedatensatz wird zurückgegeben.

Beispiel für einen lokalen Durchlauf (die ID aus der Antwort des ersten Aufrufs in den folgenden URLs einsetzen):

```bash
curl -X POST http://localhost:8000/api/posts \
  -H 'Content-Type: application/json' \
  -d '{
    "idea_id":"IDEA-001",
    "title":"Welche Brillenform passt zu dir?",
    "body":"Die richtige Fassung verbindet Sehkomfort und persönlichen Stil. Lass dich persönlich beraten.",
    "media_reference":"media/demo-reel-v1.mp4",
    "channels":["instagram","linkedin"],
    "scheduled_at":"2026-11-24T10:00:00+01:00",
    "assigned_person":"A"
  }'

# <id> durch die zurückgegebene Post-ID ersetzen:
curl -X POST http://localhost:8000/api/posts/<id>/submit
curl -X POST http://localhost:8000/api/posts/<id>/approve \
  -H 'Content-Type: application/json' \
  -d '{"reviewer":"Demo Reviewer"}'
curl -X POST http://localhost:8000/api/posts/<id>/handoff
```

Die Übergabe erzeugt lokal `storage/app/publishing/handoff-{id}.json`. Das Payload-Beispiel steht unter [examples/handoff-payload.sample.json](examples/handoff-payload.sample.json).

**Wichtig:** Es gibt keine Instagram-, Facebook-, TikTok- oder LinkedIn-Anbindung und keine echte Veröffentlichung. Die Medienreferenz ist ein synthetischer String, kein tatsächlicher Upload. Der Reviewer ist ein übermittelter Anzeigename, keine authentifizierte Benutzeridentität.

## Wochenplanung und Kapazität

Die Beispieldaten enthalten 90 Minuten für Person A und 150 Minuten für Person B. Für die drei priorisierten Ideen sind 55 Minuten für A sowie 135 Minuten für B eingeplant. Damit bleiben A 35 Minuten und B 15 Minuten. Die beiden Reservethemen werden separat ausgewiesen und belasten das feste Wochenbudget zunächst nicht.

Die Themen, Prioritäten, Kanäle, Formate, Verantwortlichen, Termine, Aufwände und Begründungen kommen aus `data/weekly-input.json`. Die Auswahl ist damit deterministisch und konfigurierbar, aber keine automatisch erzeugte KI-Rangfolge. Eine Budgetüberschreitung führt zu einer sichtbaren Warnung; verfügbare Zeit wird nicht stillschweigend erhöht.

Inspirationsquellen dienen nur als Kontext. Fehlende Kennzahlen bleiben `null`; die Quellen werden nicht als verifizierte Trends dargestellt.

## Tests

```bash
composer validate --no-check-publish
php artisan test
```

Die Tests prüfen insbesondere:

- wiederholte Übergabe erzeugt keinen doppelten Handoff;
- fehlende Freigabe blockiert die Übergabe;
- fehlende Medienreferenz blockiert bereits die Einreichung zur Prüfung;
- Textänderung nach Freigabe entwertet die vorherige Freigabe;
- ein vom Gateway abgelehnter Handoff wird nicht als erfolgreich gespeichert;
- der Plan übernimmt Themen aus der strukturierten Eingabe und macht Budgetüberschreitungen sichtbar.

Die Testmatrix und der Platz für die tatsächlichen lokalen Testergebnisse stehen in [examples/test-evidence.md](examples/test-evidence.md). CI-Status: [GitHub Actions](https://github.com/rami-aouinti/ki-social/actions).

## Implementiert, simuliert und bewusst ausgeschlossen

| Bereich | Status |
|---|---|
| Fünf Ideen: drei Prioritäten und zwei Reserven | Implementiert; deterministische Auswahl aus JSON |
| Kapazitätsprüfung und Warnungen | Implementiert |
| Briefing mit zwei Kanalvarianten | Implementiert; Inhalt vom Fake-AI-Provider |
| Entwurf, Prüfung, Freigabe und Übergabe | Implementiert |
| Versionierung und SHA-256-Fingerprint | Implementiert |
| Idempotente lokale Übergabe | Implementiert |
| Social-Media-Veröffentlichung | Nicht implementiert |
| E-Mail-Versand | Nicht implementiert |
| Analytics, UTM, ROI und Kommentarentwürfe | Nur als mögliche Erweiterungen beschrieben |
| Scheduler, Secret Manager und produktives Monitoring | Nicht implementiert |

## Annahmen und Grenzen

- Alle Personen, Inhalte, Kennzahlen, Termine und Quellenkennungen sind fiktiv.
- Inspirationsfunde sind keine Trendnachweise; fehlende Kennzahlen werden nicht erfunden.
- Es gibt keine echten Medienuploads oder Datei-Scans.
- Das Fake-Publishing-Gateway speichert nur ein lokales JSON-Payload.
- Der Prototyp ruft das Gateway innerhalb einer Datenbanktransaktion auf. Für den lokalen Fake-Adapter ist das ausreichend, aber nicht robust für einen externen Dienst: Ein externer Dienst könnte die Anfrage annehmen, während der lokale Datenbank-Commit anschließend scheitert. Vor einer echten Integration wäre eine Outbox mit separatem Worker, Retry-Status und Überwachung sinnvoll. Der Idempotency Key verhindert doppelte lokale Datensätze, ersetzt aber keine verteilte Transaktionsstrategie.
- Die Demo ist bewusst klein und kein produktionsreifes Social-Media-Management-System.

## KI-Werkzeuge und Bearbeitungszeit

ChatGPT wurde zur Strukturierung der Anforderungen, für Architekturüberlegungen, Codeentwürfe und Review-Checklisten verwendet. Der KI-Provider innerhalb der Anwendung ist ein deterministischer Fake und führt keine externen KI-Requests aus.

**Tatsächliche Bearbeitungszeit:** Bitte vor der Abgabe selbst anhand deiner tatsächlich aufgewendeten Zeit eintragen. Dieser Wert wird nicht geschätzt oder erfunden.
