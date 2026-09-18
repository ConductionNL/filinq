# Authorisation decisions

Every schema Filinq declares in `lib/Settings/filinq_register.json` carries an
explicit authorisation decision. This file is the record of *why*, and
`tests/unit/Settings/SchemaAuthorizationCoverageTest.php` fails if a schema exists
without an entry here.

> **The rule this file exists to prevent breaking again:** OpenRegister treats an
> **unconfigured** cascade as OPEN. `_rbac: true` means "apply the cascade"; it does
> not mean "a cascade exists". With `authorization: null`, applying the cascade
> permits everything. Absence of an opt-out is not the presence of a guard.

> ⚠️ **The group ids below still say `docudesk-`, and that is deliberate.** The app
> id was renamed `docudesk` → `filinq`; these Nextcloud GROUP ids were not, and must
> not be renamed as part of a rebrand. OpenRegister provisions a declared group
> **create-only**, so renaming `docudesk-template-editors` to `filinq-template-editors`
> does not rename anything — it creates a **new, empty** group while the existing one
> keeps every member and nothing reads it any more. An empty group denies everyone
> except admins and object owners, so the symptom is writes starting to 403 for
> exactly the people who were granted them, with no error at provisioning time and
> nothing in the log. Same failure shape as renaming an OpenRegister register slug.
> Renaming these groups requires its own membership migration, and that migration has
> not been written. The same reasoning pins the AVG Art. 30 processing-activity codes
> (`docudesk-anonymisation`, `docudesk-ocr`, `docudesk-signing`,
> `docudesk-metadata-enrichment`) in the register and in `src/views/settings/Settings.vue`:
> they are already recorded on written read-log rows, and renaming them would detach
> that history from the catalogue entry describing it.

## What was measured

Development instance, 2026-08-16, before the change:

- **20 of 21** schemas declared no `authorization` cascade.
- All three `filinq` register rows carried `authorization = NULL`, so the
  register-level cascade did not fill the gap either.
- Two ordinary users in **no groups** and the **same organisation**:
  `ddauth-bob` read `ddauth-alice`'s private template (HTTP 200, full `content`),
  **overwrote its content** via `PUT` (HTTP 200), and duplicated it.

That last point matters: this was a **write** exposure, not only disclosure.

### The bound

`ddauth-carol`, authenticated and in a different organisation (Gemeente Utrecht),
requested the same template and received **404**, while `ddauth-bob` in the same
organisation received **200** for the identical request.

**Multitenancy is a separate axis and remains enforced.** The exposure was to
authenticated users **within one organisation** — not anonymous, not cross-tenant.
Both available one-line summaries are wrong in the same way: "it's an IDOR" reads as
anonymous and drives panic-shaped work, and "RBAC covers it" is the belief that
produced the gap.

## The shape of the fix

**`update` and `delete` are restricted on every schema.** That is where the proven
harm was. OpenRegister's owner bypass is unconditional, so a creator keeps full
control of their own objects — this closes cross-user writes with no read-path
outage. Verified after deploy: `ddauth-bob` → **403** on `PUT`, `ddauth-alice` →
**200** on the same object.

**`read` is restricted only where the data justifies it**, because an over-tight read
cascade is a visible outage, and a read restriction I cannot verify is a guess. Where
`read` stays `authenticated`, that is a recorded decision below, not an omission.

## Reserved principals

`admin` and the object owner bypass every rule — they are not listed anywhere.
`authenticated` is any logged-in user; combined with multitenancy that means
"anyone in this organisation". `public` is anonymous and **is used by no Filinq
schema**.

## Groups

Five groups are named. OpenRegister provisions declared groups create-only on import,
ahead of the content-hash skip, so they exist on every instance.

| Group | Owns |
|---|---|
| `docudesk-template-editors` | templates, versions, house style, and the correspondence/document audit rows they produce |
| `docudesk-policy-admins` | Woo Art. 5 grounds, anonymisation recognisers, consent, prohibition overrides, dossiers |
| `docudesk-financial-admins` | invoice/receipt extraction and GL-account mapping |
| `docudesk-signing-admins` | signing requests, signers, sessions, signing audit |
| `docudesk-final-document-admins` | unfreezing a final document, and declaring which record-type states freeze one |

**They ship empty on purpose.** An empty group denies everyone except admins and
object owners. That is the correct default and it is immediately visible, which is
better than back-filling 21 schemas' worth of grants nobody chose. Populate them
deliberately.

## Per-schema decisions

| Schema | read | create | update / delete | Why |
|---|---|---|---|---|
| `template` | authenticated | authenticated | template-editors | Templates exist to be **used** across the organisation, so read stays open; the measured defect was a foreign user rewriting one. |
| `templateVersion` | authenticated | authenticated | template-editors | Snapshots follow their template. `create` must be as open as template create, because a version row is written as a side effect of editing. |
| `huisstijl` | authenticated | template-editors | template-editors | Branding must be readable or nothing renders; changing the organisation's house style is a managed act. |
| `correspondence` | authenticated | authenticated | template-editors | Audit row written as a side effect of generating, so `create` follows the generator. |
| `generatedDocument` | authenticated | authenticated | template-editors | Same. |
| `batchCorrespondenceJob` | authenticated | authenticated | authenticated / template-editors | Job state is advanced by the worker running **as the submitting user**, so `update` must stay open; deletion is not part of running a job. |
| `financialExtraction` | **financial-admins** | authenticated | financial-admins | Carries supplier IBAN, KvK and BTW numbers. Not organisation-wide readable. `create` stays open so an ordinary user can scan a receipt — they then own the row and can read it back. |
| `glAccountBooking` | **financial-admins** | authenticated | financial-admins | Ledger-mapping feedback derived from the above. |
| `glAccountMappingRule` | authenticated | financial-admins | financial-admins | Must be readable at extraction time to be applied at all; maintained by finance. |
| `customDictionary` | authenticated | policy-admins | policy-admins | A recogniser list must be readable when matching runs. |
| `customDictionaryTerm` | authenticated | policy-admins | policy-admins | Same. |
| `base` | authenticated | policy-admins | policy-admins | Woo Article 5 grounds are public-law reference data, and must be readable to explain **why** something was redacted. |
| `dossier` | authenticated | authenticated | policy-admins | A dossier points at a Nextcloud folder; Nextcloud's own share permissions govern the contents. |
| `anonymizationLink` | authenticated | authenticated | policy-admins | Written by the anonymiser running as the acting user. |
| `anonymizationBatch` | **policy-admins** | authenticated | policy-admins | Working state of one multi-file anonymisation run. `read` is restricted because the record lists the filenames of a user's private documents, and the batch owner reaches their own batch through the owner bypass. Filinq's own reads and writes go through `BatchStateRepository` with `_rbac: false` (recorded below) and are ownership-checked in `BatchStateService::getBatch()` instead, so this cascade governs only direct OpenRegister API access. Added with the schema in register v7.10.0 rather than omitted — an omitted cascade is OPEN, so shipping a new schema without one re-opens exactly the hole v7.9.0 closed. |
| `publicationConsent` | **policy-admins** | authenticated | policy-admins | GDPR consent records about identified people. `create` stays open because a data subject records their own consent and then owns it. |
| `publicationProhibition` | `consent` group | policy-admins | policy-admins | **Unchanged** — decided before this change. |
| `prohibitionOverrideAudit` | **policy-admins** | **authenticated** | policy-admins | ⚠️ `create` is deliberately open. Register v7.7.0 records that restricting it re-breaks the fail-closed override path for the ordinary operator who performs the anonymise: the write is explicitly "if the audit write fails we MUST NOT proceed", so a denied create raises 500 on every acknowledged override and no override can ever be committed. |
| `signingRequest` | **signing-admins** | authenticated | signing-admins | A signing request names its signers. Safe to restrict: the signer portal resolves records with `_rbac: false` behind its own token binding (see below), so the signer flow does not depend on this cascade. |
| `signerRecord` | **signing-admins** | authenticated | signing-admins | Identifies an individual signer. Same portal reasoning. |
| `signingAuditEntry` | **signing-admins** | authenticated | signing-admins | Deprecated, retained read-only history. |
| `signingSession` | **signing-admins** | authenticated | authenticated / signing-admins | `update` stays open because the session is advanced by the signer themselves as they progress. |
| `documentVersion` | authenticated | authenticated | authenticated / final-document-admins | The record says whether a version is final, and everyone who can open the document needs to read that. `update` stays open because making a document final is an ordinary handler's act, and the service refuses a second write to a version that is already final. Only a final-document admin may delete the record, because deleting it is how a freeze would otherwise be undone without a trace. |
| `documentFinalityRule` | authenticated | **final-document-admins** | final-document-admins | The rule says which record-type states freeze a document, so it is applied on every state change and must be readable. Declaring one is an administrative act: a wrong rule freezes documents nobody meant to freeze. |

| `intakeDocument` | authenticated | authenticated | authenticated / admins | A document waiting for a record is read by every clerk who might assign it, and assigning or rejecting it is an ordinary clerk's act. The service checks write rights on BOTH this schema and the record the clerk aims at, so the cascade here is the floor and not the whole check. Only an admin deletes one, because deleting a waiting document is how an arrival disappears with nobody having decided to reject it. |
| `intakeDefaultRule` | authenticated | **admins** | admins | The rule stamps metadata on everything that arrives, so it is read on every arrival. Writing one is administrative: a wrong rule mislabels every document from a channel, and the stamped values look like somebody's decision. |
| `intakeRoutingRule` | authenticated | **admins** | admins | The declaration says who inbound documents on a record type go to. It is read at every assignment; changing it redirects other people's work, which is not an ordinary handler's act. |
| `intakePartyCorrection` | authenticated | authenticated | admins | Accept, edit and reject are recorded by the clerk who decided, so `create` is open to them. The corpus is what the next suggestion is ranked against, so an entry is not edited afterwards and only an admin may remove one. |
| `uploadPolicy` | authenticated | **admins** | admins | Every write path reads the policy to decide whether a file may be stored, so it must be readable. Writing one changes what the whole instance accepts, including what it refuses to accept. |

| `pageLayout` | authenticated | **admins** | admins | Every generated document renders through a layout, so it is read on every render. A layout is the paper the organisation goes out on; editing one is administrative, and an edit makes a new version rather than changing the one existing documents name. |
| `periodicDocument` | authenticated | **admins** | admins | The schedule says which template renders over which saved view, on what cadence. It is read by the run and by anyone looking at what the list is made of; changing it changes a document the whole organisation reads. |
| `archiveJob` | authenticated | authenticated | admins | A bundle is asked for by an ordinary handler, so `create` is theirs, and the job records who asked because the archive holds what THAT person may read. The job is the evidence of what was handed over, so only an admin may remove one. |

| `mergeJob` | authenticated | authenticated | admins | A merge is an ordinary handler's act, so `create` and `update` are theirs; the job records who asked because every input is read as that person and the result holds only what they could read. Only an admin may delete one: the job is the trace of what was bundled and handed over. |

| `scanBatch` | authenticated | authenticated | admins | The batch is created by the watched-folder job on behalf of the instance and read by the clerk who sorts out what came off the scanner, so both are open. Only an admin deletes one: the batch is the trace that says which documents a delivered PDF was cut into, and a missing segment is only findable through it. |

## Deliberate RBAC bypasses

A cascade only guards callers that go through it. `ObjectService::find()` and
`findAll()` accept `_rbac: false`, and Filinq passes it at **25 call sites in 10
files**. Each is paired with a compensating control rather than being an oversight,
and the coverage test pins the set: **a new bypass fails the test until it is added
here with a reason.**

| File | Sites | Compensating control |
|---|---|---|
| `Service/PolicyCrudService.php` | 5 | `requirePolicyPermission()` asserts membership in `docudesk-policy-admins` / `docudesk-standing-consent-admins` for **every** action including `read`, before the lookup runs. |
| `Service/CustomDictionaryRepository.php` | 4 | Recogniser lists are read during anonymisation, which runs on behalf of a user who may not hold the maintainer group. Read-only; no caller-supplied id reaches it. |
| `Service/DossierObjectRepository.php` | 3 | Dossier contents are governed by Nextcloud folder permissions, which are checked before the repository is reached. |
| `Service/BatchStateRepository.php` | 3 | Batch state is Filinq's own working state, written and read only by `BatchStateService` — no OpenRegister API caller reaches this class. Ownership is enforced one layer up in `BatchStateService::getBatch()`, which returns `null` (not a distinct error) for a batch belonging to another non-admin user, so a guessed batch id is indistinguishable from an absent one. The bypass is required, not convenient: the cascade restricts `update`/`delete` to `docudesk-policy-admins`, which ships **empty**, so an ordinary user's own extraction run would fail closed at its first status write. |
| `Service/PolicyMatchService.php` | 2 | Matching must see every prohibition regardless of the acting user, or an anonymisation silently under-redacts. Fail-closed by design. |
| `Service/ConsentPolicyReferentValidator.php` | 2 | Validation-only; returns a boolean, never record content. |
| `Service/PolicyRetroactiveService.php` | 2 | Background re-application over records the triggering user may not own. |
| `Service/BaseLabelResolver.php` | 1 | Resolves a legal-basis label for display; `base` is organisation-readable anyway. |
| `Service/BasesResolverService.php` | 1 | Same. |
| `Service/LegalBasisCatalog.php` | 1 | Static Woo Art. 5 catalogue. |
| `Controller/PortalSigningReceiverController.php` | 1 | The signer portal is anonymous by design and binds a `signerRecord` to a token **and** an email **and** a signing-request id, refusing with the same `null` for a wrong email, a wrong request, and an unresolvable register — so no new signal is exposed. |

`Service/ConsentCrudService.php` contains the string `_rbac: false` in a comment that
**forbids** the bypass, and is not a bypass. It ends:

> *"Do not delete either as 'redundant with OpenRegister RBAC' — measured, it is not
> redundant."*

That comment exists because someone had already reasoned their way to deleting a real
control. It is why no guard in this change was added merely to make a gate green: an
unjustified guard is that control with the reasoning stripped out, and it gets deleted
the same way.

## Deploying a change to this file

A cascade edit reaches a running instance only if it is imported. `info.version` in
the register **must** be bumped; the importer additionally compares schema content
(`properties`, `required`, `authorization`) so an authorisation-only edit is not
silently skipped, but the version bump is what moves the app-level configuration
gate. Verify against the live instance — a cascade present in the file and absent
from `oc_openregister_schemas` is a fix-shaped commit, not a fix.

## `product` — the rate card

`product` carries rate-card data: what a service is called and what it costs. Its
authorization reads:

```json
{ "read": ["authenticated"],
  "create": ["docudesk-template-editors"],
  "update": ["docudesk-template-editors"],
  "delete": ["docudesk-template-editors"] }
```

**Read is deliberately open to authenticated users.** A rate card is quoted back to
clients; a price nobody inside the organisation may read is a price nobody can quote,
and the data is not personal. Multitenancy still applies on the row, so "authenticated"
means authenticated *within this organisation*, not everyone on the instance.

**Writes are narrowed to `docudesk-template-editors`**, the same group that owns
templates, because a rate card is priced content of exactly that kind: changing it
changes what every future quotation says.

⚠️ It is deliberately **not searchable**. Only `template` and `signingRequest` carry
manifest deep links, and a searchable schema without one produces Unified Search hits
that lead nowhere — a result the user can see and cannot open. The schema shipped
`searchable: true` without a deep link and three tests caught it; this records the
decision so the next person does not restore the flag on the assumption it was an
oversight in the other direction.
