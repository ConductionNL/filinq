# Stamping a text on every page of a PDF

For app developers. A sibling app asks filinq to put a text on every page of a
PDF, for example a reader's name and the date on a confidential paper, so that
a leaked page carries it. filinq returns a new PDF and stores nothing.

Spec: `openspec/changes/work-stamp-text-on-every-page` (REQ-PST-001, REQ-PST-002).

## The event

Dispatch `OCA\Filinq\Event\DocumentStampRequestedEvent` through Nextcloud's
`IEventDispatcher` and read the result back from the same event object. It is
a typed command (ADR-041): your app does not resolve a filinq service.

```php
use OCA\Filinq\Event\DocumentStampRequestedEvent;

$event = new DocumentStampRequestedEvent(
    sourceApp: 'decidiq',
    pdfContent: $pdfBytes,
    text: "J. de Vries, 28-09-2026 14:05\nVertrouwelijk",
    placement: 'both',
    correlationId: $paperId,
);
$dispatcher->dispatchTyped($event);

if ($event->isHandled() === true) {
    $stamped = $event->getStampedPdf();
} elseif ($event->getRefusalCode() !== null) {
    // filinq refused: show getRefusalReason(), never the unstamped paper.
} else {
    // filinq is not installed: nobody handled the event.
}
```

| Field | Meaning |
|---|---|
| `sourceApp` | Your app id, for filinq's log. |
| `pdfContent` | The PDF bytes. |
| `text` | Plain text, at most 200 characters; lines are separated by a newline. Control characters are dropped. |
| `placement` | `diagonal` (across the page, light grey at 15 percent opacity), `footer` (8 pt lines at the foot of the page), or `both` (default). Across the page the lines are joined into one. |
| `correlationId` | Your own reference, for your logs and filinq's. |

Every page keeps its own size and orientation. The listener never throws: a
failure always comes back as a refusal.

## Refusal codes

| Code | When |
|---|---|
| `not-a-pdf` | The bytes do not start with a PDF header. |
| `too-large` | The file is larger than the `stamp_max_bytes` app setting (default 50 MB; `0` turns the check off). |
| `encrypted` | The PDF is encrypted, so its pages cannot be imported. |
| `failed` | No text was left after cleaning, the placement is unknown, or the PDF could not be read or stamped. |

## Not covered

- Office files: convert to PDF first.
- Keeping or caching the stamped copy is the calling app's job. filinq never
  writes it to anyone's Files.
