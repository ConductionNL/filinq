# Print jobs

A handler who sends letters by post wants them printed as one stack, and
wants to know when the printer has them. Filinq sends a selection of letters
to print as one job and lists your jobs with their status.

## Send letters to print

1. Open **Letters & correspondence**.
2. Fill in the template, then either the data references of one letter or,
   under **Batch (multiple recipients)**, the register, the schema and one
   recipient id per line.
3. Choose **Send to print**. However many letters you selected, they go as
   one job.

A note confirms the job and links to **Print jobs**.

## Follow a job

**Print jobs** lists the jobs you sent, newest first. Each row shows:

- when you sent it and the file name
- how many letters have a PDF, out of how many in the job
- the status and when it last changed: making the PDFs, waiting for the
  printer, at the printer, printed or failed
- what the print service said with its last status, if anything
- a download once at least one PDF is ready: the PDF for a job of one letter,
  a ZIP with every PDF and a manifest otherwise

The page reads the list again once a minute. Only your own jobs are listed;
an admin opens someone else's job by its id.

## How a print service reports back

A print service signs in as the job's owner or as an admin. It reads a job
with `GET /apps/filinq/api/print/jobs/{id}`,
downloads it from `/apps/filinq/api/print/jobs/{id}/download` and reports
with `PUT /apps/filinq/api/print/jobs/{id}/status` and a body
`{"status": "printing" | "sent" | "printed" | "failed", "details": "..."}`.
`printing` and `sent` both show as at the printer.

## Where the job is kept

A job is a `printJob` object in the Filinq register, with its status,
requester, letter count and file references. The PDFs are files in the app
data folder `print-jobs`, never in app configuration. When the app is
upgraded, a repair step moves any job still kept in app configuration by an
older version into this shape and removes the old entry.
