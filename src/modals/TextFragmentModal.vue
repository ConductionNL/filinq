<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

Create or edit a text fragment (bouwsteen). Templates insert it where they
say ${fragment:slug}; it may hold merge tags of its own.

@spec openspec/changes/office-template-authoring/tasks.md#4-1
-->

<template>
	<NcModal
		:show="true"
		:name="
			fragment.uuid
				? t('filinq', 'Edit text fragment')
				: t('filinq', 'New text fragment')
		"
		size="normal"
		@close="$emit('close')">
		<form
			class="fragment-form"
			data-testid="text-fragment-modal"
			@submit.prevent="submit">
			<h2>
				{{
					fragment.uuid
						? t('filinq', 'Edit text fragment')
						: t('filinq', 'New text fragment')
				}}
			</h2>
			<NcTextField v-model="form.name" :label="t('filinq', 'Name')" required />
			<NcTextField v-model="form.slug" :label="t('filinq', 'Slug')" required />
			<NcTextField
				v-model="form.namespace"
				:label="t('filinq', 'Namespace')"
				:disabled="Boolean(fragment.uuid)"
				required />
			<NcTextField v-model="form.category" :label="t('filinq', 'Category')" />
			<NcTextArea
				v-model="form.content"
				:label="t('filinq', 'Text')"
				rows="6"
				required />
			<NcNoteCard v-if="error" type="error">
				{{ error }}
			</NcNoteCard>
			<div class="fragment-form__actions">
				<NcButton @click="$emit('close')">
					{{ t('filinq', 'Cancel') }}
				</NcButton>
				<NcButton
					type="submit"
					variant="primary"
					:disabled="
						busy
						|| !form.name
						|| !form.slug
						|| !form.content
						|| !form.namespace
					"
					data-testid="text-fragment-save">
					{{ t('filinq', 'Save') }}
				</NcButton>
			</div>
		</form>
	</NcModal>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import {
	NcButton,
	NcModal,
	NcNoteCard,
	NcTextArea,
	NcTextField,
} from '@nextcloud/vue'
import { errorText, saveFragment } from '../services/officeTemplates.js'

export default {
	name: 'TextFragmentModal',
	components: { NcButton, NcModal, NcNoteCard, NcTextArea, NcTextField },

	props: {
		fragment: {
			type: Object,
			default: () => ({}),
		},
	},

	emits: ['close', 'saved'],

	data() {
		return {
			form: {
				name: '',
				slug: '',
				namespace: 'filinq',
				category: '',
				content: '',
				...this.fragment,
			},

			busy: false,
			error: '',
		}
	},

	methods: {
		t,
		/**
		 * Store the fragment and hand it to the parent.
		 *
		 * @spec openspec/changes/office-template-authoring/tasks.md#4-1
		 */
		async submit() {
			this.busy = true
			this.error = ''
			try {
				const { name, slug, namespace, category, content, uuid } = this.form
				this.$emit(
					'saved',
					await saveFragment({
						name,
						slug,
						namespace,
						category,
						content,
						uuid,
					}),
				)
			} catch (error) {
				this.error = errorText(
					error,
					t('filinq', 'The text fragment could not be saved.'),
				)
			} finally {
				this.busy = false
			}
		},
	},
}
</script>

<style scoped>
.fragment-form {
	display: flex;
	flex-direction: column;
	gap: 12px;
	padding: 20px;
}

.fragment-form__actions {
	display: flex;
	justify-content: flex-end;
	gap: 8px;
}
</style>
