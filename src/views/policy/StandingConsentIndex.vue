<script setup>
import { translate as t } from '@nextcloud/l10n'
import { standingConsentStore } from '../../store/store.js'
</script>

<template>
	<div>
		<CnIndexPage
			ref="indexPage"
			:title="t('docudesk', 'Publish always')"
			:description="t('docudesk', 'Entity-level allow rules. A matched entity may be published without per-document objection workflow, unless a publish-never rule also matches.')"
			:show-title="true"
			:objects="standingConsentStore.standingConsents"
			:columns="tableColumns"
			:pagination="paginationData"
			:loading="standingConsentStore.loading"
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
						:count="standingConsentStore.standingConsentStats.total"
						:count-label="t('docudesk', 'rules')"
						variant="default"
						horizontal
						show-zero-count />
					<CnStatsBlock
						:title="t('docudesk', 'Active')"
						:count="standingConsentStore.standingConsentStats.active"
						:count-label="t('docudesk', 'active')"
						variant="success"
						horizontal
						show-zero-count />
					<CnStatsBlock
						:title="t('docudesk', 'Inactive')"
						:count="standingConsentStore.standingConsentStats.inactive"
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

			<template #column-consentMethod="{ row }">
				<CnStatusBadge
					:label="row.consentMethod || '-'"
					:color-map="methodColorMap" />
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
			StandingConsentFormModal handles its own form state; we only own
			the open flag, the record being edited, and the save outcome.
			(Previously an inline NcDialog duplicated this exact form —
			replaced here; openspec/changes/orphaned-surface-restoration.)
		-->
		<StandingConsentFormModal
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
import StandingConsentFormModal from './StandingConsentFormModal.vue'

export default {
	name: 'StandingConsentIndex',
	components: {
		CnIndexPage,
		CnStatsBlock,
		CnStatusBadge,
		NcActions,
		NcActionButton,
		StandingConsentFormModal,
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
			methodColorMap: {
				paper: 'default',
				digital_signature: 'primary',
				verbal_recorded: 'warning',
				opt_in_form: 'success',
			},
			activeColorMap: {
				[t('docudesk', 'Active')]: 'success',
				[t('docudesk', 'Inactive')]: 'default',
			},
		}
	},
	computed: {
		tableColumns() {
			return [
				{ key: 'entityText', label: t('docudesk', 'Entity'), sortable: true },
				{ key: 'entityType', label: t('docudesk', 'Type'), sortable: true },
				{ key: 'matchRules', label: t('docudesk', 'Match rules') },
				{ key: 'consentMethod', label: t('docudesk', 'Method'), sortable: true },
				{ key: 'validUntil', label: t('docudesk', 'Valid until'), sortable: true },
				{ key: 'active', label: t('docudesk', 'Status'), sortable: true },
			]
		},
		paginationData() {
			const total = standingConsentStore.standingConsents.length
			const pages = Math.ceil(total / this.pageSize)
			return { page: this.currentPage, pages, total, limit: this.pageSize }
		},
		emptyText() {
			if (standingConsentStore.error) {
				return standingConsentStore.error
			}
			return t('docudesk', 'No standing publication consents defined.')
		},
	},
	mounted() {
		standingConsentStore.fetchStandingConsents()
	},
	methods: {
		async handleRefresh() {
			this.isRefreshing = true
			try {
				await standingConsentStore.fetchStandingConsents()
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
				entityText: row.entityText || '',
				entityType: row.entityType || 'PERSON',
				consentMethod: row.consentMethod || '',
				consentDocument: row.consentDocument || '',
				consentScope: row.consentScope || '',
				legalBasis: row.legalBasis || '',
				validFrom: row.validFrom || '',
				validUntil: row.validUntil || '',
				active: row.active !== false,
				matchRules: Array.isArray(row.matchRules)
					? row.matchRules.map(r => ({ type: r.type, value: r.value }))
					: [],
				consentStatus: row.consentStatus || 'consent_given',
				publicationDecision: row.publicationDecision || 'publish_with_consent',
				notificationStatus: row.notificationStatus || 'skipped',
			}
			this.formError = ''
			this.dialogOpen = true
		},
		async onModalSubmit(formData) {
			this.saving = true
			this.formError = ''
			try {
				if (this.editing) {
					await standingConsentStore.updateStandingConsent(this.editing, formData)
				} else {
					await standingConsentStore.createStandingConsent(formData)
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
			const name = row.entityText || t('docudesk', 'this standing consent')
			// eslint-disable-next-line no-alert
			if (!window.confirm(t('docudesk', 'Delete "{name}"? This cannot be undone.', { name }))) {
				return
			}
			try {
				await standingConsentStore.deleteStandingConsent(id)
			} catch (err) {
				console.error('Failed to delete standing consent:', err)
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

.standing-consent-form {
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
