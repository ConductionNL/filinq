/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 */

export default {
	data() {
		return { count: 0, source: 'mixin' }
	},
	methods: {
		increment() {
			this.count += this.step
		},
		label() {
			return 'mixin'
		},
	},
	mounted() {
		this.log.push('mixin mounted')
	},
}
