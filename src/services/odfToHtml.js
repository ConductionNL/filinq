/**
 * OpenDocument (ODF) → HTML / text transform for the in-app ODT preview.
 *
 * PhpWord's ODText reader drops tables/headers/footers, and there is no
 * mammoth-equivalent for ODT, so the viewer renders ODT itself: unzip the
 * `.odt` (a ZIP) and transform its `content.xml` here. The transform is a
 * deliberately small, safe subset — it emits a whitelist of structural tags
 * (headings, paragraphs, tables, lists, breaks) with all text escaped and NO
 * attributes, so the result is safe to bind with `v-html` (no scripts, no
 * inline styles, no external references).
 *
 * Pure functions, no DOM-viewer coupling, so they can be unit-tested.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 */

const TEXT_NODE = 3
const ELEMENT_NODE = 1

/**
 * Escape text so it is safe to inject as HTML text content.
 *
 * @param {string} value Raw text.
 * @return {string} Escaped text.
 */
function escapeHtml(value) {
	return String(value)
		.replace(/&/g, '&amp;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;')
}

/**
 * Map an ODF `text:h` outline level to an `h1`–`h6` tag name.
 *
 * @param {?string} level The `text:outline-level` attribute value.
 * @return {string} Heading tag name.
 */
function headingTag(level) {
	const parsed = parseInt(level, 10)
	const clamped = Math.min(Math.max(Number.isNaN(parsed) ? 2 : parsed, 1), 6)
	return `h${clamped}`
}

/**
 * Render an ODF node (and its subtree) to an HTML string.
 *
 * @param {Node} node DOM node from the parsed content.xml.
 * @return {string} HTML fragment.
 */
function renderNode(node) {
	if (node.nodeType === TEXT_NODE) {
		return escapeHtml(node.nodeValue || '')
	}

	if (node.nodeType !== ELEMENT_NODE) {
		return ''
	}

	const inner = () => Array.from(node.childNodes).map(renderNode).join('')

	switch (node.tagName) {
	case 'text:h': {
		const tag = headingTag(node.getAttribute('text:outline-level'))
		return `<${tag}>${inner()}</${tag}>`
	}
	case 'text:p':
		return `<p>${inner()}</p>`
	case 'text:line-break':
		return '<br>'
	case 'text:tab':
		return ' '
	case 'text:s': {
		const count = parseInt(node.getAttribute('text:c'), 10)
		return ' '.repeat(Number.isNaN(count) || count < 1 ? 1 : count)
	}
	case 'table:table':
		return `<table>${inner()}</table>`
	case 'table:table-row':
		return `<tr>${inner()}</tr>`
	case 'table:table-cell':
		return `<td>${inner()}</td>`
	case 'text:list':
		return `<ul>${inner()}</ul>`
	case 'text:list-item':
		return `<li>${inner()}</li>`
	case 'draw:image':
	case 'draw:object':
		// Binary/embedded objects are not rendered in the preview.
		return ''
	default:
		// Wrappers (office:body, office:text, text:span, text:a,
		// table:table-header-rows, …) contribute their children only.
		return inner()
	}
}

/**
 * Parse an ODF XML part into a Document, or null when it cannot be parsed.
 *
 * @param {string} xml An ODF XML part (content.xml / styles.xml).
 * @return {?Document}
 */
function parseOdfXml(xml) {
	if (typeof xml !== 'string' || xml.trim() === '') {
		return null
	}

	const doc = new DOMParser().parseFromString(xml, 'application/xml')
	if (doc.getElementsByTagName('parsererror').length > 0) {
		return null
	}

	return doc
}

/**
 * Transform an ODF `content.xml` string into a safe HTML fragment.
 *
 * @param {string} contentXml The `content.xml` part of an .odt.
 * @return {string} HTML fragment (empty string when unparseable).
 */
export function odfXmlToHtml(contentXml) {
	const doc = parseOdfXml(contentXml)
	if (doc === null) {
		return ''
	}

	const body = doc.getElementsByTagName('office:body')[0] || doc.documentElement
	return renderNode(body)
}

/**
 * Extract the concatenated visible text of an ODF part, one paragraph per line.
 *
 * Mirrors the OpenRegister backend's within-paragraph concatenation so the
 * frontend placeholder scan sees the same text the redaction operated on.
 *
 * @param {string} xml An ODF XML part (content.xml / styles.xml).
 * @return {string} Concatenated text (empty string when unparseable).
 */
export function odfXmlToText(xml) {
	const doc = parseOdfXml(xml)
	if (doc === null) {
		return ''
	}

	const paragraphs = [
		...Array.from(doc.getElementsByTagName('text:p')),
		...Array.from(doc.getElementsByTagName('text:h')),
	]
	return paragraphs.map((p) => p.textContent || '').filter((t) => t !== '').join('\n')
}
