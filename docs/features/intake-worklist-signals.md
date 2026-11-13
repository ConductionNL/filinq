---
id: intake-worklist-signals
title: Deadlines and duplicates on the intake worklist
sidebar_label: Deadlines and duplicates
description: Every waiting document has a handling deadline, the worklist lists what is overdue, and a file that arrived before is marked as a possible duplicate.
keywords:
  - intake
  - deadline
  - overdue
  - duplicate
---

<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

@spec openspec/changes/intake-worklist-deadline-and-duplicates/specs/intake-worklist-signals/spec.md
-->

# Deadlines and duplicates on the intake worklist

The intake worklist at `/intake` tells a registrar what is late and what arrived before.

## What the registrar sees

- **Deadline.** Every waiting document has a handling deadline. The row shows the date and a countdown in working days: "3 days left", "Due today" or "5 days overdue".
- **Overdue.** Next to *Waiting* and *Taken off a record*, the *Overdue* mode lists every document past its deadline that is not handled yet, the longest overdue first.
- **Change deadline.** A row action moves the deadline. The registrar picks a date and gives a reason; the reason stays on the document (`deadlineReason`) and the change is in the document's audit trail.
- **Possible duplicate.** When the same file (byte for byte) arrived before, the row shows "Possible duplicate" naming the first document. The arrival is never refused: two identical files may belong to two cases, and the registrar decides.
- **Reject as duplicate.** A row action on a possible duplicate rejects it through the ordinary reject, with the reason "Duplicate of" and the first document's subject.

## How it works

- **The deadline is set on arrival.** The matching intake default rule's `handlingDays` is counted in working days (Saturdays and Sundays skipped, public holidays not) from the moment the document arrived. Without a rule, or with a rule that sets no period, the admin setting `intake.default_handling_days` applies, ten working days by default.
- **What handled means is per rule.** A rule's `handledWhen` is `assigned` (the default) or `answered`. Under `answered`, an assigned document stays overdue until an outgoing registration in the post register answers its inbound registration. A rejected document is always handled.
- **Duplicates are exact.** On arrival filinq stores the SHA-256 of the file's bytes as `contentHash`. When an earlier intake document that was not rejected has the same hash, the newcomer gets `duplicateOf`. The same letter scanned twice gives different bytes and is not detected. A re-delivery under the same source reference is still skipped first, as before.
- The schema also declares an exact `x-openregister-dedup` rule on `contentHash`, so OpenRegister's duplicate view for data stewards lists the same pairs.

## The API

- `GET /api/intake/documents?mode=overdue` lists the overdue documents.
- `POST /api/intake/documents/{uuid}/deadline` with `deadline` (YYYY-MM-DD) and `reason` moves the deadline of a waiting document.
