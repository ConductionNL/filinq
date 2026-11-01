/**
 * Mixin for the entity-policy form modals (publish always, publish never):
 * an NcDialog form whose record carries a list of match rules.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The modal gives its empty form as the component option `blankForm`, a
 * function returning a fresh object with a `matchRules` array. The form is
 * reset from `editingRecord` each time `open` turns true.
 */

export const matchRuleForm = {
	props: {
		open: { type: Boolean, required: true },
		editingRecord: { type: Object, default: null },
		saving: { type: Boolean, default: false },
		formError: { type: String, default: '' },
	},

	emits: ['update:open', 'submit', 'cancel'],

	data() {
		return {
			form: this.$options.blankForm(),
			matchTypeOptions: ['exact', 'normalized', 'bsn', 'kvk'],
		}
	},

	computed: {
		editing() {
			return this.editingRecord !== null
		},

		/**
		 * Whether the form has at least one match rule and every rule has a
		 * type and a value.
		 *
		 * @return {boolean}
		 * @spec openspec/specs/entity-publication-policies/spec.md#requirement-three-separate-admin-surfaces-must-exist
		 */
		matchRulesComplete() {
			return (
				this.form.matchRules.length > 0
				&& this.form.matchRules.every((r) => r.type && r.value !== '')
			)
		},
	},

	watch: {
		open(now) {
			if (now) {
				this.resetForm()
			}
		},
	},

	methods: {
		/**
		 * Fill the form from `editingRecord`, copying its match rules, or
		 * empty it for a create.
		 *
		 * @spec openspec/specs/entity-publication-policies/spec.md#requirement-three-separate-admin-surfaces-must-exist
		 */
		resetForm() {
			const blank = this.$options.blankForm()
			if (this.editingRecord) {
				this.form = {
					...blank,
					...this.editingRecord,
					matchRules: Array.isArray(this.editingRecord.matchRules)
						? this.editingRecord.matchRules.map((r) => ({ ...r }))
						: [],
				}
			} else {
				this.form = blank
			}
		},

		addRule() {
			this.form.matchRules.push({ type: 'exact', value: '' })
		},

		removeRule(idx) {
			this.form.matchRules.splice(idx, 1)
		},

		submit() {
			this.$emit('submit', {
				...this.form,
				matchRules: this.form.matchRules.map((r) => ({ ...r })),
			})
		},

		onCancel() {
			this.$emit('update:open', false)
			this.$emit('cancel')
		},
	},
}
