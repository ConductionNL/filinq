# Proposal: orphan-auth-remediation

kind: code

## Why

Hydra gate-6 (`orphan-auth`, OWASP A01:2021 — a defined-but-never-called
authorization method is identical to having no check at all) was recently
un-blinded (its file glob went from non-recursive to recursive). On clean
`origin/development`, docudesk reports **3** orphan "auth-verb" methods:

| # | Location | Method |
|---|----------|--------|
| 1 | `lib/Service/Signing/SigningProviderInterface.php:72` | `checkStatus` |
| 2 | `lib/Service/Signing/NativeSigningProvider.php:151` | `checkStatus` |
| 3 | `lib/Service/Signing/ValidSignProvider.php:115` | `checkStatus` |

All three are the **same** method — the interface declaration plus its two
implementations. The gate flags them only because the name begins with the
`check` auth-verb (`is|requires?|validate|authorize|check|ensure|verify|assert`).

Verification (see `design.md`) establishes that `checkStatus` is **not** an
authorization guard: it is a provider-contract **status read** that returns a
signing flow's `status`/`signers`/`completedAt`. It has zero native callers
because the async external-provider status-poll leg is a **pluggable extension
point** — external providers (e.g. ValidSign) implement it, invoked through the
provider flow, not the app's live status path. The live "get sign status"
surface is OR's `ApprovalChain` read via `SigningController::showRequest`, which
is fully authenticated (per-user UID authorization; 401/403 on the live path).

Verdict for all three: **legit plugin seam** — no live authorization defect.
This is a documentation + annotation remediation, not a wire or delete. No
live document-access or signing path is left unprotected.

## What Changes

- Annotate the three `checkStatus` docblocks (interface + both implementations)
  with an explicit orphan-auth seam note explaining why they have no native
  caller and pointing at this change's `design.md`. Method bodies are
  untouched, so the gate's pre-existing-method filter keeps gate-6 green while
  the annotation prevents future re-triage.
- Add a canonical spec requirement to
  `signing-via-or-approval-with-provider-plugins` documenting that the provider
  async-flow methods are a pluggable extension seam, not authorization guards.

## Impact

- **Security posture:** unchanged; confirms (not alters) that no live signing or
  document-access path is unprotected.
- **Behaviour:** none — docblock + spec text only; no method body, route, or
  register change.
- **Tests:** none added (no wire, no delete); suite unchanged.
