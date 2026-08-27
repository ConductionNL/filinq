---
sidebar_position: 2
title: Configure anonymisation rules
description: Tune which entity types Filinq anonymises, which it skips, and how confident it must be before redacting.
---

# Configure anonymisation rules

Filinq's anonymisation pipeline detects entities (PERSON, ADDRESS, BSN, PHONE_NUMBER, EMAIL, …) through the configured backend (Presidio by default). The default rule set fits most Dutch government use cases, but you'll usually want to tune it for your domain — adding custom entity types, raising the confidence threshold for noisy fields, or excluding entities that aren't sensitive in your context.

## Goal

By the end you will have reviewed the active anonymisation rule set, added one custom rule, and verified that the rule fires when you re-run an anonymisation.

## Prerequisites

- You are an administrator on the Nextcloud instance, or your user has been granted the Filinq *Anonymisation curator* role.
- The Presidio backend is reachable from Nextcloud and the connection has been tested at least once (**Settings → Filinq → Anonymisation → Test connection**).

## Steps

1. Open **Settings → Filinq → Anonymisation**. The active rule set is listed in three sections: *Entities to detect*, *Confidence thresholds* and *Custom recognisers*.

   ![Anonymisation settings overview](/screenshots/tutorials/admin/02-anonymisation-rules-01.png)

2. Review *Entities to detect*. Each entity type has an *Enabled* toggle and a *Replacement strategy* (`[ENTITY_TYPE]`, `***`, custom string). Tighten or loosen this for your domain.

   ![Entity-type toggles](/screenshots/tutorials/admin/02-anonymisation-rules-02.png)

3. Under *Custom recognisers*, click **Add custom recogniser**. Declare the entity type name, the regex pattern that matches it (Presidio uses Python re-style regex), and the replacement strategy. Save.

   ![Adding a custom recogniser](/screenshots/tutorials/admin/02-anonymisation-rules-03.png)

4. Verify by running anonymisation against a document that contains a string matching your new regex. The detection table should list a row of your custom entity type at the position of the match.

   ![Custom recogniser firing](/screenshots/tutorials/admin/02-anonymisation-rules-04.png)

## Verification

You are done when: the custom recogniser appears in the *Custom recognisers* list, an anonymisation run against a test document produces a detection row of your custom entity type, and the resulting redacted document has the match replaced with your declared replacement.

## Common issues

| Symptom | Fix |
|---|---|
| Custom recogniser saves but never fires | The regex pattern doesn't match — Presidio uses anchored matches by default, so wrap with `.*` if you need substring matching. |
| Detection table fires twice on the same span (one default, one custom) | Two recognisers overlap. Either disable the default entity type for that span or raise its confidence threshold so your custom one wins. |
| Connection test fails after the rule change | The Presidio backend rejected the rule payload (invalid regex). Check **Settings → Filinq → Logs** for the Presidio response. |

## Reference

- [Anonymisation feature](../../features/anonymization.md) — full reference.
- [Enhanced anonymisation](../../features/enhanced-anonymization.md) — custom recogniser internals.
