// @vitest-environment jsdom
/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * My documents opens the viewer on the document a `file` route parameter
 * names (work-content-search REQ-WCS-004): the id is read strictly and
 * resolved through a WebDAV SEARCH in the caller's own files.
 *
 * @spec openspec/changes/work-content-search/specs/document-content-search/spec.md#requirement-unified-search-finds-document-content-through-openregister-req-wcs-004
 */

import { beforeEach, describe, expect, it, vi } from 'vitest'
import { __setCurrentUser } from './stubs/nextcloud-auth.js'

const axios = vi.hoisted(() => vi.fn())
vi.mock('@nextcloud/axios', () => ({ default: axios }))

const {
	descriptorFromSearch,
	fileIdFromQuery,
	fileSearchBody,
	openLinkedFile,
	resolveFileById,
} = await import('../../src/services/fileDeepLink.js')

/**
 * A SEARCH multistatus answer with the given entries.
 *
 * @param {Array<object>} entries The entries.
 * @return {string} The XML.
 */
function multistatus(entries) {
	const responses = entries.map((e) => `<d:response>
		<d:href>${e.href}</d:href>
		<d:propstat><d:prop>
			<d:displayname>${e.name ?? ''}</d:displayname>
			<d:getcontenttype>${e.mime ?? ''}</d:getcontenttype>
			<d:resourcetype>${e.folder ? '<d:collection/>' : ''}</d:resourcetype>
			<oc:fileid>${e.id}</oc:fileid>
		</d:prop><d:status>HTTP/1.1 200 OK</d:status></d:propstat>
	</d:response>`).join('')
	return `<?xml version="1.0"?><d:multistatus xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns">${responses}</d:multistatus>`
}

describe('the file route parameter', () => {
	it('names a positive whole file id', () => {
		expect(fileIdFromQuery({ file: '42' })).toBe(42)
		expect(fileIdFromQuery({ file: ' 7 ' })).toBe(7)
		expect(fileIdFromQuery({ file: ['9', '10'] })).toBe(9)
	})

	it('names nothing for anything else', () => {
		expect(fileIdFromQuery({})).toBeNull()
		expect(fileIdFromQuery(undefined)).toBeNull()
		expect(fileIdFromQuery({ file: '0' })).toBeNull()
		expect(fileIdFromQuery({ file: '-3' })).toBeNull()
		expect(fileIdFromQuery({ file: '4.5' })).toBeNull()
		expect(fileIdFromQuery({ file: '12abc' })).toBeNull()
		expect(fileIdFromQuery({ file: '99999999999999999999' })).toBeNull()
	})
})

describe('the SEARCH request', () => {
	it('looks in the user own files for that one id', () => {
		const body = fileSearchBody('anna smit', 42)
		expect(body).toContain('<d:href>/files/anna%20smit</d:href>')
		expect(body).toContain('<d:literal>42</d:literal>')
		expect(body).toContain('<d:nresults>1</d:nresults>')
	})
})

describe('the SEARCH answer', () => {
	it('gives the viewer descriptor of the file', () => {
		const xml = multistatus([{
			href: '/remote.php/dav/files/anna/DocuDesk/Besluit%202026.pdf',
			name: 'Besluit 2026.pdf',
			mime: 'application/pdf',
			id: 42,
		}])
		expect(descriptorFromSearch(xml, 'anna', 42)).toEqual({
			fileId: 42,
			fileName: 'Besluit 2026.pdf',
			mimeType: 'application/pdf',
			path: '/DocuDesk/Besluit 2026.pdf',
		})
	})

	it('takes the name from the path when the answer has none', () => {
		const xml = multistatus([{ href: '/remote.php/dav/files/anna/a.txt', id: 5, mime: 'text/plain' }])
		expect(descriptorFromSearch(xml, 'anna', 5)?.fileName).toBe('a.txt')
	})

	it('gives nothing for a folder, another id, another user or no match', () => {
		const folder = multistatus([{ href: '/remote.php/dav/files/anna/DocuDesk/', id: 3, folder: true }])
		const other = multistatus([{ href: '/remote.php/dav/files/anna/b.pdf', id: 8 }])
		const elsewhere = multistatus([{ href: '/remote.php/dav/files/bob/b.pdf', id: 8 }])
		expect(descriptorFromSearch(folder, 'anna', 3)).toBeNull()
		expect(descriptorFromSearch(other, 'anna', 9)).toBeNull()
		expect(descriptorFromSearch(elsewhere, 'anna', 8)).toBeNull()
		expect(descriptorFromSearch(multistatus([]), 'anna', 8)).toBeNull()
		expect(descriptorFromSearch('', 'anna', 8)).toBeNull()
		expect(descriptorFromSearch(undefined, 'anna', 8)).toBeNull()
	})

	it('keeps a segment that does not decode', () => {
		const xml = multistatus([{ href: '/remote.php/dav/files/anna/100%.pdf', id: 6 }])
		expect(descriptorFromSearch(xml, 'anna', 6)?.path).toBe('/100%.pdf')
	})
})

describe('resolving a file id', () => {
	beforeEach(() => {
		axios.mockReset()
		__setCurrentUser({ uid: 'anna' })
	})

	it('sends one SEARCH and answers the file', async () => {
		axios.mockResolvedValue({
			data: multistatus([{ href: '/remote.php/dav/files/anna/x.pdf', name: 'x.pdf', mime: 'application/pdf', id: 11 }]),
		})
		const file = await resolveFileById(11)
		expect(file).toEqual({ fileId: 11, fileName: 'x.pdf', mimeType: 'application/pdf', path: '/x.pdf' })
		expect(axios).toHaveBeenCalledTimes(1)
		const request = axios.mock.calls[0][0]
		expect(request.method).toBe('SEARCH')
		expect(request.url).toBe('http://localhost/remote.php/dav')
		expect(request.data).toContain('<d:literal>11</d:literal>')
	})

	it('answers nothing when the search fails', async () => {
		axios.mockRejectedValue(new Error('403'))
		expect(await resolveFileById(11)).toBeNull()
	})

	it('asks nothing without a user or an id', async () => {
		__setCurrentUser(null)
		expect(await resolveFileById(11)).toBeNull()
		__setCurrentUser({ uid: 'anna' })
		expect(await resolveFileById(null)).toBeNull()
		expect(axios).not.toHaveBeenCalled()
	})
})

describe('opening the linked document', () => {
	beforeEach(() => {
		axios.mockReset()
		__setCurrentUser({ uid: 'anna' })
	})

	it('opens the viewer on the file the query names', async () => {
		axios.mockResolvedValue({
			data: multistatus([{ href: '/remote.php/dav/files/anna/x.pdf', name: 'x.pdf', mime: 'application/pdf', id: 11 }]),
		})
		const viewer = { currentFile: null, open: vi.fn() }
		expect(await openLinkedFile({ file: '11' }, viewer)).toBe('opened')
		expect(viewer.open).toHaveBeenCalledWith({ fileId: 11, fileName: 'x.pdf', mimeType: 'application/pdf', path: '/x.pdf' })
	})

	it('says missing and opens nothing for a file the caller has not', async () => {
		axios.mockResolvedValue({ data: multistatus([]) })
		const viewer = { currentFile: null, open: vi.fn() }
		expect(await openLinkedFile({ file: '11' }, viewer)).toBe('missing')
		expect(viewer.open).not.toHaveBeenCalled()
	})

	it('does nothing without a file or when it is already open', async () => {
		const viewer = { currentFile: { fileId: 11 }, open: vi.fn() }
		expect(await openLinkedFile({}, viewer)).toBe('none')
		expect(await openLinkedFile(undefined, viewer)).toBe('none')
		expect(await openLinkedFile({ file: '11' }, viewer)).toBe('none')
		expect(axios).not.toHaveBeenCalled()
		expect(viewer.open).not.toHaveBeenCalled()
	})
})
