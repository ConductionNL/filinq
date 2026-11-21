/**
 * The hand-off shows a sanitized badge on a sanitized copy, and the warning on
 * a file that is not one (document-sanitization REQ-DDSAN-004).
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/document-sanitization/specs/document-sanitization/spec.md#requirement-publication-hand-off-consults-the-sanitized-signal-req-ddsan-004
 */

import PublicationsPage from './PublicationsPage.vue'
import { renderStubbed } from '../../../tests/jest/renderGolden.js'

// The record is set by the test; every request stays pending so nothing overwrites it.
jest.mock('@nextcloud/axios', () => ({ __esModule: true, default: { get: jest.fn(() => new Promise(() => {})), put: jest.fn(), post: jest.fn() } }))
jest.mock('@nextcloud/router', () => ({ generateUrl: (path) => path }))
jest.mock('@nextcloud/l10n', () => ({ translate: (app, text) => text }))
jest.mock('@nextcloud/vue', () => ({ NcButton: {}, NcEmptyContent: {}, NcLoadingIcon: {}, NcNoteCard: {}, NcTextField: {}, NcDialog: {}, NcCheckboxRadioSwitch: {}, NcSelect: {} }))
jest.mock('@conduction/nextcloud-vue', () => ({ CnStatusBadge: {}, CnDialogActions: {} }))

const RECORD = { uuid: 'r-1', status: 'ready', officieleTitel: 'Besluit', documentFileRef: '42', redactedFileRef: '43' }

/**
 * The page on one record, with the given sanitized signal.
 *
 * @param {boolean|null} sanitized The signal.
 * @return {Promise<object>} The rendered page.
 */
async function pageWith(sanitized) {
	const page = await renderStubbed(PublicationsPage, {
		globals: { $route: { params: { id: 'r-1' }, query: {} }, $router: { push: jest.fn() } },
		data: { record: RECORD, sanitized },
	})
	await page.vm.$nextTick()
	return page
}

test('a sanitized copy carries the sanitized badge and no warning', async () => {
	const page = await pageWith(true)

	expect(page.html()).toContain('publication-sanitized')
	expect(page.html()).not.toContain('publication-not-sanitized')
	const badge = page.stubs.find((stub) => stub.name === 'CnStatusBadge')
	expect(badge.attrs.label).toBe('Sanitized')
	expect(badge.attrs.colorMap).toEqual({ Sanitized: 'success' })
	page.unmount()
})

test('a file that is not sanitized carries the warning and no badge; an unknown state carries neither', async () => {
	const unsanitized = await pageWith(false)
	expect(unsanitized.html()).toContain('publication-not-sanitized')
	expect(unsanitized.html()).not.toContain('publication-sanitized"')
	unsanitized.unmount()

	const unknown = await pageWith(null)
	expect(unknown.html()).not.toContain('publication-sanitized')
	expect(unknown.html()).not.toContain('publication-not-sanitized')
	unknown.unmount()
})
