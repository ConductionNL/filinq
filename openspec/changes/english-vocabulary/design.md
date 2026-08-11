## Context

docudesk's schema surface is the smallest in the fleet — **2 schemas / 1 Dutch
property** — but its *code* surface is not: **2 files, 2 classes and 12 methods** carry
Dutch identifiers, concentrated in `DossierController`, `DossierCheckedOnListener` and
`GrondslagenSummaryService`.

The change is listed separately from the trivial ones for two reasons: `zaakId` is a
cross-app foreign key that docudesk cannot rename alone, and `dossier` was flagged as a
possible fleet-level name collision with procest's `Zaak`.

## Goals / Non-Goals

**Goals:**

- Resolve the `dossier` / `zaak` collision and rename the schema accordingly.
- Rename the Dutch code identifiers, which outnumber the schema hits twelve to one.
- Hold `zaakId` for the coordinated window with procest and openconnector.

**Non-Goals:**

- Landing `zaakId` → `caseId` in this change on its own.
- Renaming Woo article references or legal-basis identifiers that name published law.

## Decisions

### 1. `dossier` and `zaak` are NOT the same concept — no collision

The fleet policy flagged a possible collision: two Dutch words that both translate to
"case", which would give the fleet two different schemas named `Case`. Reading
docudesk's own schema settles it:

> *"Een dossier representeert een Nextcloud-map (`@self.folder`) waarvan de inhoud onder
> één of meer **Woo Art. 5 grondslagen geanonimiseerd** wordt."*
> — properties: `name`, `description`, `bases`, `checkedOn`

docudesk's `dossier` is **a folder whose contents are anonymised under Woo Art. 5**. It
is a redaction unit, not a case. Note also that its four properties are *already
English* — only the schema name is Dutch.

**Decision:** `dossier` → `RedactionDossier`, carrying a marker recording the Woo Art. 5
basis. No relation to procest's `Case`, and no coordination needed for this half.

The word "dossier" is retained inside the English name deliberately: it is current
English usage for a case file, and `RedactionDossier` says precisely what the object is
in a way `RedactionFolder` would not.

### 2. `zaakId` is held, not renamed

`generatedDocument.zaakId` describes itself as *"UUID van de gekoppelde zaak in
Procest"* — a foreign key into another app. openconnector holds the same key with the
same meaning. Its `title` already reads `Case ID`.

**Decision:** `zaakId` → `caseId`, but **not in this change**. procest owns the name and
renames first; docudesk and openconnector follow in the same window.

This is the one part of docudesk's change that can break another app, and it is the part
that no test in either app would catch — every consumer reads with `??`, so a
desynchronised key yields `null` rather than an error.

### 3. The code layer is the real work, and `Grondslagen` is the interesting case

Twelve of the fourteen hits are method names. Most are mechanical
(`looksLikeDossier`, `isDossierObject`, `walkDossierFiles` → `…RedactionDossier…`), but
`GrondslagenSummaryService` is not.

*Grondslagen* are **legal bases** — specifically the Woo Art. 5 exception grounds under
which content is redacted. Per the ratified Woo rule, FOI concepts are internationalised
rather than preserved: `GrondslagenSummaryService` → `LegalBasesSummaryService`, and
`renderDossierSummary` / `loadDossierContext` / `aggregateForDossier` follow the schema
rename.

⚠️ The *values* are a different matter. If the service stores or emits Woo article
identifiers (`artikel 5.1.2.e` and similar), those name published Dutch law and are
preserved — the same key-versus-value split that governs openconnector's adapter layer.

### 4. `inferDossier` lives in the frontend store

One hit is in `src/store/modules/anonymization.js`, not in PHP. A rename that stops at
the PHP boundary leaves the Vue store calling a concept by a name the backend no longer
uses. The frontend is in scope.

## Risks / Trade-offs

- **`zaakId` is renamed unilaterally** → procest and openconnector desynchronise
  silently. Mitigated by holding it out of this change entirely and recording the block.
- **A Woo article identifier is internationalised along with the method names** → the
  redaction bases stop matching published law. Mitigated by the key/value split: rename
  identifiers, preserve legal citations.
- **The rename stops at PHP** → `inferDossier` in the Vue store keeps the old vocabulary
  and the two layers disagree. Mitigated by including `src/` in scope.
- **Stored `dossier` objects keep the old schema name** → renaming a schema is a data
  migration, not a text edit. The object count must be measured before landing, counting
  only rows where the soft-delete marker is null, and reading the per-schema shard table
  rather than the shared objects table.

## Migration Plan

1. Measure the stored `dossier` object count.
2. Rename the schema `dossier` → `RedactionDossier` with the Woo Art. 5 marker.
3. Rename the 2 classes, 2 files and 12 methods, including the Vue store; preserve Woo
   article identifiers.
4. Migrate stored objects if the count is non-zero.
5. `l10n/nl.json`, `check-l10n`, gates.
6. **Separately, in the procest window:** `zaakId` → `caseId`.

**Rollback:** steps 2–5 are app-local. Step 6 is not independently reversible — it moves
with procest and openconnector or not at all.

## Open Questions

- Does `GrondslagenSummaryService` emit Woo article identifiers as data? If so they are
  preserved; the code identifiers are renamed either way, but the answer decides whether
  a value-level exemption needs recording in the schema.
- How many `dossier` objects exist on the dev and any customer instance? Unmeasured, and
  it decides whether step 4 exists at all.
