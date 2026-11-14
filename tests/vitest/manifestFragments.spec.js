/**
 * The manifest split into ADR-037 fragments and assembled by the shared
 * buildManifest (ADR-044): nothing reachable before the split is lost, and no
 * page or menu entry is declared twice.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/filinq-adopt-buildmanifest-pipeline/specs/menu-architecture/spec.md#requirement-navigation-manifest-assembled-via-shared-buildmanifest
 */

import { describe, expect, it } from 'vitest'
import { baseManifest, effectiveManifest, manifestFragments } from './support/effectiveManifest.js'

/**
 * Every page route and every menu entry of the monolithic manifest this change
 * split up, as `[id, route]` pairs, recorded before the split.
 */
const PAGES_BEFORE = [
	['Store', '/store'], ['Dashboard', '/'], ['Consent', '/consent'], ['ConsentDetail', '/consent/:id'],
	['CustomDictionary', '/custom-dictionaries'], ['CustomDictionaryDetail', '/custom-dictionaries/:id'],
	['Anonymization', '/anonymization'], ['FolderAnonymization', '/folder-anonymization'], ['Dossiers', '/dossiers'],
	['DossierDetail', '/dossiers/:id'], ['Contracts', '/contracts'], ['ContractPipeline', '/contracts/pipeline'],
	['ContractDetail', '/contracts/:id'], ['Templates', '/templates'], ['TemplateDetail', '/templates/:id'],
	['WizardRunner', '/templates/:id/wizard'], ['Correspondence', '/correspondence'], ['SigningRequests', '/signing'],
	['BulkSigningBatches', '/signing-batches'], ['SigningEnvelopes', '/signing-envelopes'], ['SigningFolder', '/signing-folder'],
	['SigningRequestForm', '/signing/new'], ['SigningRequestDetail', '/signing/:id'], ['SignatureVerification', '/signing/verify/:fileId'],
	['MyDocuments', '/my-documents'], ['Publications', '/publications/:id?'], ['PrintJobs', '/print-jobs'],
	['LegalHolds', '/legal-holds'], ['SubjectErasures', '/subject-erasures'], ['EmailIngestion', '/email-ingestion'],
	['EntitySearch', '/entity-search'], ['PrintPreview', '/print-preview/:templateId?'], ['Comparison', '/comparison'],
	['Versions', '/versions'], ['ComponentGallery', '/gallery'], ['Prohibitions', '/policy/prohibitions'],
	['StandingConsents', '/policy/standing-consents'], ['Reports', '/reports'], ['SigningReport', '/reports/signing'],
	['AnonymizationReport', '/reports/anonymization'], ['ProducedDocumentsReport', '/reports/documents'],
	['Intake', '/intake'], ['ClassificationPending', '/classification'], ['FeaturesRoadmap', '/features-roadmap'],
	['Flows', '/flows'], ['FlowDetail', '/flows/:id'],
]

const MENU_BEFORE = [
	['Dashboard', 10], ['Consent', 20], ['StandingConsents', 22], ['Prohibitions', 24], ['Intake', 26],
	['ClassificationPending', 27], ['Anonymization', 30], ['MyDocuments', 35], ['FolderAnonymization', 40],
	['Dossiers', 45], ['Contracts', 46], ['ContractPipeline', 47], ['Templates', 50], ['Correspondence', 55],
	['Publications', 57], ['PrintJobs', 56], ['LegalHolds', 58], ['SubjectErasures', 59], ['EmailIngestion', 94],
	['EntitySearch', 59], ['SigningRequests', 60], ['SigningFolder', 65], ['BulkSigningBatches', 66],
	['SigningEnvelopes', 67], ['CustomDictionary', 70], ['Documentation', 90], ['StoreMenu', 92],
	['ReportsMenu', 95], ['FeaturesRoadmapMenu', 100], ['FlowsMenu', 96],
]

/**
 * Sort `[id, x]` pairs by id.
 *
 * @param {Array<Array>} pairs The pairs.
 * @return {Array<Array>} A sorted copy.
 */
const byId = (pairs) => [...pairs].sort((a, b) => a[0].localeCompare(b[0]))

describe('the manifest fragments', () => {
	it('lose no page route of the monolithic manifest and add none', () => {
		const pages = effectiveManifest().pages.map((p) => [p.id, p.route])
		expect(byId(pages)).toEqual(byId(PAGES_BEFORE))
	})

	it('lose no menu entry and keep each one\'s order', () => {
		const menu = effectiveManifest().menu.map((m) => [m.id, m.order])
		expect(byId(menu)).toEqual(byId(MENU_BEFORE))
	})

	it('point every menu route at a page that exists', () => {
		const manifest = effectiveManifest()
		const ids = new Set(manifest.pages.map((p) => p.id))
		const routed = manifest.menu.filter((m) => m.route !== undefined)
		expect(routed.length).toBeGreaterThan(0)
		expect(routed.filter((m) => !ids.has(m.route)).map((m) => m.id)).toEqual([])
	})

	it('declare each page and menu entry exactly once across the base and the fragments', () => {
		const sources = [{ file: 'manifest.json', content: baseManifest() }, ...manifestFragments()]
		for (const key of ['pages', 'menu']) {
			const seen = {}
			for (const { file, content } of sources) {
				for (const entry of content[key] || []) {
					seen[entry.id] = [...(seen[entry.id] || []), file]
				}
			}
			expect(Object.entries(seen).filter(([, files]) => files.length > 1)).toEqual([])
		}
	})

	it('carry only pages and menu entries, leaving every other key to the base', () => {
		for (const { file, content } of manifestFragments()) {
			expect([file, Object.keys(content).sort()]).toEqual([file, ['menu', 'pages']])
		}
		expect(manifestFragments().length).toBeGreaterThan(1)
	})
})
