/**
 * MDTO metadata: the record's verdict, its supplement, its sidecar and its
 * transfer to the e-depot.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/tmlo-mdto-metadata/tasks.md#3-frontend
 */

import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'

/**
 * The MDTO endpoint of a record.
 *
 * @param {string} uuid The record.
 * @return {string} The address.
 */
function record(uuid) {
	return generateUrl(`/apps/filinq/api/mdto/${encodeURIComponent(uuid)}`)
}

/**
 * The OpenRegister collection of MDTO supplements.
 *
 * @return {string} The address.
 */
function supplements() {
	return generateUrl('/apps/openregister/api/objects/filinq/mdtoSupplement')
}

/**
 * Run a call and answer { ok, data } or { ok: false, status, data, error }.
 *
 * @param {() => Promise<{data: object}>} call The axios call.
 * @param {string} fallback The message when the server gives none.
 * @return {Promise<object>} The outcome.
 */
async function outcome(call, fallback) {
	try {
		const { data } = await call()
		return { ok: true, data }
	} catch (error) {
		return {
			ok: false,
			status: error.response?.status || 0,
			data: error.response?.data || {},
			error: error.response?.data?.error || fallback,
		}
	}
}

/**
 * A record's verdict, retention block, supplement and proposals.
 *
 * @param {string} uuid The record.
 * @return {Promise<object>} The outcome.
 * @spec openspec/changes/tmlo-mdto-metadata/tasks.md#3-frontend
 */
export function fetchMdtoStatus(uuid) {
	return outcome(
		() => axios.get(record(uuid)),
		t('filinq', 'The MDTO metadata could not be loaded.'),
	)
}

/**
 * Transfer the record to the e-depot. A blocked transfer comes back as a 422
 * whose data names the missing fields; it is an outcome, not an error.
 *
 * @param {string} uuid The record.
 * @return {Promise<object>} { ok, data } with data.status blocked, manual, transferred or failed.
 * @spec openspec/changes/tmlo-mdto-metadata/tasks.md#3-frontend
 */
export async function transferRecord(uuid) {
	const result = await outcome(
		() => axios.post(`${record(uuid)}/transfer`),
		t('filinq', 'The record could not be transferred.'),
	)
	if (result.ok === false && ['blocked', 'failed'].includes(result.data?.status)) {
		return { ok: true, data: result.data }
	}
	return result
}

/**
 * The download address of the record's sidecar.
 *
 * @param {string} uuid The record.
 * @param {string} format xml or json.
 * @return {string} The address.
 * @spec openspec/changes/tmlo-mdto-metadata/tasks.md#3-frontend
 */
export function sidecarUrl(uuid, format = 'xml') {
	return `${record(uuid)}/export?format=${format === 'json' ? 'json' : 'xml'}`
}

/**
 * Store the supplement, carrying every existing field forward (PUT semantics).
 *
 * @param {object} existing The stored supplement, `{}` when there is none.
 * @param {object} changes The fields to change.
 * @param {{objectRef: string, objectType: string}} subject The record it describes.
 * @return {Promise<object>} The outcome, data the stored supplement.
 * @spec openspec/changes/tmlo-mdto-metadata/tasks.md#3-frontend
 */
export function saveSupplement(existing, changes, subject) {
	const { id, ...stored } = existing || {}
	const body = {
		aggregatieniveau: subject.objectType === 'dossier' ? 'dossier' : 'archiefstuk',
		taal: 'nl',
		beperkingGebruik: [],
		betrokkene: [],
		proposals: [],
		...stored,
		...changes,
		objectRef: subject.objectRef,
		objectType: subject.objectType,
	}
	const fallback = t('filinq', 'The MDTO metadata could not be saved.')
	if (id) {
		return outcome(
			() => axios.put(`${supplements()}/${encodeURIComponent(id)}`, body),
			fallback,
		)
	}
	return outcome(() => axios.post(supplements(), body), fallback)
}

/**
 * The supplement after accepting one proposal: the value is written and the
 * field no longer counts as unconfirmed.
 *
 * @param {object} supplement The stored supplement.
 * @param {string} field The proposed field.
 * @param {string} value The proposed value.
 * @return {object} The changes to save.
 * @spec openspec/changes/tmlo-mdto-metadata/tasks.md#3-frontend
 */
export function acceptProposal(supplement, field, value) {
	return {
		[field]: value,
		proposals: (supplement?.proposals || []).filter((name) => name !== field),
	}
}

/**
 * The supplement after accepting or rejecting one proposed use restriction.
 * An accepted one is stored confirmed. A rejected one is stored too, still
 * unconfirmed and marked rejected, so it is neither exported nor proposed
 * again.
 *
 * @param {object} supplement The stored supplement.
 * @param {object} restriction The proposed restriction.
 * @param {boolean} accept Whether the operator accepts it.
 * @return {object} The changes to save.
 * @spec openspec/changes/tmlo-mdto-metadata/tasks.md#3-frontend
 */
export function decideRestriction(supplement, restriction, accept) {
	const same = (entry) =>
		entry.type === restriction.type
		&& entry.grondslag === restriction.grondslag
		&& entry.source === restriction.source
	const kept = (supplement?.beperkingGebruik || []).filter((entry) => !same(entry))
	kept.push({ ...restriction, proposed: !accept, rejected: !accept })
	return { beperkingGebruik: kept }
}

/**
 * The restriction proposals the operator has not decided yet: the derived
 * ones not already stored confirmed.
 *
 * @param {object} status The status answer.
 * @return {object[]} The open restriction proposals.
 * @spec openspec/changes/tmlo-mdto-metadata/tasks.md#3-frontend
 */
export function openRestrictionProposals(status) {
	const stored = status?.supplement?.beperkingGebruik || []
	return (status?.proposals?.beperkingGebruik?.value || []).filter(
		(proposal) =>
			!stored.some(
				(entry) =>
					entry.type === proposal.type
					&& entry.grondslag === proposal.grondslag
					&& entry.source === proposal.source,
			),
	)
}
