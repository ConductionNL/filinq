# Design — retrofit-2026-05-24-template-management

**Retrofit change. Tasks describe retroactive annotation, not new implementation work.**

## Goal

Bring 19 already-shipped methods under `template-management` coverage by drafting 5 numbered REQs (REQ-TMPL-08 .. REQ-TMPL-12) that describe their observed behavior, then attaching `@spec` annotations from each method to the matching task.

## Method → Task Map

| File | Method | Task |
|------|--------|------|
| `lib/Controller/TemplatesController.php` | `versions` | task-1 |
| `lib/Controller/TemplatesController.php` | `restoreVersion` | task-1 |
| `lib/Service/TemplateVersionService.php` | `createVersion` | task-1 |
| `lib/Service/TemplateVersionService.php` | `getVersions` | task-1 |
| `lib/Service/TemplateVersionService.php` | `getVersion` | task-1 |
| `lib/Service/TemplateVersionService.php` | `getNextVersionNumber` | task-1 |
| `lib/Service/TemplateVersionService.php` | `restoreVersion` | task-1 |
| `lib/Controller/TemplatesController.php` | `diffVersions` | task-2 |
| `lib/Service/TemplateVersionService.php` | `getDiff` | task-2 |
| `lib/Controller/TemplatesController.php` | `duplicate` | task-3 |
| `lib/Service/TemplateService.php` | `duplicateTemplate` | task-3 |
| `lib/Controller/TemplatesController.php` | `lock` | task-4 |
| `lib/Controller/TemplatesController.php` | `unlock` | task-4 |
| `lib/Service/TemplateService.php` | `acquireLock` | task-4 |
| `lib/Service/TemplateService.php` | `releaseLock` | task-4 |
| `lib/Service/TemplateService.php` | `isLockExpired` | task-4 |
| `lib/Controller/TemplateRequestHandler.php` | `parseListParams` | task-5 |
| `lib/Controller/TemplateRequestHandler.php` | `parseBodyParams` | task-5 |
| `lib/Controller/TemplateRequestHandler.php` | `buildErrorResponse` | task-5 |

## Granularity calls

- **Versioning collapsed to one REQ (REQ-TMPL-08).** The five service methods + two controller endpoints are one coherent observable behavior — "every template-write produces a snapshot, snapshots are listable, a snapshot can become the new head." Splitting them per-method would inflate the spec without buying review clarity.
- **Diff broken out (REQ-TMPL-09).** Diff is a thin read-only helper that doesn't write to the version schema and has its own input-validation surface (`from`/`to` required). Distinct observable behavior.
- **Duplication broken out (REQ-TMPL-10).** Writes to the template schema, not the version schema, and surfaces a Dutch-literal suffix worth flagging in Notes.
- **Lock acquire/release/expiry collapsed to one REQ (REQ-TMPL-11).** Three service methods + two controller endpoints implement one coherent advisory-lock behavior with a TTL. Splitting would force scenarios to cross-reference each other.
- **Shared helpers grouped (REQ-TMPL-12).** Three pure helpers that are reused across every controller method — one REQ documents the shared parsing/error contract.

## What this change does NOT do

- No code logic changes — observed behavior only.
- No tightening of observed-but-suspicious behavior (Dutch literal `(kopie)`, `total+1` version numbering, advisory-only lock semantics) — those are surfaced in REQ Notes as TODOs.

## Source

- `openspec/coverage-report.json` generated 2026-05-24
- Cluster: `bucket_2a.template-management`
