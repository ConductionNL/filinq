/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * `OCA.Filinq.mountCompare(el, { original, delivered, labels })`: mount the
 * original/delivered split view into an element another app owns. Returns a
 * handle with `unmount()`. The component emits nothing to the host; the host
 * closes its own dialog and calls `unmount()`.
 */

import { assertCompareFiles } from './comparePanes.js'

/**
 * Mount the compare view.
 *
 * @param {HTMLElement} el The element to render into; the host owns it.
 * @param {object} options `{ original, delivered, labels? }`, see comparePanes.js.
 * @param {object} deps `{ createApp, component }`, injected by the entry so this stays testable.
 * @return {{unmount: function(): void}} The handle.
 * @throws {TypeError} When `el` is not an element or a file is unusable.
 * @spec openspec/changes/anonymization-review-workbench/specs/anonymization-review-workbench/spec.md#requirement-the-split-view-is-mountable-by-another-app-req-ddarw-014
 */
export function mountCompare(el, options, deps) {
	if (!el || typeof el !== 'object' || el.nodeType !== 1) {
		throw new TypeError('mountCompare: pass the element to render into')
	}
	assertCompareFiles(options)
	const app = deps.createApp(deps.component, {
		original: options.original,
		delivered: options.delivered,
		labels: options.labels || {},
	})
	app.mount(el)
	let mounted = true
	return {
		unmount() {
			if (mounted) {
				mounted = false
				app.unmount()
			}
		},
	}
}
