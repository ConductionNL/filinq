# Document Creation from Templates (Planned)

## Overview

Document creation from templates enables merging zaak/object data into pre-defined templates to produce ODF and PDF output. This feature extends the existing template management and PDF generation capabilities with data resolution, merge execution, and bulk generation.

## Status: Proposed

This feature is planned but not yet implemented. It is required by 39% of analyzed Dutch government tenders.

## Planned Features

- Data resolution from OpenRegister objects (with nested resolution up to 3 levels)
- Ad-hoc context data merge with OpenRegister data
- Bulk document generation (e.g., letters to multiple citizens)
- ODF output support via LibreOffice integration
- Template versioning
- Audit trail via document register

## Legal basis and objection deadline in a decision letter

A decision letter tells the reader on what legal ground you decided. It also names the last day to object. Filinq fills in both when you generate the letter.

### The fields

Open a template, choose **Insert merge field** and pick one of the common fields:

| Field | What the letter shows |
|---|---|
| `{{ grondslag.name }}` | The name of the legal basis, for example a Woo article |
| `{{ grondslag.description }}` | The explanation of that legal basis |
| `{{ bezwaar.uiterlijk }}` | The last day to object, as 13-10-2026 |
| `{{ bezwaar.vanaf }}` | The first day of the objection term |
| `{{ bezwaar.termijnWeken }}` | The objection term in weeks |

`bezwaar.uiterlijkIso` holds the same last day as 2026-10-13, for templates that format dates themselves.

The legal basis comes from a `base` object you bind to the letter. Filinq offers it as `grondslag` as well.

### Where the deadline comes from

Filinq looks for a decision date in the letter's data. It tries `besluitDatum`, then `decisionDate`. Data you pass directly wins over a linked object.

The term runs from the day after the decision date (Algemene wet bestuursrecht, article 6:7). A decision on 1 September 2026 can be objected to until 13 October 2026. A deadline on a Saturday or Sunday moves to the Monday.

Without a decision date the letter gets no `bezwaar` fields. If the template uses them, the generation warnings say the decision date is missing. Test for it with `{% if bezwaar %}` to print a fallback line.

### The setting

The term is six weeks. An admin changes it with the app setting `bezwaar_termijn_weken`:

```
occ config:app:set filinq bezwaar_termijn_weken --value=4 --type=integer
```

A value below one falls back to six weeks.

### Known limit

Public holidays do not move the deadline. The Algemene termijnenwet also moves a deadline that ends on a holiday such as Easter Monday or King's Day. Check a deadline near a holiday by hand.
