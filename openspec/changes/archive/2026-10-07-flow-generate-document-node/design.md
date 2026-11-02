# Design: flow-generate-document-node

## One path, three doors

```
flow engine ──> GenerateDocumentNode ──┐
other app  ──> DocumentGenerationRequestedEvent ──> DocumentGenerationRequestedListener ──┤
                                                                                         v
                                                  DocumentGenerationRequestService::generate()
                                                                                         v
                                                  DocumentService::generateFromTemplate()
                                                  (same path as POST /api/documents/generate)
```

The node and the listener hold no generation logic. Validation also lives in the service, so the node's `validateConfig()` (run when a flow is saved) and the per-request check (run for every request, because an imported flow and an event reach execution unvalidated) are the same rules.

## Node configuration

| Key | Required | Meaning |
|---|---|---|
| `templateId` | one of three | Filinq template id |
| `templateSlug` + `templateNamespace` | one of three | template by slug in an app's namespace; `templateTenantId` optional |
| `template` | one of three | inline template text; `templateName` optional |
| `format` | no | `pdf` (default), `odf` or `html` (the source format) |
| `storeFile` | no | default true; false needs `targetField` |
| `targetField` | no | write the rendered text into this field of the object, patching only that field |
| `targetPath` | no | folder in the acting user's Files; default `DocuDesk/<namespace>/<object id>` |
| `filename` | no | without extension; may hold `{{ }}` placeholders |
| `huisstijlId` | no | huisstijl to apply |
| `register`, `schema`, `objectId` | no | the object; default from the item's `@self` (or `id`/`uuid`) |
| `metadata` | no | object, passed through untouched to the result and DocumentGeneratedEvent |
| `requestingApp` | no | recorded on the event; default `flow` |
| `output` | no | item field for the result; default `document` |

The render context is the item's fields at the top level plus the whole item under `item`. When the object is known it is also passed as a data reference, so the stored object is available under its schema key and is named in the generated-document audit record.

A field-only request (`storeFile: false`) renders HTML and stores nothing, since the text is what is written.

## Output on the item

`json[output]` = `{fileId, path, name, mime, size, format, template: {id, slug, name, version, source}, object, targetField, metadata, requestingApp, warnings}`. With `targetField`, `json[targetField]` also carries the rendered text so the next step sees what this step stored.

## Failures

Every failure throws from the service. The node lets it reach the engine, whose `onError` policy decides. The command listener catches it and writes the message to the event's error slot, because the dispatching app must be able to read a refusal the same way it reads a success. No `DocumentGeneratedEvent` is dispatched for a failed generation.

## Event contract

`DocumentGenerationRequestedEvent(array $request, string $requestingApp)`:
`getRequest()`, `getRequestingApp()`, `setResult(array)`, `getResult(): ?array`, `isHandled(): bool`, `setError(string)`, `getError(): ?string`. The request takes the node's keys plus `data` (render context), `object` (`{register, schema, id}`) and optionally `userId`. Not handled and no error means Filinq is not installed.

`DocumentGeneratedEvent(array $document, string $requestingApp)`:
`getDocument()`, `getObject(): ?array`, `getFileId(): ?int`, `getFilePath(): ?string`, `getTemplate(): array`, `getMetadata(): array`, `getRequestingApp(): string`.

## Acting user

OpenRegister runs a contributed node inside the run's acting identity, so the session user owns the stored file. With no user and a file to store, the request is refused rather than filed as nobody. A command-event caller may pass `userId`.

## Booting without OpenRegister

`DocumentGenerationRegistrar` registers the node listener for `OCA\OpenRegister\Service\Flow\RegisterFlowNodesEvent` as a string literal. During Filinq's `register()` OpenRegister's classes are not autoloadable, and the dispatcher keys listeners by name, so with OpenRegister absent the event is never dispatched and nothing is loaded. `tests/scripts/boot-without-openregister.php` proves it.
