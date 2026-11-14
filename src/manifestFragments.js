/**
 * The manifest fragments under `src/manifest.d/` (ADR-037), in file-name order.
 *
 * The one app-local step of the shared manifest pipeline (ADR-044 Rule 1):
 * `require.context` is resolved by this app's own webpack build, so the app
 * collects its fragment objects and hands them to `buildManifest`. Kept in
 * its own module so the entry point can be loaded without webpack.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/filinq-adopt-buildmanifest-pipeline/specs/menu-architecture/spec.md#requirement-manifest-assembled-from-adr-037-fragments
 */

// `require.context` is a WEBPACK build-time API, not CommonJS `require`: the
// bundler rewrites this call at compile time and no `require` exists at
// runtime. Scoped to this one identifier so a genuinely undefined name
// elsewhere in the file still fails.
/* global require */
const fragmentContext = require.context('./manifest.d/', false, /\.json$/)

export default fragmentContext
	.keys()
	.sort()
	.map((key) => fragmentContext(key))
