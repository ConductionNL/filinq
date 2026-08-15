/*
 * SPDX-FileCopyrightText: 2026 DocuDesk Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * End-to-end regression for the agent-reachable document tools
 * (`docudesk-mcp-adoption` + `document-editing-tools`).
 *
 * WHAT THIS PROVES, AND WHY IT IS DRIVEN THROUGH MCP
 * -------------------------------------------------
 * The tools have no DocuDesk UI of their own: they exist to be called by an
 * agent, and the surface a caller actually reaches them through is
 * OpenRegister's MCP JSON-RPC endpoint. Driving them there is therefore not a
 * shortcut past the UI — it IS the integration boundary. Everything below the
 * endpoint is real: OR's tool registry resolving DocuDesk's `#[McpTool]`
 * methods, the acting user's session, the file lock, the etag precondition,
 * the codec, and the ADR-088 system tag.
 *
 * The two consequences a USER can see are asserted through user-facing
 * surfaces rather than through the tool's own reply:
 *   - the file content, re-read through `readDocument`;
 *   - the "Agent authored" tag, read through WebDAV — the same property Files
 *     renders — because a tag the tool merely CLAIMS to have written is the
 *     one thing nothing downstream would re-examine.
 *
 * ⚠️ CARRIES NO `@e2e` ANCHOR, deliberately. The `document-editing` capability
 * spec's scenarios are written against an agent conversation ("an agent edits
 * a document"), and this test drives the tool directly with no model in the
 * loop. Anchoring it to those scenarios would credit clauses about agent
 * behaviour that it never evaluates — the defect `.github#345` describes. The
 * model-in-the-loop leg was verified manually against the Anthropic chat
 * provider; it is not automatable here because it needs a live LLM credential.
 */

import { test, expect, type APIRequestContext } from '@playwright/test'

import { harvestToken, jsonHeaders, TEST_PREFIX } from './_fixtures'

/** OpenRegister's MCP JSON-RPC endpoint — the tool surface under test. */
const MCP = '/index.php/apps/openregister/api/mcp'

/** WebDAV root for the admin account the E2E session runs as. */
const DAV = '/remote.php/dav/files/admin'

/** The ADR-088 mark, fleet-wide and deliberately untranslated. */
const AGENT_TAG = 'Agent authored'

/**
 * A minimal but NOT trivial `.docx`, base64-encoded.
 *
 * Embedded rather than committed as a binary, and rather than built with a zip
 * library, so the fixture has no dependency and cannot drift. It deliberately
 * carries a `w:pStyle`, a bold run and a `word/comments.xml` part with a
 * comment range, because the codec's central claim is that everything it did
 * not target survives — a fixture of four bare paragraphs could not catch a
 * codec that silently dropped all of it.
 */
const FIXTURE_DOCX_B64 = [
	'UEsDBBQAAgAIABwBEF2Rzx8FvQAAACkBAAATAAAAW0NvbnRlbnRfVHlwZXNdLnhtbH2QvQ7CMAyEXyXKiqgLAwNqywCswMAL',
	'WKlbIpofJebv7XEBMTAw2t/d+eRqdXeDulLKNvhaz4pSr5rq+IiUlRCfa31ijkuAbE7kMBchkhfSheSQZUw9RDRn7AnmZbkA',
	'EzyT5ymPGbqpNtThZWC1vcv6fUXsWq3fuvFUrTHGwRpkwTBSaKq9lEq2JXXAxDt0ooJbSC20wVycOIv/MVff/nSdhq6zhr7+',
	'MS2mYChn63s3FF/i0PrJpwe8ntE8AVBLAwQUAAIACAAcARBdXzOVUpUAAAAHAQAACwAAAF9yZWxzLy5yZWxzjc87DsIwDAbg',
	'q0Q+QJ0yMKCmXVi6Ii4QJW5T0TzkhNftycBAEQOjf//6LHfDw6/iRpyXGBS0jYSh70606lKD7JaURW2ErMCVkg6I2TjyOjcx',
	'UaibKbLXpY48Y9LmomfCnZR75E8DtqYYrQIebQvi/Ez0jx2naTF0jObqKZQfJ74aVdY8U1Fwj2zRvuOmsoB9h5sX+xdQSwME',
	'FAACAAgAHAEQXW9FqKocAQAAEQIAABEAAAB3b3JkL2RvY3VtZW50LnhtbHWRzW7CMAzHX8XKeSOMwzRVtJzYZZdpsAcwjWkj',
	'mg/ZgcLbL6WMgcQudiz7//NH5ouj6+BALDb4Ur1MpgrI18FY35Tqe/3+/KZAEnqDXfBUqhOJWlTzvjCh3jvyCTLAS9GXqk0p',
	'FlpL3ZJDmYRIPue2gR2mHHKj+8AmcqhJJPNdp2fT6at2aL0akJtgToOPZ/PJZ7dKp46gLw7YlWptU0dKV3N9LRjN+N6MmUuU',
	'quVsCVt7THsmaAmHpYZ8GqtGyrXhRbNuCVAkj3jeLhLbYMAKkG3aBD3RTib/U+rgBt0X+oZWCTkrCmvyZYepb5pkYETGhjG2',
	'UCOzJQGEixx40N+3uWcvvbklP9rkw+YapgbZyNODifXvxfXfb1Y/UEsDBBQAAgAIABwBEF0B/c27YAAAAI0AAAARAAAAd29y',
	'ZC9jb21tZW50cy54bWxNzEsOgCAMBNCrEA6AuiV87uAVtAsSKaQg9fgKccHmpZnJ1PgnXqIBlZDQyk2t0jvD+kgxAtYivhqL',
	'ZitvQs1y6gTrcH6TkeUOdarboQVgIIGpgjJLz7o0zMP/x3wX9wJQSwECPwMUAAIACAAcARBdkc8fBb0AAAApAQAAEwAAAAAA',
	'AAAAAAAAtoEAAAAAW0NvbnRlbnRfVHlwZXNdLnhtbFBLAQI/AxQAAgAIABwBEF1fM5VSlQAAAAcBAAALAAAAAAAAAAAAAAC2',
	'ge4AAABfcmVscy8ucmVsc1BLAQI/AxQAAgAIABwBEF1vRaiqHAEAABECAAARAAAAAAAAAAAAAAC2gawBAAB3b3JkL2RvY3Vt',
	'ZW50LnhtbFBLAQI/AxQAAgAIABwBEF0B/c27YAAAAI0AAAARAAAAAAAAAAAAAAC2gfcCAAB3b3JkL2NvbW1lbnRzLnhtbFBL',
	'BQYAAAAABAAEAPgAAACGAwAAAAA=',
].join('')

/** Name of the seeded document, unique per run so parallel runs cannot collide. */
const DOC_NAME = `${TEST_PREFIX}-agent-edit.docx`

/**
 * Open an MCP session and return its id.
 *
 * The endpoint refuses every `tools/call` without `Mcp-Session-Id`, so the
 * handshake is part of the contract rather than boilerplate.
 *
 * @param request The Playwright request context.
 * @param token The harvested CSRF request-token.
 *
 * @return The session id.
 */
async function openMcpSession(request: APIRequestContext, token: string): Promise<string> {
	const res = await request.post(MCP, {
		headers: jsonHeaders(token),
		data: {
			jsonrpc: '2.0',
			id: 0,
			method: 'initialize',
			params: {
				protocolVersion: '2025-06-18',
				capabilities: {},
				clientInfo: { name: 'docudesk-e2e', version: '1' },
			},
		},
	})

	expect(res.status(), 'the MCP endpoint must accept an initialize handshake').toBe(200)

	const sessionId = res.headers()['mcp-session-id']
	expect(sessionId, 'initialize must return an Mcp-Session-Id header').toBeTruthy()

	return sessionId
}

/**
 * Call one MCP tool and return its decoded result plus the isError flag.
 *
 * The endpoint reports a tool-level refusal as `isError: true` with the message
 * in the text content — NOT as a JSON-RPC error and NOT as a non-200 status —
 * so a test that only checked the status code would read every refusal as a
 * pass.
 *
 * @param request The Playwright request context.
 * @param token The harvested CSRF request-token.
 * @param sessionId The MCP session id.
 * @param name The tool name.
 * @param args The tool arguments.
 *
 * @return The decoded payload and whether the tool refused.
 */
async function callTool(
	request: APIRequestContext,
	token: string,
	sessionId: string,
	name: string,
	args: Record<string, unknown>,
): Promise<{ isError: boolean, payload: Record<string, unknown>, text: string }> {
	const res = await request.post(MCP, {
		headers: { ...jsonHeaders(token), 'Mcp-Session-Id': sessionId },
		data: { jsonrpc: '2.0', id: 1, method: 'tools/call', params: { name, arguments: args } },
	})

	expect(res.status(), `${name} must reach the endpoint`).toBe(200)

	const body = await res.json()
	expect(body.result, `${name} must return a JSON-RPC result, got: ${JSON.stringify(body.error ?? {})}`)
		.toBeTruthy()

	const text = String(body.result.content?.[0]?.text ?? '')
	let payload: Record<string, unknown> = {}
	try {
		payload = JSON.parse(text)
	} catch {
		payload = {}
	}

	return { isError: body.result.isError === true, payload, text }
}

test.describe('agent document editing', () => {
	let token = ''
	let sessionId = ''
	let fileId = 0

	test.beforeAll(async ({ browser }) => {
		const page = await browser.newPage()
		token = await harvestToken(page)
		await page.close()
	})

	test.beforeEach(async ({ page, request }) => {
		token = await harvestToken(page)
		sessionId = await openMcpSession(request, token)

		// Seed a fresh document per test: an edit SPENDS its anchors and moves
		// the etag, so tests sharing one file would pass or fail on their order.
		const put = await request.put(`${DAV}/${DOC_NAME}`, {
			headers: { requesttoken: token, 'Content-Type': 'application/octet-stream' },
			data: Buffer.from(FIXTURE_DOCX_B64, 'base64'),
		})
		expect([201, 204], 'the fixture document must upload').toContain(put.status())

		const propfind = await request.fetch(`${DAV}/${DOC_NAME}`, {
			method: 'PROPFIND',
			headers: { requesttoken: token, Depth: '0', 'Content-Type': 'text/xml' },
			data: '<?xml version="1.0"?><d:propfind xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns">'
				+ '<d:prop><oc:fileid/></d:prop></d:propfind>',
		})
		const idMatch = (await propfind.text()).match(/<oc:fileid>(\d+)<\/oc:fileid>/)
		expect(idMatch, 'the uploaded document must expose a file id').toBeTruthy()
		fileId = Number(idMatch![1])
	})

	test.afterEach(async ({ request }) => {
		await request.delete(`${DAV}/${DOC_NAME}`, { headers: { requesttoken: token } })
	})

	test('reading a document returns anchored blocks and the version an edit needs', async ({ request }) => {
		const read = await callTool(request, token, sessionId, 'docudesk.readDocument', { fileId })

		expect(read.isError, `readDocument refused: ${read.text}`).toBe(false)
		expect(read.payload.format).toBe('ooxml')
		expect(read.payload.version, 'a read must hand back the version an edit will require').toBeTruthy()
		expect(read.payload.editable).toBe(true)

		const blocks = read.payload.blocks as Array<{ anchor: string, text: string }>
		expect(blocks.map(b => b.text)).toEqual([
			'E2E fixture heading',
			'The assessment period is eight weeks.',
			'This paragraph carries a comment range.',
			'Kind regards,',
		])

		// Anchors must be present and distinct — two blocks sharing an anchor
		// would make one of them unaddressable.
		const anchors = blocks.map(b => b.anchor)
		expect(anchors.every(a => a.length > 0)).toBe(true)
		expect(new Set(anchors).size).toBe(anchors.length)

		// No package bytes may ride back in the reply.
		expect(read.text).not.toContain('w:document')
		expect(read.text).not.toContain('PK')
	})

	test('an edit changes the targeted paragraph, leaves the others alone, and marks the file', async ({
		page,
		request,
	}) => {
		const read = await callTool(request, token, sessionId, 'docudesk.readDocument', { fileId })
		const blocks = read.payload.blocks as Array<{ anchor: string, text: string }>
		const target = blocks.find(b => b.text.includes('eight weeks'))
		expect(target, 'the fixture must contain the paragraph under test').toBeTruthy()

		const edit = await callTool(request, token, sessionId, 'docudesk.editDocument', {
			fileId,
			version: read.payload.version,
			edits: [{ anchor: target!.anchor, action: 'replace', text: 'The assessment period is six weeks.' }],
		})

		expect(edit.isError, `editDocument refused: ${edit.text}`).toBe(false)
		expect(edit.payload.outputMode).toBe('inPlace')
		expect(edit.payload.appliedAnchors).toEqual([target!.anchor])
		expect(edit.payload.artefact, 'the produced file id is what makes the record followable')
			.toEqual({ type: 'file', id: String(fileId) })

		// Re-read rather than trust the reply: the tool claiming success and the
		// bytes having changed are different facts.
		const after = await callTool(request, token, sessionId, 'docudesk.readDocument', { fileId })
		const afterTexts = (after.payload.blocks as Array<{ text: string }>).map(b => b.text)
		expect(afterTexts).toEqual([
			'E2E fixture heading',
			'The assessment period is six weeks.',
			'This paragraph carries a comment range.',
			'Kind regards,',
		])

		// The ADR-088 mark, read from the property Files itself renders.
		const tags = await request.fetch(`${DAV}/${DOC_NAME}`, {
			method: 'PROPFIND',
			headers: { requesttoken: token, Depth: '0', 'Content-Type': 'text/xml' },
			data: '<?xml version="1.0"?><d:propfind xmlns:d="DAV:" xmlns:nc="http://nextcloud.org/ns">'
				+ '<d:prop><nc:system-tags/></d:prop></d:propfind>',
		})
		expect(await tags.text(), 'an agent-written file must be marked where a user can see it')
			.toContain(AGENT_TAG)

		await page.close()
	})

	test('an edit is refused when the document moved since it was read', async ({ request }) => {
		const read = await callTool(request, token, sessionId, 'docudesk.readDocument', { fileId })
		const blocks = read.payload.blocks as Array<{ anchor: string, text: string }>
		const first = blocks[0]

		// Move the file out from under the reader, exactly as a second writer would.
		await callTool(request, token, sessionId, 'docudesk.editDocument', {
			fileId,
			version: read.payload.version,
			edits: [{ anchor: blocks[3].anchor, action: 'replace', text: 'With kind regards,' }],
		})

		// A fresh anchor paired with the NOW-STALE version. Using the original
		// anchor instead would be refused by the anchor check and prove nothing
		// about the version precondition.
		const reread = await callTool(request, token, sessionId, 'docudesk.readDocument', { fileId })
		const freshAnchor = (reread.payload.blocks as Array<{ anchor: string, text: string }>)
			.find(b => b.text === first.text)!.anchor

		const stale = await callTool(request, token, sessionId, 'docudesk.editDocument', {
			fileId,
			version: read.payload.version,
			edits: [{ anchor: freshAnchor, action: 'replace', text: 'SHOULD NEVER LAND' }],
		})

		expect(stale.isError, 'a stale version must be refused').toBe(true)
		expect(stale.text).toContain('changed since you read it')

		const after = await callTool(request, token, sessionId, 'docudesk.readDocument', { fileId })
		expect(after.text, 'a refused edit must write nothing').not.toContain('SHOULD NEVER LAND')
	})

	test('a stale anchor is refused, and it takes the whole edit set with it', async ({ request }) => {
		const read = await callTool(request, token, sessionId, 'docudesk.readDocument', { fileId })
		const blocks = read.payload.blocks as Array<{ anchor: string, text: string }>

		const result = await callTool(request, token, sessionId, 'docudesk.editDocument', {
			fileId,
			version: read.payload.version,
			edits: [
				{ anchor: blocks[0].anchor, action: 'replace', text: 'Applied?' },
				{ anchor: 'bdeadbeef-1', action: 'replace', text: 'Never' },
			],
		})

		expect(result.isError, 'an unknown anchor must be refused').toBe(true)
		expect(result.text).toContain('bdeadbeef-1')

		const after = await callTool(request, token, sessionId, 'docudesk.readDocument', { fileId })
		expect((after.payload.blocks as Array<{ text: string }>)[0].text)
			.toBe('E2E fixture heading')
	})

	test('a format the codec cannot address is refused by name, and names what it can', async ({ request }) => {
		const name = `${TEST_PREFIX}-not-a-document.pdf`
		await request.put(`${DAV}/${name}`, {
			headers: { requesttoken: token, 'Content-Type': 'application/pdf' },
			data: Buffer.from('%PDF-1.7\n%%EOF\n'),
		})

		const propfind = await request.fetch(`${DAV}/${name}`, {
			method: 'PROPFIND',
			headers: { requesttoken: token, Depth: '0', 'Content-Type': 'text/xml' },
			data: '<?xml version="1.0"?><d:propfind xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns">'
				+ '<d:prop><oc:fileid/></d:prop></d:propfind>',
		})
		const pdfId = Number((await propfind.text()).match(/<oc:fileid>(\d+)<\/oc:fileid>/)![1])

		const read = await callTool(request, token, sessionId, 'docudesk.readDocument', { fileId: pdfId })

		expect(read.isError, 'a PDF is not editable through this capability').toBe(true)
		expect(read.text, 'the refusal must name the formats that ARE editable').toMatch(/docx.*odt/)

		await request.delete(`${DAV}/${name}`, { headers: { requesttoken: token } })
	})

	test('the curated tools are exposed read-only where they should be, and no schema is agent-writable', async ({
		request,
	}) => {
		const res = await request.post(MCP, {
			headers: { ...jsonHeaders(token), 'Mcp-Session-Id': sessionId },
			data: { jsonrpc: '2.0', id: 2, method: 'tools/list', params: {} },
		})
		expect(res.status()).toBe(200)

		const tools = (await res.json()).result.tools as Array<{ name: string }>
		const docudesk = tools.map(t => t.name).filter(n => n.startsWith('docudesk.'))

		// POSITIVE CONTROL: if the register had not imported, this list would be
		// empty and every assertion below would pass vacuously.
		expect(docudesk.length, 'DocuDesk must expose an MCP surface at all').toBeGreaterThan(0)

		for (const name of ['docudesk.readDocument', 'docudesk.editDocument', 'docudesk.convertDocumentToPdf']) {
			expect(docudesk, `${name} must be exposed`).toContain(name)
		}

		// The standing refusals, as a shape rather than a promise: no schema-derived
		// write verb, and no batch or signing tool.
		const writeVerbs = docudesk.filter(n => /\.(create|update|delete)$/.test(n))
		expect(writeVerbs, 'no DocuDesk schema may expose a derived write verb').toEqual([])
		expect(docudesk.filter(n => /batch|sign/i.test(n)), 'batch and signing stay unreachable').toEqual([])
	})
})
