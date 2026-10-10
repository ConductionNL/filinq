## ADDED Requirements

### Requirement: The generate-document step can be run directly on one object

On an OpenRegister that offers direct node invocation (`IFlowDirectlyInvokable`), the node registered as `filinq.generate-document` SHALL implement it, so an app may run that one step of a published flow against one subject through `POST /api/flows/{flowId}/nodes/{nodeId}/run`. On an OpenRegister without that interface the plain node SHALL be registered under the same id. On a direct run (the run's `triggeredBy` is `direct-node`) the document SHALL be filed on the subject item; a configured `register`, `schema` or `objectId` SHALL NOT redirect it.

#### Scenario: A direct run files on its subject

- GIVEN a published flow whose step `generate` is `filinq.generate-document` with `objectId: someone-elses` in its configuration
- WHEN an app runs that step directly against case `subject-1`
- THEN the document is filed on `subject-1`
- @e2e exclude Flow-engine step with no UI of its own; covered by GenerateDocumentNodeTest; the button is dossiq's case-documents suite.

#### Scenario: An older OpenRegister still gets the step

- GIVEN an OpenRegister without `IFlowDirectlyInvokable`
- WHEN filinq registers its flow nodes
- THEN `filinq.generate-document` is registered as the plain node
- @e2e exclude Registration seam; covered by FilinqFlowNodeListenerTest.
