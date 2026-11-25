/**
 * Every file picker filinq opens has a button to confirm the choice.
 *
 * @nextcloud/dialogs 7 builds a picker without buttons unless one is added,
 * and such a picker cannot be confirmed: on a live instance the mail archive
 * import could not pick its archive. The pickers now add one primary button,
 * disabled until something is selected.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @vitest-environment jsdom
 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#4-1
 */

import { join } from 'node:path'
import { describe, expect, it, vi } from 'vitest'
import { createInstance, loadSfcOptions } from './sfc/sfcOptions.js'

const built = []

/** @return {object} A chainable picker builder double that records what it was given. */
function builder() {
	const state = { factory: null }
	const chain = {
		setMultiSelect: () => chain,
		allowDirectories: () => chain,
		setMimeTypeFilter: () => chain,
		setButtonFactory: (factory) => {
			state.factory = factory
			return chain
		},
		addButton: (button) => {
			state.factory = () => [button]
			return chain
		},
		build: () => ({ pickNodes: async () => [] }),
	}
	built.push(state)
	return chain
}

vi.mock('@nextcloud/dialogs', () => ({
	getFilePickerBuilder: () => builder(),
	showError: () => {},
	showSuccess: () => {},
}))
vi.mock('@nextcloud/l10n', () => ({ translate: (app, text) => text, translatePlural: (a, one) => one }))
vi.mock('@nextcloud/router', () => ({ generateUrl: (url) => url }))
vi.mock('@nextcloud/axios', () => ({ default: { get: async () => ({ data: {} }), post: async () => ({ data: {} }) } }))
vi.mock('@nextcloud/vue', () => ({}))

describe('file pickers can be confirmed', () => {
	for (const [component, method] of [
		['src/views/mailArchiveImport/MailArchiveImport.vue', 'pickArchive'],
		['src/views/contracts/ContractDetail.vue', 'attach'],
	]) {
		it(`${component} adds a primary button that waits for a selection`, async () => {
			const options = await loadSfcOptions(join(__dirname, '../..', component))
			const vm = createInstance(options, { props: { id: 'c1' } })
			built.length = 0

			await vm[method]()

			expect(built).toHaveLength(1)
			const factory = built[0].factory
			expect(typeof factory).toBe('function')
			const [none] = factory([], '/', 'files')
			const [some] = factory([{ fileid: 7 }], '/', 'files')
			expect(none.variant).toBe('primary')
			expect(none.disabled).toBe(true)
			expect(some.disabled).toBe(false)
		})
	}
})
