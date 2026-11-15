/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Golden test for the leaves that read and write on behalf of a host object
 * (documents, download all files, merge to PDF): every request they send,
 * with its headers, and what they render from each answer, for an absent
 * object, a good answer, a refusal and an unreachable server.
 * RECORD_GOLDEN=1 rewrites the golden file.
 */

import fs from 'fs'
import path from 'path'
import CnFilinqDocumentsWidget from './CnFilinqDocumentsWidget.vue'
import CnFilinqDownloadAllFilesWidget from './CnFilinqDownloadAllFilesWidget.vue'
import CnFilinqMergeToPdfWidget from './CnFilinqMergeToPdfWidget.vue'
import { renderStubbed } from '../../tests/jest/renderGolden.js'

jest.mock('@nextcloud/l10n', () => ({
	translate: (app, text, vars) =>
		vars ? text + ' ' + JSON.stringify(vars) : text,
}))
jest.mock('@nextcloud/router', () => ({
	generateUrl: (url, params) =>
		'url:' + url + (params ? JSON.stringify(params) : ''),
}))

const GOLDEN = path.join(
	__dirname,
	'../../tests/jest/golden/host-object-leaves.json',
)
const RECORD = process.env.RECORD_GOLDEN === '1'
const golden = RECORD ? {} : JSON.parse(fs.readFileSync(GOLDEN, 'utf8'))

const calls = []
let answers = []

/**
 * A fetch answer.
 *
 * @param {number} status The HTTP status.
 * @param {*} body The JSON body, or an Error to fail the JSON read.
 * @return {object} The response.
 */
function answer(status, body) {
	return {
		ok: status >= 200 && status < 300,
		status,
		json: () =>
			body instanceof Error ? Promise.reject(body) : Promise.resolve(body),
	}
}

const DOCS = {
	results: [
		{ fileId: 11, name: 'a.pdf', record: { status: 'final' } },
		{ fileId: 12, name: 'b.docx' },
	],
}

const HOST = { register: 'cases', schema: 'case', objectId: 'obj-1' }

const SCENARIOS = {
	absent: { props: { ...HOST, objectId: '' }, answers: [] },
	ok: {
		props: HOST,
		answers: [
			answer(200, DOCS),
			answer(200, {
				results: [{ uuid: 't-1', name: 'Cover' }, { id: 7 }, {}],
			}),
		],
		archive: [
			answer(200, {
				included: [1, 2],
				excluded: { permission: ['x.pdf'] },
				fileId: 99,
			}),
		],
	},
	refused: {
		props: HOST,
		answers: [answer(403, { error: 'no' }), answer(500, {})],
		archive: [answer(413, { error: 'too big' })],
	},
	unreachable: {
		props: HOST,
		answers: ['throw', 'throw'],
		archive: ['throw'],
	},
	badJson: {
		props: HOST,
		answers: [
			answer(200, { results: 'nope' }),
			answer(200, new Error('bad json')),
		],
		archive: [answer(200, new Error('bad json'))],
	},
	noToken: {
		props: { ...HOST, schema: 'a&b c' },
		answers: [answer(200, DOCS), answer(200, {})],
		noToken: true,
	},
}

const LEAVES = {
	documents: CnFilinqDocumentsWidget,
	downloadAll: CnFilinqDownloadAllFilesWidget,
	mergeToPdf: CnFilinqMergeToPdfWidget,
}

/** Let every pending promise settle. */
async function settle() {
	for (let i = 0; i < 10; i++) {
		await new Promise((resolve) => setTimeout(resolve, 0))
	}
}

beforeAll(() => {
	global.fetch = (url, init) => {
		calls.push([url, init ?? null, init ? Object.keys(init) : null])
		const next = answers.shift()
		if (next === 'throw' || next === undefined) {
			return Promise.reject(new Error('unreachable'))
		}
		return Promise.resolve(next)
	}
})

afterAll(() => {
	if (RECORD) {
		fs.writeFileSync(GOLDEN, JSON.stringify(golden, null, '\t') + '\n')
	}
})

describe('host object leaves golden', () => {
	for (const [leaf, component] of Object.entries(LEAVES)) {
		for (const [name, scenario] of Object.entries(SCENARIOS)) {
			it(`${leaf} ${name}`, async () => {
				if (scenario.noToken) {
					document.head.removeAttribute('data-requesttoken')
				} else {
					document.head.setAttribute('data-requesttoken', 'token-1')
				}
				calls.length = 0
				answers = [...scenario.answers]
				const page = await renderStubbed(component, {
					props: scenario.props,
				})
				await settle()
				const actual = {
					mounted: {
						calls: JSON.parse(JSON.stringify(calls)),
						html: page.html(),
					},
				}
				if (leaf === 'downloadAll') {
					calls.length = 0
					answers = [...(scenario.archive || [])]
					await page.vm.download()
					await settle()
					actual.download = {
						calls: JSON.parse(JSON.stringify(calls)),
						html: page.html(),
					}
				}
				if (leaf === 'mergeToPdf') {
					actual.token = page.vm.requestToken()
				}
				page.unmount()
				if (RECORD) {
					golden[leaf + '.' + name] = actual
				} else {
					expect(actual).toEqual(golden[leaf + '.' + name])
				}
			})
		}
	}
})
