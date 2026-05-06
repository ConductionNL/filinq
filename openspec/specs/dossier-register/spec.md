# Dossier Register Specification

**Status**: in-progress
**Scope**: docudesk
**OpenSpec changes**:
- [add-dossier-schema](../../changes/add-dossier-schema/)

## Purpose

Defines the DocuDesk dossier register: a dedicated OpenRegister register that stores folder-level anonymisation metadata. A dossier is a Nextcloud folder whose contents are being anonymised; the `dossier` schema captures the folder's purpose (`name`, `description`), its legal basis for anonymisation (`bases[]` — references to `base` objects representing Woo Art. 5 grondslagen), and its review timestamp (`checkedOn`). The `base` schema holds the canonical Dutch legal grondslagen as an immutable, reusable vocabulary. Folder binding reuses OpenRegister's native `@self.folder` metadata contract — no bespoke DocuDesk endpoint. See ADR-006 (Schema Standards), ADR-013 (Loadable Register Templates), and ADR-016 (Mandatory Seed Data).

## Requirements

_(Requirements are defined in the in-progress change delta at [changes/add-dossier-schema/specs/dossier-register/spec.md](../../changes/add-dossier-schema/specs/dossier-register/spec.md). They will be folded into this canonical spec when the change is archived.)_

## Non-Functional Requirements

- **Performance:** Dossier CRUD is bounded by OpenRegister's existing object-store performance — no new hot paths introduced.
- **Accessibility:** Any future dossier UI MUST meet WCAG AA via NL Design System tokens (ADR-003).
- **Internationalization:** Dutch and English MUST be supported (ADR-005). The six `base` seed objects are authored in Dutch; English translations of their `name` and `description` are required, while `slug` remains canonical (Dutch kebab-case).

## Acceptance Criteria

- [ ] `dossier` register exists in `lib/Settings/docudesk_register.json` with schemas `dossier` and `base`.
- [ ] Six canonical Woo Art. 5 grondslagen are installed as immutable seed `base` objects.
- [ ] At least three seed `dossier` objects cover the ADR-016 personas (municipality, consultancy, travel agency).
- [ ] `bases` field on `dossier` uses `items.$ref: "#/components/schemas/base"` and is walked by `ReferentialIntegrityService`.
- [ ] `@self.folder` binding works against existing folder node IDs without creating new folders.
- [ ] `checkedOn` updates produce actor/timestamp entries in the OpenRegister audit trail.

## Notes

- Access control on `@self.folder` writes is not covered by this spec; it is deferred to a separate OpenRegister change (`validate-self-folder-access`) that will apply to every register, not only `dossier`.
- One-folder-to-many-dossiers is not prevented at schema level; uniqueness, if needed later, is a UI concern first and a schema constraint only if that proves insufficient.
- The stored `dossier.name` mirrors the folder name at creation time and is intentionally not re-synced on folder rename — audit-log stability takes priority over liveness. A future change may add an on-demand "refresh from folder" action.
