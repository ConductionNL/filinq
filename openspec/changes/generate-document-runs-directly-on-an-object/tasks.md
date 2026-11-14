# Tasks: generate-document-runs-directly-on-an-object

- [x] 1.1 `DirectGenerateDocumentNode extends GenerateDocumentNode implements IFlowDirectlyInvokable`, same node id.
  - `tests/unit/Flow/GenerateDocumentNodeTest.php::testOnlyTheDirectVariantOptsInToDirectInvocation`
- [x] 1.2 `FilinqFlowNodeListener` registers the variant when OpenRegister has the interface, the plain node otherwise.
  - `tests/unit/Flow/FilinqFlowNodeListenerTest.php` (both paths)
- [x] 1.3 A direct run (`triggeredBy: direct-node`) files on the subject; configured register/schema/objectId are ignored there.
  - `tests/unit/Flow/GenerateDocumentNodeTest.php::testADirectRunFilesOnTheSubjectWhateverTheConfigNames`
- [x] 1.4 Test stub carries `IFlowDirectlyInvokable` (openregister `lib/Service/Flow/IFlowDirectlyInvokable.php`).
- [ ] 1.5 Live: run the node through `POST /apps/openregister/api/flows/{flowId}/nodes/{nodeId}/run` on a published flow against a case (live pass, decision 139).
