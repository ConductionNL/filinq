---
kind: spec
depends_on: []
---

# Proposal: anonymization-main-spec-valid

## Summary

The main `anonymization` spec becomes structurally valid, so the deltas that target it (among them `image-redaction`) can archive, without changing anything it requires.

- Rows: supporting, unblocks the archive of `filinq/image-redaction` (https://github.com/ConductionNL/filinq/issues/1347), row 4.24.
- Wave 1 (spec only, no code).
- Dependencies: none.
- Decisions: none bears on it; it carries out a follow-up of the Woo capability programme.
- Build rules: openspec/woo-build-rules.md

## Why

`openspec validate anonymization --type spec --strict` on `development` (2026-10-06) fails with two
errors:

```
✗ [ERROR] file: Requirement header "### Requirement: File Input via OR File Attachments (REQ-ANON-00)" appears outside the main ## Requirements section. Main specs only parse requirements inside that section, so this requirement is currently invisible to validate, list, and archive.
✗ [ERROR] file: Requirement header "### Requirement: Anonymization Confidence is a Calculation (REQ-ANON-CAL)" appears outside the main ## Requirements section. Main specs only parse requirements inside that section, so this requirement is currently invisible to validate, list, and archive.
```

The two requirements sit under `## OR Adoption decisions (from docudesk-adopt-or-abstractions)`,
above `## Requirements`. OpenSpec parses only what is inside `## Requirements`, so REQ-ANON-00 and
REQ-ANON-CAL are invisible to validate, list and archive, and every change with an `anonymization`
delta reports "Archive would refuse this delta: target spec is structurally invalid". The
`image-redaction` amendment of the Woo programme is one of them.

## What changes

1. In `openspec/specs/anonymization/spec.md`, the two requirement blocks (REQ-ANON-00 and
   REQ-ANON-CAL, each with its `@e2e exclude` line, priority, statement, scenarios and table) move,
   byte for byte, from above `## Requirements` to the top of that section. The decisions list stays
   where it is. This is done in this change's PR, so the main spec is valid as soon as it merges.
2. The delta restates both requirements unchanged under `## MODIFIED Requirements`, so archiving
   this change rewrites them with the same text and records the repair.

After the move, `openspec validate anonymization --type spec --strict` answers
"Specification 'anonymization' is valid" (only the existing informational notes on long
requirements remain), the spec lists 28 requirements instead of 26, and `openspec validate
image-redaction --strict` no longer reports that its archive would be refused.

## What does not change

Every word of every requirement and scenario. A sorted line diff of the spec before and after shows
only two added blank lines. No code changes.
