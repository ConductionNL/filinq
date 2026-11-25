# Woo decision letter and inventory

For app developers. dossiq (or decidiq, if the decision moves there) asks
filinq to draft the decision on a Woo request and its inventory list. filinq
checks the decision data, renders the organisation's own templates and files
the documents. filinq does not assess documents and does not assemble the
disclosure package: dossiq holds the documents and does both.

Spec: `openspec/changes/woo-request-workflow` (REQ-DDWRW-006, -007, -010, -011).

## The templates

filinq seeds two templates in namespace `woo`. Both declare the data contract
`woo-decision`.

| Slug | What it renders |
| --- | --- |
| `woo-besluit` | The decision letter: reference, subject, the received and decision dates, the decision kind, then per assessment the documents by inventory number with their refusal grounds in words. |
| `woo-inventarislijst` | One row per document in the caller's order: inventory number, title, date, assessment, grounds, remark. |

An organisation edits both in the template editor. An upgrade never overwrites
an edited template: the seeds are matched by slug and carry no version of their
own. A document with assessment `partially-disclose` is never shown as
disclosed, and inventory numbers are printed as sent, never renumbered.

## The request

Dispatch `OCA\Filinq\Event\DocumentGenerationRequestedEvent` through
`IEventDispatcher` and read the outcome from the same event object.

```php
use OCA\Filinq\Event\DocumentGenerationRequestedEvent;

$event = new DocumentGenerationRequestedEvent(
    request: [
        'templateNamespace' => 'woo',
        'templateSlug' => 'woo-besluit',          // then 'woo-inventarislijst'
        'format' => 'pdf',
        'filename' => 'Besluit Woo-verzoek WOO-2026-0042',
        'object' => ['register' => '…', 'schema' => '…', 'id' => $caseId],
        'data' => ['wooDecision' => $wooDecision],
        // 'allowRetiredGrounds' => true,        // only to redraft an old decision
    ],
    requestingApp: 'dossiq',
);
$dispatcher->dispatchTyped($event);
```

### `data.wooDecision`

| Key | Type |
| --- | --- |
| `reference`, `subject`, `organisation` | non-empty string |
| `receivedAt`, `decisionDate` | date, `Y-m-d` |
| `decisionKind` | `disclose`, `partially-disclose`, `withhold` or `not-held` |
| `documents` | list of `{inventoryNumber, title, date, assessment, groundCodes, remark}` |

`assessment` is `disclose`, `partially-disclose` or `withhold`. `groundCodes`
are codes from the refusal grounds list (for example `5.1.2.e`); filinq prints
each as its label, article and code. The list is read from dossiq when it is
installed, else from the snapshot filinq ships.

## The outcome

- **Handled**: `isHandled()` is true and `getResult()` carries `fileId`,
  `path`, the template that was used and `requestingApp`. A
  `DocumentGeneratedEvent` announces the document to the fleet.
- **Refused**: `isHandled()` is false and `getError()` names every problem
  found, not only the first: a missing key, a `partially-disclose` or
  `withhold` document without a ground, two documents with one inventory
  number, an unknown ground, or a retired ground without
  `allowRetiredGrounds`. Nothing is rendered or filed.
- **Nobody answered** (filinq not installed): neither handled nor refused. The
  caller records that no letter was drafted.

## Tests

- `tests/unit/Settings/WooTemplateSeedTest.php`: both seeds, and that an edit
  survives an upgrade.
- `tests/unit/Service/Disclosure/DisclosureDecisionTemplatesTest.php`: the letter and the
  inventory agree, from `tests/fixtures/woo-decision.json`.
- `tests/unit/EventListener/DocumentGenerationRequestedListenerWooTest.php`:
  the request as dossiq sends it, filed and refused.
