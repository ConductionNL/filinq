# Design: portal-contribution

## Context

hydra ADR-046 defines **portaliq** as the ONE shared external portal for people
without a Nextcloud account. Contribution contract v2.1: an app contributes via
a single plain class at the convention FQCN
`OCA\{App}\Portal\PortalContributionProvider`, duck-typed by portaliq
(`method_exists()`, never `instanceof`) — inert without portaliq installed
(amendment A1). DocuDesk ships that one class; nothing else in the runtime app
is touched.

All register facts below were verified against HEAD
(`lib/Settings/docudesk_register.json`). DocuDesk uses **five** OpenRegister
registers; the two the portal reads from are:

| Register slug | Schemas (verified at HEAD) |
|---|---|
| `consent` | `publicationConsent`, `publicationProhibition` |
| `signing` | `signingRequest`, `signerRecord`, `signingAuditEntry`, `signingSession` |

The manifest is an explicit **allowlist**: every register/schema not named below
is out of portal scope by default.

## Claim-names contract (DocuDesk's claim namespace — STABLE, do not rename)

portaliq resolves a bare `scopeClaim` against the subject's own server-managed
`portalAccount` claim bag at `claims.docudesk.<name>` (the value is read
server-side only, never from client input). These two names are DocuDesk's claim
contract with portaliq operators; provisioning an account sets them, so renaming
either is a breaking change:

| Claim (`claims.docudesk.*`) | Value | Used by audience | Scopes |
|---|---|---|---|
| `contactId` | The `contactRef` value — a linkage pointer to the canonical Nextcloud Contact record for the affected entity (`publicationConsent.contactRef`, `type: string`, `format: uri`) | `data-subject` | `publicationConsent.contactRef` |
| `signerEmail` | The email a signer was invited under (`signerRecord.email`, `type: string`, `format: email`) | `signer` | `signerRecord.email` (directly, and via the join) |

### Why `contactId` (a reference), not `contactEmail` (PII-in-clear)

`publicationConsent` carries **both** `contactRef` (a URI/UUID pointer to the NC
Contact record — "Linkage pointer to the canonical NC Contact record for this
affected entity") and `contactEmail` (a denormalised, legacy/fallback cleartext
email). Per ADR-046 amendment A4 we scope on the **reference**, not the cleartext
email: a reference is a stronger, non-guessable identity binding, and using a
cleartext email as the scoping key would make the whole surface enumerable by
anyone who knows a target's email. Consequence (accepted, fail-closed): legacy
consent rows that predate contact-linkage and have `contactRef` empty are simply
**invisible** in the portal — under-showing is the safe failure mode.

### Why `signerEmail` (email) IS the signer scope

`signerRecord` has no external-contact UUID. Its `userId` is a Nextcloud account
id, which A4 forbids as an external-subject scope (externals have no NC account
by premise). The only stable identifier a signer is known by is the **invitation
email** portaliq's auth edge verifies (magic-link / eIDAS) and stamps into
`claims.docudesk.signerEmail`. This is the documented "email only when no UUID
linkage exists" case — the email is a scoping key here, and it is deliberately
NOT projected back into any row (see the exclusions).

## Scoping map (audience → schema → scopeField → claim → minTrust)

| Audience | Collection id | Register | Schema | scopeField | scopeClaim | via | minTrust |
|---|---|---|---|---|---|---|---|
| `data-subject` | `subjectConsents` | `consent` | `publicationConsent` | `contactRef` | `contactId` | — | `substantial` |
| `signer` | `signerRecords` | `signing` | `signerRecord` | `email` | `signerEmail` | — | (default `low`) |
| `signer` | `signerSigningRequests` | `signing` | `signingRequest` | *(empty)* | `signerEmail` | see below | `substantial` |

**The `via` one-hop join (contract v2.1, A5)** for `signerSigningRequests`,
because `signingRequest` carries no direct subject-scope property:

```
via = { register: 'signing', schema: 'signerRecord',
        scopeField: 'email', targetField: 'signingRequestId' }
```

portaliq resolves `signerEmail`, queries `signerRecord` where `email ==
signerEmail`, **verifies each row per-row** (the security boundary), collects
each survivor's `signingRequestId`, then reads `signingRequest` and keeps only
rows whose id is in that verified set. A structurally invalid or nested `via`
fails **closed to zero rows** in portaliq's reader (`isValidVia`), never to a
wider read.

### minTrust decisions

- **`subjectConsents` = `substantial`.** A consent/objection record is the data
  subject's own GDPR/WOO case file; revealing it (and letting them act on the
  objection window) must sit above eIDAS-substantial identity assurance. This
  mirrors the Wave-1 `avgVerzoek` (DSAR) gating in pipelinq.
- **`signerRecords` = default `low`.** The projected fields are the subject's own
  benign participation facts (their name, their status, their position, when they
  signed, their own decline reason) — no special-category data — so an
  email-verified signer may see their own signature status. This follows the
  task rule "signer default low unless the data is sensitive."
- **`signerSigningRequests` = `substantial`.** This surface reveals *which
  binding documents* (their names, the eIDAS level, the deadline) await a
  subject's signature. Showing a merely email-verified party the roster of legal
  documents pending their signature is a higher-stakes disclosure that precedes
  an eIDAS signing act, so it is gated at substantial. The graduated model is
  deliberate: "you can see that you have a pending signature (low), but to see
  *what document* you need substantial."

## Field whitelists (read-side projection — every field justified subject-safe)

portaliq projects each verified row down to the collection's `fields` whitelist
**after** per-row verification (identifiers always survive; a malformed
declaration degrades to identifiers-only). Every field below exists on the schema
at HEAD (pinned by `testManifestMatchesShippedRegisterSchemas`).

### `subjectConsents` → `publicationConsent` (12 fields)

| Field | Why subject-safe |
|---|---|
| `scope` | Discriminator (`document` vs standing `entity`) telling the subject which kind of consent record this is — meta about their own record. |
| `consentStatus` | The subject's own consent/objection status. |
| `objectionDeadline` | The deadline by which the subject may object (WOO ≥ 4 weeks) — the whole point of the surface. |
| `objectionReceivedAt` | When the subject's own objection was received — transparency on their own action. |
| `objectionReason` | The subject's own objection text. |
| `publicationDecision` | The publication decision affecting the subject — WOO gives the affected party the right to know it (and appeal). |
| `legalBasis` | The legal ground for publication — GDPR Art. 13/14 requires disclosing this to the data subject. |
| `validFrom` | Start of the subject's own standing consent window. |
| `validUntil` | End of the subject's own standing consent window. |
| `consentScope` | Free-text scope limitation of the subject's own standing consent. |
| `consentMethod` | How the subject's consent was captured — transparency. |
| `active` | Whether the subject's standing consent is currently active. |

### `signerRecords` → `signerRecord` (5 fields)

| Field | Why subject-safe |
|---|---|
| `displayName` | The signer's own display name. |
| `status` | The signer's own signing status. |
| `order` | The signer's own position in the signing sequence. |
| `signedAt` | When the signer themselves signed. |
| `declineReason` | The signer's own decline reason. |

### `signerSigningRequests` → `signingRequest` (6 fields)

| Field | Why subject-safe |
|---|---|
| `documentName` | The signer must know which document they are being asked to sign. |
| `signatureLevel` | The eIDAS level required of them — relevant to the signer. |
| `signingMode` | Sequential vs parallel signing mode — relevant to the signer's turn. |
| `status` | The overall request status. |
| `deadline` | The signing deadline. |
| `provider` | Which signing provider will process the signature — process transparency; not other-party data. |

## Exclusions (every dropped column, with reason)

### `publicationConsent` — excluded (13 of 25 properties)

| Field | Reason excluded |
|---|---|
| `documentId` | Internal Nextcloud document reference — a system id enabling enumeration, not subject content. |
| `entityType` | Detection classifier metadata (PERSON/ORGANIZATION); legacy/fallback denormalised detection internal. |
| `entityText` | Denormalised copy of the detected entity text (legacy/fallback) — exposes detection internals; the subject is already identified by scope. |
| `entityKey` | Internal anonymisation key — a system key that could enable cross-record correlation. |
| `contactEmail` | PII-in-clear email (legacy/fallback denormalised); identity/scope-adjacent, not needed as content. |
| `contactAddress` | PII-in-clear postal address (legacy/fallback denormalised); not needed. |
| `contactRef` | The scope field itself / identity linkage — portaliq preserves identifiers separately; not projected content. |
| `notificationStatus` | Internal notification-delivery state (staff process tracking). |
| `notificationSentAt` | Internal notification-delivery timestamp (staff process tracking). |
| `notes` | Explicitly "Interne notities over het proces" — staff-only internal notes. |
| `matchRules` | Internal matching configuration (same enum as `publicationProhibition`) — staff config that leaks matching logic. |
| `policyMatch` | **Other-party leak risk:** a polymorphic linkage pointing at a `publicationProhibition` record; would expose another party's prohibition policy. |
| `consentDocument` | Reference to a captured consent document (file URI) — a staff-managed artifact linkage. |

### `signerRecord` — excluded (5 of 10 properties)

| Field | Reason excluded |
|---|---|
| `signatureData` | The base64 signature blob; the schema itself marks it `visible: false`. Hard exclude — sensitive cryptographic signature material. |
| `ipAddress` | The IP captured at signing — forensic/audit data, security-sensitive. |
| `userId` | Nextcloud account id (A4 anti-pattern for accountless externals); internal identifier. |
| `signingRequestId` | Internal parent-request UUID (system linkage; used only inside the `via` join, never shown). |
| `email` | PII-in-clear and the scope field itself; never projected back. |

### `signingRequest` — excluded (3 of 9 properties)

| Field | Reason excluded |
|---|---|
| `initiatorUserId` | **Other-party leak:** the initiator's Nextcloud account id — a different person's identity. |
| `signerIds` | **Other-party leak:** the full roster of co-signer UUIDs — reveals the existence/identity of other signers. |
| `documentFileId` | Internal Nextcloud file id — a system identifier enabling access/enumeration attempts. |

### No inbox collection

The task asks for a `kind: 'inbox'` collection *if* a per-subject
notification/message schema exists. **None does.** The nearest candidates were
inspected and rejected:

- `correspondence` (register `document`) is outbound, staff-generated batch
  correspondence (`recipientId`, `generatedBy`, `errorMessage`, `templateId`) —
  not a subject-owned inbox scoped by `contactRef`/`signerEmail`.
- `publicationConsent` tracks notification delivery on the record itself
  (`notificationStatus`/`notificationSentAt`), not in a separate message schema.
- `signingSession` / `signingAuditEntry` are audit/session internals (other
  signers, IPs, signatures) — never subject surfaces.

So no `notifications`/inbox collection ships; `notifications` is `[]`.

## Deferred actions (no create / endpoint actions this wave)

- **Data-subject objection intake — deferred.** Creating a `publicationConsent`
  requires the staff-only detection fields `documentId`, `entityType`,
  `entityText` (all in the schema `required` list), which a data subject cannot
  supply; and *lodging an objection* is an **UPDATE** to an existing record, not
  a `create`. The create-action vocabulary cannot express it safely, so there is
  no clean create whitelist and the action is deferred (task guidance: "if a
  clean whitelist exists, else defer").
- **Signer sign / decline — deferred.** These are **A6 endpoint actions** (a
  signed server-to-server assertion forwarded to a guarded receiver, per the
  petstore recipe). The task marks A6 optional; DocuDesk ships read surfaces
  first and defers the sign/decline endpoint actions to a follow-up so the
  receiver + `PortalAssertionVerifier` land as a reviewed unit.

Both are recorded on tracking issue Conduction/docudesk#160 for a follow-up
change.

## Declarative vs imperative

**Decision: fully declarative — a pure-data manifest, zero I/O.** The provider
branches only on `$subject['audience']` (server-derived per ADR-005) and returns
constants. Rejected alternatives:

- *Imperative provider* (query OR to tailor collections per subject): portaliq
  already scopes reads server-side and verifies per row; app-side queries would
  duplicate the authz path (ADR-022 violation) and add OR coupling to a class
  whose entire value is being dependency-free.
- *Reusing DocuDesk's `SigningService`/`ConsentService`*: couples the
  contribution to services with constructor dependencies, breaking the
  duck-typed inertness guarantee.

Consequence: anything needing per-subject logic stays in portaliq; the manifest
stays audit-readable data. A *class* (not a JSON file) is the delivery vehicle
only because ADR-046 mandates FQCN discovery.

## Mixed-spec rationale

This change is `kind: code`: it ships a provider class + unit tests and — unlike
the petstore reference — makes **no** register JSON edit, because every
scopeField, `via` field and projected field already exists on the shipped
schemas at HEAD (verified). There is therefore no schema-version bump and no data
migration; the register-drift pin test guards against future drift.

## Seed Data

The provider performs no I/O, so this change creates **no** OpenRegister objects,
registers or schemas. Unit tests construct the provider directly (no container)
and feed synthetic subjects built on the **nil-UUID pattern** so fixtures are
self-evidently fake and can never collide with live data:

```php
$dataSubject = [
    'subjectRef'   => '00000000-0000-0000-0000-000000000001',
    'audience'     => 'data-subject',
    'organisation' => '00000000-0000-0000-0000-000000000002',
    'trust'        => 'substantial',
];
$signerSubject = [
    'subjectRef'   => '00000000-0000-0000-0000-000000000003',
    'audience'     => 'signer',
    'organisation' => '00000000-0000-0000-0000-000000000002',
    'trust'        => 'substantial',
];
```

Live-portal seeding (a `portalAccount` carrying `claims.docudesk.contactId` /
`claims.docudesk.signerEmail`) belongs to portaliq's own e2e environment, keyed
by the claim-names contract above.

## Security Considerations

- **Server-derived subject only** (ADR-005 / A6): the `$subject` array is built
  by portaliq's auth edge; the provider only *reads* `audience` to branch and
  never echoes subject data back or trusts client input.
- **Reference/email scoping, never NC uid** (A4): consent scopes on the contact
  reference, signer on the verified invitation email — never a Nextcloud user id.
- **Fail-closed audience filter**: any audience other than `data-subject` /
  `signer` yields `null`.
- **Server-side projection**: every collection ships a `fields` whitelist;
  staff-only, forensic, cryptographic and other-party columns are dropped by
  portaliq after per-row verification (fail-closed to identifiers-only on a
  malformed declaration).
- **No secrets, tokens, routes or endpoints** in this change.

## Risks

- Claim names are load-bearing once a portaliq operator provisions accounts —
  hence the STABLE marker; renames are breaking.
- If a future register edit adds a staff-only property to `publicationConsent`,
  `signerRecord` or `signingRequest`, it is NOT auto-exposed (the whitelist is
  positive), but the exclusion tables above are the review checklist for register
  PRs touching these schemas.
- The `via` join depends on contract-v2.1 join support in portaliq's reader; on a
  reader that predates it the join is not resolved. The change documents this
  dependency; portaliq's reader fails closed on an unrecognised/invalid `via`.
