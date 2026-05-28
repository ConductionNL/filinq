<script setup>
import { consentStore } from '../../store/store.js'
import AnonymizationWidget from '../anonymization/AnonymizationWidget.vue'
</script>

<template>
	<div class="dashboard-content">
		<h2 class="pageHeader">
			Dashboard
		</h2>

		<div class="dashboard-stats">
			<div class="stat-card">
				<h5>Total Consents</h5>
				<div class="content">
					{{ consentStore.consentStats.total }}
				</div>
			</div>
			<div class="stat-card">
				<h5>Pending</h5>
				<div class="content pending">
					{{ consentStore.consentStats.pending }}
				</div>
			</div>
			<div class="stat-card">
				<h5>Approved</h5>
				<div class="content approved">
					{{ consentStore.consentStats.approved }}
				</div>
			</div>
			<div class="stat-card">
				<h5>Objected</h5>
				<div class="content objected">
					{{ consentStore.consentStats.objected }}
				</div>
			</div>
		</div>

		<div class="dashboard-section">
			<h3>Recent Consent Activity</h3>
			<div v-if="consentStore.loading" class="loading-state">
				<NcLoadingIcon :size="32" />
			</div>
			<div v-else-if="consentStore.consents.length === 0" class="empty-state">
				<p>No consent records yet. Consent records will appear when entities are detected in documents managed by Open Register.</p>
			</div>
			<ul v-else class="recent-list">
				<li v-for="consent in recentConsents" :key="consent.id || consent.uuid" class="recent-item">
					<span class="entity-text">{{ consent.entityText }}</span>
					<span class="badge" :class="'status-' + (consent.consentStatus || 'pending')">
						{{ formatStatus(consent.consentStatus) }}
					</span>
				</li>
			</ul>
		</div>

		<div class="dashboard-section">
			<h3>Quick Anonymization</h3>
			<AnonymizationWidget />
		</div>
	</div>
</template>

<script>
import { NcLoadingIcon } from '@nextcloud/vue'

export default {
	name: 'DashboardIndex',
	components: {
		NcLoadingIcon,
		AnonymizationWidget,
	},
	computed: {
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
				pending: 'Pending',
				consent_given: 'Approved',
				objection_received: 'Objected',
				no_response: 'No Response',
				anonymized: 'Anonymized',
			}
			return map[status] || status || 'Unknown'
		},
	},
}
</script>

<style scoped>
.dashboard-content {
	padding: 20px;
	max-width: 1000px;
}

.dashboard-stats {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
	gap: 16px;
	margin-bottom: 32px;
}

.stat-card {
	padding: 16px;
	border-radius: 8px;
	border: 1px solid var(--color-border);
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

.dashboard-section {
	margin-bottom: 24px;
}

.dashboard-section h3 {
	margin-bottom: 12px;
}

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
