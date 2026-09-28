# Proposal: flow-generate-document-node

## Why

dossiq carried its own `createDocument` and `mergeTemplate` flow nodes with a private `{{...}}` renderer. They never called Filinq, although Filinq owns document and PDF generation for the fleet (hydra ADR-075: reached through a published contract, never loopback HTTP). So the fleet had two template engines, and a letter made in a flow did not get Filinq's huisstijl, formats, storage or audit record.

Ruben decided: Filinq exposes generation to OpenRegister's flow engine and to other apps, and dossiq's nodes are deleted. This change is Filinq's half.

## What changes

- A flow node `filinq.generate-document`, registered on OpenRegister's node catalogue through `RegisterFlowNodesEvent`. Per item it renders a Filinq template (by id, by namespace and slug, or inline text), produces a PDF, ODF or HTML file and files it against the item's object. Optionally it writes the rendered text into one field of that object instead of, or besides, the file.
- A command event `OCA\Filinq\Event\DocumentGenerationRequestedEvent` that other apps dispatch to ask for a document in-process. Filinq's listener generates it and writes the result, or the reason it could not, back onto the event.
- An announcement event `OCA\Filinq\Event\DocumentGeneratedEvent`, dispatched after every generation through the node or the command event. It carries the object reference, the file id, the template used, the requester's metadata untouched, and the requesting app, so dossiq can file its informatieobject from it.
- The node and the listener share `DocumentGenerationRequestService`, which hands the resolved template to `DocumentService::generateFromTemplate()`. That method is the body of the existing `generateDocument()`, split out so there is one render, store and audit path.

## Out of scope

- Deleting dossiq's nodes and moving its flows onto this node (dossiq's lane).
- Registering the generated file as an OpenRegister object file (`FileService`). The file is stored where Filinq already stores generated documents, in a folder per object, and the object is named in the audit record and on the event.

## Impact

- New: `lib/Flow/`, `lib/Event/DocumentGenerationRequestedEvent.php`, `lib/Event/DocumentGeneratedEvent.php`, `lib/EventListener/DocumentGenerationRequestedListener.php`, `lib/Service/DocumentGenerationRequestService.php`, `lib/AppInfo/DocumentGenerationRegistrar.php`.
- Changed: `DocumentService` (split, behaviour of `generateDocument()` unchanged, result gains `html`), `RegistrationBootstrap` (one line).
- Filinq still boots without OpenRegister: the node listener is registered by event name only, and the node class is built only when OpenRegister dispatches the event.
