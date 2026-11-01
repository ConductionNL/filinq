/**
 * Re-homing merged navigation entries onto the canonical menu layout.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/filinq-adopt-buildmanifest-pipeline/specs/menu-architecture/spec.md#requirement-navigation-manifest-assembled-via-shared-buildmanifest
 */

/**
 * Merge incoming menu items onto a target list by `id`. A top-level or child
 * entry already present is extended (only filling undefined scalar keys, then
 * recursing into children); a new entry is appended. Used by the relocation
 * pass to re-home leaves/groups into a target group without duplicating ids.
 *
 * @param {Array<object>} target The accumulated menu (mutated in place).
 * @param {Array<object>} incoming Menu items to merge in.
 * @return {void}
 * @spec openspec/changes/filinq-adopt-buildmanifest-pipeline/specs/menu-architecture/spec.md#requirement-navigation-manifest-assembled-via-shared-buildmanifest
 */
export function mergeMenuItems(target, incoming) {
	incoming.forEach((item) => mergeMenuItem(target, item))
}

/**
 * The scalar keys a merge fills in on an entry that already exists.
 *
 * @type {Array<string>}
 */
const MERGED_KEYS = [
	'label',
	'icon',
	'route',
	'order',
	'section',
	'featureFlag',
	'permission',
	'visibleIf',
	'href',
	'action',
]

/**
 * The link keys that make an entry worth showing on its own.
 *
 * @type {Array<string>}
 */
const LINK_KEYS = ['route', 'href', 'action']

/**
 * How often the relocation pass runs at most.
 *
 * @type {number}
 */
const MAX_PASSES = 5

/**
 * Whether a menu entry has children.
 *
 * @param {object} entry The menu entry.
 * @return {boolean}
 */
function hasChildren(entry) {
	return Array.isArray(entry.children) && entry.children.length > 0
}

/**
 * Merge one incoming item onto a target list by `id`.
 *
 * @param {Array<object>} target The accumulated menu (mutated in place).
 * @param {object} item The menu item to merge in.
 * @return {void}
 */
function mergeMenuItem(target, item) {
	const existing = target.find((t) => t.id === item.id)
	if (!existing) {
		target.push({
			...item,
			children: Array.isArray(item.children)
				? [...item.children]
				: item.children,
		})
		return
	}
	fillMissingKeys(existing, item)
	if (hasChildren(item)) {
		if (!Array.isArray(existing.children)) {
			existing.children = []
		}
		mergeMenuItems(existing.children, item.children)
	}
}

/**
 * Copy the scalar keys an existing entry lacks from an incoming item.
 *
 * @param {object} existing The entry already in the menu (mutated in place).
 * @param {object} item The incoming item.
 * @return {void}
 */
function fillMissingKeys(existing, item) {
	for (const key of MERGED_KEYS) {
		if (existing[key] === undefined && item[key] !== undefined) {
			existing[key] = item[key]
		}
	}
}

/**
 * Re-home merged menu entries onto the canonical navigation layout declared
 * by `src/menu-layout.json#relocations` (`{ sourceId: targetGroupId }`).
 *
 *  - A relocated GROUP dissolves: its children merge (by id) into the
 *    target group and the now-empty shell is dropped.
 *  - A relocated LEAF (top-level or child of any group) moves under the
 *    target group.
 *  - A child group relocated onto its own parent flattens into it.
 *  - Unknown source ids are inert; a missing target group keeps the entry
 *    at the top level so nothing silently disappears.
 *
 * Runs in passes until stable (children freed by a dissolved group can
 * themselves be relocated on the next pass).
 *
 * @param {Array<object>} menu The merged menu (mutated in place).
 * @param {Record<string, string>|undefined} relocations Source-id → target-group-id map.
 * @return {Array<object>} The menu with relocations applied.
 * @spec openspec/changes/filinq-adopt-buildmanifest-pipeline/specs/menu-architecture/spec.md#requirement-navigation-manifest-assembled-via-shared-buildmanifest
 */
export function applyMenuRelocations(menu, relocations) {
	if (!relocations || typeof relocations !== 'object') return menu
	for (let pass = 0; pass < MAX_PASSES; pass++) {
		const moves = takeMoves(menu, relocations)
		if (moves.length === 0) break
		moves.forEach((move) => applyMove(menu, move))
	}
	return menu.filter(isShown)
}

/**
 * Take every relocated entry out of the menu: top-level entries and the
 * children of groups.
 *
 * @param {Array<object>} menu The menu (mutated in place).
 * @param {Record<string, string>} relocations Source-id → target-group-id map.
 * @return {Array<{node: object, target: string}>} The moves, in walk order.
 */
function takeMoves(menu, relocations) {
	const moves = []
	for (let i = menu.length - 1; i >= 0; i--) {
		const node = menu[i]
		const target = relocations[node.id]
		if (target && target !== node.id) {
			menu.splice(i, 1)
			moves.push({ node, target })
			continue
		}
		moves.push(...takeChildMoves(node, relocations))
	}
	return moves
}

/**
 * Take the relocated children out of a group.
 *
 * @param {object} group The group (its children are mutated in place).
 * @param {Record<string, string>} relocations Source-id → target-group-id map.
 * @return {Array<{node: object, target: string}>} The moves, last child first.
 */
function takeChildMoves(group, relocations) {
	const moves = []
	const children = Array.isArray(group.children) ? group.children : []
	for (let j = children.length - 1; j >= 0; j--) {
		const move = { node: children[j], target: relocations[children[j].id] }
		if (leavesParent(move, group.id)) {
			children.splice(j, 1)
			moves.push(move)
		}
	}
	return moves
}

/**
 * Whether a child's relocation takes it out of its group. A leaf relocated
 * onto its own parent stays; a group relocated there flattens into it.
 *
 * @param {{node: object, target: string|undefined}} move The candidate move.
 * @param {string} parentId The id of the child's group.
 * @return {boolean}
 */
function leavesParent(move, parentId) {
	if (!move.target) {
		return false
	}
	return move.target !== parentId || Array.isArray(move.node.children)
}

/**
 * Re-home one taken entry: a group's children merge into the target group,
 * a leaf merges in itself, and a missing target keeps it at the top level.
 *
 * @param {Array<object>} menu The menu (mutated in place).
 * @param {{node: object, target: string}} move The move.
 * @return {void}
 */
function applyMove(menu, move) {
	const group = menu.find((m) => m.id === move.target)
	if (!group) {
		menu.push(move.node)
		return
	}
	if (!Array.isArray(group.children)) group.children = []
	mergeMenuItems(
		group.children,
		Array.isArray(move.node.children) ? move.node.children : [move.node],
	)
}

/**
 * Whether an entry stays in the menu: a caption, a link, or a group that
 * still has children.
 *
 * @param {object} entry The menu entry.
 * @return {boolean}
 */
function isShown(entry) {
	return (
		entry.type === 'caption'
		|| LINK_KEYS.some((key) => entry[key])
		|| hasChildren(entry)
	)
}
