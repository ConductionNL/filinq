<script setup>
import { translate as t } from '@nextcloud/l10n'
import { consentStore } from '../../store/store.js'
</script>

<template>
	<CnIndexPage
		ref="indexPage"
		:title="t('filinq', 'Consent Workflow')"
		:description="
			t(
				'filinq',
				'Per-document consent records produced by the publication-clearance workflow.',
			)
		"
		:showTitle="true"
		:objects="workflowConsents"
		:columns="tableColumns"
		:pagination="paginationData"
		:loading="consentStore.loading"
		:selectable="false"
		:showEditAction="false"
		:showCopyAction="false"
		:showDeleteAction="false"
		:showMassImport="false"
		:showMassExport="false"
		:showMassCopy="false"
		:showMassDelete="false"
		:showViewToggle="false"
		:showAdd="false"
		rowKey="id"
		:emptyText="emptyContentName"
		:refreshing="isRefreshing"
		@refresh="handleRefresh"
		@pageChanged="onPageChanged"
		@pageSizeChanged="onPageSizeChanged"
		@rowClick="viewConsent">
		<!-- Stats between the page header and the actions bar.
		     The slot is `below-header`, NOT `above-table`: CnIndexPage
		     defines no `above-table` slot, and Vue drops an unmatched named
		     slot silently, so these four CnStatsBlocks rendered NOTHING at
		     all. See CnIndexPage.vue: `v-if="$slots['below-header']"`. -->
		<template #below-header>
			<div class="consent-stats">
				<CnStatsBlock
					:title="t('filinq', 'Total')"
					:count="consentStore.consentStats.total"
					:countLabel="t('filinq', 'records')"
					variant="default"
					horizontal
					showZeroCount />
				<CnStatsBlock
					:title="t('filinq', 'Pending')"
					:count="consentStore.consentStats.pending"
					:countLabel="t('filinq', 'pending')"
					variant="warning"
					horizontal
					showZeroCount />
				<CnStatsBlock
					:title="t('filinq', 'Approved')"
					:count="consentStore.consentStats.approved"
					:countLabel="t('filinq', 'approved')"
					variant="success"
					horizontal
					showZeroCount />
				<CnStatsBlock
					:title="t('filinq', 'Objected')"
					:count="consentStore.consentStats.objected"
					:countLabel="t('filinq', 'objected')"
					variant="error"
					horizontal
					showZeroCount />
			</div>
		</template>

		<!-- Entity text with policy-pre-empted indicator -->
		<template #column-entityText="{ row }">
			<span>{{ row.entityText }}</span>
			<CnStatusBadge
				v-if="row.policyMatch"
				class="policy-preempted-badge"
				:label="t('filinq', 'policy')"
				:colorMap="{ [t('filinq', 'policy')]: 'primary' }" />
		</template>

		<!-- Entity type badge -->
		<template #column-entityType="{ row }">
			<CnStatusBadge
				:label="row.entityType || t('filinq', 'Unknown')"
				:colorMap="entityTypeColorMap" />
		</template>

		<!-- Consent status badge -->
		<template #column-consentStatus="{ row }">
			<CnStatusBadge
				:label="formatStatus(row.consentStatus)"
				:colorMap="consentStatusColorMap" />
		</template>

		<!-- Notification status badge -->
		<template #column-notificationStatus="{ row }">
			<CnStatusBadge
				:label="formatStatus(row.notificationStatus)"
				:colorMap="notificationStatusColorMap" />
		</template>

		<!-- Deadline column -->
		<template #column-objectionDeadline="{ row }">
			{{ formatDate(row.objectionDeadline) }}
		</template>

		<!-- Publication decision badge -->
		<template #column-publicationDecision="{ row }">
			<CnStatusBadge
				:label="formatDecision(row.publicationDecision)"
				:colorMap="decisionColorMap" />
		</template>

		<!-- Row actions: view detail -->
		<template #row-actions="{ row }">
			<NcActions>
				<template #icon>
					<DotsHorizontal :size="20" />
				</template>
				<NcActionButton closeAfterClick @click="viewConsent(row)">
					<template #icon>
						<Eye :size="20" />
					</template>
					{{ t('filinq', 'View Details') }}
				</NcActionButton>
			</NcActions>
		</template>
	</CnIndexPage>
</template>

<script>
import { CnIndexPage, CnStatsBlock, CnStatusBadge } from '@conduction/nextcloud-vue'
import { NcActionButton, NcActions } from '@nextcloud/vue'
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
				[t('filinq', 'Pending')]: 'default',
				[t('filinq', 'Approved')]: 'success',
				[t('filinq', 'Objected')]: 'error',
				[t('filinq', 'No Response')]: 'warning',
				[t('filinq', 'Anonymized')]: 'primary',
			},

			notificationStatusColorMap: {
				[t('filinq', 'Pending')]: 'default',
				[t('filinq', 'Sent')]: 'primary',
				[t('filinq', 'Delivered')]: 'success',
				[t('filinq', 'Failed')]: 'error',
				[t('filinq', 'Skipped')]: 'warning',
			},

			decisionColorMap: {
				[t('filinq', 'Pending')]: 'default',
				[t('filinq', 'Publish')]: 'success',
				[t('filinq', 'Publish Anonymized')]: 'primary',
				[t('filinq', 'Anonymize')]: 'warning',
				[t('filinq', 'Rejected')]: 'error',
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
				{
					key: 'entityText',
					label: t('filinq', 'Entity'),
					sortable: true,
				},
				{ key: 'entityType', label: t('filinq', 'Type'), sortable: true },
				{
					key: 'consentStatus',
					label: t('filinq', 'Consent Status'),
					sortable: true,
				},
				{
					key: 'notificationStatus',
					label: t('filinq', 'Notification'),
					sortable: true,
				},
				{
					key: 'objectionDeadline',
					label: t('filinq', 'Deadline'),
					sortable: true,
				},
				{
					key: 'publicationDecision',
					label: t('filinq', 'Decision'),
					sortable: true,
				},
			]
		},

		// Workflow records only — scope:"entity" rows live on the Standing Consents page.
		workflowConsents() {
			return consentStore.consents.filter(
				(c) => (c.scope || 'document') === 'document',
			)
		},

		paginationData() {
			const total = this.workflowConsents.length
			const pages = Math.ceil(total / this.pageSize)
			return { page: this.currentPage, pages, total, limit: this.pageSize }
		},

		/**
		 * NcEmptyContent heading for the Consent Workflow list — the store's
		 * error when loading failed, otherwise the empty-list message.
		 *
		 * @return {string}
		 * @spec openspec/specs/consent-management/spec.md#requirement-consent-ui-req-cons-10
		 */
		emptyContentName() {
			if (consentStore.error) {
				return consentStore.error
			}
			return t('filinq', 'No consent records found')
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
			this.$router.push({
				name: 'ConsentDetail',
				params: { id: consent.id || consent.uuid },
			})
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
				pending: t('filinq', 'Pending'),
				consent_given: t('filinq', 'Approved'),
				objection_received: t('filinq', 'Objected'),
				no_response: t('filinq', 'No Response'),
				anonymized: t('filinq', 'Anonymized'),
				sent: t('filinq', 'Sent'),
				delivered: t('filinq', 'Delivered'),
				failed: t('filinq', 'Failed'),
				skipped: t('filinq', 'Skipped'),
			}
			return map[status] || status || t('filinq', 'Unknown')
		},

		/**
		 * Map a publication-decision code to a localized label.
		 *
		 * @param decision
		 * @spec openspec/specs/consent-management/spec.md#requirement-consent-ui-req-cons-10
		 */
		formatDecision(decision) {
			const map = {
				pending: t('filinq', 'Pending'),
				anonymize: t('filinq', 'Anonymize'),
				publish_with_consent: t('filinq', 'Publish'),
				publish_anonymized: t('filinq', 'Publish Anonymized'),
				reject: t('filinq', 'Rejected'),
			}
			return map[decision] || decision || t('filinq', 'Pending')
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
	margin-inline-start: 8px;
}
</style>
