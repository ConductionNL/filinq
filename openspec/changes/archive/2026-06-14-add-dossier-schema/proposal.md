## Why

DocuDesk anonymises documents per folder ("dossier"), but today a folder has no structured metadata: there is no record of *why* the folder needs anonymisation, *when* it was last reviewed, or *who* signed off. Anonymisation decisions therefore live only in the file tree and the audit trail of individual documents — which makes dossier-level review, re-certification, and reporting to an archivist impossible. Dutch open-government law (Woo Art. 5) requires a recorded *grondslag* (legal basis) for each anonymisation, and we need a first-class object that binds those grondslagen to the folder they apply to.

## What Changes

- Add a new **dossier register** (`dossier`) to `docudesk_register.json`, alongside the existing `document`, `consent`, `signing`, and `templates` registers.
- Introduce a `dossier` schema with:
  - `name` (mirrors the Nextcloud folder name — stored for audit-log stability even if the folder is renamed)
  - `description` (free-text purpose of the dossier)
  - `bases[]` (array of references to `base` objects — the Woo Art. 5 grondslagen that justify anonymisation of this dossier)
  - `checkedOn` (ISO-8601 datetime; setting this field must surface in the audit trail so we know *who* reviewed *when*)
- Introduce a `base` schema with `name` and `description`, representing a single Woo Art. 5 grondslag for anonymisation.
- Seed the `base` register with the 6 canonical Woo Art. 5 uitzonderingsgronden (persoonsgegevens, bijzondere persoonsgegevens, strafrechtelijke gegevens, bedrijfs- en fabricagegegevens, onevenredige benadeling, nationale veiligheid).
- Seed the `dossier` register with 3–5 realistic dossiers (municipality, consultancy, travel agency) per ADR-016.
- Bind dossier objects to Nextcloud folders through OpenRegister's native `@self.folder` metadata — no new DocuDesk endpoint or folder-attachment code is required. Consumers `POST /api/objects/dossier` with `@self.folder` set to the target folder's node ID.
- `bases` cardinality is optional (`array`, no `minItems`) — a dossier may have zero, one, or many grondslagen.

Not in scope (deliberate follow-ups):
- Access-control validation on `@self.folder` (user must be able to read the folder) — tracked as a separate OpenRegister change `validate-self-folder-access`, since it must apply to every register, not only dossiers.
- Backlink from `document` to `dossier` (so documents inside a dossier can navigate up) — deferred until dossier data exists.
- UI for dossier review, approval, and re-certification — deferred; this change is data-model only.

## Capabilities

### New Capabilities

- `dossier-register`: Defines the DocuDesk dossier register, its two schemas (`dossier`, `base`), the `@self.folder` binding contract, and the `bases[]` reference semantics. Covers seed data for the six Woo Art. 5 grondslagen and realistic dossier examples.

### Modified Capabilities

None. The dossier register is additive. No existing DocuDesk spec (`anonymization`, `document-register`, `metadata-enrichment`, …) defines dossier-level metadata, so no existing requirement changes.

## Impact

**Affected code (DocuDesk):**
- `lib/Settings/docudesk_register.json` — add the `dossier` register entry with the two new schemas and their seed objects (per ADR-013 loadable-template envelope).
- No PHP code change. Folder binding is handled entirely by OpenRegister's existing `@self.folder` pipeline (`RegisterService::saveObject` → `FolderManagementHandler::createObjectFolderById`).

**Affected code (OpenRegister):** None in this change.
- The existing `@self.folder` hydration path already accepts a folder node ID via the object payload and associates the object with that folder (`FolderManagementHandler::createObjectFolderById` returns the existing folder when `$objectEntity->getFolder()` is non-empty).
- A separate OpenRegister change — `validate-self-folder-access` — will add access-control for arbitrary `@self.folder` values. That change is not a prerequisite: until it lands, dossier folder binding relies on the same trust model as every other `@self.folder` write today.

**Affected downstream apps:** None.

**APIs / dependencies:**
- HTTP API: new endpoints surface automatically via OpenRegister's generic `/api/objects/{register}` routes — `POST /api/objects/dossier`, `GET /api/objects/dossier/{uuid}`, etc. No new OCS controllers.
- DI: `ObjectEntityService` (OpenRegister) continues to handle all CRUD. DocuDesk does not need a `DossierService`.

**Data / migrations:**
- Running DocuDesk's `RegistersLoader` repair step applies the new register and schemas, plus seed objects (per ADR-013).
- No database schema migration; everything lives in OpenRegister's existing `object` table.

**Architectural alignment:**
- ADR-006 (Schema Standards): `dossier` and `base` use PascalCase-named schemas, schema.org-aligned vocabulary, and explicit types — no free-form JSON.
- ADR-011 (Deduplication): `bases` uses OpenRegister's native `$ref` mechanism rather than a bespoke join.
- ADR-013 (Loadable Register Templates): all schema + seed data ships via `docudesk_register.json` envelopes.
- ADR-016 (Mandatory Seed Data): seed objects cover municipality / consultancy / travel agency personas for the dossier schema; the `base` schema is seeded with the real-world canonical grondslagen.
