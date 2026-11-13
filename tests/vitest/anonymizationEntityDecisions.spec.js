/**
 * The anonymisation store's per-entity decisions: include/skip toggles,
 * grondslagen, the relation PATCH they go through, manually added entities,
 * and the queue housekeeping actions.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 */

import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { useAnonymizationStore } from '../../src/store/modules/anonymization.js'

const calls = []
let patchFailures = {}
let postAnswer = {}

vi.mock('@nextcloud/axios', () => {
	const axios = vi.fn(async () => ({ data: {} }))
	axios.patch = vi.fn(async (url, body) => {
		calls.push(['PATCH', url, body])
		const failure = Object.entries(patchFailures).find(([fragment]) => url.endsWith(fragment))
		if (failure) {
			throw failure[1]
		}
		return { status: 200, data: { saved: true } }
	})
	axios.post = vi.fn(async (url, body) => {
		calls.push(['POST', url, body])
		return { data: postAnswer }
	})
	return { default: axios }
})

/**
 * An HTTP error shaped like axios's.
 *
 * @param {number} status The HTTP status.
 * @param {object} data The response body.
 * @return {Error}
 */
function httpError(status, data) {
	const err = new Error('HTTP ' + status)
	err.response = { status, data }
	return err
}

describe('anonymization store entity decisions', () => {
	let store

	beforeEach(() => {
		setActivePinia(createPinia())
		store = useAnonymizationStore()
		calls.length = 0
		patchFailures = {}
		postAnswer = {}
		vi.spyOn(console, 'error').mockImplementation(() => {})
	})

	it('setRelationSkip PATCHes the relation with the decision and bases', async () => {
		const result = await store.setRelationSkip(7, 1, 0, ['5.1.2e'])

		expect(result).toEqual({ ok: true, status: 200, body: { saved: true } })
		expect(calls).toEqual([
			['PATCH', '/index.php/apps/filinq/api/anonymization/relations/7', { skipAnonymization: true, force: false, bases: ['5.1.2e'] }],
		])
	})

	it('setRelationSkip leaves bases out when none are given and reports a failure', async () => {
		patchFailures = { '/relations/8': httpError(409, { error: 'confirm' }) }

		const result = await store.setRelationSkip(8, false, true)

		expect(result).toEqual({ ok: false, status: 409, body: { error: 'confirm' } })
		expect(calls[0][2]).toEqual({ skipAnonymization: false, force: true })
	})

	it('setRelationSkip reports status 0 for a network failure', async () => {
		patchFailures = { '/relations/9': new Error('offline') }

		expect(await store.setRelationSkip(9, true)).toEqual({ ok: false, status: 0, body: {} })
	})

	it('toggleEntity flips an included entity to skipped on every relation', async () => {
		const entity = { type: 'PERSON', value: 'Jan', included: true, relationIds: [1, 2] }
		const entry = { entities: [entity] }

		const result = await store.toggleEntity(entry, 0, true)

		expect(result).toEqual({ ok: true, status: 200, body: {} })
		expect(calls.map((c) => [c[1], c[2]])).toEqual([
			['/index.php/apps/filinq/api/anonymization/relations/1', { skipAnonymization: true, force: true }],
			['/index.php/apps/filinq/api/anonymization/relations/2', { skipAnonymization: true, force: true }],
		])
		expect(entity).toMatchObject({ included: false, skipAnonymization: true, _decisionSkip: true, _patchError: null })
	})

	it('a refused relation keeps the entity as it was and records the error', async () => {
		patchFailures = { '/relations/2': httpError(403, { error: 'Not allowed' }) }
		const entity = { included: true, relationIds: [1, 2] }

		const result = await store.setEntitySkip({ entities: [entity] }, 0, true)

		expect(result).toEqual({ ok: false, status: 403, body: { error: 'Not allowed' } })
		expect(entity.included).toBe(true)
		expect(entity._patchError).toBe('Not allowed')
	})

	it('a refusal without an error message gets the default one', async () => {
		patchFailures = { '/relations/4': httpError(500, {}) }
		const entity = { included: true, relationId: 4 }

		await store.setEntitySkip({ entities: [entity] }, 0, true)

		expect(entity._patchError).toBe('Kon de wijziging niet opslaan')
	})

	it('an entity without relations or index cannot be decided', async () => {
		const none = { ok: false, status: 0, body: {} }

		expect(await store.setEntitySkip({ entities: [{ included: true }] }, 0, true)).toEqual(none)
		expect(await store.setEntitySkip({ entities: [] }, 3, true)).toEqual(none)
		expect(await store.toggleEntity(null, 0)).toEqual(none)
		expect(await store.setEntityBases({ entities: [] }, 0, ['x'])).toEqual(none)
		expect(calls).toEqual([])
	})

	it('setEntityBases stores the bases and keeps the current skip decision', async () => {
		const entity = { included: true, relationId: 5, _decisionSkip: false, bases: [] }

		await store.setEntityBases({ entities: [entity] }, 0, ['5.1.1d', '5.1.2e'])

		expect(calls[0][2]).toEqual({ skipAnonymization: false, force: false, bases: ['5.1.1d', '5.1.2e'] })
		expect(entity.bases).toEqual(['5.1.1d', '5.1.2e'])
		expect(entity._decisionBases).toEqual(['5.1.1d', '5.1.2e'])
		expect(entity.included).toBe(true)
	})

	it('setEntityBases treats a non-array as no bases', async () => {
		const entity = { relationId: 6, _decisionSkip: true }

		await store.setEntityBases({ entities: [entity] }, 0, 'loose')

		expect(calls[0][2]).toEqual({ skipAnonymization: true, force: false, bases: [] })
		expect(entity.bases).toEqual([])
	})

	it('setVisibleEntities sets inclusion only on existing visible rows', () => {
		const entry = { entities: [{ included: false }, { included: false }, { included: false }] }

		store.setVisibleEntities(entry, [0, 2, 9], 1)

		expect(entry.entities.map((e) => e.included)).toEqual([true, false, true])
		expect(() => store.setVisibleEntities({}, [0], true)).not.toThrow()
	})

	it('addManualEntity posts the entity, sets its bases and puts it on top', async () => {
		postAnswer = { entity: { type: 'PERSON', value: 'Piet' }, relations: [{ id: 31 }, { id: null }, { id: 32 }] }
		const entry = { fileId: 12, entities: [{ type: 'EMAIL', value: 'a@b.nl' }] }

		const data = await store.addManualEntity(entry, { value: '  Piet ', type: 'PERSON', wholeWord: false, bases: ['5.1.2e'] })

		expect(data).toBe(postAnswer)
		expect(calls).toEqual([
			['POST', '/index.php/apps/openregister/api/files/12/manual-entities', { value: 'Piet', type: 'PERSON', wholeWord: false, caseSensitive: true }],
			['PATCH', '/index.php/apps/openregister/api/entity-relations/31', { bases: ['5.1.2e'], skipAnonymization: false }],
			['PATCH', '/index.php/apps/openregister/api/entity-relations/32', { bases: ['5.1.2e'], skipAnonymization: false }],
		])
		expect(entry.entityCount).toBe(2)
		expect(entry.entities[0]).toEqual({
			type: 'PERSON',
			value: 'Piet',
			included: true,
			confidence: 1.0,
			highestConfidence: 1.0,
			fileCount: 1,
			count: 2,
			relationId: 31,
			relationIds: [31, 32],
			bases: ['5.1.2e'],
			_decisionBases: ['5.1.2e'],
			_decisionSkip: false,
			skipAnonymization: false,
			_patchError: null,
		})
	})

	it('addManualEntity keeps the row when setting bases fails, without persisted bases', async () => {
		postAnswer = { relations: [{ id: 40 }] }
		patchFailures = { '/entity-relations/40': new Error('down') }
		const entry = { fileId: 12, entities: [] }

		await store.addManualEntity(entry, { value: 'Zuidas', type: 'LOCATION', bases: ['x'] })

		expect(entry.entities[0]).toMatchObject({ type: 'LOCATION', value: 'Zuidas', bases: [], _decisionBases: ['x'], count: 1 })
		expect(console.error).toHaveBeenCalled()
	})

	it('addManualEntity without bases or relations does not PATCH', async () => {
		postAnswer = null
		const entry = { fileId: 12, entities: [] }

		await store.addManualEntity(entry, { value: 'Zuidas', type: 'LOCATION' })

		expect(calls.map((c) => c[0])).toEqual(['POST'])
		expect(entry.entities[0]).toMatchObject({ relationId: null, relationIds: [], count: 1 })
	})

	it('addManualEntity refuses an entry without a file or a blank value', async () => {
		await expect(store.addManualEntity({ entities: [] }, { value: 'x', type: 'PERSON' }))
			.rejects.toMatchObject({ message: 'Entry has no fileId yet.', status: 0 })
		await expect(store.addManualEntity({ fileId: 1, entities: [] }, { value: '   ', type: 'PERSON' }))
			.rejects.toMatchObject({ message: 'A value and a type are required.', status: 0 })
		await expect(store.addManualEntity({ fileId: 1, entities: [] }, { value: 'x' }))
			.rejects.toThrow('A value and a type are required.')
		expect(calls).toEqual([])
	})

	it('clearCompleted drops finished and failed files only', () => {
		store.files = [{ status: 'completed' }, { status: 'error' }, { status: 'queued' }, { status: 'extracted' }]

		store.clearCompleted()

		expect(store.files.map((f) => f.status)).toEqual(['queued', 'extracted'])
	})

	it('reset empties the queue, dossiers and batch progress', () => {
		store.files = [{ status: 'queued' }]
		store.processing = true
		store.dossiers = [{ folderName: 'a' }]
		store.batch = { running: true, total: 3, done: 1, failed: 1, unreviewed: [{ fileId: 1 }] }

		store.reset()

		expect(store.files).toEqual([])
		expect(store.processing).toBe(false)
		expect(store.dossiers).toEqual([])
		expect(store.batch).toEqual({ running: false, total: 0, done: 0, failed: 0, unreviewed: [] })
	})

	it('findDossier finds a dossier by folder name', () => {
		store.dossiers = [{ folderName: 'a', uuid: 'u-a' }, { folderName: 'b', uuid: 'u-b' }]

		expect(store.findDossier('b')).toEqual({ folderName: 'b', uuid: 'u-b' })
		expect(store.findDossier('c')).toBeUndefined()
	})
})
