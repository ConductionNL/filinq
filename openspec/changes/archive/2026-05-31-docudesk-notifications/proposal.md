---
kind: config
depends_on: [notification-updated-field-change-condition]
---

## Why

Docudesk (Woo publication-consent + e-signing for Woo/records officers, DPO, signers) declares no `x-openregister-notifications` today, so the OpenRegister notification engine (change `notification-schema-rules-and-userconfig-prefs`, archived 2026-05-26) emits nothing for docudesk objects. Per the fleet notification plan, docudesk should notify about: signing request → signers; signing deadline; consent objection-deadline; correspondence-failed — with the explicit caveat that **the data-subject is an external party (held as an email string), so anything routed to a data subject is out of scope; only the staff subset is notifiable today.**

This change adds schema-declared rules in the verified dialect to `lib/Settings/docudesk_register.json`. Recipient-field verification:

- `signerRecord.userId` — a Nextcloud user ID (good; `kind:field` resolves). Signers are notified on the `signerRecord`, not the `signingRequest` (whose `signerIds[]` are signerRecord UUIDs, not uids).
- `signingRequest.initiatorUserId` — a uid (staff initiator).
- `correspondence.generatedBy` — a uid (staff who started generation).
- `publicationConsent.contactEmail` — an **external email string** (the data subject). Not a uid → not deliverable via `field`/`groups`/`object-acl`. The objection-deadline rule therefore notifies the **staff** (Woo/records officers via `groups`, plus object-acl), NOT the data subject. Sending the objection notice to the data subject is the existing `notificationStatus` correspondence flow, out of scope here.

`depends_on: notification-updated-field-change-condition` is declared because the precise "status changed to X" forms (signerRecord → SIGNED/DECLINED, signingRequest → COMPLETED) need the field-change condition on `updated`; until then those use `created` (on the signerRecord) or `scheduled`.

## What Changes

Add `x-openregister-notifications` to these schemas in `lib/Settings/docudesk_register.json`.

### signerRecord — signing request reaches a signer

```jsonc
"x-openregister-notifications": {
  "signingRequested": {
    "trigger": {"type": "created"}, "enabled": true,
    "channels": ["nc-notification"],
    "recipients": [{"kind": "field", "field": "userId"}],
    "subject": {"nl": "Je moet een document ondertekenen", "en": "You have a document to sign"}
  }
}
```

A `signerRecord` is created per signer when a request fans out; `created` + `field:userId` delivers to each signer directly (uid confirmed). For sequential signing the record for the next signer appears when their turn starts, so `created` naturally tracks the queue.

### signerRecord — signing deadline approaching

```jsonc
"x-openregister-notifications": {
  ...,
  "signingDeadline": {
    "trigger": {"type": "scheduled", "intervalSec": 86400, "filter": {"status": "PENDING"}}, "enabled": true,
    "channels": ["nc-notification"],
    "recipients": [{"kind": "field", "field": "userId"}],
    "subject": {"nl": "Herinnering: een document wacht op je handtekening", "en": "Reminder: a document is awaiting your signature"}
  }
}
```

Filtered to still-`PENDING` signer records; the engine evaluates the parent request `deadline` per run.

### signingRequest — completed → initiator

```jsonc
"x-openregister-notifications": {
  "signingCompleted": {
    "trigger": {"type": "scheduled", "intervalSec": 86400, "filter": {"status": "COMPLETED"}}, "enabled": true,
    "channels": ["nc-notification"],
    "recipients": [{"kind": "field", "field": "initiatorUserId"}],
    "subject": {"nl": "Ondertekeningsverzoek '{{documentName}}' is voltooid", "en": "Signing request '{{documentName}}' is complete"}
  }
}
```

Note: the precise "status changed to COMPLETED" form is deferred (see Caveats); the `scheduled`+filter form notifies the initiator while the request is in COMPLETED state. Prefer a `transition` action `complete` if one is defined on the schema lifecycle.

### publicationConsent — objection deadline (STAFF only)

```jsonc
"x-openregister-notifications": {
  "objectionDeadline": {
    "trigger": {"type": "scheduled", "intervalSec": 86400, "filter": {"consentStatus": "pending"}}, "enabled": true,
    "channels": ["nc-notification"],
    "recipients": [{"kind": "groups", "groups": ["docudesk-woo-officers"]}, {"kind": "object-acl", "permission": "manage"}],
    "subject": {"nl": "Bezwaartermijn verloopt binnenkort voor een publicatie", "en": "Objection deadline is approaching for a publication"}
  }
}
```

Recipients are **staff** (Woo/records officers + manage-ACL), never the data subject. The engine evaluates `objectionDeadline` per scheduled run; the data-subject notice itself remains the existing `notificationStatus`/correspondence path.

### correspondence — generation failed → staff

```jsonc
"x-openregister-notifications": {
  "correspondenceFailed": {
    "trigger": {"type": "created"}, "enabled": true,
    "channels": ["nc-notification"],
    "recipients": [{"kind": "field", "field": "generatedBy"}, {"kind": "groups", "groups": ["docudesk-woo-officers"]}],
    "subject": {"nl": "Documentgeneratie mislukt: {{templateName}}", "en": "Document generation failed: {{templateName}}"}
  }
}
```

Note: `correspondence` carries a `status` enum (`generated`|`failed`). `created` fires for every correspondence row regardless of status; a true "only when failed" rule needs the field-change/created-with-status-filter form. If the engine supports a `filter` on `created`, add `"filter": {"status": "failed"}`; otherwise this is deferred to the field-change engine change — see Caveats.

## Capabilities

No new product capability. This adds schema-declared notification configuration consumed by the existing OpenRegister notification engine.

## Impact

- **Affected file:** `lib/Settings/docudesk_register.json` only (additive `x-openregister-notifications` blocks).
- No data migration, no API change, no Vue change.
- Rules go live only when `notification-schema-rules-and-userconfig-prefs` engine is present.

## Caveats

- **Data subjects are external (`publicationConsent.contactEmail` is an email string, not a uid).** Anything addressed to a data subject is **out of scope** — `field`/`groups`/`object-acl` resolve internal Nextcloud users only. The objection-deadline rule notifies **staff** (Woo/records officers + manage-ACL); the data-subject objection notice stays on the existing `notificationStatus`/correspondence flow. Carried from the fleet plan caveat.
- **"only when status = failed/COMPLETED" is a field-change pattern not expressible today** unless `created` supports a status `filter`. `correspondenceFailed` and `signingCompleted` are approximated (`created` for all correspondence; `scheduled`+`filter:status` for completed requests) and the precise form is deferred to `notification-updated-field-change-condition` (declared in depends_on) or a named `transition` action on the schema lifecycle.
- **`transition` triggers assume named lifecycle actions** (e.g. `complete` on signingRequest); none are confirmed on the docudesk schemas, so `created`/`scheduled` are used. Verify and switch to `transition` where a named action exists.
- **`docudesk-woo-officers` group** is assumed to exist; confirm provisioning or swap to the deployment's actual Woo/records-officer group name.
- **Signer delivery relies on `signerRecord.userId` being populated** (a Nextcloud uid). For purely external signers (no NC account) this is null and delivery falls back to the existing provider e-mail flow — not the notification engine.
