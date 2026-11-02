/**
 * DOM-based entity highlighting for viewers that render library HTML we do not
 * template ourselves (e.g. the WordViewer's mammoth output via `v-html`).
 *
 * Where `TextViewer` can render highlight segments directly, this walks the
 * already-rendered DOM, wraps matching text in highlight spans, and can undo
 * that wrapping so the highlights can be recomputed when the entity list or the
 * pending selection changes.
 */

import { entityTypeColor } from './entityTypes.js'
import { buildHighlightSegments, PENDING_TYPE } from './highlightText.js'

/**
 * Remove all highlight spans previously injected by `applyDomHighlights`,
 * restoring the original text nodes.
 *
 * @param {HTMLElement|null} root Container element to clean.
 * @return {void}
 */
export function clearDomHighlights(root) {
	if (!root) {
		return
	}
	const spans = root.querySelectorAll('[data-dd-hl]')
	spans.forEach((span) => {
		const parent = span.parentNode
		if (!parent) {
			return
		}
		parent.replaceChild(document.createTextNode(span.textContent), span)
		// Merge the restored text node back with its neighbours so re-running
		// the matcher sees contiguous text.
		parent.normalize()
	})
}

/**
 * The non-blank text nodes under `root`, collected up front because mutating
 * the tree while walking it is fragile.
 *
 * @param {HTMLElement} root Container element.
 * @return {Text[]}
 */
function nonBlankTextNodes(root) {
	const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, null)
	const targets = []
	let node = walker.nextNode()
	while (node) {
		if (node.nodeValue && node.nodeValue.trim()) {
			targets.push(node)
		}
		node = walker.nextNode()
	}
	return targets
}

/**
 * The DOM node for one highlight segment: a highlight span for a typed
 * segment, a plain text node otherwise.
 *
 * @param {{text: string, type: (string|null)}} seg The segment.
 * @return {Node}
 */
function segmentNode(seg) {
	if (!seg.type) {
		return document.createTextNode(seg.text)
	}
	const span = document.createElement('span')
	span.setAttribute('data-dd-hl', '')
	if (seg.type === PENDING_TYPE) {
		span.className = 'dd-hl dd-hl--pending'
	} else {
		span.className = 'dd-hl'
		span.style.backgroundColor = entityTypeColor(seg.type)
	}
	span.textContent = seg.text
	return span
}

/**
 * Replace one text node by its highlight segments, when any segment is
 * highlighted.
 *
 * @param {Text} textNode The text node.
 * @param {Array<{value: string, type: string}>} values Values to highlight.
 * @return {void}
 */
function highlightTextNode(textNode, values) {
	const segments = buildHighlightSegments(textNode.nodeValue, values)
	if (!segments.some((s) => s.type)) {
		return
	}
	const frag = document.createDocumentFragment()
	for (const seg of segments) {
		frag.appendChild(segmentNode(seg))
	}
	textNode.parentNode.replaceChild(frag, textNode)
}

/**
 * Wrap every occurrence of the given values inside `root` in a highlight span.
 * Clears any previous highlights first, so this is safe to call repeatedly.
 *
 * @param {HTMLElement|null} root Container element to highlight in.
 * @param {Array<{value: string, type: string}>} entities Values to highlight.
 * @param {string} [pendingValue] Optional pending selection to mark distinctly.
 * @return {void}
 * @spec openspec/specs/document-preview/spec.md#requirement-format-specific-in-app-document-preview-req-ddprv-001
 */
export function applyDomHighlights(root, entities, pendingValue) {
	if (!root) {
		return
	}
	clearDomHighlights(root)

	const all = [
		...(pendingValue ? [{ value: pendingValue, type: PENDING_TYPE }] : []),
		...(entities || []),
	]
	if (!all.length) {
		return
	}
	for (const textNode of nonBlankTextNodes(root)) {
		highlightTextNode(textNode, all)
	}
}
