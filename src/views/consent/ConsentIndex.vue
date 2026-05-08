<script setup>
import { translate as t } from '@nextcloud/l10n'
import { consentStore } from '../../store/store.js'
</script>

<template>
	<CnIndexPage
		ref="indexPage"
		:title="t('docudesk', 'Consent Management')"
		:description="t('docudesk', 'Manage publication consent records for detected entities')"
		:show-title="true"
		:objects="consentStore.consents"
		:columns="tableColumns"
		:pagination="paginationData"
		:loading="consentStore.loading"
		:selectable="false"
		:show-edit-action="false"
		:show-copy-action="false"
		:show-delete-action="false"
		:show-mass-import="false"
		:show-mass-export="false"
		:show-mass-copy="false"
		:show-mass-delete="false"
		:show-view-toggle="false"
		:show-add="false"
		row-key="id"
		:empty-text="emptyContentName"
		:refreshing="isRefreshing"
		@refresh="handleRefresh"
		@page-changed="onPageChanged"
		@page-size-changed="onPageSizeChanged"
		@row-click="viewConsent">
		<!-- Stats above the table -->
		<template #above-table>
			<div class="consent-stats">
				<CnStatsBlock
					:title="t('docudesk', 'Total')"
					:count="consentStore.consentStats.total"
					:count-label="t('docudesk', 'records')"
					variant="default"
					horizontal
					show-zero-count />
				<CnStatsBlock
					:title="t('docudesk', 'Pending')"
					:count="consentStore.consentStats.pending"
					:count-label="t('docudesk', 'pending')"
					variant="warning"
					horizontal
					show-zero-count />
				<CnStatsBlock
					:title="t('docudesk', 'Approved')"
					:count="consentStore.consentStats.approved"
					:count-label="t('docudesk', 'approved')"
					variant="success"
					horizontal
					show-zero-count />
				<CnStatsBlock
					:title="t('docudesk', 'Objected')"
					:count="consentStore.consentStats.objected"
					:count-label="t('docudesk', 'objected')"
					variant="error"
					horizontal
					show-zero-count />
			</div>
		</template>

		<!-- Entity type badge -->
		<template #column-entityType="{ row }">
			<CnStatusBadge
				:label="row.entityType || t('docudesk', 'Unknown')"
				:color-map="entityTypeColorMap" />
		</template>

		<!-- Consent status badge -->
		<template #column-consentStatus="{ row }">
			<CnStatusBadge
				:label="formatStatus(row.consentStatus)"
				:color-map="consentStatusColorMap" />
		</template>

		<!-- Notification status badge -->
		<template #column-notificationStatus="{ row }">
			<CnStatusBadge
				:label="formatStatus(row.notificationStatus)"
				:color-map="notificationStatusColorMap" />
		</template>

		<!-- Deadline column -->
		<template #column-objectionDeadline="{ row }">
			{{ formatDate(row.objectionDeadline) }}
		</template>

		<!-- Publication decision badge -->
		<template #column-publicationDecision="{ row }">
			<CnStatusBadge
				:label="formatDecision(row.publicationDecision)"
				:color-map="decisionColorMap" />
		</template>

		<!-- Row actions: view detail -->
		<template #row-actions="{ row }">
			<NcActions>
				<template #icon>
					<DotsHorizontal :size="20" />
				</template>
				<NcActionButton close-after-click @click="viewConsent(row)">
					<template #icon>
						<Eye :size="20" />
					</template>
					{{ t('docudesk', 'View Details') }}
				</NcActionButton>
			</NcActions>
		</template>
	</CnIndexPage>
</template>

<script>
import { NcActions, NcActionButton } from '@nextcloud/vue'
import { CnIndexPage, CnStatsBlock, CnStatusBadge } from '@conduction/nextcloud-vue'
import DotsHorizontal from 'vue-material-design-icons/DotsHorizontal.vue'
import Eye from 'vue-material-design-icons/Eye.vue'

export default {
	name: 'ConsentIndex',
	components: {
		CnIndexPage,
		CnStatsBlock,
		CnStatusBadge,
		NcActions,
		NcActionButton,
		DotsHorizontal,
		Eye,
	},
	data() {
		return {
			isRefreshing: false,
			currentPage: 1,
			pageSize: 20,
			entityTypeColorMap: {
				person: 'warning',
				organization: 'primary',
			},
			consentStatusColorMap: {
				[t('docudesk', 'Pending')]: 'default',
				[t('docudesk', 'Approved')]: 'success',
				[t('docudesk', 'Objected')]: 'error',
				[t('docudesk', 'No Response')]: 'warning',
				[t('docudesk', 'Anonymized')]: 'primary',
			},
			notificationStatusColorMap: {
				[t('docudesk', 'Pending')]: 'default',
				[t('docudesk', 'Sent')]: 'primary',
				[t('docudesk', 'Delivered')]: 'success',
				[t('docudesk', 'Failed')]: 'error',
				[t('docudesk', 'Skipped')]: 'warning',
			},
			decisionColorMap: {
				[t('docudesk', 'Pending')]: 'default',
				[t('docudesk', 'Publish')]: 'success',
				[t('docudesk', 'Publish Anonymized')]: 'primary',
				[t('docudesk', 'Anonymize')]: 'warning',
				[t('docudesk', 'Rejected')]: 'error',
			},
		}
	},
	computed: {
		tableColumns() {
			return [
				{ key: 'entityText', label: t('docudesk', 'Entity'), sortable: true },
				{ key: 'entityType', label: t('docudesk', 'Type'), sortable: true },
				{ key: 'consentStatus', label: t('docudesk', 'Consent Status'), sortable: true },
				{ key: 'notificationStatus', label: t('docudesk', 'Notification'), sortable: true },
				{ key: 'objectionDeadline', label: t('docudesk', 'Deadline'), sortable: true },
				{ key: 'publicationDecision', label: t('docudesk', 'Decision'), sortable: true },
			]
		},
		paginationData() {
			const total = consentStore.consents.length
			const pages = Math.ceil(total / this.pageSize)
			return { page: this.currentPage, pages, total, limit: this.pageSize }
		},
		emptyContentName() {
			if (consentStore.error) {
				return consentStore.error
			}
			return t('docudesk', 'No consent records found')
		},
	},
	mounted() {
		consentStore.fetchConsents()
	},
	methods: {
		viewConsent(consent) {
			consentStore.setConsentItem(consent)
			this.$router.push({ name: 'ConsentDetail', params: { id: consent.id || consent.uuid } })
		},
		async handleRefresh() {
			this.isRefreshing = true
			try {
				await consentStore.fetchConsents()
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
		formatStatus(status) {
			const map = {
				pending: t('docudesk', 'Pending'),
				consent_given: t('docudesk', 'Approved'),
				objection_received: t('docudesk', 'Objected'),
				no_response: t('docudesk', 'No Response'),
				anonymized: t('docudesk', 'Anonymized'),
				sent: t('docudesk', 'Sent'),
				delivered: t('docudesk', 'Delivered'),
				failed: t('docudesk', 'Failed'),
				skipped: t('docudesk', 'Skipped'),
			}
			return map[status] || status || t('docudesk', 'Unknown')
		},
		formatDecision(decision) {
			const map = {
				pending: t('docudesk', 'Pending'),
				anonymize: t('docudesk', 'Anonymize'),
				publish_with_consent: t('docudesk', 'Publish'),
				publish_anonymized: t('docudesk', 'Publish Anonymized'),
				reject: t('docudesk', 'Rejected'),
			}
			return map[decision] || decision || t('docudesk', 'Pending')
		},
		formatDate(dateStr) {
			if (!dateStr) return '-'
			try {
				return new Date(dateStr).toLocaleDateString()
			} catch (e) {
				return dateStr
			}
		},
	},
}
</script>

<style scoped>
.consent-stats {
	display: flex;
	gap: 16px;
	margin-bottom: 16px;
	flex-wrap: wrap;
}
</style>
