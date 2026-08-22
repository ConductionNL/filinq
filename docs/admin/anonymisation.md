# Filinq — Anonymisation Backends

Filinq delegates entity recognition to OpenRegister's `EntityRecognitionHandler`.
The handler supports four backend modes, each with different accuracy and infrastructure
requirements.

## Backend Options

| Method | Description | Requires |
|---|---|---|
| **regex** | Built-in pattern matching (names, BSNs, IBANs, dates). Fast; no external dependencies. Lower recall on edge cases. | Nothing — always available |
| **presidio** | Microsoft Presidio NLP engine. Runs locally or via a custom endpoint configured in OpenRegister settings. | Self-hosted Presidio instance or custom URL |
| **openanonymiser** | GPU-accelerated model via the OpenAnonymiser ExApp. Highest recall. | OpenAnonymiser ExApp + GPU server + AppAPI |
| **openanonymiser_light** | CPU-optimised model via the OpenAnonymiser Light ExApp. Good recall without GPU hardware. | OpenAnonymiser Light ExApp + AppAPI |

## Install Workflow (ExApp backends)

1. **Install AppAPI** — required for all ExApp backends. Open
   `/settings/apps/discover/app_api` and click *Download and enable*.

2. **Install an ExApp backend** — pick one:
   - OpenAnonymiser Light (CPU): `/settings/apps/discover/openanonymiser_light`
   - OpenAnonymiser (GPU): `/settings/apps/discover/openanonymiser`

   After enabling, AppAPI will register the ExApp's daemon and start the container.

3. **Configure the backend in OpenRegister** — navigate to
   `/settings/admin/openregister` and select the backend from the
   *Anonymisation backend* dropdown.

4. **Verify** — reload the Filinq admin settings page. If the backend is
   detected, the warning banner disappears automatically.

## Admin Warning Banner

When entity recognition is operating in regex-only mode and no non-regex backend
is configured, Filinq shows an informational warning banner on:

- The **Filinq admin settings page** (top of section, persistent).
- The **Filinq dashboard** (top of page, admin users only).

The banner provides direct links to the App Store entries and to OpenRegister
settings, and can be **dismissed per admin**. A dismissed admin can re-enable
the banner from the *Anonymisation* section of the Filinq admin settings page
by toggling *Show anonymiser backend warning*.

> **Note:** Dismissal is informational only — regex-only mode continues to work.
> The banner is a discovery aid, not a blocker.

### AppAPI not installed

If AppAPI itself is not installed on the instance, the banner additionally
instructs the admin to install AppAPI first before installing an ExApp backend.
The deep-link CTAs remain visible and navigate to the standard Nextcloud
"AppAPI required" page.

## Architecture Reference

This feature implements the responsibility split defined in
[ADR-017](../../openspec/architecture/adr-017-ai-exapp-backend-discovery.md):

- **Filinq** owns the warning UX and deep links.
- **OpenRegister** owns backend selection UI and the actual health check.
- Filinq reads state via `OCA\OpenRegister\Service\AnonymisationBackendService::getState()`.
- Filinq does NOT query `IAppManager`, AppAPI services, or HTTP health endpoints directly.
