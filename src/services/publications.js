/**
 * Woo publications: the calls the pages make.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/woo-publicatie-pipeline/specs/woo-publicatie-pipeline/spec.md
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

const base = () => generateUrl('/apps/filinq/api/publications')

/**
 * The publications the caller can see.
 *
 * @return {Promise<{results: object[], platformAvailable: boolean}>}
 * @spec openspec/changes/woo-publicatie-pipeline/tasks.md#task-3.1
 */
export async function listPublications() {
	return (await axios.get(base())).data
}

/**
 * One publication with its log.
 *
 * @param {string} id The record
 * @return {Promise<object>}
 * @spec openspec/changes/woo-publicatie-pipeline/tasks.md#task-3.1
 */
export async function getPublication(id) {
	return (await axios.get(base() + '/' + encodeURIComponent(id))).data
}

/**
 * Start a publication for a document.
 *
 * @param {number|string} fileId The document
 * @return {Promise<object>} The record.
 * @spec openspec/changes/woo-publicatie-pipeline/tasks.md#task-3.2
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
 * @spec openspec/changes/woo-publicatie-pipeline/tasks.md#task-3.1
 */
export async function publicationStep(id, step, body = {}) {
	const url = base() + '/' + encodeURIComponent(id) + '/' + step
	const response =
		step === 'metadata'
			? await axios.put(url, body)
			: await axios.post(url, body)
	return response.data
}
