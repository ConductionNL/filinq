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
 * Pre-existing fix (openspec/changes/orphaned-surface-restoration): the
 * `TemplateIndex` and `SigningRequestList` registry entries used to point
 * at components superseded by the Phase-8 `type:"index"` decomposition
 * (see the `Templates` / `SigningRequests` `_note` fields in
 * src/manifest.json) — registered here but referenced by NO manifest
 * page, contradicting this file's own "keys must match a manifest
 * `component` string" contract. Removed. `TemplateIndex.vue` and
 * `SigningRequestList.vue` stay on disk (not deleted — see the
 * reachability guard's `KNOWN_HEADLESS` allow-list in
 * tests/unit/reachability.spec.js for why) but are no longer registered.
 *
 * Restoration additions (openspec/changes/orphaned-surface-restoration):
 * `CorrespondenceIndex`, `SigningRequestForm`, `SignatureVerification`,
 * `ProhibitionIndex`, `StandingConsentIndex` (kind:"page") and
 * `ProhibitionFormModal`, `StandingConsentFormModal` (kind:"modal") were
 * built (backends live at HEAD) but never registered, so they 404'd
 * through the manifest router while their only wiring was the dead
 * `src/router/index.js` (also removed by this change). See
 * openspec/changes/orphaned-surface-restoration/design.md D3–D5.
 *
 * @type {Record<string, { kind: string, component: object }>}
 */

import DashboardIndex from './views/dashboard/DashboardIndex.vue'
import ConsentIndex from './views/consent/ConsentIndex.vue'
import ConsentDetail from './views/consent/ConsentDetail.vue'
import AnonymizationIndex from './views/anonymization/AnonymizationIndex.vue'
import FolderAnonymizationView from './views/anonymization/FolderAnonymizationView.vue'
import TemplateDetail from './views/templates/TemplateDetail.vue'
import SigningRequestDetail from './views/signing/SigningRequestDetail.vue'
import SigningRequestForm from './views/signing/SigningRequestForm.vue'
import SignatureVerification from './views/signing/SignatureVerification.vue'
import MyDocumentsIndex from './views/myDocuments/MyDocumentsIndex.vue'
import PrintPreview from './components/PrintPreview.vue'
import ComparisonView from './views/comparison/ComparisonView.vue'
import VersionsView from './views/versions/VersionsView.vue'
import ComponentGallery from './views/gallery/ComponentGallery.vue'
import CorrespondenceIndex from './views/correspondence/CorrespondenceIndex.vue'
import ProhibitionIndex from './views/policy/ProhibitionIndex.vue'
import ProhibitionFormModal from './dialogs/ProhibitionFormModal.vue'
import StandingConsentIndex from './views/policy/StandingConsentIndex.vue'
import StandingConsentFormModal from './dialogs/StandingConsentFormModal.vue'
import CustomDictionaryIndex from './views/customDictionary/CustomDictionaryIndex.vue'
import CustomDictionaryDetail from './views/customDictionary/CustomDictionaryDetail.vue'

export default {
	DashboardIndex: { kind: 'page', component: DashboardIndex },
	ConsentIndex: { kind: 'page', component: ConsentIndex },
	ConsentDetail: { kind: 'page', component: ConsentDetail },
	AnonymizationIndex: { kind: 'page', component: AnonymizationIndex },
	FolderAnonymizationView: { kind: 'page', component: FolderAnonymizationView },
	TemplateDetail: { kind: 'page', component: TemplateDetail },
	SigningRequestDetail: { kind: 'page', component: SigningRequestDetail },
	SigningRequestForm: { kind: 'page', component: SigningRequestForm },
	SignatureVerification: { kind: 'page', component: SignatureVerification },
	MyDocumentsIndex: { kind: 'page', component: MyDocumentsIndex },
	PrintPreview: { kind: 'page', component: PrintPreview },
	ComparisonView: { kind: 'page', component: ComparisonView },
	VersionsView: { kind: 'page', component: VersionsView },
	ComponentGallery: { kind: 'page', component: ComponentGallery },
	CorrespondenceIndex: { kind: 'page', component: CorrespondenceIndex },
	ProhibitionIndex: { kind: 'page', component: ProhibitionIndex },
	ProhibitionFormModal: { kind: 'modal', component: ProhibitionFormModal },
	StandingConsentIndex: { kind: 'page', component: StandingConsentIndex },
	StandingConsentFormModal: { kind: 'modal', component: StandingConsentFormModal },
	CustomDictionaryIndex: { kind: 'page', component: CustomDictionaryIndex },
	CustomDictionaryDetail: { kind: 'page', component: CustomDictionaryDetail },
}
