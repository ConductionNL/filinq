## Why

76% of Dutch government tenders require digital document signing. DocuDesk currently has zero signing capability — no service, no controller, no routes, no UI. Municipalities need to sign vergunningbesluiten, overeenkomsten, verwerkersovereenkomsten, mandaatbesluiten, and B&W collegestukken digitally. This change implements the `document-signing` spec that was derived from market intelligence analysis of 74 tenders.

The integration must support three eIDAS signature levels (SES, AdES, QES), both native signing and external provider integration (ValidSign primary), and comply with PAdES, PKIoverheid, and Archiefwet retention requirements.

## What Changes

- Add `SigningService` — orchestrates signing workflows (sequential/parallel), manages signing request lifecycle
- Add `SigningProviderInterface` — pluggable provider abstraction for ValidSign, DocuSign, Adobe Sign, LibreSign
- Add `NativeSigningProvider` — built-in SES signing using Nextcloud user identity + timestamp
- Add `ValidSignProvider` — ValidSign API integration via OpenConnector
- Add `SigningController` — REST API for creating, tracking, and completing signing requests
- Add `SigningVerificationService` — validates PAdES signatures, checks certificate chains, detects tampering
- Add `SigningAuditService` — immutable audit trail for all signing events (10-year retention per Archiefwet)
- Add signing request schema to `docudesk_register.json` — OpenRegister objects for signing requests, signer records, audit entries
- Add Vue signing UI components — signing request creation, status tracking, bulk signing panel, signature verification view
- Add signing-related routes to `appinfo/routes.php`
- Add admin settings for signing provider configuration

## Capabilities

### New Capabilities
- `document-signing`: Digital document signing with eIDAS signature levels (SES/AdES/QES), sequential and parallel multi-signer flows, external provider integration (ValidSign/DocuSign), PAdES-compliant PDF signatures, bulk signing, signature verification, and immutable audit trail

### Modified Capabilities
- `admin-settings`: Add signing provider configuration section (provider selection, API credentials, default signature level)

## Impact

- **New PHP classes**: `SigningService`, `SigningController`, `SigningProviderInterface`, `NativeSigningProvider`, `ValidSignProvider`, `SigningVerificationService`, `SigningAuditService`
- **Modified files**: `appinfo/routes.php` (new signing routes), `docudesk_register.json` (signing schemas), `lib/AppInfo/Application.php` (DI registration)
- **New Vue components**: signing request form, signing status panel, bulk signing view, signature verification display
- **Dependencies**: OpenConnector (for ValidSign API routing), OpenRegister >= v0.2.10 (object storage)
- **External APIs**: ValidSign API (via OpenConnector, not direct — keeps processing local to infrastructure)
- **Standards compliance**: eIDAS (EU 910/2014), PAdES (ETSI EN 319 142), PKIoverheid, TSA (RFC 3161), Archiefwet 1995
