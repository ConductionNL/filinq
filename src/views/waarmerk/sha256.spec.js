/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

import { createHash, webcrypto } from 'crypto'
import { sha256Hex } from './sha256.js'

test('the hex digest equals the SHA-256 of the bytes', async () => {
	const bytes = Uint8Array.from(Buffer.from('%PDF-1.4 sealed document'))
	const file = { arrayBuffer: async () => bytes.buffer }

	await expect(sha256Hex(file, webcrypto.subtle)).resolves.toBe(createHash('sha256').update(Buffer.from(bytes)).digest('hex'))
})

test('without WebCrypto it refuses instead of sending anything', async () => {
	await expect(sha256Hex({ arrayBuffer: async () => new ArrayBuffer(0) }, null)).rejects.toThrow('WebCrypto is not available')
})
