# Legal holds

Freeze the records a lawsuit, an audit or a Woo appeal needs, so nobody destroys or deletes them while the matter runs.

A legal hold is a case: a name, a matter type, a reason and the records it covers. While it is active, OpenRegister leaves those records off every destruction list. Where the `files_lock` app is installed, their files are locked as well. Releasing the case lifts the freeze, except on records another active case still covers.

Filinq does hold, freeze and audit. It does not review, tag or export documents for counsel.

## Who may place and release holds

Admins, and the groups in the app setting `legal_hold_authority_groups` (a JSON list):

```bash
occ config:app:set filinq legal_hold_authority_groups --value='["juridische-zaken"]'
```

An empty list means admins only. A value that is not a list of group names means nobody may act, admins included. That way a broken setting never widens who can unfreeze evidence.

Everybody else gets "You are not allowed to place or release legal holds." from the server, whatever the page shows.

## Place a hold

1. Open **Legal holds** in the menu and click **New legal hold**.
2. Fill in a name, the matter type, why the records are frozen, and the record ids of the documents and dossiers. The custodian and the case reference are optional.
3. Click **Place hold**.

The case is saved as active first, then every record is frozen. The case detail shows per record what happened:

| Record hold | Meaning |
|---|---|
| Frozen | OpenRegister holds the record for this case |
| Already frozen by another party | Somebody else's hold was there first. Filinq leaves it as it is |
| Not frozen | The hold could not be placed. The reason is shown, and **Retry** tries again |

The files column says whether the files behind the record were locked, had no files, or could not be locked. Without `files_lock` it says so, and the case shows that files are not locked. The records are still frozen.

A case says **Active, not every record frozen** until every record is frozen by this case.

The owners of the records and the custodian get a notification naming the case.

## Add records to a hold

Open the case, type the extra record ids under **Add document record ids** and click **Add to hold**. Only the new records are frozen; the others are not touched. A hold never grows or shrinks by itself.

## Release a hold

Open the case and click **Release**. The button in the dialog stays off until you type why. The server refuses a release without a reason too.

Per record, release does one of three things:

- no other active case covers it: the hold is lifted and the files are unlocked;
- another active case covers it: the hold stays, now in that case's name, and the files stay locked;
- another party's hold is on it: Filinq leaves it alone.

Released is final. A new matter is a new case. Released cases stay in the register, with who released them, when and why. OpenRegister keeps the full history of each record's holds.

## On a dossier

A held dossier shows **Under legal hold** at the top. People with hold authority see which case holds it. Everybody else only sees that it is held. **Remove from dossier**, which can move a file to the trash, is switched off.

## Two matters on one record

OpenRegister keeps one hold per record. Filinq works out which active cases list a record whenever it releases one, and keeps the record frozen while any other case needs it. Native support for several holds per record is proposed in ConductionNL/openregister#4172; when it lands, Filinq drops its own bookkeeping.

## API

| Method | Route | What it does |
|---|---|---|
| GET | `/apps/filinq/api/legal-holds?status=&holdType=&custodian=` | The register |
| POST | `/apps/filinq/api/legal-holds` | Place: `name`, `holdType`, `reason`, `scopeDocuments`, `scopeDossiers`, optional `caseReference`, `custodian` |
| GET | `/apps/filinq/api/legal-holds/{id}` | One case |
| POST | `/apps/filinq/api/legal-holds/{id}/scope` | Add records |
| POST | `/apps/filinq/api/legal-holds/{id}/retry` | Retry failed records |
| POST | `/apps/filinq/api/legal-holds/{id}/release` | Release, `releaseReason` required |
| GET | `/apps/filinq/api/legal-holds/status/{objectId}` | Whether a record you can read is held; case names only for hold authority |

Refusals: 403 without authority, 400 when something required is missing, 404 for an unknown case or a record you cannot read, 409 on a released case.
