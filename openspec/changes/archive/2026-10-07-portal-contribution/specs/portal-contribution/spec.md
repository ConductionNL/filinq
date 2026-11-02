# portal-contribution Specification (delta)

---
status: proposed
---

## Purpose

Filinq contributes `data-subject` and `signer` read surfaces to **portaliq**,
the shared external portal for people without a Nextcloud account (hydra
ADR-046, contribution contract v2.1). The contribution is one plain,
dependency-free provider class (`OCA\Filinq\Portal\PortalContributionProvider`,
duck-typed by FQCN — inert without portaliq) that returns a pure-data manifest
per audience, with server-side field projection and reference/email claim
scoping. No register JSON is changed; every referenced property is verified
against HEAD.

## ADDED Requirements

### Requirement: Provider is a plain, dependency-free class (REQ-DDPORT-001)

The app MUST ship `OCA\Filinq\Portal\PortalContributionProvider` as a plain
PHP class: no imports from portaliq, no `implements` clause, no `info.xml`
dependency on portaliq, and no constructor dependencies. portaliq discovers it
by convention FQCN and duck-types it via `method_exists` (never `instanceof`),
so without portaliq installed the class MUST be inert and MUST NOT change any
app behaviour (ADR-046 amendment A1). It MUST NOT be registered in
`lib/AppInfo/Application.php`.

#### Scenario: Provider constructs standalone

- GIVEN a PHP runtime where portaliq is not installed and no portaliq class is autoloadable
- WHEN `new PortalContributionProvider()` is called
- THEN the class instantiates without error
- AND it declares no `implements` clause, no parent class, and no constructor
- AND its source references no portaliq symbol
- @e2e exclude backend-only contract class with no Filinq UI surface; the portal renders inside portaliq — covered by PHPUnit (tests/unit/Portal/PortalContributionProviderTest.php::testProviderIsPlainAndDependencyFree)

### Requirement: Provider declares both v2 and v1 audience methods (REQ-DDPORT-002)

The provider MUST implement `getAudiences(): array` returning
`['data-subject', 'signer']` (contract v2, preferred by the registry) AND
`getAudience(): string` returning `'data-subject'` (v1 fallback), so it works
against both registry generations (ADR-046 amendment A2).
`getContribution(array $subject): ?array` MUST return `null` for any audience
other than `data-subject` or `signer`, and for an absent/empty audience
(fail-closed).

#### Scenario: Audience methods agree and unknown audiences fail closed

- GIVEN a constructed provider
- WHEN `getAudiences()`, `getAudience()` and `getContribution()` are called
- THEN `getAudiences()` returns exactly `['data-subject', 'signer']`
- AND `getAudience()` returns `'data-subject'`, which is contained in `getAudiences()`
- AND `getContribution()` returns `null` for audience `'supplier'`, for `[]`, and for an empty-string audience
- @e2e exclude backend-only contract methods with no Filinq UI surface — covered by PHPUnit (testAudiencesOnBothContractVersions, testUnknownAudienceYieldsNull)

### Requirement: Data-subject reads their own consent, scoped and projected (REQ-DDPORT-003)

For a `data-subject` subject, `getContribution()` MUST return a manifest labelled
`Filinq` with exactly one read collection `subjectConsents` over register
`consent`, schema `publicationConsent`, scoped by `scopeField: contactRef` and
`scopeClaim: contactId` (the contact-record reference — NOT the PII-in-clear
`contactEmail`), gated at `minTrust: substantial`, and projected to exactly the
twelve subject-safe fields `scope`, `consentStatus`, `objectionDeadline`,
`objectionReceivedAt`, `objectionReason`, `publicationDecision`, `legalBasis`,
`validFrom`, `validUntil`, `consentScope`, `consentMethod`, `active`. The
whitelist MUST NOT contain any staff-only, detection-internal,
notification-delivery, matching, cross-object-linkage or PII-in-clear column.
`actions` and `notifications` MUST be empty.

#### Scenario: Data subject receives the substantial-gated, projected consent collection

- GIVEN a subject array with `audience` `'data-subject'`, a `subjectRef` UUID, an organisation and a trust level
- WHEN `getContribution($subject)` is called
- THEN it returns a manifest labelled `Filinq` with a single `subjectConsents` collection over `consent`/`publicationConsent`
- AND the collection is scoped by `contactRef` / claim `contactId` and gated `minTrust: substantial`
- AND its `fields` whitelist is exactly the twelve documented subject-safe fields and excludes `documentId`, `entityKey`, `notes`, `matchRules`, `policyMatch`, `contactEmail` and `consentDocument`
- AND `actions` and `notifications` are empty
- @e2e exclude manifest is consumed and rendered by portaliq, not by any Filinq UI — covered by PHPUnit (testDataSubjectConsentCollectionIsScopedAndProjected)

### Requirement: Signer reads their signatures directly and their requests via a one-hop join (REQ-DDPORT-004)

For a `signer` subject, `getContribution()` MUST return a manifest labelled
`Filinq` with two read collections over register `signing`:

- `signerRecords` — schema `signerRecord`, scoped directly by `scopeField: email`
  / `scopeClaim: signerEmail`, projected to exactly `displayName`, `status`,
  `order`, `signedAt`, `declineReason`. It MUST NOT expose `signatureData`,
  `ipAddress`, `userId`, `signingRequestId` or `email`.
- `signerSigningRequests` — schema `signingRequest`, empty `scopeField`,
  `scopeClaim: signerEmail`, reached through the contract-v2.1 one-hop `via`
  join `{register: signing, schema: signerRecord, scopeField: email,
  targetField: signingRequestId}`, gated `minTrust: substantial`, projected to
  exactly `documentName`, `signatureLevel`, `signingMode`, `status`, `deadline`,
  `provider`. It MUST NOT expose the other-party columns `initiatorUserId`,
  `signerIds` or the internal `documentFileId`.

`actions` and `notifications` MUST be empty.

#### Scenario: Signer receives the direct-scope record collection and the via-joined request collection

- GIVEN a subject array with `audience` `'signer'`, a `subjectRef` UUID, an organisation and a trust level
- WHEN `getContribution($subject)` is called
- THEN it returns a manifest labelled `Filinq` with `signerRecords` (scoped `email` / claim `signerEmail`) and `signerSigningRequests`
- AND `signerSigningRequests` carries an empty `scopeField`, `scopeClaim: signerEmail`, `minTrust: substantial` and the one-hop `via` join `{register: signing, schema: signerRecord, scopeField: email, targetField: signingRequestId}`
- AND `signerRecords` projects only participation fields (no `signatureData`, `ipAddress`, `userId`) and `signerSigningRequests` drops `initiatorUserId`, `signerIds` and `documentFileId`
- @e2e exclude manifest + via-join are consumed and enforced by portaliq's reader, not by any Filinq UI — covered by PHPUnit (testSignerRecordCollectionIsScopedAndProjected, testSignerSigningRequestsUsesViaJoin)

### Requirement: Manifest matches the shipped register schemas (REQ-DDPORT-005)

The unit suite MUST pin the manifest against the shipped register: every
declared `scopeField`, every `via` join field (`scopeField` and `targetField`)
and every projected read field MUST exist as a property on the declared schema
in `lib/Settings/filinq_register.json`. A register drift (renamed scope
property, dropped whitelist field) MUST therefore break the unit suite instead
of silently emptying a portal scope or dropping a projected column.

#### Scenario: Register-drift pin holds against HEAD

- GIVEN the shipped `filinq_register.json` and the manifest for each served audience
- WHEN every collection's `scopeField` (when non-empty), `via` `scopeField`/`targetField`, and projected `fields` are checked against the schema's properties
- THEN each declared property exists on its schema
- @e2e exclude declarative register/manifest cross-check with no UI surface — covered by PHPUnit (testManifestMatchesShippedRegisterSchemas)

### Requirement: No create, endpoint or inbox surfaces this wave (REQ-DDPORT-006)

This wave MUST declare read collections only. For every served audience the
manifest's `actions` MUST be empty (no `create` actions, no A6 endpoint actions)
and `notifications` MUST be empty (no `kind: 'inbox'` collection), because
objection intake is an UPDATE the create vocabulary cannot express, sign/decline
are deferred A6 endpoint actions, and no per-subject message schema exists. These
are deferred to a follow-up (tracking issue Conduction/filinq#160).

#### Scenario: Both audiences ship read-only manifests

- GIVEN the manifests for the `data-subject` and `signer` audiences
- WHEN their `actions`, `notifications` and each collection's `kind` are inspected
- THEN `actions` is empty, `notifications` is empty, and no collection declares `kind`
- @e2e exclude backend-only manifest shape with no Filinq UI surface — covered by PHPUnit (testNoActionsShipThisWave)
