// @vitest-environment jsdom
/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * My documents asks for the comment count in the listing it already makes,
 * and each row carries its number of notes: the count Nextcloud answered, 0
 * when it answered none.
 *
 * @spec openspec/changes/work-document-notes/specs/document-notes/spec.md#requirement-my-documents-shows-which-documents-have-notes-req-wdn-004
 */

import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { useMyDocumentsStore } from '../../src/store/modules/myDocuments.js'
import { __setCurrentUser } from './stubs/nextcloud-auth.js'

const calls = []
const routes = {}

vi.mock('@nextcloud/axios', () => {
	const respond = async (config) => {
		calls.push({ method: config.method, url: config.url, body: config.data || '' })
		const route = routes[config.method + ' ' + config.url]
		if (route === undefined) {
			throw Object.assign(new Error('no route ' + config.url), { response: { status: 500 } })
		}
		return { data: route }
	}
	const axios = vi.fn(respond)
	axios.get = vi.fn((url, opts) => respond({ method: 'GET', url, ...opts }))
	return { default: axios }
})

const DAV = 'http://localhost/remote.php/dav/files/admin'
const HREF = '/remote.php/dav/files/admin'

/**
 * One PROPFIND response entry for a file.
 *
 * @param {string} name The file name.
 * @param {number} id The file id.
 * @param {string|null} count The comments count, or null to leave it out.
 * @return {string} The XML.
 */
function file(name, id, count) {
	const notes = count === null ? '' : `<oc:comments-count>${count}</oc:comments-count>`
	return `<d:response><d:href>${HREF}/DocuDesk/${name}</d:href><d:propstat><d:prop><d:resourcetype/><d:getcontenttype>application/pdf</d:getcontenttype><oc:fileid>${id}</oc:fileid>${notes}</d:prop></d:propstat></d:response>`
}

beforeEach(() => {
	setActivePinia(createPinia())
	calls.length = 0
	for (const k of Object.keys(routes)) delete routes[k]
	__setCurrentUser({ uid: 'admin', displayName: 'Admin' })
	vi.spyOn(console, 'error').mockImplementation(() => {})
})

describe('myDocuments notes count', () => {
	it('asks for the count in the listing and puts it on each row', async () => {
		routes['GET /index.php/apps/openregister/api/objects/filinq/anonymizationLink'] = { results: [] }
		routes[`PROPFIND ${DAV}/DocuDesk`] = '<?xml version="1.0"?><d:multistatus xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns">'
			+ `<d:response><d:href>${HREF}/DocuDesk/</d:href><d:propstat><d:prop><d:resourcetype><d:collection/></d:resourcetype></d:prop></d:propstat></d:response>`
			+ file('contract.pdf', 11, '2')
			+ file('brief.pdf', 12, '0')
			+ file('oud.pdf', 13, null)
			+ '</d:multistatus>'

		const store = useMyDocumentsStore()
		await store.fetchDocuments()

		const propfinds = calls.filter((c) => c.method === 'PROPFIND')
		expect(propfinds).toHaveLength(1)
		expect(propfinds[0].body).toContain('<oc:comments-count />')
		expect(store.documents.map((d) => [d.fileName, d.notesCount])).toEqual([
			['contract.pdf', 2],
			['brief.pdf', 0],
			['oud.pdf', 0],
		])
	})
})
