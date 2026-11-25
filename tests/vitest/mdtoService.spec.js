/**
 * The MDTO service: the calls behind the MDTO panel, and the supplement
 * changes accepting or rejecting a proposal makes.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/tasks.md#3-frontend
 */

import { beforeEach, describe, expect, it, vi } from 'vitest'

const axios = vi.hoisted(() => ({ get: vi.fn(), post: vi.fn(), put: vi.fn() }))
vi.mock('@nextcloud/axios', () => ({ default: axios }))
vi.mock('@nextcloud/l10n', () => ({ translate: (app, text) => text }))

const {
	acceptProposal,
	decideRestriction,
	fetchMdtoStatus,
	isTransferred,
	openRestrictionProposals,
	saveSupplement,
	sidecarUrl,
	transferRecord,
} = await import('../../src/services/mdto.js')

const subject = { objectRef: 'rec-1', objectType: 'document' }
const avg = { type: 'avg-persoonsgegevens', grondslag: 'AVG art. 6', proposed: true, source: 'consent' }

beforeEach(() => {
	vi.resetAllMocks()
})

describe('fetchMdtoStatus', () => {
	it('reads the record status from the filinq endpoint', async () => {
		axios.get.mockResolvedValue({ data: { complete: true } })

		expect(await fetchMdtoStatus('rec/1')).toEqual({ ok: true, data: { complete: true } })
		expect(axios.get.mock.calls[0][0]).toContain('/apps/filinq/api/mdto/rec%2F1')
	})

	it('answers the server message on a failure', async () => {
		axios.get.mockRejectedValue({ response: { status: 404, data: { error: 'The record cannot be read.' } } })

		expect(await fetchMdtoStatus('x')).toMatchObject({ ok: false, status: 404, error: 'The record cannot be read.' })
	})
})

describe('transferRecord', () => {
	it('treats a blocked transfer as an outcome with the missing fields', async () => {
		axios.post.mockRejectedValue({ response: { status: 422, data: { status: 'blocked', missing: ['aggregatieniveau'] } } })

		expect(await transferRecord('rec-1')).toEqual({ ok: true, data: { status: 'blocked', missing: ['aggregatieniveau'] } })
		expect(axios.post.mock.calls[0][0]).toContain('/api/mdto/rec-1/transfer')
	})

	it('keeps a refusal an error', async () => {
		axios.post.mockRejectedValue({ response: { status: 403, data: { error: 'Only archive staff may export or transfer a record.' } } })

		expect(await transferRecord('rec-1')).toMatchObject({ ok: false, status: 403 })
	})
})

describe('sidecarUrl', () => {
	it('asks for xml unless json is named', () => {
		expect(sidecarUrl('rec-1')).toMatch(/\/api\/mdto\/rec-1\/export\?format=xml$/)
		expect(sidecarUrl('rec-1', 'json')).toMatch(/format=json$/)
		expect(sidecarUrl('rec-1', 'pdf')).toMatch(/format=xml$/)
	})
})

describe('saveSupplement', () => {
	it('creates a supplement for the record with the defaults and the subject', async () => {
		axios.post.mockResolvedValue({ data: { id: 's-1' } })

		await saveSupplement({}, { omschrijving: 'Besluit' }, subject)

		expect(axios.post.mock.calls[0][0]).toContain('/apps/openregister/api/objects/filinq/mdtoSupplement')
		expect(axios.post.mock.calls[0][1]).toEqual({
			aggregatieniveau: 'archiefstuk',
			taal: 'nl',
			beperkingGebruik: [],
			betrokkene: [],
			proposals: [],
			omschrijving: 'Besluit',
			objectRef: 'rec-1',
			objectType: 'document',
		})
	})

	it('updates an existing one carrying every stored field forward, never repointing it', async () => {
		axios.put.mockResolvedValue({ data: {} })
		const stored = { id: 's-1', objectRef: 'rec-1', objectType: 'document', aggregatieniveau: 'archiefstuk', taal: 'en', betrokkene: [{ rol: 'aanvrager', naamRef: 'urn:x' }] }

		await saveSupplement(stored, { taal: 'nl', objectRef: 'other' }, subject)

		expect(axios.put.mock.calls[0][0]).toMatch(/mdtoSupplement\/s-1$/)
		const body = axios.put.mock.calls[0][1]
		expect(body.taal).toBe('nl')
		expect(body.objectRef).toBe('rec-1')
		expect(body.betrokkene).toEqual(stored.betrokkene)
		expect(body).not.toHaveProperty('id')
	})

	it('defaults a dossier to the dossier aggregation level', async () => {
		axios.post.mockResolvedValue({ data: {} })

		await saveSupplement({}, {}, { objectRef: 'd-1', objectType: 'dossier' })

		expect(axios.post.mock.calls[0][1].aggregatieniveau).toBe('dossier')
	})
})

describe('proposals', () => {
	it('accepting a field writes it and drops it from the unconfirmed list', () => {
		expect(acceptProposal({ proposals: ['aggregatieniveau', 'dekkingInTijdBegin'] }, 'aggregatieniveau', 'archiefstuk'))
			.toEqual({ aggregatieniveau: 'archiefstuk', proposals: ['dekkingInTijdBegin'] })
	})

	it('an accepted restriction is stored confirmed', () => {
		expect(decideRestriction({ beperkingGebruik: [] }, avg, true).beperkingGebruik)
			.toEqual([{ ...avg, proposed: false, rejected: false }])
	})

	it('a rejected restriction is stored unconfirmed and rejected, replacing an earlier decision', () => {
		const earlier = { ...avg, proposed: false, rejected: false }

		expect(decideRestriction({ beperkingGebruik: [earlier] }, avg, false).beperkingGebruik)
			.toEqual([{ ...avg, proposed: true, rejected: true }])
	})

	it('only undecided restriction proposals stay open', () => {
		const woo = { type: 'woo-uitzondering', grondslag: 'Woo', proposed: true, source: 'prohibition' }
		const status = {
			supplement: { beperkingGebruik: [{ ...avg, proposed: true, rejected: true }] },
			proposals: { beperkingGebruik: { value: [avg, woo], proposed: true } },
		}

		expect(openRestrictionProposals(status)).toEqual([woo])
		expect(openRestrictionProposals(null)).toEqual([])
	})
})

describe('isTransferred', () => {
	// @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-transfer-to-archive-state-for-permanent-records-req-ddare-006
	it('reads overgebracht, in either spelling OpenRegister accepts, as transferred', () => {
		expect(isTransferred({ archiefstatus: 'overgebracht' })).toBe(true)
		expect(isTransferred({ archiefstatus: ' Transferred ' })).toBe(true)
		expect(isTransferred({ archiefstatus: 'nog_te_archiveren' })).toBe(false)
		expect(isTransferred({ archiefstatus: 7 })).toBe(false)
		expect(isTransferred({})).toBe(false)
		expect(isTransferred(null)).toBe(false)
	})
})
