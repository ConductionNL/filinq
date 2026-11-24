/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The Share by link dialog: it asks the preflight on open, keeps the share
 * button disabled for a labelled document until the clerk confirms, sends
 * one share, shows the link, and keeps Nextcloud's refusal in the dialog.
 *
 * @spec openspec/changes/share-document-links/tasks.md#task-3.2
 */

const { nextTick } = require('vue')

jest.mock('@nextcloud/l10n', () => ({
	translate: (app, text) => text,
	translatePlural: (app, one, many, count) => (count === 1 ? one : many),
}))
jest.mock('@nextcloud/router', () => ({ generateUrl: (url) => url }))
jest.mock('@nextcloud/axios', () => ({ post: jest.fn(), get: jest.fn(), delete: jest.fn() }))
jest.mock('@nextcloud/vue', () => ({
	NcButton: {},
	NcCheckboxRadioSwitch: {},
	NcLoadingIcon: {},
	NcModal: {},
	NcNoteCard: {},
	NcTextField: {},
}))

const axios = require('@nextcloud/axios')
const { renderStubbed } = require('../../tests/jest/renderGolden.js')
const ShareByLinkDialog = require('./ShareByLinkDialog.vue').default

let rendered

/**
 * Let pending promises and renders settle.
 *
 * @return {Promise<void>}
 */
async function settle() {
	for (let i = 0; i < 6; i++) {
		await Promise.resolve()
		await nextTick()
	}
}

/**
 * Open the dialog for some documents with a given preflight answer.
 *
 * @param {number[]} documents The file ids.
 * @param {object} preflight The preflight answer.
 * @return {Promise<object>} The rendered dialog.
 */
async function open(documents, preflight) {
	axios.post.mockResolvedValueOnce({ data: preflight })
	rendered = await renderStubbed(ShareByLinkDialog, { props: { show: true, documents } })
	await settle()
	return rendered
}

/**
 * The share button's attrs.
 *
 * @return {object} The attrs.
 */
function shareButton() {
	const buttons = rendered.stubs.filter((s) => s.name === 'NcButton')
	return buttons.filter((s) => s.attrs['data-testid'] === 'share-by-link-submit').pop().attrs
}

const preflight = {
	maxDays: null,
	passwordRequired: false,
	defaultExpiresAt: '2026-11-28',
	labels: [],
	bundle: null,
}

beforeEach(() => {
	axios.post.mockReset()
})

afterEach(() => {
	rendered?.unmount()
	rendered = null
})

describe('ShareByLinkDialog', () => {
	it('asks the preflight on open and takes its default expiry', async () => {
		await open([42], preflight)

		expect(axios.post).toHaveBeenCalledWith('/apps/filinq/api/shares/preflight', { fileIds: [42] })
		expect(rendered.vm.expiresOn).toBe('2026-11-28')
		expect(shareButton().disabled).toBe(false)
	})

	it('keeps the share button disabled for a labelled document until the clerk confirms', async () => {
		await open([42], { ...preflight, labels: [{ fileId: 42, label: 'Confidential' }] })

		expect(rendered.html()).toContain('share-by-link-label')
		expect(shareButton().disabled).toBe(true)

		rendered.stubs.filter((s) => s.name === 'NcCheckboxRadioSwitch').pop().attrs['onUpdate:modelValue'](true)
		await settle()

		expect(shareButton().disabled).toBe(false)
	})

	it('needs a bundle name for several documents and says when the bundle goes over the ceiling', async () => {
		await open([7, 8], {
			...preflight,
			bundle: { files: 2, bytes: 3145728, ceilingBytes: 2097152, exceedsCeiling: true, excluded: [{ name: 'b.pdf', reason: 'ceiling' }] },
		})

		expect(rendered.html()).toContain('share-by-link-ceiling')
		expect(rendered.vm.ceilingNames).toEqual(['b.pdf'])
		expect(shareButton().disabled).toBe(true)

		rendered.vm.name = 'Bezwaar 2026-031'
		await settle()
		expect(shareButton().disabled).toBe(false)
	})

	it('shares once, shows the link and tells the parent', async () => {
		await open([42], preflight)
		axios.post.mockResolvedValueOnce({
			data: { share: { uuid: 's-1' }, url: 'https://cloud.example/s/x', expiresAt: '2026-11-28', expiryShortened: false },
		})

		shareButton().onClick()
		await settle()

		expect(axios.post).toHaveBeenLastCalledWith('/apps/filinq/api/shares', {
			fileIds: [42],
			expiresOn: '2026-11-28',
			note: '',
			labelConfirmed: false,
		})
		expect(rendered.html()).toContain('https://cloud.example/s/x')
		expect(rendered.vm.result.url).toBe('https://cloud.example/s/x')
	})

	it("keeps Nextcloud's refusal in the dialog and shows no link", async () => {
		await open([42], preflight)
		axios.post.mockRejectedValueOnce({ response: { status: 403, data: { error: 'Sharing is not allowed' } } })

		shareButton().onClick()
		await settle()

		expect(rendered.vm.error).toBe('Sharing is not allowed')
		expect(rendered.vm.result).toBeNull()
		expect(rendered.html()).toContain('share-by-link-error')
	})
})
