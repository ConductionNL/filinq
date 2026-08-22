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
			:showAdd="true"
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
					<NcActionButton closeAfterClick @click="confirmDelete(row)">
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
import {
	CnIndexPage,
	CnStatusBadge,
	NcActionButton,
	NcActions,
} from '@conduction/nextcloud-vue'
import Delete from 'vue-material-design-icons/Delete.vue'
import DotsHorizontal from 'vue-material-design-icons/DotsHorizontal.vue'
import Pencil from 'vue-material-design-icons/Pencil.vue'
import ConfirmActionDialog from '../../dialogs/ConfirmActionDialog.vue'
import CustomDictionaryFormDialog from '../../dialogs/CustomDictionaryFormDialog.vue'

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

	data() {
		return {
			isRefreshing: false,
			currentPage: 1,
			pageSize: 20,
			dialogOpen: false,
			saving: false,
			formError: '',
			deleteTarget: null, // row awaiting delete confirmation, or null
			deleting: false,
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

		matchModeLabel(mode) {
			const labels = {
				exact: t('filinq', 'Exact'),
				caseInsensitive: t('filinq', 'Case-insensitive'),
				wordBoundary: t('filinq', 'Word boundary'),
			}
			return labels[mode] || mode || t('filinq', 'Case-insensitive')
		},

		async handleRefresh() {
			this.isRefreshing = true
			try {
				await customDictionaryStore.fetchDictionaries()
			} finally {
				this.isRefreshing = false
			}
		},

		onPageChanged(page) {
			this.currentPage = page
		},

		onPageSizeChanged(size) {
			this.pageSize = size
			this.currentPage = 1
		},

		viewDictionary(row) {
			const id = row['@self']?.id || row.id || row.uuid
			this.$router.push({ name: 'CustomDictionaryDetail', params: { id } })
		},

		openCreateDialog() {
			this.formError = ''
			this.dialogOpen = true
		},

		async onCreateSubmit(formData) {
			this.saving = true
			this.formError = ''
			try {
				const created =
					await customDictionaryStore.createDictionary(formData)
				this.dialogOpen = false
				this.viewDictionary(created)
			} catch (err) {
				this.formError =
					err.response?.data?.error
					|| err.message
					|| t('filinq', 'Save failed')
			} finally {
				this.saving = false
			}
		},

		/**
		 * Ask for confirmation before deleting a dictionary.
		 *
		 * Opens ConfirmActionDialog; nothing is removed here.
		 *
		 * @param {object} row - The dictionary row to delete.
		 * @spec openspec/specs/custom-dictionary-recognition/spec.md
		 */
		confirmDelete(row) {
			this.deleteTarget = row
		},

		/**
		 * Dismiss the delete confirmation without deleting anything.
		 *
		 * @spec openspec/specs/custom-dictionary-recognition/spec.md
		 */
		cancelDelete() {
			this.deleteTarget = null
		},

		/**
		 * Delete the confirmed dictionary and all its terms. Reachable only
		 * from the dialog's @confirm, so the record is never removed without
		 * an explicit confirmation.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/specs/custom-dictionary-recognition/spec.md
		 */
		async executeDelete() {
			const row = this.deleteTarget
			if (!row) {
				return
			}
			const id = row['@self']?.id || row.id || row.uuid
			this.deleting = true
			try {
				await customDictionaryStore.deleteDictionary(id)
				this.deleteTarget = null
			} catch (err) {
				console.error('Failed to delete custom dictionary:', err)
			} finally {
				this.deleting = false
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
