## Why

Filinq has two anonymisation upload widgets that do the same job differently.

- `AnonymizationWidget` sits on the Anonymisation page. It accepts Word (.docx), ODT, PDF, TXT and email (.eml) files. Every upload opens the upload dialog, where the user names a dossier (for several files) and chooses whether to show the grondslagen. After the upload the first file opens in the file viewer on My documents.
- `AnonymizationDashboardWidget` sits on the Filinq dashboard and on the Nextcloud dashboard. It accepts only .docx, PDF and TXT. One file uploads at once, without a dialog. Several files open a smaller dossier-name dialog. After the upload it goes to My documents without opening the file, and outside the app it stays put and shows a progress table.

So a user who drops an ODT or an email on the dashboard gets "not supported", while the same file works on the Anonymisation page. The same drop asks different questions and lands in different places. Ruben decided on 3 Nov 2026 that these become one widget.

## What changes

- One anonymisation upload widget, `AnonymizationWidget`, on the Anonymisation page, the Filinq dashboard and the Nextcloud dashboard. `AnonymizationDashboardWidget` is removed.
- It accepts the union of both lists: .docx, .odt, .pdf, .txt and .eml, by MIME type or by extension.
- Every upload goes through the one upload dialog (`AnonymizationUploadDialog`): one file shows its name and the grondslagen choice, several files ask for a dossier name and the grondslagen choice. `DossierNameDialog` has no other user and is removed.
- One routing: after the upload, the first file opens in the file viewer and the user is on My documents. On the Nextcloud dashboard the app's router does not exist, so the browser opens Filinq's My documents page there.
- Outside the app the widget shows an "Open Filinq" link, as the dashboard tile did.
- The progress table of the old dashboard widget goes. Every upload now leaves the widget for My documents, where progress and results are shown.
- The "Recent documents" list shows on the Anonymisation page only. The Filinq dashboard cell and the Nextcloud dashboard tile have a fixed height, so the widget leaves it out there.
- A failed upload keeps the upload dialog open and shows the file's error.
- The drop zone uses Nextcloud's own colour variables, so it follows the dark theme in the Nextcloud dashboard tile too.

## What stays

- The Nextcloud dashboard widget id (`docudesk-anonymization`), title and order. Users keep the tile on their dashboard.
- The upload store calls (`addFiles`, `addFilesAsDossier`, `bindDossier`) and the user-scoped upload folder.
- The keyboard-operable file picker button (upload-dropzone-keyboard-access).

## Capabilities

### Modified capabilities

- `anonymization`: one upload widget, its accepted file types, dialog and routing.
- `dashboard`: the quick anonymisation section and the Nextcloud dashboard tile render that widget; an upload leaves the dashboard for My documents.

## Impact

- Frontend: `src/views/anonymization/AnonymizationWidget.vue` (context-aware routing, footer link), `src/views/dashboard/DashboardIndex.vue` and `src/dashboard.js` (render it), `src/views/widgets/AnonymizationDashboardWidget.vue` and `src/dialogs/DossierNameDialog.vue` (removed).
- No backend, API or register change.
- Users of the dashboard tile see a dialog before every upload, can upload ODT and email files, and land on My documents with the first file open.
