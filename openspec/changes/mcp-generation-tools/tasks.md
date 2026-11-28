# Tasks: mcp-generation-tools

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 10.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Backend

- [x] 1.1 Confirm `filinq-mcp-adoption` is applied (dialect imported, `FilinqScannableServices` + `generateCorrespondence` live) — this change extends that artifact and MUST NOT proceed without it (depends_on)
- [x] 1.2 Add `FileListingService::getFileStatus(int $fileId): array` (public, reusing `buildFileInfo()` internals; principal-scoped file resolution; not-found indistinguishable from nonexistent) with `#[McpTool(name: 'getDocumentStatus', scope: 'read', readOnlyHint: true, destructiveHint: false, idempotentHint: true)]` (REQ-DDMGT-002)
- [x] 1.3 Add `AnonymizationService::anonymizeViaAgent(int $fileId): array` — fileId-only signature; intake pipeline via `extractAndDetectEntities()`; identical checked-gate + prohibition-gate rules as every surface; `ProhibitionGateException` mapped to a refused result; `mcp` attribution on the run — with `#[McpTool(name: 'anonymizeDocument', scope: 'create', readOnlyHint: false, destructiveHint: false, idempotentHint: false)]` (REQ-DDMGT-003, REQ-DDMGT-005)
  - Counts-only response shape: `{status, entityCounts, reviewRequired, refusedReason?}` — never entity values/placeholder maps/text (REQ-DDMGT-004).
- [x] 1.4 Extend `lib/Mcp/FilinqScannableServices.php` to `[CorrespondenceService::class, FileListingService::class, AnonymizationService::class]` (REQ-DDMGT-002..003)
  - Coordination (design.md D6): the wording collision is already reconciled (decision F4) — the `filinq-mcp-adoption` delta now requires the list to *include* `CorrespondenceService::class` and permits this change to extend it, so no archive-time amendment is needed; just verify the extended list still passes OR's scanner and every adoption refusal holds.

## 2. Quality

- [x] 2.1 PHPUnit: status-aggregate shape + access contract, anonymise gate pass-through (prohibition refusal, checked gate untouched), fileId-only signature pin (reflection: no override/entity/steering parameters), counts-only shape pins for BOTH tool results, `mcp` attribution — 75% coverage on new code (ADR-009)
  - Run in container: `docker exec -w /var/www/html/custom_apps/filinq nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`.
- [x] 2.2 MCP surface probe (`tests/integration/Mcp/McpSurfaceTest.php`, built and EXECUTED — grepping bundles/registries is theatre): with OpenRegister on Postgres (8080), assert the surface is exactly 16 derived read tools + 3 curated tools, complete hints on all curated tools, no `.create`/`.update`/`.delete` names, no excluded schema reachable (REQ-DDMGT-001, REQ-DDMGT-006)
  - Written 17 Nov: the probe reads the expected derived tools from `lib/Settings/filinq_register.json` and pins 16; it skips without `NC_BASE_URL`/`MCP_URL`. Left: execute it against a live instance.
  - Executed live 26 Nov 2026 (sandbox: Nextcloud 34.0.6 + MariaDB + Redis, OpenRegister main 99ea152d, filinq development 802b0196): 6/6 green, 179 assertions. The first run was 5/6: the live surface held 31 Filinq tools, the 16 derived read tools as pinned plus 15 curated tools, where the probe pinned 3. The other 12 come from later changes (document editing, charts, metadata, signing-request initiation, consent decision), each declared with `#[McpTool]` in lib/. Nothing undeclared was exposed. The probe now reads the curated set from those attributes, as it already did for the derived set, and checks the hints of every curated tool. Scenario wording updated to match.
- [ ] 2.3 Live-verify via Hermiq chat: grant the tools to a test agent, ask "is document X anonymised?" and "anonymiseer document X", observe status result, review-queue entry, gate refusal on a prohibited entity, and the ungranted-agent negative case (REQ-DDMGT-005)
- [ ] 2.4 Docs: `docs/features/mcp-tools.md` — the operation map (which market operation binds to which tool), the entity-value firewall, grant administration pointer to OpenRegister, screenshots of a Hermiq conversation exercising both tools (ADR-010)
  - Written 17 Nov: the operation map, the firewall, grants and the probe. Left: the Hermiq screenshots (need a live conversation, 2.3).
  - No new UI surface in Filinq itself ⇒ no new Playwright UI spec; no new user-facing strings ⇒ no i18n task (tool descriptions are agent-facing English per the adoption change).
- [x] 2.5 Validate: `openspec validate mcp-generation-tools --strict` passes; hydra gates green (orphaned-write gate: both new methods have a registered consumer — the OR tool registry)
  - Built (c6): the spec delta reworded without changing an obligation (the `status` front matter dropped from the delta, REQ-DDMGT-002 to -006 tightened under 500 characters; the not-found rule of -002 already sits in its scenario "Inaccessible file is not disclosed"). `openspec validate mcp-generation-tools --strict` reports the change valid. Hydra diff check against `origin/development`: no new findings; the orphaned-write gate passes; the one red gate (gate-119 procedure-code, 3 counted files against a baseline of 1) is red on `development` itself and comes from files this change does not touch.
