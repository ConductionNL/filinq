<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

@spec openspec/changes/contract-lifecycle-management/tasks.md#3-1
-->

<template>
	<NcDialog
		:name="t('filinq', 'Generate a document')"
		:noClose="busy"
		@closing="$emit('close')">
		<template #default>
			<p>
				{{
					t(
						'filinq',
						'The template is filled in with this contract and saved as a PDF in your files. The contract keeps a link to it.',
					)
				}}
			</p>
			<NcSelect
				v-model="template"
				:options="templates"
				label="label"
				:loading="loading"
				:inputLabel="t('filinq', 'Template')"
				data-testid="contract-generate-template" />
			<NcNoteCard v-if="error" type="error">
				{{ error }}
			</NcNoteCard>
		</template>
		<template #actions>
			<NcButton :disabled="busy" @click="$emit('close')">
				{{ t('filinq', 'Cancel') }}
			</NcButton>
			<NcButton
				variant="primary"
				:disabled="busy || !template"
				data-testid="contract-generate-confirm"
				@click="onConfirm">
				{{ t('filinq', 'Generate') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcDialog, NcNoteCard, NcSelect } from '@nextcloud/vue'
import { generateDocument, listTemplates } from '../services/contracts.js'

/**
 * Generate a contract document from a template, through the app's normal
 * generation path, and hand back the new file id.
 *
 * @spec openspec/changes/contract-lifecycle-management/tasks.md#3-1
 */
export default {
	name: 'GenerateContractDocumentDialog',
	components: { NcButton, NcDialog, NcNoteCard, NcSelect },
	props: {
		/** The contract id. */
		contractId: { type: String, required: true },
		/** The contract title, the file name. */
		contractTitle: { type: String, default: '' },
	},

	emits: ['close', 'generated'],
	data() {
		return {
			templates: [],
			template: null,
			loading: true,
			busy: false,
			error: '',
		}
	},

	/**
	 * Load the templates to choose from.
	 *
	 * @return {Promise<void>}
	 * @spec openspec/changes/contract-lifecycle-management/tasks.md#3-1
	 */
	async mounted() {
		const answer = await listTemplates()
		this.loading = false
		if (!answer.ok) {
			this.error = answer.error
			return
		}
		this.templates = answer.data.map((row) => ({
			id: String(row.id ?? row['@self']?.id ?? ''),
			label: row.name || row.title || String(row.id ?? ''),
		}))
		if (this.templates.length === 0) {
			this.error = t('filinq', 'There are no templates yet.')
		}
	},

	methods: {
		t,
		/**
		 * Generate, and hand the file id back.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/contract-lifecycle-management/tasks.md#3-1
		 */
		async onConfirm() {
			this.busy = true
			this.error = ''
			const answer = await generateDocument(
				this.template.id,
				this.contractId,
				this.contractTitle || 'contract',
			)
			this.busy = false
			if (!answer.ok || answer.data.fileId === '') {
				this.error =
					answer.error
					|| t('filinq', 'The document could not be generated.')
				return
			}
			this.$emit('generated', answer.data.fileId)
		},
	},
}
</script>
