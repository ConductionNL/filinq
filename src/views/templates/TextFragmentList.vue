<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

The text fragments tab of the templates page: every fragment with the tag a
template uses to insert it.

@spec openspec/changes/office-template-authoring/tasks.md#4-1
-->

<template>
	<div class="fragment-list" data-testid="text-fragment-list">
		<div class="fragment-list__header">
			<NcButton
				variant="primary"
				data-testid="text-fragment-new"
				@click="editing = {}">
				{{ t('filinq', 'New text fragment') }}
			</NcButton>
		</div>
		<NcLoadingIcon v-if="loading" />
		<table v-else-if="fragments.length > 0" class="fragment-list__table">
			<thead>
				<tr>
					<th scope="col">{{ t('filinq', 'Name') }}</th>
					<th scope="col">{{ t('filinq', 'Tag') }}</th>
					<th scope="col">{{ t('filinq', 'Category') }}</th>
					<th scope="col">{{ t('filinq', 'Namespace') }}</th>
					<th scope="col">{{ t('filinq', 'Actions') }}</th>
				</tr>
			</thead>
			<tbody>
				<tr v-for="fragment in fragments" :key="fragment.uuid">
					<td>{{ fragment.name }}</td>
					<td>
						<code>{{ tagOf(fragment) }}</code>
					</td>
					<td>{{ fragment.category || '-' }}</td>
					<td>{{ fragment.namespace }}</td>
					<td>
						<NcButton
							variant="tertiary"
							:aria-label="t('filinq', 'Edit text fragment')"
							@click="editing = fragment">
							{{ t('filinq', 'Edit') }}
						</NcButton>
						<NcButton
							variant="error"
							:aria-label="t('filinq', 'Delete text fragment')"
							@click="remove(fragment)">
							{{ t('filinq', 'Delete') }}
						</NcButton>
					</td>
				</tr>
			</tbody>
		</table>
		<NcEmptyContent
			v-else
			:name="t('filinq', 'No text fragments yet')"
			:description="
				t(
					'filinq',
					'A text fragment is a piece of text, such as a signature block, that many templates share.',
				)
			" />
		<NcNoteCard v-if="error" type="error">
			{{ error }}
		</NcNoteCard>
		<TextFragmentModal
			v-if="editing"
			:fragment="editing"
			@close="editing = null"
			@saved="saved" />
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcEmptyContent, NcLoadingIcon, NcNoteCard } from '@nextcloud/vue'
import TextFragmentModal from '../../modals/TextFragmentModal.vue'
import {
	deleteFragment,
	errorText,
	listFragments,
} from '../../services/officeTemplates.js'

export default {
	name: 'TextFragmentList',
	components: {
		NcButton,
		NcEmptyContent,
		NcLoadingIcon,
		NcNoteCard,
		TextFragmentModal,
	},

	data() {
		return {
			fragments: [],
			loading: false,
			editing: null,
			error: '',
		}
	},

	mounted() {
		this.load()
	},

	methods: {
		t,
		/**
		 * Read the fragments.
		 *
		 * @spec openspec/changes/office-template-authoring/tasks.md#4-1
		 */
		async load() {
			this.loading = true
			try {
				this.fragments = await listFragments()
			} catch (error) {
				this.error = errorText(
					error,
					t('filinq', 'The text fragments could not be loaded.'),
				)
			} finally {
				this.loading = false
			}
		},

		/**
		 * The tag that inserts a fragment.
		 *
		 * @param {object} fragment The fragment.
		 * @return {string} The tag.
		 * @spec openspec/changes/office-template-authoring/tasks.md#4-1
		 */
		tagOf(fragment) {
			return '${fragment:' + fragment.slug + '}'
		},

		/**
		 * Close the modal and reload.
		 *
		 * @spec openspec/changes/office-template-authoring/tasks.md#4-1
		 */
		async saved() {
			this.editing = null
			await this.load()
		},

		/**
		 * Delete a fragment and reload.
		 *
		 * @param {object} fragment The fragment.
		 * @spec openspec/changes/office-template-authoring/tasks.md#4-1
		 */
		async remove(fragment) {
			this.error = ''
			try {
				await deleteFragment(fragment.uuid)
				await this.load()
			} catch (error) {
				this.error = errorText(
					error,
					t('filinq', 'The text fragment could not be deleted.'),
				)
			}
		},
	},
}
</script>

<style scoped>
.fragment-list__header {
	display: flex;
	justify-content: flex-end;
	margin-bottom: 12px;
}

.fragment-list__table {
	width: 100%;
	border-collapse: collapse;
}

.fragment-list__table th,
.fragment-list__table td {
	padding: 8px;
	border-bottom: 1px solid var(--color-border);
	text-align: start;
}
</style>
