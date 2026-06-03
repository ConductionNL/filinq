# Tasks: migrate-signing-audit-to-or-audit

All tasks are `[docudesk]`. Estimates: S = half-day, M = 1–2 days, L = 3+ days.

> **Scope adjustment (2026-05-11):** `lib/Service/SigningAuditService.php`
> exists and defines `VALID_ACTIONS = [CREATED, SIGNED, DECLINED, CANCELLED,
> EXPIRED, COMPLETED, VIEWED]`. The service is called from `SigningService`
> + `SigningController` to record events. Clean migration requires:
>
> 1. Constructor injection of `OCA\OpenRegister\Db\AuditTrailMapper` into
>    `SigningAuditService`.
> 2. Method bodies rewritten to call
>    `AuditTrailMapper::createAuditTrailEntry($object, 'docudesk.signing.<ACTION>', $context)`.
> 3. The current persistence target (if any) deprecated; existing rows stay
>    read-only.
> 4. Retention configured via OR's tenant-quotas / retention API at 10 years
>    for Archiefwet compliance.
> 5. Callers updated where they expect the old return shape.
>
> The audit service does NOT currently see the underlying `ObjectEntity` —
> it gets passed strings (document ID, signer ID). Resolving those to OR
> object UUIDs is the cross-cutting step that ties the migration to
> `SigningService`'s persistence layer. This is bound up with the broader
> `migrate-signing-to-or-approval-workflow` change.
>
> This commit records the umbrella rule + the migration plan; implementation
> ships alongside the signing approval-workflow PR (where the
> ObjectEntity-from-string resolution naturally lives).

---

## [docudesk] Signing Audit Service Migration

### D-1. Inject AuditTrailMapper into SigningAuditService (M)

- [ ] D-1.1 Update the constructor of `lib/Service/SigningAuditService.php` to inject
  `OCA\OpenRegister\Db\AuditTrailMapper`. Remove `SettingsService` and `IAppConfig` from
  constructor (they are no longer needed for audit writes). Retain `LoggerInterface`.
  The method to call is `createAuditTrailEntry(ObjectEntity $object, string $action, array $context = [])`.
  The `$context` array is already supported — it is persisted in the `changed` JSON column.
  No OR-side changes or blocking dependency exists.
  - **Acceptance:** Constructor updated; `composer check:strict` passes with no new errors.

### D-2. Rewrite SigningAuditService.logEvent() to emit via OR (M)

- [ ] D-2.1 Implement the new `logEvent()` body:
  (a) validate `$action` against `VALID_ACTIONS` (same guard),
  (b) build action type `'docudesk.signing.' . $action`,
  (c) build `$context` array (`signRequestId`, `actorUserId`, `actorDisplayName`, `ipAddress`,
      `signatureLevel`, `provider`, plus the `$metadata` pass-through) — persisted in `changed`,
  (d) call `AuditTrailMapper::createAuditTrailEntry($object, $actionType, $context)` where
      `$object` is the `ObjectEntity` for the signing request.
  (e) return the created entry serialised as array (same return contract).
  - **Acceptance:** PHPUnit unit test with mocked mapper confirms `createAuditTrailEntry()` is
    called once per `logEvent()` call with the correct action type. All seven VALID_ACTIONS
    tested. `composer check:strict` passes.

- [ ] D-2.2 Remove the old `ObjectService::saveObject()` path and the `signingAuditEntry_register`
  / `signingAuditEntry_schema` IAppConfig reads from `logEvent()`. Add deprecation comment
  to the IAppConfig keys.
  - **Acceptance:** No reference to `signingAuditEntry_register` or `signingAuditEntry_schema`
    in the active code path of `logEvent()`.

### D-3. Rewrite SigningAuditService.getAuditTrail() to read from OR (M)

- [ ] D-3.1 Rewrite `getAuditTrail()` to query OR's audit trail for entries matching
  `objectUuid = $signingRequestId`, sort chronologically, and return as array. Use
  `AuditTrailMapper::findAllByObject()` or `findAll(filters: ['objectUuid' => ...])`.
  - **Acceptance:** PHPUnit unit test with mocked mapper confirms the correct query method
    is called with `$signingRequestId`. Returns array in chronological order.

### D-4. Remove rejectUpdate() and rejectDelete() methods (S)

- [ ] D-4.1 Remove `rejectUpdate()` and `rejectDelete()` from `SigningAuditService`. If any
  code calls these methods, update callers to remove the calls (they should no longer be needed
  since OR enforces immutability).
  - **Acceptance:** Neither method exists in `SigningAuditService` after this task;
    `composer check:strict` passes; no uncalled references remain.

### D-5. Add 10-year retention configuration (deploy-time) (S)

- [ ] D-5.1 Document the 10-year Archiefwet retention requirement in docudesk's
  administration/deployment documentation. Specify: OR retention for the signing register
  MUST be configured to ≥ 3650 days per Archiefwet 1995. This is an OR admin UI / occ
  command configuration, not a code change.
  - **Acceptance:** Deployment documentation contains a section on signing audit retention
    referencing Archiefwet 1995 and the 3650-day minimum.

### D-6. Mark old audit storage deprecated; document sunset in CHANGELOG (S)

- [ ] D-6.1 Add deprecation comments to the `signingAuditEntry_register` and
  `signingAuditEntry_schema` IAppConfig key reads (retain the keys for legacy read access
  until sunset). Update any docudesk openspec that previously referenced the parallel audit
  storage to note the new discovery path.
  - **Acceptance:** Deprecation comment present on each legacy config key reference;
    no active write path to `signingAuditEntry_schema` after migration.

- [ ] D-6.2 Add an entry to `CHANGELOG.md` noting:
  - `SigningAuditService` now emits via OR audit trail.
  - `signingAuditEntry` schema is deprecated as of this release.
  - Sunset: existing records remain readable for one major release.
  - Archiefwet retention MUST be configured in OR (≥ 3650 days).
  - **Acceptance:** CHANGELOG entry exists with the above information.

### D-7. Integration tests (L)

- [ ] D-7.1 Write integration tests that trigger each of the seven VALID_ACTIONS via the
  signing flow (or directly via `SigningAuditService::logEvent()`), then query
  `GET /api/audit-trails?objectUuid={signRequestId}` and assert:
  - At least one entry per action type exists with the correct `docudesk.signing.*` action.
  - Entries are returned in chronological order.
  - `GET /api/audit-trails/verify` returns a passing integrity check.
  - **Acceptance:** All seven action types tested; tests pass against a running NC dev
    instance with docudesk + OR installed; `composer check:strict` passes.

- [ ] D-7.2 Add a test confirming no new `signingAuditEntry` objects are written after
  the migration is applied. The test triggers a `logEvent()` call and asserts the
  `signingAuditEntry` object count is unchanged.
  - **Acceptance:** Test passes; verifies the migration fully redirects writes to OR audit trail.
