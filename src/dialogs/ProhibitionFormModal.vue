<script>
import { translate as t } from '@nextcloud/l10n'
import {
	NcButton,
	NcCheckboxRadioSwitch,
	NcDialog,
	NcLoadingIcon,
	NcSelect,
	NcTextField,
} from '@nextcloud/vue'
import Delete from 'vue-material-design-icons/Delete.vue'

/**
 *
 */
function blankForm() {
	return {
		primaryName: '',
		entityType: 'PERSON',
		reason: '',
		legalAuthority: '',
		caseReference: '',
		severity: 'medium', // matches the publicationProhibition.severity default in docudesk_register.json
		jurisdiction: '',
		validUntil: '',
		active: true,
		matchRules: [],
	}
}

export default {
	name: 'ProhibitionFormModal',
	components: {
		NcButton,
		NcCheckboxRadioSwitch,
		NcDialog,
		NcLoadingIcon,
		NcSelect,
		NcTextField,
		Delete,
	},

	props: {
		open: { type: Boolean, required: true },
		editingRecord: { type: Object, default: null },
		saving: { type: Boolean, default: false },
		formError: { type: String, default: '' },
	},

	emits: ['update:open', 'submit', 'cancel'],
	data() {
		return {
			form: blankForm(),
			entityTypeOptions: ['PERSON', 'ORGANIZATION', 'OTHER'],
			// Canonical set per the publicationProhibition schema's severity
			// enum in docudesk_register.json. Keep in lock-step with the
			// register; widening here without widening the schema would
			// silently fail the OR write-time validation.
			severityOptions: ['high', 'medium', 'low'],
			matchTypeOptions: ['exact', 'normalized', 'bsn', 'kvk'],
		}
	},

	computed: {
		editing() {
			return this.editingRecord !== null
		},

		dialogTitle() {
			return this.editing
				? t('docudesk', 'Edit publish-never rule')
				: t('docudesk', 'Add publish-never rule')
		},

		canSubmit() {
			return (
				this.form.primaryName.trim() !== ''
				&& this.form.reason.trim() !== ''
				&& this.form.matchRules.length > 0
				&& this.form.matchRules.every((r) => r.type && r.value !== '')
			)
		},

		onlyNameRules() {
			if (this.form.matchRules.length === 0) {
				return false
			}
			return this.form.matchRules.every(
				(r) => r.type === 'exact' || r.type === 'normalized',
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
		t,
		resetForm() {
			if (this.editingRecord) {
				this.form = {
					...blankForm(),
					...this.editingRecord,
					matchRules: Array.isArray(this.editingRecord.matchRules)
						? this.editingRecord.matchRules.map((r) => ({ ...r }))
						: [],
				}
			} else {
				this.form = blankForm()
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
</script>

<template>
	<NcDialog
		v-if="open"
		:name="dialogTitle"
		:open="open"
		size="normal"
		@update:open="$emit('update:open', $event)">
		<div class="prohibition-form">
			<NcTextField
				v-model="form.primaryName"
				:label="t('docudesk', 'Primary name (Dutch)')"
				required />
			<NcSelect
				v-model="form.entityType"
				:options="entityTypeOptions"
				:inputLabel="t('docudesk', 'Entity type')"
				:label="t('docudesk', 'Entity type')"
				required />
			<NcTextField
				v-model="form.reason"
				:label="t('docudesk', 'Reason (markdown allowed)')"
				required />
			<NcTextField
				v-model="form.legalAuthority"
				:label="
					t('docudesk', 'Legal authority (court order, statute, …)')
				" />
			<NcTextField
				v-model="form.caseReference"
				:label="t('docudesk', 'Case reference (optional)')" />
			<NcSelect
				v-model="form.severity"
				:options="severityOptions"
				:inputLabel="t('docudesk', 'Severity')"
				:label="t('docudesk', 'Severity')" />
			<NcTextField
				v-model="form.jurisdiction"
				:label="t('docudesk', 'Jurisdiction (optional)')" />
			<NcTextField
				v-model="form.validUntil"
				:label="t('docudesk', 'Valid until (ISO 8601, optional)')" />
			<NcCheckboxRadioSwitch v-model="form.active" type="switch">
				{{ t('docudesk', 'Active') }}
			</NcCheckboxRadioSwitch>

			<h4>{{ t('docudesk', 'Match rules') }}</h4>
			<div v-if="!form.matchRules?.length" class="form-warning">
				{{
					t(
						'docudesk',
						'Add at least one match rule. Prefer stable identifiers (BSN/KvK) over name-only matches — names alone produce false positives.',
					)
				}}
			</div>
			<div
				v-for="(rule, idx) in form.matchRules"
				:key="idx"
				class="match-rule-row">
				<NcSelect
					v-model="rule.type"
					:options="matchTypeOptions"
					:inputLabel="t('docudesk', 'Match type')"
					:label="t('docudesk', 'Match type')" />
				<NcTextField
					v-model="rule.value"
					:label="t('docudesk', 'Match value')" />
				<!--
					Icon-only, so it needs its own name. The row index is part of
					it: every row renders the same icon, and "Remove match rule"
					repeated N times tells a screen-reader user nothing about
					which row focus is on (WCAG 4.1.2).
				-->
				<NcButton
					variant="tertiary"
					:aria-label="
						t('docudesk', 'Remove match rule {number}', {
							number: idx + 1,
						})
					"
					@click="removeRule(idx)">
					<template #icon>
						<Delete :size="20" />
					</template>
				</NcButton>
			</div>
			<NcButton variant="secondary" @click="addRule">
				{{ t('docudesk', 'Add match rule') }}
			</NcButton>

			<div v-if="onlyNameRules" class="form-warning">
				{{
					t(
						'docudesk',
						'Warning: only name-based rules are present. Names alone often produce false positives — consider adding a BSN or KvK match.',
					)
				}}
			</div>

			<div v-if="formError" class="form-error">
				{{ formError }}
			</div>
		</div>

		<template #actions>
			<NcButton variant="tertiary" @click="onCancel">
				{{ t('docudesk', 'Cancel') }}
			</NcButton>
			<NcButton
				variant="primary"
				:disabled="saving || !canSubmit"
				@click="submit">
				<template v-if="saving" #icon>
					<NcLoadingIcon :size="20" />
				</template>
				{{ editing ? t('docudesk', 'Save') : t('docudesk', 'Create') }}
			</NcButton>
		</template>
	</NcDialog>
</template>
