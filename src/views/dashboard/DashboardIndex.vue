<script setup>
import { translate as t } from '@nextcloud/l10n'
import { consentStore } from '../../store/store.js'
</script>

<template>
	<div>
		<!-- Anonymiser backend warning banner — admin-only, sits above the dashboard grid -->
		<AnonymiserBackendWarning
			v-if="isAdmin"
			:showWarning="anonymiserBackend.showWarning"
			:appApiInstalled="anonymiserBackend.appApiInstalled"
			@dismissed="onAnonymiserWarningDismissed" />

		<CnDashboardPage
			:title="t('filinq', 'Dashboard')"
			:widgets="widgetDefs"
			:layout="dashboardLayout"
			:loading="consentStore.loading">
			<!--
				Each KPI tile navigates to the consent overview. That is a
				navigation, so it ships as a real link: <RouterLink> renders an
				<a href>, which is focusable, activates on Enter, and supports
				middle-click / open-in-new-tab. The previous `<div @click>`
				wrapper was unreachable by keyboard entirely (WCAG 2.1.1).
				The explicit aria-label names the link, because its visible text
				lives inside CnStatsBlock.
			-->
			<!-- KPI: Total Consents -->
			<template #widget-total-consents>
				<RouterLink
					class="dashboard-kpi-link"
					:to="{ name: 'Consent' }"
					:aria-label="
						t('filinq', 'Total Consents — open the consent overview')
					">
					<CnStatsBlock
						:title="t('filinq', 'Total Consents')"
						:count="consentStore.consentStats.total"
						:countLabel="t('filinq', 'records')"
						variant="default"
						showZeroCount />
				</RouterLink>
			</template>

			<!-- KPI: Pending -->
			<template #widget-pending>
				<RouterLink
					class="dashboard-kpi-link"
					:to="{ name: 'Consent' }"
					:aria-label="
						t('filinq', 'Pending consents — open the consent overview')
					">
					<CnStatsBlock
						:title="t('filinq', 'Pending')"
						:count="consentStore.consentStats.pending"
						:countLabel="t('filinq', 'pending')"
						variant="warning"
						showZeroCount />
				</RouterLink>
			</template>

			<!-- KPI: Approved -->
			<template #widget-approved>
				<RouterLink
					class="dashboard-kpi-link"
					:to="{ name: 'Consent' }"
					:aria-label="
						t(
							'filinq',
							'Approved consents — open the consent overview',
						)
					">
					<CnStatsBlock
						:title="t('filinq', 'Approved')"
						:count="consentStore.consentStats.approved"
						:countLabel="t('filinq', 'approved')"
						variant="success"
						showZeroCount />
				</RouterLink>
			</template>

			<!-- KPI: Objected -->
			<template #widget-objected>
				<RouterLink
					class="dashboard-kpi-link"
					:to="{ name: 'Consent' }"
					:aria-label="
						t(
							'filinq',
							'Objected consents — open the consent overview',
						)
					">
					<CnStatsBlock
						:title="t('filinq', 'Objected')"
						:count="consentStore.consentStats.objected"
						:countLabel="t('filinq', 'objected')"
						variant="error"
						showZeroCount />
				</RouterLink>
			</template>

			<!-- Pending Consents table -->
			<template #widget-pending-consents>
				<NcEmptyContent
					v-if="!consentStore.loading && pendingConsents.length === 0"
					:name="t('filinq', 'No pending consents')"
					:description="
						t('filinq', 'All consents have been handled.')
					" />
				<CnDataTable
					v-else
					:rows="pendingConsents"
					:columns="consentColumns"
					borderless
					@rowClick="navigateToPendingConsent" />
			</template>

			<!-- Quick Anonymization -->
			<template #widget-anonymization>
				<AnonymizationDashboardWidget :inApp="true" />
			</template>
		</CnDashboardPage>
	</div>
</template>

<script>
import {
	CnDashboardPage,
	CnDataTable,
	CnStatsBlock,
} from '@conduction/nextcloud-vue'
import { NcEmptyContent } from '@nextcloud/vue'
import AnonymiserBackendWarning from '../../components/AnonymiserBackendWarning.vue'
import AnonymizationDashboardWidget from '../widgets/AnonymizationDashboardWidget.vue'

export default {
	name: 'DashboardIndex',
	components: {
		CnDashboardPage,
		CnStatsBlock,
		CnDataTable,
		NcEmptyContent,
		AnonymizationDashboardWidget,
		AnonymiserBackendWarning,
	},

	data() {
		return {
			isAdmin: false,
			anonymiserBackend: {
				method: 'regex',
				appApiInstalled: false,
				warningDismissed: false,
				showWarning: false,
			},

			dashboardLayout: [
				{
					id: 1,
					widgetId: 'total-consents',
					gridX: 0,
					gridY: 0,
					gridWidth: 3,
					gridHeight: 2,
					showTitle: false,
				},
				{
					id: 2,
					widgetId: 'pending',
					gridX: 3,
					gridY: 0,
					gridWidth: 3,
					gridHeight: 2,
					showTitle: false,
				},
				{
					id: 3,
					widgetId: 'approved',
					gridX: 6,
					gridY: 0,
					gridWidth: 3,
					gridHeight: 2,
					showTitle: false,
				},
				{
					id: 4,
					widgetId: 'objected',
					gridX: 9,
					gridY: 0,
					gridWidth: 3,
					gridHeight: 2,
					showTitle: false,
				},
				{
					id: 5,
					widgetId: 'pending-consents',
					gridX: 0,
					gridY: 2,
					gridWidth: 6,
					gridHeight: 5,
				},
				{
					id: 6,
					widgetId: 'anonymization',
					gridX: 6,
					gridY: 2,
					gridWidth: 6,
					gridHeight: 5,
				},
			],
		}
	},

	computed: {
		/**
		 * Widget definitions for CnDashboardPage.
		 *
		 * @spec openspec/specs/dashboard/spec.md#requirement-filinq-dashboard-view-req-dash-01
		 */
		widgetDefs() {
			return [
				{ id: 'total-consents', title: t('filinq', 'Total Consents') },
				{ id: 'pending', title: t('filinq', 'Pending') },
				{ id: 'approved', title: t('filinq', 'Approved') },
				{ id: 'objected', title: t('filinq', 'Objected') },
				{ id: 'pending-consents', title: t('filinq', 'Pending Consents') },
				{ id: 'anonymization', title: t('filinq', 'Quick Anonymization') },
			]
		},

		/**
		 * Column definitions for the Pending Consents CnDataTable.
		 *
		 * @spec openspec/specs/dashboard/spec.md#requirement-filinq-dashboard-view-req-dash-01
		 */
		consentColumns() {
			return [{ key: 'entity', label: t('filinq', 'Entity') }]
		},

		/**
		 * Consent records with status "pending", capped at 10 rows.
		 * The `id` field is retained so row-click can navigate to ConsentDetail.
		 *
		 * @spec openspec/specs/dashboard/spec.md#requirement-filinq-dashboard-view-req-dash-01
		 */
		pendingConsents() {
			return consentStore.consents
				.filter((c) => c.consentStatus === 'pending')
				.slice(0, 10)
				.map((c) => ({ id: c.id || c.uuid, entity: c.entityText || '—' }))
		},
	},

	mounted() {
		consentStore.fetchConsents()
		this.fetchAnonymiserBackendState()
	},

	methods: {
		/**
		 * Fetch anonymiser backend state to decide whether to show the warning banner.
		 *
		 * @spec openspec/changes/anonymiser-backend-warning/tasks.md#task-7
		 */
		async fetchAnonymiserBackendState() {
			try {
				const response = await fetch(
					'/index.php/apps/filinq/api/settings',
					{ method: 'GET' },
				)
				if (response.ok === false) {
					return
				}
				const data = await response.json()
				this.isAdmin = data.isAdmin ?? false
				if (data.anonymiserBackend) {
					this.anonymiserBackend = {
						method: data.anonymiserBackend.method ?? 'regex',
						appApiInstalled:
							data.anonymiserBackend.appApiInstalled ?? false,

						warningDismissed:
							data.anonymiserBackend.warningDismissed ?? false,

						showWarning: data.anonymiserBackend.showWarning ?? false,
					}
				}
			} catch (_err) {
				// Non-critical — dashboard still works without the warning.
			}
		},

		/**
		 * Navigate to ConsentDetail when a pending-consents table row is clicked.
		 *
		 * @param {object} row - The clicked row object (contains id + entity fields).
		 */
		navigateToPendingConsent(row) {
			if (row && row.id) {
				this.$router.push({ name: 'ConsentDetail', params: { id: row.id } })
			} else {
				this.$router.push({ name: 'Consent' })
			}
		},

		/**
		 * Handle the anonymiser backend warning being dismissed on the dashboard.
		 *
		 * @spec openspec/changes/anonymiser-backend-warning/tasks.md#task-8
		 */
		onAnonymiserWarningDismissed() {
			this.anonymiserBackend = {
				...this.anonymiserBackend,
				showWarning: false,
				warningDismissed: true,
			}
		},
	},
}
</script>

<style scoped>
/* The KPI tiles are links, but they must keep the tile look — no underline,
   no link colour — while retaining a visible keyboard focus ring. */
.dashboard-kpi-link {
	display: block;
	height: 100%;
	color: inherit;
	text-decoration: none;
}

.dashboard-kpi-link:focus-visible {
	outline: 2px solid var(--color-primary-element);
	outline-offset: 2px;
	border-radius: var(--border-radius-large);
}
</style>
