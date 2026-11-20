/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The Integriteitscontrole section: it shows the last stored run with a count
 * per kind and a link per finding, and "Nu controleren" starts a run through
 * the admin endpoint and shows that run.
 *
 * @spec openspec/changes/whole-collection-integrity-scan/tasks.md#task-3-the-admin-section-reads-and-starts-a-scan
 */

import axios from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'
import IntegrityScanSection from './IntegrityScanSection.vue'
import { renderStubbed } from '../../../tests/jest/renderGolden.js'

jest.mock('@nextcloud/axios', () => ({
	__esModule: true,
	default: { get: jest.fn(), post: jest.fn() },
}))
jest.mock('@nextcloud/dialogs', () => ({ showError: jest.fn(), showSuccess: jest.fn() }))
jest.mock('@nextcloud/l10n', () => ({
	translate: (app, text, vars = {}) => text.replace(/\{(\w+)\}/g, (match, key) => (key in vars ? String(vars[key]) : match)),
	translatePlural: (app, singular, plural, count) => (count === 1 ? singular : plural).replace('%n', String(count)),
}))
jest.mock('@nextcloud/router', () => ({ generateUrl: (url) => '/index.php' + url }))
jest.mock('@nextcloud/vue', () => ({
	NcButton: {},
	NcLoadingIcon: {},
	NcSettingsSection: {},
}))

const ENDPOINT = '/index.php/apps/filinq/api/settings/integrity-scan'
const flush = () => new Promise((resolve) => setTimeout(resolve, 0))

const TWO_MISSING = {
	trigger: 'nightly',
	startedAt: '2026-11-11T02:00:00+00:00',
	checked: 415,
	missing: 2,
	changed: 0,
	orphaned: 0,
	findings: [
		{ kind: 'missing', schema: 'dossier', record: 'd-301', name: 'Omgevingsvergunning Lindelaan 12', fileId: 9301 },
		{ kind: 'missing', schema: 'dossier', record: 'd-301', name: 'Omgevingsvergunning Lindelaan 12', fileId: 9302 },
	],
}

/**
 * Render the section after its load settles.
 *
 * @return {Promise<object>} The render.
 */
async function render() {
	const page = await renderStubbed(IntegrityScanSection)
	await flush()
	return page
}

beforeEach(() => {
	jest.clearAllMocks()
})

test('a run with two missing files shows the count and a link per finding', async () => {
	axios.get.mockResolvedValue({ data: { run: TWO_MISSING } })

	const page = await render()

	expect(axios.get).toHaveBeenCalledWith(ENDPOINT)
	expect(page.vm.counts.map((count) => count.label)).toEqual(['2 missing files', '0 changed files', '0 orphaned files'])
	expect(page.vm.findings).toHaveLength(2)
	expect(page.vm.findings.map((finding) => page.vm.recordLink(finding))).toEqual([
		'/index.php/apps/filinq/dossiers/d-301',
		'/index.php/apps/filinq/dossiers/d-301',
	])
	expect(page.html()).toContain('415 records checked')
	expect(page.html()).toContain('File 9302')
	page.unmount()
})

test('before the first run the section says so and lists nothing', async () => {
	axios.get.mockResolvedValue({ data: { run: null } })

	const page = await render()

	expect(page.vm.run).toBeNull()
	expect(page.vm.findings).toEqual([])
	expect(page.html()).toContain('No integrity scan has run yet.')
	page.unmount()
})

test('a finding of a schema without its own page links to the file', async () => {
	axios.get.mockResolvedValue({ data: { run: null } })
	const page = await render()

	expect(page.vm.recordLink({ kind: 'changed', schema: 'somethingElse', record: 'x-1', fileId: 77 })).toBe('/index.php/f/77')
	expect(page.vm.recordLink({ kind: 'changed', schema: 'documentVersion', record: 'v-1', fileId: 21 })).toBe('/index.php/apps/filinq/versions')
	page.unmount()
})

test('"Check now" posts to the endpoint and shows the run it answers', async () => {
	axios.get.mockResolvedValue({ data: { run: null } })
	axios.post.mockResolvedValue({ data: { run: { ...TWO_MISSING, trigger: 'admin' } } })
	const page = await render()

	await page.vm.start()

	expect(axios.post).toHaveBeenCalledWith(ENDPOINT)
	expect(page.vm.run.trigger).toBe('admin')
	expect(page.vm.busy).toBe(false)
	expect(showSuccess).toHaveBeenCalledWith('The integrity scan has finished')
	page.unmount()
})

test('a refused start keeps the shown run and reports the failure', async () => {
	axios.get.mockResolvedValue({ data: { run: TWO_MISSING } })
	axios.post.mockRejectedValue(new Error('403'))
	const page = await render()

	await page.vm.start()

	expect(page.vm.run).toEqual(TWO_MISSING)
	expect(showError).toHaveBeenCalledWith('The integrity scan could not run')
	expect(page.vm.busy).toBe(false)
	page.unmount()
})
