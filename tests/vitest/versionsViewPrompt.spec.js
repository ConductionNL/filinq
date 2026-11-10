/**
 * Golden: the versions panel's reason dialog — the copy per action and what
 * submitting it calls, emits and shows — recorded on the implementation
 * where both were if/else chains.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec exclude characterization test of the versions panel reason dialog
 */

import { join } from 'node:path'
import { describe, it, vi } from 'vitest'
import { golden } from './golden/golden.js'
import { createInstance, loadSfcOptions } from './sfc/sfcOptions.js'

const world = vi.hoisted(() => ({ calls: [], failing: '' }))

/**
 * A recording service call that fails when the world says so.
 *
 * @param {string} name The call.
 * @return {Function} The double.
 */
function recorder(name) {
	return async (...args) => {
		world.calls.push([name, ...args])
		if (world.failing === name) {
			throw Object.assign(new Error('x'), { response: { data: { error: name + ' refused' } } })
		}
		if (world.failing === name + '-bare') {
			throw new Error('x')
		}
		return name === 'listVersions' ? { versions: [] } : { id: name + '-result' }
	}
}

vi.mock('@nextcloud/l10n', () => ({ translate: (app, text) => '[t] ' + text }))
vi.mock('../../src/services/versionService.js', () => ({
	restoreVersion: async () => ({}),
	versionDownloadUrl: () => '',
	listVersions: (...args) => recorder('listVersions')(...args),
}))
vi.mock('../../src/services/finalDocumentService.js', () => ({
	correctDocument: (...args) => recorder('correctDocument')(...args),
	finaliseDocument: (...args) => recorder('finaliseDocument')(...args),
	unfreezeDocument: (...args) => recorder('unfreezeDocument')(...args),
	readFinalState: (...args) => recorder('readFinalState')(...args),
}))

/**
 * A versions panel on file 7.
 *
 * @return {Promise<object>} The instance.
 */
async function panel() {
	const options = await loadSfcOptions(
		join(import.meta.dirname, '../../src/views/versions/VersionsView.vue'),
	)
	return createInstance(options, { props: { initialFileId: 7 } })
}

describe('versions panel reason dialog golden', () => {
	it('the copy per dialog', async () => {
		const vm = await panel()
		const copy = {}
		for (const prompt of ['finalise', 'correct', 'unfreeze', '', 'constructor']) {
			vm.prompt = prompt
			copy[prompt || '(none)'] = vm.promptCopy
		}
		golden('versionsViewPrompt-copy', copy)
	})

	const SUBMITS = {
		finalise: ['finalise', ''],
		correct: ['correct', ''],
		unfreeze: ['unfreeze', ''],
		'no dialog': ['', ''],
		'an unknown dialog': ['constructor', ''],
		'finalise refused': ['finalise', 'finaliseDocument'],
		'correct refused without a message': ['correct', 'correctDocument-bare'],
		'the reload fails': ['unfreeze', 'listVersions'],
	}
	for (const [name, [prompt, failing]] of Object.entries(SUBMITS)) {
		it('submit: ' + name, async () => {
			world.calls = []
			world.failing = failing
			const vm = await panel()
			vm.prompt = prompt
			vm.error = 'stale'
			await vm.submitPrompt('because')
			golden('versionsViewPrompt-submit-' + name.replace(/\W+/g, '-'), {
				calls: world.calls,
				emitted: vm.$emitted,
				prompt: vm.prompt,
				error: vm.error,
			})
		})
	}
})
