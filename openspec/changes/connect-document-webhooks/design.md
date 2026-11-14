# Design: connect-document-webhooks

Kind: code. Four event classes, one translating listener, one settings
section. Delivery stays in OpenRegister.

## Context

Read at development `2088cc1f`.

- `lib/Settings/filinq_register.json` holds the four records the events
  are about:
  - `signingRequest` (:2353), with an `x-openregister-lifecycle` on
    `status` (:2633) whose `complete` transition (:2679) moves
    `IN_PROGRESS` to `COMPLETED`.
  - `anonymizationLink` (:3932), `status` enum `anonymized`, with
    `sourceFileId`, `anonymizedFileId` and `verificationVerdict`.
  - `generatedDocument` (:1125), `status` enum `generated` or `failed`,
    with `fileId`, `templateId` and `templateVersion`.
  - `documentRegistration` (:71), with `direction`, `unit`, `document` and
    `registrationNumber`.
- `lib/Service/SigningService.php:742` writes `COMPLETED` and then calls
  `SigningConclusionEmitter::emitIfDelegated()` (:747), which dispatches
  `SigningConcludedEvent` only for a request that carries `sourceApp`.
  Internal requests tell nobody.
- `lib/AppInfo/ObjectEventRegistrar.php:136` `boot()` subscribes a
  listener to chosen schemas through OpenRegister's
  `ObjectEventSubscription`, with a loud fallback when OpenRegister is
  absent (:177).
- `src/views/settings/Settings.vue` is the admin page, 1534 lines of
  `NcSettingsSection` blocks (:14 to :529).
- OpenRegister (read-only clone at `ae898b0`):
  - `lib/Service/WebhookService.php:634` `dispatchEvent()` finds the
    enabled webhooks for an event name and enqueues one
    `WebhookDeliveryJob` per webhook. `:1276` signs the payload with the
    webhook's secret. `:293` refuses private and unsafe target hosts.
  - `lib/Listener/WebhookEventListener.php:120` builds a payload only for
    the event classes it names (`:166` onwards), and
    `lib/AppInfo/Application.php:3574` registers it for object, register
    and schema events only. `ObjectTransitionedEvent` is not among them.
  - `lib/Controller/WebhooksController.php:728` `events()` returns a
    hard-coded catalogue.
  - `lib/Db/Webhook.php:390` `matchesEvent()` matches an event name
    exactly or by `fnmatch` pattern, so a webhook can already name any
    class string.
  - Routes `/api/webhooks*` (`appinfo/routes.php:1918`): index, create,
    update, destroy, test, logs and stats. Create requires an admin.

New: the event classes, `DocumentEventTranslator`,
`DocumentWebhooksSection.vue`.

## Goals / Non-goals

Goals: four document events with a stable envelope; the same event
whatever path wrote the record; an admin sets up a receiver from filinq.

Non-goals: delivery, retries and logs (OpenRegister); sending file bytes;
inbound callbacks; more than four events in this change.

## Decisions

### D1. Four named events, one envelope

`OCA\Filinq\Event\DocumentSignedEvent`, `DocumentAnonymisedEvent`,
`DocumentGeneratedEvent` and `DocumentRegisteredEvent` extend one base,
`DocumentWebhookEvent`, which returns the payload. A receiver can subscribe
to one, or to all four with `OCA\Filinq\Event\Document*Event`.

Alternative considered: preset filters on OpenRegister's object events
("Object Updated" where schema is `signingRequest` and status is
`COMPLETED`). Rejected. The receiver then reads filinq's internal object,
so every schema change breaks it. A status filter also matches every later
save of a completed request, and `ObjectTransitionedEvent` is not delivered
to webhooks at all today.

### D2. Raised from object events, in one listener

`DocumentEventTranslator` listens to OpenRegister's
`ObjectTransitionedEvent` for `signingRequest` with action `complete`, and
to `ObjectCreatedEvent` for `anonymizationLink`, for `generatedDocument`
with status `generated`, and for `documentRegistration`. It is subscribed
from `ObjectEventRegistrar::boot()` for those four schemas only, so it is
never woken for other writes (ADR-078). It builds the envelope and
dispatches the matching event. It does no I/O of its own.

Alternative considered: dispatch from each service (`SigningService`,
`GeneratedDocumentLogger::log()`, `AnonymizationPersistenceService`, the
registration writer). Rejected. A write through OpenRegister's API, the
portal or an agent skips the service, and the event would silently not
fire.

### D3. OpenRegister delivers

filinq dispatches through Nextcloud's event dispatcher and stops there.
OpenRegister matches the event to webhooks, queues the delivery, retries,
signs and logs. filinq holds no HTTP client, queue or log (ADR-001, ADR-022).
This needs openregister's half (see the proposal). Without it the events
still fire in-process, and the settings section says delivery is not
available.

### D4. The envelope carries references, not content

Every event carries `event`, `occurredAt`, `document` (`fileId`, `name`),
`record` (`register`, `schema`, `id`) and `actor` (user id, or null for a
system write). Each adds its own fields:

| event | extra fields |
|---|---|
| `document.signed` | `signingRequestId`, `signedFileRef`, `signatureLevel` |
| `document.anonymised` | `sourceFileId`, `anonymisedFileId`, `verificationVerdict` |
| `document.generated` | `templateId`, `templateVersion`, `format` |
| `document.registered` | `direction`, `unit`, `registrationNumber` |

No file bytes, detected entities, signer e-mail addresses or IP addresses.
Alternative considered: attach the file, as Paperless-ngx can. Rejected.
The file would leave with no access check. The receiver fetches it with its
own credentials, so Nextcloud's permissions still apply.

### D5. A section in filinq over OpenRegister's webhook API

`DocumentWebhooksSection.vue` lists the OpenRegister webhooks whose events
name a filinq document event. The admin adds one with a name, a URL, one or
more of the four events and a secret, sends a test, sees the last delivery
result from `/api/webhooks/{id}/logs/stats`, and deletes it. The section
calls OpenRegister's `/api/webhooks` endpoints directly. filinq adds no
controller (ADR-022).

Alternative considered: only point the admin at OpenRegister's webhook
page. Rejected as the only way in. There the admin must know class names.
The filinq section offers four plain choices, and the rows stay visible in
OpenRegister's page too.

### D6. A secret is required

The filinq form does not save a webhook without a secret. OpenRegister
signs each delivery with it. Alternative considered: optional, as
OpenRegister allows. Rejected. Without a signature a receiver cannot tell a
real event from a forged one.

## Declarative-vs-imperative decision (ADR-031)

| concern | choice | why |
|---|---|---|
| which writes raise an event | declared at registration (`ObjectEventSubscription`, four schemas) | the listener is never built for other writes |
| building the envelope | imperative listener | a mapping per event, no lifecycle of its own |
| delivery, retry, signature | OpenRegister `WebhookService` | exists; ADR-001 forbids a second one |
| a `webhook` channel in `x-openregister-notifications` | not used | the target URL would sit in filinq's shipped register file, the same for every install, and an admin could not add one without editing the app |

## Seed data

No schema changes. No seed.

## Risks / trade-offs

- A file name can hold personal data, and it travels in the envelope. The
  admin chooses the receiver; the docs say what each event sends.
- OpenRegister's `findEnabled()` filters webhooks by the active
  organisation. An event raised by a background job may run without one.
  The translator passes the record's organisation along; if delivery
  still misses, that is openregister's to fix and the test in 1.3 shows it.
- Until openregister's half lands, the section shows the webhooks but no
  delivery happens. The section says so rather than looking broken.

## Open questions

- Should a declined or expired signing request also raise an event? The
  envelope allows it. Left out until a receiver asks.
