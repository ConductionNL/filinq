// @vitest-environment jsdom
/**
 * The My Documents store's counterpart lookups, overview stats, folder
 * navigation and WebDAV deletes.
 *
 * Only HTTP is mocked (@nextcloud/axios); the router and auth resolve
 * through the shared stubs.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

import { __setCurrentUser } from '@nextcloud/auth'
import axios from '@nextcloud/axios'
import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { useMyDocumentsStore } from '../../src/store/modules/myDocuments.js'

const EMPTY_LISTING =
	'<?xml version="1.0"?><d:multistatus xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns"></d:multistatus>'

const deletes = []
const failingDeletes = new Set()

vi.mock('@nextcloud/axios', () => {
	const axios = vi.fn(async (config) => {
		if (config.method === 'DELETE') {
			deletes.push(config.url)
			if (failingDeletes.has(config.url)) {
				throw new Error('Locked')
			}
			return { data: '' }
		}
		return { data: EMPTY_LISTING }
	})
	axios.get = vi.fn(async () => ({ data: { results: [] } }))
	return { default: axios }
})

beforeEach(() => {
	setActivePinia(createPinia())
	deletes.length = 0
	failingDeletes.clear()
	vi.mocked(axios).mockClear()
	__setCurrentUser({ uid: 'alice', displayName: 'Alice' })
	vi.spyOn(console, 'error').mockImplementation(() => {})
})

afterEach(() => {
	vi.restoreAllMocks()
	__setCurrentUser({ uid: 'admin', displayName: 'Admin' })
})

/**
 * A store with a source (1) anonymised twice (10 older, 11 newer), a
 * second source (2) whose output (20) is absent, and an unlinked file (3).
 *
 * @return {object}
 */
function seeded() {
	const store = useMyDocumentsStore()
	store.anonymizationLinks = [
		{ sourceFileId: 1, anonymizedFileId: 11 },
		{ sourceFileId: 1, anonymizedFileId: 10 },
		{ sourceFileId: 2, anonymizedFileId: 20 },
	]
	store.documents = [
		{ fileId: 1, fileName: 'a.pdf', modified: 100, entityCount: 4, riskLevel: 'high' },
		{ fileId: 10, fileName: 'a_anonymized.pdf', modified: 200, entityCount: 1, riskLevel: 'low' },
		{ fileId: 11, fileName: 'a_anonymized (2).pdf', modified: 300, entityCount: 2, riskLevel: 'very_high' },
		{ fileId: 2, fileName: 'b.pdf', modified: 150, riskLevel: 'medium' },
		{ fileId: 3, fileName: 'c.pdf', modified: 50, entityCount: 3, riskLevel: 'high' },
		{ fileId: 40, fileName: 'Dossier', isFolder: true, modified: 10 },
	]
	return store
}

describe('counterpart lookups', () => {
	it('conceptFor finds the source of an output and nothing for an unlinked or missing doc', () => {
		const store = seeded()

		expect(store.conceptFor({ fileId: '10' })?.fileId).toBe(1)
		expect(store.conceptFor({ fileId: 3 })).toBeUndefined()
		expect(store.conceptFor(null)).toBeUndefined()
	})

	it('anonymizedFor prefers the newest present output', () => {
		const store = seeded()

		// The link map's last record for source 1 is the older output 10.
		expect(store.linkMaps.sourceToAnon.get(1)).toBe(10)
		expect(store.anonymizedFor({ fileId: 1 })?.fileId).toBe(11)
	})

	it('anonymizedFor falls back to the link when no output is listed', () => {
		const store = seeded()
		store.documents.push({ fileId: 20, fileName: 'b_anonymized.pdf', isFolder: true })

		// The only listing of 20 is a folder, which is never an output, so the link map answers.
		expect(store.anonymizedFor({ fileId: 2 })?.fileId).toBe(20)
	})

	it('anonymizedFor answers nothing for an unlinked or missing doc', () => {
		const store = seeded()

		expect(store.anonymizedFor({ fileId: 3 })).toBeUndefined()
		expect(store.anonymizedFor(undefined)).toBeUndefined()
	})
})

describe('documentStats', () => {
	it('counts only the visible documents', () => {
		const store = seeded()

		// Visible: 11 (newest output), 2 (output absent), 3 (unlinked) and the folder.
		expect(store.visibleDocuments.map((d) => d.fileId)).toEqual([11, 2, 3, 40])
		expect(store.documentStats).toEqual({ total: 4, entitiesDetected: 5, highRisk: 2 })
	})
})

describe('navigation', () => {
	it('builds breadcrumbs below the DocuDesk root', () => {
		const store = useMyDocumentsStore()

		store.navigateTo('/DocuDesk/Projects/Woo 12')

		expect(store.currentPath).toBe('/DocuDesk/Projects/Woo 12')
		expect(store.breadcrumbs).toEqual([
			{ name: 'DocuDesk', path: '/DocuDesk' },
			{ name: 'Projects', path: '/DocuDesk/Projects' },
			{ name: 'Woo 12', path: '/DocuDesk/Projects/Woo 12' },
		])
	})

	it('openFolder lists the subfolder and moves into it', async () => {
		const store = useMyDocumentsStore()

		await store.openFolder('Projects')

		expect(axios).toHaveBeenCalledWith(expect.objectContaining({
			method: 'PROPFIND',
			url: 'http://localhost/remote.php/dav/files/alice/DocuDesk/Projects',
		}))
		expect(store.currentPath).toBe('/DocuDesk/Projects')
		expect(store.error).toBeNull()
	})
})

describe('deleteDocument', () => {
	it('deletes the encoded path through WebDAV and relists the folder', async () => {
		const store = useMyDocumentsStore()

		await store.deleteDocument('Report #1.pdf')

		expect(deletes).toEqual(['http://localhost/remote.php/dav/files/alice/DocuDesk/Report%20%231.pdf'])
		expect(axios).toHaveBeenLastCalledWith(expect.objectContaining({ method: 'PROPFIND' }))
	})

	it('refuses without a signed-in user', async () => {
		__setCurrentUser(null)
		const store = useMyDocumentsStore()

		await expect(store.deleteDocument('a.pdf')).rejects.toThrow('User not authenticated')
		expect(deletes).toEqual([])
	})
})

describe('deleteDocuments', () => {
	it('deletes each name, relists once and returns the ones that failed', async () => {
		failingDeletes.add('http://localhost/remote.php/dav/files/alice/DocuDesk/b.pdf')
		const store = useMyDocumentsStore()

		const failed = await store.deleteDocuments(['a.pdf', 'b.pdf', 'c d.pdf'])

		expect(failed).toEqual(['b.pdf'])
		expect(deletes).toEqual([
			'http://localhost/remote.php/dav/files/alice/DocuDesk/a.pdf',
			'http://localhost/remote.php/dav/files/alice/DocuDesk/b.pdf',
			'http://localhost/remote.php/dav/files/alice/DocuDesk/c%20d.pdf',
		])
		const propfinds = vi.mocked(axios).mock.calls.filter(([config]) => config.method === 'PROPFIND')
		expect(propfinds).toHaveLength(1)
	})

	it('refuses without a signed-in user', async () => {
		__setCurrentUser(null)
		const store = useMyDocumentsStore()

		await expect(store.deleteDocuments(['a.pdf'])).rejects.toThrow('User not authenticated')
		expect(deletes).toEqual([])
	})
})
