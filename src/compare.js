/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The `filinq-compare` bundle: the original/delivered split view for other
 * apps, as `OCA.Filinq.mountCompare(el, { original, delivered, labels })`.
 *
 * WHY A SEPARATE ENTRY. Another app (dossiq's delivered Woo set) loads this
 * only when its compare dialog opens. `filinq-main.js` is the whole SPA and
 * would put Filinq's shell on someone else's page.
 *
 * WHY SELF-CONTAINED. webpack.config.js keeps this entry out of the shared
 * chunks, so the host loads ONE file: it cannot know Filinq's shared chunk
 * names, and a missing chunk would leave the dialog empty without an error.
 */
import { loadTranslations } from '@nextcloud/l10n'
import { createApp } from 'vue'
import DocumentCompare from './components/compare/DocumentCompare.vue'
import { mountCompare } from './compare/mountCompare.js'

// Sets __webpack_public_path__ to Filinq's js/ folder, so the PDF viewer's
// lazy pdfjs chunk and worker load from Filinq, not from the host app. They
// are fetched on first render, after this module has run.
import './setPublicPath.js'

window.OCA = window.OCA || {}
window.OCA.Filinq = window.OCA.Filinq || {}
window.OCA.Filinq.mountCompare = (el, options) =>
	mountCompare(el, options, { createApp, component: DocumentCompare })

// A missing catalogue rejects; the view then reads in English, which is the
// lesser failure inside another app's page.
loadTranslations('filinq').catch(() => {})
