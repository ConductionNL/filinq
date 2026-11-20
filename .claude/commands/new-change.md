---
description: Start a new OpenSpec change in openspec/changes/
---
Start an OpenSpec change named `$ARGUMENTS` (kebab-case).

1. Read `openspec/config.yaml` for the project rules and look at an existing change in `openspec/changes/` for the layout.
2. Create `openspec/changes/$ARGUMENTS/` with:
   - `proposal.md`: why, what changes, and impact;
   - `design.md`: the decisions and their trade-offs;
   - `tasks.md`: a checklist of implementation tasks;
   - `specs/<capability>/spec.md`: the requirement deltas, each with scenarios.
3. Check `openspec/specs/` first, so the change extends an existing capability where one fits.

Do not build anything yet. Archiving comes later and needs the spec deltas merged into `openspec/specs/` first; see `openspec/changes/README.md`.
