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
 * Wrap every occurrence of the given values inside `root` in a highlight span.
 * Clears any previous highlights first, so this is safe to call repeatedly.
 *
 * @param {HTMLElement|null} root Container element to highlight in.
 * @param {Array<{value: string, type: string}>} entities Values to highlight.
 * @param {string} [pendingValue] Optional pending selection to mark distinctly.
 * @return {void}
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

	// Collect text nodes up front — mutating the tree while walking it is
	// fragile.
	const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, null)
	const targets = []
	let node = walker.nextNode()
	while (node) {
		if (node.nodeValue && node.nodeValue.trim()) {
			targets.push(node)
		}
		node = walker.nextNode()
	}

	for (const textNode of targets) {
		const segments = buildHighlightSegments(textNode.nodeValue, all)
		if (!segments.some((s) => s.type)) {
			continue
		}
		const frag = document.createDocumentFragment()
		for (const seg of segments) {
			if (seg.type) {
				const span = document.createElement('span')
				span.setAttribute('data-dd-hl', '')
				if (seg.type === PENDING_TYPE) {
					span.className = 'dd-hl dd-hl--pending'
				} else {
					span.className = 'dd-hl'
					span.style.backgroundColor = entityTypeColor(seg.type)
				}
				span.textContent = seg.text
				frag.appendChild(span)
			} else {
				frag.appendChild(document.createTextNode(seg.text))
			}
		}
		textNode.parentNode.replaceChild(frag, textNode)
	}
}
