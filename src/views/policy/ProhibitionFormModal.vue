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

const blankForm = () => ({
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
})

// Text-matching rule kinds. `exact` compares byte-for-byte; `normalized` compares
// accent-stripped and lower-cased. Presented as a switch rather than a dropdown
// because those are the only two text options and operators found the four-way
// select (which mixed in the identifier kinds) hard to reason about.
const TEXT_KINDS = ['exact', 'normalized']

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
			// Collapsed by default. Every field behind these is OPTIONAL in the
			// schema (required is: primaryName, entityType, matchRules, reason,
			// active), so nothing here blocks saving a rule — they were simply
			// rendered at equal weight, which made a 5-field form look like a
			// 9-field one.
			showLegal: false,
			showIdentifierRules: false,
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
			return this.form.primaryName.trim() !== ''
				&& this.form.reason.trim() !== ''
				&& this.form.matchRules.length > 0
				&& this.form.matchRules.every(r => r.type && r.value !== '')
		},
		textRules() {
			return this.form.matchRules.filter(r => TEXT_KINDS.includes(r.type))
		},
		identifierRules() {
			return this.form.matchRules.filter(r => !TEXT_KINDS.includes(r.type))
		},
		onlyNameRules() {
			if (this.form.matchRules.length === 0) {
				return false
			}
			return this.form.matchRules.every(r => TEXT_KINDS.includes(r.type))
		},
		// Any existing record that already carries identifier rules must show that
		// section on open, or the operator would think the rules had vanished.
		legalFieldsInUse() {
			return [
				this.form.legalAuthority,
				this.form.caseReference,
				this.form.jurisdiction,
				this.form.validUntil,
			].some(v => (v || '').toString().trim() !== '')
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
						? this.editingRecord.matchRules.map(r => ({ ...r }))
						: [],
				}
			} else {
				this.form = blankForm()
			}
			// Reveal the optional sections when the record already uses them.
			this.showLegal = this.legalFieldsInUse
			this.showIdentifierRules = this.identifierRules.length > 0
		},
		/**
		 * Index of a rule in the underlying matchRules array.
		 *
		 * The template iterates the filtered text/identifier lists, so mutations
		 * must resolve back to the real array rather than the filtered position.
		 *
		 * @param {object} rule The rule object (same identity as in matchRules).
		 * @return {number} Its index in form.matchRules, or -1.
		 */
		indexOfRule(rule) {
			return this.form.matchRules.indexOf(rule)
		},
		/**
		 * Whether a text rule is set to exact matching.
		 *
		 * @param {object} rule The rule.
		 * @return {boolean} True when the rule matches byte-for-byte.
		 */
		isExact(rule) {
			return rule.type === 'exact'
		},
		/**
		 * Flip a text rule between exact and normalised matching.
		 *
		 * @param {object} rule  The rule.
		 * @param {boolean} exact Whether it should match exactly.
		 * @return {void}
		 */
		setExact(rule, exact) {
			const idx = this.indexOfRule(rule)
			if (idx !== -1) {
				this.$set(this.form.matchRules, idx, { ...rule, type: exact ? 'exact' : 'normalized' })
			}
		},
		/**
		 * Indicative preview of how a value will be stored when not exact.
		 *
		 * Display only. The authoritative normalisation runs server-side in
		 * PolicyCrudService, using the same rule as the matcher — an NFD
		 * diacritic strip here cannot reproduce intl's Any-Latin transliteration
		 * for Cyrillic or Greek, so this is a hint, not the contract.
		 *
		 * @param {string} value The raw input.
		 * @return {string} The previewed normalised form.
		 */
		normalisedPreview(value) {
			return (value || '')
				.normalize('NFD')
				.replace(/\p{Diacritic}/gu, '')
				.toLowerCase()
				.trim()
		},
		addTextRule() {
			// Not-exact by default: for a publish-never rule a false positive is
			// reviewable, but a miss means publishing something that must never
			// be published, so accents and casing should not defeat it.
			this.form.matchRules.push({ type: 'normalized', value: '' })
		},
		addIdentifierRule(type) {
			this.form.matchRules.push({ type, value: '' })
			this.showIdentifierRules = true
		},
		removeRule(rule) {
			const idx = this.indexOfRule(rule)
			if (idx !== -1) {
				this.form.matchRules.splice(idx, 1)
			}
		},
		submit() {
			this.$emit('submit', { ...this.form, matchRules: this.form.matchRules.map(r => ({ ...r })) })
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
				:value.sync="form.primaryName"
				:label="t('docudesk', 'Primary name (Dutch)')"
				required />
			<NcSelect
				v-model="form.entityType"
				:options="entityTypeOptions"
				:input-label="t('docudesk', 'Entity type')"
				:label="t('docudesk', 'Entity type')"
				required />
			<NcTextField
				:value.sync="form.reason"
				:label="t('docudesk', 'Reason (markdown allowed)')"
				required />
			<NcCheckboxRadioSwitch
				v-model="form.active"
				type="switch">
				{{ t('docudesk', 'Active') }}
			</NcCheckboxRadioSwitch>

			<h4>{{ t('docudesk', 'Match rules') }}</h4>
			<div v-if="!form.matchRules?.length" class="form-warning">
				{{ t('docudesk', 'Add at least one match rule. Prefer stable identifiers (BSN/KvK) over name-only matches — names alone produce false positives.') }}
			</div>

			<div v-for="rule in textRules" :key="`text-${indexOfRule(rule)}`" class="match-rule-row">
				<NcTextField
					:value.sync="rule.value"
					:label="t('docudesk', 'Match value')" />
				<NcCheckboxRadioSwitch
					type="switch"
					:checked="isExact(rule)"
					@update:checked="setExact(rule, $event)">
					{{ t('docudesk', 'Exact match') }}
				</NcCheckboxRadioSwitch>
				<span v-if="!isExact(rule) && rule.value" class="match-rule-hint">
					{{ t('docudesk', 'Stored as: {value}', { value: normalisedPreview(rule.value) }) }}
				</span>
				<NcButton type="tertiary" @click="removeRule(rule)">
					<template #icon>
						<Delete :size="20" />
					</template>
				</NcButton>
			</div>
			<NcButton type="secondary" @click="addTextRule">
				{{ t('docudesk', 'Add match rule') }}
			</NcButton>

			<div v-if="onlyNameRules" class="form-warning">
				{{ t('docudesk', 'Warning: only name-based rules are present. Names alone often produce false positives — consider adding a BSN or KvK match.') }}
			</div>

			<NcCheckboxRadioSwitch
				v-model="showIdentifierRules"
				type="switch">
				{{ t('docudesk', 'Match on an identifier (BSN / KvK) instead of a name') }}
			</NcCheckboxRadioSwitch>
			<div v-if="showIdentifierRules" class="policy-form-section">
				<p class="policy-form-note">
					{{ t('docudesk', 'An identifier rule matches a resolved BSN or KvK number rather than the text in the document. Use * to match any value of that identifier.') }}
				</p>
				<div v-for="rule in identifierRules" :key="`id-${indexOfRule(rule)}`" class="match-rule-row">
					<span class="match-rule-kind">{{ rule.type.toUpperCase() }}</span>
					<NcTextField
						:value.sync="rule.value"
						:label="t('docudesk', 'Identifier (or * for any)')" />
					<NcButton type="tertiary" @click="removeRule(rule)">
						<template #icon>
							<Delete :size="20" />
						</template>
					</NcButton>
				</div>
				<NcButton type="secondary" @click="addIdentifierRule('bsn')">
					{{ t('docudesk', 'Add BSN rule') }}
				</NcButton>
				<NcButton type="secondary" @click="addIdentifierRule('kvk')">
					{{ t('docudesk', 'Add KvK rule') }}
				</NcButton>
			</div>

			<NcCheckboxRadioSwitch
				v-model="showLegal"
				type="switch">
				{{ t('docudesk', 'Record legal documentation (optional)') }}
			</NcCheckboxRadioSwitch>
			<div v-if="showLegal" class="policy-form-section">
				<NcTextField
					:value.sync="form.legalAuthority"
					:label="t('docudesk', 'Legal authority (court order, statute, …)')" />
				<NcTextField
					:value.sync="form.caseReference"
					:label="t('docudesk', 'Case reference')" />
				<NcSelect
					v-model="form.severity"
					:options="severityOptions"
					:input-label="t('docudesk', 'Severity')"
					:label="t('docudesk', 'Severity')" />
				<NcTextField
					:value.sync="form.jurisdiction"
					:label="t('docudesk', 'Jurisdiction')" />
				<NcTextField
					:value.sync="form.validUntil"
					:label="t('docudesk', 'Valid until (ISO 8601)')" />
			</div>

			<div v-if="formError" class="form-error">
				{{ formError }}
			</div>
		</div>

		<template #actions>
			<NcButton type="tertiary" @click="onCancel">
				{{ t('docudesk', 'Cancel') }}
			</NcButton>
			<NcButton type="primary" :disabled="saving || !canSubmit" @click="submit">
				<template v-if="saving" #icon>
					<NcLoadingIcon :size="20" />
				</template>
				{{ editing ? t('docudesk', 'Save') : t('docudesk', 'Create') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<style scoped>
.policy-form-section {
	margin: 4px 0 8px 12px;
	padding-left: 8px;
	border-left: 2px solid var(--color-border);
}

.policy-form-note {
	color: var(--color-text-maxcontrast);
	font-size: 0.9em;
	margin: 4px 0;
}

.match-rule-hint {
	color: var(--color-text-maxcontrast);
	font-size: 0.9em;
	font-family: monospace;
}

.match-rule-kind {
	font-weight: bold;
	min-width: 3.5em;
}
</style>
