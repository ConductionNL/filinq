## Context

The completed `anonymise-output-as-pdf-by-default` change introduced two `outputFormat` values:

- `pdf` (default today) — after OpenRegister anonymises the file, if the result is not already a PDF, Filinq runs `PdfConversionService::convertToPdf()` and writes the resulting PDF/A-3b to Nextcloud Files.
- `preserve` — skip conversion; the native anonymised file is the only output.

The conversion gate lives in `AnonymizationService::anonymizeDocument()` (around lines 245–286). `PdfConversionService::convertToPdf(File $source, array $opts = []): File` (signature at `lib/Service/PdfConversionService.php:82`) returns a **brand-new** PDF node and leaves its `$source` (the native anonymised intermediate) untouched. So in `pdf` mode the cascade produces e.g. `report_anonymized.pdf` while `report_anonymized.docx` is left orphaned on disk. The `anonymizationLink` relation already records the PDF id in `anonymizedFileId` (`recordAnonymizationLink()`, ~line 357), so the native file is unreferenced leftover.

That leftover is a privacy defect: a native DOCX/ODT/RTF is trivially re-editable and carries metadata that anonymise-to-PDF exists to strip. Anyone with folder access can pick up the un-redacted native copy.

There is already a best-effort rollback in the conversion-failure branch (`AnonymizationService.php:270–281`): try `delete()`, catch `Throwable`, log a warning, never abort. That is the exact pattern to reuse for the success-path cleanup.

## Goals / Non-Goals

**Goals:**

- Add a third `outputFormat` value `pdf-only`: convert to PDF (same cascade as `pdf`) and then delete the native anonymised intermediate so only the PDF remains.
- Make `pdf-only` the new default (tenant default config + service-method default param).
- Keep `pdf` (convert, keep both) and `preserve` (native only) available and unchanged.
- Make the intermediate deletion best-effort: never fail an otherwise-successful anonymise run over cleanup.

**Non-Goals:**

- No change to `PdfConversionService` or the conversion cascade — it still returns a new PDF and does not delete its source. The deletion is the caller's responsibility.
- No OpenRegister schema / register / `anonymizationLink` relation change — the relation already points at the PDF.
- No DB migration. The default flip is a config/behaviour change handled via release note.
- No re-processing of previously anonymised files — applies to new anonymise calls only.
- No new admin UI control; the existing `default_output_format` config key carries the new default value.

## Decisions

### D1. Three-mode semantics

| Mode | Convert to PDF? | Keep native anonymised file? | Notes |
|---|---|---|---|
| `pdf-only` (NEW default) | yes | no — delete after successful convert | the privacy-correct default |
| `pdf` | yes | yes | today's behaviour, leftover native kept |
| `preserve` | no | yes | native is the only file |

`pdf-only` is strictly `pdf` plus a best-effort delete of the native intermediate on the success path. The enum in both controllers becomes `['pdf-only', 'pdf', 'preserve']`; invalid values still 400.

### D2. Where the deletion hooks in

The conversion gate already captures the native node in the local `$result` variable before reassigning it to the converted PDF:

```php
if ($outputFormat === 'pdf-only' || $outputFormat === 'pdf') {     // gate now fires for both convert modes
    $resultMime = (string) $result->getMimeType();
    if ($resultMime !== 'application/pdf') {
        $nativeIntermediate = $result;                            // capture BEFORE reassignment
        try {
            $result = $this->pdfConversion->convertToPdf($result); // $result now points at the PDF
        } catch (ConversionFailedException $e) {
            // existing best-effort rollback (delete $result, log, re-throw) — unchanged
        }
        if ($outputFormat === 'pdf-only') {
            // best-effort delete $nativeIntermediate (see D3)
        }
    }
}
```

The capture must happen before `$result` is reassigned, otherwise the reference to the native node is lost. The delete runs only after a **successful** `convertToPdf()` (it is unreachable when the catch re-throws).

**Alternative considered:** have `PdfConversionService::convertToPdf()` delete its own source when an `opts['deleteSource'] => true` flag is set. Rejected — the service is a generic, reusable conversion primitive (other consumers may want the source kept); source-lifecycle is a policy decision that belongs to the anonymise caller, not the converter.

### D3. Best-effort deletion mirroring the existing rollback

The success-path delete uses the same shape as the conversion-failure rollback at `AnonymizationService.php:270–281`:

```php
try {
    $nativeIntermediate->delete();
} catch (Throwable $deleteError) {
    $this->logger->warning(
        'pdf-only: failed to delete native anonymised intermediate; orphaned file remains.',
        ['fileId' => $fileId, 'exception' => get_class($deleteError), 'message' => $deleteError->getMessage()]
    );
}
```

The run has already succeeded (PDF written, relation recorded) by the time cleanup runs, so a delete failure MUST be swallowed and logged at warning level — never propagated. Logs stay PII-free (file id + exception class/message only).

### D4. Already-a-PDF is a no-op (no special-casing)

The conversion gate is guarded by `mime !== 'application/pdf'`. When the anonymised result is already a PDF, conversion is skipped, so no native intermediate is ever created — there is nothing extra to delete. In that case `pdf-only` is observably identical to `pdf`. No branch needs to special-case it; the delete simply lives inside the `mime !== 'application/pdf'` block alongside the conversion.

### D5. New default via config + service param

The tenant default `filinq.anonymisation.default_output_format` flips from `'pdf'` to `'pdf-only'` in `SettingsService` (~line 197–201), and the `anonymizeDocument()` default param flips from `'pdf'` to `'pdf-only'`. The per-call `outputFormat` value continues to override the tenant default in the controllers' `resolveOutputFormat()`. Rollback is configuration-only: set the key back to `pdf`.

### D6. Declarative-vs-imperative (ADR-031): justified imperative exception

ADR-031 prefers declarative behaviour wiring for lifecycle/aggregation/derived-field/notification/relation/widget concerns. The new behaviour is **best-effort file-system cleanup** (deleting a Nextcloud `File` node) executed inside a service method as a side effect of a conversion that already happened. It is not a lifecycle hook, not a derived/aggregated field, not a notification, not a relation, and not a widget — there is no declarative surface for "delete this leftover file node". The deletion is therefore imperative file-IO by necessity. This is a deliberate, justified exception to the declarative-first preference: a file-system side effect, not a derived field, so a reviewer should not flag it as missing declarative wiring.

## Risks / Trade-offs

- **[Default flip silently stops shipping the native file]** → Mitigation: explicit CHANGELOG "Behavior changes" entry + release note. Callers that need the native file kept alongside the PDF send `outputFormat: "pdf"`; native-only callers send `preserve`. Worst case is "operator expected a DOCX next to the PDF and now only has the PDF" — recoverable by re-running with `pdf`. The new default is privacy-positive.
- **[Delete fails, leaving an orphaned native file]** → Mitigation: best-effort + warning log; the run still succeeds and the PDF is the referenced output. Same exposure as today's `pdf` mode (which always leaves the native file), so this is never worse than the status quo. The warning gives operators a signal to clean up.
- **[Capturing the wrong node / deleting the PDF]** → Mitigation: the native reference is captured before `$result` is reassigned; the delete targets `$nativeIntermediate`, never the post-conversion `$result`. Covered by a unit test asserting the PDF survives and the native node is the one deleted.
- **[Mode confusion between `pdf` and `pdf-only`]** → Mitigation: the 400 body lists all three allowed values; CHANGELOG + docs spell out the semantics table.

## Migration Plan

1. Widen `VALID_OUTPUT_FORMATS` to `['pdf-only', 'pdf', 'preserve']` in both controllers.
2. Flip the `SettingsService` tenant default and the `anonymizeDocument()` default param to `pdf-only`.
3. Add the capture + best-effort delete in the conversion gate.
4. Add unit tests for the new path; run `composer check:strict`.
5. Release with a CHANGELOG "Behavior changes" entry and a release note.

**Rollback:** configuration-only — set `filinq.anonymisation.default_output_format = pdf` to restore the keep-both default. No code rollback or DB migration needed. There is no DB migration artifact for this change.

## Seed Data

Not applicable — this change introduces no new OpenRegister schema, register, or object. The `anonymizationLink` relation already stores the PDF id; no `_registers.json` / seed-data entry is added or modified. The apply agent MUST NOT generate seed-data for this change.

## Open Questions

- **Should `pdf-only` also remove a pre-existing stale native intermediate from an earlier `pdf` run of the same source?** Provisional: no — this change only cleans up the intermediate it created in the current run; cleaning historical leftovers is out of scope (a separate housekeeping concern). Resolve only if a real need surfaces.