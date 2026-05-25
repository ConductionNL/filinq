# Dossier Register

The dossier register binds a Nextcloud folder to its anonymisation context: which Woo Art. 5 grondslagen apply, when it was last reviewed, and the free-text description of the dossier's purpose. It is the structural backbone for the anonimiseren-bij-de-bron flow — every other change in that workstream reads or writes against dossier objects.

## Schemas

Two schemas land via `docudesk_register.json` (added by the `add-dossier-schema` change). No new PHP code; folder binding and CRUD ride on OpenRegister's existing `@self.folder` pipeline and the generic `/api/objects/{register}` routes.

### `dossier` schema

| Field | Type | Required | Notes |
|---|---|---|---|
| `name` | string | yes | Operator-facing label — copied from the Nextcloud folder name at create-time, kept for audit stability even if the folder is renamed. |
| `description` | string | no | Free-text purpose of the dossier. |
| `bases` | `string[]` | no | Array of slug strings referencing `base` objects (the legal grondslagen). Consumer-side resolution against the `dossier/base` register — OR doesn't validate that slugs resolve, doesn't block deletion of a referenced base. v1 trade-off, see design D1. |
| `checkedOn` | date-time | no | When the dossier was last reviewed by an operator. `null` until first review. |

Folder binding: `@self.folder` is the Nextcloud folder node ID. CRUD inherits OpenRegister's existing validation + audit-trail; no DocuDesk-side controller is required. Operators interact via `POST /api/objects/dossier`, `GET /api/objects/dossier/{uuid}`, `PUT /api/objects/dossier/{uuid}`.

### `base` schema

| Field | Type | Required | Notes |
|---|---|---|---|
| `name` | string | yes | Dutch end-user label (e.g. "Persoonsgegevens"). |
| `description` | string | yes | Dutch explanation referencing Woo Art. 5 / AVG articles. |

The six canonical Woo Art. 5 uitzonderingsgronden ship as seed objects with stable slugs:

| Slug | Woo Art. 5 reference |
|---|---|
| `persoonsgegevens` | Art. 5.1 Woo jo. AVG Art. 4 lid 1 |
| `bijzondere-persoonsgegevens` | Art. 5.1 Woo jo. AVG Art. 9 |
| `strafrechtelijk` | Art. 5.1 Woo jo. AVG Art. 10 |
| `bedrijfs-fabricagegegevens` | Art. 5.1 sub c Woo |
| `onevenredige-benadeling` | Art. 5.2 Woo |
| `nationale-veiligheid` | Art. 5.1 sub a/b Woo |

The seeded objects are not enforced as immutable in v1 — OpenRegister does not currently gate instance writes by a schema/object flag; the contract is operator-discipline + audit-log. Tenants MAY add their own grondslagen alongside the seeded ones in the same register.

## Seed dossiers

Five seed dossiers ship across the three personas:

| Slug | Persona | Bases | Notes |
|---|---|---|---|
| `demostad-woo-2025-017` | Gemeente Demostad | `persoonsgegevens`, `onevenredige-benadeling` | Woo-verzoek — subsidietoekenningen cultuur |
| `demostad-wmo-2024` | Gemeente Demostad | `persoonsgegevens`, `bijzondere-persoonsgegevens` | WMO-bezwaarschriften (medische onderbouwing) |
| `conduction-demo` | Conduction B.V. | `persoonsgegevens` | Synthetic referentieproject voor klantendemonstraties |
| `zonnestraal-klachten-2025` | ReisBureau Zonnestraal | `persoonsgegevens`, `bedrijfs-fabricagegegevens` | Klachtendossier zomerseizoen |
| `zonnestraal-incident-2026-03` | ReisBureau Zonnestraal | `[]` | Draft dossier — grondslagen nog te bepalen; `checkedOn: null` |

The last entry exercises the optionality cases: empty `bases` + null `checkedOn`. Consumer code and the review UI MUST handle both as valid in-progress states.

## Relation to other changes

- **`entity-relation-grondslagen`** (OpenRegister) — the `EntityRelation.bases` column stores UUID references to `base` objects from this register. Cross-register references are not validated by OpenRegister (consumer-owned vocabulary).
- **`anonymisation-grondslagen-summary`** (DocuDesk) — renders per-document and per-dossier summaries that resolve `EntityRelation.bases` UUIDs back to `base.name` via this register.
- **`anonymisation-output-folder-layout`** (DocuDesk) — anonymised outputs land in `<dossier-folder>/anonymised/`, where `<dossier-folder>` is the dossier's `@self.folder`.

## Spec references

- Capability: [`openspec/changes/add-dossier-schema/specs/dossier-register/spec.md`](../../openspec/changes/add-dossier-schema/specs/dossier-register/spec.md)
- Design (D1–D9, seed data, migration plan, risks): [`openspec/changes/add-dossier-schema/design.md`](../../openspec/changes/add-dossier-schema/design.md)
- ADR-013 (Loadable register templates) — the docudesk_register.json envelope
- ADR-016 (Mandatory seed data) — covers personas + realistic seed values
