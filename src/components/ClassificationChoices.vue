<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

The pickers a person uses to correct a suggestion before confirming it:
document type, sender and dossier. Starts on the suggestion; emits the
choices on every change, as { documentType, correspondentName, dossier }.

@spec openspec/changes/inbound-auto-classification/tasks.md#3-2
-->

<template>
	<div class="classification-choices">
		<NcSelect
			v-model="documentType"
			:inputLabel="t('filinq', 'Document type')"
			:options="types"
			label="label"
			:reduce="(option) => option.id"
			:clearable="false"
			data-testid="classification-type"
			@update:modelValue="emitChoices" />
		<NcTextField
			v-model="correspondentName"
			:label="t('filinq', 'Sender')"
			data-testid="classification-sender"
			@update:modelValue="emitChoices" />
		<NcSelect
			v-model="dossier"
			:inputLabel="t('filinq', 'File in dossier')"
			:placeholder="t('filinq', 'Do not file')"
			:options="dossiers"
			label="label"
			:reduce="(option) => option.id"
			data-testid="classification-dossier"
			@update:modelValue="emitChoices" />
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcSelect, NcTextField } from '@nextcloud/vue'
import { typeOptions } from '../services/classification.js'

export default {
	name: 'ClassificationChoices',
	components: { NcSelect, NcTextField },

	props: {
		record: {
			type: Object,
			required: true,
		},

		dossiers: {
			type: Array,
			default: () => [],
		},
	},

	emits: ['change'],

	data() {
		return {
			documentType: this.record.suggestedDocumentType,
			correspondentName: this.record.suggestedCorrespondent?.name || '',
			dossier: this.record.suggestedDossier || null,
			types: typeOptions(),
		}
	},

	mounted() {
		this.emitChoices()
	},

	methods: {
		t,

		/**
		 * Tell the parent what is chosen now.
		 *
		 * @spec openspec/changes/inbound-auto-classification/tasks.md#3-2
		 */
		emitChoices() {
			this.$emit('change', {
				documentType: this.documentType,
				correspondentName: this.correspondentName,
				dossier: this.dossier,
			})
		},
	},
}
</script>

<style scoped>
.classification-choices {
	display: flex;
	flex-direction: column;
	gap: 12px;
}
</style>
