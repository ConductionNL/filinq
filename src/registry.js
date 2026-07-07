/**
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 *
 * V2 component registry for docudesk (hydra ADR-036).
 *
 * Keys must match the `component` strings used in the manifest's
 * `type: "custom"` pages and the `widgetKey` strings used in the
 * manifest's top-level `widgets[]` arrays. Each entry is
 * `{ kind, component }`.
 *
 * Recognised kinds: page, modal, widget, form-field, cell-renderer.
 *
 * Resolution paths at runtime:
 *   - `pages[].type === 'custom'` → CnPageRenderer resolves
 *     `page.component` against `customComponents` (derived from the
 *     `kind:"page"` entries of this registry in src/main.js).
 *   - `pages[].widgets[].widgetKey` (v2 uniform widget array) →
 *     CnWidgetGrid resolves against the `registry` prop and mounts the
 *     matching `kind:"widget"` entry's component.
 *   - Future `kind:"modal"` entries are looked up directly from the
 *     `registry` prop by CnAppRoot.
 *
 * Dashboard note: the Dashboard page is declared `type:"custom"` in the
 * manifest with `component:"DashboardIndex"`. `DashboardIndex` is a
 * self-contained view that renders exactly one `CnDashboardPage` (KPI
 * cards + recent-activity list + quick-anonymization panel), so it is
 * registered as `kind:"page"`. It was previously a `kind:"widget"` body
 * of a `type:"dashboard"` page, which nested CnDashboardPage inside
 * CnDashboardPage (the hydra dashboard-antipattern) — fixed here.
 *
 * Every other page entry stays `kind:"page"` because docudesk's domain
 * pages fall back to bespoke views — consent, anonymization, templates
 * and signing have hybrid data paths (docudesk PHP controllers + custom
 * sidebars) that the library's built-in index/detail page-types do not
 * cover yet. See each page's `_note` in src/manifest.json for the
 * specific reason a `type:"custom"` entry stayed in place.
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
import MyDocumentsIndex from './views/myDocuments/MyDocumentsIndex.vue'
import PrintPreview from './components/PrintPreview.vue'
import ComparisonView from './views/comparison/ComparisonView.vue'
import VersionsView from './views/versions/VersionsView.vue'

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
	MyDocumentsIndex: { kind: 'page', component: MyDocumentsIndex },
	PrintPreview: { kind: 'page', component: PrintPreview },
	ComparisonView: { kind: 'page', component: ComparisonView },
	VersionsView: { kind: 'page', component: VersionsView },
}
