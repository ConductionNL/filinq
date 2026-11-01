/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * What every filinq leaf shares: rooting its widget at a host-owned element,
 * and registering its descriptor with OpenRegister's integration registry.
 *
 * @spec openspec/specs/leaves-library-card/spec.md
 */
import { translate as t } from '@nextcloud/l10n'
import { createApp } from 'vue'

/**
 * The mount and unmount pair of one leaf widget.
 *
 * Filinq is Vue 3 and a consuming host may be Vue 2.7. A Vue 3 SFC handed to
 * such a host renders blank under its incompatible runtime, so the host hands
 * over a bare DOM element and each side runs its own framework across that
 * neutral boundary. Mounting is idempotent per element. The apps are keyed by
 * ELEMENT, not by leaf id: one page may show a leaf as a tab and as a widget at
 * once, and each needs to unmount independently.
 *
 * @param {object} component The widget's Vue component.
 * @return {{mount: function(Element, object): void, unmount: function(Element): void}}
 * @spec openspec/specs/leaves-library-card/spec.md
 */
export function leafMounter(component) {
	const mountedApps = new Map()
	return {
		mount(el, props) {
			if (el === undefined || el === null || mountedApps.has(el) === true) {
				return
			}
			const app = createApp(component, { ...(props || {}) })
			// The SFC calls `t(...)` as a global, which main.js installs for the
			// app bundle; a leaf mounts its own instance, so install it here too.
			app.config.globalProperties.t = t
			app.mount(el)
			mountedApps.set(el, app)
		},
		unmount(el) {
			const app = mountedApps.get(el)
			if (app === undefined) {
				return
			}
			mountedApps.delete(el)
			app.unmount()
		},
	}
}

/**
 * The shared OpenRegister integration registry a leaf registers on.
 *
 * Installs a load-order-safe queue stub when OpenRegister's own bundle has not
 * installed the real registry yet, so a filinq bundle that happens to load
 * first is not simply lost. Without a global there is nowhere to register, so
 * the registry returned then ignores what it is given.
 *
 * Each leaf calls `register` on it with its own descriptor, which keeps the
 * registration readable at the leaf for the integration parity check.
 *
 * @param {object} [globalRef] Global to attach to (defaults to `window`).
 * @return {{register: function(object): void}} The registry.
 * @spec openspec/specs/leaves-library-card/spec.md
 */
export function integrationRegistry(globalRef) {
	const target = globalRef || (typeof window !== 'undefined' ? window : null)
	if (target === null) {
		return { register() {} }
	}

	target.OCA = target.OCA || {}
	target.OCA.OpenRegister = target.OCA.OpenRegister || {}
	target.OCA.OpenRegister.integrations = target.OCA.OpenRegister.integrations || {
		_queue: [],
		register(entry) {
			this._queue.push(entry)
		},
	}

	return target.OCA.OpenRegister.integrations
}
