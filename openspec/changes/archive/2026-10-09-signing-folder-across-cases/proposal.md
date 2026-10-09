---
kind: code
---

# Proposal: a signing folder across cases

One row of the dossiq competitor parity register, entered under decision
D1 out of `dossiq#2314`. A wethouder signing forty decisions on a Friday
afternoon should open one folder, not forty cases. filinq owns signing
for the fleet, so filinq owns the folder.

## The row it closes

### 4.27, signing folder gathering documents from several cases for one signer

Rating for dossiq: `no`.

Source, the ledger `source` field verbatim: `dossiq#2314, published as 4.25`

Corpus batch file `procest/_round4/compare/proposed-rows-dossiq-2026-09-10.md`
in ConductionNL/market-intelligence, the table row verbatim:

```
| proposed | in dossiq | capability | dossiq | competitors | cross-reference |
| **4.27** | 4.25 | Signing folder gathering documents from several cases for one signer | no | unread | corpus 4.12 |
```

The ledger note, dossiq's own evidence, verbatim:

> Qualified signing ships per beschikking, through the signing adapter and the mandaat check. Nothing gathers documents from several cases into one pass, and a wethouder signing forty decisions opens forty cases.

## What the competitor evidence actually is

There is none, and the corpus says so. From
`procest/_round4/compare/proposed-rows-dossiq-2026-09-10.md`:

> **Every competitor column is `unread`, and none of them is `no`.** The corpus columns are
> GLPI, Zammad, OpenProject, Plane, Redmine, Forgejo, osTicket, FreeScout, Znuny, GitLab, OTOBO,
> iTop, Odoo, Deck, Kanboard, Vikunja, RT, Helpdesk, Gitea, Taiga, Tuleap, Huly, JSM, YouTrack,
> Jira DC, Easy Redmine, OpenCase, GZAC, xxllnc Zaken and Dimpact ZAC.
> Not one of them has been read against a row below. `unread` is what the ledger writes for
> that, and the distinction is the whole point: `no` is a reading of a product somebody
> opened, and filling these cells with it would fabricate thirty readings per row.

So no passer is named here. The register carries no `best` string for the
row, and its `covered`, `change` and `carried_by` fields are empty in
`procest/_gaps/gap-register.json`.

The cross-reference is corpus row 4.12, "E-signing", which was read and
which dossiq passes: `yes` in `procest/_round2/compare/M1-functionality.md`,
`lib/Service/Beschikking/LibresignSigningAdapter.php` and
`/api/beschikkingen/{id}/onderteken`. Signing one beschikking works. 4.27
asks the neighbouring question, which is what happens when there are
forty of them on nine cases.

## The ADRs it cites

Opened and read at
`~/nextcloud-docker-dev/workspace/server/apps-extra/hydra/openspec/architecture/`.

- **ADR-070**, OpenRegister-backed persistence is the default. The folder
  is a query over `signingRequest` and `signerRecord`, not a new table
  and not a stored list.
- **ADR-023**, action-level authorization. Whether this person may sign
  this document is an action check, declared by the consuming app and
  applied by filinq, never an `isAdmin()` in a controller body.
- **ADR-066**, cross-app leaf registration. The folder reaches dossiq and
  decidiq as a leaf; filinq carries no verb into either.
- **ADR-038**, canonical requirement heading format, for the shape of the
  requirements below.

## What exists already, and why it is not the row

Two things in filinq are close enough that claiming them would be easy
and wrong.

- The `document-signing` capability on `development` carries a **Bulk
  signing** requirement: `POST /api/signing/bulk` with an array of
  signing request ids, each signed in sequence in one authenticated
  session. `SigningService::bulkSign()` implements exactly that. It signs
  a list the caller already has. Nothing in it finds the list, which is
  the whole of the row: the gathering, not the signing.
- The open change `bulk-signing-field-builder` adds an envelope grouping
  N documents into one ceremony. The envelope is composed by the sender
  at request time, out of one dossier, and its own proposal says so:
  "contract + annexes + processing agreement". It answers "send these
  three together", not "show me everything waiting for my signature".

This change builds on both rather than restating either. The folder is
the query in front of them, and one pass through the folder reuses the
existing per-request path with every gate it already has.

## What filinq builds

- **The folder is a query, for the signer.** Everything with a pending
  signer record for the person asking, across every record and every
  case, in one list, sorted by what is most urgent.
- **Every document carries its context.** Which record it belongs to,
  what it is, who asked, since when, and the deadline if one was
  declared. A signer who cannot see what they are signing signs it
  anyway, which is the failure this requirement exists to prevent.
- **One pass, many documents, each its own record.** Signing the folder
  authenticates once and produces a per-document signature, artifact and
  audit trail, with the same level floors and honest-completion gates a
  single request passes. A refusal on one document is reported against
  that document and does not stop the rest.
- **A document the signer may not sign is not in the folder.** The
  mandate rule is declared by the consuming app per record type and
  applied by filinq. A document outside the mandate is absent from the
  folder, and a direct attempt is refused with a reason naming the rule.
- **The folder is a leaf.** dossiq and decidiq place it; filinq does not
  reach into either.

## What dossiq consumes

dossiq places the folder leaf on its own signing surface and declares the
mandate rule per case type, next to the beschikking path corpus 4.12
already credits. Assigning, deciding and the beschikking itself stay
dossiq's. No dossiq slug carries this half on dossiq `development`, so it
is to be specified in dossiq. decidiq is the second consumer: a
besluitenlijst is the same forty signatures with a different noun.

## Size

M. One query, one context projection, one pass that reuses the existing
per-request path, one declared mandate rule and one leaf. No new signing
mechanism, no new provider, no new artifact.

## The existing spec it extends

`document-signing`. The delta adds requirements beside the existing
**Bulk signing** requirement and changes none of them.

## Out of scope

- The signature itself, the providers and the artifact. Unchanged.
- Envelope grouping composed by the sender. That is
  `bulk-signing-field-builder`.
- The mandate vocabulary. dossiq declares which role signs what.
- Anything about the trust rebuild. `signing-trust-rebuild` owns that,
  and this change adds no path around its gates.
