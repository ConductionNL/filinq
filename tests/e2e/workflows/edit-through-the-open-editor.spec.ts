/*
 * SPDX-FileCopyrightText: 2026 Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * End-to-end regression for `edit-through-the-open-editor`: an agent edit
 * reaches a document that is OPEN in an office editor, through the editor,
 * and never underneath it.
 *
 * WHAT IS DRIVEN, AND HOW
 * -----------------------
 * The tools are driven through OpenRegister's MCP endpoint, as in
 * `agent-document-editing.spec.ts`: that endpoint is the boundary an agent
 * reaches them through. The editor is opened the way a user opens it — from
 * Files, in a real browser page — and the test then waits until the backend
 * itself reports the editor's session. Which suite answers is read from
 * `lib/Settings/editor-delivery.json`, so the test names no editor frame.
 *
 * ⚠️ VERIFYING ONLY WITH THE DOCUMENT CLOSED TESTS THE PATH THIS CHANGE IS NOT
 * ABOUT. The closed-document leg runs everywhere; the open-document legs need
 * an office suite wired into the instance, named in
 * `FILINQ_E2E_OFFICE_SUITES` (comma-separated lock owners, e.g.
 * `richdocuments,eurooffice`). Without it they skip WITH the reason recorded,
 * because a skipped test reads exactly like a passing one in the count.
 *
 * The leg that applies the edit INSIDE the editor, and the unsaved-typing leg,
 * need the companion that relays the delivery on the editor page
 * (`@conduction/nextcloud-vue`). Until that relay ships they are `fixme`,
 * not green.
 */

import type { APIRequestContext, Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { readFileSync } from 'node:fs'
import path from 'node:path'
import { FIXTURE_DOCX_B64 } from './_agentDocumentFixture.ts'
import { harvestToken, jsonHeaders, TEST_PREFIX } from './_fixtures.ts'

/** OpenRegister's MCP JSON-RPC endpoint. */
const MCP = '/index.php/apps/openregister/api/mcp'

/** WebDAV root for the account the E2E session runs as. */
const DAV = '/remote.php/dav/files/admin'

/** The paragraph every leg edits. */
const TARGET = 'The assessment period is eight weeks.'

/** What it becomes. */
const REPLACEMENT = 'The assessment period is six weeks.'

/** The declared editors, by lock owner. */
const REGISTRY = JSON.parse(
	readFileSync(path.resolve(process.cwd(), 'lib/Settings/editor-delivery.json'), 'utf8'),
).editors as Record<string, { suite: string; adapter: string }>

/** The suites this instance has wired in, by lock owner. */
const SUITES = String(process.env.FILINQ_E2E_OFFICE_SUITES ?? '')
	.split(',')
	.map((owner) => owner.trim().toLowerCase())
	.filter((owner) => owner !== '')

/**
 * Open an MCP session and return its id.
 *
 * @param request The request context.
 * @param token The CSRF request-token.
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
			params: { protocolVersion: '2025-06-18', capabilities: {}, clientInfo: { name: 'filinq-e2e', version: '1' } },
		},
	})
	expect(res.status(), 'the MCP endpoint must accept an initialize handshake').toBe(200)
	const sessionId = res.headers()['mcp-session-id']
	expect(sessionId, 'initialize must return an Mcp-Session-Id header').toBeTruthy()

	return sessionId
}

/**
 * Call one MCP tool; a refusal is `isError: true` with the message as text.
 *
 * @param request The request context.
 * @param token The CSRF request-token.
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
): Promise<{ isError: boolean; payload: Record<string, any>; text: string }> {
	const res = await request.post(MCP, {
		headers: { ...jsonHeaders(token), 'Mcp-Session-Id': sessionId },
		data: { jsonrpc: '2.0', id: 1, method: 'tools/call', params: { name, arguments: args } },
	})
	expect(res.status(), `${name} must reach the endpoint`).toBe(200)
	const body = await res.json()
	expect(body.result, `${name} must return a JSON-RPC result, got: ${JSON.stringify(body.error ?? {})}`).toBeTruthy()

	const text = String(body.result.content?.[0]?.text ?? '')
	let payload: Record<string, any>
	try {
		payload = JSON.parse(text)
	} catch {
		payload = {}
	}

	return { isError: body.result.isError === true, payload, text }
}

/**
 * The texts of a read.
 *
 * @param read A readDocument payload.
 *
 * @return The block texts.
 */
function textsOf(read: Record<string, any>): string[] {
	return (read.blocks as Array<{ text: string }>).map((block) => block.text)
}

/**
 * Open the document in its editor from Files, as a user does.
 *
 * @param page The page.
 * @param fileId The file id.
 *
 * @return Promise<void>
 */
async function openInEditor(page: Page, fileId: number): Promise<void> {
	await page.goto(`/index.php/apps/files/files/${fileId}?dir=/&openfile=true`)
}

test.describe('edit through the open editor', () => {
	let token = ''
	let sessionId = ''
	let fileId = 0
	let docName = ''
	let mcpUnavailable = ''

	test.beforeAll(async ({ browser, request }) => {
		const page = await browser.newPage()
		token = await harvestToken(page)
		await page.close()

		try {
			const probeSession = await openMcpSession(request, token)
			const probe = await request.post(MCP, {
				headers: { ...jsonHeaders(token), 'Mcp-Session-Id': probeSession },
				data: { jsonrpc: '2.0', id: 99, method: 'tools/list', params: {} },
			})
			const body = await probe.json()
			if (body.result === undefined) {
				const message = String(body.error?.message ?? 'unknown')
				if (/session/i.test(message) === false) {
					throw new Error(`MCP tools/list failed for a NON-session reason: ${message}`)
				}
				mcpUnavailable = `the MCP session does not survive between requests on this instance (${message}).`
			}
		} catch (error) {
			if (String(error).includes('NON-session reason') === true) {
				throw error
			}
			mcpUnavailable = `the MCP endpoint could not be reached: ${String(error)}`
		}
	})

	test.beforeEach(async ({ page, request }, testInfo) => {
		if (mcpUnavailable !== '') {
			testInfo.annotations.push({ type: 'skip-reason', description: mcpUnavailable })
			test.skip(true, mcpUnavailable)
		}

		token = await harvestToken(page)
		sessionId = await openMcpSession(request, token)

		docName = `${TEST_PREFIX}-open-editor-${testInfo.workerIndex}-${Date.now()}.docx`
		const put = await request.put(`${DAV}/${docName}`, {
			headers: { requesttoken: token, 'Content-Type': 'application/octet-stream' },
			data: Buffer.from(FIXTURE_DOCX_B64, 'base64'),
		})
		expect([201, 204], 'the fixture document must upload').toContain(put.status())

		const propfind = await request.fetch(`${DAV}/${docName}`, {
			method: 'PROPFIND',
			headers: { requesttoken: token, Depth: '0', 'Content-Type': 'text/xml' },
			data: '<?xml version="1.0"?><d:propfind xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns"><d:prop><oc:fileid/></d:prop></d:propfind>',
		})
		const idMatch = (await propfind.text()).match(/<oc:fileid>(\d+)<\/oc:fileid>/)
		expect(idMatch, 'the uploaded document must expose a file id').toBeTruthy()
		fileId = Number(idMatch![1])
	})

	test.afterEach(async ({ request }) => {
		if (docName !== '') {
			await request.delete(`${DAV}/${docName}`, { headers: { requesttoken: token } })
		}
	})

	test('with no editor open, the direct path is unchanged: written, not delivered', async ({ request }) => {
		const read = await callTool(request, token, sessionId, 'filinq.readDocument', { fileId })
		expect(read.isError, `readDocument refused: ${read.text}`).toBe(false)
		expect(read.payload.editor, 'a closed document must not report an editor').toBeUndefined()

		const target = (read.payload.blocks as Array<{ anchor: string; text: string }>).find((b) => b.text === TARGET)
		expect(target, 'the fixture must contain the paragraph under test').toBeTruthy()

		const edit = await callTool(request, token, sessionId, 'filinq.editDocument', {
			fileId,
			version: read.payload.version,
			edits: [{ anchor: target!.anchor, action: 'replace', text: REPLACEMENT }],
		})
		expect(edit.isError, `editDocument refused: ${edit.text}`).toBe(false)
		expect(edit.payload.delivery, 'the direct path writes; it does not hand off to an editor').toBeUndefined()
		expect(edit.payload.outputMode).toBe('inPlace')

		const after = await callTool(request, token, sessionId, 'filinq.readDocument', { fileId })
		expect(textsOf(after.payload)).toContain(REPLACEMENT)
		expect(textsOf(after.payload)).not.toContain(TARGET)
	})

	for (const owner of Object.keys(REGISTRY)) {
		const { suite, adapter } = REGISTRY[owner]

		test.describe(`open in ${suite}`, () => {
			test.beforeEach(async ({ page: _page }, testInfo) => {
				if (SUITES.includes(owner) === false) {
					const reason = `${suite} is not wired into this instance (FILINQ_E2E_OFFICE_SUITES does not list "${owner}").`
					testInfo.annotations.push({ type: 'skip-reason', description: reason })
					test.skip(true, reason)
				}
			})

			test('the read names the editor and announces the save', async ({ page, request }) => {
				await openInEditor(page, fileId)

				let read: Record<string, any> = {}
				await expect
					.poll(async () => {
						read = (await callTool(request, token, sessionId, 'filinq.readDocument', { fileId })).payload
						return read.editor?.suite ?? null
					}, { message: `the backend must detect ${suite} holding the document`, timeout: 60_000 })
					.toBe(suite)

				expect(read.editor.deliverable).toBe(true)
				expect(read.editor.saveFirst).toBe(true)
				expect(read.editor.announce).toContain('will be saved first')
				expect(read.editor.announce).toContain(suite)
			})

			test('the edit is delivered through the editor and nothing is written underneath it', async ({ page, request }) => {
				await openInEditor(page, fileId)

				let read: Record<string, any> = {}
				await expect
					.poll(async () => {
						read = (await callTool(request, token, sessionId, 'filinq.readDocument', { fileId })).payload
						return read.editor?.suite ?? null
					}, { timeout: 60_000 })
					.toBe(suite)

				const target = (read.blocks as Array<{ anchor: string; text: string }>).find((b) => b.text === TARGET)
				const edit = await callTool(request, token, sessionId, 'filinq.editDocument', {
					fileId,
					version: read.version,
					edits: [{ anchor: target!.anchor, action: 'replace', text: REPLACEMENT }],
				})

				expect(edit.isError, `editDocument refused: ${edit.text}`).toBe(false)
				expect(edit.payload.delivery).toBe('editor')
				expect(edit.payload.suite).toBe(suite)
				expect(edit.payload.adapter).toBe(adapter)
				expect(edit.payload.replacements).toEqual([{ find: TARGET, replace: REPLACEMENT }])

				// The file layer was not used: the stored bytes still hold the old text.
				const stored = await callTool(request, token, sessionId, 'filinq.readDocument', { fileId })
				expect(textsOf(stored.payload)).toContain(TARGET)
				expect(stored.payload.version, 'no write may move the stored version').toBe(read.version)
			})

			test('an insert cannot be delivered and is refused by name, leaving the file alone', async ({ page, request }) => {
				await openInEditor(page, fileId)

				let read: Record<string, any> = {}
				await expect
					.poll(async () => {
						read = (await callTool(request, token, sessionId, 'filinq.readDocument', { fileId })).payload
						return read.editor?.suite ?? null
					}, { timeout: 60_000 })
					.toBe(suite)

				const target = (read.blocks as Array<{ anchor: string; text: string }>).find((b) => b.text === TARGET)
				const edit = await callTool(request, token, sessionId, 'filinq.editDocument', {
					fileId,
					version: read.version,
					edits: [{ anchor: target!.anchor, action: 'insertAfter', text: 'A new paragraph.' }],
				})

				expect(edit.isError).toBe(true)
				expect(edit.text).toContain('"insertAfter" edit cannot be applied through an open editor')
				const stored = await callTool(request, token, sessionId, 'filinq.readDocument', { fileId })
				expect(stored.payload.version).toBe(read.version)
			})

			test.fixme('the change appears IN the open editor and the user can see it', async () => {
				// Needs the companion relay on the editor page (@conduction/nextcloud-vue)
				// to hand the delivery to src/services/editorDelivery.js.
			})

			test.fixme('unsaved typing is flushed first and survives the edit', async () => {
				// Type into the editor, ask for the edit, then assert both the typing and
				// the replacement are in the saved file. Needs the same companion relay.
			})
		})
	}
})
