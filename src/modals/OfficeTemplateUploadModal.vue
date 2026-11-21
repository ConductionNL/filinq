<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

@spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md#requirement-office-files-are-accepted-as-first-class-templates-req-ddota-001
-->

<template>
	<NcModal :name="t('filinq', 'Upload a Word template')" @close="$emit('close')">
		<form class="office-upload-modal" @submit.prevent="submit">
			<h2>{{ t('filinq', 'Upload a Word template') }}</h2>
			<p class="office-upload-modal__help">
				{{ t('filinq', 'A DOCX or ODT file with merge tags such as ${applicant.name}. Files with macros are refused.') }}
			</p>
			<label class="office-upload-modal__label" for="office-upload-file">
				{{ t('filinq', 'File') }}
			</label>
			<input
				id="office-upload-file"
				type="file"
				accept=".docx,.odt"
				required
				@change="pick">
			<NcTextField v-model="fields.name" :label="t('filinq', 'Name')" required />
			<NcTextField v-model="fields.namespace" :label="t('filinq', 'Namespace')" required />
			<NcTextField v-model="fields.category" :label="t('filinq', 'Category')" />
			<NcTextField v-model="fields.boundRegister" :label="t('filinq', 'Register to check the tags against')" />
			<NcTextField v-model="fields.boundSchema" :label="t('filinq', 'Schema to check the tags against')" />
			<NcNoteCard v-if="error" type="error">
				{{ error }}
			</NcNoteCard>
			<div class="office-upload-modal__buttons">
				<NcButton @click="$emit('close')">
					{{ t('filinq', 'Cancel') }}
				</NcButton>
				<NcButton type="submit" variant="primary" :disabled="!file || saving">
					{{ t('filinq', 'Upload') }}
				</NcButton>
			</div>
		</form>
	</NcModal>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcModal, NcNoteCard, NcTextField } from '@nextcloud/vue'
import { uploadOfficeTemplate } from '../services/officeTemplates.js'

export default {
	name: 'OfficeTemplateUploadModal',
	components: { NcButton, NcModal, NcNoteCard, NcTextField },
	props: {
		namespace: { type: String, default: 'filinq' },
	},

	emits: ['close', 'created'],
	data() {
		return {
			file: null,
			fields: { name: '', namespace: this.namespace, category: '', boundRegister: '', boundSchema: '' },
			saving: false,
			error: '',
		}
	},

	methods: {
		t,
		/**
		 * pick.
		 *
		 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
		 */
		pick(event) {
			this.file = (event.target.files && event.target.files[0]) || null
			if (this.file && this.fields.name === '') {
				this.fields.name = this.file.name.replace(/\.[^.]+$/, '')
			}
		},

		/**
		 * submit.
		 *
		 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
		 */
		async submit() {
			this.saving = true
			this.error = ''
			const result = await uploadOfficeTemplate(this.file, this.fields)
			this.saving = false
			if (!result.ok) {
				this.error = result.error || t('filinq', 'The file could not be uploaded.')
				return
			}
			this.$emit('created', result.data)
		},
	},
}
</script>

<style scoped>
.office-upload-modal {
	display: flex;
	flex-direction: column;
	gap: 12px;
	padding: 16px;
}

.office-upload-modal__help {
	color: var(--color-text-maxcontrast);
}

.office-upload-modal__buttons {
	display: flex;
	justify-content: flex-end;
	gap: 8px;
}
</style>
