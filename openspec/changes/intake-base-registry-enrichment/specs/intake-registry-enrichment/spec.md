# intake-registry-enrichment Specification (delta)

## Purpose

An arriving document that carries a KvK number or a BSN gets a registry
suggestion from the Handelsregister or the BRP, which a registrar accepts
or dismisses. Nothing is written without that step. Matrix row
`in-basisreg` (filinq). The lookups are openregister's providers over
integriq's sources; this spec is filinq's half.

## ADDED Requirements

### Requirement: A KvK number on an arrival becomes a registry suggestion (REQ-IBR-001)

After an intake document is saved, filinq MUST look up every KvK number
detected on its file in the Handelsregister through openregister's KvK
provider and MUST write the company name, visiting address and KvK number
to `registrySuggestion` with `written: false`. The lookup MUST run in the
background and MUST NOT delay or refuse the arrival.

Rows: `in-basisreg` (filinq matrix)

#### Scenario: A registrar sees the company behind a letterhead

- GIVEN a KvK source configured in integriq and a letter whose letterhead carries KvK number 12345678
- WHEN the letter arrives and the registrar opens its assign dialog in `/intake`
- THEN the dialog shows the company name and visiting address from the Handelsregister, marked as a suggestion
- @e2e tests/e2e/intake-registry-enrichment.spec.ts

### Requirement: The BRP is asked only under a declared purpose (REQ-IBR-002)

filinq MUST look up a BSN only when the matched intake default rule sets
`brpPurpose` and the admin setting for BRP lookups is on (default off).
The BSN MUST pass the elfproef first, MUST travel with the purpose, MUST
NOT be written to a log or a note, and the suggestion MUST keep only name
and address.

#### Scenario: No purpose, no query

- GIVEN BRP lookups switched on and a rule for channel `mail` without a purpose
- WHEN a letter carrying a BSN arrives by mail
- THEN no BRP query is made and the document has no BRP suggestion
- @e2e exclude absence of an external call; covered by PHPUnit with a spy provider

#### Scenario: An admin leaves the BRP off

- GIVEN a fresh installation
- WHEN an admin opens the filinq admin settings
- THEN the BRP lookup switch is off
- @e2e tests/e2e/intake-registry-enrichment.spec.ts

### Requirement: A lookup that cannot run says why (REQ-IBR-003)

When a provider is missing, disabled or answers that its source is down,
filinq MUST write the reason to `registryLookupNote` and MUST leave the
rest of the intake document unchanged.

#### Scenario: KvK is not configured

- GIVEN no KvK source in integriq
- WHEN a letter with a KvK number arrives
- THEN the assign dialog says "KvK lookup is not configured" and the party suggestion is still offered
- @e2e tests/e2e/intake-registry-enrichment.spec.ts

### Requirement: A person accepts or dismisses a registry suggestion (REQ-IBR-004)

The registrar MUST be able to accept, correct or dismiss each registry
suggestion. Accepting MUST write the fields to the party the document is
assigned with and set `written: true`. Dismissing MUST set `dismissed:
true`. Both MUST name the session user in the audit trail.

#### Scenario: A registrar accepts a corrected address

- GIVEN a registry suggestion for "Voorbeeld B.V." on a waiting document
- WHEN the registrar corrects the house number and accepts
- THEN the party on the assigned record carries the corrected address and the suggestion reads as written
- @e2e tests/e2e/intake-registry-enrichment.spec.ts
