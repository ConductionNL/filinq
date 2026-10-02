# Tasks: documents-in-and-out-of-the-building

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 12. -->

## 1. The registration entry

- [x] 1.1 Add a `documentRegistration` schema to the `filinq` register with `direction`, `registeredAt`, `unit`, `document`, `registrationNumber`, `answers` and `withdrawnReason`, with a descriptor version bump (REQ-DIO-01)
  - `direction` is a closed enum: a document either came in or went out, and a
    third value would be a document that never left, which is not a post
    register entry at all.
  - `registeredAt` is the moment of REGISTERING, not the date printed on the
    document. The two differ often enough that conflating them loses the point
    of a post register.
  - The discharge is a LINK (`answers`) and there is no `answered` flag on the
    inbound entry. A flag can be set without an answer existing; a link cannot.
    Asserted by name, including the absence of `answered` and `dischargedAt`.
- [~] 1.2 Declare `x-openregister-generated` on `registrationNumber`: a format carrying the year and the position, and a yearly reset; no counter in filinq (REQ-DIO-01)
  - DECLARED AND VERIFIED AGAINST THE REAL VALIDATOR, not just written:
    OpenRegister's own `GeneratedIdentifierDeclaration::fromProperty()` accepts
    it and renders `2026-00042` for sequence value 42. No counter in filinq, so
    two registrations in the same second cannot race for one number and silently
    reuse it.
  - 🔴 "A SEQUENCE NAMED PER UNIT" IS NOT POSSIBLE TODAY, AND SHIPPING IT WOULD
    HAVE BEEN SILENT. `GeneratedIdentifierListener::scopeKey()` builds the scope
    as `'gen:' . $declaration->sequence() . '|' . $period`, substituting NOTHING.
    So `filinq-post-{unit}` creates ONE series literally named that, shared by
    every unit, while reading in the schema as though each unit had its own. Two
    units' numbers would interleave in one pool and nothing would report it.
  - So the sequence is the literal `filinq-post` and the series is
    INSTANCE-WIDE. Said in the property's own description and asserted by a test
    that refuses a `{` in the sequence name, so it cannot be added back as an
    apparent improvement.
  - WHAT WAITS, named rather than guessed: per-unit series need OpenRegister to
    resolve placeholders in a sequence name against the object being saved. That
    is a platform change and belongs in openregister's `generated-identifier`
    change, not here.
- [x] 1.3 Refuse an update that changes a set registration number (REQ-DIO-01)
  - 🔑 THE TASK ASSUMED A SERVICE FILINQ DOES NOT HAVE, and the platform was
    checked rather than trusted. Registrations are written through
    OpenRegister's objects API, so a guard in a filinq service would be bypassed
    by every ordinary write.
  - What filinq does have is the platform's PRE-WRITE event, verified end to
    end: `MagicMapper` dispatches `ObjectUpdatingEvent` before an update with
    both the new and the old object, and throws `HookStoppedException` carrying
    the listener's own errors when propagation is stopped. So the refusal lands
    on every path through the mapper, which is all of them.
  - The past-tense `ObjectUpdatedEvent` filinq already listens to could NOT have
    done this: by the time it fires the number has already changed.
  - `DocumentRegistrationWriteGuard`. It matches on the schema SLUG, not the
    numeric id, which differs per instance and would make the guard silently
    inert everywhere but the instance it was written on.
  - A FIRST number may still land: the platform writes it on create, and an
    entry saved before it had one may legitimately gain one. Refusing that would
    make the ordinary path impossible, which is how an over-eager guard takes
    down the feature it protects. Mutation-checked in both directions.
- [~] 1.4 Refuse the registration, naming the missing sequence, when the register declares no generated identifier (REQ-DIO-01)
  - 🔑 MOSTLY THE PLATFORM'S ALREADY, MEASURED NOT ASSUMED.
    `GeneratedIdentifierListener` already refuses when a DECLARED sequence
    cannot issue: it sets `generated-identifier-unavailable` naming the property
    AND the sequence, stops propagation, and the object is not created. Building
    a second refusal for that case would be a second answer to one question.
  - THE REAL GAP IS THE OTHER CASE: when a schema declares NO generated
    identifier at all, that listener's loop finds nothing and the object is
    created with no number, silently. Filinq closes that by SHIPPING the
    declaration and pinning it with a test, rather than at runtime.
  - 🔴 AND A RUNTIME GUARD HERE WOULD BE ORDERING-DEPENDENT, which is why it is
    not built. A create-time check for "a number is present" runs against
    whatever the listener order happens to be: if filinq's guard runs before the
    platform issues the number, it refuses every legitimate create. That is a
    guard whose correctness depends on something neither app declares, and it
    would fail in the direction that blocks the feature entirely.
  - What would make it sound is the platform guaranteeing issuance ordering, or
    exposing "this schema declares an identifier" as a question filinq can ask
    before the write. Named rather than guessed.

## 2. Discharge and the open post list

- [x] 2.1 An outbound registration names the inbound one it answers; the discharge is read from the link and never written as a status on the inbound entry (REQ-DIO-02)
  - `PostRegisterReader::answersFor()`. The inbound entry carries NO `answered`
    flag and never will: a flag can be set by anybody at any time without an
    answer existing, and then the register reports a letter as dealt with
    because somebody ticked a box. Reading it from the outbound entry that NAMES
    the inbound one means the register can only claim a discharge with a
    document behind it. The schema's lack of `answered`/`dischargedAt` is
    asserted by name.
  - 🔑 THE SEARCH CONTRACT WAS CHECKED, NOT ASSUMED, and it is the sharp edge
    here. OpenRegister's objects search takes BARE property keys beside a
    `@self` block; the sibling aggregations endpoint spells the same filter as
    `filter[...]`, and the objects endpoint reads that wrapper as the EMPTY SET.
    Written the wrong way this reader would report every inbound entry as
    undischarged, confidently and with no error anywhere. The query shape is
    pinned by a test and mutation-checked.
  - IT RETURNS THE ANSWERS, NOT A BOOLEAN. "Discharged: yes" loses which
    document did it, which is what a reader a year later actually wants, and a
    caller given a boolean cannot get the list back.
  - 🔴 A FAILED READ IS RAISED, NOT REPORTED AS "NO ANSWERS". An empty list
    would say the letter is still open when it may have been answered a month
    ago, which is the reading that gets a second reply sent.
- [~] 2.2 The open post list per unit, oldest first, offered as a leaf per ADR-066 (REQ-DIO-02)
  - BUILT: `PostRegisterReader::openPostFor()`, the undischarged inbound entries
    of a unit, oldest first.
  - 🔴 "OPEN" IS NOT A STORED FIELD, FOR THE SAME REASON `answered` IS NOT. The
    list is built from the same read as the discharge: an entry is open when
    nothing names it. A cached `open` column would be the identical lie one step
    further from where anybody would look for it.
  - 🔴 AN ENTRY WHOSE DISCHARGE COULD NOT BE READ IS RAISED, NOT LISTED AS OPEN.
    Listing it puts a letter somebody answered a month ago at the top of a work
    list ordered oldest first, and it gets answered again. Dropping it silently
    is worse: a letter nobody answered would vanish from the only list that
    would have caught it. `answersFor()` already raises, and that raise is
    deliberately not caught.
  - THE QUERY USES BARE KEYS BESIDE `@self`, pinned by a test that refuses a
    `filter` key. Written the other way this method reports a unit with no post
    at all, confidently and with nothing in the log.
  - AN UNDATED ENTRY SORTS LAST, NOT FIRST. An unknown date is not evidence of
    age, and sorting it first would put it above letters that really have been
    waiting.
  - 🔑 STILL BLOCKED: the leaf. Same blocker as `case-documents-and-the-flat-list`
    2.2, measured again on 2026-09-18: filinq consumes no
    `RegisterLeafProvidersEvent` and `webpack.config.js` declares no `leaves`
    entry, so a leaf registered here would be DARK while its registration
    reported success. The list is an endpoint's worth of behaviour now; the leaf
    is a change of its own.
- [x] 2.3 Record a withdrawn allocation with its reason and moment when a numbered registration is not written, and expose the series so it reads end to end (REQ-DIO-03)
  - `withdrawnAt` ADDED: the schema shipped with `withdrawnReason` alone, and
    the task asks for the reason AND the moment. A withdrawal with only one of
    the two is half a record, because an auditor reading the series a year later
    needs to place the gap in time as well as explain it. Schema version moved
    with it.
  - A HALF-RECORDED WITHDRAWAL IS REPORTED INCOMPLETE rather than quietly
    treated as either withdrawn or not. Smoothing it over is how a gap stops
    being legible.
  - `PostRegisterReader::series()` returns the entries in number order with each
    one's withdrawal state attached, so an unexplained hole is visible as one. An
    entry with no number is left out: including it would put a row with no
    position among rows ordered by position.
  - WHAT IS NOT BUILT HERE: the surface that shows the series, which is 2.2's
    leaf and is blocked on the missing `leaves` webpack entry already recorded
    in `case-documents-and-the-flat-list`.

## 3. The plain-language rendition

- [x] 3.1 A template declares a plain-language counterpart and its required statements; generation produces both renditions from one generation record, and the plain one names the formal document (REQ-DIO-04)
  - `template.plainLanguage` (counterpart template, `requiredStatements`,
    `source`), schema 1.2.0 → 1.3.0; the plain rendition's fields on
    `generatedDocument`, 1.1.0 → 1.2.0. `PlainLanguageRenditionService` reads
    the declaration and `DocumentService::producePlainRendition()` renders it.
  - 🔴 BOTH RENDITIONS COME OUT OF ONE GENERATION, from the same data and the
    same huisstijl. There is no second call that produces the plain one, which
    is what stops the two letters disagreeing about a date while both claim to
    describe one decision.
  - 🔴 NO COUNTERPART MEANS NO RENDITION, AND NOTHING IS INVENTED IN ITS PLACE.
    Generated plain text reads fluently whether or not the organisation ever
    approved it, which is exactly why nobody would catch it. A declaration
    naming no template is not a counterpart either: treating it as one would
    refuse every generation over a rendition nothing can render.
  - THE PLAIN RENDITION NAMES THE FORMAL DOCUMENT, on the record and in the
    result. Somebody receives two letters about one decision; a plain letter
    that does not say which decision it explains leaves them holding two
    documents and no relation between them.
  - THE FORMAL RENDITION IS UNTOUCHED. Nothing in this change reads or rewrites
    it; it is passed in so it can be named.
- [x] 3.2 Refuse the generation on an unresolved required statement, and regenerate the plain rendition whenever the formal one is regenerated (REQ-DIO-04)
  - 🔴 THE REFUSAL HAPPENS BEFORE ANYTHING IS FILED, AND THAT IS A PROPERTY OF
    WHERE IT IS CALLED, NOT OF THE SERVICE. `plan()` runs before
    `storeOutputIfRequested()`, so a refusal leaves NEITHER rendition behind.
    Mutation-checked by moving the call after the store: the assertion that
    reddens is `$this->storage->expects(self::never())->method('store')` in
    `DocumentServicePlainRenditionTest`, which is the only place that ordering
    is visible at all.
  - THE REFUSAL NAMES THE UNRESOLVED STATEMENT. "The generation failed" sends a
    handler looking through a template for a hole they cannot see.
  - AN EMPTY STRING IS UNRESOLVED; `0` AND `false` ARE ANSWERS. A blank where
    the term should be reads as "there is no term" to the person holding the
    letter, while a term of zero days is a strange decision that is still a
    decision, and refusing it would make the letter impossible to send.
  - A COUNTERPART TEMPLATE THAT CANNOT BE READ REFUSES THE GENERATION. Filing
    the formal letter alone would drop a rendition the template says every
    reader gets, and nobody would notice until somebody complained they could
    not read their besluit.
  - REGENERATION NEEDS NO SEPARATE CALL: one generation produces both, so a
    correction takes the twin with it by construction rather than by anybody
    remembering. `stale()` exists for records written before that was true and
    for any path that ever files one without the other; an unreadable formal
    moment reads as stale, because the cheap error is regenerating something
    current and the expensive one is sending a plain letter about a decision
    that has since been corrected.
- [~] 3.3 A machine-drafted plain rendition is a suggestion: not filed and not sent until a named person accepts it, with the acceptance recorded (REQ-DIO-05)
  - BUILT: `PlainRenditionAcceptanceGate`, one chokepoint asked the same
    question by every path, wired into the generation path through
    `PlainLanguageRenditionService::plan()`.
  - 🔴 ACCEPTANCE IS A PERSON AND A MOMENT, BOTH OR NEITHER. "Accepted: true"
    can be written by the same machine that drafted the text, and it is exactly
    the flag this requirement exists to refuse. A name with no moment cannot be
    placed in time when somebody asks a year later whether the draft was read
    before or after the correction, so half an acceptance is refused AS an
    acceptance rather than accepted as half.
  - TEXT FROM THE COUNTERPART TEMPLATE NEEDS NO ACCEPTANCE: it is the
    organisation's own, reviewed when the template was written. Demanding one
    there would make every ordinary besluit wait for a click nobody was told to
    make, which is how a gate takes down the feature it guards.
  - 🔑 WHAT WAITS: the correspondence and portal paths. The requirement names
    three ways out and only generation calls the gate today. That is a MISSING
    CALL, which somebody can grep for, rather than a second rule written
    slightly differently in two more places, which nobody can see. Naming it
    here rather than writing two more copies of the decision.
  - ALSO NOT BUILT: a store for the draft itself, and the surface a person
    accepts it on. Filinq drafts nothing with machine assistance yet, so an
    acceptance UI would be a screen for a thing that does not exist.

## 4. The download notification

- [~] 4.1 Record file, version, moment, route and identity; name the link instead of a person on a public link; enqueue the notification rather than delivering it on the download path (REQ-DIO-06)
  - `DocumentDownloadRecorder` builds the record and decides whether to notify.
  - 🔴 "EVERY DOWNLOAD ROUTE" IS NOT REACHABLE FROM A PLATFORM LISTENER, checked
    end to end. The only download event Nextcloud exposes is
    `BeforeDirectFileDownloadEvent`, and it carries a PATH and a success flag —
    no version, no route, no identity. Worse, only ONE thing dispatches it:
    `apps/dav/lib/Controller/DirectController.php`, the DAV direct-link feature.
    `files_sharing` merely listens. So the web UI, WebDAV GET and public share
    downloads never raise it.
  - A listener there would have recorded a FRACTION of downloads while the audit
    read as complete, which is the failure this whole change is about. So the
    recorder is wired to filinq's OWN routes, which are enumerable
    (`version#download`, `printJob#download` and two print routes), and the
    platform routes are named here as not covered rather than silently missed.
  - A PUBLIC LINK NAMES THE LINK, NOT A PERSON. Whoever opened it is
    unauthenticated, so any name is a guess, and a guessed name in an audit
    record is read as fact by whoever reads it next. The link is what was used
    and what can be revoked. Mutation-checked.
  - THE NOTIFICATION IS ENQUEUED, NEVER DELIVERED ON THE DOWNLOAD PATH. A slow
    mail server would make the download slow; a dead one would make it fail, and
    the document would then not leave the building because a notification could
    not be sent.
  - WHAT WAITS: wiring the recorder into the four controllers, and the audit
    write itself. Named rather than half-done.
- [~] 4.2 Declare the notification in the `x-openregister-notifications` dialect with steward and watcher recipients, staff only, and collapse repeats inside a declared window (REQ-DIO-07)
  - 🔴 THE DIALECT CANNOT COLLAPSE AN EVENT-DRIVEN NOTIFICATION, measured not
    assumed. It supports `trigger.dedupeFields`, and that key is honoured by
    exactly two things: `ScheduledNotificationJob` and
    `TaskScheduledNotificationJob`, both SCHEDULED paths. Nothing collapses an
    event-driven notification, and the dialect has no time-WINDOW concept at
    all — `dedupeFields` is field-based, per scheduled run.
  - So declaring a window there would be a key nobody reads: stored, validated
    by the annotation validator, and never once honoured. The collapse is
    implemented in `DocumentDownloadRecorder` instead, where it runs.
  - THE COLLAPSE IS PER FILE AND PER IDENTITY, not per file alone. Two different
    people downloading the same document inside the window are two facts, and
    collapsing them would hide the second person entirely — which on a
    confidential document is the one you most want to know about.
  - AN UNREADABLE TIMESTAMP NOTIFIES rather than swallowing the download. A
    missing notification about a document leaving is the failure this
    requirement exists to prevent; noise is the cheaper error.
  - WHAT WAITS: the declaration itself, once somebody decides whether to add a
    window to the dialect or leave collapsing to the app. Not guessed at here.

## 5. Quality

- [~] 5.1 PHPUnit inside the container for the numbering, the discharge, the withdrawal, both renditions, the suggestion gate and every download route; 75% on new code (ADR-009); Playwright `tests/e2e/documents-in-and-out.spec.ts`; Dutch and English strings; docs in `docs/features/documents-in-and-out.md` with screenshots
  - DONE: PHPUnit for the numbering, the discharge, the withdrawal, the open
    post list, both renditions and the suggestion gate, plus the ordering test
    on `DocumentService` that no other suite can see.
  - DONE: `tests/e2e/workflows/documents-in-and-out.spec.ts`, anchored to three
    scenarios. It drives the STORE rather than a screen, deliberately: the leaf
    is blocked, so what is reachable is that the schemas resolve, accept the
    shape the services write, and DROP `answered` and `dischargedAt` in
    silence. That silence is the assertion, not a detail.
  - STILL OPEN: the download-route half of the tests, which waits on 4.1's
    wiring; the Dutch and English strings, because nothing in this change puts
    a word on a screen yet; and `docs/features/documents-in-and-out.md` with
    screenshots, which waits on there being a screen to photograph.
