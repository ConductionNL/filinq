/**
 * Woo publications: the calls the pages make.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/specs/woo-publicatie-pipeline/spec.md
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

const base = () => generateUrl('/apps/filinq/api/publications')

/**
 * The publications the caller can see.
 *
 * @param {object} [options] `{ incomplete: true }` lists only records with missing Woo metadata
 * @return {Promise<{results: object[], platformAvailable: boolean}>}
 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-3.1
 */
export async function listPublications(options = {}) {
	if (options.incomplete !== true) {
		return (await axios.get(base())).data
	}

	return (await axios.get(base(), { params: { incomplete: 1 } })).data
}

/**
 * Set one metadata value on a selection of publications. Each record is
 * checked on its own; one past hand-off or not the caller's is skipped.
 *
 * @param {string[]} ids The records
 * @param {object} values One field and its value
 * @return {Promise<{updated: object[], skipped: Array<{uuid: string, reason: string}>}>}
 * @spec openspec/changes/archive/2026-11-25-woo-metadata-worklist/specs/woo-metadata-worklist/spec.md#requirement-a-coordinator-fills-one-value-on-a-selection-req-wmw-002
 */
export async function setMetadataMany(ids, values) {
	return (await axios.put(base() + '/metadata', { ids, values })).data
}

/**
 * One publication with its log.
 *
 * @param {string} id The record
 * @return {Promise<object>}
 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-3.1
 */
export async function getPublication(id) {
	return (await axios.get(base() + '/' + encodeURIComponent(id))).data
}

/**
 * Start a publication for a document.
 *
 * @param {number|string} fileId The document
 * @return {Promise<object>} The record.
 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-3.2
 */
export async function startPublication(fileId) {
	return (
		await axios.post(base(), {
			documentFileRef: String(fileId),
			subjectType: 'document',
		})
	).data
}

/**
 * Run a step on a publication.
 *
 * @param {string} id The record
 * @param {string} step readiness, metadata, handoff, withdraw or destruction-date
 * @param {object} body The body
 * @return {Promise<object>} The record.
 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-3.1
 */
export async function publicationStep(id, step, body = {}) {
	const url = base() + '/' + encodeURIComponent(id) + '/' + step
	const response =
		step === 'metadata'
			? await axios.put(url, body)
			: await axios.post(url, body)
	return response.data
}

/**
 * The Woo information categories, from OpenCatalogi's TOOI list.
 *
 * @return {Promise<Array<{code: string, label: string}>>}
 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-3.1
 */
export async function listCategories() {
	return (await axios.get(base() + '/categories')).data.results || []
}

/**
 * Ask for the published copy of a publication. The server answers 409 with the
 * download agreement to accept first when one is in force.
 *
 * @param {string} id The publication.
 * @return {Promise<{blob: Blob|null, agreement: object|null}>} The file, or the agreement to show.
 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/tasks.md#task-3.3
 */
export async function downloadPublication(id) {
	const response = await axios.get(`${base()}/${id}/download`, {
		responseType: 'blob',
		validateStatus: (status) => status === 200 || status === 409,
	})
	if (response.status === 409) {
		const decision = JSON.parse(await response.data.text())
		return { blob: null, agreement: decision }
	}
	return { blob: response.data, agreement: null }
}

/**
 * Record that the caller accepts the download agreement of one document.
 *
 * @param {string} document The document the agreement is on.
 * @param {string} version The version of the terms the caller read.
 * @return {Promise<object>} The decision.
 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/tasks.md#task-3.3
 */
export async function acceptDownloadAgreement(document, version) {
	const response = await axios.post(generateUrl('/apps/filinq/api/redaction/agreement/accept'), { document, version })
	return response.data
}
