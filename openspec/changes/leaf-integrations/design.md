# Design — leaf-integrations

Context: the fleet leaf wave (ADR-019 registry, ADR-022 consume-don't-build). Filinq at
HEAD declares no `linkedTypes` and no `mailObjectTemplate` on any of its 23 schemas
(verified by grep over `lib/Settings/filinq_register.json`) — this change is the app's
first leaf adoption. Sibling changes already cover adjacent ground:
`document-detail-leaf-widgets` (contacts/activity/shares on the generic document detail
page) and `signer-consent-notifications-to-email-leaf` (outbound comms through the email
leaf). This change is the remaining declarative surface: Mail linkage/intake, deadlines,
signer contacts, pipeline files, and follow-up cards.

## Mechanics

- `configuration.linkedTypes` is validated by OR's `Schema::validateLinkedTypes()` and
  read per leaf: `EmailService` selects "schemas that opt into mail linkage via
  `linkedTypes: ["mail"]`" as Mail-sidebar link targets; the registry
  (`IntegrationRegistry::getEnabled()`) drives the tab/widget render for the other leaf
  ids. Provider ids verified in OR at HEAD: `email` (`EmailProvider`, requiredApp
  `mail`), `calendar` (`CalendarProvider`), `contacts` (`ContactsProvider`), `files`
  (builtin `FilesProvider`), `deck` (`DeckProvider`).
- `configuration.mailObjectTemplate` is the Mail-sidebar create-object-from-email field
  map (`Schema.php` §configuration docs); every key must be a real property name and
  every value scalar, or the register import fails — the same fail-the-import discipline
  as the MCP dialect's `search.filters`.
- All declarations land directly in `filinq_register.json` (this repo keeps a single
  monolith register; there is no `register.d/` fragment mechanism here), with an
  `info.version` bump so `SettingsInitializer` re-imports on existing installs — the
  lesson recorded in `filinq-mcp-adoption` task 1.5: without the bump the change is
  inert on every installed instance.

## Adoption table

| Schema | Leaf(s) | Anchor / mapping | Why |
|--------|---------|------------------|-----|
| `signingRequest` | mail, calendar | `deadline` | "Has that contract been signed / when does it expire?" — expiry becomes visible where deadlines live; provider mail links to the request. |
| `signerRecord` | contacts | `displayName`, `email` | A signer is a person; the contacts leaf is the standard person bridge (mirrors the archived consent-recipients contacts migration). |
| `publicationConsent` | mail (+`mailObjectTemplate`), calendar, deck | `objectionDeadline`; sender→`contactEmail`, subject→`notes` | The Woo objection window is the app's most consequential deadline; objection traffic is email-borne; publication follow-ups need a task surface. |
| `correspondence` | mail (+`mailObjectTemplate`) | subject→`caseReference` context | Letters relate to mail threads; inbound case mail can seed the register record. |
| `generatedDocument` | files | `fileId` / `filePath` | The pipeline's output is an NC file; the files leaf is the standard surface for it. |
| `dossier` | files, deck | linked source/produced files | Dossier review (`checkedOn`) and publication follow-up need files visibility and a card surface. |

Deliberately not adopted here: `template` / `huisstijl` (governance artefacts, no leaf
need), `batchCorrespondenceJob` (progress record, no per-object leaf value),
`publicationProhibition` / `anonymizationLink` / signing-material schemas (their detail
exposure is intentionally minimal; adding leaves would invite exactly the linkage those
records exist to prevent), the GL side-domain, and Talk/Polls/Forms/Maps leaves (no
Filinq workflow maps onto them — bias to fewer, ADR-063's rule 3 applied to leaves).

## Privacy boundary

The leaves are authenticated UI surfaces under Filinq's normal access control. They do
not move any schema across the agent boundary: `signerRecord` and `publicationConsent`
remain excluded from the MCP surface exactly as `filinq-mcp-adoption` declares (both
delta specs restate this as a requirement so the two surfaces cannot drift apart
silently). The contacts leaf on `signerRecord` shows identity fields a caseworker already
sees on the signer surface; it must never render `signatureData` or `ipAddress`.
Create-from-email produces records in their initial state only — no decision, no status
advance, no send — so Mail intake cannot short-circuit a consent or generation flow.

## Degradation

Every leaf hides when its NC app is absent (`requiredApp` per provider) and the record
surface renders without error — the same graceful-degradation contract as
`document-detail-leaf-widgets`. No leaf introduces a second write path for the fields it
visualises; the register object stays canonical throughout.
