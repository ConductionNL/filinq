# Proposal: generate-document-runs-directly-on-an-object

## Why

dossiq's case page has a Generate document action. Ruben decided (dossiq decision 154, 10 Oct 2026) to keep its dialog for choosing a template by hand AND to let the action run filinq's generate-document step directly, through OpenRegister's run-node endpoint (`POST /api/flows/{flowId}/nodes/{nodeId}/run`, or-flow-run-node). OpenRegister refuses a node that has not opted in to that (`IFlowDirectlyInvokable`), and `filinq.generate-document` had not.

## What changes

- `filinq.generate-document` opts in to direct invocation on an OpenRegister that offers it, through a variant class (`DirectGenerateDocumentNode`), so the node still loads and registers on an OpenRegister without the interface.
- On a direct run the document is filed on the subject the caller was authorized against. A configured `register`, `schema` or `objectId` is not honoured there, because OpenRegister checked the caller's update right on the subject only.

## Impact

- `lib/Flow/DirectGenerateDocumentNode.php` (new), `lib/Flow/FilinqFlowNodeListener.php`, `lib/Flow/GenerateDocumentNode.php`.
- No register, schema or migration change. No UI.
