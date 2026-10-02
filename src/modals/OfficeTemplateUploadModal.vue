<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

Upload a DOCX or ODT made in Word or LibreOffice as an office template. The
server reads its ${field} tags and checks them against the bound schema.

@spec openspec/changes/office-template-authoring/tasks.md#4-1
-->

<template>
	<NcModal
		:show="true"
		:name="t('filinq', 'Upload office template')"
		size="normal"
		@close="$emit('close')">
		<form
			class="office-upload"
			data-testid="office-template-upload-modal"
			@submit.prevent="submit">
			<h2>{{ t('filinq', 'Upload office template') }}</h2>
			<p class="office-upload__hint">
				{{ t('filinq', 'A DOCX or ODT with merge tags, for example:') }}
				<code>${aanvrager.naam}</code>
				<code>${fragment:ondertekening}</code>
			</p>
			<label class="office-upload__file">
				{{ t('filinq', 'Document') }}
				<input
					type="file"
					accept=".docx,.odt"
					data-testid="office-template-file"
					@change="file = $event.target.files[0] || null" />
			</label>
			<NcTextField
				v-model="fields.name"
				:label="t('filinq', 'Name')"
				required />
			<NcTextField
				v-model="fields.namespace"
				:label="t('filinq', 'Namespace')"
				required />
			<NcTextField
				v-model="fields.category"
				:label="t('filinq', 'Category')" />
			<NcTextField
				v-model="fields.boundRegister"
				:label="t('filinq', 'Bound register')" />
			<NcTextField
				v-model="fields.boundSchema"
				:label="t('filinq', 'Bound schema')" />
			<NcNoteCard v-if="error" type="error">
				{{ error }}
			</NcNoteCard>
			<div class="office-upload__actions">
				<NcButton @click="$emit('close')">
					{{ t('filinq', 'Cancel') }}
				</NcButton>
				<NcButton
					type="submit"
					variant="primary"
					:disabled="busy || !file || !fields.name || !fields.namespace"
					data-testid="office-template-upload-submit">
					{{ t('filinq', 'Upload') }}
				</NcButton>
			</div>
		</form>
	</NcModal>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcModal, NcNoteCard, NcTextField } from '@nextcloud/vue'
import { errorText, uploadOfficeTemplate } from '../services/officeTemplates.js'

export default {
	name: 'OfficeTemplateUploadModal',
	components: { NcButton, NcModal, NcNoteCard, NcTextField },

	emits: ['close', 'created'],

	data() {
		return {
			file: null,
			fields: {
				name: '',
				namespace: 'filinq',
				category: '',
				boundRegister: '',
				boundSchema: '',
			},

			busy: false,
			error: '',
		}
	},

	methods: {
		t,
		/**
		 * Send the document and hand the created template to the parent.
		 *
		 * @spec openspec/changes/office-template-authoring/tasks.md#4-1
		 */
		async submit() {
			this.busy = true
			this.error = ''
			try {
				const result = await uploadOfficeTemplate(this.file, this.fields)
				this.$emit('created', result)
			} catch (error) {
				this.error = errorText(
					error,
					t('filinq', 'The template could not be uploaded.'),
				)
			} finally {
				this.busy = false
			}
		},
	},
}
</script>

<style scoped>
.office-upload {
	display: flex;
	flex-direction: column;
	gap: 12px;
	padding: 20px;
}

.office-upload__hint {
	color: var(--color-text-maxcontrast);
}

.office-upload__file {
	display: flex;
	flex-direction: column;
	gap: 4px;
}

.office-upload__actions {
	display: flex;
	justify-content: flex-end;
	gap: 8px;
}
</style>
