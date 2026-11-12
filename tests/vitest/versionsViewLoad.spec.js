/**
 * Golden: what the versions panel shows after loading, for each answer the
 * version list can give, recorded on the implementation where load() read
 * the failure inline.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec exclude characterization test of the versions panel load
 */

import { join } from 'node:path'
import { describe, it, vi } from 'vitest'
import { golden } from './golden/golden.js'
import { createInstance, loadSfcOptions } from './sfc/sfcOptions.js'

const world = vi.hoisted(() => ({ calls: [], answer: null, final: null }))

vi.mock('@nextcloud/l10n', () => ({ translate: (app, text) => '[t] ' + text }))
vi.mock('../../src/services/versionService.js', () => ({
	restoreVersion: async () => ({}),
	versionDownloadUrl: () => '',
	listVersions: async (fileId) => {
		world.calls.push(['listVersions', fileId])
		if (world.answer instanceof Error || world.answer === null || world.answer?.thrown) {
			throw world.answer?.thrown ?? world.answer
		}
		return world.answer
	},
}))
vi.mock('../../src/services/finalDocumentService.js', () => ({
	correctDocument: async () => ({}),
	finaliseDocument: async () => ({}),
	unfreezeDocument: async () => ({}),
	readFinalState: async (fileId) => {
		world.calls.push(['readFinalState', fileId])
		if (world.final === 'fail') {
			throw new Error('no record')
		}
		return world.final
	},
}))

const CASES = {
	'a list': [{ versions: [{ id: 1 }, { id: 2 }] }, { final: true, version: 2 }],
	'no versions key': [{}, 'fail'],
	'versions unavailable': [{ thrown: Object.assign(new Error('x'), { response: { data: { reason: 'versions-unavailable' } } }) }, null],
	'an error message': [{ thrown: Object.assign(new Error('x'), { response: { data: { error: 'Denied' } } }) }, null],
	'a reason and an error': [{ thrown: Object.assign(new Error('x'), { response: { data: { reason: 'other', error: 'Other' } } }) }, null],
	'an empty error': [{ thrown: Object.assign(new Error('x'), { response: { data: { error: '' } } }) }, null],
	'no data': [{ thrown: Object.assign(new Error('x'), { response: {} }) }, null],
	'no response': [new Error('network'), null],
	'a thrown null': [null, null],
}

describe('versions panel load golden', () => {
	for (const [name, [answer, final]] of Object.entries(CASES)) {
		it(name, async () => {
			world.calls = []
			world.answer = answer
			world.final = final
			const options = await loadSfcOptions(
				join(import.meta.dirname, '../../src/views/versions/VersionsView.vue'),
			)
			const vm = createInstance(options, { props: { initialFileId: 7 } })
			vm.error = 'stale'
			vm.unavailable = true
			await vm.load()
			golden('versionsViewLoad-' + name.replace(/\W+/g, '-'), {
				calls: world.calls,
				loading: vm.loading,
				error: vm.error,
				unavailable: vm.unavailable,
				versions: vm.versions,
				finalState: vm.finalState,
			})
		})
	}
})
