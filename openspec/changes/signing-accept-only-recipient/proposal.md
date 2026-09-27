---
kind: code
depends_on: []
---

# Proposal: signing-accept-only-recipient

Matrix row `sig-accept-only` in filinq's `openspec/parity/capabilities.json`,
rated no, built.state none. Written in the OpenSpec pass of 27 September 2026.

## Why

A lease goes to the tenant to sign. The housing officer must also agree to
it, but her signature is not needed on the document. filinq can only ask
everyone on a request to sign. So either the officer signs a document she
should only have accepted, or she is left off and her agreement goes
unrecorded.

A recipient in filinq has no role. The `signerRecord` status is `PENDING`,
`SIGNED`, `DECLINED`, `EXPIRED` or `CANCELLED`
(`lib/Settings/filinq_register.json:2830`), and
`SigningService::updateRequestStatus()` completes a request only when every
recipient reads `SIGNED` (`lib/Service/SigningService.php:688`).

### Matrix rows (filinq openspec/parity/capabilities.json)

| row | capability | filinq today |
|---|---|---|
| `sig-accept-only` | Let one recipient accept a document without signing it while others sign. | no: no accept role; `signerRecord` has no role property and `SigningService` knows only sign and decline |

### Demand

- changelog: https://kb.validsign.eu/nl/release-notes/release-notes-2026-09-23

### Competitors rated yes

- ValidSign (docs read 2026-09-26): "'Accept only' now set per recipient so
  one accepts while another signs". Evidence:
  https://kb.validsign.eu/nl/release-notes/release-notes-2026-09-23 and
  https://developers.validsign.eu/docs/documents/accept_only

## What changes

- Each recipient on a signing request has a role: sign, or accept only.
  Sign stays the default, so every existing request reads as before.
- An accept-only recipient accepts or declines. Accepting records who, when
  and through which route, and is never called a signature.
- A request completes when every signer has signed and every accept-only
  recipient has accepted.
- The initiator sets the role on the new request form, through the API, or
  through the delegation event another app sends.
- An internal accept-only recipient accepts from the signing folder. An
  external one accepts on the portal, through an `accept` row action
  declared the same way as `sign` and `decline`.

## Capabilities

### New capabilities

- `signing-accept-only`: a recipient role that accepts a document without
  signing it, counted towards completion and recorded as an acceptance.

### Modified capabilities

None. `portal-signing-surface` and `portal-signing-actions` keep their
requirements; this change adds a third row action and receiver act beside
theirs.

## Impact

- `lib/Settings/filinq_register.json`: `signerRecord` gains `role` and
  `acceptedAt`, its status gains `ACCEPTED`, and the `signingRequested`
  notification subject becomes neutral. Register version bump.
- `lib/Service/SigningService.php`: `createRequest()` stores the role,
  new `accept()`, `sign()` refuses an accept-only recipient, and
  `updateRequestStatus()` counts acceptances.
- `lib/Controller/SigningController.php` and `appinfo/routes.php`:
  `POST api/signing/requests/{id}/accept`.
- `lib/Portal/PortalContributionProvider.php`: an `accept` row action and
  action. `lib/Controller/PortalSigningReceiverController.php`: an
  `acceptDocument` act.
- `src/views/signing/SigningRequestForm.vue`: recipient rows with a role.
  `src/views/signing/SigningFolder.vue`: "Accept" on accept-only rows.
  `src/views/signing/SigningRequestDetail.vue`: a recipient list.
- `docs/features/`: a section with screenshots.

## Out of scope

- Mapping the role onto ValidSign's own accept-only setting. The ValidSign
  provider is a stub today (`lib/Service/Signing/ValidSignProvider.php:79`);
  it receives the role in the signer list and maps it when the integration
  is built.
- Other roles (reviewer, copy recipient).
- Placing fields for an accept-only recipient. `bulk-signing-field-builder`
  places fields for signers.

## Cross-app dependencies

- **portaliq**: two gaps found in the portaliq lane, filed there as a
  portaliq issue. (1) portaliq's `CollectionConfigNormaliser` keeps only
  string update actions, so it drops filinq's `sign` and `decline` row
  actions today and would drop `accept` the same way; it must keep
  non-string row actions. (2) portaliq mints no `signerEmail` claim, which
  the `signerSigningRequests` collection scopes on and the receiver checks;
  it must pass the verified signer e-mail in the assertion. Until both
  land, an external accept-only recipient cannot act on the portal, and
  neither can an external signer.
