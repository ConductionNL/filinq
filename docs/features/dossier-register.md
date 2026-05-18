# Dossier Register

## Overview

The dossier register gives DocuDesk a structured first-class object per anonymisation folder. Before this feature, a "dossier" was just a Nextcloud folder — there was no record of *why* it needed anonymisation, *when* it was last reviewed, or which Dutch open-government law article (Woo Art. 5) applied.

The register adds two schemas — `dossier` and `base` — to `lib/Settings/docudesk_register.json`, applied automatically via OpenRegister's `RegistersLoader` on install or upgrade. No custom PHP code or database migration is required.

## Register Details

- **Slug**: `dossier`
- **Version**: `1.0.0`
- **Location**: `lib/Settings/docudesk_register.json`
- **Schemas**: `dossier`, `base`
- **Loader**: `ConfigurationService::importFromApp()` → `RegistersLoader` (ADR-013)

## Schemas

### `dossier`

Represents an anonymisation folder at the object level.

| Property | Type | Required | Description |
|---|---|---|---|
| `name` | string | yes | Folder name at creation time — stored for audit-log stability even if the folder is renamed later |
| `description` | string | no | Free-text purpose of the dossier |
| `bases` | array of `$ref base` | no | Woo Art. 5 grondslagen that justify anonymisation of this folder |
| `checkedOn` | date-time | no | ISO-8601 datetime of the last review; *who* reviewed is recorded in the OpenRegister audit trail |

Configuration:
- `objectNameField`: `name`
- `facetable`: `bases`, `checkedOn`

### `base`

A single Woo Art. 5 uitzonderingsgrond (exception/legal basis).

| Property | Type | Required | Description |
|---|---|---|---|
| `name` | string | yes | Dutch end-user label |
| `description` | string | yes | Dutch explanation referencing Woo Art. 5 and, where relevant, AVG articles |

Configuration:
- `objectNameField`: `name`

## `@self.folder` Binding

A dossier is linked to its Nextcloud folder by including `@self.folder` in the POST/PUT payload, set to the folder's node ID (integer, as a string). OpenRegister's existing `FolderManagementHandler::createObjectFolderById` returns the existing folder when the entity already has a folder set — no new DocuDesk code or endpoint is needed.

```http
POST /api/objects/dossier
Content-Type: application/json

{
    "@self": { "folder": "12345" },
    "name": "Woo-verzoek 2025-017",
    "bases": ["<uuid-of-persoonsgegevens>"],
    "checkedOn": "2026-03-14T10:22:00+00:00"
}
```

The `name` is stored at creation time and does NOT automatically re-sync if the folder is renamed. This is intentional: the audit trail must say what the dossier was called when the review happened. The live folder name is always accessible via `@self.folder` for callers who need it.

## `bases` Reference Model

`bases` is an optional array of `$ref` pointers to `base` objects, using OpenRegister's native referential-integrity mechanism (`ReferentialIntegrityService::extractTargetRef`). This means:

- Referential integrity is enforced: deleting a `base` object that is still referenced by at least one dossier is blocked.
- Referencing a non-existent base UUID returns a validation error.
- A dossier may have zero, one, or multiple grondslagen (`minItems` is not enforced).

Rationale: inlining the six grondslagen on every dossier would duplicate Dutch-law wording dozens of times and make wording changes a search-and-replace. `$ref` is the correct OpenRegister pattern for a closed, reusable vocabulary.

## Six Canonical Woo Art. 5 Grondslagen

The six canonical uitzonderingsgronden are seeded as `base` objects with stable slugs used by UI code for icon/colour mapping.

| Slug | Name (NL) | Legal reference |
|---|---|---|
| `persoonsgegevens` | Persoonsgegevens | Woo Art. 5.1 jo. AVG Art. 4 lid 1 |
| `bijzondere-persoonsgegevens` | Bijzondere persoonsgegevens | Woo Art. 5.1 jo. AVG Art. 9 |
| `strafrechtelijk` | Strafrechtelijke gegevens | Woo Art. 5.1 jo. AVG Art. 10 |
| `bedrijfs-fabricagegegevens` | Bedrijfs- en fabricagegegevens | Woo Art. 5.1 sub c |
| `onevenredige-benadeling` | Onevenredige benadeling | Woo Art. 5.2 |
| `nationale-veiligheid` | Nationale veiligheid | Woo Art. 5.1 sub a/b |

These objects are not enforced as immutable in v1 (OpenRegister does not currently gate instance writes by a schema/object flag; only archival status controls that). Editing the seeded objects after install is an operator-discipline matter visible in the audit log. Referential integrity prevents *deletion* of a base while any dossier references it.

## Seed Data

Five seed dossiers are shipped with the register, covering the three ADR-016 personas:

| Slug | Persona | Bases | checkedOn |
|---|---|---|---|
| `woo-verzoek-2025-017` | Gemeente Demostad | persoonsgegevens, onevenredige-benadeling | 2026-03-14 |
| `bezwaar-wmo-2024` | Gemeente Demostad | persoonsgegevens, bijzondere-persoonsgegevens | 2026-02-28 |
| `conduction-demo` | Conduction B.V. | persoonsgegevens | 2026-04-01 |
| `klachten-zomerseizoen-2025` | ReisBureau Zonnestraal | persoonsgegevens, bedrijfs-fabricagegegevens | 2026-01-20 |
| `incident-analyse-2026-03` | ReisBureau Zonnestraal | *(none)* | *(null)* |

The last seed demonstrates the unreviewed/draft state: `bases: []` and `checkedOn: null`.

Folder references in seed data use placeholder strings (`seed-folder-<slug>`) that the loader resolves to real Nextcloud folder node IDs at install time.

## API Surface

The dossier register exposes standard OpenRegister generic routes — no DocuDesk-specific controllers are needed:

| Method | Path | Description |
|---|---|---|
| `GET` | `/api/objects/dossier` | List all dossiers |
| `POST` | `/api/objects/dossier` | Create a dossier (include `@self.folder` to bind a folder) |
| `GET` | `/api/objects/dossier/{uuid}` | Get a single dossier |
| `PUT` | `/api/objects/dossier/{uuid}` | Update a dossier |
| `DELETE` | `/api/objects/dossier/{uuid}` | Delete a dossier |
| `GET` | `/api/objects/base` | List all grondslagen |
| `POST` | `/api/objects/base` | Create a custom grondslag |
| `GET` | `/api/objects/base/{uuid}` | Get a single grondslag |

## `checkedOn` Audit Trail

The design deliberately stores only `checkedOn` (a datetime) and no `checkedBy` field. OpenRegister's audit trail already records *who* updated an object and *when*. Querying "who checked this dossier last?" means looking up the latest audit-trail entry where `checkedOn` changed — via `AuditTrailMapper::findByObject(<uuid>)`. That answer is authoritative; a separate `checkedBy` field would create a second source of truth that could disagree with the audit log.

## Access Control

The `@self.folder` field is written without additional validation in v1: the caller must be an authenticated Nextcloud user, but DocuDesk does not verify that the user has read access to the target folder. This is consistent with how every other `@self.folder` write works in OpenRegister today.

A separate OpenRegister change — `validate-self-folder-access` — will add per-write access validation. That change is tracked as a follow-up and will benefit all apps using `@self.folder`, not only DocuDesk dossiers. See the [Tracking Issue](#validate-self-folder-access-tracking) section below.

## validate-self-folder-access Tracking

DocuDesk's dossier register is the first confirmed consumer of `@self.folder` write semantics that requires access-control enforcement. The OpenRegister change `validate-self-folder-access` should be filed referencing this change as the first use case. Until it lands, any authenticated Nextcloud user can POST a dossier with an arbitrary `@self.folder` value, binding the dossier to any folder they can reference by node ID.

This is accepted residual risk in v1: the same risk exists for every other OpenRegister app using `@self.folder` today. See `openspec/changes/add-dossier-schema/design.md` § Risks / Trade-offs for the full mitigation rationale.
