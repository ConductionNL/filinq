# Tasks — docudesk-mcp-adoption

## 1. Declare the dialect (config)

- [x] 1.1 Add `configuration.x-openregister-mcp` (`enabled: true`, `search` + `get`, `scope: read`, `readOnlyHint: true`, agent-facing `description` per verb) to `template`, `huisstijl`, `correspondence`, `generatedDocument` in `lib/Settings/docudesk_register.json`.
- [x] 1.2 Same for `batchCorrespondenceJob`, `signingRequest`, `dossier`, `base`. No other schema gets a block.
- [x] 1.3 Declare the `search.filters` lists exactly as in design.md and cross-check every entry against that schema's `properties` map (an unknown filter fails the whole register import).
- [x] 1.4 `python3 -m json.tool lib/Settings/docudesk_register.json` — valid, still 18 schemas, no key dropped, indentation preserved.
      **Done, with two corrections to the task as written.** (a) The register holds **21** schemas, not 18 — it grew since this task was drafted; the invariant asserted was 21-before/21-after. (b) `json.tool`/`json.load` **silently keep the last duplicate key**, so the check was run with an `object_pairs_hook` that raises on a repeat instead. 8 blocks present, 0 unknown filters, 0 duplicate keys.
- [x] 1.5 **Bump `info.version` (7.7.0 → 7.8.0).** Not in the original task list, and the change is inert without it: `SettingsInitializer` gates the import on `info.version` against the stored `configuration_version`, so a dialect added without a bump never reaches an existing install. Measured before the bump: the 4 curated tools were live, all 16 derived tools absent, no error anywhere.

## 2. The curated generation tool (code)

- [x] 2.1 Add `#[McpTool(name: 'generateCorrespondence', ...)]` to `CorrespondenceService::generate()`; logic and signature unchanged.
- [x] 2.2 Create `lib/Mcp/DocudeskScannableServices.php` implementing `OCA\OpenRegister\Mcp\IMcpScannableServices`, with SPDX + `@spec` in the docblock. Registered under the `IMcpScannableServices::docudesk` alias in `RegistrationBootstrap`. Returns `CorrespondenceService` **and** `DocumentAgentService` (the sibling `document-editing-tools` change's curated tools, per the extension this task anticipates).
- [x] 2.3 Confirm `generateBatch()` carries **no** attribute and that no `#[McpTool]` exists under `lib/Service/Signing/`, `SigningService`, `SigningVerificationService` or `SigningAuditService`. Asserted by test (`DocumentAgentServiceTest::testNoSigningServiceIsReachableByAnAgent`) rather than by a one-off grep, so it stays true.

## 3. Verify

- [x] 3.1 Import the register into OpenRegister; `McpAnnotationValidator` returns zero errors. Imported live on the dev instance; `configuration_version` advanced to 7.8.0 and all 8 schemas carry the block.
- [x] 3.2 Assert the derived surface is exactly 16 tools (8 schemas × search+get) plus the curated tools, and that no tool name ends in `.create` / `.update` / `.delete`. **Measured live:** 20 `docudesk.*` tools in `ToolOversightController::toolCatalog()` — 16 derived + 4 curated (`generateCorrespondence`, `readDocument`, `editDocument`, `convertDocumentToPdf`); zero write verbs.
- [x] 3.3 Scoped `phpcs` clean on the touched/new PHP files; zero new PHPUnit failures vs a self-measured baseline. phpcs/phpmd/psalm: 0 errors, 0 warnings. PHPUnit 1288 → 1361 tests; the 22 errors are identical before and after (pre-existing, measured by stashing).
- [x] 3.4 CHANGELOG entry.

## Acceptance criteria

- Eight schemas expose `search` + `get`; no DocuDesk schema exposes a derived write verb.
- The curated generation tool is reachable; `generateBatch()` and every signing service are not.
- The surface is visible and correctly classified in Hermiq's Tool governance grant editor.
