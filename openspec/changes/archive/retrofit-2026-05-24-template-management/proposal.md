# Retrofit — template-management

Describes observed behavior of 19 methods under the `template-management` capability as 5 new REQs (REQ-TMPL-08 through REQ-TMPL-12). Code already exists — this change retroactively specifies it.

## Affected code units

- `lib/Controller/TemplatesController.php` — `versions`, `restoreVersion`, `diffVersions`, `duplicate`, `lock`, `unlock`
- `lib/Controller/TemplateRequestHandler.php` — `parseListParams`, `parseBodyParams`, `buildErrorResponse`
- `lib/Service/TemplateService.php` — `duplicateTemplate`, `acquireLock`, `releaseLock`, `isLockExpired`
- `lib/Service/TemplateVersionService.php` — `createVersion`, `getVersions`, `getVersion`, `getNextVersionNumber`, `restoreVersion`, `getDiff`

## Approach

- Read each method's body to extract observed inputs, outputs, pre/postconditions, failure modes.
- Group the 19 methods into 5 REQs by observable behavior:
  - REQ-TMPL-08: Template version history snapshotting + retrieval (5 methods + 1 controller endpoint)
  - REQ-TMPL-09: Template version diff retrieval (1 method + 1 controller endpoint)
  - REQ-TMPL-10: Template duplication (1 method + 1 controller endpoint)
  - REQ-TMPL-11: Template edit lock acquire/release with TTL expiry (3 methods + 2 controller endpoints)
  - REQ-TMPL-12: Shared request parsing + error response helpers (3 helper methods reused across the controller)
- REQ language matches what the code does today; ambiguities surfaced in Notes (lock TTL hard-coded, restoreVersion creates auto-snapshot, namespace `(kopie)` Dutch literal in duplicate output, version-number computed from total count not max(version)).
- One REQ per observable behavior; no aspirational tightening.

Source: `openspec/coverage-report.json` generated 2026-05-24. See [retrofit playbook](../../../.github/docs/claude/retrofit.md).
