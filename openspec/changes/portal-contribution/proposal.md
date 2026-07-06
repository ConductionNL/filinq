---
kind: code
tracking_issue: https://github.com/ConductionNL/docudesk/issues/160
---

# Proposal: portal-contribution

## Why

hydra ADR-046 establishes **portaliq** as the ONE shared external portal for
people WITHOUT a Nextcloud account, and its contribution contract v2.1 defines
how a domain app opts in: by shipping a single plain, dependency-free class at
the convention FQCN `OCA\{App}\Portal\PortalContributionProvider`, which
portaliq discovers and duck-types (`method_exists`, never `instanceof`). The
class is inert when portaliq is not installed (amendment A1).

DocuDesk has two natural external audiences that today have **no** self-service
surface:

- a **data subject** — a WOO-affected entity who was notified that a document
  mentioning them is about to be published and who has a legal objection window
  (`consent-management` / CONS-011); and
- a **signer** — an external party invited to sign a document who is not a
  Nextcloud user (`document-signing`).

Both are exactly the "person without a Nextcloud account" portaliq exists for.
This change contributes their read surfaces declaratively, reusing the
petstore/pipelinq reference shape, so that when portaliq is installed a data
subject can see their consent/objection status and a signer can see the
documents awaiting their signature — with every staff-only and other-party
column projected out server-side.

Tracking issue: Conduction/docudesk#160.

## What

Ship one plain class `lib/Portal/PortalContributionProvider.php` (no portaliq
import, no `implements`, no info.xml dependency, no constructor) that returns a
pure-data manifest per audience:

1. **`data-subject`** — one read collection `subjectConsents` over
   `consent`/`publicationConsent`, scoped by the `contactRef` contact-record
   reference (claim `contactId`), gated at `minTrust: substantial`, projected to
   twelve subject-safe transparency + objection-rights fields.
2. **`signer`** — two read collections over the `signing` register:
   `signerRecords` (`signerRecord`, scoped directly by the invited `email` /
   claim `signerEmail`) and `signerSigningRequests` (`signingRequest`, reached
   through the contract-v2.1 one-hop `via` join over the subject's own
   `signerRecord` rows, gated `substantial`).

No register JSON is modified: every scopeField, `via` field and projected field
already exists on the shipped schemas at HEAD. No routes, controllers, services,
frontend or info.xml change. Create-actions and A6 endpoint actions (lodge an
objection, sign/decline) are deferred — see design.md "Deferred actions".

## Capabilities

### Added Capabilities

- `portal-contribution`: DocuDesk contributes `data-subject` and `signer` read
  surfaces to portaliq via a plain, dependency-free provider class with
  server-side field projection and UUID/email claim scoping (ADR-046 v2.1).

## Affected Projects

- [x] Project: `docudesk` — new `lib/Portal/PortalContributionProvider.php`, unit tests under `tests/unit/Portal/`, this OpenSpec change. No register or runtime-wiring changes.
- Reference: `apps-extra/petstore` — the fleet-reference provider (copied shape).
- Reference: `apps-extra/pipelinq` — multi-audience + field-projection + register-drift-pin reference.
- Reference: `hydra` ADR-046 (portaliq external portal, contribution contract v2.1).
- Runtime consumer: `apps-extra/portaliq` — discovers and renders the contribution when installed.

## Out of Scope

- Any portal UI, auth edge, inbox, session or rendering — portaliq owns the
  entire external surface (ADR-046); DocuDesk ships zero portal frontend.
- Create-actions and A6 endpoint actions (objection intake, sign/decline) —
  deferred to a follow-up (design.md "Deferred actions").
- Any change to `lib/Settings/docudesk_register.json` — the manifest reuses
  existing schema properties verified at HEAD.
- Any change to portaliq itself, or to DocuDesk's existing consent/signing
  services.

## Success Criteria

- `openspec validate portal-contribution --strict` exits 0.
- `new PortalContributionProvider()` constructs with no dependencies; the class
  declares no `implements` clause and references no portaliq symbol.
- `getAudiences()` returns `['data-subject', 'signer']`; `getAudience()` returns
  `'data-subject'`; a non-served audience yields `null`.
- Each read collection scopes to the subject and projects only the documented
  subject-safe fields; no staff-only or other-party column appears in any
  whitelist.
- The register-drift pin proves every scopeField, `via` field and projected
  field exists on the shipped schema at HEAD.
- `composer phpcs`, `phpstan`, `psalm` and the unit suite pass on the new files
  with zero new violations.
