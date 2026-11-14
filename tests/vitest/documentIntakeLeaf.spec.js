// @vitest-environment jsdom
/**
 * The document intake leaf: the waiting documents on a record in another app,
 * those whose source reference names the record first, and assigning one to
 * that record.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/document-intake-inbox/tasks.md#task-3.2
 */

import { beforeEach, describe, expect, it, vi } from 'vitest'
import {
	DOCUMENT_INTAKE_INTEGRATION_ID,
	DOCUMENT_INTAKE_SURFACES,
	registerDocumentIntakeLeaf,
} from '../../src/integrations/registerDocumentIntakeLeaf.js'
import { assignToHost, orderForHost } from '../../src/services/intakeLeaf.js'

const calls = []

// The widget is a .vue file this vitest config does not compile.
vi.mock('../../src/integrations/CnFilinqDocumentIntakeWidget.vue', () => ({
	default: {},
}))

vi.mock('@nextcloud/axios', () => ({
	default: {
		post: (url, body) => {
			calls.push({ url, body })
			return Promise.resolve({ data: { uuid: 'a', status: 'assigned' } })
		},
	},
}))

beforeEach(() => {
	calls.length = 0
})

describe('orderForHost', () => {
	it('puts documents whose source reference names the record first and marks them', () => {
		const rows = orderForHost(
			[
				{ uuid: 'a', sourceRef: 'mail-1' },
				{ uuid: 'b', sourceRef: 'Z-2026-0042' },
				{ uuid: 'c' },
			],
			{ objectId: 'case-uuid', reference: 'Z-2026-0042' },
		)
		expect(rows.map((row) => row.uuid)).toEqual(['b', 'a', 'c'])
		expect(rows[0].matchesHost).toBe(true)
		expect(rows[1].matchesHost).toBe(false)
	})

	it('matches on the object id too, and keeps the order otherwise', () => {
		const rows = orderForHost(
			[{ uuid: 'a' }, { uuid: 'b', sourceRef: 'case-uuid' }],
			{ objectId: 'case-uuid' },
		)
		expect(rows.map((row) => row.uuid)).toEqual(['b', 'a'])
	})

	it('marks nothing when the document has no source reference', () => {
		expect(
			orderForHost([{ uuid: 'a', sourceRef: '' }], { objectId: '' })[0]
				.matchesHost,
		).toBe(false)
	})
})

describe('assignToHost', () => {
	it("assigns through filinq's own route to the host record, never through the host app", async () => {
		await assignToHost('a', {
			register: 'dossiq',
			schema: 'case',
			objectId: 'case-uuid',
		})
		expect(calls[0].url).toContain('/apps/filinq/api/intake/documents/')
		expect(calls[0].url).toContain('/assign')
		expect(calls[0].body).toEqual({
			register: 'dossiq',
			schema: 'case',
			id: 'case-uuid',
		})
	})
})

describe('registerDocumentIntakeLeaf', () => {
	it('registers a record leaf under its id, mounted by the host', () => {
		const target = {}
		registerDocumentIntakeLeaf(target)
		const entry = target.OCA.OpenRegister.integrations._queue[0]
		expect(entry.id).toBe(DOCUMENT_INTAKE_INTEGRATION_ID)
		expect(entry.id).toBe('filinq-document-intake')
		expect(DOCUMENT_INTAKE_SURFACES).toEqual(['detail-page', 'single-entity'])
		expect(entry.renderMode).toBe('mount')
	})
})
