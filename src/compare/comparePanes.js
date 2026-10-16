/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The two panes of the compare view: which viewer renders each file and with
 * which props. Pure, so the contract other apps rely on is testable without a
 * DOM. The routing itself is the file viewer's own (viewerRouting.js): the
 * compare view adds no second idea of which viewer opens which file.
 *
 * A compare file is `{ fileName, mimeType, path?, url?, downloadUrl? }`:
 *   - `path`: a path in the current user's files, read over WebDAV;
 *   - `url`: a same-origin URL that answers the file's bytes, for files that
 *     are not in the user's own storage (another app's record, for example);
 *   - `downloadUrl`: where the "Download" link of the pane points; defaults to
 *     `url`.
 * One of `path` or `url` is required.
 */

import {
	detectViewer,
	viewerComponentFor,
	viewerPropsFor,
} from '../services/viewerRouting.js'

export const COMPARE_SIDES = ['original', 'delivered']

/**
 * Check the files handed to the compare view, and say which one is wrong.
 *
 * @param {object} files `{ original, delivered }`.
 * @return {void}
 * @throws {TypeError} When a side is missing or names neither a path nor a URL.
 * @spec openspec/changes/anonymization-review-workbench/specs/anonymization-review-workbench/spec.md#requirement-the-split-view-is-mountable-by-another-app-req-ddarw-014
 */
export function assertCompareFiles(files) {
	if (!files || typeof files !== 'object') {
		throw new TypeError('mountCompare: pass { original, delivered }')
	}
	for (const side of COMPARE_SIDES) {
		const file = files[side]
		if (!file || typeof file !== 'object') {
			throw new TypeError(`mountCompare: the ${side} file is missing`)
		}
		if (!file.path && !file.url) {
			throw new TypeError(
				`mountCompare: the ${side} file needs a path or a url`,
			)
		}
	}
}

/**
 * The viewer and props for one pane.
 *
 * @param {object} file A compare file.
 * @return {{kind: (string|null), component: (string|null), props: object, downloadUrl: string}}
 *   `component` is null when no in-app viewer renders the file.
 * @spec openspec/changes/anonymization-review-workbench/specs/anonymization-review-workbench/spec.md#requirement-the-split-view-is-mountable-by-another-app-req-ddarw-014
 */
export function comparePane(file) {
	const kind = detectViewer(file)
	const component = viewerComponentFor(kind)
	const downloadUrl = file?.downloadUrl || file?.url || ''
	if (!component) {
		return { kind, component: null, props: {}, downloadUrl }
	}
	if (file.url && kind !== 'eml') {
		// The bytes come from the URL; `path` only keys the viewer's reload.
		return {
			kind,
			component,
			props: { path: file.path || file.url, url: file.url },
			downloadUrl,
		}
	}
	return { kind, component, props: viewerPropsFor(file, kind), downloadUrl }
}
