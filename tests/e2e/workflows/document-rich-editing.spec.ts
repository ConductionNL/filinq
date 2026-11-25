/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * document-rich-editing, task 5: style a paragraph and set metadata through
 * the agent tool surface, asserting on the document's BYTES.
 *
 * WHY BYTES. A tool that answers "applied" is not evidence the document
 * changed, and a re-read through `readDocument` returns text only, so it
 * cannot see a style at all. This spec downloads the file through WebDAV, the
 * way Files serves it, opens the package and compares `word/document.xml`
 * paragraph by paragraph, and `docProps/core.xml` for the metadata:
 *
 *   - the style edit changes the TARGETED paragraph's `w:pPr`/`w:rPr` and no
 *     other paragraph's XML;
 *   - the metadata write leaves `word/document.xml` byte-identical and puts
 *     the title in `docProps/core.xml`.
 *
 * Driven through OpenRegister's MCP endpoint, the surface an agent reaches the
 * tools through (see agent-document-editing.spec.ts for why that is the
 * integration boundary, not a shortcut). With no model in the loop it carries
 * no `@e2e` anchor on the agent-conversation scenarios.
 */

import type { APIRequestContext } from '@playwright/test'

import { expect, test } from '@playwright/test'
import JSZip from 'jszip'
import { harvestToken, TEST_PREFIX } from './_fixtures.ts'
import { callTool, FIXTURE_DOCX_B64, openMcpSession } from './_mcp.ts'

/** WebDAV root for the admin account the E2E session runs as. */
const DAV = '/remote.php/dav/files/admin'

/** Unique per run so parallel runs cannot collide. */
const DOC_NAME = `${TEST_PREFIX}-rich-edit.docx`

/**
 * One part of the document as it is stored now.
 *
 * @param request The request context.
 * @param token   The CSRF token.
 * @param part    The part path inside the package.
 * @return The part's XML, '' when the package has no such part.
 */
async function storedPart(request: APIRequestContext, token: string, part: string): Promise<string> {
	const res = await request.get(`${DAV}/${DOC_NAME}`, { headers: { requesttoken: token } })
	expect(res.status(), 'the document downloads').toBe(200)
	const zip = await JSZip.loadAsync(await res.body())

	return (await zip.file(part)?.async('string')) ?? ''
}

/**
 * The `<w:p>` elements of a document part, in order.
 *
 * @param xml `word/document.xml`.
 * @return Each paragraph's XML.
 */
function paragraphs(xml: string): string[] {
	return xml.match(/<w:p[ >][\s\S]*?<\/w:p>/g) ?? []
}

test.describe('rich editing through the tool surface', () => {
	let token = ''
	let sessionId = ''
	let fileId = 0

	test.beforeEach(async ({ page, request }) => {
		token = await harvestToken(page)
		sessionId = await openMcpSession(request, token)

		const put = await request.put(`${DAV}/${DOC_NAME}`, {
			headers: { requesttoken: token, 'Content-Type': 'application/octet-stream' },
			data: Buffer.from(FIXTURE_DOCX_B64, 'base64'),
		})
		expect([201, 204], 'the fixture document must upload').toContain(put.status())

		const propfind = await request.fetch(`${DAV}/${DOC_NAME}`, {
			method: 'PROPFIND',
			headers: { requesttoken: token, Depth: '0', 'Content-Type': 'text/xml' },
			data: '<?xml version="1.0"?><d:propfind xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns"><d:prop><oc:fileid/></d:prop></d:propfind>',
		})
		fileId = Number(/<oc:fileid>(\d+)<\/oc:fileid>/.exec(await propfind.text())?.[1] ?? 0)
		expect(fileId, 'the uploaded document has a file id').toBeGreaterThan(0)
	})

	test.afterEach(async ({ request }) => {
		await request.delete(`${DAV}/${DOC_NAME}`, { headers: { requesttoken: token } })
	})

	test('a style edit changes exactly the targeted paragraph\'s bytes', async ({ request }) => {
		const before = paragraphs(await storedPart(request, token, 'word/document.xml'))
		const read = await callTool(request, token, sessionId, 'filinq.readDocument', { fileId })
		const blocks = read.payload.blocks as Array<{ anchor: string; text: string }>
		const index = blocks.findIndex((b) => b.text.includes('eight weeks'))
		expect(index, 'the fixture holds the paragraph under test').toBeGreaterThanOrEqual(0)
		expect(before.length, 'one stored paragraph per block').toBe(blocks.length)
		expect(before[index]).not.toContain('<w:jc ')

		const edit = await callTool(request, token, sessionId, 'filinq.editDocument', {
			fileId,
			version: read.payload.version,
			edits: [{ anchor: blocks[index].anchor, action: 'style', style: { bold: true, alignment: 'center' } }],
		})
		expect(edit.isError, `editDocument refused: ${edit.text}`).toBe(false)

		const after = paragraphs(await storedPart(request, token, 'word/document.xml'))
		expect(after.length, 'no paragraph added or lost').toBe(before.length)
		expect(after[index], 'the target is centred').toMatch(/<w:jc w:val="center"\s*\/>/)
		expect(after[index], 'the target\'s runs are bold').toMatch(/<w:b\s*\/>|<w:b w:val="(1|true)"\s*\/>/)
		expect(after[index].replace(/<[^>]+>/g, ''), 'the text is untouched').toBe(before[index].replace(/<[^>]+>/g, ''))
		after.forEach((xml, i) => {
			if (i !== index) {
				expect(xml, `paragraph ${i} is byte-identical`).toBe(before[i])
			}
		})
	})

	test('a metadata write sets the title and leaves the body byte-identical', async ({ request }) => {
		const body = await storedPart(request, token, 'word/document.xml')
		const title = `${TEST_PREFIX} titel`

		const meta = await callTool(request, token, sessionId, 'filinq.readDocumentMetadata', { fileId })
		expect(meta.isError, `readDocumentMetadata refused: ${meta.text}`).toBe(false)
		expect(String(meta.payload.version ?? ''), 'the read hands out the version a write needs').not.toBe('')

		const write = await callTool(request, token, sessionId, 'filinq.setDocumentMetadata', {
			fileId,
			version: meta.payload.version,
			metadata: { title, keywords: 'e2e, metadata' },
		})
		expect(write.isError, `setDocumentMetadata refused: ${write.text}`).toBe(false)

		expect(await storedPart(request, token, 'word/document.xml'), 'the body part is byte-identical').toBe(body)
		const core = await storedPart(request, token, 'docProps/core.xml')
		expect(core, 'the title is in the core properties').toContain(`<dc:title>${title}</dc:title>`)
		expect(core).toMatch(/<cp:keywords>e2e, metadata<\/cp:keywords>/)

		const reread = await callTool(request, token, sessionId, 'filinq.readDocumentMetadata', { fileId })
		expect(reread.payload.metadata?.title).toBe(title)
	})

	test('both tools are on the surface under their dotted ids', async ({ request }) => {
		const res = await request.post('/index.php/apps/openregister/api/mcp', {
			headers: { requesttoken: token, 'OCS-APIRequest': 'true', 'Content-Type': 'application/json', Accept: 'application/json, text/event-stream', 'Mcp-Session-Id': sessionId },
			data: { jsonrpc: '2.0', id: 9, method: 'tools/list', params: {} },
		})
		expect(res.status()).toBe(200)
		const ids = ((await res.json()).result.tools as Array<{ id?: string }>).map((t) => String(t.id ?? ''))
		expect(ids).toContain('filinq.readDocumentMetadata')
		expect(ids).toContain('filinq.setDocumentMetadata')
	})
})
