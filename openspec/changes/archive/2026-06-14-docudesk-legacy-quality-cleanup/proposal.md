# DocuDesk Legacy Quality Cleanup

## Why

The OR-abstraction audit (2026-05-03, stream 3 + the quality-gates
cleanup at session start) flagged that docudesk's quality gates have
legacy debt absorbed via exclude patterns and a PHPStan baseline.
Burning these down keeps PR diffs honest — gates catch real
regressions rather than silently absorbing already-broken code.

DocuDesk has 6 phpcs.xml exclude-patterns and a 311-line
phpstan-baseline.neon. PHPMD has no baseline yet. The work is a
moderate burn-down: clear PHPCS excludes, run PHPMD unified, and
chip away at the PHPStan baseline.

This is a tracking change so the burn-down can be picked up later.
It is spec-only; no code changes are proposed in this change.

## What Changes

- Inventory and clear the 6 phpcs.xml exclude-patterns. For each:
  add proper docblocks + named-parameter call audits, then drop
  the exclude.
- Run PHPMD for the first time as a unified gate (phpmd.xml is
  configured but no baseline exists). Capture surfacing violations
  as a baseline OR fix outright depending on volume.
- Burn down the 311-line phpstan-baseline.neon. Group errors by
  directory cluster; one PR per cluster; regenerate baseline
  between PRs.
- Wire phpcs/phpmd/phpstan into CI as the unified quality gate.

## Problem

Exclude-patterns and the PHPStan baseline exist because the audit
captured legacy files / errors that predated the current quality
conventions. The audit flagged docudesk as a moderate-volume
case — small enough to clear in 3-4 PRs, large enough to need
phasing.

PHPMD baseline doesn't exist yet because the gate hasn't been run
as part of unified `check:strict`. Capturing it (or fixing
outright) is a Phase 1 activity.

Now is the time because the per-app OR-abstraction adoption work
(Hydra ADR-022) is touching the same files. Cleanup amortises
across both efforts.

## Proposed Solution

File-by-file cleanup phased by directory cluster. Phase 2 lists
each excluded file. Phase 4 groups PHPStan errors by `lib/Service/`,
`lib/Controller/`, `lib/Db/`, etc. — exact buckets emerge from the
baseline inventory in Phase 1.

Estimated effort: 3-4 PRs over 1-2 sprints.

## Out of scope

- Refactoring beyond what the sniff requires
- New features (separate adoption-spec changes own those)
- Test additions (separate test-coverage spec change if needed)

## See also

- The canonical audit lives in openregister at
  `.claude/audit-2026-05-03/03-repo-hygiene.md`. DocuDesk references
  it from there.
- `phpcs.xml` (the legacy-debt baseline section)
- `phpstan-baseline.neon` (the PHPStan baseline file)
- Hydra ADR-022 (apps consume OR abstractions) — quality conventions
- `composer.json` `check:strict` script (the unified gate target)
