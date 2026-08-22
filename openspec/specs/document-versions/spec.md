# document-versions Specification

## Purpose
TBD - created by archiving change document-versions-detail-tab. Update Purpose after archive.
## Requirements
### Requirement: Document detail presents a Versies tab listing Nextcloud file versions

The system SHALL present a `Versies` tab on the `Document detail` surface that lists the Nextcloud
file versions of the document, newest first, each showing at least its timestamp, author, and
size, read via `OCA\Files_Versions\Versions\IVersionManager::getVersionsForFile`. The tab SHALL be
part of the ADR-001 detail tab family and SHALL NOT be a new top-level menu. When `files_versions`
is disabled on the instance the tab SHALL render an informative notice (the localised
`versions-unavailable` message) rather than an error, keeping the information architecture stable.

#### Scenario: Versions are listed newest-first on the detail tab
- GIVEN a document whose underlying Nextcloud file has three prior versions
- WHEN the user opens the `Document detail` `Versies` tab
- THEN the three versions are listed newest-first with timestamp, author, and size
- AND the current version is distinguished from the prior versions

#### Scenario: files_versions disabled shows a notice, not an error
- GIVEN the `files_versions` app is disabled on the instance
- WHEN the user opens the `Versies` tab
- THEN the tab renders an informative "file versions are not available on this instance" notice
- AND the tab remains present in the detail tab family

### Requirement: A version can be opened or downloaded

The system SHALL allow a user with read access to the document to open or download any listed
version of the file.

#### Scenario: Download a prior version
- GIVEN the `Versies` tab lists a prior version
- WHEN the user selects "download" on that version row
- THEN the bytes of that specific version are returned to the user

### Requirement: A version can be restored

The system SHALL allow a user with write access to the document to restore a prior version via
`IVersionManager::rollback`. Before restoring, the current state SHALL be preserved as a new
version, and the user SHALL confirm the action. A user without write access SHALL NOT be able to
restore a version.

#### Scenario: Restore a prior version preserves the current state
- GIVEN a user with write access on the `Versies` tab
- WHEN they choose "restore" on a prior version and confirm
- THEN the current state is first saved as a new version
- AND the selected prior version becomes the current file content

#### Scenario: Restore requires write access
@e2e exclude authorization guard — per-object write check is covered by PHPUnit/API tests, not a UI path
- GIVEN a user with only read access to the document
- WHEN a restore is attempted for a version
- THEN the operation is rejected and the file content is unchanged

### Requirement: A version can be handed to the existing comparison flow

The system SHALL let the user compare a listed version with the current version (or with the
previous version) by handing the `fileId` + `versionTimestamp` pair to the existing
`DocumentComparisonService` / `ComparisonController` — reusing the shipped comparison engine, not a
new diff implementation. The compare action SHALL be offered only for text-extractable versions
(as `DocumentComparisonService` already gates), and hidden or disabled otherwise.

#### Scenario: Compare a version with the current document
- GIVEN a text-extractable document with a prior version listed on the `Versies` tab
- WHEN the user selects "compare with current" on that version
- THEN the existing comparison flow is invoked with the version's `fileId` + `versionTimestamp`
- AND a structured diff between the version and the current content is presented

#### Scenario: Compare is not offered for non-extractable versions
- GIVEN a version whose mime type is not text-extractable
- WHEN the version row is rendered
- THEN the "compare" action is hidden or disabled for that row (open/download/restore remain)

### Requirement: Version listing is served by a thin, authorized read endpoint

The system SHALL expose a read endpoint that lists a document's versions by delegating to
`IVersionManager`, guarded per-object so a user can only list versions of a document whose
underlying Nextcloud file they can read. The endpoint SHALL NOT introduce any Filinq-owned
version storage, and SHALL paginate large histories (limit/offset) consistent with Filinq's
other list endpoints.

#### Scenario: Listing is scoped to the caller's file permissions
@e2e exclude authorization guard — per-object read check verified by PHPUnit/API tests, no distinct UI surface
- GIVEN a document whose underlying file the caller cannot read
- WHEN the caller requests that document's version list
- THEN the request is rejected (no versions are disclosed)

#### Scenario: No Filinq-owned version storage is created
@e2e exclude architecture invariant — verified by code review / route + schema inspection, not a UI flow
- WHEN Filinq's schemas and routes are inspected
- THEN no Filinq-owned table, schema, or store persists file versions
- AND versions are read exclusively from Nextcloud `files_versions` via `IVersionManager`

