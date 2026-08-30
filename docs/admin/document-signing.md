---
id: document-signing-admin
title: Document signing — administrator guide
sidebar_label: Document signing
sidebar_position: 3
description: Configure Filinq's signing providers, register/schema bindings, default signature levels, and signing-request expiry.
keywords:
  - signing
  - administrator
  - configuration
  - ValidSign
  - eIDAS
---

# Document signing — administrator guide

This guide walks a Nextcloud administrator through enabling, configuring and
operating Filinq's signing workflow. It is the operational counterpart to the
end-user [Digital Signing Integration](../features/digital-signing.md) document
and to the OpenSpec change `document-signing`.

The signing feature is fully implemented in `lib/Service/SigningService` and the
two `SigningProviderInterface` implementations
(`NativeSigningProvider`, `ValidSignProvider`); this guide describes the admin
surface only — endpoint reference lives in `appinfo/openapi.json`.

## 1. Prerequisites

- Filinq is installed and enabled (`occ app:enable filinq`).
- OpenRegister is installed and the Filinq register/schema set has been
  imported (Filinq's repair step `InitializeRegister` handles this on first
  enable).
- A user account that is a member of the `admin` group (Nextcloud's
  `IGroupManager::isAdmin()` test is used by the controllers).

## 2. Active provider — `signing_provider`

The active signing provider is resolved at request time by
`SigningProviderFactory::getProvider()` from the `IAppConfig` key
`filinq.signing_provider`. Two providers ship in-app:

| Value | Provider class | When to choose |
|---|---|---|
| `native` (default) | `NativeSigningProvider` | Self-contained signing with a Nextcloud-resident audit trail; no external dependency. |
| `validsign` | `ValidSignProvider` | Delegate to ValidSign for AdES / QES levels; required for the eIDAS levels above SES. |

Switch with:

```
occ config:app:set filinq signing_provider --value=validsign
```

Setting any other value falls back to `native` (defensive, matches the factory
default).

## 3. Default signature level — `signing_default_level`

Sets the eIDAS level applied to new signing requests that do not specify one.

| Value | Meaning | Provider support |
|---|---|---|
| `SES` (default) | Simple electronic signature | native, validsign |
| `AdES` | Advanced electronic signature | validsign only |
| `QES` | Qualified electronic signature | validsign only |

```
occ config:app:set filinq signing_default_level --value=AdES
```

If the configured provider does not support the requested level, the
`SigningService::createSigningRequest()` flow returns a 422-shaped error with a
machine-readable `code` so the caller can surface a precise message.

## 4. Signing-request expiry — `signing_request_expiry_days`

`signing_request_expiry_days` (default `30`) controls how long an open
signing request stays valid. The background job `SigningExpirationJob`
(declared in `appinfo/info.xml` under `<background-jobs>`) walks open requests
once a day and transitions every request whose `expiresAt` has passed into the
`expired` lifecycle state.

```
occ config:app:set filinq signing_request_expiry_days --value=14
```

The value is read every time a request is created — no service restart needed.

## 5. Register / schema bindings

`SigningService` writes four object types into OpenRegister; each pair is
configured with its register slug + schema slug. The repair step pre-populates
these on first enable; admins only override them when running custom OR
registers.

| Object | `*_register` key | `*_schema` key |
|---|---|---|
| Signing request | `filinq.signingRequest_register` | `filinq.signingRequest_schema` |
| Signer record   | `filinq.signerRecord_register`   | `filinq.signerRecord_schema`   |
| Signing session | `filinq.signingSession_register` | `filinq.signingSession_schema` |
| Audit entry     | `filinq.signingAuditEntry_register` | `filinq.signingAuditEntry_schema` |

The values are register/schema **slugs** (e.g. `document` and `signingRequest`),
not numeric IDs.

## 6. ValidSign credentials

When `signing_provider = validsign`, the following keys MUST also be set:

| Key | Purpose |
|---|---|
| `filinq.validsign_base_url` | API base URL of the ValidSign tenant. |
| `filinq.validsign_api_token` | Bearer token used in `Authorization` headers. |
| `filinq.validsign_webhook_secret` | HMAC secret used to verify callback signatures. |

Use `occ config:app:set` with `--sensitive` for the token and secret so they are
never returned by the admin settings API.

## 7. Operational concerns

- **Audit trail** — every state-changing operation
  (`createSigningRequest`, `signRequest`, `cancelRequest`, expiration sweep)
  emits a `signingAuditEntry` row with `x-openregister-archival = P10Y`.
  Operators should never delete these objects directly; archival is enforced by
  OpenRegister.
- **Notifications** — Filinq emits notifications via the canonical
  `x-openregister-notifications` dialect (`SigningRequestNotifier`,
  `SigningSessionNotifier`). Tenants can override per-event recipients in
  `lib/Settings/filinq_register.json` and re-import.
- **Background job verification** — confirm the expiry sweep runs with
  `occ background-job:list | grep SigningExpiration` and the standard NC cron.
  A missing entry means the repair step did not run (re-enable the app to
  re-trigger it).

## 8. Troubleshooting

| Symptom | Likely cause | Fix |
|---|---|---|
| "Provider not available" on create | `signing_provider = validsign` with empty `validsign_api_token` | Set the token (see §6) or fall back to `native`. |
| Requests stay `pending` after `expiresAt` | `SigningExpirationJob` not scheduled | Ensure NC cron runs at least once a day; verify `background-jobs` block in `appinfo/info.xml` was picked up by re-running `occ app:enable filinq`. |
| Signed PDF rejected on verification | Provider mismatch (signed by ValidSign, verified by native) | Verify with the same provider used to sign, or re-issue a request using the desired provider. |

## 9. Cross-references

- ADR-022 (apps-consume-or-abstractions) — explains why Filinq's signing
  pipeline is built on OpenRegister objects rather than a private table.
- OpenSpec `document-signing` — feature-level requirements and scenarios.
- OpenSpec `migrate-signing-audit-to-or-audit` — current shape of the audit
  pipeline; this guide reflects the post-migration state.
- OpenSpec `migrate-signing-to-or-approval-workflow` — describes the planned
  lifecycle-driven implementation; admin keys above stay backward-compatible.
