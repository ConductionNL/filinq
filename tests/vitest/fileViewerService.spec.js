// @vitest-environment jsdom
/**
 * SPDX-FileCopyrightText: 2026 Conduction / DocuDesk Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Runs under jsdom: fileViewerService transitively imports @nextcloud/axios,
 * whose @nextcloud/browser-storage dependency touches `window` at module load.
 *
 * Unit tests for buildWebdavUrl in src/services/fileViewerService.js: the
 * WebDAV URL builder + the Nextcloud-internal (/<uid>/files/...) →
 * user-relative path normalisation, plus per-segment URL encoding and the
 * unauthenticated guard. @nextcloud/auth + router are stubbed.
 */

import { describe, it, expect, afterEach } from 'vitest'
import { buildWebdavUrl } from '../../src/services/fileViewerService.js'
import { __setCurrentUser } from '../../tests/vitest/stubs/nextcloud-auth.js'

afterEach(() => {
	__setCurrentUser({ uid: 'admin', displayName: 'Admin' })
})

describe('buildWebdavUrl', () => {
	it('builds a DAV URL from a user-relative path', () => {
		expect(buildWebdavUrl('/DocuDesk/foo.pdf')).toBe(
			'http://localhost/remote.php/dav/files/admin/DocuDesk/foo.pdf',
		)
	})

	it('strips the Nextcloud-internal /<uid>/files prefix', () => {
		expect(buildWebdavUrl('/admin/files/DocuDesk/foo.pdf')).toBe(
			'http://localhost/remote.php/dav/files/admin/DocuDesk/foo.pdf',
		)
	})

	it('prepends a leading slash to a bare relative path', () => {
		expect(buildWebdavUrl('DocuDesk/bar.txt')).toBe(
			'http://localhost/remote.php/dav/files/admin/DocuDesk/bar.txt',
		)
	})

	it('URL-encodes each path segment (spaces, special chars) without encoding slashes', () => {
		const url = buildWebdavUrl('/DocuDesk/My Reports/één & twee.pdf')
		expect(url).toContain('/dav/files/admin/DocuDesk/My%20Reports/')
		expect(url).toContain('%20%26%20') // " & "
		// path separators stay literal
		expect(url.split('/dav/files/admin/')[1].split('/').length).toBe(3)
	})

	it('maps the exact internal prefix to root', () => {
		expect(buildWebdavUrl('/admin/files')).toBe(
			'http://localhost/remote.php/dav/files/admin/',
		)
	})

	it('throws when there is no authenticated user', () => {
		__setCurrentUser(null)
		expect(() => buildWebdavUrl('/DocuDesk/foo.pdf')).toThrow(
			'User not authenticated',
		)
	})
})
