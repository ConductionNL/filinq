# Tasks: mcp-generation-tools

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 10.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Backend

- [ ] 1.1 Confirm `docudesk-mcp-adoption` is applied (dialect imported, `DocudeskScannableServices` + `generateCorrespondence` live) — this change extends that artifact and MUST NOT proceed without it (depends_on)
- [ ] 1.2 Add `FileListingService::getFileStatus(int $fileId): array` (public, reusing `buildFileInfo()` internals; principal-scoped file resolution; not-found indistinguishable from nonexistent) with `#[McpTool(name: 'getDocumentStatus', scope: 'read', readOnlyHint: true, destructiveHint: false, idempotentHint: true)]` (REQ-DDMGT-002)
- [ ] 1.3 Add `AnonymizationService::anonymizeViaAgent(int $fileId): array` — fileId-only signature; intake pipeline via `extractAndDetectEntities()`; identical checked-gate + prohibition-gate rules as every surface; `ProhibitionGateException` mapped to a refused result; `mcp` attribution on the run — with `#[McpTool(name: 'anonymizeDocument', scope: 'create', readOnlyHint: false, destructiveHint: false, idempotentHint: false)]` (REQ-DDMGT-003, REQ-DDMGT-005)
  - Counts-only response shape: `{status, entityCounts, reviewRequired, refusedReason?}` — never entity values/placeholder maps/text (REQ-DDMGT-004).
- [ ] 1.4 Extend `lib/Mcp/DocudeskScannableServices.php` to `[CorrespondenceService::class, FileListingService::class, AnonymizationService::class]` (REQ-DDMGT-002..003)
  - Coordination (design.md D6): the wording collision is already reconciled (decision F4) — the `docudesk-mcp-adoption` delta now requires the list to *include* `CorrespondenceService::class` and permits this change to extend it, so no archive-time amendment is needed; just verify the extended list still passes OR's scanner and every adoption refusal holds.

## 2. Quality

- [ ] 2.1 PHPUnit: status-aggregate shape + access contract, anonymise gate pass-through (prohibition refusal, checked gate untouched), fileId-only signature pin (reflection: no override/entity/steering parameters), counts-only shape pins for BOTH tool results, `mcp` attribution — 75% coverage on new code (ADR-009)
  - Run in container: `docker exec -w /var/www/html/custom_apps/docudesk nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`.
- [ ] 2.2 MCP surface probe (`tests/integration/Mcp/McpSurfaceTest.php`, built and EXECUTED — grepping bundles/registries is theatre): with OpenRegister on Postgres (8080), assert the surface is exactly 16 derived read tools + 3 curated tools, complete hints on all curated tools, no `.create`/`.update`/`.delete` names, no excluded schema reachable (REQ-DDMGT-001, REQ-DDMGT-006)
- [ ] 2.3 Live-verify via Hermiq chat: grant the tools to a test agent, ask "is document X anonymised?" and "anonymiseer document X", observe status result, review-queue entry, gate refusal on a prohibited entity, and the ungranted-agent negative case (REQ-DDMGT-005)
- [ ] 2.4 Docs: `docs/features/mcp-tools.md` — the operation map (which market operation binds to which tool), the entity-value firewall, grant administration pointer to OpenRegister, screenshots of a Hermiq conversation exercising both tools (ADR-010)
  - No new UI surface in DocuDesk itself ⇒ no new Playwright UI spec; no new user-facing strings ⇒ no i18n task (tool descriptions are agent-facing English per the adoption change).
- [ ] 2.5 Validate: `openspec validate mcp-generation-tools --strict` passes; hydra gates green (orphaned-write gate: both new methods have a registered consumer — the OR tool registry)
