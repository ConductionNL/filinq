/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * ci-seed.sh read OpenRegister's schema list once, with `_limit=1000`. On an
 * instance with more schemas than that (the shared dev box has well over a
 * thousand) the filinq schemas past the cut were reported missing and nothing
 * was bound. `tests/e2e/lib/fetch-all-pages.sh` pages through the list with
 * `_offset` until a short page, and this test runs it against a fake
 * OpenRegister that holds 2,345 schemas and never returns more than 1,000.
 */

import { execFile } from 'child_process'
import { mkdtempSync, readFileSync } from 'fs'
import { createServer } from 'http'
import { tmpdir } from 'os'
import * as path from 'path'
import { promisify } from 'util'
import { afterAll, beforeAll, describe, expect, it } from 'vitest'

const run = promisify(execFile)
const SCRIPT = path.resolve(__dirname, '../e2e/lib/fetch-all-pages.sh')
const TOTAL = 2345
const CAP = 1000

let server
let base
const seen = []

beforeAll(async () => {
	server = createServer((req, res) => {
		const url = new URL(req.url, 'http://x')
		seen.push(url.search)
		const limit = Math.min(Number(url.searchParams.get('_limit') || CAP), CAP)
		const offset = Number(url.searchParams.get('_offset') || 0)
		const results = []
		for (let i = offset; i < Math.min(offset + limit, TOTAL); i++) {
			results.push({ id: i + 1, slug: `schema${i + 1}` })
		}
		res.writeHead(200, { 'Content-Type': 'application/json' })
		res.end(JSON.stringify({ results }))
	})
	await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve))
	base = `http://127.0.0.1:${server.address().port}`
})

afterAll(() => new Promise((resolve) => server.close(resolve)))

describe('ci-seed schema listing', () => {
	it('collects every schema, past the per-request cap', async () => {
		const out = path.join(
			mkdtempSync(path.join(tmpdir(), 'seed-')),
			'schemas.json',
		)
		await run('bash', [SCRIPT, `${base}/schemas`, out, 'u:p', '500'])
		const body = JSON.parse(readFileSync(out, 'utf8'))
		expect(body.results).toHaveLength(TOTAL)
		expect(body.results.at(-1).slug).toBe(`schema${TOTAL}`)
		expect(new Set(body.results.map((r) => r.id)).size).toBe(TOTAL)
	})

	it('stops on the first short page', async () => {
		seen.length = 0
		const out = path.join(
			mkdtempSync(path.join(tmpdir(), 'seed-')),
			'schemas.json',
		)
		await run('bash', [SCRIPT, `${base}/schemas`, out, 'u:p', '1000'])
		// 1000 + 1000 + 345: three requests, no fourth.
		expect(seen).toHaveLength(3)
	})

	it('fails loudly on a response that is not JSON', async () => {
		const out = path.join(
			mkdtempSync(path.join(tmpdir(), 'seed-')),
			'schemas.json',
		)
		await expect(
			run('bash', [
				SCRIPT,
				`${base.replace(/:\d+$/, ':1')}/schemas`,
				out,
				'u:p',
				'500',
			]),
		).rejects.toThrow()
	})
})
