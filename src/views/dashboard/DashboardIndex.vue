<script setup>
import { translate as t } from '@nextcloud/l10n'
import { consentStore } from '../../store/store.js'
import AnonymizationWidget from '../anonymization/AnonymizationWidget.vue'
</script>

<template>
	<div>
		<!-- Anonymiser backend warning banner — admin-only, sits above the dashboard grid -->
		<AnonymiserBackendWarning
			v-if="isAdmin"
			:show-warning="anonymiserBackend.showWarning"
			:app-api-installed="anonymiserBackend.appApiInstalled"
			@dismissed="onAnonymiserWarningDismissed" />

	<CnDashboardPage
		:title="t('docudesk', 'Dashboard')"
		:widgets="widgetDefs"
		:layout="dashboardLayout"
		:loading="consentStore.loading">
		<!-- KPI: Total Consents -->
		<template #widget-total-consents>
			<CnStatsBlock
				:title="t('docudesk', 'Total Consents')"
				:count="consentStore.consentStats.total"
				:count-label="t('docudesk', 'records')"
				variant="default"
				show-zero-count />
		</template>

		<!-- KPI: Pending -->
		<template #widget-pending>
			<CnStatsBlock
				:title="t('docudesk', 'Pending')"
				:count="consentStore.consentStats.pending"
				:count-label="t('docudesk', 'pending')"
				variant="warning"
				show-zero-count />
		</template>

		<!-- KPI: Approved -->
		<template #widget-approved>
			<CnStatsBlock
				:title="t('docudesk', 'Approved')"
				:count="consentStore.consentStats.approved"
				:count-label="t('docudesk', 'approved')"
				variant="success"
				show-zero-count />
		</template>

		<!-- KPI: Objected -->
		<template #widget-objected>
			<CnStatsBlock
				:title="t('docudesk', 'Objected')"
				:count="consentStore.consentStats.objected"
				:count-label="t('docudesk', 'objected')"
				variant="error"
				show-zero-count />
		</template>

		<!-- Recent Consent Activity -->
		<template #widget-recent-activity>
			<div v-if="consentStore.loading" class="loading-state">
				<NcLoadingIcon :size="32" />
			</div>
			<div v-else-if="consentStore.consents.length === 0" class="empty-state">
				<p>{{ t('docudesk', 'No consent records yet. Consent records will appear when entities are detected in documents managed by Open Register.') }}</p>
			</div>
			<ul v-else class="recent-list">
				<li v-for="consent in recentConsents" :key="consent.id || consent.uuid" class="recent-item">
					<span class="entity-text">{{ consent.entityText }}</span>
					<CnStatusBadge
						:label="formatStatus(consent.consentStatus)"
						:color-map="consentStatusColorMap" />
				</li>
			</ul>
		</template>

		<!-- Quick Anonymization -->
		<template #widget-anonymization>
			<AnonymizationWidget />
		</template>
	</CnDashboardPage>
	</div>
</template>

<script>
import { NcLoadingIcon } from '@nextcloud/vue'
import { CnDashboardPage, CnStatsBlock, CnStatusBadge } from '@conduction/nextcloud-vue'
import AnonymiserBackendWarning from '../../components/AnonymiserBackendWarning.vue'

export default {
	name: 'DashboardIndex',
	components: {
		CnDashboardPage,
		CnStatsBlock,
		CnStatusBadge,
		NcLoadingIcon,
		AnonymizationWidget,
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
				{ id: 1, widgetId: 'total-consents', gridX: 0, gridY: 0, gridWidth: 3, showTitle: false },
				{ id: 2, widgetId: 'pending', gridX: 3, gridY: 0, gridWidth: 3, showTitle: false },
				{ id: 3, widgetId: 'approved', gridX: 6, gridY: 0, gridWidth: 3, showTitle: false },
				{ id: 4, widgetId: 'objected', gridX: 9, gridY: 0, gridWidth: 3, showTitle: false },
				{ id: 5, widgetId: 'recent-activity', gridX: 0, gridY: 1, gridWidth: 6 },
				{ id: 6, widgetId: 'anonymization', gridX: 6, gridY: 1, gridWidth: 6 },
			],
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
		 * Definitions of the dashboard statistic/activity widgets.
		 *
		 * @spec openspec/specs/dashboard/spec.md#requirement-docudesk-dashboard-view-req-dash-01
		 */
		widgetDefs() {
			return [
				{ id: 'total-consents', title: t('docudesk', 'Total Consents') },
				{ id: 'pending', title: t('docudesk', 'Pending') },
				{ id: 'approved', title: t('docudesk', 'Approved') },
				{ id: 'objected', title: t('docudesk', 'Objected') },
				{ id: 'recent-activity', title: t('docudesk', 'Recent Consent Activity') },
				{ id: 'anonymization', title: t('docudesk', 'Quick Anonymization') },
			]
		},
		/**
		 * The ten most recent consent records for the activity panel.
		 *
		 * @spec openspec/specs/dashboard/spec.md#requirement-docudesk-dashboard-view-req-dash-01
		 */
		recentConsents() {
			return consentStore.consents.slice(0, 10)
		},
	},
	mounted() {
		consentStore.fetchConsents()
		this.fetchAnonymiserBackendState()
	},
	methods: {
		/**
		 * Map a consent status code to a localized label for the dashboard.
		 *
		 * @spec openspec/specs/dashboard/spec.md#requirement-docudesk-dashboard-view-req-dash-01
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
		 * Fetch anonymiser backend state to decide whether to show the warning banner.
		 * Admin flag and backend state come from the settings API response.
		 *
		 * @spec openspec/changes/anonymiser-backend-warning/tasks.md#task-7
		 */
		async fetchAnonymiserBackendState() {
			try {
				const response = await fetch('/index.php/apps/docudesk/api/settings', { method: 'GET' })
				if (response.ok === false) {
					return
				}
				const data = await response.json()
				this.isAdmin = data.isAdmin ?? false
				if (data.anonymiserBackend) {
					this.anonymiserBackend = {
						method: data.anonymiserBackend.method ?? 'regex',
						appApiInstalled: data.anonymiserBackend.appApiInstalled ?? false,
						warningDismissed: data.anonymiserBackend.warningDismissed ?? false,
						showWarning: data.anonymiserBackend.showWarning ?? false,
					}
				}
			} catch (_err) {
				// Non-critical — dashboard still works without the warning.
			}
		},

		/**
		 * Handle the anonymiser backend warning being dismissed on the dashboard.
		 *
		 * @spec openspec/changes/anonymiser-backend-warning/tasks.md#task-8
		 */
		onAnonymiserWarningDismissed() {
			this.anonymiserBackend = { ...this.anonymiserBackend, showWarning: false, warningDismissed: true }
		},
	},
}
</script>

<style scoped>
.recent-list {
	list-style: none;
	padding: 0;
	margin: 0;
}

.recent-item {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 10px 12px;
	border-bottom: 1px solid var(--color-border);
}

.recent-item:last-child {
	border-bottom: none;
}

.entity-text {
	font-weight: 500;
}

.loading-state {
	display: flex;
	justify-content: center;
	padding: 20px;
}

.empty-state {
	padding: 20px;
	color: var(--color-text-maxcontrast);
	text-align: center;
}
</style>
