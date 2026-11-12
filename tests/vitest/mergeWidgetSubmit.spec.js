/**
 * Golden: what the merge-to-PDF leaf sends and shows when it is submitted,
 * recorded on the implementation where submit() built, sent and read the
 * request inline.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec exclude characterization test of the merge leaf submit
 */

import { join } from 'node:path'
import { afterEach, describe, it, vi } from 'vitest'
import { golden } from './golden/golden.js'
import { createInstance, loadSfcOptions } from './sfc/sfcOptions.js'

vi.mock('@nextcloud/l10n', () => ({
	translate: (app, text, vars) => '[t] ' + text.replace(/\{(\w+)\}/g, (_, k) => String(vars?.[k] ?? '')),
}))
vi.mock('@nextcloud/router', () => ({ generateUrl: (url) => '/index.php' + url }))

const calls = []

/**
 * A leaf with three documents, two chosen.
 *
 * @param {object} state State to set on top.
 * @return {Promise<object>} The instance.
 */
async function leaf(state = {}) {
	const options = await loadSfcOptions(join(import.meta.dirname, '../../src/integrations/CnFilinqMergeToPdfWidget.vue'))
	const vm = createInstance(options, { props: { register: 'zaken', schema: 'zaak', objectId: 'z-1' } })
	vm.requestToken = () => 'token-1'
	Object.assign(vm, {
		rows: [{ id: 1, name: 'a.pdf' }, { id: 2, name: 'b.pdf' }, { id: 3, name: 'c.pdf' }],
		selection: [2, 1],
		coverTemplateRef: 'cover-1',
		bookmarks: false,
		name: 'Bundel',
		...state,
	})
	return vm
}

const ANSWERS = {
	'accepted': async () => ({ status: 202, json: async () => ({ jobId: 'j1', status: 'queued' }) }),
	'refused': async () => ({ status: 422, json: async () => ({ error: 'Too many pages' }) }),
	'not json': async () => ({ status: 500, json: async () => { throw new Error('html') } }),
	'unreachable': async () => { throw new Error('offline') },
}

afterEach(() => {
	calls.length = 0
	delete globalThis.fetch
})

describe('merge leaf submit golden', () => {
	for (const [name, answer] of Object.entries(ANSWERS)) {
		it(name, async () => {
			globalThis.fetch = async (url, init) => {
				calls.push([url, init.method, init.headers, JSON.parse(init.body)])
				return answer()
			}
			const vm = await leaf()
			await vm.submit()
			golden('mergeWidgetSubmit-' + name.replace(/\W+/g, '-'), { calls, outcome: vm.outcome, busy: vm.busy })
		})
	}

	it('does nothing while busy or with too few documents', async () => {
		globalThis.fetch = async () => {
			calls.push('fetch')
			return ANSWERS.accepted()
		}
		const busy = await leaf({ busy: true })
		await busy.submit()
		const single = await leaf({ selection: [1] })
		await single.submit()
		golden('mergeWidgetSubmit-skipped', { calls, busy: [busy.busy, busy.outcome], single: [single.busy, single.outcome] })
	})
})
