/**
 * Golden: how the anonymisation store reads an already anonymised file into a
 * review entry, through its placeholders or through its anonymisation link,
 * recorded on the implementation where each reader built the entry inline.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec exclude characterization test of existing anonymised-file reading
 */

import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, it, vi } from 'vitest'
import { useAnonymizationStore } from '../../src/store/modules/anonymization.js'
import { golden } from './golden/golden.js'

const calls = []
let text = ''
let link = null

vi.mock('../../src/services/fileViewerService.js', () => ({
	extractDocumentText: vi.fn(async (meta) => {
		calls.push(['TEXT', meta])
		if (text instanceof Error) {
			throw text
		}
		return text
	}),
}))

const ENTITIES = {
	1: {
		data: { type: 'PERSON_NAME', value: 'Jan', confidence: 0.9 },
		relations: [{ bases: ['a', 'b'] }, { bases: ['a'] }, {}, { bases: 'x' }],
	},
	2: { data: {}, relations: 'none' },
	6: { data: { value: 'Jan', confidence: 0 } },
	7: {},
	8: { data: { value: '', confidence: 0 } },
}

const FAILURES = {
	3: Object.assign(new Error('gone'), { response: { status: 404 } }),
	4: Object.assign(new Error('denied'), {
		response: { status: 403, data: { message: 'no access' } },
	}),
	5: new Error('network'),
}

vi.mock('@nextcloud/axios', () => {
	const axios = vi.fn(async () => ({ data: {} }))
	axios.get = vi.fn(async (url, config) => {
		calls.push(['GET', url, config ?? null])
		if (url.includes('anonymizationLink')) {
			if (link instanceof Error) {
				throw link
			}
			return { data: link }
		}
		const id = Number(url.split('/').pop())
		if (FAILURES[id]) {
			throw FAILURES[id]
		}
		return { data: ENTITIES[id] }
	})
	return { default: axios }
})

const LINK = {
	id: 'L1',
	replacementCount: 12,
	sourceFileId: 40,
	sourceFileName: 'bron.pdf',
	sourceFilePath: '/DocuDesk/Dos/bron.pdf',
	runCount: 2,
}

const PLACEHOLDERS =
	'[PERSON: 1] tekst [EMAIL: 2] [PERSON: 1] [LOCATION: 3] [IBAN: 4] [PHONE: 5] [PERSON: 6] [X: 7] [zie bijlage 2]'

const META = {
	fileId: 9,
	fileName: 'f.pdf',
	path: '/DocuDesk/Dos/f.pdf',
	mimeType: 'application/pdf',
}

const CASES = [
	['no link, unreadable text', new Error('read'), { results: [] }, {}],
	['no link, no placeholders', 'plain text', { results: [] }, {}],
	['link lookup fails, no placeholders', 'plain', new Error('search'), {}],
	['link, unreadable text', new Error('read'), { results: [LINK] }, {}],
	['link, no placeholders', 'plain', { results: [LINK, { id: 'L2' }] }, {}],
	['link, no placeholders, no push', '', { results: [LINK] }, { push: false }],
	['empty link', '', { results: [{}] }, {}],
	['placeholders, no link', PLACEHOLDERS, { results: [] }, {}],
	['placeholders and link', PLACEHOLDERS, { results: [LINK] }, {}],
	['placeholders, no push', PLACEHOLDERS, null, { push: false }],
	[
		'placeholders, link without count',
		'[PERSON: 1] [PERSON: 1]',
		{ results: [{ id: 'L3' }] },
		{},
	],
	[
		'placeholders, link counting none',
		'[PERSON: 8] [EMAIL: 8]',
		{ results: [{ id: 'L4', replacementCount: 0 }] },
		{},
	],
]

beforeEach(() => {
	vi.spyOn(console, 'error').mockImplementation(() => {})
})

describe('anonymised entry golden', () => {
	it('reads an anonymised file into a review entry', async () => {
		const out = []
		for (const [name, answer, linkAnswer, opts] of CASES) {
			setActivePinia(createPinia())
			calls.length = 0
			text = answer
			link = linkAnswer
			const store = useAnonymizationStore()
			const entry = await store.loadAnonymizedEntities(
				META,
				opts.push === undefined ? undefined : opts,
			)
			out.push({
				name,
				entry,
				queued: store.files.length,
				calls: [...calls],
			})
		}
		setActivePinia(createPinia())
		calls.length = 0
		const store = useAnonymizationStore()
		out.push({
			name: 'no file id',
			link: await store.findAnonymizationLink(null),
			undef: await store.findAnonymizationLink(undefined),
			calls: [...calls],
		})
		out.push({
			name: 'linked entry from a bare link',
			entry: store.buildLinkedAnonymizedEntry(
				{ fileId: 1, fileName: 'a.pdf', path: '/a.pdf' },
				null,
			),
			queued: store.files.length,
		})
		golden('anonymizedEntry', out)
	})
})
