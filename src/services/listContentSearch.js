// SPDX-License-Identifier: EUPL-1.2
// Copyright (C) 2026 Conduction B.V.
//
// Searching a list of the caller's documents by the words in them: ask the
// server which of the listed files hold the words, then order the list with
// the name matches first and the content matches after them, best first.

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

/** The most files one search asks about; the server refuses more. */
export const MAX_FILES = 500

/**
 * The file ids of the listed documents, folders left out, each once.
 *
 * @param {Array<object>} documents The listed documents.
 * @return {Array<number>} At most MAX_FILES file ids.
 * @spec openspec/changes/work-content-search/tasks.md#task-2.1
 */
export function listedFileIds(documents) {
	const ids = []
	for (const doc of documents || []) {
		const id = Number(doc && doc.fileId)
		if (doc && !doc.isFolder && Number.isInteger(id) && id > 0 && !ids.includes(id)) {
			ids.push(id)
		}
	}
	return ids.slice(0, MAX_FILES)
}

/**
 * Ask which of the listed documents hold the words.
 *
 * @param {string} words The words.
 * @param {Array<object>} documents The listed documents.
 * @return {Promise<{available: boolean, failed: boolean, hits: Array<{fileId: number, snippet: string}>, unread: Array<number>}>} The answer; `failed` when the server could not be asked.
 * @spec openspec/changes/work-content-search/tasks.md#task-2.1
 */
export async function searchListContent(words, documents) {
	const fileIds = listedFileIds(documents)
	const search = String(words ?? '').trim()
	if (search === '' || fileIds.length === 0) {
		return { available: true, failed: false, hits: [], unread: [] }
	}
	try {
		const { data } = await axios.post(generateUrl('/apps/filinq/api/documents/content-search'), { words: search, fileIds })
		return {
			available: data?.available !== false,
			failed: false,
			hits: (Array.isArray(data?.hits) ? data.hits : [])
				.map((hit) => ({ fileId: Number(hit.fileId), snippet: String(hit.snippet ?? '') }))
				.filter((hit) => fileIds.includes(hit.fileId)),
			unread: (Array.isArray(data?.unread) ? data.unread : []).map(Number).filter((id) => fileIds.includes(id)),
		}
	} catch {
		return { available: false, failed: true, hits: [], unread: [] }
	}
}

/**
 * The documents whose name holds the query, then those the content search
 * found, in the order it ranked them; a document is listed once.
 *
 * @param {Array<object>} documents The listed documents.
 * @param {string} query The words as typed.
 * @param {Array<{fileId: number, snippet: string}>} hits The content hits, best first.
 * @return {{rows: Array<object>, snippets: Object<string, string>}} The rows, and the snippet per content match by file id.
 * @spec openspec/changes/work-content-search/tasks.md#task-2.1
 */
export function nameThenContent(documents, query, hits) {
	const needle = String(query ?? '').trim().toLowerCase()
	const docs = documents || []
	const byName = docs.filter((doc) => needle !== '' && (doc.fileName || '').toLowerCase().includes(needle))
	const named = new Set(byName.map((doc) => Number(doc.fileId)))
	const byId = new Map(docs.map((doc) => [Number(doc.fileId), doc]))
	const snippets = {}
	const byContent = []
	for (const hit of hits || []) {
		const doc = byId.get(Number(hit.fileId))
		if (doc && !named.has(Number(hit.fileId)) && !(String(hit.fileId) in snippets)) {
			byContent.push(doc)
			snippets[String(hit.fileId)] = hit.snippet
		}
	}
	return { rows: [...byName, ...byContent], snippets }
}

/**
 * Have OpenRegister read the text of these files, one after the other, so
 * a content search can find them. OpenRegister checks each file is the
 * caller's to read.
 *
 * @param {Array<number>} fileIds The files.
 * @return {Promise<{read: number, failed: number}>} How many were read and how many not.
 * @spec openspec/changes/work-content-search/tasks.md#task-2.1
 */
export async function readListFiles(fileIds) {
	let read = 0
	let failed = 0
	for (const fileId of fileIds || []) {
		try {
			await axios.post(generateUrl('/apps/openregister/api/files/{id}/extract', { id: fileId }))
			read++
		} catch {
			failed++
		}
	}
	return { read, failed }
}
