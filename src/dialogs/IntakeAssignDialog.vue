<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

Assigns one waiting intake document to a record. The clerk picks the register
and the schema, then the record itself; the list of records is fetched only once
both slugs are known, because that is the first moment the question "which
record?" has an answer.

The dialog never decides whether the assignment is allowed. It sends it and
shows what the server answered: the write rights on both schemas are the
server's to judge, and a dialog that hid the button would be a second, weaker
copy of that judgement.

@spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
-->

<template>
	<NcDialog
		:name="t('filinq', 'Assign to a record')"
		data-testid="intake-assign-dialog"
		@closing="$emit('cancel')">
		<template #default>
			<p>{{ explanation }}</p>
			<!-- What the base registries answered and the parties read out of the
			     text, each accepted, corrected or dismissed before the document is
			     assigned (intake-base-registry-enrichment REQ-IBR-004, REQ-IDW-03). -->
			<section
				v-if="registrySuggestions.length > 0 || registryNote"
				class="intake-assign__suggestions"
				data-testid="intake-registry-suggestions">
				<h3 class="intake-assign__heading">
					{{ t('filinq', 'What the base registries found') }}
				</h3>
				<p v-if="registryNote" data-testid="intake-registry-note">
					{{ registryNote }}
				</p>
				<IntakeSuggestionCard
					v-for="suggestion in registrySuggestions"
					:key="`registry-${suggestion.index}`"
					:title="registryTitle(suggestion)"
					:fields="suggestion.fields"
					:editableKeys="registryKeys"
					:labels="fieldLabels"
					:acceptLabel="t('filinq', 'Accept')"
					:state="decisions[`registry-${suggestion.index}`] || ''"
					:saving="deciding === `registry-${suggestion.index}`"
					@accept="decideRegistry(suggestion, 'accepted', $event)"
					@dismiss="decideRegistry(suggestion, 'dismissed')" />
			</section>
			<section
				v-if="partySuggestions.length > 0"
				class="intake-assign__suggestions"
				data-testid="intake-party-suggestions">
				<h3 class="intake-assign__heading">
					{{ t('filinq', 'Senders read from the document') }}
				</h3>
				<IntakeSuggestionCard
					v-for="suggestion in partySuggestions"
					:key="`party-${suggestion.index}`"
					:title="partyTitle(suggestion)"
					:fields="suggestion.party"
					:editableKeys="partyKeys"
					:labels="fieldLabels"
					:acceptLabel="t('filinq', 'Confirm')"
					:state="decisions[`party-${suggestion.index}`] || ''"
					:saving="deciding === `party-${suggestion.index}`"
					@accept="decideParty(suggestion, 'confirm', $event)"
					@dismiss="decideParty(suggestion, 'dismiss')" />
			</section>
			<p
				v-if="suggestionError"
				class="intake-assign__error"
				role="alert"
				data-testid="intake-suggestion-error">
				{{ suggestionError }}
			</p>
			<CnRegisterSchemaSelect
				:register="register"
				:schema="schema"
				:disabled="saving"
				@update:register="onRegister"
				@update:schema="onSchema" />
			<NcSelect
				v-model="selected"
				class="intake-assign__records"
				:options="recordOptions"
				:loading="loadingRecords"
				:disabled="saving || recordOptions.length === 0"
				:inputLabel="t('filinq', 'Record')"
				:placeholder="recordPlaceholder"
				data-testid="intake-assign-record" />
			<p
				v-if="error"
				class="intake-assign__error"
				data-testid="intake-assign-error">
				{{ error }}
			</p>
		</template>
		<template #actions>
			<CnDialogActions
				:saving="saving"
				:canSubmit="Boolean(selected)"
				:confirmLabel="t('filinq', 'Assign')"
				@cancel="$emit('cancel')"
				@submit="confirm" />
		</template>
	</NcDialog>
</template>

<script>
import { CnDialogActions, CnRegisterSchemaSelect } from '@conduction/nextcloud-vue'
import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { NcDialog, NcSelect } from '@nextcloud/vue'
import IntakeSuggestionCard from '../components/intake/IntakeSuggestionCard.vue'
import {
	decidePartyChoice,
	decideRegistrySuggestion,
	openPartySuggestions,
	openRegistrySuggestions,
	PARTY_FIELDS,
	REGISTRY_FIELDS,
} from '../services/intakeSuggestions.js'

export default {
	name: 'IntakeAssignDialog',
	components: { CnDialogActions, CnRegisterSchemaSelect, IntakeSuggestionCard, NcDialog, NcSelect },

	props: {
		// The document being assigned, for the sentence above the pickers.
		document: { type: Object, required: true },
		// True while the parent is waiting for the server.
		saving: { type: Boolean, default: false },
		// What the server said when it refused.
		error: { type: String, default: '' },
	},

	emits: ['confirm', 'cancel'],

	data() {
		return {
			register: '',
			schema: '',
			selected: null,
			records: [],
			loadingRecords: false,
			// What the clerk decided per suggestion, keyed `registry-<i>` or `party-<i>`.
			decisions: {},
			deciding: '',
			suggestionError: '',
		}
	},

	computed: {
		/**
		 * The registry suggestions on the document nobody has decided yet.
		 *
		 * @return {Array<object>} The open registry suggestions.
		 * @spec openspec/changes/intake-base-registry-enrichment/tasks.md#task-3.2
		 */
		registrySuggestions() {
			return openRegistrySuggestions(this.document)
		},

		/**
		 * The party suggestions on the document nobody has decided yet.
		 *
		 * @return {Array<object>} The open party suggestions.
		 * @spec openspec/changes/intake-base-registry-enrichment/tasks.md#task-3.3
		 */
		partySuggestions() {
			return openPartySuggestions(this.document)
		},

		/**
		 * Why a registry lookup could not run, or an empty string.
		 *
		 * @return {string} The lookup note.
		 * @spec openspec/changes/intake-base-registry-enrichment/tasks.md#task-3.2
		 */
		registryNote() {
			const note = this.document?.registryLookupNote
			return typeof note === 'string' ? note.trim() : ''
		},

		/**
		 * The registry fields a clerk may correct.
		 *
		 * @return {Array<string>} The field keys.
		 * @spec openspec/changes/intake-base-registry-enrichment/tasks.md#task-3.2
		 */
		registryKeys() {
			return REGISTRY_FIELDS
		},

		/**
		 * The party fields a clerk may correct.
		 *
		 * @return {Array<string>} The field keys.
		 * @spec openspec/changes/intake-base-registry-enrichment/tasks.md#task-3.3
		 */
		partyKeys() {
			return PARTY_FIELDS
		},

		/**
		 * The label of each suggestion field.
		 *
		 * @return {object} The labels, by field key.
		 * @spec openspec/changes/intake-base-registry-enrichment/specs/intake-registry-enrichment/spec.md#requirement-a-person-accepts-or-dismisses-a-registry-suggestion-req-ibr-004
		 */
		fieldLabels() {
			return {
				name: t('filinq', 'Name'),
				address: t('filinq', 'Address'),
				kvkNumber: t('filinq', 'KvK number'),
				email: t('filinq', 'Email'),
				telephone: t('filinq', 'Telephone'),
			}
		},

		explanation() {
			const subject = this.document?.subject || t('filinq', 'this document')
			return t('filinq', 'Pick the record "{subject}" belongs to.', {
				subject,
			})
		},

		recordOptions() {
			return this.records.map((record) => ({
				id: record['@self']?.id || record.id || record.uuid || '',
				label:
					record['@self']?.name
					|| record.name
					|| record.title
					|| record['@self']?.id
					|| t('filinq', 'Unnamed record'),
			}))
		},

		recordPlaceholder() {
			if (this.register === '' || this.schema === '') {
				return t('filinq', 'Pick a register and a schema first')
			}
			if (this.loadingRecords) {
				return t('filinq', 'Loading records')
			}
			if (this.recordOptions.length === 0) {
				return t('filinq', 'This schema holds no records yet')
			}
			return t('filinq', 'Pick a record')
		},
	},

	methods: {
		t,

		/**
		 * The card title of a registry suggestion: its source and identifier.
		 *
		 * @param {object} suggestion The suggestion.
		 * @return {string} The title.
		 * @spec openspec/changes/intake-base-registry-enrichment/tasks.md#task-3.2
		 */
		registryTitle(suggestion) {
			const source = suggestion.source === 'brp' ? t('filinq', 'BRP') : t('filinq', 'KvK')
			return suggestion.identifier ? `${source}: ${suggestion.identifier}` : source
		},

		/**
		 * The card title of a party suggestion: its name, else its email.
		 *
		 * @param {object} suggestion The suggestion.
		 * @return {string} The title.
		 * @spec openspec/changes/intake-base-registry-enrichment/tasks.md#task-3.3
		 */
		partyTitle(suggestion) {
			return suggestion.party.name || suggestion.party.email || t('filinq', 'Sender')
		},

		/**
		 * Accept or dismiss one registry suggestion through the server.
		 *
		 * @param {object} suggestion The suggestion.
		 * @param {'accepted'|'dismissed'} decision The decision.
		 * @param {object} [fields] The fields as the clerk accepted them.
		 * @return {Promise<void>}
		 * @spec openspec/changes/intake-base-registry-enrichment/tasks.md#task-3.2
		 */
		async decideRegistry(suggestion, decision, fields = {}) {
			await this.decide(`registry-${suggestion.index}`, decision, () => decideRegistrySuggestion(this.document.uuid, suggestion.index, decision, fields))
		},

		/**
		 * Confirm, correct or dismiss one party suggestion through the server.
		 *
		 * @param {object} suggestion The suggestion.
		 * @param {'confirm'|'dismiss'} choice What the clerk chose.
		 * @param {object} [fields] The fields as the clerk left them.
		 * @return {Promise<void>}
		 * @spec openspec/changes/intake-base-registry-enrichment/tasks.md#task-3.3
		 */
		async decideParty(suggestion, choice, fields = null) {
			await this.decide(`party-${suggestion.index}`, choice === 'dismiss' ? 'dismissed' : 'accepted', () => decidePartyChoice(this.document, suggestion, choice, fields))
		},

		/**
		 * Run one decision and keep its outcome, or say why it was refused.
		 *
		 * @param {string} key The suggestion's key.
		 * @param {string} state The state it reads as once stored.
		 * @param {() => Promise<unknown>} send Sends the decision.
		 * @return {Promise<void>}
		 */
		async decide(key, state, send) {
			if (this.deciding !== '') {
				return
			}
			this.deciding = key
			this.suggestionError = ''
			try {
				await send()
				this.decisions = { ...this.decisions, [key]: state }
			} catch (error) {
				const answer = error?.response?.data
				this.suggestionError = (answer && (answer.error || answer.message)) || t('filinq', 'The suggestion could not be saved.')
			} finally {
				this.deciding = ''
			}
		},

		onRegister(value) {
			this.register = value || ''
			this.schema = ''
			this.selected = null
			this.records = []
		},

		onSchema(value) {
			this.schema = value || ''
			this.selected = null
			this.loadRecords()
		},

		/**
		 * Fetch the records of the picked schema.
		 *
		 * @return {Promise<void>} Resolves once the list has settled.
		 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
		 */
		async loadRecords() {
			if (this.register === '' || this.schema === '') {
				return
			}
			this.loadingRecords = true
			try {
				const url = generateUrl(
					'/apps/openregister/api/objects/{register}/{schema}',
					{ register: this.register, schema: this.schema },
				)
				const { data } = await axios.get(url, { params: { _limit: 100 } })
				this.records = Array.isArray(data?.results) ? data.results : []
				this.preselectFromSourceRef()
			} catch {
				this.records = []
			} finally {
				this.loadingRecords = false
			}
		},

		/**
		 * Pre-select the record the separator sheet named.
		 *
		 * A scanned document carries the case number from the sheet that
		 * separated it, as a source reference. It is a suggestion: the clerk
		 * still confirms, and the server still checks the write rights, so a
		 * number somebody mistyped on a sheet costs one glance rather than a
		 * document filed on the wrong case.
		 *
		 * @return {void}
		 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
		 */
		preselectFromSourceRef() {
			const reference = String(this.document?.sourceRef || '').trim()
			if (reference === '' || this.selected) {
				return
			}
			const match = this.recordOptions.find(
				(option) =>
					String(option.label).includes(reference)
					|| String(option.id) === reference,
			)
			if (match) {
				this.selected = match
			}
		},

		confirm() {
			if (!this.selected) {
				return
			}
			this.$emit('confirm', {
				register: this.register,
				schema: this.schema,
				id: this.selected.id || this.selected,
			})
		},
	},
}
</script>

<style scoped>
.intake-assign__suggestions {
	margin-block: calc(var(--default-grid-baseline) * 3);
}

.intake-assign__heading {
	font-size: var(--default-font-size);
	margin: 0 0 calc(var(--default-grid-baseline) * 2);
}

.intake-assign__records {
	margin-top: 12px;
	width: 100%;
}

.intake-assign__error {
	margin-top: 12px;
	color: var(--color-error);
}
</style>
