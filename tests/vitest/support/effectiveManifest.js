/**
 * The effective manifest as the SPA builds it: the bundled base, the
 * `src/manifest.d/` fragments in file-name order (what `require.context`
 * collects in `src/manifestFragments.js`), and `src/menu-layout.json`, through
 * the shared `buildManifest`. For specs that read pages or menu entries, which
 * may live in any fragment.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/filinq-adopt-buildmanifest-pipeline/specs/menu-architecture/spec.md#requirement-manifest-assembled-from-adr-037-fragments
 */

import { buildManifest } from '@conduction/nextcloud-vue/src/utils/buildManifest.js'
import { readdirSync, readFileSync } from 'node:fs'
import { dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'

const SRC = join(dirname(fileURLToPath(import.meta.url)), '..', '..', '..', 'src')

/**
 * Read a JSON file.
 *
 * @param {string} file The path.
 * @return {object} The parsed content.
 */
const json = (file) => JSON.parse(readFileSync(file, 'utf8'))

/**
 * The bundled base manifest.
 *
 * @return {object} `src/manifest.json`.
 * @spec openspec/changes/filinq-adopt-buildmanifest-pipeline/specs/menu-architecture/spec.md#requirement-manifest-assembled-from-adr-037-fragments
 */
export function baseManifest() {
	return json(join(SRC, 'manifest.json'))
}

/**
 * The fragments, sorted by file name as the webpack collector sorts them.
 *
 * @return {Array<{file: string, content: object}>} Each fragment with its file name.
 * @spec openspec/changes/filinq-adopt-buildmanifest-pipeline/specs/menu-architecture/spec.md#requirement-manifest-assembled-from-adr-037-fragments
 */
export function manifestFragments() {
	return readdirSync(join(SRC, 'manifest.d'))
		.filter((file) => file.endsWith('.json'))
		.sort()
		.map((file) => ({ file, content: json(join(SRC, 'manifest.d', file)) }))
}

/**
 * The manifest the SPA renders from.
 *
 * @return {object} The built manifest.
 * @spec openspec/changes/filinq-adopt-buildmanifest-pipeline/specs/menu-architecture/spec.md#requirement-manifest-assembled-from-adr-037-fragments
 */
export function effectiveManifest() {
	return buildManifest(
		baseManifest(),
		manifestFragments().map((f) => f.content),
		json(join(SRC, 'menu-layout.json')),
	)
}
