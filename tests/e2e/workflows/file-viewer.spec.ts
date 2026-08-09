/*
 * SPDX-FileCopyrightText: 2026 DocuDesk Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * DEEP, data-dependent workflow test — the in-app file viewer.
 *
 * `src/views/fileViewer/FileViewerPage.vue` has no route of its own: it
 * renders inside `MyDocumentsIndex` only while `fileViewerStore.currentFile`
 * is set, and the only way to set it from the UI is to open a row in the
 * My Documents list. So the only honest proof this component renders is to
 * put a REAL file where the list reads from (`/DocuDesk` via WebDAV), open it,
 * and assert the viewer's own chrome and the document's own contents.
 *
 * Deliberately a `.txt` fixture: TextViewer is the one viewer that needs no
 * conversion backend, so what lands on screen is the exact text this test
 * wrote — an assertion the SPA shell cannot satisfy by accident.
 */

import { test, expect } from '@playwright/test'
import { go } from '../spec-coverage/_helpers'
import {
	harvestToken, TEST_PREFIX, createDavFile, createDavFolder, deleteDavPath,
} from './_fixtures'

/** The folder MyDocumentsIndex lists by default (`myDocumentsStore.currentPath`). */
const DOCS_FOLDER = 'DocuDesk'

const VIEWER_FILE = `${TEST_PREFIX}-viewer.txt`
const VIEWER_PATH = `${DOCS_FOLDER}/${VIEWER_FILE}`

/** Contents asserted verbatim in the viewer body — unique to this run. */
const VIEWER_BODY = `Signed statement ${TEST_PREFIX}: the quarterly report has been reviewed.`

test.afterAll(async ({ request }) => {
	// Cleanup of this run's fixture only; the shared /DocuDesk folder stays,
	// because the app and other specs use it. WebDAV writes authenticate on
	// the session cookie jar, not on the CSRF token, so the empty token here
	// is the same one signing-workflow.spec.ts's purge passes.
	await deleteDavPath(request, '', VIEWER_PATH)
})

test('FileViewerPage renders a real document opened from My Documents', async ({ page }) => {
	const token = await harvestToken(page)
	const req = page.request

	// MKCOL is 405 when the collection already exists — that is success for
	// our purposes, so accept it explicitly rather than swallowing the status.
	const mkcol = await createDavFolder(req, token, DOCS_FOLDER)
	expect([201, 405], `MKCOL /${DOCS_FOLDER} (201 created / 405 already there)`).toContain(mkcol)

	const seeded = await createDavFile(req, token, VIEWER_PATH, VIEWER_BODY)
	expect(seeded.status, `PUT /${VIEWER_PATH}`).toBeLessThan(300)

	await go(page, 'my-documents')

	// The list strips the extension for display (MyDocumentsIndex.displayName),
	// so the row is identified by the stem.
	const stem = VIEWER_FILE.replace(/\.txt$/, '')
	const row = page.locator('tr').filter({ hasText: stem }).first()
	await expect(row, 'the seeded document must appear in My Documents').toBeVisible()
	await row.click()

	// FileViewerPage replaces the list inside `.my-documents-wrapper`.
	const viewer = page.locator('.file-viewer-page')
	await expect(viewer).toBeVisible()
	// Its own header component, carrying the full file name (extension and all).
	await expect(viewer.locator('.dd-file-viewer-header__title')).toHaveText(VIEWER_FILE)
	// And the document's own text, rendered by TextViewer inside the viewer
	// body. Nothing but a working viewer can put this string on screen.
	await expect(viewer.locator('.text-viewer__content')).toContainText(VIEWER_BODY)
})
