# Tasks: intake-failure-reaches-someone

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 6. -->

## 1. The rule

- [x] 1.1 Declare `readingState`, `readingError` and `readingUpdatedAt` on `intakeDocument`, with `readingState` facetable, and bump the descriptor version so `SettingsInitializer` imports it (REQ-IFR-01)
- [x] 1.2 Declare the `readingFailed` rule in the verified dialect: scheduled trigger filtered on `readingState: failed`, `kind: groups` recipients, coalescing window and daily digest with a named timezone, subject and message in Dutch and English (REQ-IFR-02)

## 2. Whether it reaches anybody

- [x] 2.1 `IntakeNotificationReach::describe()` says on the inbox how many people the rule reaches, and warns naming the group when that is nobody, including when nothing has failed yet (REQ-IFR-03)

## 3. Quality

- [x] 3.1 PHPUnit over the declared rule and over the reach, including the storm case and the unstaffed case
- [x] 3.2 Mutation-check the reach: each assertion reddens when the rule it guards is broken
- [ ] 3.3 Wire `describe()` into the inbox view, so the warning is on the screen and not only returned
  - The inbox surface is `document-intake-inbox`'s; this change ships the answer and the assertion, not the template.
