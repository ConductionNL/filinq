# Design: case documents, the flat list and the provisioned folder

Kind: code. One leaf, one provisioning service, one upload policy, one
nightly job, two schema additions.

## Context

The register is `filinq`, one register since descriptor v8.0.0. The
document record, the dossier and their versions already live there.
`DossierFileService` and `DocumentStorageService` already write files;
nothing reconciles a folder's permissions and nothing gates an upload by
type.

## D1. Two views, two questions, one store

The record list answers "what documents does this case have". The flat
list answers "what files are on this case". They read the same objects.
The flat list is a leaf of its own so a consuming app can place either,
both or neither, and so neither view has to grow a mode switch that makes
it worse at both jobs.

## D2. Provisioning is create once, reconcile always

Creating the folder is the easy half. OpenProject keeps
`last_project_folder` precisely because the hard half is what happens
when the domain's membership changes afterwards. So provisioning is a
reconciliation: on domain create, on membership change, and on a nightly
sweep that reports drift it corrected and drift it could not.

A folder the service cannot reconcile is reported, never silently left
wrong. A permission that drifted and was corrected is written to the
audit trail, because "who could read this dossier in March" is a question
an AVG incident asks.

## D3. The upload policy is enforced at the write, not described in code

`DocumentDefaults.php` names types today and nothing gates on it. The
policy becomes an administered object: allowed extensions, allowed media
types, a maximum size, and whether an unknown type is refused or accepted
with a warning. It is checked server-side on every write path, including
the leaf, the API and the intake channels, because a policy enforced in
one of four paths is not a policy.

The media type is read from the bytes, not from the filename. A .exe
renamed to .pdf is the case the check exists for.

## D4. A document has domains, it does not have copies

The document record carries `domains[]` rather than one owning domain.
Plane models exactly this: a page is workspace-owned and linked to
projects through a join. Copying is how three versions of one advies
appear and how two of them go stale. Access is the union of what the
domains allow, evaluated by OpenRegister, not recomputed here.

Removing the last domain does not delete the record; it leaves it owned
by its creator, visible in the my-documents list. Silent deletion on
unlink is the way a record disappears with nobody having decided to
delete it.

## D5. Desktop editing and resumable upload are Nextcloud's

Both already work through the platform: the sync client edits a file in
Word and syncs it back, and chunked upload resumes an interrupted one.
The candidate note on resumable upload says as much, "Nextcloud chunked
upload does it, filinq does not own it". So this change tests them on the
case folder and documents them, and builds neither. Claiming to have
built a feature the platform gives you is how a capability matrix stops
being trustworthy.

## D6. An external store is a mount, not a second integration

A municipality that keeps documents in SharePoint mounts it in Nextcloud
and points the case folder at it. The records stay in OpenRegister, the
files live on the mount. filinq validates on setup that the mount
supports what the policy needs, and says which parts of the policy a
given mount cannot enforce, rather than discovering it on the first
upload.

## D7. The reaper says what it did

A nightly job removes upload fragments older than a declared age and logs
the count and the bytes. A reaper that runs silently is a reaper nobody
notices has stopped.

## Risks

- **Reconciliation that fights an administrator.** A folder somebody
  deliberately shared wider is reset by the next sweep. The sweep reports
  before it corrects on the first run after a change, and an
  administrator can pin a folder out of reconciliation with a reason.
- **A flat list over a large dossier.** Three hundred files is a page,
  not a screen. The list pages and filters, and it is the same list
  component every other index uses.
- **Media type detection on a large file.** The check reads the leading
  bytes, not the whole file.
- **A document unlinked from every domain.** Kept, owned, and listed.
  Never deleted as a side effect.
