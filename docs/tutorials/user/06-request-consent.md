---
sidebar_position: 6
title: Request publication consent
description: Open a consent request on a document so Filinq tracks the WOO-compliant objection period before the document can be published.
---

# Request publication consent

Under the Dutch *Wet Open Overheid* (WOO) every document containing personal data must observe a minimum **four-week objection period** before it is published. Filinq's consent flow makes that period explicit: you open a consent request on a document, the affected data subjects are notified, and the document is locked from publication until the period has expired or all objections have been resolved.

## Goal

By the end you will have opened a consent request on one document, picked the affected data subjects, set the objection period, and watched Filinq record the request with its earliest-publish date.

## Prerequisites

- You have completed [Upload a document](./02-upload-document.md) (and ideally [Anonymise a document](./03-anonymise-document.md)).
- The document you want to publish is in the **Documents** register, and you know which data subjects (citizens, organisations) are mentioned in it.

## Steps

1. Open the document detail view and click **Request consent** in the action bar.

   ![Document detail with Request consent action](/screenshots/tutorials/user/06-request-consent-01.png)

2. The consent dialog asks for the **affected data subjects** (search or add manually with name + contact channel), the **publication channel** (which downstream system the document will appear on), and the **objection period** (defaulting to 4 weeks per WOO).

   ![Consent request dialog](/screenshots/tutorials/user/06-request-consent-02.png)

3. Submit. Filinq creates a Consent object linking the document, the data subjects, the channel and the objection period — and dispatches notifications to each data subject through the configured channel.

   ![Consent created](/screenshots/tutorials/user/06-request-consent-03.png)

4. Open the **Consents** list view to see the new request. Its **Earliest publish date** column makes clear when the objection period ends; before that date the document is held back from any publication action.

   ![Consents list with earliest-publish date](/screenshots/tutorials/user/06-request-consent-04.png)

## Verification

You are done when: a Consent object exists in **Consents**, its *Status* column reads *Awaiting period*, and the source document's detail view shows a banner *Consent in progress — earliest publish DD-MM-YYYY*.

## Common issues

| Symptom | Fix |
|---|---|
| Submit fails with "no notification channel configured" | The publication channel doesn't have an email/post template attached — an admin sets this in **Settings → Filinq → Notifications**. |
| Data subject search returns nothing | The instance has no contact register configured — pick *Add manually* and key the name + email/post address directly. |
| Earliest-publish date is shorter than 4 weeks | The WOO minimum is enforced server-side; if you see < 4 weeks the admin has explicitly overridden the default. Confirm this is intentional. |

## Reference

- [Consent management feature](../../features/consent-management.md) — full reference.
- [Wet Open Overheid (WOO)](https://www.rijksoverheid.nl/onderwerpen/wet-open-overheid) — the law that drives the four-week minimum.
