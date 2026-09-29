<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

@spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-3.1
-->

<template>
	<NcDialog
		:name="t('filinq', 'New legal hold')"
		:noClose="busy"
		size="normal"
		@closing="$emit('close')">
		<template #default>
			<div class="new-legal-hold">
				<NcTextField
					v-model="name"
					:label="t('filinq', 'Name')"
					:required="true" />
				<NcSelect
					v-model="holdType"
					:options="types"
					label="label"
					:inputLabel="t('filinq', 'Matter type')"
					:clearable="false" />
				<NcTextArea
					v-model="reason"
					:label="t('filinq', 'Why are the records frozen?')"
					:required="true" />
				<NcTextField
					v-model="caseReference"
					:label="t('filinq', 'Case reference (optional)')" />
				<NcTextField
					v-model="custodian"
					:label="t('filinq', 'Custodian user id (optional)')" />
				<NcTextArea
					v-model="documents"
					:label="t('filinq', 'Document record ids, one per line')" />
				<NcTextArea
					v-model="dossiers"
					:label="t('filinq', 'Dossier ids, one per line')" />
				<NcNoteCard v-if="error" type="error">
					{{ error }}
				</NcNoteCard>
			</div>
		</template>
		<template #actions>
			<NcButton :disabled="busy" @click="$emit('close')">
				{{ t('filinq', 'Cancel') }}
			</NcButton>
			<NcButton
				variant="primary"
				:disabled="busy || !complete"
				data-testid="legal-hold-create"
				@click="onCreate">
				{{ t('filinq', 'Place hold') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import {
	NcButton,
	NcDialog,
	NcNoteCard,
	NcSelect,
	NcTextArea,
	NcTextField,
} from '@nextcloud/vue'
import { createCase, holdTypeOptions, parseRefs } from '../services/legalHolds.js'

/**
 * Open a legal hold: name, matter type, reason and the records it freezes.
 * Scope is explicit record ids only: a hold never grows or shrinks by itself.
 *
 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-3.1
 */
export default {
	name: 'NewLegalHoldDialog',
	components: {
		NcButton,
		NcDialog,
		NcNoteCard,
		NcSelect,
		NcTextArea,
		NcTextField,
	},

	emits: ['close', 'created'],
	data() {
		const types = holdTypeOptions()
		return {
			types,
			name: '',
			holdType: types[0],
			reason: '',
			caseReference: '',
			custodian: '',
			documents: '',
			dossiers: '',
			busy: false,
			error: '',
		}
	},

	computed: {
		/**
		 * Whether everything the server requires is filled in.
		 *
		 * @return {boolean}
		 */
		complete() {
			return (
				this.name.trim() !== ''
				&& this.reason.trim() !== ''
				&& parseRefs(this.documents).length + parseRefs(this.dossiers).length
					> 0
			)
		},
	},

	methods: {
		t,
		/**
		 * Place the hold and hand the new case back.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-3.1
		 */
		async onCreate() {
			this.busy = true
			this.error = ''
			const answer = await createCase({
				name: this.name.trim(),
				holdType: this.holdType?.id,
				reason: this.reason.trim(),
				caseReference: this.caseReference.trim(),
				custodian: this.custodian.trim(),
				scopeDocuments: parseRefs(this.documents),
				scopeDossiers: parseRefs(this.dossiers),
			})
			this.busy = false
			if (!answer.ok) {
				this.error = answer.error
				return
			}
			this.$emit('created', answer.data)
		},
	},
}
</script>

<style scoped>
.new-legal-hold {
	display: flex;
	flex-direction: column;
	gap: calc(var(--default-grid-baseline, 4px) * 2);
}
</style>
