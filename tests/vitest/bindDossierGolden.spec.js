// @vitest-environment jsdom
/**
 * Golden: the requests, results and errors of the anonymisation store's
 * bindDossier action, recorded on the implementation where every step ran
 * inline.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec exclude characterization test of existing dossier binding
 */

import { createPinia, setActivePinia } from 'pinia'
import { describe, it, vi } from 'vitest'
import { useAnonymizationStore } from '../../src/store/modules/anonymization.js'
import { golden } from './golden/golden.js'

const calls = []
let propfind = null
let created = null

vi.mock('@nextcloud/axios', () => {
	const axios = vi.fn(async (config) => {
		calls.push([config.method, config.url, config.headers, config.data])
		if (propfind instanceof Error) {
			throw propfind
		}
		return { data: propfind }
	})
	axios.post = vi.fn(async (url, body) => {
		calls.push(['POST', url, body])
		if (created instanceof Error) {
			throw created
		}
		return { data: created }
	})
	return { default: axios }
})

const FOLDER =
	'<?xml version="1.0"?><d:multistatus xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns"><d:response><d:propstat><d:prop><oc:fileid>321</oc:fileid></d:prop></d:propstat></d:response></d:multistatus>'

const CASES = [
	['no name', null, {}, FOLDER, { id: 'u' }],
	['blank name', '   ', {}, FOLDER, { id: 'u' }],
	['folder lookup fails', 'Dos', {}, new Error('dav'), { id: 'u' }],
	['folder without id', 'Dos', {}, '<x/>', { id: 'u' }],
	['folder answer empty', 'Dos', {}, '', { id: 'u' }],
	[
		'self id, own bases',
		' Mijn dossier/é ',
		{ description: 'Omschrijving', bases: ['a', 'b'] },
		FOLDER,
		{ '@self': { id: 'self-uuid' }, id: 'plain' },
	],
	['plain id, empty bases', 'Dos', { bases: [] }, FOLDER, { id: 'plain' }],
	[
		'bases not a list',
		'Dos',
		{ bases: 'x', description: null },
		FOLDER,
		{ '@self': {}, id: 0 },
	],
	['no uuid', 'Dos', {}, FOLDER, { '@self': { id: null } }],
	['empty answer', 'Dos', undefined, FOLDER, null],
	['create fails', 'Dos', {}, FOLDER, new Error('create')],
]

/**
 * Run bindDossier and record its outcome.
 *
 * @param {object} store The store.
 * @param {Array} args The arguments.
 * @return {Promise<object>}
 */
async function bind(store, args) {
	try {
		return { result: await store.bindDossier(...args) }
	} catch (err) {
		return { error: err.message }
	}
}

describe('bindDossier golden', () => {
	it('binds a folder to a dossier', async () => {
		const out = []
		for (const [name, folder, options, answer, createAnswer] of CASES) {
			setActivePinia(createPinia())
			calls.length = 0
			propfind = answer
			created = createAnswer
			const store = useAnonymizationStore()
			const args = options === undefined ? [folder] : [folder, options]
			const first = await bind(store, args)
			const again = await bind(store, args)
			out.push({
				name,
				first,
				again,
				same: first.result !== undefined && first.result === again.result,
				dossiers: store.dossiers,
				calls: [...calls],
			})
		}
		golden('bindDossier', out)
	})
})
