# Tasks — docudesk-mcp-adoption

## 1. Declare the dialect (config)

- [ ] 1.1 Add `configuration.x-openregister-mcp` (`enabled: true`, `search` + `get`, `scope: read`, `readOnlyHint: true`, agent-facing `description` per verb) to `template`, `huisstijl`, `correspondence`, `generatedDocument` in `lib/Settings/docudesk_register.json`.
- [ ] 1.2 Same for `batchCorrespondenceJob`, `signingRequest`, `dossier`, `base`. No other schema gets a block.
- [ ] 1.3 Declare the `search.filters` lists exactly as in design.md and cross-check every entry against that schema's `properties` map (an unknown filter fails the whole register import).
- [ ] 1.4 `python3 -m json.tool lib/Settings/docudesk_register.json` — valid, still 18 schemas, no key dropped, indentation preserved.

## 2. The curated generation tool (code)

- [ ] 2.1 Add `#[McpTool(name: 'generateCorrespondence', description: ..., readOnlyHint: false, destructiveHint: false, idempotentHint: false, scope: 'create')]` to `CorrespondenceService::generate()`; logic and signature unchanged.
- [ ] 2.2 Create `lib/Mcp/DocudeskScannableServices.php` implementing `OCA\OpenRegister\Mcp\IMcpScannableServices`, returning `[CorrespondenceService::class]`, with SPDX + `@spec openspec/specs/docudesk-mcp-surface/spec.md` in the docblock.
- [ ] 2.3 Confirm `generateBatch()` carries **no** attribute and that no `#[McpTool]` exists anywhere under `lib/Service/Signing/`, `SigningService`, `SigningVerificationService` or `SigningAuditService`. `grep -rn "McpTool" lib/` for this baseline shows `CorrespondenceService` + `DocudeskScannableServices`; sibling changes (e.g. `mcp-generation-tools`) may add their own curated `#[McpTool]` services via the scannable-services path — the invariant to assert is that no `#[McpTool]` ever appears on a signing/batch service.

## 3. Verify

- [ ] 3.1 Import the register into OpenRegister; `McpAnnotationValidator` returns zero errors.
- [ ] 3.2 Assert the derived surface is exactly 16 tools (8 schemas x search+get) — sibling changes add no schema, so this count stays 16 — plus the curated `docudesk.generateCorrespondence` (sibling changes may add further curated tools), and that no tool name ends in `.create` / `.update` / `.delete`.
- [ ] 3.3 Scoped `phpcs` clean on the two touched/new PHP files; zero new PHPUnit failures vs a self-measured baseline.
- [ ] 3.4 CHANGELOG entry (ADR-063 adoption: 8 read-only schemas + 1 curated generation tool; signing and batch mail-merge explicitly not exposed).
