---
kind: code
---

# Proposal: documents in and out of the building

Three rows of the dossiq competitor parity register, all entered under
decision D1 out of `dossiq#2314`. Each asks what happens at the boundary
of the organisation: what is registered when a document arrives or
leaves, whether the letter that leaves can be understood by the person
who gets it, and whether anybody is told when a copy is taken out.
filinq owns the document half of all three.

## The rows it closes

### 4.26, registration number for every document in and out, scoped to a year and a unit

Rating for dossiq: `no`.

Source, the ledger `source` field verbatim: `dossiq#2314, published as 4.24`

Corpus batch file `procest/_round4/compare/proposed-rows-dossiq-2026-09-10.md`
in ConductionNL/market-intelligence, the table row verbatim:

```
| proposed | in dossiq | capability | dossiq | competitors | cross-reference |
| **4.26** | 4.24 | Registration number for every document in and out, scoped to a year and a unit | no | unread |  |
```

The ledger note, dossiq's own evidence, verbatim:

> The informatieobject already carries a direction, incoming, outgoing or internal, and a registration date. document.identifier has no generator, so there is no gapless year and unit scoped number, and no outbound entry discharging an inbound one.

### 4.28, plain-language version of a generated letter, kept beside the formal one

Rating for dossiq: `no`.

Source, the ledger `source` field verbatim: `dossiq#2314, published as 4.26`

Corpus batch file `procest/_round4/compare/proposed-rows-dossiq-2026-09-10.md`,
the table row verbatim:

```
| proposed | in dossiq | capability | dossiq | competitors | cross-reference |
| **4.28** | 4.26 | Plain-language version of a generated letter, kept beside the formal one | no | unread | corpus 7.4 |
```

The ledger note verbatim:

> Rows 4.4 and 11.11 ask whether a letter can be generated. Nothing asks whether it can be understood, and for a decision carrying a bezwaartermijn that is a legal interest.

### 13.39, notification when a file is downloaded

Rating for dossiq: `no`. Area: access and privacy.

Source, the ledger `source` field verbatim: `dossiq#2314, published as 13.32`

Corpus batch file `procest/_round4/compare/proposed-rows-dossiq-2026-09-10.md`,
the table row verbatim:

```
| proposed | in dossiq | capability | dossiq | competitors | cross-reference |
| **13.39** | 13.32 | Notification when a file is downloaded | no | unread | corpus 13.9 |
```

The ledger note verbatim:

> Row 13.9 logs a read. lib/Notification holds only the notifier and no download listener is registered, so nothing acts on one while it is happening.

## What the competitor evidence actually is

There is none, and the corpus says so in as many words. From
`procest/_round4/compare/proposed-rows-dossiq-2026-09-10.md`:

> **Every competitor column is `unread`, and none of them is `no`.** The corpus columns are
> GLPI, Zammad, OpenProject, Plane, Redmine, Forgejo, osTicket, FreeScout, Znuny, GitLab, OTOBO,
> iTop, Odoo, Deck, Kanboard, Vikunja, RT, Helpdesk, Gitea, Taiga, Tuleap, Huly, JSM, YouTrack,
> Jira DC, Easy Redmine, OpenCase, GZAC, xxllnc Zaken and Dimpact ZAC.
> Not one of them has been read against a row below. `unread` is what the ledger writes for
> that, and the distinction is the whole point: `no` is a reading of a product somebody
> opened, and filling these cells with it would fabricate thirty readings per row.

So no passer is named here, no competitor is credited, and no row below
rests on one. The register carries no `best` string for any of the three;
their `covered`, `change` and `carried_by` fields are empty in
`procest/_gaps/gap-register.json`.

Two of the three carry a cross-reference to a corpus row that was read,
and reading it is what shows the gap is real rather than a restatement.

- Corpus 7.4, "Decision letter generated as a workflow step", dossiq
  `yes` in `procest/_round2/compare/M1-functionality.md`:
  `src/dialogs/BeschikkingComposerDialog.vue`,
  `lib/Service/BeschikkingGenerationService.php`,
  `lib/Flow/DossiqRequestDecisionNode.php`. The letter is generated.
  4.28 asks whether it can be read.
- Corpus 13.9, "Audit trail including reads and views", dossiq `yes` in
  the same file: the CaseDetail sidebar `audit`, OpenRegister audit, with
  `read` rows in the baseline. The read is recorded. 13.39 asks whether
  anybody is told while it is happening.

## The ADRs it cites

Opened and read at
`~/nextcloud-docker-dev/workspace/server/apps-extra/hydra/openspec/architecture/`.

- **ADR-070**, OpenRegister-backed persistence is the default. The
  registration entry, the sequence and the download record are
  OpenRegister objects. filinq adds no table.
- **ADR-031**, schema-declarative business logic over service classes.
  The download notification is declared in the
  `x-openregister-notifications` dialect on the schema, not written as a
  notification service.
- **ADR-078**, post-event listener work is asynchronous to the write. A
  download handler records and enqueues; it never renders a notification
  on the download path.
- **ADR-075**, document generation has one owner and one channel. The
  plain-language rendition leaves through the same generation channel as
  the formal letter, not through a second path.
- **ADR-066**, cross-app leaf registration. The open post list and the
  download history reach a consuming app as leaves.
- **ADR-038**, canonical requirement heading format, for the shape of
  the requirements below.

## What filinq builds

- **A registration act with a direction and a number.** Registering a
  document records its direction, incoming, outgoing or internal, the
  registration date, the organisational unit, and a registration number
  rendered from a sequence scoped to that unit and that year. The number
  is set once and refused afterwards.
- **The number comes from openregister, not from filinq.** openregister's
  open change `generated-identifier` declares
  `x-openregister-generated` with a `sequence`, a `format` carrying
  `{year}` and `{seq:5}`, and `resetOn: year`, taken under a lock. filinq
  declares the annotation and the per-unit sequence name and reads the
  result. It does not write a counter of its own, per ADR-070 and the
  repeat rule in `openspec/config.yaml`.
- **An accounted series.** openregister's generator is gap tolerant by
  its own proposal: a number is never reused after a rollback. A postal
  register that is read end to end needs the missing number explained, so
  filinq records a withdrawn allocation with its reason rather than
  claiming a series that never skips.
- **An outbound entry that discharges an inbound one.** The reply names
  the inbound registration it answers. The inbound entry then shows the
  date it was discharged and the number that did it, and the open post
  list is what is left.
- **A plain-language rendition beside the formal letter.** A template may
  declare a plain-language counterpart. Generating produces both from the
  same data and the same generation record. The formal text stays the
  legal one; the plain one names the document it explains and carries the
  statements the template declares as required, the decision, the term
  and where to object among them. An unresolved required statement
  refuses the generation rather than sending a plain letter that leaves
  the bezwaartermijn out.
- **Plain language is never guessed silently.** A machine-assisted
  rewrite is a suggestion a named person accepts before it is filed, the
  same suggest-then-approve posture `inbound-auto-classification` and
  `anonymization-review-workbench` already take.
- **A download is an event the product can act on.** A download of a file
  filinq owns is recorded with the person, the moment, the version and
  the route it left by, and the declared watchers are told. A public link
  download has no user, so the record names the link and who made it and
  invents no identity.
- **Who is told is declared.** Recipients are declared on the schema and
  are staff, never the data subject by external email, which is what
  `filinq-notifications` already requires of every filinq notification.

## What dossiq consumes

- The registration number and the direction sit on the document. dossiq
  reads them on the informatieobject its case already carries, and places
  the open post list leaf on its Documents page. dossiq declares which of
  its units number independently. No dossiq slug carries this half yet on
  dossiq `development`, so it is to be specified in dossiq.
- The plain-language rendition is offered beside the formal letter
  wherever dossiq shows a beschikking, next to the existing
  `BeschikkingComposerDialog` path. To be specified in dossiq.
- The download notification reaches the case through the same
  notification engine dossiq already reads. dossiq declares who watches a
  case. To be specified in dossiq.

Detection, redaction, the sequence itself and the audit trail stay
openregister's. The mail transport stays integriq's under D12.

## Size

M. Three schema declarations, one registration service, one generation
extension, one listener and two leaves. No new mechanism: the sequence,
the notification engine and the generation channel all exist.

## The existing specs it extends

- `document-register`, the document-domain data model, for the
  registration entry and the sequence declaration.
- `letter-correspondence-generation`, for the plain-language rendition.
- `filinq-notifications`, for the download notification and its
  staff-only routing.

## Out of scope

- The case number and the case's own identifier. openregister's
  `generated-identifier` and dossiq.
- The audit trail itself. openregister owns it, and corpus 13.9 says it
  already records reads.
- Rewriting the formal letter. The formal text is the legal one and this
  change does not touch it.
- The mail transport that carries the letter out. integriq, under D12.
