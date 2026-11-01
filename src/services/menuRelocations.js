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
	incoming.forEach((item) => {
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
		for (const key of [
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
		]) {
			if (existing[key] === undefined && item[key] !== undefined) {
				existing[key] = item[key]
			}
		}
		if (Array.isArray(item.children) && item.children.length > 0) {
			if (!Array.isArray(existing.children)) {
				existing.children = []
			}
			mergeMenuItems(existing.children, item.children)
		}
	})
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
	for (let pass = 0; pass < 5; pass++) {
		const moves = []
		for (let i = menu.length - 1; i >= 0; i--) {
			const node = menu[i]
			const target = relocations[node.id]
			if (target && target !== node.id) {
				menu.splice(i, 1)
				moves.push({ node, target })
				continue
			}
			if (!Array.isArray(node.children)) continue
			for (let j = node.children.length - 1; j >= 0; j--) {
				const child = node.children[j]
				const childTarget = relocations[child.id]
				if (!childTarget) continue
				if (childTarget === node.id && !Array.isArray(child.children))
					continue
				node.children.splice(j, 1)
				moves.push({ node: child, target: childTarget })
			}
		}
		if (moves.length === 0) break
		moves.forEach(({ node, target }) => {
			const group = menu.find((m) => m.id === target)
			if (!group) {
				menu.push(node)
				return
			}
			if (!Array.isArray(group.children)) group.children = []
			if (Array.isArray(node.children)) {
				mergeMenuItems(group.children, node.children)
			} else {
				mergeMenuItems(group.children, [node])
			}
		})
	}
	return menu.filter(
		(m) =>
			m.type === 'caption'
			|| m.route
			|| m.href
			|| m.action
			|| (Array.isArray(m.children) && m.children.length > 0),
	)
}
