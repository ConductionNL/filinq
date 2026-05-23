/**
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 *
 * V2 component registry for docudesk (hydra ADR-036).
 *
 * Keys must match the `component` strings used in the manifest's
 * `type: "custom"` pages (and any future sidebar tabs / modals that
 * resolve by name). Each entry is `{ kind, component }`.
 *
 * Recognised kinds: page, modal, widget, form-field, cell-renderer.
 * Every entry below is `kind: "page"` because docudesk's domain pages
 * fall back to bespoke views — consent, anonymization, templates and
 * signing have hybrid data paths (docudesk PHP controllers + custom
 * sidebars) that the library's built-in index/detail page-types do
 * not cover yet. See each page's `_note` in src/manifest.json for the
 * specific reason a `type:"custom"` entry stayed in place.
 *
 * Page resolution path at runtime:
 *   - `pages[].type === 'custom'` → CnPageRenderer resolves
 *     `page.component` against `customComponents` (derived from the
 *     `kind:"page"` entries of this registry in src/main.js).
 *   - Future `kind:"modal"` / `kind:"widget"` entries are looked up
 *     directly from the `registry` prop by CnAppRoot / CnPageRenderer.
 *
 * @type {Record<string, { kind: string, component: object }>}
 */

import DashboardIndex from './views/dashboard/DashboardIndex.vue'
import ConsentIndex from './views/consent/ConsentIndex.vue'
import ConsentDetail from './views/consent/ConsentDetail.vue'
import AnonymizationIndex from './views/anonymization/AnonymizationIndex.vue'
import FolderAnonymizationView from './views/anonymization/FolderAnonymizationView.vue'
import TemplateIndex from './views/templates/TemplateIndex.vue'
import TemplateDetail from './views/templates/TemplateDetail.vue'
import SigningRequestList from './views/signing/SigningRequestList.vue'
import SigningRequestDetail from './views/signing/SigningRequestDetail.vue'
import PrintPreview from './components/PrintPreview.vue'

export default {
	DashboardIndex: { kind: 'page', component: DashboardIndex },
	ConsentIndex: { kind: 'page', component: ConsentIndex },
	ConsentDetail: { kind: 'page', component: ConsentDetail },
	AnonymizationIndex: { kind: 'page', component: AnonymizationIndex },
	FolderAnonymizationView: { kind: 'page', component: FolderAnonymizationView },
	TemplateIndex: { kind: 'page', component: TemplateIndex },
	TemplateDetail: { kind: 'page', component: TemplateDetail },
	SigningRequestList: { kind: 'page', component: SigningRequestList },
	SigningRequestDetail: { kind: 'page', component: SigningRequestDetail },
	PrintPreview: { kind: 'page', component: PrintPreview },
}
