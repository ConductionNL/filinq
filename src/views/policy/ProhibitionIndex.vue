<script setup>
import { translate as t } from '@nextcloud/l10n'
import { prohibitionStore } from '../../store/store.js'
</script>

<template>
	<div>
		<CnIndexPage
			ref="indexPage"
			:title="t('docudesk', 'Publish never')"
			:description="t('docudesk', 'Entity-level deny rules. A matched entity is always anonymised, regardless of the per-document consent workflow.')"
			:show-title="true"
			:objects="prohibitionStore.prohibitions"
			:columns="tableColumns"
			:pagination="paginationData"
			:loading="prohibitionStore.loading"
			:selectable="false"
			:show-edit-action="false"
			:show-copy-action="false"
			:show-delete-action="false"
			:show-mass-import="false"
			:show-mass-export="false"
			:show-mass-copy="false"
			:show-mass-delete="false"
			:show-view-toggle="false"
			:show-add="true"
			row-key="id"
			:empty-text="emptyText"
			:refreshing="isRefreshing"
			@refresh="handleRefresh"
			@page-changed="onPageChanged"
			@page-size-changed="onPageSizeChanged"
			@add="openCreateDialog">
			<template #above-table>
				<div class="policy-stats">
					<CnStatsBlock
						:title="t('docudesk', 'Total')"
						:count="prohibitionStore.prohibitionStats.total"
						:count-label="t('docudesk', 'rules')"
						variant="default"
						horizontal
						show-zero-count />
					<CnStatsBlock
						:title="t('docudesk', 'Active')"
						:count="prohibitionStore.prohibitionStats.active"
						:count-label="t('docudesk', 'active')"
						variant="error"
						horizontal
						show-zero-count />
					<CnStatsBlock
						:title="t('docudesk', 'Inactive')"
						:count="prohibitionStore.prohibitionStats.inactive"
						:count-label="t('docudesk', 'inactive')"
						variant="default"
						horizontal
						show-zero-count />
				</div>
			</template>

			<template #column-entityType="{ row }">
				<CnStatusBadge
					:label="row.entityType || t('docudesk', 'Unknown')"
					:color-map="entityTypeColorMap" />
			</template>

			<template #column-severity="{ row }">
				<CnStatusBadge
					:label="row.severity || '-'"
					:color-map="severityColorMap" />
			</template>

			<template #column-active="{ row }">
				<CnStatusBadge
					:label="row.active === false ? t('docudesk', 'Inactive') : t('docudesk', 'Active')"
					:color-map="activeColorMap" />
			</template>

			<template #column-matchRules="{ row }">
				{{ formatMatchRules(row.matchRules) }}
			</template>

			<template #row-actions="{ row }">
				<NcActions>
					<template #icon>
						<DotsHorizontal :size="20" />
					</template>
					<NcActionButton close-after-click @click="openEditDialog(row)">
						<template #icon>
							<Pencil :size="20" />
						</template>
						{{ t('docudesk', 'Edit') }}
					</NcActionButton>
					<NcActionButton close-after-click @click="confirmDelete(row)">
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
			:editing-record="editingRecord"
			:saving="saving"
			:form-error="formError"
			@update:open="dialogOpen = $event"
			@submit="onModalSubmit"
			@cancel="dialogOpen = false" />
	</div>
</template>

<script>
import {
	NcActions,
	NcActionButton,
} from '@nextcloud/vue'
import { CnIndexPage, CnStatsBlock, CnStatusBadge } from '@conduction/nextcloud-vue'
import Delete from 'vue-material-design-icons/Delete.vue'
import DotsHorizontal from 'vue-material-design-icons/DotsHorizontal.vue'
import Pencil from 'vue-material-design-icons/Pencil.vue'
import ProhibitionFormModal from './ProhibitionFormModal.vue'

export default {
	name: 'ProhibitionIndex',
	components: {
		CnIndexPage,
		CnStatsBlock,
		CnStatusBadge,
		NcActions,
		NcActionButton,
		ProhibitionFormModal,
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
				{ key: 'primaryName', label: t('docudesk', 'Primary name'), sortable: true },
				{ key: 'entityType', label: t('docudesk', 'Type'), sortable: true },
				{ key: 'matchRules', label: t('docudesk', 'Match rules') },
				{ key: 'severity', label: t('docudesk', 'Severity'), sortable: true },
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
			return rules.map(r => `${r.type}:${r.value}`).join(', ')
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
					? row.matchRules.map(r => ({ type: r.type, value: r.value }))
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
				this.formError = err.response?.data?.error || err.message || t('docudesk', 'Save failed')
			} finally {
				this.saving = false
			}
		},
		async confirmDelete(row) {
			const id = row['@self']?.id || row.id || row.uuid
			const name = row.primaryName || t('docudesk', 'this prohibition')
			// eslint-disable-next-line no-alert
			if (!window.confirm(t('docudesk', 'Delete "{name}"? This cannot be undone.', { name }))) {
				return
			}
			try {
				await prohibitionStore.deleteProhibition(id)
			} catch (err) {
				console.error('Failed to delete prohibition:', err)
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
