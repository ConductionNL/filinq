# Tasks: redaction-and-what-leaves-the-building

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 10. -->

## 1. Schemas

- [x] 1.1 Add `redactionReviewMark` (document, detection run, checkedBy, checkedAt) and `downloadAgreement` with its acceptance to the `filinq` register, with authorization cascades and a descriptor version bump (REQ-RWB-02, REQ-RWB-04)

## 2. The irreversibility guarantee

- [x] 2.1 `RedactionIrreversibilityVerifier`: check the produced bytes for text under the mark, embedded previews, XMP and EXIF, incremental updates, annotations and form fields, and embedded attachments (REQ-RWB-01)
- [x] 2.2 Run the verifier on every output mode including PDF/A and PDF/UA; a new output mode with no verification entry fails the suite (REQ-RWB-01)
- [ ] 2.3 Record the verification result on the `anonymizationLink` so a published copy can be shown to have been checked (REQ-RWB-01)

## 3. The review gate

- [x] 3.1 Refuse output server-side until a `redactionReviewMark` exists for the document and its current detection run (REQ-RWB-02)
- [x] 3.2 Clear the mark when detection is re-run, and say so in the refusal message (REQ-RWB-02)
- [x] 3.3 Enforce the same gate on the API, the batch path and the leaf, with one message that tells the operator what to do (REQ-RWB-02)

## 4. Composition and the agreement

- [x] 4.1 Compose a publishable document over the records a named saved view returns, resolving each redacted copy through its `anonymizationLink` and referencing no original (REQ-RWB-03)
- [x] 4.2 Gate a download on an accepted `downloadAgreement`, recording who, when and which version; a new version asks again and an unwritable acceptance stops the download (REQ-RWB-04)

## 5. Quality

- [x] 5.1 PHPUnit inside the container for every verification route, the gate on all three paths, the composition and the agreement; 75% on new code (ADR-009)
- [~] 5.2 Playwright `tests/e2e/redaction-gate.spec.ts`; Dutch and English strings; docs in `docs/features/redaction.md` with screenshots; tell dossiq the review leaf id

## Status, 2026-09-18

**What was already here, and what was not.** The schemas (1.1), the
irreversibility verifier (2.1, 2.2) and the review gate's decision class landed
earlier, in #1122 and #1123. Measured before starting: `RedactionReviewGate`
had eleven green cases and **no call site**. Nothing in `lib/` referenced it.
Every output path ran past it, and the suite reported that as green, because a
decision class tested in isolation passes whether or not anything asks it.

**So 3.1 to 3.3 were the work, not a redirect.** The gate now sits in
`AnonymizationService::runAnonymize()`, which is the one funnel behind both
public entry points and therefore behind the API, the batch path and the folder
job. `AnonymizationServiceReviewGateTest` asserts the consequence rather than
the call: with an unreviewed document, `DocumentAnonymizeRunner::run()` must not
happen at all, because a document that reaches the runner gets a file written
for it.

**The mark binds to the run by what the run found**, not by a counter. A
re-detection that finds one name more is a different run, so the earlier check
stops covering it with nothing having to remember to clear anything. A
re-detection that finds the same names in a different order is the same run, so
an operator is not trained to click through a refusal.

**Still open:**

- **2.3**, recording the verification result on the `anonymizationLink`. The
  verifier runs over produced bytes in its own suite; nothing yet writes its
  verdict onto the link.
- **5.2 is partial**: `tests/e2e/redaction-gate.spec.ts` is written and tagged
  to six scenarios. The Dutch and English strings, `docs/features/redaction.md`
  and telling dossiq the review leaf id are not done.
- The review surface itself stays with `anonymization-review-workbench`, which
  is where this change assumed it.
