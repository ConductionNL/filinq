---
kind: code
---

# Proposal: intake-failure-reaches-someone

Closes the limit named in filinq#1133, in that PR's own words: nothing
there notifies anybody, so a failure reaches a person who opens the inbox
and nobody who does not.

## Why

`IntakeReadingProgress` made a failed read a state of its own, carrying
what went wrong, counted apart from still-reading. That was the right
half. The other half is that a scanner jamming at two on a Saturday
morning is discovered on Monday, by whoever happens to open the inbox,
and by then the paper original has been filed and the post has moved on.

The failure is recorded. Nobody is told. Those are different things, and
the first looks exactly like the second on any screen that only shows
what somebody opened.

## What this does

Declares a `readingFailed` rule on `intakeDocument` in the verified
`x-openregister-notifications` dialect, addressed to the records
officers, coalesced and digested so a jammed scanner is one message
rather than four hundred.

And then says, on the inbox itself, whether that rule can reach anybody
at all.

## Why the second half exists

The rule addresses `docudesk-woo-officers`. Every declared group in this
fleet ships empty on purpose, because an empty group denies everyone
except admins and object owners, which is the right default for a fresh
install. So on the day this ships, the rule resolves to nobody.

openregister#3961 made that visible: a rule reaching zero recipients
records itself once per rule per run, at warning level. Measured on
openregister `parity/round2`, nothing calls `RuleReachRecorder::report()`
yet, so today that record is a log line and not a person. It is findable
by somebody who already suspects the problem, which is the wrong
audience.

The person who needs to know is the registrar who will never be told a
scan failed, and they are looking at the inbox. So the inbox asks the
question at read time, from the group the rule names, and says the answer
in words beside the failure count.

## What this is not

Not a fallback recipient. openregister#3961 argued that one out: a
fallback sends a records failure to whoever happens to be an admin, which
is how a notification channel gets muted. The answer here is the same,
and it is to name the gap where the person who can close it is standing.

Not a second notification engine. The rule is declared in the dialect
openregister owns and dispatched by it. What filinq adds is a question
about that rule, answered on a screen.
