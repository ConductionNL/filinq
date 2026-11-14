/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * HTML that comes from a document or from stored content is bound with
 * `v-html`, so it is sanitised first: scripts, event handlers and
 * `javascript:` links are removed, formatting stays.
 */
import DOMPurify from 'dompurify'

/**
 * Sanitise HTML for binding with `v-html`.
 *
 * @param {string|null|undefined} html The untrusted HTML.
 * @return {string} The HTML without scripts, event handlers or script URLs.
 * @spec exclude Shared sanitiser for v-html bindings, no feature of its own.
 */
export function sanitizeHtml(html) {
	return DOMPurify.sanitize(String(html ?? ''), { USE_PROFILES: { html: true } })
}
