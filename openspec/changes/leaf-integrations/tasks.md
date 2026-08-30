# Tasks — leaf-integrations

## 1. Declare the leaves (config)

- [ ] 1.1 Add `configuration.linkedTypes` to `signingRequest` (`["mail","calendar"]`),
  `signerRecord` (`["contacts"]`), `publicationConsent` (`["mail","calendar","deck"]`),
  `correspondence` (`["mail"]`), `generatedDocument` (`["files"]`), and `dossier`
  (`["files","deck"]`) in `lib/Settings/filinq_register.json`. No other schema gets a
  block; no property or `required` list changes.
- [ ] 1.2 Add `configuration.mailObjectTemplate` to `publicationConsent` (sender →
  `contactEmail`, subject → `notes`) and `correspondence` (subject-derived
  `caseReference` context), cross-checking every template key against that schema's
  `properties` map (an unknown key fails the whole register import).
- [ ] 1.3 Bump `info.version` so `SettingsInitializer` re-imports on existing installs
  (the dialect-without-bump trap recorded in `filinq-mcp-adoption` task 1.5).
- [ ] 1.4 Validate: JSON parses with a duplicate-key-rejecting loader; schema count
  unchanged; every schema not named in 1.1/1.2 byte-identical.

## 2. Host the leaves on the record surfaces (frontend)

- [ ] 2.1 Ensure the signing-request, consent, correspondence, generated-document, and
  dossier detail surfaces mount the registry's enabled leaf tabs/widgets for their object
  (shared registry tab host per ADR-019) — reusing the host wiring from
  `document-detail-leaf-widgets`, not a parallel tab system.
- [ ] 2.2 Graceful degradation: with Mail / Calendar / Contacts / Deck absent, the
  corresponding leaf is hidden and each surface renders without error.
- [ ] 2.3 nl + en translations for any new tab labels (ADR-007 / ADR-025).

## 3. Verify

- [ ] 3.1 Import the register into OpenRegister on the dev instance: zero
  configuration-validation errors; NC Mail's sidebar lists the three link-target schemas
  and offers create-from-email for consent and correspondence.
- [ ] 3.2 Create-from-email produces records in their initial state only — assert no
  `publicationDecision`, no `consentStatus` advance, no generation, no send.
- [ ] 3.3 Assert the MCP surface is unchanged by this change: no tool exposes
  `signerRecord` or `publicationConsent` after the leaves are enabled (extend the MCP
  surface probe assertion rather than a one-off grep).
- [ ] 3.4 Component/integration tests: leaves render when their apps are enabled, hidden
  when absent; no second write path for `deadline` / `objectionDeadline` / `fileId`.
- [ ] 3.5 CHANGELOG entry.

## Acceptance criteria

- Six schemas carry the declared `linkedTypes` (two also `mailObjectTemplate`); no other
  schema is touched; the register imports cleanly on an existing install.
- Mail linkage, calendar deadlines, signer contacts, pipeline files, and follow-up deck
  cards all render through the registry on their record surfaces, and every leaf degrades
  gracefully.
- The agent-facing MCP surface is byte-for-byte unchanged.
