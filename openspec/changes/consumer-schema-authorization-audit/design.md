## Context

Two facts, both measured on 2026-08-16, and neither obvious from reading a
controller:

1. **20 of 21 DocuDesk schemas declare no `authorization` cascade**, and
   OpenRegister treats an unconfigured cascade as OPEN.
2. **9 controller methods have no authorisation guard in their body**, and at least
   one of them (`DocumentController::preview`) acts on a request-supplied id behind
   nothing but a `401`.

Either alone is arguable. Together they mean the app is relying on a guard that is
not there.

## Goals / Non-Goals

**Goals:**
- An explicit authorisation decision for every schema DocuDesk owns.
- A guard on every endpoint the schema decision cannot cover.
- A ratchet so the schema count cannot drift back.

**Non-Goals:**
- Nine guards in one commit. See D2.
- Changing OpenRegister's open-by-default behaviour. That is OR's decision to make
  and would be a fleet-wide breaking change; this app's job is to stop assuming
  otherwise.
- Auditing other apps. The finding is fleet-shaped and should be raised, but each
  app owns its own schemas.

## Decisions

### D1 — Fix at the schema layer first, the controller layer second

A declared cascade fixes a whole class of access at the data layer, for every caller
including ones that do not exist yet. A controller guard fixes one endpoint and must
be repeated, correctly, at every future call site.

So the schema decision comes first, and only what it cannot express becomes a
controller guard.

⚠️ A schema change needs an `info.version` bump or the import is skipped and the
change never deploys — silently, on every existing install. That has bitten this
codebase before.

### D2 — Do not write nine guards to clear a gate

Each of the nine needs its own answer to "who may do this, to this object". A guard
written to satisfy a checker rather than a threat model looks identical to a real
one in a diff, and is deleted the moment someone reasons that the data layer covers
it.

That is not hypothetical here. `ConsentCrudService` carries the comment *"Do not
delete either as 'redundant with OpenRegister RBAC' — measured, it is not
redundant"*, which exists because someone had already reasoned their way there. An
unjustified guard is that control with the reasoning stripped out.

### D3 — State the bound

Multitenancy is a separate axis from RBAC and remains enforced, so the exposure is to
authenticated users **within one organisation** — not anonymous, not cross-tenant.

Both available summaries are wrong: "it's an IDOR" reads as anonymous and drives
panic-shaped work; "RBAC covers it" is the belief that produced the gap. The bound
has to be carried with the finding.

### D4 — Ratchet on schema count, not on findings

The test asserts that every schema has a decision, not that some number of findings
is zero. A findings-count ratchet passes as soon as someone adds an `@exclude`; a
coverage assertion requires an actual decision per schema, and a new schema without
one fails on the day it is added rather than at the next audit.

## Seed Data (ADR-001)

**None** in the sense of new objects. The change edits authorisation blocks on
existing schemas in `lib/Settings/docudesk_register.json`; no schema is introduced.

## Declarative-vs-imperative decision (ADR-031)

| Behaviour | Path | Rationale |
|---|---|---|
| Per-schema authorisation | **Declarative** | `authorization` in the schema register is exactly the declarative surface ADR-031 prefers, and it applies to every caller rather than every call site. |
| Per-endpoint guard | **Imperative** | Only where the cascade cannot express the rule — e.g. an ownership comparison the cascade has no vocabulary for. |
| Coverage ratchet | **Imperative** | A test over the register file. |

## Risks / Trade-offs

**An over-tight cascade locks users out of their own data**, and the failure is
immediate and visible — which is the safer direction than the current one, where the
failure is invisible and someone else reads your document.

**A cascade is only as good as its deployment.** The `info.version` bump is the whole
difference between a fix and a fix-shaped commit.

**Nine endpoints is a lot of threat-modelling.** Accepted. The alternative — nine
guards written quickly — produces controls nobody can justify later, and the codebase
already contains a comment written by someone defending a real control from exactly
that fate.
