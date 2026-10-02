# Classify documents that come in

When a document arrives in the intake, Filinq suggests what kind of document it is, who it is from and which dossier it belongs to. A person confirms, corrects or rejects each suggestion. Nothing is written onto the document and no file moves until somebody confirms.

That is the point of the feature. A classifier that files documents on its own makes mistakes nobody sees. A suggestion that waits for a person makes the same mistakes visible, and every correction is kept beside the suggestion, so you can see how often it was right.

## What gets suggested

| Suggestion | Where it comes from |
|---|---|
| Document type | Words and the shape of the text: an IBAN, an amount and "factuurnummer" make an invoice, "besluit" and "bezwaar" make a decision. The types are letter, decision, invoice, report, contract, form and other. Below the threshold the suggestion is **Other** with a low confidence, never a guess. |
| Sender | The organisations and people OpenRegister detected in the document. A name in the letterhead outranks a name that occurs in the body more often. While detection has not run yet, the page says **Sender not known yet** and the suggestion is replaced once it has. A name is never made up. |
| Dossier | Only an exact match of the sender or a case reference with a dossier name. No match means no dossier. |

Only intake documents are classified. Other records with a file, such as a conformance report, are left alone.

## Decide on the suggestions

1. Open **Classification** in the menu. You see the suggestions on files you can open yourself.
2. Check the type, its confidence, the sender and the dossier.
3. Click **Confirm** to accept it as it is. The type and the sender are written onto the document, and when a dossier was suggested the file moves into the dossier's folder.
4. Click **Correct** to change the type, the sender or the dossier first, then **Confirm**. The record keeps what was suggested and what you chose.
5. Click **Reject** when the suggestion is wrong and you do not want to fix it. The document stays as it is.

**Confirm all shown** confirms every suggestion on the page as suggested. Use it after you have read the list, not instead of reading it.

The same choices are on the document itself: open a document in the file viewer and the **Classification** card in the sidebar shows its suggestion, or the decision once somebody took it.

## When the file does not move

Confirming with a dossier moves the file only into a dossier folder you can open yourself. When you cannot, the dossier is still recorded on the document and the card says the file was not moved. Ask somebody with access to that folder to move it.

## Switch it off

Classification has its own setting in the filinq admin settings: **Inbound classification**. It is on by default. Switched off, no new suggestions are made. Existing suggestions stay, and you can still decide on them.

## Privacy and retention

A suggestion stores the file id, the suggested and confirmed type, at most one sender name and a dossier reference. It never stores document text or the other names in the document. The suggestions are kept for one year. Through the OpenRegister API only admins can read them; everybody else reaches a suggestion through the classification page, for a file they can open.

## Not yet

The classifier does not learn from corrections. The corrections are stored so that a later version can, after a documented risk assessment.

## Next step

Open **Classification** in the menu and confirm or correct the first suggestion. Then check the document's sidebar to see what was written.
