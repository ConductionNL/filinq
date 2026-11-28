# File emails into a dossier

Save an email as an `.eml` file in a watched inbox folder and Filinq files it into the dossier that folder belongs to. It puts a PDF copy beside the email and keeps one record per email with the sender, recipients, subject and thread headers. This is how mail that has to be archived under the Woo and the Archiefwet ends up in the dossier, without anyone moving files by hand.

Filinq does not connect to a mailbox. Fetching mail over IMAP or Microsoft 365 belongs to Integriq, which writes the `.eml` files into the inbox folder. Filinq has no setting for a mail server, account or password, on purpose.

## Map an inbox to a dossier

1. Open the filinq admin settings and go to **Email ingestion**.
2. Click **Add an inbox**, enter the folder ID of the inbox and the ID of the dossier its mail belongs to.
3. Set **Emails per run**. The default is 25. Larger drops are worked through over the next runs.
4. Click **Save email ingestion settings**.

The background job checks the inboxes every five minutes.

## What happens to a dropped email

| Step | Result |
|---|---|
| Filed | The `.eml` moves out of the inbox into the dossier folder and gets a record |
| Converted | A PDF/A copy is made with the conversion the rest of Filinq uses, and saved beside the email |
| Dropped again | The same email for the same dossier is recognised by its content hash and its Message-ID. The inbox copy is removed and no second record is made |
| Reply | The `In-Reply-To` and `References` headers are kept, with a thread key, so the status page can show emails that belong together |

Filing never waits for the PDF. When the conversion is down, the email is still filed and the record says **Filed, not converted**. Click **Convert again** on the status page once the conversion works.

## When an email cannot be filed

The file stays in the inbox and the status page shows it as **Failed**, with the reason and what to do:

| Reason | What to do |
|---|---|
| Outlook `.msg` file | Save the email as `.eml` and put it in the inbox again |
| Not readable as an email | Export it again as `.eml` |
| The dossier has no folder that can be reached | Check the inbox mapping |
| The email could not be moved | Check the permissions of the dossier folder |

A failed record holds the reason, never the content of the email.

## The status page

Admins open **Email ingestion** in the menu. It lists every filed and failed email, with filters on status and dossier. **Scan inboxes now** runs the job at once instead of waiting for the next run.

## API

All routes are for admins only.

| Route | What it does |
|---|---|
| `GET /apps/filinq/api/email-ingestion` | The email records, filtered by `status` and `dossier` |
| `POST /apps/filinq/api/email-ingestion/scan` | Scan the inboxes now; answers how many were filed, failed or duplicates |
| `POST /apps/filinq/api/email-ingestion/{uuid}/convert` | Make the PDF copy of a filed email again |
| `GET`/`PUT /apps/filinq/api/email-ingestion/settings` | The inbox mapping and the emails per run |

Next: map your first inbox in the admin settings and drop a test `.eml` in it.
