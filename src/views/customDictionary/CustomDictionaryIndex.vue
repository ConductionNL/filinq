<!--
	SPDX-License-Identifier: EUPL-1.2
	SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>

	Custom dictionaries admin page (custom-dictionary-recognition,
	REQ-DDCDR-006). Lists organisation-managed term lists with their live
	term count, match mode and active state; "Add" creates a new dictionary
	and navigates to its detail page for term management.
-->
<script setup>
import { translate as t } from '@nextcloud/l10n'
import { customDictionaryStore } from '../../store/store.js'
import { resolveI18nValue } from '../../utils/registerI18n.js'
</script>

<template>
	<div>
		<CnIndexPage
			ref="indexPage"
			:title="t('filinq', 'Custom dictionaries')"
			:description="
				t(
					'filinq',
					'Organisation-managed term lists — project codenames, local street names, case-file codes — that add an extra recognizer alongside Presidio and regex.',
				)
			"
			:showTitle="true"
			:objects="customDictionaryStore.dictionaries"
			:columns="tableColumns"
			:pagination="paginationData"
			:loading="customDictionaryStore.loading"
			:selectable="false"
			:showEditAction="false"
			:showCopyAction="false"
			:showDeleteAction="false"
			:showMassImport="false"
			:showMassExport="false"
			:showMassCopy="false"
			:showMassDelete="false"
			:showViewToggle="false"
			:showAdd="customDictionaryStore.canMaintain"
			rowKey="id"
			:emptyText="emptyText"
			:refreshing="isRefreshing"
			@refresh="handleRefresh"
			@pageChanged="onPageChanged"
			@pageSizeChanged="onPageSizeChanged"
			@add="openCreateDialog"
			@rowClick="viewDictionary">
			<template #column-label="{ row }">
				<span class="custom-dictionary-index__label">
					<span
						class="custom-dictionary-index__swatch"
						:style="{ backgroundColor: row.colour || '#0082C9' }" />
					{{ displayLabel(row) }}
				</span>
			</template>

			<template #column-termCount="{ row }">
				{{ row.termCount ?? 0 }}
			</template>

			<template #column-matchMode="{ row }">
				<CnStatusBadge
					:label="matchModeLabel(row.matchMode)"
					:colorMap="matchModeColorMap" />
			</template>

			<template #column-active="{ row }">
				<CnStatusBadge
					:label="
						row.active === false
							? t('filinq', 'Inactive')
							: t('filinq', 'Active')
					"
					:colorMap="activeColorMap" />
			</template>

			<template #row-actions="{ row }">
				<NcActions>
					<template #icon>
						<DotsHorizontal :size="20" />
					</template>
					<NcActionButton closeAfterClick @click="viewDictionary(row)">
						<template #icon>
							<Pencil :size="20" />
						</template>
						{{ t('filinq', 'Manage terms') }}
					</NcActionButton>
					<NcActionButton
						v-if="customDictionaryStore.canMaintain"
						closeAfterClick
						@click="confirmDelete(row)">
						<template #icon>
							<Delete :size="20" />
						</template>
						{{ t('filinq', 'Delete') }}
					</NcActionButton>
				</NcActions>
			</template>
		</CnIndexPage>

		<!-- ADR-004 gate-13: dialog lives in its own file, not inline. -->
		<CustomDictionaryFormDialog
			v-if="dialogOpen"
			:saving="saving"
			:formError="formError"
			@submit="onCreateSubmit"
			@cancel="dialogOpen = false" />

		<!--
			Delete confirmation. Replaces window.confirm(): the deletion below
			runs only from @confirm, so an explicit confirmation is still
			required before anything is removed.
		-->
		<ConfirmActionDialog
			v-if="deleteTarget"
			:name="t('filinq', 'Delete dictionary')"
			:message="deleteMessage"
			:busy="deleting"
			@confirm="executeDelete"
			@cancel="cancelDelete" />
	</div>
</template>

<script>
import { CnIndexPage, CnStatusBadge } from '@conduction/nextcloud-vue'
import { NcActionButton, NcActions } from '@nextcloud/vue'
import Delete from 'vue-material-design-icons/Delete.vue'
import DotsHorizontal from 'vue-material-design-icons/DotsHorizontal.vue'
import Pencil from 'vue-material-design-icons/Pencil.vue'
import ConfirmActionDialog from '../../dialogs/ConfirmActionDialog.vue'
import CustomDictionaryFormDialog from '../../dialogs/CustomDictionaryFormDialog.vue'
import {
	indexPaging,
	recordDeletion,
	recordId,
	saveErrorMessage,
} from '../../mixins/recordIndexPage.js'

export default {
	name: 'CustomDictionaryIndex',
	components: {
		CnIndexPage,
		CnStatusBadge,
		NcActions,
		NcActionButton,
		CustomDictionaryFormDialog,
		ConfirmActionDialog,
		Delete,
		DotsHorizontal,
		Pencil,
	},

	mixins: [indexPaging, recordDeletion],
	deleteFailedLog: 'Failed to delete custom dictionary:',

	data() {
		return {
			dialogOpen: false,
			saving: false,
			formError: '',
			matchModeColorMap: {
				exact: 'error',
				caseInsensitive: 'primary',
				wordBoundary: 'success',
			},

			activeColorMap: {
				[t('filinq', 'Active')]: 'success',
				[t('filinq', 'Inactive')]: 'default',
			},
		}
	},

	computed: {
		/**
		 * CnDataTable column set for the dictionaries index: label, term count,
		 * match mode and active state.
		 *
		 * @return {object[]}
		 * @spec openspec/specs/custom-dictionary-recognition/spec.md#requirement-custom-dictionary-admin-ui-req-ddcdr-006
		 */
		tableColumns() {
			return [
				{ key: 'label', label: t('filinq', 'Label'), sortable: true },
				{ key: 'termCount', label: t('filinq', 'Terms'), sortable: true },
				{
					key: 'matchMode',
					label: t('filinq', 'Match mode'),
					sortable: true,
				},
				{ key: 'active', label: t('filinq', 'Status'), sortable: true },
			]
		},

		paginationData() {
			const total = customDictionaryStore.dictionaries.length
			const pages = Math.ceil(total / this.pageSize) || 1
			return { page: this.currentPage, pages, total, limit: this.pageSize }
		},

		/**
		 * Empty-state text for the dictionaries index — the store's error when
		 * loading failed, otherwise the no-dictionaries message.
		 *
		 * @return {string}
		 * @spec openspec/specs/custom-dictionary-recognition/spec.md#requirement-custom-dictionary-admin-ui-req-ddcdr-006
		 */
		emptyText() {
			if (customDictionaryStore.error) {
				return customDictionaryStore.error
			}
			return t('filinq', 'No custom dictionaries defined yet.')
		},

		/**
		 * Body text of the delete confirmation dialog.
		 *
		 * @spec openspec/specs/custom-dictionary-recognition/spec.md
		 */
		deleteMessage() {
			const name = this.deleteTarget?.label || t('filinq', 'this dictionary')
			return t(
				'filinq',
				'Delete "{name}" and all its terms? This cannot be undone.',
				{ name },
			)
		},
	},

	mounted() {
		customDictionaryStore.fetchDictionaries()
		customDictionaryStore.fetchCapabilities()
	},

	methods: {
		t,
		/**
		 * Resolve a dictionary's register-i18n label for display.
		 *
		 * @param {object} row The dictionary row.
		 * @return {string} The displayable label.
		 * @spec openspec/specs/custom-dictionary-recognition/spec.md
		 */
		displayLabel(row) {
			return resolveI18nValue(row.label, t('filinq', 'Custom dictionary'))
		},

		/**
		 * Translated label for a dictionary match mode, falling back to the raw
		 * value and finally to the schema default.
		 *
		 * @param {string} mode Stored match mode.
		 * @return {string} Displayable label.
		 * @spec openspec/specs/custom-dictionary-recognition/spec.md#requirement-custom-dictionary-admin-ui-req-ddcdr-006
		 */
		matchModeLabel(mode) {
			const labels = {
				exact: t('filinq', 'Exact'),
				caseInsensitive: t('filinq', 'Case-insensitive'),
				wordBoundary: t('filinq', 'Word boundary'),
			}
			return labels[mode] || mode || t('filinq', 'Case-insensitive')
		},

		/**
		 * Load the dictionaries from the store.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/specs/custom-dictionary-recognition/spec.md#requirement-custom-dictionary-admin-ui-req-ddcdr-006
		 */
		reloadRecords() {
			return customDictionaryStore.fetchDictionaries()
		},

		/**
		 * Delete a dictionary and all its terms by id.
		 *
		 * @param {string} id The dictionary id.
		 * @return {Promise<void>}
		 * @spec openspec/specs/custom-dictionary-recognition/spec.md
		 */
		deleteRecord(id) {
			return customDictionaryStore.deleteDictionary(id)
		},

		viewDictionary(row) {
			this.$router.push({
				name: 'CustomDictionaryDetail',
				params: { id: recordId(row) },
			})
		},

		openCreateDialog() {
			this.formError = ''
			this.dialogOpen = true
		},

		/**
		 * Create a dictionary through the organisation-gated management API and
		 * open its detail page.
		 *
		 * @param {object} formData Dialog form payload.
		 * @return {Promise<void>}
		 * @spec openspec/specs/custom-dictionary-recognition/spec.md#requirement-organisation-gated-dictionary-and-term-management-api-req-ddcdr-004
		 */
		async onCreateSubmit(formData) {
			this.saving = true
			this.formError = ''
			try {
				const created =
					await customDictionaryStore.createDictionary(formData)
				this.dialogOpen = false
				this.viewDictionary(created)
			} catch (err) {
				this.formError = saveErrorMessage(err)
			} finally {
				this.saving = false
			}
		},
	},
}
</script>

<style scoped>
.custom-dictionary-index__label {
	display: inline-flex;
	align-items: center;
	gap: 8px;
}

.custom-dictionary-index__swatch {
	display: inline-block;
	width: 12px;
	height: 12px;
	border-radius: 50%;
	border: 1px solid var(--color-border);
	flex-shrink: 0;
}
</style>
