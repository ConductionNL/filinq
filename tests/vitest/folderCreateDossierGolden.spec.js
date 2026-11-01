/**
 * Golden: what the folder anonymisation store's createDossier sends and
 * keeps, recorded on the implementation where the payload and UUID were read
 * inline.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec exclude characterization test of existing folder dossier creation
 */

import { createPinia, setActivePinia } from 'pinia'
import { describe, it, vi } from 'vitest'
import { useFolderAnonymizationStore } from '../../src/store/modules/folderAnonymization.js'
import { golden } from './golden/golden.js'

const calls = []
let answer = null
let probe = () => null

vi.mock('@nextcloud/axios', () => {
	const axios = vi.fn(async () => ({ data: {} }))
	axios.post = vi.fn(async (url, body) => {
		calls.push(['POST', url, body, probe()])
		if (answer instanceof Error) {
			throw answer
		}
		return { data: answer }
	})
	return { default: axios }
})

const STATES = [
	{ folderId: null, folderPath: '/a' },
	{ folderId: 0, folderPath: '/a' },
	{
		folderId: 12,
		folderPath: '/Dossiers/A',
		dossier: { name: 'Naam', description: 'Omschrijving', bases: ['x'] },
	},
	{
		folderId: '13',
		folderPath: '/Dossiers/B',
		dossier: { name: '', description: '', bases: null },
	},
	{ folderId: 14, folderPath: '', dossier: { name: '', bases: undefined } },
]

const ANSWERS = [
	{ '@self': { id: 'self' }, id: 'id', uuid: 'uuid' },
	{ '@self': { id: '' }, id: 'id' },
	{ uuid: 'only-uuid' },
	{ '@self': {} },
	null,
	Object.assign(new Error('down'), { response: { data: { error: 'refused' } } }),
	new Error('plain'),
]

describe('folder createDossier golden', () => {
	it('creates a dossier for the folder', async () => {
		const out = []
		for (const state of STATES) {
			for (const a of ANSWERS) {
				setActivePinia(createPinia())
				calls.length = 0
				answer = a
				const store = useFolderAnonymizationStore()
				probe = () => ({
					creating: store.dossier.creating,
					error: store.dossier.error,
				})
				store.$patch({
					folderId: state.folderId,
					folderPath: state.folderPath,
					dossier: { error: 'old', ...(state.dossier || {}) },
				})
				await store.createDossier()
				out.push({
					state,
					answer: a instanceof Error ? a.message : a,
					calls: [...calls],
					dossier: { ...store.dossier },
				})
			}
		}
		golden('folderCreateDossier', out)
	})
})
