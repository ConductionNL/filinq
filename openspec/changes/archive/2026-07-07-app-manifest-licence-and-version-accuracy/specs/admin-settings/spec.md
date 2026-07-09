# admin-settings Specification (delta)

---
status: proposed
---

## Purpose

Correct the app-identity metadata DocuDesk declares in `appinfo/info.xml` so it agrees with the
repository's actual licence and shipped compatibility range. Adds a licence-consistency
requirement and brings the compatibility thresholds (REQ-SET-08) in line with the manifest.

## MODIFIED Requirements

### Requirement: App Metadata and Compatibility (REQ-SET-08)

**Priority:** MUST

DocuDesk declares platform compatibility and app identity in its `appinfo/info.xml`. The declared
compatibility SHALL match what the manifest actually enforces at install time: PHP **8.3+** with
64-bit integer support, and Nextcloud **30 through 34**. PostgreSQL 10+ is the primary database,
with SQLite and MySQL 8.0+ also supported.

#### Scenario: Database compatibility verification
@e2e exclude app metadata in info.xml — platform compatibility verified at install time by Nextcloud; not UI-observable
- GIVEN DocuDesk is installed on a PostgreSQL 10+ server
- WHEN the app is enabled
- THEN the app functions correctly with PostgreSQL as the primary database
- AND SQLite and MySQL 8.0+ are also supported

#### Scenario: PHP version check
@e2e exclude app metadata in info.xml — PHP version gate enforced by Nextcloud at install time; not UI-testable
- GIVEN a server running PHP 8.2
- WHEN attempting to install DocuDesk
- THEN the installation fails because PHP 8.3+ with 64-bit integer support is required

#### Scenario: Nextcloud version compatibility
@e2e exclude app metadata in info.xml — NC version compatibility enforced by app marketplace; not UI-testable
- GIVEN Nextcloud version 29 is running
- WHEN attempting to enable DocuDesk
- THEN the app cannot be enabled because the minimum required version is Nextcloud 30
- AND the maximum supported version is Nextcloud 34

## ADDED Requirements

### Requirement: Declared licence matches the repository licence (REQ-SET-10)

**Priority:** MUST

The licence declared in `appinfo/info.xml` (`<licence>`) SHALL equal the repository's actual
licence, **EUPL-1.2**, expressed as the SPDX identifier. It SHALL agree with the bundled `LICENSE`
file (EUPL v.1.2 text), `composer.json` (`"license"`), `publiccode.yml` (`license`), and the
`SPDX-License-Identifier` header in the source files. The government-facing feature sheet
(`docs/GOVERNMENT-FEATURES.md`) SHALL state the same licence. No source in the repository SHALL
declare a licence (e.g. `agpl`) that contradicts the bundled `LICENSE`.

#### Scenario: Manifest licence agrees with the bundled LICENSE
@e2e exclude manifest metadata — licence declaration is validated at build/publish time, not a UI surface
- GIVEN the repository ships an EUPL v.1.2 `LICENSE` file and EUPL-1.2 in `composer.json`,
  `publiccode.yml`, and the source `SPDX-License-Identifier` headers
- WHEN `appinfo/info.xml` is inspected
- THEN its `<licence>` element reads `EUPL-1.2`
- AND no repository metadata file declares `agpl`

#### Scenario: Government feature sheet states the correct licence
@e2e exclude docs metadata — feature sheet content, not a navigable app surface
- GIVEN `docs/GOVERNMENT-FEATURES.md` documents DocuDesk for procurement
- WHEN the licence line and technical requirement T-02 are read
- THEN both state EUPL-1.2, matching the manifest and the bundled `LICENSE`
