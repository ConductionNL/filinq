/*
 * SPDX-FileCopyrightText: 2026 Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * What the document-tool specs share: OpenRegister's MCP endpoint, a session
 * on it, one tool call, and the fixture document. Moved here from
 * agent-document-editing.spec.ts so office-suite-portability's no-suite spec
 * drives the same tools over the same fixture.
 */

import type { APIRequestContext } from '@playwright/test'

import { expect } from '@playwright/test'
import { jsonHeaders } from './_fixtures.ts'

/** OpenRegister's MCP JSON-RPC endpoint — the tool surface under test. */
export const MCP = '/index.php/apps/openregister/api/mcp'

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
export const FIXTURE_DOCX_B64 = [
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
export async function openMcpSession(
	request: APIRequestContext,
	token: string,
): Promise<string> {
	const res = await request.post(MCP, {
		headers: jsonHeaders(token),
		data: {
			jsonrpc: '2.0',
			id: 0,
			method: 'initialize',
			params: {
				protocolVersion: '2025-06-18',
				capabilities: {},
				clientInfo: { name: 'filinq-e2e', version: '1' },
			},
		},
	})

	expect(
		res.status(),
		'the MCP endpoint must accept an initialize handshake',
	).toBe(200)

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
export async function callTool(
	request: APIRequestContext,
	token: string,
	sessionId: string,
	name: string,
	args: Record<string, unknown>,
): Promise<{ isError: boolean; payload: Record<string, unknown>; text: string }> {
	const res = await request.post(MCP, {
		headers: { ...jsonHeaders(token), 'Mcp-Session-Id': sessionId },
		data: {
			jsonrpc: '2.0',
			id: 1,
			method: 'tools/call',
			params: { name, arguments: args },
		},
	})

	expect(res.status(), `${name} must reach the endpoint`).toBe(200)

	const body = await res.json()
	expect(
		body.result,
		`${name} must return a JSON-RPC result, got: ${JSON.stringify(body.error ?? {})}`,
	).toBeTruthy()

	const text = String(body.result.content?.[0]?.text ?? '')
	let payload: Record<string, unknown>
	try {
		payload = JSON.parse(text)
	} catch {
		payload = {}
	}

	return { isError: body.result.isError === true, payload, text }
}
