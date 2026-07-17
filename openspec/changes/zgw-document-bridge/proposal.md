---
kind: code
---

# Proposal: zgw-document-bridge

## Why

Dutch municipalities do not keep their case documents in Nextcloud — they keep
them in a zaaksysteem, and every recent anonymisation/publication tender makes
that a hard boundary condition. Dordrecht/Drechtsteden 407973 requires couplings
with **Rx.Enterprise, Djuma (ZGW), StUF-ZDS and generic ZGW Documenten API**
sources with a **≤24h sync freshness** requirement; Den Helder 306597/297564
requires a zaaksysteem.nl (xxllnc) coupling; Arnhem 407824 states explicitly
that **originals stay in the zaaksysteem** — the anonymisation tool processes
copies and the zaaksysteem remains the master record. GH #96 counts 109 tenders
that require document handling against case systems. Today DocuDesk can only
process files that already live in Nextcloud folders: there is no staging
model, no provenance, no write-back contract and no per-source health surface,
which disqualifies DocuDesk from every one of these tenders.

The connector mechanics already exist on the platform side: OpenConnector owns
the Source → Synchronization → SynchronizationContract triad (bidirectional
pull/push, file handling, `stuf-adapter`, per-object provenance via
`synced-from-tab`). What is missing is the **DocuDesk-side contract**: the
OR-backed staging register OpenConnector syncs into, the processing-status
model OpenConnector's push leg reacts to, the dossier pick-up hooks, and the UI
(source badge, admin bridge-status panel).

## What Changes

- New `bridge` register in `lib/Settings/docudesk_register.json` with two
  schemas:
  - `bridgeSource` — one object per configured external case-system source
    (display name, source type `zgw-drc`/`stuf-zds`, vendor label, reference to
    the OpenConnector synchronization, sync telemetry, computed health).
  - `externalDocument` — one staging record per synced informatieobject
    (external identifiers, ZGW metadata, staged-file reference, content hash,
    processing status, write-back result).
- A documented, versioned **bridge contract** between OpenConnector and
  DocuDesk: OpenConnector writes/updates `externalDocument` objects (inbound
  leg) and picks up objects in status `ready_for_writeback` (outbound leg);
  DocuDesk never talks ZGW/StUF itself.
- **Write-back decision (documented)**: the redacted derivative is delivered to
  the DRC as a **new informatieobject** related to the same zaak; the original
  informatieobject is never modified or deleted — the zaaksysteem stays master.
- Dossier pick-up: external documents can be attached to a DocuDesk dossier and
  flow through the existing anonymize → consent → publish capabilities working
  on the staged copy.
- ≤24h freshness SLA: per-source health (`fresh`/`stale`/`failing`) computed
  from sync telemetry.
- UI: a source badge ("Zaaksysteem: <vendor>") on externally-sourced documents
  in MyDocuments/document detail, and a bridge status panel in DocuDesk admin
  settings showing per-source connection health.
- Seed data: one demo `bridgeSource` + staged `externalDocument` examples.

## Capabilities

### New Capabilities

- `zgw-document-bridge`: DocuDesk-side contract for processing documents
  mastered in an external case system — staging register schemas, the
  OpenConnector sync contract (inbound staging + outbound write-back of the
  redacted derivative as a new informatieobject), processing-status model,
  dossier pick-up, ≤24h freshness health, source badge and admin bridge-status
  panel.

### Modified Capabilities

<!-- none — the dossier, anonymization and consent capabilities are consumed
     unchanged; the bridge feeds them staged Nextcloud files. -->

## Impact

- `lib/Settings/docudesk_register.json`: new `bridge` register + 2 schemas +
  seed objects (register version bump).
- New `lib/Service/BridgeService.php` (staging queries, status transitions,
  health computation) and `lib/Controller/BridgeController.php` with routes
  under `api/bridge/*` (sources health, external-document listing, attach to
  dossier, mark ready-for-writeback).
- `src/manifest.json` + `src/views/settings/` (bridge status panel),
  MyDocuments/document detail (source badge).
- Dependency (soft): OpenConnector installed and a synchronization configured
  per source; without OpenConnector the bridge register is inert and the admin
  panel shows "no sources configured". No dependency added to `info.xml`.
- No change to OpenConnector code — its synchronization engine is consumed
  as-is; the ZGW/StUF specifics live in OpenConnector source/mapping
  configuration, not in DocuDesk.
- Evidence: Dordrecht/Drechtsteden 407973 (couplings + ≤24h), Den Helder
  306597/297564 (zaaksysteem.nl), Arnhem 407824 ("originals stay in
  zaaksysteem"), GH #96.
