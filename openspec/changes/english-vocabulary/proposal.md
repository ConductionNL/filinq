# English vocabulary for docudesk

> Implements `hydra/openspec/changes/fleet-english-vocabulary`.

## Why

Scan found **1 Dutch-named schema (`dossier`) and 1 Dutch property (`zaakId`)** —
the smallest slice in the fleet. It is listed separately anyway because `zaakId`
is a **cross-app key**, so docudesk cannot rename it alone.

## What changes

| Dutch | English |
|---|---|
| `dossier` | `Case` or `Dossier`* |
| `zaakId` | `caseId` |

\* ⚠️ **Word collision.** `dossier` and `zaak` are two Dutch words that both map
naturally to "case". procest models `Zaak`; docudesk models `dossier`. If both
become `Case` the fleet has two different schemas with one name. Resolve before
renaming: either docudesk's becomes `DocumentDossier`/`FileDossier`, or the two
concepts are genuinely the same and should be one schema.

This is exactly the collision class that produced shillinq#485 — two fragments
declaring the same schema name with different vocabularies, silently merged into
something no payload could satisfy.

## Tasks

- [ ] Confirm whether docudesk's `dossier` and procest's `Zaak` are the same
      concept. If yes → one schema, one owner. If no → two distinct English names.
- [ ] Rename `zaakId` → `caseId` **in the same coordinated change** as procest,
      openconnector and zaakafhandelapp.
- [ ] Rename the schema; check lib/ + src/ for Dutch in class/method/file names.
- [ ] `l10n/nl.json` + `check-l10n`.
- [ ] Full suite + hydra gates.

## Risks

- ⚠️ `zaakId` is shared across at least four apps. A unilateral rename breaks the
  others silently (consumers read with `??`).
- Naming both `dossier` and `Zaak` as `Case` would create a fleet-level schema
  name collision.
