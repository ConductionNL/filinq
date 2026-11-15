// @vitest-environment jsdom
/**
 * Golden: how every filinq leaf mounts, unmounts and registers, recorded on
 * the implementation where each leaf file carried its own copy.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec exclude characterization test of existing leaf registration
 */

import { describe, it, vi } from 'vitest'
import { golden } from './golden/golden.js'

const events = []

vi.mock('vue', () => ({
	createApp: (component, props) => {
		const id = events.filter((e) => e[0] === 'createApp').length
		events.push(['createApp', component.name, props])
		const app = {
			config: { globalProperties: {} },
			mount: (el) =>
				events.push([
					'mount',
					id,
					el.id,
					typeof app.config.globalProperties.t,
				]),
			unmount: () => events.push(['unmount', id]),
		}
		return app
	},
}))

for (const name of [
	'CnFilinqDocumentIntakeWidget',
	'CnFilinqDocumentsWidget',
	'CnFilinqDownloadAllFilesWidget',
	'CnFilinqMergeToPdfWidget',
	'CnFilinqSigningFolderWidget',
]) {
	vi.doMock(`../../src/integrations/${name}.vue`, () => ({ default: { name } }))
}

const LEAVES = [
	['registerDocumentIntakeLeaf', 'documentIntakeLeafDescriptor'],
	['registerDocumentsLeaf', 'documentsLeafDescriptor'],
	['registerDownloadAllFilesLeaf', 'downloadAllFilesLeafDescriptor'],
	['registerMergeToPdfLeaf', 'mergeToPdfLeafDescriptor'],
	['registerSigningFolderLeaf', 'signingFolderLeafDescriptor'],
]

/**
 * The descriptor with its functions replaced by their names.
 *
 * @param {object} descriptor Leaf descriptor.
 * @param {object} mod The leaf module.
 * @return {object}
 */
function describeDescriptor(descriptor, mod) {
	const out = {}
	for (const [key, value] of Object.entries(descriptor)) {
		out[key] =
			typeof value === 'function'
				? { fn: key, isExport: value === mod[key] }
				: value
	}
	return out
}

/**
 * An element with an id, for the event log.
 *
 * @param {string} id Element id.
 * @return {HTMLElement}
 */
function el(id) {
	const node = document.createElement('div')
	node.id = id
	return node
}

describe('leaf registration golden', () => {
	it('mounts, unmounts and registers every leaf the recorded way', async () => {
		const out = {}
		for (const [register, descriptorName] of LEAVES) {
			const mod = await import(`../../src/integrations/${register}.js`)
			events.length = 0
			const a = el('a')
			const b = el('b')
			mod.mount(undefined, { x: 1 })
			mod.mount(null)
			mod.mount(a, { register: 'r', objectId: 'o' })
			mod.mount(a, { again: true })
			mod.mount(b)
			mod.unmount(el('never'))
			mod.unmount(a)
			mod.unmount(a)
			mod.mount(a, null)
			mod.unmount(b)

			const fresh = {}
			mod[register](fresh)
			mod[register](fresh)
			const registered = []
			const live = {
				OCA: {
					Other: 1,
					OpenRegister: {
						keep: true,
						integrations: {
							register: (entry) => registered.push(entry.id),
						},
					},
				},
			}
			mod[register](live)
			const partial = { OCA: { OpenRegister: { x: 1 } } }
			mod[register](partial)
			mod[register]()

			out[register] = {
				exports: Object.keys(mod).sort(),
				descriptor: describeDescriptor(mod[descriptorName], mod),
				events: [...events],
				freshQueue: fresh.OCA.OpenRegister.integrations._queue.map(
					(e) => e.id,
				),
				freshKeys: Object.keys(fresh.OCA.OpenRegister.integrations).sort(),
				registered,
				liveKeys: Object.keys(live.OCA.OpenRegister).sort(),
				partial: Object.keys(partial.OCA.OpenRegister).sort(),
				windowQueue: (
					window.OCA?.OpenRegister?.integrations?._queue || []
				).map((e) => e.id),
			}
		}
		golden('leafRegistration', out)
	}, 120000)
})
