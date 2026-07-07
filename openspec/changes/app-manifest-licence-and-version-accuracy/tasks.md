# Tasks: app-manifest-licence-and-version-accuracy

Metadata + documentation correctness change. No runtime code, no schema, no l10n key. Every edit
brings a declaration into agreement with what the app already ships.

## [docudesk] Manifest

### T-1. Correct the declared licence (S)

- [ ] T-1.1 In `appinfo/info.xml`, change `<licence>agpl</licence>` to
  `<licence>EUPL-1.2</licence>`.
  - **Acceptance:** the file validates against the App Store `info.xsd`; the value matches
    `composer.json` `"license"` and the source `SPDX-License-Identifier` verbatim.

## [docudesk] Documentation

### T-2. Correct the government feature sheet (S)

- [ ] T-2.1 In `docs/GOVERNMENT-FEATURES.md`, change the header "Licentie: AGPL (vrije open
  source)" to "Licentie: EUPL-1.2 (vrije open source)".
- [ ] T-2.2 In the same file, change technical requirement T-02 toelichting "AGPL, GitHub" to
  "EUPL-1.2, Codeberg".
  - **Acceptance:** no "AGPL" token remains in `docs/GOVERNMENT-FEATURES.md`.

### T-3. Align the README requirements table (S)

- [ ] T-3.1 In `README.md`, update the "Requirements" table to match `appinfo/info.xml`:
  Nextcloud `30 – 34`, PHP `8.3+`, OpenRegister `v0.2.14+`. Update the "Standards & Compliance"
  and "Tech Stack" rows that still say PHP 8.1 accordingly.
  - **Acceptance:** the README requirement rows equal the manifest's declared minima.

## [docudesk] Verify

### T-4. Validate + consistency sweep (S)

- [ ] T-4.1 `grep -rn "agpl\|AGPL" appinfo/ docs/ README.md composer.json publiccode.yml` returns
  no matches (the bundled `LICENSE` file body, which is the EUPL text, is exempt from this sweep).
  - **Acceptance:** no contradicting licence token remains outside `LICENSE`.
- [ ] T-4.2 `openspec validate app-manifest-licence-and-version-accuracy --strict` exits 0.
- [ ] T-4.3 No user-facing English l10n key is added or changed (the l10n parity gate stays green).
