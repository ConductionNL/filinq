# Tasks: flow-generate-document-node

- [x] Split `DocumentService::generateDocument()` into a lookup plus `generateFromTemplate()`, and return the rendered `html` beside the content.
- [x] `DocumentGenerationRequestService`: validate, resolve template (id, slug, inline), generate, write the target field, dispatch `DocumentGeneratedEvent`.
- [x] `DocumentGenerationRequestedEvent` and `DocumentGeneratedEvent`.
- [x] `DocumentGenerationRequestedListener`, error slot on failure.
- [x] `GenerateDocumentNode` (`filinq.generate-document`) with config keys, config form and taxonomy.
- [x] `FilinqFlowNodeListener` and `DocumentGenerationRegistrar`, called from `RegistrationBootstrap`.
- [x] OpenRegister flow contract stub for PHPStan, psalm and the unit bootstrap; `patchObject` on the ObjectService stub.
- [x] Tests: service, node, flow listener, command event round trip, wiring from the bootstrap, boot without OpenRegister.
- [x] l10n: node and form strings in en and nl.
