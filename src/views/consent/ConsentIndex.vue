<script setup>
import { translate as t } from '@nextcloud/l10n'
import { consentStore } from '../../store/store.js'
</script>

<template>
	<CnIndexPage
		ref="indexPage"
		:title="t('docudesk', 'Consent Workflow')"
		:description="t('docudesk', 'Per-document consent records produced by the publication-clearance workflow.')"
		:show-title="true"
		:objects="workflowConsents"
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

		<!-- Entity text with policy-pre-empted indicator -->
		<template #column-entityText="{ row }">
			<span>{{ row.entityText }}</span>
			<CnStatusBadge
				v-if="row.policyMatch"
				class="policy-preempted-badge"
				:label="t('docudesk', 'policy')"
				:color-map="{ [t('docudesk', 'policy')]: 'primary' }" />
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
		/**
		 * Column definitions for the consent records table.
		 *
		 * @spec openspec/specs/consent-management/spec.md#requirement-consent-ui-req-cons-10
		 */
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
		// Workflow records only — scope:"entity" rows live on the Standing Consents page.
		workflowConsents() {
			return consentStore.consents.filter(c => (c.scope || 'document') === 'document')
		},
		paginationData() {
			const total = this.workflowConsents.length
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
		/**
		 * Open the selected consent record in the detail view.
		 *
		 * @param consent
		 * @spec openspec/specs/consent-management/spec.md#requirement-consent-ui-req-cons-10
		 */
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
		/**
		 * Update the current page index of the consent table.
		 *
		 * @param page
		 * @spec openspec/specs/consent-management/spec.md#requirement-consent-listing-and-querying-req-cons-03
		 */
		onPageChanged(page) {
			this.currentPage = page
		},
		/**
		 * Update the page size and reset to the first page.
		 *
		 * @param size
		 * @spec openspec/specs/consent-management/spec.md#requirement-consent-listing-and-querying-req-cons-03
		 */
		onPageSizeChanged(size) {
			this.pageSize = size
			this.currentPage = 1
		},
		/**
		 * Map a consent/notification status code to a localized label.
		 *
		 * @param status
		 * @spec openspec/specs/consent-management/spec.md#requirement-consent-ui-req-cons-10
		 */
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
		/**
		 * Map a publication-decision code to a localized label.
		 *
		 * @param decision
		 * @spec openspec/specs/consent-management/spec.md#requirement-consent-ui-req-cons-10
		 */
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
		/**
		 * Format a date string for display, falling back gracefully.
		 *
		 * @param dateStr
		 * @spec openspec/specs/consent-management/spec.md#requirement-consent-ui-req-cons-10
		 */
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

.policy-preempted-badge {
	margin-left: 8px;
}
</style>
