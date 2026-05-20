<script>
import {
	NcButton,
	NcCheckboxRadioSwitch,
	NcDialog,
	NcLoadingIcon,
	NcNoteCard,
	NcSelect,
	NcTextField,
} from '@nextcloud/vue'
import { translate as t } from '@nextcloud/l10n'

import { anonymizationPocStore } from '../../store/store.js'

/**
 * Entity-type seed for the PoC — flat list per the docudesk#225 scope.
 * A production version would source these from OR's detector vocabulary
 * (presidio + openanonymiser type tags). The PoC keeps it static so
 * the frontend team has a working example.
 */
const ENTITY_TYPES = [
	'PERSON',
	'ORGANIZATION',
	'EMAIL',
	'PHONE_NUMBER',
	'IBAN_CODE',
	'IP_ADDRESS',
	'LOCATION',
	'OTHER',
]

export default {
	name: 'AddManualEntityModal',
	components: {
		NcButton,
		NcCheckboxRadioSwitch,
		NcDialog,
		NcLoadingIcon,
		NcNoteCard,
		NcSelect,
		NcTextField,
	},
	props: {
		open: {
			type: Boolean,
			required: true,
		},
		entry: {
			type: Object,
			default: null,
		},
	},
	emits: ['update:open', 'success'],
	data() {
		return {
			form: {
				value: '',
				type: '',
				category: '',
				wholeWord: true,
				caseSensitive: true,
			},
			submitting: false,
			submitError: null,
		}
	},
	computed: {
		entityTypeOptions() {
			return ENTITY_TYPES
		},
		canSubmit() {
			return this.form.value.trim().length > 0
				&& this.normalisedType().length > 0
				&& !this.submitting
				&& this.entry?.fileId != null
		},
		dialogTitle() {
			if (this.entry?.name) {
				return t('docudesk', 'Add manual entity to {file}', { file: this.entry.name })
			}
			return t('docudesk', 'Add manual entity')
		},
	},
	watch: {
		open(val) {
			if (val === true) {
				this.resetForm()
			}
		},
	},
	methods: {
		t,
		resetForm() {
			this.form = {
				value: '',
				type: '',
				category: '',
				wholeWord: true,
				caseSensitive: true,
			}
			this.submitError = null
			this.submitting = false
		},
		/**
		 * Unwrap `form.type` — NcSelect may bind a plain string or a
		 * `{ value, label }` object depending on the option shape. We
		 * feed it plain strings, but defend anyway.
		 *
		 * @return {string}
		 */
		normalisedType() {
			const raw = this.form.type
			if (typeof raw === 'string') {
				return raw
			}
			if (raw && typeof raw === 'object') {
				return raw.value || raw.label || ''
			}
			return ''
		},
		onCancel() {
			this.$emit('update:open', false)
		},
		async submit() {
			if (!this.canSubmit) {
				return
			}

			const payload = {
				value: this.form.value,
				type: this.normalisedType(),
				wholeWord: !!this.form.wholeWord,
				caseSensitive: !!this.form.caseSensitive,
			}
			if (this.form.category.trim().length > 0) {
				payload.category = this.form.category.trim()
			}

			this.submitting = true
			this.submitError = null
			try {
				const result = await anonymizationPocStore.addManualEntity(this.entry, payload)
				this.submitting = false
				this.$emit('success', result)
				this.$emit('update:open', false)
				this.resetForm()
			} catch (err) {
				this.submitting = false
				this.submitError = err?.message || err?.error || t('docudesk', 'Unknown error')
			}
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
		<div class="manual-entity-form">
			<p class="form-hint">
				{{ t('docudesk', 'Add an exact text occurrence (or recurring value) to this file\'s anonymisation list. The text is matched against the file\'s extracted chunks; one EntityRelation is created per occurrence found.') }}
			</p>

			<NcTextField
				:value.sync="form.value"
				:label="t('docudesk', 'Text to anonymise')"
				:placeholder="t('docudesk', 'e.g. Jan Jansen')"
				required />

			<NcSelect
				v-model="form.type"
				:options="entityTypeOptions"
				:input-label="t('docudesk', 'Entity type')"
				:placeholder="t('docudesk', 'Pick an entity type…')"
				required />

			<NcTextField
				:value.sync="form.category"
				:label="t('docudesk', 'Category (optional)')"
				:placeholder="t('docudesk', 'e.g. natural_person')" />

			<div class="form-flags">
				<NcCheckboxRadioSwitch
					:checked.sync="form.wholeWord"
					type="switch">
					{{ t('docudesk', 'Whole word only') }}
				</NcCheckboxRadioSwitch>
				<NcCheckboxRadioSwitch
					:checked.sync="form.caseSensitive"
					type="switch">
					{{ t('docudesk', 'Case sensitive') }}
				</NcCheckboxRadioSwitch>
			</div>

			<NcNoteCard v-if="submitError" type="error">
				{{ submitError }}
			</NcNoteCard>
		</div>

		<template #actions>
			<NcButton type="tertiary" @click="onCancel">
				{{ t('docudesk', 'Cancel') }}
			</NcButton>
			<NcButton type="primary" :disabled="!canSubmit" @click="submit">
				<template v-if="submitting" #icon>
					<NcLoadingIcon :size="20" />
				</template>
				{{ t('docudesk', 'Add to anonymisation list') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<style scoped>
.manual-entity-form {
	display: flex;
	flex-direction: column;
	gap: 12px;
	padding: 4px 0;
}

.form-hint {
	color: var(--color-text-maxcontrast);
	margin: 0 0 4px;
}

.form-flags {
	display: flex;
	flex-direction: column;
	gap: 4px;
	padding: 4px 0;
}
</style>
