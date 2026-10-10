# Stamp a text on every page of a PDF

filinq puts a short text on every page of a PDF for another app that asks. decidiq is the first: it puts a council member's name and the date on a confidential meeting paper before showing it. A page that leaks carries the reader's name on its own.

## For app developers

Your app dispatches `OCA\Filinq\Event\DocumentStampRequestedEvent` through Nextcloud's event dispatcher. filinq handles it in the same request. You read the result off the same event object.

```php
$event = new DocumentStampRequestedEvent(
    sourceApp: 'decidiq',
    pdfContent: $pdfBytes,
    text: "J. de Vries, 28-09-2026 14:05\nVertrouwelijk",
    placement: 'both',
    correlationId: 'paper-42',
);
$dispatcher->dispatchTyped($event);

if ($event->isHandled() === true) {
    $stamped = $event->getStampedPdf();
} else {
    $code = $event->getRefusalCode(); // '' when filinq is not installed
}
```

### Fields

| Field | Meaning |
|---|---|
| `sourceApp` | Your app id. filinq writes it to the log. |
| `pdfContent` | The PDF as bytes. filinq never changes it. |
| `text` | Plain text, at most 200 characters. A newline starts a new line. |
| `placement` | `diagonal`, `footer` or `both`. The default is `both`. |
| `correlationId` | Your own reference. filinq writes it to the log. |

### Placements

- `diagonal` writes the text across the middle of each page, light grey and mostly transparent.
- `footer` writes each line in 8 point at the bottom left of each page.
- `both` does both.

Every page keeps its own size and orientation. A landscape page stays landscape.

### Refusal codes

When filinq does not stamp, `isHandled()` is false and `getRefusalCode()` says why. `getRefusalReason()` holds a sentence for your log.

| Code | When |
|---|---|
| `not-a-pdf` | The bytes are not a PDF. |
| `encrypted` | The PDF is encrypted. filinq cannot read its pages. |
| `too-large` | The PDF is above the limit. The default limit is 50 MB. An admin changes it with `occ config:app:set filinq stamp_max_bytes --value <bytes>`. |
| `failed` | Something else went wrong, such as an unknown placement. The details are in the Nextcloud log. |

The listener never throws an exception to your app. When filinq is not installed, nobody handles the event and the refusal code stays empty. Never serve the paper unstamped in either case.

### What filinq does not do

filinq does not store the stamped copy. It is personal to one reader, so it only travels back on the event. Cache it in your own app if you need it again.
