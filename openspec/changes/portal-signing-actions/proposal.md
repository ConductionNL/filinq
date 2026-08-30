---
kind: code
depends_on: [portal-contribution, signing-trust-rebuild]
tracking_issue: https://github.com/ConductionNL/filinq/issues/160
---

# Proposal: portal-signing-actions

## Why

`portal-contribution` gave an external **signer** (a person WITHOUT a Nextcloud
account) a READ-ONLY window into Filinq through **portaliq**, the shared
external portal (hydra ADR-046, contribution contract v2). That change
deliberately shipped `actions: []` on the signer manifest (`REQ-DDPORT-006`) and
recorded sign/decline as deferred A6 endpoint actions "so the receiver +
`PortalAssertionVerifier` land as a reviewed unit" (its design.md "Deferred
actions"), tracked on Conduction/filinq#160.

This is the headline follow-up: it gives that signer a real outside signing
FRONTEND. An invited external signer can, from portaliq's SPA, read the document
awaiting them and then **sign** or **decline** it — with no Nextcloud account —
because portaliq forwards the act server-to-server to a guarded Filinq
receiver under a short-lived signed subject assertion. Without this change the
only way to sign remains an in-app Nextcloud session, which the whole premise of
portaliq (accountless externals, ADR-046) excludes; Filinq's signing product
cannot serve external counterparties at all.

The write path is only honest to sell once two preconditions land, hence the
`depends_on`:

- `portal-contribution` ships the plain `PortalContributionProvider` and the
  `signer` audience whose manifest this change extends.
- `signing-trust-rebuild` makes `SigningService::sign()` / `decline()` HONEST
  (terminal-state status machine on `decline()`, identity-bound assertion MAC,
  provider/level honesty). This change consumes that honest primitive rather
  than re-implementing signing.

Tracking issue: Conduction/filinq#160.

## What Changes

- **Signer manifest gains three A6 endpoint actions.** Extend
  `lib/Portal/PortalContributionProvider.php` (still plain, dependency-free) so
  the `signer` manifest declares contract-v2 `{id, label, endpoint, method,
  minTrust}` actions `sign` (POST), `decline` (POST, reason in body) and
  `viewDocument` (GET), all `minTrust: substantial`, all pointing at
  instance-local relative paths under `/apps/filinq/api/portal/signing/`. The
  `data-subject` manifest stays read-only.
- **A bearer-guarded Filinq receiver.** A new controller exposes the three
  `#[PublicPage]` + `#[NoCSRFRequired]` receiver routes portaliq forwards to.
- **`PortalAssertionVerifier`.** Validates portaliq's frozen `X-Portal-Subject`
  HS256 assertion (signature against the portaliq-managed shared secret;
  `alg = HS256` only; `iss = portaliq`; `use = assertion`; unexpired; the frozen
  claim set) and derives `subjectRef` / `audience` / `organisation` / `trust` /
  `jti` and the resolved `signerEmail` scope claim SERVER-SIDE — never from the
  request body or `Authorization` header.
- **Server-side signer resolution + IDOR guard.** The receiver resolves the
  invited `signerRecord` (`email == assertion signerEmail` AND
  `signingRequestId == target`) before any act, so no signer can act on a
  request they were not invited to; the client's `signingRequestId` is treated
  as an opaque id only (full URLs / paths rejected — SSRF).
- **Honest sign/decline via a verified-actor entrypoint.** The receiver drives
  `SigningService::sign()` / `decline()` as the resolved external signer (email
  identity, no Nextcloud uid), preserving every existing guard, and records each
  act in the signing audit (portal email + assertion `jti`).
- Close Conduction/filinq#160 with live-verified evidence once applied.

## Capabilities

### Added Capabilities

- `portal-signing-actions`: an external, accountless signer signs or declines a
  document — and reads it first — through portaliq's A6 endpoint actions,
  validated by a fail-closed Filinq receiver that derives identity from a
  signed subject assertion and drives the honest `SigningService` primitive.

## Affected Projects

- [x] Project: `filinq` — extend `lib/Portal/PortalContributionProvider.php`; new `lib/Controller/PortalSigningReceiverController.php` + `lib/Portal/PortalAssertionVerifier.php`; a verified-actor entrypoint on `lib/Service/SigningService.php`; routes in `appinfo/routes.php`; unit tests under `tests/unit/Portal/`; Newman receiver contract; this OpenSpec change.
- Contract: `apps-extra/portaliq` — the A6 "Endpoint bearer-forward actions" and "Frozen assertion wire format" requirements (`openspec/specs/portal-contribution-contract/spec.md`) this receiver is templated against; runtime consumer that forwards the actions.
- Reference: `hydra` ADR-046 (portaliq external portal, contribution contract v2, A6).
- Depends on: `portal-contribution` (signer manifest), `signing-trust-rebuild` (honest sign/decline).

## Out of Scope

- Any portal UI, session, auth edge or rendering — portaliq owns the entire
  external surface (ADR-046); Filinq ships zero portal frontend, only the
  receiver.
- Any change to portaliq itself. This change is templated against portaliq's
  FROZEN assertion wire format and its A6 forward; the portaliq-side amendment
  that forwards the resolved `signerEmail` scope claim in the A6 assertion is a
  named apply-blocker dependency, not authored here (design.md "Open questions").
- The `data-subject` objection-intake write path — still deferred
  (`portal-contribution` design.md); this change touches the `signer` audience
  only.
- Full PAdES/CMS chain validation, deed-of-signing artifacts, NL identity rails
  and bulk send — owned by `signing-trust-rebuild` / `signer-identity-rails` /
  `bulk-signing-field-builder`.

## Success Criteria

- `openspec validate portal-signing-actions --strict` exits 0.
- The `signer` manifest declares exactly `sign`, `decline`, `viewDocument` as
  substantial-gated A6 endpoint actions with instance-local relative endpoints;
  the `data-subject` manifest's `actions` stays empty.
- The receiver rejects a missing/invalid/expired/wrongly-signed assertion, an
  `alg` other than `HS256`, a wrong `iss`/`use`, and an unconfigured shared
  secret — all `401` — before any OpenRegister or `SigningService` call.
- Signer identity is derived only from the verified assertion; a body-supplied
  `email`/`userId`/`subjectRef`/`claims` never changes it.
- A signer who is not an invited `signerRecord` on the target request gets the
  identical not-authorised result as a non-existent request (no existence
  oracle) and `SigningService` is never called; a full-URL `signingRequestId` is
  rejected.
- A verified invited signer's `sign`/`decline` flows through the honest
  `SigningService` primitive, preserves the terminal-state and
  belongs-to-request guards, and writes a signing-audit entry carrying the
  portal email and assertion `jti`.
- `composer phpcs`, `phpstan`, `psalm` and the unit suite pass on the new files
  with zero new violations.
