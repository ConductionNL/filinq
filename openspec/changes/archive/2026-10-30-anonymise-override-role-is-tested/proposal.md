---
kind: code
depends_on: []
---

# Proposal: anonymise-override-role-is-tested

## Why

#1436 made releasing a publish-never match on the anonymise call depend on the
override role (`ProhibitionOverrideAuthority`). That branch had no test of its
own. Writing one showed the gate could never reach it: `ProhibitionGateService`
and `ProhibitionPolicyService::checkUnredactedProhibitions()` called
`PolicyMatchService::matchProhibition()` with arguments named `entityType` and
`entityValue`, which that method does not take, and read `ruleId`/`ruleName`,
which it does not return. Every call threw. Fail-closed, the gate refused every
anonymise on a file with detected entities as "backend unavailable"; fail-open,
it matched nothing. The unit test double had the same wrong signature, so no
test saw it.

## What changes

- Both callers use `matchProhibitionHint(entityType, entityValue)`, the method
  with that signature and that `{ruleId, ruleName}` result.
- A unit test drives `ProhibitionGateService::run()` with an override below the
  threshold: without the role it is rejected and nothing is recorded; with the
  role it is released and audited.
- The double in `AnonymizationServicePublicationClearanceTest` now fakes the
  real method.
