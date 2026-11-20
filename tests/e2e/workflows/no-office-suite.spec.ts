/*
 * SPDX-FileCopyrightText: 2026 Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Reading and editing a document with NO office suite installed
 * (office-suite-portability, Verify 5.1).
 *
 * WHY: ADR-087 §2 says document manipulation is suite-independent:
 * `PackageCodec` edits the package XML in place and no suite is in the call
 * path. This spec proves it on a real instance: it first asserts that the
 * instance reports no suite through `GET /api/office/capabilities`, then
 * reads and edits a `.docx` through the same document tools an agent uses,
 * and re-reads the file to see the edit landed.
 *
 * NOT RUN, NOT PASSED. When the instance HAS a suite, the precondition does
 * not hold and the spec says so as a skip with the reason, never as a pass:
 * a green run on an instance with a suite would prove nothing about the
 * absence of one.
 *
 * The admin session of the project runs it, as agent-document-editing.spec.ts
 * does; base URL from the environment (playwright.config.ts).
 *
 * @spec openspec/changes/office-suite-portability/specs/office-suite-portability/spec.md
 */

import { expect, test } from '@playwright/test'
import { API, harvestToken, jsonHeaders, TEST_PREFIX } from './_fixtures.ts'
import { callTool, FIXTURE_DOCX_B64, openMcpSession } from './_mcp.ts'

/** WebDAV root for the admin account the E2E session runs as. */
const DAV = '/remote.php/dav/files/admin'

/** The document, unique per run. */
const DOC_NAME = `${TEST_PREFIX}-no-suite.docx`

test.describe('documents work with no office suite installed', () => {
	let token = ''
	let fileId = 0

	test.beforeEach(async ({ page, request }, testInfo) => {
		token = await harvestToken(page)

		const caps = await request.get(`${API}/office/capabilities`, { headers: jsonHeaders(token) })
		expect(caps.status(), 'the capability endpoint answers').toBe(200)
		const body = await caps.json()
		if (body.available === true) {
			const reason = `not run: this instance reports an office suite (${JSON.stringify(body)}), so it cannot show that nothing depends on one`
			testInfo.annotations.push({ type: 'skip-reason', description: reason })
			test.skip(true, reason)
		}

		const put = await request.put(`${DAV}/${DOC_NAME}`, {
			headers: { requesttoken: token, 'Content-Type': 'application/octet-stream' },
			data: Buffer.from(FIXTURE_DOCX_B64, 'base64'),
		})
		expect([201, 204], 'the fixture document uploads').toContain(put.status())

		const propfind = await request.fetch(`${DAV}/${DOC_NAME}`, {
			method: 'PROPFIND',
			headers: { requesttoken: token, Depth: '0', 'Content-Type': 'text/xml' },
			data: '<?xml version="1.0"?><d:propfind xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns"><d:prop><oc:fileid/></d:prop></d:propfind>',
		})
		const match = (await propfind.text()).match(/<oc:fileid>(\d+)<\/oc:fileid>/)
		expect(match, 'the uploaded file has an id').toBeTruthy()
		fileId = Number(match![1])
	})

	test.afterEach(async ({ request }) => {
		await request.delete(`${DAV}/${DOC_NAME}`, { headers: { requesttoken: token } })
	})

	test('a document is read and edited, and the edit is in the file', async ({ request }) => {
		const sessionId = await openMcpSession(request, token)

		const read = await callTool(request, token, sessionId, 'filinq.readDocument', { fileId })
		expect(read.isError, `readDocument refused without a suite: ${read.text}`).toBe(false)
		expect(read.payload.editable, 'the document is editable without a suite').toBe(true)
		const blocks = read.payload.blocks as Array<{ anchor: string; text: string }>
		const target = blocks.find((block) => block.text === 'The assessment period is eight weeks.')
		expect(target, 'the fixture holds the paragraph under test').toBeTruthy()

		const edit = await callTool(request, token, sessionId, 'filinq.editDocument', {
			fileId,
			version: read.payload.version,
			edits: [{ anchor: target!.anchor, action: 'replace', text: 'The assessment period is six weeks.' }],
		})
		expect(edit.isError, `editDocument refused without a suite: ${edit.text}`).toBe(false)

		const again = await callTool(request, token, sessionId, 'filinq.readDocument', { fileId })
		const texts = (again.payload.blocks as Array<{ text: string }>).map((block) => block.text)
		expect(texts).toContain('The assessment period is six weeks.')
		expect(texts).not.toContain('The assessment period is eight weeks.')
	})
})
