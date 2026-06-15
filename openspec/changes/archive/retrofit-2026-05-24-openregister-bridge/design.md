# Design — retrofit-2026-05-24-openregister-bridge

**Retrofit change. Tasks describe retroactive annotation, not new implementation work.**

## Goal

Mint a new `openregister-bridge` capability describing the observed contract of `OpenRegisterResolver` — a 3-method config-translation seam consumed by `TemplateService` and `TemplateVersionService`.

## Why a new capability instead of extending template-management

- `OpenRegisterResolver` is reused outside the template path: it gates the version-history schema separately.
- Future register-backed features (signing requests, document register, dossier register, consent log) will need the same config-translation seam. Keeping the bridge standalone surfaces the contract.
- The class is independent of any consumer's data model. Embedding the resolver REQ inside template-management would tie the resolver's evolution to template feature changes.

## Method → Task Map

| File | Method | Task |
|------|--------|------|
| `lib/Service/OpenRegisterResolver.php` | `getRegisterAndSchema` | task-1 |
| `lib/Service/OpenRegisterResolver.php` | `getVersionRegisterAndSchema` | task-1 |
| `lib/Service/OpenRegisterResolver.php` | `validateNamespace` | task-1 |

## Granularity calls

- **All 3 methods collapse to one REQ.** They implement one cohesive observable contract: "translate config → register/schema, enforce namespace shape." Splitting per-method would inflate the spec.
- The two lookups share an identical failure mode (500 on missing config) and an identical input source (`SettingsService::getAllSettings()['configuration']`), so they belong in one REQ.
- Namespace validation joins the same REQ because the regex is the shape used by every register-scoping operation that the resolver intermediates.

## Notable observed-but-suspicious behavior surfaced in REQ Notes

- Resolver caches what `SettingsService` has cached — no live re-read on each call.
- 500 status used for both register-missing and schema-missing; callers cannot distinguish without message parsing.
- Namespace regex duplicated implicitly with REQ-TMPL-03's namespace contract; TODO note added.
- Expansion model for future registers is deferred (single class? sibling resolvers?).

## What this change does NOT do

- No code logic changes — observed behavior only.
- Does not pre-emptively expand the resolver for other registers (signing, dossier, document).
- Does not extract the namespace regex to a shared constant.

## Source

- `openspec/coverage-report.json` generated 2026-05-24
- Cluster: `bucket_2b.openregister-bridge`
