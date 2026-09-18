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
			<p v-if="error" class="intake-assign__error" data-testid="intake-assign-error">
				{{ error }}
			</p>
		</template>
		<template #actions>
			<NcButton :disabled="saving" @click="$emit('cancel')">
				{{ t('filinq', 'Cancel') }}
			</NcButton>
			<NcButton
				variant="primary"
				:disabled="saving || !selected"
				data-testid="intake-assign-confirm"
				@click="confirm">
				{{ t('filinq', 'Assign') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<script>
import {
	CnRegisterSchemaSelect,
	NcButton,
	NcDialog,
	NcSelect,
} from '@conduction/nextcloud-vue'
import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'

export default {
	name: 'IntakeAssignDialog',
	components: { CnRegisterSchemaSelect, NcButton, NcDialog, NcSelect },

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
		}
	},

	computed: {
		explanation() {
			const subject = this.document?.subject || t('filinq', 'this document')
			return t('filinq', 'Pick the record "{subject}" belongs to.', { subject })
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
.intake-assign__records {
	margin-top: 12px;
	width: 100%;
}

.intake-assign__error {
	margin-top: 12px;
	color: var(--color-error);
}
</style>
