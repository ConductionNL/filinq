# Accessibility after redaction

A redacted copy for a Woo publication has to stay readable for a screen reader. Redaction can destroy the tag structure a screen reader reads. Filinq asks OpenRegister to keep it, records whether it did, and warns before such a copy is published.

## What happens on every anonymisation

- Filinq asks OpenRegister's redaction to keep the tag structure (`preserveStructure`). An admin can switch this off with `filinq.redaction.preserve_tags_default` set to `false`.
- OpenRegister reports what it kept: the tags before and after, and why tags were lost.
- Filinq records the outcome on the anonymisation link as `structurePreservation`: `preserved`, `degraded`, `not-applicable` (the original had no tags) or `unknown` (OpenRegister reported nothing). No entity value is ever part of it.
- When veraPDF is installed and the copy claims PDF/UA, Filinq checks the claim. A copy that fails the check is recorded as `degraded`, with the reason `verapdf-not-pdfua`.

The file viewer sidebar shows the outcome next to the anonymisation result: the tag counts, and in plain words why tags were lost.

## Before publishing

The Woo publication pipeline reads the outcome of the redacted copy and records it on the publication as `accessibilityState`. What happens next is set with `filinq.redaction.accessibility_gate`:

| Setting | A degraded or unknown copy |
|---|---|
| `warn` (default) | The publication page shows a warning. The hand-off goes ahead. |
| `block` | The publication is not ready. Fill in **Why may it be published anyway?** and save the metadata; the reason is kept on the publication and in its log. |
| `off` | Only recorded. |

Unknown counts as degraded: a copy nobody checked is not called accessible.

```
occ config:app:set filinq filinq.redaction.accessibility_gate --value=block
```

## What this does not do

Filinq does not rewrite tags itself. Whether tags survive depends on OpenRegister's redaction engine. Generated documents (not redacted ones) are covered by [accessible PDF output](pdfua-accessible-output.md).
