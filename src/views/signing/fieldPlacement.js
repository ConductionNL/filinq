/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Field placements on the new signing request form. A placement names a
 * signer by position, a page, and a box in page-relative coordinates from 0
 * to 1 with the origin top left: the shape the server validates and the
 * native provider draws before it signs. Every change returns a new list and
 * keeps the box on the page, so the form can never send a box the server
 * would refuse for leaving it.
 */

/** The five static field types. Conditional fields are future scope. */
export const FIELD_TYPES = ['signature', 'initials', 'date', 'text', 'checkbox']

const SIZES = {
	signature: [0.3, 0.06],
	initials: [0.1, 0.04],
	date: [0.16, 0.035],
	text: [0.3, 0.04],
	checkbox: [0.035, 0.025],
}

const MIN = 0.02

/**
 * Keep a number between two bounds.
 *
 * @param {number} value The number.
 * @param {number} low The lowest allowed.
 * @param {number} high The highest allowed.
 * @return {number}
 */
function clamp(value, low, high) {
	return Math.min(high, Math.max(low, value))
}

/**
 * Add a box of the type's usual size, centred on a point of the page.
 *
 * @param {Array<object>} list The placements.
 * @param {{signerIndex: number, page: number, type: string, x: number, y: number}} at Where and what.
 * @return {Array<object>} The new list.
 * @spec openspec/changes/bulk-signing-field-builder/tasks.md#task-3.3
 */
export function addPlacement(list, at) {
	const [width, height] = SIZES[at.type] ?? SIZES.text
	return [
		...list,
		{
			signerIndex: at.signerIndex,
			page: at.page,
			type: at.type,
			width,
			height,
			x: clamp(at.x - width / 2, 0, 1 - width),
			y: clamp(at.y - height / 2, 0, 1 - height),
		},
	]
}

/**
 * Move one box by a distance, stopping at the page edge.
 *
 * @param {Array<object>} list The placements.
 * @param {number} index The box.
 * @param {number} dx Distance to the right, as a share of the page width.
 * @param {number} dy Distance down, as a share of the page height.
 * @return {Array<object>} The new list.
 * @spec openspec/changes/bulk-signing-field-builder/tasks.md#task-3.3
 */
export function movePlacement(list, index, dx, dy) {
	return list.map((box, i) =>
		i !== index
			? box
			: {
					...box,
					x: clamp(box.x + dx, 0, 1 - box.width),
					y: clamp(box.y + dy, 0, 1 - box.height),
				},
	)
}

/**
 * Grow or shrink one box from its bottom right corner.
 *
 * @param {Array<object>} list The placements.
 * @param {number} index The box.
 * @param {number} dw Change in width.
 * @param {number} dh Change in height.
 * @return {Array<object>} The new list.
 * @spec openspec/changes/bulk-signing-field-builder/tasks.md#task-3.3
 */
export function resizePlacement(list, index, dw, dh) {
	return list.map((box, i) =>
		i !== index
			? box
			: {
					...box,
					width: clamp(box.width + dw, MIN, 1 - box.x),
					height: clamp(box.height + dh, MIN, 1 - box.y),
				},
	)
}

/**
 * Remove a signer's boxes and shift later signers down one position, as
 * removing a signer row does to the signer list.
 *
 * @param {Array<object>} list The placements.
 * @param {number} signerIndex The removed signer.
 * @return {Array<object>} The new list.
 * @spec openspec/changes/bulk-signing-field-builder/tasks.md#task-3.3
 */
export function dropSignerPlacements(list, signerIndex) {
	return list
		.filter((box) => box.signerIndex !== signerIndex)
		.map((box) =>
			box.signerIndex > signerIndex
				? { ...box, signerIndex: box.signerIndex - 1 }
				: box,
		)
}

/**
 * The placements as the request sends them: the seven keys, rounded.
 *
 * @param {Array<object>} list The placements.
 * @return {Array<object>}
 * @spec openspec/changes/bulk-signing-field-builder/tasks.md#task-3.1
 */
export function toRequestPlacements(list) {
	const round = (n) => Math.round(n * 10000) / 10000
	return list.map((box) => ({
		signerIndex: box.signerIndex,
		page: box.page,
		x: round(box.x),
		y: round(box.y),
		width: round(Math.min(box.width, 1 - round(box.x))),
		height: round(Math.min(box.height, 1 - round(box.y))),
		type: box.type,
	}))
}
