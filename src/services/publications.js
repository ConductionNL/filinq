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
 * @spec openspec/changes/woo-metadata-worklist/specs/woo-metadata-worklist/spec.md#requirement-a-coordinator-fills-one-value-on-a-selection-req-wmw-002
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
