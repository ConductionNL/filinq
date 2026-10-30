# Tasks: object-files-read-through-the-record

- [x] 1.1 `ReadableFileResolver::node()`: the reader's own Files first, then an object's file through `FileReadScope::mayReadFile()` before `FileService::getFileById()`
- [x] 1.2 `FlatFileListService` lists files through the resolver
- [x] 1.3 `CaseArchiveWriter` copies files through the resolver
- [x] 1.4 phpunit: an object's file the reader may read is listed; one the reader may not read is never opened; no reader or no OpenRegister reads nothing beyond the reader's own Files
