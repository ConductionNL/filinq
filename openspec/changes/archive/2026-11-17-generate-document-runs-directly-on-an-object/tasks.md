# Tasks: generate-document-runs-directly-on-an-object

- [x] 1.1 `DirectGenerateDocumentNode extends GenerateDocumentNode implements IFlowDirectlyInvokable`, same node id.
  - `tests/unit/Flow/GenerateDocumentNodeTest.php::testOnlyTheDirectVariantOptsInToDirectInvocation`
- [x] 1.2 `FilinqFlowNodeListener` registers the variant when OpenRegister has the interface, the plain node otherwise.
  - `tests/unit/Flow/FilinqFlowNodeListenerTest.php` (both paths)
- [x] 1.3 A direct run (`triggeredBy: direct-node`) files on the subject; configured register/schema/objectId are ignored there.
  - `tests/unit/Flow/GenerateDocumentNodeTest.php::testADirectRunFilesOnTheSubjectWhateverTheConfigNames`
- [x] 1.4 Test stub carries `IFlowDirectlyInvokable` (openregister `lib/Service/Flow/IFlowDirectlyInvokable.php`).
- [x] 1.5 Live: run the node through `POST /apps/openregister/api/flows/{flowId}/nodes/{nodeId}/run` on a published flow against a case (live pass, decision 139).
  - Live pass 2026-11-17 (NC 34.0.6, openregister main 82131bd5f6): a published flow `trigger-manual -> filinq.generate-document -> end`, run with `POST .../flows/{id}/nodes/g1/run` and subject `{register, schema, uuid}` of a filinq dossier, answered 201 with `trigger: direct-node`, status `completed`, and the document filed on that subject (`object` = the subject, fileId 122 under the subject's folder). The subject key is `uuid`; `id` is refused with 400.
