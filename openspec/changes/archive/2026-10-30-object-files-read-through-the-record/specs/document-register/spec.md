# document-register (delta)

## ADDED Requirements

### Requirement: A file in an object's folder is read through the object that owns it

The flat file list and the case bundle MUST resolve a file in the reader's
own Files there, and a file in an OpenRegister object's folder only when the
reader may read the object that owns the file, as OpenRegister's object read
rule answers it. The file MUST NOT be opened before that check admits the
reader. A file neither in the reader's Files nor admitted by its object MUST be
left out of the list.

#### Scenario: A dossier's document on the case's list

- GIVEN a case whose document record points at a file in a dossier's folder
- AND a reader who may read that dossier but has no share on the file
- WHEN the reader opens the case's flat file list
- THEN the file is listed, naming its record

#### Scenario: An object the reader may not read

- GIVEN a document record pointing at a file in the folder of an object the reader may not read
- WHEN the reader opens the flat file list or builds the case bundle
- THEN the file is left out
- AND the file is not opened

#### Scenario: Without OpenRegister

- GIVEN OpenRegister is not installed
- WHEN the reader opens the flat file list
- THEN only files in the reader's own Files are listed
