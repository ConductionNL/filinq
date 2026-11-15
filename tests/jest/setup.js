/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

// jsdom does not expose Node's structuredClone; browsers and Node both have it,
// so the test environment gets the same deep copy through v8's serializer.
if (typeof globalThis.structuredClone !== 'function') {
	const v8 = require('node:v8')
	globalThis.structuredClone = (value) => v8.deserialize(v8.serialize(value))
}
