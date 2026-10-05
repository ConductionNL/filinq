---
kind: code
depends_on: []
---

# Proposal: redaction-guarantees-from-the-engine

## Why

Ruben's decision **D2** of 2026-10-05: the redaction guarantees move into
OpenRegister's engine, so every path has them, and filinq consumes the engine
and retires its own copies, keeping its review workbench on top. Today the
guarantees exist only for the files filinq writes:

- REQ-RWB-01 "The written copy cannot be read back":
  `lib/Service/Redaction/RedactionIrreversibilityVerifier.php` with
  `RedactionLeakRoute`, `RedactionOutputModes` and `RedactionVerdictRecorder`.
- REQ-RWB-02 "Nothing is written until a person has checked it":
  `RedactionReviewGate`, `RedactionOutputGuard`,
  `RedactionReviewMarkRepository` and `DetectionRunIdentity`.
- The always-redact and never-redact lists: `entity-publication-policies`
  (`publicationProhibition`, `publicationConsent`, `PolicyMatchService`,
  `ProhibitionGateService`, `ProhibitionPolicyService`,
  `PolicyRuleNormaliser`).
- The Woo profile: `WooProfileService` (which entity types to anonymise).

`openregister/redaction-release-safeguards` (wave 1) carries REQ-RWB-01 and
REQ-RWB-02 into `FileService::anonymizeDocument()` as REQ-RRS-001 (verdict
`clean`, `leaking` or `unverifiable`, returned as `verification`) and
REQ-RRS-002 (`EntityRelation.decision`, a review mark at
`POST /api/files/{fileId}/anonymisation/review-mark`, enforced when
`anonymisation.requireReview` is on). `openregister/redaction-policy-as-data`
(wave 2) makes term lists and named policy profiles organisation data the
engine applies on every path.

This change closes no row itself. It supports the rows those two close:
4.5 "Redaction cannot be undone in the published file", 4.27 "An administrator
forbids unattended redaction, so nothing is released without a person having
decided", 18.1 "An organisation keeps a list of terms the product must always
redact, and a list it must never redact", 18.2 "A term list is scoped to one
request, and does not carry into the next", and 18.3 "The redaction policy is
a named configuration the organisation owns, and the product applies it to a
new request without an officer restating it". Build plan: new supporting
change, wave 3, size M.

## What changes

1. filinq requires the OpenRegister release that carries REQ-RRS-001 and
   REQ-RRS-002. Below it, filinq refuses to write a redacted copy and says
   which OpenRegister release it needs. It does not fall back to its own copy,
   because two verifiers that can disagree are worse than one.
2. Verification: filinq reads `verification` from OpenRegister's anonymise
   result and stores it on the `anonymizationLink` fields it already has
   (`verificationVerdict`, `verificationOutputMode`, `verificationRoutes`,
   `verificationLeakRoutes`, `verifiedAt`). Publication readiness accepts only
   `clean`. filinq's verifier classes are deleted.
3. Review: the workbench's "mark as reviewed" and every include or skip write
   OpenRegister's `decision` and review mark. OpenRegister enforces the gate.
   filinq's `documentReview` stays as the workbench's own record of who
   checked what, and is no longer the enforcement. filinq's gate classes are
   deleted. filinq's setting `filinq.review.checked_gate` becomes a link to
   OpenRegister's `anonymisation.requireReview`.
4. Term lists: a repair step moves filinq's prohibitions and standing consents
   that are term rules into OpenRegister's always-redact and never-redact
   lists, with their scope, and reports any rule it cannot express there.
   `PolicyMatchService` then reads OpenRegister's lists. Consent records about
   a person, which are more than a term, stay filinq's.
5. The Woo profile becomes an OpenRegister named policy profile, created once
   from filinq's stored profile. `WooProfileService` reads it.

## What does not change

- The review workbench, its preview, its decision table and its grounds
  pickers. They sit on top of the engine.
- REQ-RWB-03 (a publishable document is composed from redacted copies) and
  REQ-RWB-04 (a download gated on an accepted agreement). Those are filinq's
  composition and access rules, not engine guarantees.

## Fail closed

- No OpenRegister release with the safeguards: no redacted copy is written.
- A `verification` that is missing from OpenRegister's answer counts as
  `unverifiable`, never as `clean`.
- A rule the repair cannot express in OpenRegister's lists stays in filinq,
  still enforced by filinq, and is reported. Nothing is dropped.

## App absent

OpenRegister absent: filinq has no anonymisation today either, and nothing
changes. filinq absent: OpenRegister's guarantees apply to every other path,
which is the point of D2.

## Cross-app contract

OpenRegister side, from `openregister/redaction-release-safeguards`:
`POST /api/files/{fileId}/anonymize` and `FileService::anonymizeDocument()`
return `verification` with `verdict`, `outputMode`, `routes` and `leakRoutes`
(route and count, never the value); `PATCH /api/entity-relations/{id}` with
`decision`; `POST /api/files/{fileId}/anonymisation/review-mark`; a 409 with
one message when the gate refuses. The term list and profile methods come from
`openregister/redaction-policy-as-data`, which is not written yet: the
building agent reads that change on OpenRegister `development` and names the
methods in the PR body before writing the calls, and stops if it has not
merged. Each side tests its own half; filinq tests against OpenRegister's real
response shape.

## Dependencies and wave

- `openregister/redaction-release-safeguards` (wave 1) and
  `openregister/redaction-policy-as-data` (wave 2), both merged and in an
  OpenRegister release.
- `filinq/anonymization-review-workbench` (wave 2): its checked gate is
  re-pointed here, so build after it.
- `filinq/redaction-and-what-leaves-the-building` (11 of 12 tasks done, not
  archived): REQ-RWB-01 and REQ-RWB-02 live in its delta, so the main spec
  `redaction-output-guarantee` does not exist yet. That change archives first;
  until then this change's MODIFIED delta validates but cannot archive.
- Wave 3. Implements decision D2 for filinq.
