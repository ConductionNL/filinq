<script setup>
import { translate as t } from '@nextcloud/l10n'
import { prohibitionStore } from '../../store/store.js'
</script>

<template>
	<div>
		<CnIndexPage
			ref="indexPage"
			:title="t('docudesk', 'Publish never')"
			:description="
				t(
					'docudesk',
					'Entity-level deny rules. A matched entity is always anonymised, regardless of the per-document consent workflow.',
				)
			"
			:showTitle="true"
			:objects="prohibitionStore.prohibitions"
			:columns="tableColumns"
			:pagination="paginationData"
			:loading="prohibitionStore.loading"
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
			@add="openCreateDialog">
			<template #above-table>
				<div class="policy-stats">
					<CnStatsBlock
						:title="t('docudesk', 'Total')"
						:count="prohibitionStore.prohibitionStats.total"
						:countLabel="t('docudesk', 'rules')"
						variant="default"
						horizontal
						showZeroCount />
					<CnStatsBlock
						:title="t('docudesk', 'Active')"
						:count="prohibitionStore.prohibitionStats.active"
						:countLabel="t('docudesk', 'active')"
						variant="error"
						horizontal
						showZeroCount />
					<CnStatsBlock
						:title="t('docudesk', 'Inactive')"
						:count="prohibitionStore.prohibitionStats.inactive"
						:countLabel="t('docudesk', 'inactive')"
						variant="default"
						horizontal
						showZeroCount />
				</div>
			</template>

			<template #column-entityType="{ row }">
				<CnStatusBadge
					:label="row.entityType || t('docudesk', 'Unknown')"
					:colorMap="entityTypeColorMap" />
			</template>

			<template #column-severity="{ row }">
				<CnStatusBadge
					:label="row.severity || '-'"
					:colorMap="severityColorMap" />
			</template>

			<template #column-active="{ row }">
				<CnStatusBadge
					:label="
						row.active === false
							? t('docudesk', 'Inactive')
							: t('docudesk', 'Active')
					"
					:colorMap="activeColorMap" />
			</template>

			<template #column-matchRules="{ row }">
				{{ formatMatchRules(row.matchRules) }}
			</template>

			<template #row-actions="{ row }">
				<NcActions>
					<template #icon>
						<DotsHorizontal :size="20" />
					</template>
					<NcActionButton closeAfterClick @click="openEditDialog(row)">
						<template #icon>
							<Pencil :size="20" />
						</template>
						{{ t('docudesk', 'Edit') }}
					</NcActionButton>
					<NcActionButton closeAfterClick @click="confirmDelete(row)">
						<template #icon>
							<Delete :size="20" />
						</template>
						{{ t('docudesk', 'Delete') }}
					</NcActionButton>
				</NcActions>
			</template>
		</CnIndexPage>

		<!--
			ADR-004 gate-13: modal lives in its own component, not inline.
			ProhibitionFormModal handles its own form state; we only own
			the open flag, the record being edited, and the save outcome.
		-->
		<ProhibitionFormModal
			:open="dialogOpen"
			:editingRecord="editingRecord"
			:saving="saving"
			:formError="formError"
			@update:open="dialogOpen = $event"
			@submit="onModalSubmit"
			@cancel="dialogOpen = false" />

		<!--
			Delete confirmation. Replaces window.confirm(): the deletion below
			runs only from @confirm, so an explicit confirmation is still
			required before anything is removed.
		-->
		<ConfirmActionDialog
			v-if="deleteTarget"
			:name="t('docudesk', 'Delete prohibition')"
			:message="deleteMessage"
			:busy="deleting"
			@confirm="executeDelete"
			@cancel="cancelDelete" />
	</div>
</template>

<script>
import { CnIndexPage, CnStatsBlock, CnStatusBadge } from '@conduction/nextcloud-vue'
import { NcActionButton, NcActions } from '@nextcloud/vue'
import Delete from 'vue-material-design-icons/Delete.vue'
import DotsHorizontal from 'vue-material-design-icons/DotsHorizontal.vue'
import Pencil from 'vue-material-design-icons/Pencil.vue'
import ConfirmActionDialog from '../../dialogs/ConfirmActionDialog.vue'
import ProhibitionFormModal from '../../dialogs/ProhibitionFormModal.vue'

export default {
	name: 'ProhibitionIndex',
	components: {
		CnIndexPage,
		CnStatsBlock,
		CnStatusBadge,
		NcActions,
		NcActionButton,
		ProhibitionFormModal,
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
			editing: null, // UUID of the record being edited, or null for create
			editingRecord: null, // full record passed to the modal so it can hydrate its form
			formError: '',
			deleteTarget: null, // row awaiting delete confirmation, or null
			deleting: false,
			entityTypeColorMap: {
				PERSON: 'warning',
				ORGANIZATION: 'primary',
				OTHER: 'default',
			},

			// Severity values must mirror the publicationProhibition schema
			// enum (`high` / `medium` / `low`) in docudesk_register.json.
			severityColorMap: {
				high: 'error',
				medium: 'warning',
				low: 'default',
			},

			activeColorMap: {
				[t('docudesk', 'Active')]: 'error',
				[t('docudesk', 'Inactive')]: 'default',
			},
		}
	},

	computed: {
		tableColumns() {
			return [
				{
					key: 'primaryName',
					label: t('docudesk', 'Primary name'),
					sortable: true,
				},
				{ key: 'entityType', label: t('docudesk', 'Type'), sortable: true },
				{ key: 'matchRules', label: t('docudesk', 'Match rules') },
				{
					key: 'severity',
					label: t('docudesk', 'Severity'),
					sortable: true,
				},
				{ key: 'reason', label: t('docudesk', 'Reason') },
				{ key: 'active', label: t('docudesk', 'Status'), sortable: true },
			]
		},

		paginationData() {
			const total = prohibitionStore.prohibitions.length
			const pages = Math.ceil(total / this.pageSize)
			return { page: this.currentPage, pages, total, limit: this.pageSize }
		},

		emptyText() {
			if (prohibitionStore.error) {
				return prohibitionStore.error
			}
			return t('docudesk', 'No publication prohibitions defined.')
		},

		/**
		 * Body text of the delete confirmation dialog.
		 *
		 * @spec openspec/specs/orphaned-surface-restoration/spec.md#requirement-policy-surfaces-are-reachable-menu-ownership-deferred-req-ddosr-005
		 */
		deleteMessage() {
			const name =
				this.deleteTarget?.primaryName || t('docudesk', 'this prohibition')
			return t('docudesk', 'Delete "{name}"? This cannot be undone.', { name })
		},
	},

	mounted() {
		prohibitionStore.fetchProhibitions()
	},

	methods: {
		async handleRefresh() {
			this.isRefreshing = true
			try {
				await prohibitionStore.fetchProhibitions()
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

		formatMatchRules(rules) {
			if (!Array.isArray(rules) || rules.length === 0) {
				return '-'
			}
			return rules.map((r) => `${r.type}:${r.value}`).join(', ')
		},

		openCreateDialog() {
			this.editing = null
			this.editingRecord = null
			this.formError = ''
			this.dialogOpen = true
		},

		openEditDialog(row) {
			this.editing = row['@self']?.id || row.id || row.uuid
			this.editingRecord = {
				primaryName: row.primaryName || '',
				entityType: row.entityType || 'PERSON',
				reason: row.reason || '',
				legalAuthority: row.legalAuthority || '',
				caseReference: row.caseReference || '',
				severity: row.severity || 'standard',
				jurisdiction: row.jurisdiction || '',
				validUntil: row.validUntil || '',
				active: row.active !== false,
				matchRules: Array.isArray(row.matchRules)
					? row.matchRules.map((r) => ({ type: r.type, value: r.value }))
					: [],
			}
			this.formError = ''
			this.dialogOpen = true
		},

		async onModalSubmit(formData) {
			this.saving = true
			this.formError = ''
			try {
				if (this.editing) {
					await prohibitionStore.updateProhibition(this.editing, formData)
				} else {
					await prohibitionStore.createProhibition(formData)
				}
				this.dialogOpen = false
			} catch (err) {
				this.formError =
					err.response?.data?.error
					|| err.message
					|| t('docudesk', 'Save failed')
			} finally {
				this.saving = false
			}
		},

		/**
		 * Ask for confirmation before deleting a prohibition.
		 *
		 * Opens ConfirmActionDialog; nothing is removed here.
		 *
		 * @param {object} row - The prohibition row to delete.
		 * @spec openspec/specs/orphaned-surface-restoration/spec.md#requirement-policy-surfaces-are-reachable-menu-ownership-deferred-req-ddosr-005
		 */
		confirmDelete(row) {
			this.deleteTarget = row
		},

		/**
		 * Dismiss the delete confirmation without deleting anything.
		 *
		 * @spec openspec/specs/orphaned-surface-restoration/spec.md#requirement-policy-surfaces-are-reachable-menu-ownership-deferred-req-ddosr-005
		 */
		cancelDelete() {
			this.deleteTarget = null
		},

		/**
		 * Delete the confirmed prohibition. Reachable only from the dialog's
		 *
		 * @confirm, so the record is never removed without an explicit
		 * confirmation.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/specs/orphaned-surface-restoration/spec.md#requirement-policy-surfaces-are-reachable-menu-ownership-deferred-req-ddosr-005
		 */
		async executeDelete() {
			const row = this.deleteTarget
			if (!row) {
				return
			}
			const id = row['@self']?.id || row.id || row.uuid
			this.deleting = true
			try {
				await prohibitionStore.deleteProhibition(id)
				this.deleteTarget = null
			} catch (err) {
				console.error('Failed to delete prohibition:', err)
			} finally {
				this.deleting = false
			}
		},
	},
}
</script>

<style scoped>
.policy-stats {
	display: flex;
	gap: 16px;
	margin-bottom: 16px;
	flex-wrap: wrap;
}

.prohibition-form {
	display: flex;
	flex-direction: column;
	gap: 12px;
	padding: 8px;
}

.match-rule-row {
	display: grid;
	grid-template-columns: 180px 1fr 40px;
	gap: 8px;
	align-items: center;
}

.form-warning {
	background: var(--color-warning, #fff3cd);
	color: var(--color-text-maxcontrast, #333);
	padding: 8px 12px;
	border-radius: var(--border-radius);
	font-size: 13px;
}

.form-error {
	background: var(--color-error, #ffd1d1);
	color: var(--color-text-maxcontrast, #333);
	padding: 8px 12px;
	border-radius: var(--border-radius);
	font-size: 13px;
}
</style>
