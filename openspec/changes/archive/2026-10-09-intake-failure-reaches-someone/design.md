---
kind: code
---

# Design: intake-failure-reaches-someone

## The storm, and why coalescing alone is not enough

A scanner that jams feeds every page it holds and fails every one of
them. Four hundred notifications is the same silence with noise in front
of it, because the registrar turns the channel off and then the next
failure, the real one, arrives into a muted channel.

Two declarations, not one:

- `coalesce: {windowSeconds: 3600, maxEvents: 50}` folds the burst. An
  hour is the scanner's whole jam, and fifty is a cap on how much one
  message carries so producing it cannot itself become the incident.
- `digest: {schedule: daily, at: "08:30", timezone: "Europe/Amsterdam"}`
  holds the rest to one message at the start of the working day.

The timezone is declared rather than left to the server, or 08:30 is
whatever the container thinks 08:30 is.

Together: a jammed scanner at two on a Saturday morning is one message at
08:30, not four hundred through the night.

## Why the trigger is scheduled and not a field change

The engine cannot express "fire when `readingState` becomes `failed`".
The capability's own spec already names that limit and its deferral. So
the rule is `scheduled` with `filter: {readingState: "failed"}`, hourly,
which is the approximation the capability mandates. The cost is stated
rather than hidden: a document that fails and is fixed within the hour
may never produce a message, which is the correct outcome, and a document
that stays failed is picked up on the next pass.

## Where the properties live

The filter reads `readingState`, so `readingState` must be a declared,
facetable property on `intakeDocument` or the filter matches nothing for
ever and the rule is a rule about an empty set. `readingError` and
`readingUpdatedAt` ship with it: a message saying three documents could
not be read, without saying why, sends a registrar to a developer.

`IntakeFailureNotificationTest` asserts the property exists and carries
`failed` in its enum, because a filter naming a property nothing declares
is the exact shape of a rule that reports nothing while looking correct.

## What the message says

That the documents are still in the inbox and still assignable by hand.
The text is what failed, not the document. A registrar meeting this at
08:30 needs to know they can still work, or they will wait for a fix that
was never blocking them.

## The reach question

`IntakeNotificationReach::describe(int $failureCount, int $groupMembers)`
returns what the inbox shows. Three cases:

- Nothing failed and the group is staffed: nothing to say.
- Nothing failed and the group is empty: say it anyway. An unstaffed rule
  is worth knowing about before the night it is needed. An inbox that
  only mentions it once something has already gone unnoticed is telling
  somebody too late.
- Something failed and the group is empty: say how many, say nobody was
  told, and say the reason they are reading this is that they opened the
  inbox.

The warning names the group. "Nobody is configured to receive this" sends
an administrator hunting through settings; the group name is the one
thing that turns it into a two-minute fix.

## Why this is not openregister's job

openregister answers "did this rule reach anybody when it ran", after the
fact, in a log. This answers "will it reach anybody at all", before it
runs, on a screen. The first is for whoever reads logs. The second is for
whoever works the post. They are different questions with different
audiences and neither substitutes for the other.

Where filinq's answer differs from the platform's, this is the reason: it
is not a competing mechanism, it is the same finding delivered to the
person who can act on it.
