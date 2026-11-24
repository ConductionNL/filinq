## ADDED Requirements

### Requirement: The companion knows the document or folder a page shows (REQ-CKOD-001)

A filinq page that shows one document MUST set the companion context to
`pageKind: "document"` and that document's file id; a dossier page MUST set
`pageKind: "folder"` and the dossier's home folder id. Leaving the page MUST
take the file id back out of the context.

#### Scenario: A document page

- **GIVEN** the file viewer showing file 4711
- **WHEN** the companion opens
- **THEN** its context carries `fileId: 4711` and `pageKind: "document"`

#### Scenario: A dossier page

- **GIVEN** a dossier whose home folder is 88
- **WHEN** the companion opens on that dossier
- **THEN** its context carries `fileId: 88` and `pageKind: "folder"`

#### Scenario: Leaving the page

- **GIVEN** the companion context points at a document
- **WHEN** the user leaves the page
- **THEN** the context no longer carries that file id

### Requirement: The dossier detail names its home folder (REQ-CKOD-002)

The dossier detail response MUST carry `folderId`, the id of the dossier's home
folder, or null when it has none.

#### Scenario: A dossier with and without a folder

- **GIVEN** a dossier with home folder 4242, and one without
- **WHEN** each detail is read
- **THEN** the first carries `folderId: 4242` and the second `folderId: null`
