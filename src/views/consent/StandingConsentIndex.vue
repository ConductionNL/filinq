<script setup>
import { translate as t } from '@nextcloud/l10n'
import { consentStore } from '../../store/store.js'
</script>

<template>
	<div>
		<CnIndexPage
			ref="indexPage"
			:title="t('docudesk', 'Standing Publication Consents')"
			:description="
				t(
					'docudesk',
					'Manage entity-level standing publication consent records',
				)
			"
			:showTitle="true"
			:objects="entityConsents"
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
			:showAdd="true"
			rowKey="id"
			:emptyText="emptyContentName"
			:refreshing="isRefreshing"
			@refresh="handleRefresh"
			@pageChanged="onPageChanged"
			@pageSizeChanged="onPageSizeChanged"
			@add="openCreateModal">
			<!-- Stats above the table -->
			<template #above-table>
				<div class="consent-stats">
					<CnStatsBlock
						:title="t('docudesk', 'Total')"
						:count="entityConsents.length"
						:countLabel="t('docudesk', 'records')"
						variant="default"
						horizontal
						showZeroCount />
					<CnStatsBlock
						:title="t('docudesk', 'Active')"
						:count="activeCount"
						:countLabel="t('docudesk', 'active')"
						variant="success"
						horizontal
						showZeroCount />
					<CnStatsBlock
						:title="t('docudesk', 'Inactive')"
						:count="inactiveCount"
						:countLabel="t('docudesk', 'inactive')"
						variant="warning"
						horizontal
						showZeroCount />
				</div>
			</template>

			<!-- Entity type badge -->
			<template #column-entityType="{ row }">
				<CnStatusBadge
					:label="row.entityType || t('docudesk', 'Unknown')"
					:colorMap="entityTypeColorMap" />
			</template>

			<!-- Consent method badge -->
			<template #column-consentMethod="{ row }">
				<CnStatusBadge
					:label="formatConsentMethod(row.consentMethod)"
					:colorMap="consentMethodColorMap" />
			</template>

			<!-- Valid From column -->
			<template #column-validFrom="{ row }">
				{{ formatDate(row.validFrom) }}
			</template>

			<!-- Valid Until column -->
			<template #column-validUntil="{ row }">
				{{ formatDate(row.validUntil) }}
			</template>

			<!-- Active column -->
			<template #column-active="{ row }">
				<CnStatusBadge
					:label="row.active ? t('docudesk', 'Yes') : t('docudesk', 'No')"
					:colorMap="activeColorMap" />
			</template>

			<!-- Consent status badge -->
			<template #column-consentStatus="{ row }">
				<CnStatusBadge
					:label="formatStatus(row.consentStatus)"
					:colorMap="consentStatusColorMap" />
			</template>

			<!-- Row actions -->
			<template #row-actions="{ row }">
				<NcActions>
					<template #icon>
						<DotsHorizontal :size="20" />
					</template>
					<NcActionButton
						closeAfterClick
						:disabled="row.active === false"
						@click="expireConsent(row)">
						<template #icon>
							<ClockRemove :size="20" />
						</template>
						{{ t('docudesk', 'Expire') }}
					</NcActionButton>
					<NcActionButton closeAfterClick @click="revokeConsent(row)">
						<template #icon>
							<Cancel :size="20" />
						</template>
						{{ t('docudesk', 'Revoke') }}
					</NcActionButton>
				</NcActions>
			</template>
		</CnIndexPage>

		<!-- Create standing consent modal -->
		<CreateStandingConsentModal
			:show="showCreateModal"
			@close="closeCreateModal"
			@created="handleCreate" />

		<!--
			Revoke confirmation. Replaces window.confirm(): revokeConsent below
			runs only from @confirm, so the withdrawal still requires an
			explicit confirmation.
		-->
		<ConfirmActionDialog
			v-if="revokeTarget"
			:name="t('docudesk', 'Revoke standing consent')"
			:message="
				t(
					'docudesk',
					'Revoke this standing consent? This withdraws permission for any in-flight publications and cannot be undone.',
				)
			"
			:confirmLabel="t('docudesk', 'Revoke')"
			:busy="revoking"
			@confirm="executeRevoke"
			@cancel="cancelRevoke" />
	</div>
</template>

<script>
import { CnIndexPage, CnStatsBlock, CnStatusBadge } from '@conduction/nextcloud-vue'
import { NcActionButton, NcActions } from '@nextcloud/vue'
import Cancel from 'vue-material-design-icons/Cancel.vue'
import ClockRemove from 'vue-material-design-icons/ClockRemove.vue'
import DotsHorizontal from 'vue-material-design-icons/DotsHorizontal.vue'
import ConfirmActionDialog from '../../dialogs/ConfirmActionDialog.vue'
import CreateStandingConsentModal from '../../modals/CreateStandingConsentModal.vue'

export default {
	name: 'StandingConsentIndex',
	components: {
		CnIndexPage,
		CnStatsBlock,
		CnStatusBadge,
		NcActions,
		NcActionButton,
		DotsHorizontal,
		ClockRemove,
		Cancel,
		CreateStandingConsentModal,
		ConfirmActionDialog,
	},

	data() {
		return {
			isRefreshing: false,
			currentPage: 1,
			pageSize: 20,
			showCreateModal: false,
			revokeTarget: null, // consent awaiting revoke confirmation, or null
			revoking: false,
			entityTypeColorMap: {
				person: 'warning',
				organization: 'primary',
			},

			consentMethodColorMap: {
				[t('docudesk', 'Written')]: 'success',
				[t('docudesk', 'Verbal')]: 'primary',
				[t('docudesk', 'Digital')]: 'default',
				[t('docudesk', 'Implicit')]: 'warning',
			},

			activeColorMap: {
				[t('docudesk', 'Yes')]: 'success',
				[t('docudesk', 'No')]: 'error',
			},

			consentStatusColorMap: {
				[t('docudesk', 'Pending')]: 'default',
				[t('docudesk', 'Approved')]: 'success',
				[t('docudesk', 'Objected')]: 'error',
				[t('docudesk', 'No Response')]: 'warning',
				[t('docudesk', 'Anonymized')]: 'primary',
			},
		}
	},

	computed: {
		/**
		 * Filter the loaded consents to only entity-scope standing consents.
		 *
		 * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-11
		 */
		entityConsents() {
			return consentStore.consents.filter((c) => c.scope === 'entity')
		},

		/**
		 * Count of active entity consents.
		 *
		 * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-11
		 */
		activeCount() {
			return this.entityConsents.filter((c) => c.active === true).length
		},

		/**
		 * Count of inactive entity consents.
		 *
		 * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-11
		 */
		inactiveCount() {
			return this.entityConsents.filter((c) => c.active !== true).length
		},

		/**
		 * Column definitions for the standing consent records table.
		 *
		 * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-11
		 */
		tableColumns() {
			return [
				{
					key: 'entityText',
					label: t('docudesk', 'Entity'),
					sortable: true,
				},
				{ key: 'entityType', label: t('docudesk', 'Type'), sortable: true },
				{
					key: 'consentMethod',
					label: t('docudesk', 'Consent Method'),
					sortable: true,
				},
				{
					key: 'validFrom',
					label: t('docudesk', 'Valid From'),
					sortable: true,
				},
				{
					key: 'validUntil',
					label: t('docudesk', 'Valid Until'),
					sortable: true,
				},
				{ key: 'active', label: t('docudesk', 'Active'), sortable: true },
				{
					key: 'consentStatus',
					label: t('docudesk', 'Status'),
					sortable: true,
				},
			]
		},

		/**
		 * Pagination metadata derived from the loaded entity consent list.
		 *
		 * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-11
		 */
		paginationData() {
			const total = this.entityConsents.length
			const pages = Math.ceil(total / this.pageSize)
			return { page: this.currentPage, pages, total, limit: this.pageSize }
		},

		/**
		 * Empty-state message, surfacing any store error when present.
		 *
		 * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-11
		 */
		emptyContentName() {
			if (consentStore.error) {
				return consentStore.error
			}
			return t('docudesk', 'No standing consent records found')
		},
	},

	mounted() {
		consentStore.fetchConsents()
	},

	methods: {
		/**
		 * Open the create standing consent modal.
		 *
		 * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-11
		 */
		openCreateModal() {
			this.showCreateModal = true
		},

		/**
		 * Close the create standing consent modal.
		 *
		 * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-11
		 */
		closeCreateModal() {
			this.showCreateModal = false
		},

		/**
		 * Persist a new standing consent record returned by the create modal.
		 *
		 * @param payload
		 * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-11
		 */
		async handleCreate(payload) {
			try {
				await consentStore.createConsent(payload)
				this.showCreateModal = false
				await consentStore.fetchConsents()
			} catch (e) {
				// Error is stored on consentStore.error — the empty-state text will show it.
			}
		},

		/**
		 * Set the consent's active flag to false (expire it).
		 *
		 * @param consent
		 * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-11
		 */
		async expireConsent(consent) {
			const id = consent.id || consent.uuid
			await consentStore.updateConsent(id, { ...consent, active: false })
		},

		/**
		 * Ask for confirmation before revoking a standing consent.
		 *
		 * Revoke is destructive — Art. 7(3) consent withdrawal flips
		 * `consentStatus` to `anonymized` and `active` to false; the
		 * record cannot be re-activated. So this only opens
		 * ConfirmActionDialog; executeRevoke() does the write.
		 *
		 * @param consent
		 * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-11
		 */
		revokeConsent(consent) {
			this.revokeTarget = consent
		},

		/**
		 * Dismiss the revoke confirmation without changing anything.
		 *
		 * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-11
		 */
		cancelRevoke() {
			this.revokeTarget = null
		},

		/**
		 * Apply the confirmed revocation.
		 *
		 * Reachable only from ConfirmActionDialog's @confirm, so a single
		 * mis-click in the row-action menu still cannot destroy a standing
		 * consent.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-11
		 */
		async executeRevoke() {
			const consent = this.revokeTarget
			if (!consent) {
				return
			}
			const id = consent.id || consent.uuid
			this.revoking = true
			try {
				await consentStore.updateConsent(id, {
					...consent,
					consentStatus: 'anonymized',
					active: false,
				})
				this.revokeTarget = null
			} finally {
				this.revoking = false
			}
		},

		/**
		 * Reload the consent list from the backend.
		 *
		 * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-11
		 */
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
		 * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-11
		 */
		onPageChanged(page) {
			this.currentPage = page
		},

		/**
		 * Update the page size and reset to the first page.
		 *
		 * @param size
		 * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-11
		 */
		onPageSizeChanged(size) {
			this.pageSize = size
			this.currentPage = 1
		},

		/**
		 * Map a consent method code to a localized label.
		 *
		 * @param method
		 * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-11
		 */
		formatConsentMethod(method) {
			const map = {
				written: t('docudesk', 'Written'),
				verbal: t('docudesk', 'Verbal'),
				digital: t('docudesk', 'Digital'),
				implicit: t('docudesk', 'Implicit'),
			}
			return map[method] || method || t('docudesk', 'Unknown')
		},

		/**
		 * Map a consent status code to a localized label.
		 *
		 * @param status
		 * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-11
		 */
		formatStatus(status) {
			const map = {
				pending: t('docudesk', 'Pending'),
				consent_given: t('docudesk', 'Approved'),
				objection_received: t('docudesk', 'Objected'),
				no_response: t('docudesk', 'No Response'),
				anonymized: t('docudesk', 'Anonymized'),
			}
			return map[status] || status || t('docudesk', 'Unknown')
		},

		/**
		 * Format a date string for display, falling back gracefully.
		 *
		 * @param dateStr
		 * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-11
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
</style>
