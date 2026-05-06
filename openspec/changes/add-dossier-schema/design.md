## Context

DocuDesk currently treats "dossier" as a synonym for "Nextcloud folder containing documents to anonymise". The folder is the unit of work, but there is no object describing it: no stored legal basis, no review history, no per-folder description, no stable name that survives a rename. The anonymisation audit trail exists at document level, not dossier level.

OpenRegister already supplies the plumbing:
- Objects can be attached to a Nextcloud folder via the `@self.folder` metadata field (a folder node ID as string). `FolderManagementHandler::createObjectFolderById` returns the existing folder when `ObjectEntity::getFolder()` is non-empty, so POSTing an object with `@self.folder` set binds — rather than creates — a folder.
- `SaveObjects::hydrate($object['@self'])` forwards the `folder` field verbatim onto the entity.
- Relations between objects use `$ref` pointers (`property.$ref` for single, `property.items.$ref` for arrays), walked by `ReferentialIntegrityService::extractTargetRef`.
- Loadable register templates (ADR-013) ship schemas + seed objects through a single `_registers.json` / `docudesk_register.json` envelope.

So the work in this change is not "build a folder-binding mechanism". It is "define two schemas, hang them off a new register, and seed the grondslagen". The only non-trivial decision is the shape of the `bases` link and how we reconcile a stored dossier name with the live folder name.

## Goals / Non-Goals

**Goals:**
- A structured `dossier` object per anonymisation folder, carrying the four fields specified in the proposal.
- A reusable `base` vocabulary — the six Dutch Woo Art. 5 grondslagen — that dossiers reference, not inline.
- Folder binding that reuses OpenRegister's existing `@self.folder` contract (no new DocuDesk endpoint).
- Audit-log surface for `checkedOn`: who reviewed, when.
- Seed data that matches the three ADR-016 personas (municipality, consultancy, travel agency) so screenshots and demos are instantly populated.

**Non-Goals:**
- Enforcing that one folder maps to exactly one dossier (no uniqueness constraint on `@self.folder` in this change).
- Access-control on `@self.folder` writes. Tracked as a separate OpenRegister change `validate-self-folder-access`. Until it lands, dossier folder writes trust the caller the same way every other `@self.folder` write does today.
- UI (dossier list, review screen, approve button). This change is data-model only.
- Re-certification / "due for review" scheduling — a dossier has a `checkedOn` field, but nothing in this change decides *when* a re-check is due.
- Document → dossier backlink. Useful, but deferred to a follow-up once real dossiers exist.

## Decisions

### D1. `bases` uses `$ref`, not inline objects

The `bases` field on `dossier` is an array of references to `base` objects via OpenRegister's native `$ref` syntax:

```json
"bases": {
  "type": "array",
  "items": {
    "$ref": "#/components/schemas/base"
  }
}
```

**Rationale:** The six grondslagen are a closed, reusable vocabulary — inlining their definition on every dossier would duplicate the same text dozens of times and make wording changes a search-and-replace. `$ref` is the pattern OpenRegister already walks for referential integrity, so we get validation and backlinks for free.

**Alternative considered:** An `enum` of grondslag codes (`persoonsgegevens`, `bijzondere-persoonsgegevens`, …). Rejected: closes extension (a tenant that needs a custom grondslag can't add one), loses the Dutch-law long descriptions, and doesn't scale to adding fields to a grondslag later (e.g. a link to the wetstekst).

### D2. `name` is stored, not resolved live

The dossier's `name` mirrors the folder name **at creation time**. It is not re-resolved from the Nextcloud node on every read.

**Rationale:** Audit-log stability. The audit trail logs `dossier.name = "2024-Q3 klachten"` — if someone renames the folder a year later, the audit record must still say what the dossier was called when the review happened. The live folder name is always accessible via `@self.folder` for callers who need the current name.

**Trade-off:** A stored name can drift from the folder name. We accept the drift; a future change can add a "refresh name from folder" button or a scheduled sync job if drift turns out to hurt.

### D3. `checkedOn` is the only review field; the rest lives in the audit trail

The schema has `checkedOn` (ISO-8601 datetime) and nothing else review-related — no `checkedBy`, no `reviewNotes`, no `reviewStatus`.

**Rationale:** OpenRegister's audit trail already records *who* updated an object and *when*. Duplicating that into schema fields creates two sources of truth that can disagree. Querying "who checked this dossier last and when?" becomes: look up the latest audit-trail entry where `checkedOn` changed. That answer is authoritative; a `checkedBy` field could be overwritten by any mapper that didn't know about it.

**Trade-off:** Reading "last reviewer" requires an audit-trail lookup, not a plain object field. The audit-trail mapper already supports the lookup pattern (`AuditTrailMapper::findByObject`), so this is a few lines of caller code, not new infrastructure.

### D4. `bases` is optional (no `minItems`)

A dossier may be created before its grondslag is decided, or may genuinely have none yet (e.g. a draft dossier).

**Rationale:** Forcing `minItems: 1` at schema level blocks the "create the dossier first, fill in grondslagen during review" workflow — which is the natural workflow for archivists. If a future UI wants to enforce "approved dossiers must have ≥1 grondslag", that's a UI/state rule, not a schema rule.

### D5. New register, not an extension of `document`

The dossier schemas live in a new `dossier` register, not appended to the existing `document` register.

**Rationale:** `document` is about individual documents; `dossier` is about folders-of-documents and legal bases. Different concerns, different access patterns, different lifecycles (a dossier outlives the documents it contains as they move in and out). Bundling them into one register makes the register's purpose muddy and makes access-control harder to reason about.

**Alternative considered:** Put the schemas in the `document` register. Rejected per user feedback: mixing dossier-level metadata with document records makes the register's contract unclear.

### D6. Seed data lives in `docudesk_register.json`, not a separate loader

Both the six `base` objects and the 3–5 seed `dossier` objects are embedded in `docudesk_register.json` under `components.objects` (or the loader's equivalent array), consistent with ADR-013 and the way existing DocuDesk seed data ships.

**Rationale:** One file = one source of truth. `RegistersLoader` already applies this file on install/upgrade; piggybacking on it means no new code path.

## Risks / Trade-offs

**[Orphan folder bindings]** — If a user deletes the Nextcloud folder referenced by `@self.folder`, the dossier is left pointing at a stale node ID.
→ Mitigation: acceptable for this change. OpenRegister already has the same risk for every object that sets `@self.folder`, so this isn't dossier-specific. The eventual `validate-self-folder-access` change can add a "folder no longer exists" check at read time.

**[Two dossiers on one folder]** — Nothing in this change prevents two dossier objects from sharing a folder.
→ Mitigation: acceptable for now. Deduplication is a UI concern (show a warning if the target folder already has a dossier). Adding a schema-level uniqueness constraint would require OpenRegister work that isn't justified by current use cases.

**[Grondslag vocabulary drift]** — The six canonical grondslagen are seeded once. If a tenant edits or deletes a seed object, all dossiers referencing it carry a dangling or mutated reference.
→ Mitigation: mark the seed grondslag objects as `immutable: true` on the `base` schema so the six canonical entries can't be changed after install. Tenants add their own custom grondslagen alongside them — this covers extension without risking the baseline.

**[Name drift]** — Per D2, the stored name can disagree with the folder name.
→ Mitigation: document the intent (audit stability over liveness) in the schema's `description` so UI builders know not to "helpfully" re-sync names on render.

**[Access control, still unresolved]** — `@self.folder` writes are not validated.
→ Mitigation: covered by the separate `validate-self-folder-access` change in OpenRegister. Not blocking this change; this change makes that follow-up more valuable, not more urgent.

## Seed Data

Per ADR-016, seed objects cover the three personas: a Dutch municipality ("Gemeente Demostad"), a consultancy ("Conduction B.V." sample project), and a travel agency ("ReisBureau Zonnestraal"). The seed set exercises the full schema — dossiers with zero, one, and multiple `bases`; realistic `checkedOn` dates; descriptions that match real-world anonymisation scenarios.

### `base` register — 6 canonical Woo Art. 5 grondslagen

These are the *fixed* vocabulary from Wet open overheid Art. 5. Shipped as `immutable: true` seed objects so they cannot be edited after install. The `slug` is stable and used by the UI for icon / colour mapping; `name` and `description` are Dutch end-user strings.

| slug | name (NL) | description (NL, excerpt) |
|---|---|---|
| `persoonsgegevens` | Persoonsgegevens | Herleidbare gegevens van een natuurlijke persoon (Art. 5.1 Woo jo. AVG Art. 4 lid 1). Denk aan namen, adressen, BSN, contactgegevens, functie gekoppeld aan persoon. |
| `bijzondere-persoonsgegevens` | Bijzondere persoonsgegevens | Gegevens over ras/etniciteit, politieke opvattingen, religie, lidmaatschap vakbond, genetica/biometrie, gezondheid, seksueel leven of geaardheid (Art. 5.1 Woo jo. AVG Art. 9). Strikte anonimiseringsplicht. |
| `strafrechtelijk` | Strafrechtelijke gegevens | Gegevens over strafrechtelijke veroordelingen en strafbare feiten (Art. 5.1 Woo jo. AVG Art. 10). Alleen onder wettelijke grondslag verwerkbaar; in openbaarmaking altijd geanonimiseerd. |
| `bedrijfs-fabricagegegevens` | Bedrijfs- en fabricagegegevens | Vertrouwelijk meegedeelde bedrijfs- en fabricagegegevens (Art. 5.1 sub c Woo). Beschermt concurrentiepositie — bijv. offertebedragen, receptuur, leveranciersafspraken. |
| `onevenredige-benadeling` | Onevenredige benadeling | Openbaarmaking zou leiden tot onevenredige benadeling van betrokkenen of derden (Art. 5.2 Woo). Brede restcategorie; belangenafweging per geval gedocumenteerd. |
| `nationale-veiligheid` | Nationale veiligheid | Gegevens waarvan openbaarmaking de veiligheid van de Staat of de opsporing en vervolging van strafbare feiten schaadt (Art. 5.1 sub a/b Woo). Zelden van toepassing bij gemeenten. |

### `dossier` register — 3–5 seed dossiers across personas

Each seed dossier shows a realistic anonymisation scenario and references `base` objects via `$ref` (by slug, resolved by OpenRegister's referential-integrity walker). Folder IDs in the seed data use placeholder strings (`seed-folder-<slug>`) that the loader resolves to actual Nextcloud folder node IDs created for the seed registers at install time — same mechanism used by other apps' seed data.

**Seed 1 — Gemeente Demostad (municipality)**

- `name`: "Woo-verzoek 2025-017 — Subsidietoekenningen cultuur"
- `description`: "Woo-verzoek van lokale krant over alle subsidietoekenningen cultuur 2022–2024. Folder bevat 47 beschikkingen, 12 bezwaarschriften en bijbehorende correspondentie. Anonimiseren vóór publicatie op overheid.nl."
- `bases`: [`persoonsgegevens`, `onevenredige-benadeling`] — namen van subsidieontvangers + ondernemingen die om niet-publicatie hebben verzocht.
- `checkedOn`: "2026-03-14T10:22:00+00:00"
- `@self.folder`: `seed-folder-demostad-woo-2025-017`

**Seed 2 — Gemeente Demostad (municipality)**

- `name`: "Bezwaarschriften WMO 2024"
- `description`: "Geanonimiseerd archief van WMO-bezwaarschriften 2024 ten behoeve van kwartaalrapportage Raad. Bevat medische onderbouwing — alles met AVG Art. 9-grondslag."
- `bases`: [`persoonsgegevens`, `bijzondere-persoonsgegevens`]
- `checkedOn`: "2026-02-28T15:45:00+00:00"
- `@self.folder`: `seed-folder-demostad-wmo-2024`

**Seed 3 — Conduction B.V. (consultancy)**

- `name`: "Referentieproject — Anonimiseringsvoorbeelden"
- `description`: "Werkvoorbeelden voor klantendemonstraties. Alle persoonsgegevens zijn synthetisch maar behandeld alsof echt, om anonimiseringsflow end-to-end te tonen."
- `bases`: [`persoonsgegevens`]
- `checkedOn`: "2026-04-01T09:00:00+00:00"
- `@self.folder`: `seed-folder-conduction-demo`

**Seed 4 — ReisBureau Zonnestraal (travel agency)**

- `name`: "Klachtendossier zomerseizoen 2025"
- `description`: "Interne behandeling van klantklachten zomer 2025 t.b.v. verbetertraject. Gedeeld met verzekeraar en branchevereniging — namen klanten + accommodaties anonimiseren."
- `bases`: [`persoonsgegevens`, `bedrijfs-fabricagegegevens`]
- `checkedOn`: "2026-01-20T11:00:00+00:00"
- `@self.folder`: `seed-folder-zonnestraal-klachten-2025`

**Seed 5 — ReisBureau Zonnestraal (travel agency) — draft dossier, no bases yet**

- `name`: "Incident-analyse vlucht AMS-CAN 2026-03"
- `description`: "Nieuw dossier voor anonimiseringstraject — grondslagen nog te bepalen door privacy officer."
- `bases`: [] (empty — demonstrates that `bases` is optional)
- `checkedOn`: null (demonstrates that `checkedOn` is optional until first review)
- `@self.folder`: `seed-folder-zonnestraal-incident-2026-03`

## Migration Plan

1. Merge schema additions into `docudesk_register.json` (new `dossier` register, two new schemas, seeded `base` and `dossier` objects).
2. On `composer run install-deps` → `occ app:enable docudesk` (or upgrade), `ConfigurationService::importFromApp()` loads the updated file. OpenRegister's `RegistersLoader` creates the new register, installs the two schemas, upserts the six `base` seed objects, and creates the five seed dossiers + their folders.
3. Existing installs: the loader is idempotent on `uuid` and `slug`; re-running on upgrade adds the new register without disturbing existing data.
4. Rollback: remove the new register + schemas + seed objects by reverting `docudesk_register.json` and re-running the importer. Since the schemas have no dependencies from other registers at this point, rollback is local to this file.

## Open Questions

- **Folder-level vs. document-level `bases`** — when a dossier has `bases: [persoonsgegevens]` but a single document inside needs an extra grondslag, where does that live? Current answer: ignored for now. Per-document override can be added later via a `bases[]` on the document schema that falls back to the dossier's list.
- **Immutability of the six canonical `base` objects** — we mark the schema `immutable: true`. Do we want to allow tenants to add *their own* grondslagen in the same register, or force them into a separate "custom-base" register? Current answer: same register. Immutability is per-object (via the `immutable` field on the schema), not per-register; the six seeded entries can be flagged immutable while tenant-created entries remain editable. Validate this assumption during apply; if the schema-level `immutable: true` affects all instances, switch to per-object immutability.
