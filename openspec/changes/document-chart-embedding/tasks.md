# Tasks

## 1. Build the chart

- [ ] Add `ChartCodec` producing bar, line and pie charts as native DrawingML
- [ ] Write values and labels into `<c:numCache>`/`<c:strCache>`, which is what suites render from
- [ ] Omit axes for pie charts, which open with a repair prompt if they carry them
- [ ] Validate the definition: one value per category, one series for a pie, known type

Acceptance criteria:
- A mismatched series length is refused naming both counts. Padding would draw a chart the caller did not describe; truncating would drop data.

## 2. Keep the package consistent

- [ ] Write the chart part, the content-type Override, the relationship, and the body drawing
- [ ] Derive the relationship id by scanning existing ones; never hard-code it
- [ ] Append before the `sectPr`, which must remain the body's last child
- [ ] Support placement after an anchored paragraph, refusing an unresolvable anchor

Acceptance criteria:
- On a document with six relationships the chart takes `rId7` and all six survive.
- The id in the rels and in the drawing are asserted to be the same — a mismatch makes the file unopenable.

## 3. Agent surface

- [ ] Add `addDocumentChart`, and `EditSessionService::embedChartForAgent()` running through the same session as a text edit
- [ ] Name the no-embedded-workbook limitation in the tool description so the model can tell the user

Acceptance criteria:
- Same lock, same version precondition, same `Agent authored` tag as a text edit.
- Document tools: 5 → 6.

## 4. Verify against a real suite

- [ ] Render the same document through ONLYOFFICE with and WITHOUT the chart and compare
- [ ] Treat a suite error as a hard failure, not a warning

Acceptance criteria:
- The control run is mandatory. A conversion returning success is not evidence — a suite that skips an unparseable part still emits a PDF.
- Measured: 25,793 bytes without, 51,777 with.

## 5. Regression

- [ ] Full unit suite green; `phpcs` clean across `lib/Service/Editing/`

Acceptance criteria:
- The pre-existing editing tests pass unmodified.
