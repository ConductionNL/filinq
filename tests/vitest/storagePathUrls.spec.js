/**
 * The links built from a Nextcloud storage path: the Files app deep link,
 * the raw WebDAV URL and the per-segment encoded WebDAV URL, including a path
 * that does not live under a user's `files` folder.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 */

import { describe, expect, it, vi } from 'vitest'

vi.mock('@nextcloud/router', () => ({
	generateUrl: (url, params) => (params ? `${url}|${JSON.stringify(params)}` : `/index.php${url}`),
	generateRemoteUrl: (service) => `http://localhost/remote.php/${service}`,
}))

const {
	encodedWebdavDownloadUrl,
	filesAppLink,
	webdavDownloadUrl,
} = await import('../../src/services/storagePathUrls.js')

describe('filesAppLink', () => {
	it('opens the folder and scrolls to the file', () => {
		expect(filesAppLink('/anna/files/Zaken/2026/brief.pdf')).toBe(
			'/apps/files/?dir={dir}&scrollto={file}|{"dir":"/Zaken/2026","file":"brief.pdf"}',
		)
	})

	it('uses the root as folder for a file directly in files', () => {
		expect(filesAppLink('/anna/files/brief.pdf')).toBe(
			'/apps/files/?dir={dir}&scrollto={file}|{"dir":"/","file":"brief.pdf"}',
		)
	})

	it('falls back to the Files app root outside a files folder', () => {
		expect(filesAppLink('/anna/trash/brief.pdf')).toBe('/index.php/apps/files')
	})
})

describe('webdavDownloadUrl', () => {
	it('appends the user-relative path to the WebDAV root', () => {
		expect(webdavDownloadUrl('/anna/files/Zaken/brief 1.pdf')).toBe(
			'http://localhost/remote.php/webdav/Zaken/brief 1.pdf',
		)
	})

	it('is the WebDAV root outside a files folder', () => {
		expect(webdavDownloadUrl('appdata/x.pdf')).toBe('http://localhost/remote.php/webdav')
	})
})

describe('encodedWebdavDownloadUrl', () => {
	it('encodes each segment but keeps the separators', () => {
		expect(encodedWebdavDownloadUrl('/anna/files/Zaken & co/brief #1.pdf')).toBe(
			'http://localhost/remote.php/webdav/Zaken%20%26%20co/brief%20%231.pdf',
		)
	})

	it('is empty for an empty path', () => {
		expect(encodedWebdavDownloadUrl('')).toBe('')
		expect(encodedWebdavDownloadUrl(undefined)).toBe('')
	})

	it('is the WebDAV root outside a files folder', () => {
		expect(encodedWebdavDownloadUrl('/anna/versions/x.pdf')).toBe('http://localhost/remote.php/webdav')
	})
})
