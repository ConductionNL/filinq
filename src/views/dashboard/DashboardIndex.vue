<script setup>
import { translate as t } from '@nextcloud/l10n'
import { consentStore } from '../../store/store.js'
import AnonymizationWidget from '../anonymization/AnonymizationWidget.vue'
</script>

<template>
	<CnDashboardPage
		:title="t('docudesk', 'Dashboard')"
		:widgets="widgetDefs"
		:layout="dashboardLayout"
		:loading="consentStore.loading">
		<!-- KPI: Total Consents -->
		<template #widget-total-consents>
			<div class="stat-card">
				<h5>{{ t('docudesk', 'Total Consents') }}</h5>
				<div class="content">
					{{ consentStore.consentStats.total }}
				</div>
			</div>
		</template>

		<!-- KPI: Pending -->
		<template #widget-pending>
			<div class="stat-card">
				<h5>{{ t('docudesk', 'Pending') }}</h5>
				<div class="content pending">
					{{ consentStore.consentStats.pending }}
				</div>
			</div>
		</template>

		<!-- KPI: Approved -->
		<template #widget-approved>
			<div class="stat-card">
				<h5>{{ t('docudesk', 'Approved') }}</h5>
				<div class="content approved">
					{{ consentStore.consentStats.approved }}
				</div>
			</div>
		</template>

		<!-- KPI: Objected -->
		<template #widget-objected>
			<div class="stat-card">
				<h5>{{ t('docudesk', 'Objected') }}</h5>
				<div class="content objected">
					{{ consentStore.consentStats.objected }}
				</div>
			</div>
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
					<span class="badge" :class="'status-' + (consent.consentStatus || 'pending')">
						{{ formatStatus(consent.consentStatus) }}
					</span>
				</li>
			</ul>
		</template>

		<!-- Quick Anonymization -->
		<template #widget-anonymization>
			<AnonymizationWidget />
		</template>
	</CnDashboardPage>
</template>

<script>
import { NcLoadingIcon } from '@nextcloud/vue'
import { CnDashboardPage } from '@conduction/nextcloud-vue'

export default {
	name: 'DashboardIndex',
	components: {
		CnDashboardPage,
		NcLoadingIcon,
		AnonymizationWidget,
	},
	data() {
		return {
			dashboardLayout: [
				{ id: 1, widgetId: 'total-consents', gridX: 0, gridY: 0, gridWidth: 3, showTitle: false },
				{ id: 2, widgetId: 'pending', gridX: 3, gridY: 0, gridWidth: 3, showTitle: false },
				{ id: 3, widgetId: 'approved', gridX: 6, gridY: 0, gridWidth: 3, showTitle: false },
				{ id: 4, widgetId: 'objected', gridX: 9, gridY: 0, gridWidth: 3, showTitle: false },
				{ id: 5, widgetId: 'recent-activity', gridX: 0, gridY: 1, gridWidth: 6 },
				{ id: 6, widgetId: 'anonymization', gridX: 6, gridY: 1, gridWidth: 6 },
			],
		}
	},
	computed: {
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
		recentConsents() {
			return consentStore.consents.slice(0, 10)
		},
	},
	mounted() {
		consentStore.fetchConsents()
	},
	methods: {
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
	},
}
</script>

<style scoped>
.stat-card {
	padding: 16px;
	border-radius: 8px;
	background-color: var(--color-main-background);
}

.stat-card h5 {
	margin: 0 0 8px 0;
	font-weight: normal;
	color: var(--color-text-maxcontrast);
}

.stat-card .content {
	font-size: 2.5rem;
	font-weight: bold;
	text-align: center;
	color: var(--color-main-text);
}

.stat-card .content.pending { color: var(--color-warning); }
.stat-card .content.approved { color: var(--color-success); }
.stat-card .content.objected { color: var(--color-error); }

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

.badge {
	display: inline-block;
	padding: 2px 8px;
	border-radius: 12px;
	font-size: 0.8rem;
	font-weight: 500;
}

.status-pending { background-color: var(--color-background-dark); color: var(--color-main-text); }
.status-consent_given { background-color: var(--color-success); color: white; }
.status-objection_received { background-color: var(--color-error); color: white; }
.status-no_response { background-color: var(--color-warning); color: white; }
.status-anonymized { background-color: var(--color-primary); color: white; }

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
