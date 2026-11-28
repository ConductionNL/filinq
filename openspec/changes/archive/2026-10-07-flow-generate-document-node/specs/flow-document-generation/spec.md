# flow-document-generation Specification (delta)

---
status: proposed
---

## Purpose

Filinq owns document generation for the fleet (hydra ADR-075). This capability publishes it to OpenRegister's flow engine as the `filinq.generate-document` node and to other apps as a command event, so no app needs a template renderer of its own.

## ADDED Requirements

### Requirement: A flow step generates a document per item

Filinq SHALL contribute a flow node with id `filinq.generate-document`. For each input item it SHALL render the configured Filinq template with the item's data, produce a file in the configured format (`pdf`, `odf` or `html`), store it in the acting user's Files under Filinq's generated-document folder with a folder per object, and return the item with the document's details (file id, path, name, mime type, size, format, template used, object, metadata) under the configured output key (default `document`).

#### Scenario: Each item gets its own document filed against its object

- GIVEN a flow step of type `filinq.generate-document` with an inline template and two items whose `@self` names objects `obj-a` and `obj-b`
- WHEN the step runs
- THEN two documents are generated, each stored under `DocuDesk/<namespace>/<object id>`
- AND each outgoing item carries its own file id under `document`, and keeps its own fields
- @e2e exclude Flow-engine step with no UI of its own; covered by GenerateDocumentNodeTest.

#### Scenario: The rendered text is written into one field

- GIVEN a step with `targetField: besluit` and `storeFile: false`
- WHEN the step runs for an item whose object is known
- THEN only the `besluit` field of the stored object is patched with the rendered text, no file is stored
- AND the outgoing item carries the text under `besluit`
- @e2e exclude Flow-engine step with no UI of its own; covered by GenerateDocumentNodeTest and DocumentGenerationRequestServiceTest.

#### Scenario: A failure reaches the engine

- GIVEN a step naming a template that does not exist
- WHEN the step runs
- THEN the node throws, so the step's `onError` policy decides what happens
- @e2e exclude Engine error policy path; covered by GenerateDocumentNodeTest.

### Requirement: A configuration that cannot run is refused when the flow is saved

`validateConfig()` SHALL refuse a configuration that names no template or more than one of `templateId`, `templateSlug` and `template`; a `templateSlug` without `templateNamespace`; a format other than `pdf`, `odf` or `html`; `storeFile` off without `targetField`; `metadata` that is not an object; or an empty `output`. The same rules SHALL be applied to every request at run time.

#### Scenario: A step without a template is refused

- GIVEN a step configuration with only `format: pdf`
- WHEN the flow is saved
- THEN validation fails with a message asking for exactly one template
- @e2e exclude Save-time validation of a backend contract; covered by DocumentGenerationRequestServiceTest.

### Requirement: Other apps request a document through a command event

Filinq SHALL publish `OCA\Filinq\Event\DocumentGenerationRequestedEvent`, constructed with a request (the node's keys plus `data`, `object` and optional `userId`) and the requesting app id. Filinq's listener SHALL generate the document synchronously during dispatch and write the result onto the event (`getResult()`, `isHandled()`), or, when generation fails, the reason (`getError()`), without letting an exception escape into the dispatcher.

#### Scenario: A request comes back with its document

- GIVEN dossiq dispatches the event with an inline template, case data, the case object and metadata
- WHEN dispatch returns
- THEN `isHandled()` is true and `getResult()` carries the file id and the metadata unchanged
- @e2e exclude In-process event contract; covered by DocumentGenerationRequestedListenerTest.

#### Scenario: A refused request comes back with its reason

- GIVEN a request that names no template
- WHEN dispatch returns
- THEN `isHandled()` is false, `getError()` names the problem, and no document was generated
- @e2e exclude In-process event contract; covered by DocumentGenerationRequestedListenerTest.

### Requirement: Every generation on this path announces itself

After every successful generation through the node or the command event, Filinq SHALL dispatch `OCA\Filinq\Event\DocumentGeneratedEvent` carrying the object reference, the file id and path, the template used, the requester's metadata passed through untouched, and the requesting app. It SHALL NOT be dispatched for a failed generation.

#### Scenario: A consumer files its own record from the event

- GIVEN a document generated for object `case-1` with metadata naming an informatieobjecttype
- WHEN a listener reads the `DocumentGeneratedEvent`
- THEN `getObject()`, `getFileId()` and `getMetadata()` give it everything it needs without asking Filinq again
- @e2e exclude In-process event contract; covered by DocumentGenerationRequestedListenerTest and DocumentGenerationRequestServiceTest.

### Requirement: One generation path for the flow node, the command event and the API

The node and the command listener SHALL both call `DocumentGenerationRequestService::generate()`, which SHALL resolve the template and render, store and audit through `DocumentService::generateFromTemplate()`, the same path `POST /api/documents/generate` uses.

#### Scenario: The node and the event behave the same

- GIVEN the same request sent through the node and through the command event
- WHEN each runs
- THEN both reach `DocumentService::generateFromTemplate()` with the same template, data and options
- @e2e exclude Structural guarantee; covered by the node and listener tests running over the real service.

### Requirement: Filinq registers its node and still boots without OpenRegister

Filinq SHALL register its node listener for `OCA\OpenRegister\Service\Flow\RegisterFlowNodesEvent` by name during `register()`, and SHALL boot and register without error when OpenRegister is not installed.

#### Scenario: The node reaches the catalogue

- GIVEN OpenRegister dispatches `RegisterFlowNodesEvent`
- WHEN Filinq's listener handles it
- THEN the catalogue contains `filinq.generate-document`
- @e2e exclude Registration seam; covered by FilinqFlowNodeListenerTest and DocumentGenerationWiringTest.

#### Scenario: Filinq boots without OpenRegister

- GIVEN a process in which OpenRegister's flow contract does not resolve
- WHEN Filinq's document-generation registrar runs
- THEN it links and registers both listeners without error
- @e2e exclude Boot proof; covered by tests/scripts/boot-without-openregister.php via RetiredApprovalSurfaceTest.
