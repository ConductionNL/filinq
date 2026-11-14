---
kind: code
depends_on: []
---

# Proposal: connect-document-webhooks

Matrix row `con-webhook` in filinq's `openspec/parity/capabilities.json`,
rated no, built.state none. Written in the OpenSpec pass of 27 September 2026.

## Why

A records system wants to know when a contract comes back signed. Today it
has to poll. filinq tells nobody outside the building: `grep -ri webhook lib
src appinfo` finds nothing.

The delivery machinery exists one app over. OpenRegister delivers webhooks
with retries, signatures and logs (`lib/Service/WebhookService.php`, routes
`/api/webhooks*`). filinq's records are OpenRegister objects, so an outside
system can already subscribe to "Object Created" or "Object Updated". That
is the wrong grain. It gets every save of every object, in the object's
internal shape, and has to work out for itself that a document was signed.
What is missing is an event about the document: it was signed, anonymised,
generated or registered. And an admin has no place in filinq to point such
an event at a URL.

### Matrix rows (filinq openspec/parity/capabilities.json)

| row | capability | filinq today |
|---|---|---|
| `con-webhook` | Call an outside system when something happens to a document. | no: `grep -ri webhook lib` finds no match anywhere in the app |

### Competitors rated yes

- ValidSign (docs read 2026-09-26): "21 callback events (DOCUMENT_SIGNED,
  PACKAGE_COMPLETE, PACKAGE_DECLINE, ...)".
  Evidence: https://developers.validsign.eu/docs/callback/setting-up-callback-notifications
- Paperless-ngx (source read at v3.2.1): "workflow Webhook action POSTs to a
  configured URL with params or JSON body, headers and optionally the
  document file (src/documents/models.py:1626-1673,
  src/documents/workflows/webhooks.py)". Evidence:
  https://github.com/paperless-ngx/paperless-ngx/tree/v3.2.1/src/documents/workflows

## What changes

- Four document events: signed, anonymised, generated and registered. Each
  carries one small envelope that names the document and the record, never
  the file's content.
- filinq raises them from the OpenRegister object events it already
  receives, so a write through the API, the portal or an agent raises the
  same event as a click in filinq.
- OpenRegister delivers them. filinq writes no HTTP client, queue or
  delivery log.
- A "Document webhooks" section on filinq's admin settings page. An admin
  picks the events, types the receiving URL and a secret, sends a test, and
  sees whether the last delivery arrived.

## Capabilities

### New capabilities

- `document-webhooks`: document-level events an outside system can
  subscribe to, and the admin section that sets up the subscription.

### Modified capabilities

None. `filinq-signing-events` keeps its in-process `SigningConcludedEvent`
for delegating apps; this change adds an outward event beside it.

## Impact

- New `lib/Event/DocumentWebhookEvent.php` and four subclasses
  (`DocumentSignedEvent`, `DocumentAnonymisedEvent`,
  `DocumentGeneratedEvent`, `DocumentRegisteredEvent`).
- New `lib/EventListener/DocumentEventTranslator.php`, subscribed in
  `lib/AppInfo/ObjectEventRegistrar.php::boot()` for four schemas.
- New `src/views/settings/DocumentWebhooksSection.vue`, placed in
  `src/views/settings/Settings.vue`.
- `docs/features/`: a section with a screenshot and the payload of each
  event.

## Out of scope

- Delivery, retries, signing of payloads and delivery logs. OpenRegister
  owns them.
- Sending the file itself with the event.
- Inbound callbacks from outside systems. That is a different surface
  (ADR-054, ADR-091).
- Events for other moments (declined, expired, downloaded). The envelope
  allows more events later; this change ships four.

## Cross-app dependencies

- **openregister**: an extension point for app-declared webhook events.
  Today `WebhookEventListener::handle()` only builds a payload for the
  event classes it names (`lib/Listener/WebhookEventListener.php:166` and
  on), and `WebhooksController::events()` (`:728`) returns a hard-coded
  catalogue. openregister must (1) deliver an app event that hands over its
  own payload through a published interface, without the listener naming
  the class, and (2) list app-declared events in the catalogue with a name,
  description and category, so its own webhook page and filinq's section
  read one list. Until that lands filinq's events fire in-process and reach
  no webhook.
