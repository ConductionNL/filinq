---
kind: code
---

## Why

**20 of DocuDesk's 21 schemas declare no `authorization` cascade, and OpenRegister
treats an unconfigured cascade as OPEN.**

This is not inferred. `ConsentCrudService::getConsent()` documents it in the code,
from a measured security finding (#283):

> ⚠️ But this is NOT what stops one user reading another's consent record… The
> `publicationConsent` schema declares `"authorization": null` in
> `lib/Settings/docudesk_register.json`, and OpenRegister treats an unconfigured
> authorization cascade as **OPEN** — so the per-object RBAC half permits the read
> for any authenticated caller in the org.
>
> The control that actually closes #283 is `ConsentController::canAccessConsent()`…
> Do not delete either as "redundant with OpenRegister RBAC" — measured, it is not
> redundant.

So for 20 of 21 schemas, **OpenRegister's RBAC is not the guard.** Anything that
protects those objects has to be an explicit check in DocuDesk's own code.

Gate-7 reports **9 controller methods with no authorisation guard in the body**:

```
TemplatePreviewController::previewTemplate     ConsentController::create
DocumentController::preview                    DocumentController::generateBulk
ComparisonController::compare                  BatchAnonymizationController::folderBatch
BatchAnonymizationController::batchExtract     BatchAnonymizationController::batchAnonymize
BatchAnonymizationController::batchReport
```

`DocumentController::preview()` is representative. It reads `templateId` from the
request, checks only that a user is **authenticated** — returning 401 when not — and
proceeds. There is no check that the caller may see *that* template, and `template`
is one of the 20 schemas with no cascade.

An authentication check is not an authorisation check. This is the precise defect
class that, once gate-7 stopped accepting a `401` as a guard, turned **0 findings
across 18 apps into 167 real IDORs** fleet-wide.

### A correction to the record

An earlier reading of this concluded the opposite — that OpenRegister's default-on
data layer already guarded these nine, because none of them passes `_rbac: false`.
That reasoning was wrong, and the way it was wrong is worth keeping:

`_rbac: true` means *"apply the cascade"*. It does not mean *"a cascade exists."*
With `authorization: null`, applying the cascade permits everything. Absence of an
opt-out is not presence of a guard.

## Scope of exposure

Bounded, and the bound matters for prioritisation:

- **Multitenancy is separate from RBAC and remains enforced.** The
  `ConsentCrudService` comment calls the multitenancy half "genuinely load-bearing",
  so cross-organisation reads are still blocked.
- The exposure is therefore **any authenticated user within the same organisation**
  reading or acting on another user's object by id.

That is a real IDOR, not a theoretical one, but it is not anonymous and not
cross-tenant.

## What Changes

- **An authorisation decision per schema.** For each of the 20, either declare an
  `authorization` cascade in `lib/Settings/docudesk_register.json`, or record why the
  data is legitimately org-readable (reference data, catalogues, shared templates).
- **An explicit guard on each of the 9 endpoints**, or a recorded finding that the
  endpoint's data is legitimately org-wide.
- **A test that fails when a schema is added without an authorisation decision**, so
  the count cannot silently return to 20.

## What this change deliberately does NOT do

It does not add nine guards in one commit. Each of the nine needs its own answer to
"who may do this to this object", and a guard written to satisfy a gate rather than a
threat model is how `canAccessConsent()`-shaped controls get deleted later as
"redundant with OpenRegister RBAC" — which the code comment above explicitly warns
against, because someone already tried.

The schema-level decision comes first: a declared cascade fixes a whole class at the
data layer, and only the endpoints it cannot cover need a controller guard.

## Capabilities

### New Capabilities
- `consumer-schema-authorization-audit`: what a consumer app must decide about
  authorisation for every schema it owns, and what it may not assume OpenRegister is
  doing for it.

## Impact

- **Data**: `lib/Settings/docudesk_register.json` — an `authorization` block per
  schema is a schema change and therefore needs an `info.version` bump, or the import
  is skipped and the change never deploys.
- **Code**: guards on up to 9 controller methods.
- **Risk of the fix**: an over-tight cascade locks users out of their own data. Each
  schema needs the decision made deliberately, which is why this is a change with a
  spec rather than a sweep.

## Related

- Found while triaging gate-7 for [ConductionNL/.github#475](https://github.com/ConductionNL/.github/pull/475).
- ⚠️ **This finding is fleet-shaped.** Every consumer app that assumed OR's RBAC was
  guarding it has the same question to answer. DocuDesk is where it was measured, not
  necessarily where it is worst.
