// @vitest-environment jsdom
/**
 * Golden: the My Documents listing, the dossier annotation, the recent
 * anonymized list and the orphaned-output detection, recorded on the
 * implementation with three inline PROPFIND parsers.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec exclude characterization test of existing listing behaviour
 */

import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, it, vi } from 'vitest'
import { useMyDocumentsStore } from '../../src/store/modules/myDocuments.js'
import { golden } from './golden/golden.js'
import { __setCurrentUser } from './stubs/nextcloud-auth.js'

const calls = []
const routes = {}

/**
 * Build one PROPFIND response element.
 *
 * @param {object} e Entry fields.
 * @return {string}
 */
function entry(e) {
	const props = [
		e.folder
			? '<d:resourcetype><d:collection/></d:resourcetype>'
			: '<d:resourcetype/>',
		e.type === undefined ? '' : `<d:getcontenttype>${e.type}</d:getcontenttype>`,
		e.length === undefined
			? ''
			: `<d:getcontentlength>${e.length}</d:getcontentlength>`,
		e.size === undefined ? '' : `<oc:size>${e.size}</oc:size>`,
		e.mod === undefined ? '' : `<d:getlastmodified>${e.mod}</d:getlastmodified>`,
		e.id === undefined ? '' : `<oc:fileid>${e.id}</oc:fileid>`,
	].join('')
	return `<d:response><d:href>${e.href}</d:href><d:propstat><d:prop>${props}</d:prop></d:propstat></d:response>`
}

/**
 * Build a multistatus body.
 *
 * @param {object[]} entries Entries.
 * @return {string}
 */
function multistatus(entries) {
	return (
		'<?xml version="1.0"?><d:multistatus xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns">'
		+ entries.map(entry).join('')
		+ '</d:multistatus>'
	)
}

vi.mock('@nextcloud/axios', () => {
	const respond = async (config) => {
		calls.push({
			method: config.method,
			url: config.url,
			depth: config.headers?.Depth ?? null,
			body: (config.data || '').replace(/\s+/g, ' ').trim(),
			params: config.params ?? null,
		})
		const route = routes[config.method + ' ' + config.url]
		if (route instanceof Error) {
			throw route
		}
		if (route === undefined) {
			throw Object.assign(new Error('no route ' + config.url), {
				response: { status: 500 },
			})
		}
		return { data: route }
	}
	const axios = vi.fn(respond)
	axios.get = vi.fn((url, opts) => respond({ method: 'GET', url, ...opts }))
	return { default: axios }
})

const DAV = 'http://localhost/remote.php/dav/files/admin'
const HREF = '/remote.php/dav/files/admin'
const LINKS = 'GET /index.php/apps/openregister/api/objects/filinq/anonymizationLink'

/**
 * Snapshot the observable store state.
 *
 * @param {object} store The store.
 * @return {object}
 */
function snapshot(store) {
	return {
		documents: store.documents,
		total: store.total,
		loading: store.loading,
		error: store.error,
		currentPath: store.currentPath,
		breadcrumbs: store.breadcrumbs,
		visible: store.visibleDocuments.map((d) => d.fileId),
		orphans: [...store.orphanedOutputIds],
		calls: [...calls],
	}
}

beforeEach(() => {
	setActivePinia(createPinia())
	calls.length = 0
	for (const k of Object.keys(routes)) delete routes[k]
	__setCurrentUser({ uid: 'admin', displayName: 'Admin' })
	vi.spyOn(console, 'error').mockImplementation(() => {})
})

describe('myDocuments golden', () => {
	it('lists a folder, annotates its dossiers and hides outputs', async () => {
		routes[LINKS] = {
			results: [
				{ sourceFileId: 11, anonymizedFileId: 12 },
				{
					sourceFileId: 13,
					anonymizedFileId: 14,
					sourceFileName: 'gone.pdf',
				},
				{ sourceFileId: 11, anonymizedFileId: 15 },
				{ sourceFileId: 'x', anonymizedFileId: 16 },
				{ sourceFileId: 17, anonymizedFileId: 17 },
			],
		}
		routes[`PROPFIND ${DAV}/DocuDesk/Sub%3F%20A`] = multistatus([
			{ href: `${HREF}/DocuDesk/Sub%3F%20A/`, folder: true },
			{
				href: `${HREF}/DocuDesk/Sub%3F%20A/Dossier/`,
				folder: true,
				id: 20,
				mod: 'Mon, 01 Jan 2024 10:00:00 GMT',
			},
			{ href: `${HREF}/DocuDesk/Sub%3F%20A/Broken/`, folder: true, id: 21 },
			{
				href: `${HREF}/DocuDesk/Sub%3F%20A/Empty/`,
				folder: true,
				id: 22,
				type: 'x/dir',
			},
			{
				href: `${HREF}/DocuDesk/Sub%3F%20A/a.pdf`,
				type: 'application/pdf',
				length: 100,
				id: 11,
				mod: 'Mon, 01 Jan 2024 09:00:00 GMT',
			},
			{
				href: `${HREF}/DocuDesk/Sub%3F%20A/a_anonymized.pdf`,
				length: '',
				size: 55,
				id: 12,
				mod: 'Tue, 02 Jan 2024 09:00:00 GMT',
			},
			{
				href: `${HREF}/DocuDesk/Sub%3F%20A/a_anonymized_old.pdf`,
				id: 15,
				mod: 'Sun, 31 Dec 2023 09:00:00 GMT',
			},
			{
				href: `${HREF}/DocuDesk/Sub%3F%20A/gone_anonymized.pdf`,
				id: 14,
				mod: 'Mon, 01 Jan 2024 08:00:00 GMT',
			},
			{
				href: `${HREF}/DocuDesk/Sub%3F%20A/gone.pdf`,
				id: 30,
				mod: 'Wed, 03 Jan 2024 08:00:00 GMT',
			},
			{
				href: `${HREF}/DocuDesk/Sub%3F%20A/caf%C3%A9.txt`,
				type: 'text/plain',
				size: 7,
			},
			{ href: '/', id: 99 },
		])
		routes[`PROPFIND ${DAV}/DocuDesk/Sub%3F%20A/Dossier`] = multistatus([
			{ href: 'self', folder: true, id: 20 },
			{ href: 'c1', id: 11 },
			{ href: 'c2', id: 12 },
			{ href: 'c3', folder: true, id: 40 },
			{ href: 'c4', id: 15 },
		])
		routes[`PROPFIND ${DAV}/DocuDesk/Sub%3F%20A/Broken`] = new Error('boom')
		routes[`PROPFIND ${DAV}/DocuDesk/Sub%3F%20A/Empty`] = multistatus([
			{ href: 'self', folder: true, id: 22 },
			{ href: 'c1', id: 12 },
		])
		const store = useMyDocumentsStore()
		await store.fetchDocuments('/DocuDesk/Sub? A')
		const first = snapshot(store)
		calls.length = 0
		await store.fetchDocuments()
		golden('myDocumentsFetch', { first, again: snapshot(store) })
	})

	it('records the failures of a listing', async () => {
		const store = useMyDocumentsStore()
		routes[LINKS] = new Error('links down')
		await store.fetchDocuments()
		const noListing = snapshot(store)
		calls.length = 0
		__setCurrentUser(null)
		await store.fetchDocuments('/DocuDesk/X')
		golden('myDocumentsFetchErrors', { noListing, noUser: snapshot(store) })
	})

	it('lists the recent anonymized files and dossiers', async () => {
		const store = useMyDocumentsStore()
		const url = `PROPFIND ${DAV}/DocuDesk`
		routes[url] = multistatus([
			{ href: `${HREF}/DocuDesk/`, folder: true },
			{
				href: `${HREF}/DocuDesk/D1/`,
				folder: true,
				id: 1,
				mod: 'Mon, 01 Jan 2024 10:00:00 GMT',
			},
			{
				href: `${HREF}/DocuDesk/D1/x_anonymized.pdf`,
				type: 'application/pdf',
				length: 9,
				id: 2,
				mod: 'Tue, 02 Jan 2024 10:00:00 GMT',
			},
			{
				href: `${HREF}/DocuDesk/D1/plain.pdf`,
				id: 3,
				mod: 'Wed, 03 Jan 2024 10:00:00 GMT',
			},
			{
				href: `${HREF}/DocuDesk/y_anonymized.docx`,
				size: 4,
				id: 4,
				mod: 'Thu, 04 Jan 2024 10:00:00 GMT',
			},
			{ href: '/elsewhere/z_anonymized%20b.txt', id: 5 },
			{
				href: `${HREF}/DocuDesk/D2/`,
				folder: true,
				id: 6,
				mod: 'Fri, 05 Jan 2024 10:00:00 GMT',
			},
		])
		const all = await store.fetchRecentAnonymized(10)
		const two = await store.fetchRecentAnonymized(2)
		const byDefault = await store.fetchRecentAnonymized()
		routes[url] = Object.assign(new Error('missing'), {
			response: { status: 404 },
		})
		const missing = await store.fetchRecentAnonymized()
		routes[url] = Object.assign(new Error('denied'), {
			response: { status: 403 },
		})
		let denied = null
		try {
			await store.fetchRecentAnonymized()
		} catch (err) {
			denied = err.message
		}
		__setCurrentUser(null)
		let noUser = null
		try {
			await store.fetchRecentAnonymized()
		} catch (err) {
			noUser = err.message
		}
		golden('myDocumentsRecent', {
			all,
			two,
			byDefault,
			missing,
			denied,
			noUser,
			calls: [...calls],
		})
	})

	it('finds the outputs stranded by a re-upload', () => {
		const store = useMyDocumentsStore()
		const cases = [
			{
				links: [
					{
						sourceFileId: 1,
						anonymizedFileId: 2,
						sourceFileName: 'a.pdf',
					},
					{
						sourceFileId: 3,
						anonymizedFileId: 4,
						sourceFileName: 'b.pdf',
					},
					{
						sourceFileId: 5,
						anonymizedFileId: 6,
						sourceFileName: 'c.pdf',
					},
					{ sourceFileId: 7, anonymizedFileId: 8 },
					{
						sourceFileId: 9,
						anonymizedFileId: 10,
						sourceFileName: 'e.pdf',
					},
					{
						sourceFileId: 11,
						anonymizedFileId: 'n',
						sourceFileName: 'f.pdf',
					},
					{
						sourceFileId: 50,
						anonymizedFileId: 51,
						sourceFileName: 'g.pdf',
					},
				],
				documents: [
					{
						fileId: 2,
						fileName: 'a_anonymized.pdf',
						modified: 10,
						isAnonymized: true,
					},
					{ fileId: 20, fileName: 'a.pdf', modified: 11 },
					{ fileId: 21, fileName: 'a.pdf', modified: 5 },
					{
						fileId: 4,
						fileName: 'b_anonymized.pdf',
						modified: 10,
						isAnonymized: true,
					},
					{ fileId: 22, fileName: 'b.pdf', modified: 10 },
					{ fileId: 26, fileName: 'b.pdf', modified: 12 },
					{
						fileId: 51,
						fileName: 'g.pdf',
						modified: 5,
						isAnonymized: true,
					},
					{
						fileId: 52,
						fileName: 'g.pdf',
						modified: 9,
						isAnonymized: true,
					},
					{ fileId: 6, fileName: 'c_anonymized.pdf', modified: 10 },
					{ fileId: 5, fileName: 'c.pdf', modified: 1 },
					{ fileId: 8, fileName: 'd_anonymized.pdf', modified: 1 },
					{ fileId: 10, fileName: 'e_anonymized.pdf' },
					{ fileId: 23, fileName: 'e.pdf', modified: 1 },
					{ fileId: 24, fileName: 'e.pdf', isFolder: true, modified: 99 },
					{
						fileId: 25,
						fileName: 'e_anonymized.pdf',
						isAnonymized: true,
						modified: 99,
					},
					{ fileId: '2', fileName: 'dup' },
				],
			},
			{ links: [], documents: [{ fileId: 1, fileName: 'x' }] },
		]
		golden(
			'myDocumentsOrphans',
			cases.map((c) => {
				store.anonymizationLinks = c.links
				store.documents = c.documents
				return {
					orphans: [...store.orphanedOutputIds],
					visible: store.visibleDocuments.map((d) => d.fileId),
				}
			}),
		)
	})
})
