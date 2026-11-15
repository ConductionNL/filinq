# platform-properties Specification (delta)

## ADDED Requirements

### Requirement: Filinq runs on the customer's own infrastructure (REQ-PLAT-01)

Filinq MUST install as a Nextcloud app on the customer's own Nextcloud,
from the app store or a release archive. Every feature MUST work with only
components the customer runs: Nextcloud, OpenRegister and the document
conversion stack in `deploy/office-suites/`. Filinq MUST NOT require an
outside service. An outside backend (an AI model, a signing provider, a
mail server) MUST be optional, and a feature that uses one MUST say so
when it is absent instead of failing silently.

#### Scenario: Core features work without an outside service

- GIVEN a Nextcloud with OpenRegister and filinq and no outbound network access
- WHEN a user generates a document from a template and anonymises a PDF
- THEN both complete on the customer's own server
- @e2e exclude deployment property; checked on the self-hosted dev stack, not a UI flow

#### Scenario: An absent outside backend is named

- GIVEN no AI backend is configured
- WHEN a feature that can use one runs
- THEN it falls back to its local method and says that the AI backend is not configured
- @e2e exclude covered per feature by the degradation requirements, such as REQ-FIN-06 and REQ-GLS-04

### Requirement: The source is public under EUPL-1.2 (REQ-PLAT-02)

Filinq's full source MUST be public in `ConductionNL/filinq` under the
EUPL-1.2 licence. The bundled `LICENSE` MUST be the EUPL v1.2 text, every
PHP file under `lib/` MUST carry an SPDX licence header, and the manifest,
`composer.json` and `publiccode.yml` MUST declare EUPL-1.2 as REQ-SET-10 in
`admin-settings` states. No feature MUST sit behind a licence key or a
paid tier.

#### Scenario: Anyone can read the source

- GIVEN a person with no Conduction account
- WHEN they open the filinq repository
- THEN they can read and clone the full source and its EUPL-1.2 `LICENSE`
- @e2e exclude repository property, not an app surface

#### Scenario: Every source file names its licence

- GIVEN the files under `lib/`
- WHEN the SPDX gate runs
- THEN every file carries a licence header that names EUPL-1.2
- @e2e exclude enforced by the SPDX quality gate
