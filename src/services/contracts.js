/**
 * The contract pages' calls. Contract CRUD goes to OpenRegister's object
 * API (its RBAC decides who reads and writes); renew, terminate, suggestions
 * and the signing link go to the app's action routes.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/contract-lifecycle-management/tasks.md#3-1
 */

import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'

const REGISTER = 'filinq'
const SCHEMA = 'documentContract'

const objects = () =>
	generateUrl(`/apps/openregister/api/objects/${REGISTER}/${SCHEMA}`)
const object = (id) => `${objects()}/${encodeURIComponent(id)}`
const action = (id, path) =>
	generateUrl(
		`/apps/filinq/api/contracts/${encodeURIComponent(id)}/${path}`,
	)

/**
 * The server's refusals, as a person reads them.
 *
 * @return {object} Server message to shown sentence.
 */
function refusals() {
	return {
		'A termination needs a reason.': t(
			'filinq',
			'Give a reason for ending the contract.',
		),
		'Only an active contract can be renewed or terminated.': t(
			'filinq',
			'Only an active contract can be renewed or ended.',
		),
		'This suggestion was already decided.': t(
			'filinq',
			'Somebody already decided on this suggestion.',
		),
		signing_request_not_found: t(
			'filinq',
			'That signing request is not there, or you cannot see it.',
		),
	}
}

/**
 * Run a call and answer { ok, data } or { ok: false, status, error }.
 *
 * @param {() => Promise<{data: object}>} call The axios call.
 * @return {Promise<object>} The outcome.
 */
async function outcome(call) {
	try {
		const { data } = await call()
		return { ok: true, data }
	} catch (error) {
		const message = error.response?.data?.error
		return {
			ok: false,
			status: error.response?.status || 0,
			error:
				refusals()[message]
				|| t('filinq', 'The contract could not be changed. Try again later.'),
		}
	}
}

/**
 * The id of an object from the object API.
 *
 * @param {object} contract The contract.
 * @return {string} Its id.
 * @spec openspec/changes/contract-lifecycle-management/tasks.md#3-1
 */
export function contractId(contract) {
	return String(contract?.id ?? contract?.['@self']?.id ?? contract?.uuid ?? '')
}

/**
 * The fields of a contract as they are saved: the whole object, without the
 * envelope the object API adds.
 *
 * @param {object} contract The contract.
 * @return {object} The fields.
 */
function fields(contract) {
	const copy = { ...contract }
	delete copy['@self']
	delete copy.id
	delete copy.uuid
	return copy
}

/**
 * Every contract the caller can read.
 *
 * @return {Promise<object>} The outcome, data the contracts.
 * @spec openspec/changes/contract-lifecycle-management/tasks.md#3-2
 */
export async function listContracts() {
	const result = await outcome(() =>
		axios.get(objects(), { params: { _limit: 1000 } }),
	)
	if (result.ok) {
		result.data = Array.isArray(result.data)
			? result.data
			: result.data?.results || []
	}
	return result
}

/**
 * One contract.
 *
 * @param {string} id The contract.
 * @return {Promise<object>} The outcome.
 * @spec openspec/changes/contract-lifecycle-management/tasks.md#3-1
 */
export function getContract(id) {
	return outcome(() => axios.get(object(id)))
}

/**
 * Save a whole contract with some fields changed.
 *
 * @param {object} contract The contract as read.
 * @param {object} changes The fields to change.
 * @return {Promise<object>} The outcome.
 */
function saveContract(contract, changes) {
	return outcome(() =>
		axios.put(object(contractId(contract)), {
			...fields(contract),
			...changes,
		}),
	)
}

/**
 * Put a draft contract in force. OpenRegister's lifecycle refuses it from any
 * other state.
 *
 * @param {object} contract The contract.
 * @return {Promise<object>} The outcome.
 * @spec openspec/changes/contract-lifecycle-management/tasks.md#3-1
 */
export function activateContract(contract) {
	return saveContract(contract, { status: 'active' })
}

/**
 * Add files to the contract's documents, each once.
 *
 * @param {object} contract The contract.
 * @param {Array<string|number>} fileIds The files.
 * @return {Promise<object>} The outcome.
 * @spec openspec/changes/contract-lifecycle-management/tasks.md#3-1
 */
export function attachDocuments(contract, fileIds) {
	const documents = [
		...new Set([...(contract.documents || []), ...fileIds].map(String)),
	]
	return saveContract(contract, { documents })
}

/**
 * Renew: the contract becomes renewed and a linked draft successor exists.
 *
 * @param {string} id The contract.
 * @return {Promise<object>} The outcome, data { contract, successor }.
 * @spec openspec/changes/contract-lifecycle-management/tasks.md#3-1
 */
export function renewContract(id) {
	return outcome(() => axios.post(action(id, 'renew')))
}

/**
 * End a contract early, with the reason.
 *
 * @param {string} id The contract.
 * @param {string} reason Why.
 * @return {Promise<object>} The outcome.
 * @spec openspec/changes/contract-lifecycle-management/tasks.md#3-1
 */
export function terminateContract(id, reason) {
	return outcome(() => axios.post(action(id, 'terminate'), { reason }))
}

/**
 * Read the contract's documents for suggested terms.
 *
 * @param {string} id The contract.
 * @return {Promise<object>} The outcome, data { contract, added, enabled }.
 * @spec openspec/changes/contract-lifecycle-management/tasks.md#3-1
 */
export function suggestTerms(id) {
	return outcome(() => axios.post(action(id, 'suggestions')))
}

/**
 * Accept or reject one suggestion.
 *
 * @param {string} id The contract.
 * @param {number} index The suggestion.
 * @param {string} decision accepted or rejected.
 * @return {Promise<object>} The outcome, data the contract.
 * @spec openspec/changes/contract-lifecycle-management/tasks.md#3-1
 */
export function decideSuggestion(id, index, decision) {
	return outcome(() =>
		axios.put(action(id, `suggestions/${index}`), { decision }),
	)
}

/**
 * The parties, with names from their contacts.
 *
 * @param {string} id The contract.
 * @return {Promise<object>} The outcome, data the parties.
 * @spec openspec/changes/contract-lifecycle-management/tasks.md#3-1
 */
export function contractParties(id) {
	return outcome(() => axios.get(action(id, 'parties')))
}

/**
 * Record a signing request on the contract; answers its status, and links
 * the signed document once it completed.
 *
 * @param {string} id The contract.
 * @param {string} signingRequestId The request.
 * @return {Promise<object>} The outcome, data { contract, signingRequest }.
 * @spec openspec/changes/contract-lifecycle-management/tasks.md#3-1
 */
export function linkSigningRequest(id, signingRequestId) {
	return outcome(() => axios.post(action(id, 'signing'), { signingRequestId }))
}

/**
 * The templates a document can be generated from.
 *
 * @return {Promise<object>} The outcome, data the templates.
 * @spec openspec/changes/contract-lifecycle-management/tasks.md#3-1
 */
export async function listTemplates() {
	const result = await outcome(() =>
		axios.get(generateUrl(`/apps/openregister/api/objects/${REGISTER}/template`)),
	)
	if (result.ok) {
		result.data = Array.isArray(result.data)
			? result.data
			: result.data?.results || []
	}
	return result
}

/**
 * Generate a PDF from a template with the contract as its data, stored as a
 * file; answers the file id.
 *
 * @param {string} templateId The template.
 * @param {string} id The contract.
 * @param {string} filename The file name, without extension.
 * @return {Promise<object>} The outcome, data { fileId, name }.
 * @spec openspec/changes/contract-lifecycle-management/tasks.md#3-1
 */
export async function generateDocument(templateId, id, filename) {
	const result = await outcome(() =>
		axios.post(generateUrl('/apps/filinq/api/documents/generate'), {
			templateId,
			dataRefs: [{ register: REGISTER, schema: SCHEMA, id }],
			filename,
			options: { format: 'pdf', output: { mode: 'files' } },
		}),
	)
	if (result.ok) {
		result.data = { ...result.data, fileId: String(result.data?.fileId ?? '') }
	}
	return result
}
