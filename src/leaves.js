/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The `filinq-leaves` bundle: this app's OpenRegister leaves, and nothing else.
 *
 * WHERE THIS RUNS. Not on Filinq's own pages, where `main.js` registers the
 * leaf already. This entry is what OpenRegister's `LeafScriptListener` enqueues
 * on OTHER apps' pages, so a case page in another app can show the documents
 * Filinq holds for that case without reading Filinq's register itself.
 *
 * WHY IT EXISTS AT ALL. Without it the server half of the leaf reaches every
 * consumer, the parity gate compares the two halves and passes, and the surface
 * renders NOTHING: the listener looks for `js/filinq-leaves.js`, finds nothing,
 * and skips the app in silence, because enqueuing a script that does not exist
 * would 404 inside someone else's page. That is the failure this file prevents,
 * and it is invisible from either half on its own.
 *
 * WHY IT IS A SEPARATE ENTRY. `filinq-main.js` carries the whole SPA. Loading
 * that on another app's page would trade one feature for a performance
 * regression on every page of every consuming app.
 *
 * KEEP IT THIN. Anything imported here lands on other apps' pages: the leaf
 * registrations and nothing else. No router, no pinia, no app shell, no
 * `./manifest.json`, no component library.
 */
import { loadTranslations } from '@nextcloud/l10n'
import { registerDocumentsLeaf } from './integrations/registerDocumentsLeaf.js'

// Register FIRST, translate second.
//
// The registry entry must exist before the host looks for it, and the host may
// look during the same tick. `loadTranslations` is a fetch, so awaiting it
// before registering would lose that race on a cold cache and the leaf would
// simply not be in the registry when the page asked — nothing rendered, nothing
// logged. The labels fall back to their English source until the catalogue
// lands, which is the lesser failure and a visible one.
registerDocumentsLeaf()

// `loadTranslations` REJECTS on a 404, which is any locale for which
// l10n/<lang>.json was never generated, so an unguarded call here would raise an
// unhandled rejection inside a page that does not belong to this app.
loadTranslations('filinq').catch(() => {
	// Deliberately silent: this bundle runs inside another app's page, where a
	// warning about Filinq's catalogue is noise the reader cannot act on. The
	// leaf still renders, in English.
})
