# Contracts

Keep your contracts in Filinq and get a reminder before a notice deadline or an end date passes.

A contract holds its parties, dates, value and documents. It starts as a draft, you put it in force, and then you renew it or end it. Filinq reads the contract documents and suggests dates and amounts. A suggestion changes nothing until somebody accepts it.

Filinq does contracts as documents with dates. It has no clause library, no negotiation and no approval chain.

## Who can work with contracts

Members of the group `filinq-contract-managers` can read, create and change contracts. Admins can also delete them. Everybody else does not see them: OpenRegister decides, in the list, the detail and the actions.

Create the group in the Nextcloud user settings and add the people who manage contracts.

## Pages

- **Contracts** lists every contract you can read, with its status, end date and notice deadline. Click **New contract** to add one. Click a row to open it.
- **Renewal pipeline** shows the active contracts by what needs attention first:

| Bucket | Which contracts |
|---|---|
| Expired | The end date has passed |
| Notice due within 30 days | The notice deadline is within 30 days, or has passed |
| Ending within 90 days | The end date is within 90 days |
| Later | Every other active contract |

Drafts, renewed, ended and expired contracts are not in the pipeline. You can renew or end a contract from its row.

An active contract whose end date has passed shows as expired, on the detail and in the pipeline, even before its status is changed.

## Put a contract in force, renew or end it

On the contract page:

- **Put in force** moves a draft to active.
- **Renew** marks the contract renewed and creates a new draft. The draft carries the parties, type, owner, value and currency. The two contracts link to each other, and the page opens the new draft.
- **End contract** ends an active contract early. You must give a reason. The reason stays on the contract.

OpenRegister checks every status change against the lifecycle the contract declares. A move it does not allow, like from draft straight to ended, is refused and the contract stays as it was.

## Dates and reminders

Fill in the end date and the notice period in days. When you leave the notice deadline empty, Filinq sets it to the end date minus the notice period. A deadline you entered yourself is never changed.

OpenRegister sends a Nextcloud notification to the contract managers before the notice deadline and before the end date of an active contract. Filinq itself sends nothing.

## Parties

A party can point to a Nextcloud contact (`urn:nc:contact:<uid>`). Then the page shows the name and e-mail address from that contact, marked "from contacts". A party without a contact, or whose contact is gone, shows the name stored on the contract.

## Documents and signing

- **Attach files** adds files from Files to the contract.
- **Generate from a template** fills a template with the contract and saves the PDF in your files.
- **Send for signature** creates an ordinary signing request for one of the contract documents.

The contract only keeps references: file ids, the signing request and the signed document. Signing and templates work as they always do. The page shows the status of the signing request. Once the request is completed, the signed document is linked to the contract.

## Suggested terms

After you attach or generate a document, Filinq reads it for a start date, an end date, a notice period, a value, a currency and party names. It reads text files, and PDFs when `pdftotext` is installed on the server. Nothing leaves the server.

Each suggestion shows its field, value and how sure the reading is. **Accept** writes the value on the contract. **Reject** only marks the suggestion. OpenRegister's audit trail records who accepted what.

Switch suggestions off in the Filinq admin settings under **Contract terms**, or with:

```bash
occ config:app:set filinq enable_contract_term_extraction --value=0
```

When they are off, nothing is read and the page shows no suggestions.

## API

Contract create, read and edit go through OpenRegister's object API (`/apps/openregister/api/objects/filinq/documentContract`). The actions are Filinq routes:

| Route | What it does |
|---|---|
| `POST /apps/filinq/api/contracts/{id}/renew` | Renew; answers the contract and the successor |
| `POST /apps/filinq/api/contracts/{id}/terminate` | End early; `reason` is required |
| `POST /apps/filinq/api/contracts/{id}/suggestions` | Read the documents for suggested terms |
| `PUT /apps/filinq/api/contracts/{id}/suggestions/{index}` | `decision`: `accepted` or `rejected` |
| `POST /apps/filinq/api/contracts/{id}/signing` | Record a signing request (`signingRequestId`); links the signed document once it completed |
| `GET /apps/filinq/api/contracts/{id}/parties` | The parties, named from their contacts |

Each route reads the contract as you first. A contract you cannot read answers 404.
