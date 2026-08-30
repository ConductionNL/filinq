# Tasks

## 1. Establish the ground truth

- [x] Record, per schema, whether it has an `authorization` cascade (measured: 1 of 21 does)
- [x] For each of the 9 gate-7 endpoints, record which schema it touches and whether that schema is open

Acceptance criteria:
- The record distinguishes "open by decision" from "open by omission". Only the second is a defect.
- Confirm multitenancy is still enforced, so the exposure bound in the proposal is measured rather than assumed.

Measured 2026-08-16 on the development instance:
- 20 of 21 schemas had no cascade; all three `filinq` **register** rows also had `authorization = NULL`, so the register-level cascade did not fill the gap either.
- The gate-7 count is **17 across the whole app**, not 9 — the 9 was a diff-scoped run (ADR-020). All 17 route through `ObjectService::find()` / `findAll()` with `_rbac: true`.
- Bound verified: `ddauth-carol` (different organisation) received **404** on the object `ddauth-bob` (same organisation) read with **200**. Multitenancy is enforced.
- The defect is worse than disclosure: `ddauth-bob`, in no groups, **overwrote** `ddauth-alice`'s template content via `PUT` (HTTP 200) and duplicated it.

## 2. Decide authorisation per schema

- [x] For each of the 20 open schemas, declare an `authorization` cascade or record why org-wide readability is correct
- [x] Bump `info.version` in `lib/Settings/filinq_register.json`

Acceptance criteria:
- Without the version bump the import is SKIPPED and the change never deploys to any existing install. Verify the cascade is live on a running instance, not just present in the file.
- Verify a locked-down schema still lets its legitimate owner read their own objects. An over-tight cascade is a visible outage; that is the safer failure, but it is still a failure.

Decisions recorded in `docs/authorization-decisions.md`. `info.version` 7.8.0 → 7.9.0.
Verified live after import: all 21 cascades present in `oc_openregister_schemas`, and the four declared groups auto-provisioned (empty, deliberately).
Owner path intact: `ddauth-alice` still reads **and updates** her own template (200); `ddauth-bob` is refused on update (**403**).
Read restriction bites: `ddauth-bob` gets **404** on another user's `prohibitionOverrideAudit` while its owner gets **200** — and 404, not 403, so the refusal is not an existence oracle.

⚠️ `prohibitionOverrideAudit.create` is deliberately left `authenticated`. Register v7.7.0 records that restricting it re-breaks the fail-closed override path for the ordinary operator, raising 500 on every acknowledged override.

## 3. Guard what the cascade cannot cover

- [x] For each of the 9 endpoints, add a per-object guard OR record that its data is legitimately org-wide
- [x] Make each guard state which actor it excludes and why the data layer does not already exclude them

Acceptance criteria:
- No guard is added solely to clear the gate. `ConsentCrudService` documents someone already reasoning their way to deleting a real control as "redundant with OpenRegister RBAC" — an unjustified guard is that control with the reasoning removed.
- A refusal must not reveal whether the requested id exists.

**No controller guard was justified, and none was added.** Every flagged endpoint reaches its data through `ObjectService` with RBAC on, so the cascade guards them. Verified on the exemplar the spec names, `DocumentController::preview`: carol (other org) → **404**, bob (same org) → **200** with rendered content, which is the recorded decision that templates are organisation-shared assets.

What the cascade genuinely cannot cover is the **22 deliberate `_rbac: false` call sites in 9 files**. Each was audited and each already carries a compensating control — `PolicyCrudService` asserts group membership for every action including `read` before its lookup; the signer portal binds a token *and* an email *and* a request id. They are recorded in `docs/authorization-decisions.md` and pinned by the ratchet rather than "fixed".

## 4. Ratchet

- [x] Add a test asserting every schema in the register has an authorisation decision
- [x] Add a test asserting no flagged endpoint relies on a 401 alone

Acceptance criteria:
- The ratchet asserts COVERAGE per schema, not a findings count. A count passes the moment someone adds an exclude; coverage requires a decision.
- Adding a schema with no decision fails on the day it is added.

`tests/unit/Settings/SchemaAuthorizationCoverageTest.php`, 6 tests. The second bullet is implemented as the invariant that actually holds: a 401 is not the guard, the cascade is — so the test pins the set of `_rbac: false` bypasses that step around it. A new bypass fails until it is recorded with its compensating control; a removed one fails too, so a justification cannot outlive its call site.

**Each assertion was shown to fail before it was trusted.** Six plants, all caught: a stripped cascade, a missing action, an anonymous read grant, a typo'd group, a new bypass, a rolled-back version.

⚠️ The typo plant initially **passed**: `docudesk-template-editor` is a prefix of `docudesk-template-editors`, and the check used `str_contains`. That is the exact failure mode the assertion exists for, so the check was rewritten with explicit identifier-boundary lookarounds (`\b` is unusable — `_` is a word character and `-` is not). Full suite green afterwards: 1415 tests.

## 5. Raise it beyond this app

- [ ] Report the finding to the fleet: any consumer app assuming OR's RBAC guards it has the same question

Acceptance criteria:
- Filinq is where this was measured, not necessarily where it is worst.
