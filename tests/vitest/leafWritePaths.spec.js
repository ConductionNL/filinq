/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * No leaf is a second write path (leaf-integrations 3.4). The register object
 * stays the one store of a signing request's `deadline`, a consent record's
 * `objectionDeadline` and a generated document's `fileId`/`filePath`; the
 * calendar, mail and files leaves only show them. Three ways a leaf could
 * write one are pinned shut here:
 *   - the host that hands a record to the registry sidebar changes nothing on
 *     it, so a leaf receives the record as loaded;
 *   - a create-from-email template maps no mail field onto one of them;
 *   - the leaf host code names none of them, so no host code writes them.
 *
 * @spec openspec/changes/leaf-integrations/specs/document-signing/spec.md
 * @spec openspec/changes/leaf-integrations/specs/publication-consent/spec.md
 * @spec openspec/changes/leaf-integrations/specs/document-register/spec.md
 */

import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { describe, expect, it } from 'vitest'
import { publishRecord, recordLeafSidebarMixin } from '../../src/sidebars/recordSidebar.js'

const ROOT = resolve(__dirname, '../..')

/** The fields a leaf shows but never writes. */
const CANONICAL_FIELDS = ['deadline', 'objectionDeadline', 'fileId', 'filePath']

/** The code that hosts the registry's leaves on filinq's record pages. */
const LEAF_HOST_SOURCES = [
	'src/sidebars/RecordLeafSidebar.vue',
	'src/sidebars/recordSidebar.js',
	'src/services/leafAvailability.js',
]

/**
 * The register's schemas.
 *
 * @return {object} The schemas by slug.
 */
function schemas() {
	const register = JSON.parse(readFileSync(resolve(ROOT, 'lib/Settings/filinq_register.json'), 'utf8'))
	return register.components.schemas
}

/**
 * The shared sidebar state as CnAppRoot provides it.
 *
 * @return {object} The state.
 */
function sidebarState() {
	return { active: false, open: false, objectType: '', objectId: '', title: '', subtitle: '', register: '', schema: '', object: null, requestedTab: null }
}

describe('a leaf receives the record unchanged', () => {
	const records = [
		['signingRequest', { id: 'sr-1', title: 'Contract', deadline: '2026-09-01', status: 'pending' }],
		['publicationConsent', { id: 'pc-1', title: 'Besluit', objectionDeadline: '2026-10-01', consentStatus: 'notified' }],
		['generatedDocument', { id: 'gd-1', title: 'Brief', fileId: 4711, filePath: '/Filinq/brief.pdf', status: 'completed' }],
	]

	it.each(records)('publishing a %s keeps its canonical fields as loaded', (schema, record) => {
		const before = structuredClone(record)
		const state = sidebarState()

		expect(publishRecord(state, { schema, id: record.id, title: record.title, object: record })).toBe(true)

		expect(record).toEqual(before)
		expect(state.object).toBe(record)
		for (const field of CANONICAL_FIELDS) {
			expect(Object.hasOwn(state, field), `the sidebar state carries no ${field} of its own`).toBe(false)
		}
	})

	it('the record page mixin hands the loaded record on without touching it', () => {
		const record = { id: 'sr-2', name: 'Lease', deadline: '2026-12-01' }
		const before = structuredClone(record)
		const state = sidebarState()
		const mixin = recordLeafSidebarMixin({ schema: 'signingRequest', record: (vm) => vm.record, title: (r) => r.name })
		const vm = {
			objectSidebarState: state,
			record,
			...mixin.data(),
			$watch(read, handler) {
				handler(read(vm))
			},
		}

		mixin.created.call(vm)

		expect(state.object).toBe(record)
		expect(record).toEqual(before)
	})
})

describe('create-from-email writes no canonical field', () => {
	it('every mail template maps only onto properties its schema has, none of them a deadline or a file', () => {
		const templated = Object.entries(schemas()).filter(([, schema]) => schema.configuration?.mailObjectTemplate)

		expect(templated.map(([slug]) => slug).sort()).toEqual(['correspondence', 'publicationConsent'])
		for (const [slug, schema] of templated) {
			for (const target of Object.keys(schema.configuration.mailObjectTemplate)) {
				expect(CANONICAL_FIELDS, `${slug} maps a mail field onto ${target}`).not.toContain(target)
				expect(Object.keys(schema.properties), `${slug}.${target} is a property of the schema`).toContain(target)
			}
		}
	})

	it('the schemas whose leaves show a canonical field still hold it as a property', () => {
		const all = schemas()

		expect(Object.keys(all.signingRequest.properties)).toContain('deadline')
		expect(Object.keys(all.publicationConsent.properties)).toContain('objectionDeadline')
		expect(Object.keys(all.generatedDocument.properties)).toContain('fileId')
		expect(all.signingRequest.configuration.linkedTypes).toContain('calendar')
		expect(all.publicationConsent.configuration.linkedTypes).toContain('calendar')
		expect(all.generatedDocument.configuration.linkedTypes).toContain('files')
	})
})

describe('the leaf host code names no canonical field', () => {
	it.each(LEAF_HOST_SOURCES)('%s', (file) => {
		const source = readFileSync(resolve(ROOT, file), 'utf8')

		for (const field of CANONICAL_FIELDS) {
			expect(new RegExp(`\\b${field}\\b`).test(source), `${file} names ${field}`).toBe(false)
		}
	})
})
