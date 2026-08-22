# document-creatie-sjablonen Specification (delta)

---
status: proposed
---

## Purpose

Extend the document-generation audit trail (REQ-DCS-06/DCS-072 family): a
generation driven by a guided wizard records the full interview context —
wizard id, wizard object version, and answers — on the `generatedDocument`
object, making every wizard-driven generation reproducible evidence. Existing
requirements are unchanged; this delta only ADDs.

## ADDED Requirements

### Requirement: Wizard-driven generations record the interview context (REQ-DDGDW-008)

The resulting `generatedDocument` object MUST, whenever a generation request
carries `options.wizardContext`, include a `wizardContext` property
containing the wizard's id, the wizard object's OpenRegister version at run
time, and the submitted answers, alongside the existing `templateId`,
`templateVersion`, and `dataRefs` metadata (DCS-051/DCS-072) — so the exact
interview that produced a document can be audited and replayed. The
`generatedDocument` schema in `lib/Settings/filinq_register.json` MUST gain
`wizardContext` as an optional property (document register bump `2.2.0` →
`2.3.0`, additive only), and generations without a wizard MUST omit it and
behave exactly as before this change. Stored answers are personal data:
access follows the register's existing RBAC and the property MUST be deleted
with the object (no separate copy).

#### Scenario: Generated document carries the wizard context

- GIVEN a completed wizard run that generated a document
- WHEN the logged `generatedDocument` object is fetched
- THEN it contains `wizardContext` with the wizard id, the wizard version at run time, and every submitted answer
- AND the existing `templateId`, `templateVersion`, and `dataRefs` metadata are present unchanged
- @e2e tests/e2e/spec-coverage/guided-document-wizard.spec.ts

#### Scenario: Non-wizard generations are unaffected

- GIVEN a direct API generation without `options.wizardContext`
- WHEN the document is generated and logged
- THEN the `generatedDocument` object has no `wizardContext` property
- AND the response is byte-identical in shape to the pre-change contract
- @e2e exclude absence-of-property regression pin; covered by PHPUnit (tests/unit/Service/DocumentServiceTest.php::testGenerationWithoutWizardContextUnchanged)
