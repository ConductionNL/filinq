<script setup>
import { translate as t } from '@nextcloud/l10n'
import { consentStore, navigationStore } from '../../store/store.js'
</script>

<template>
	<div class="consent-index">
		<h2 class="pageHeader">
			{{ t('docudesk', 'Consent Management') }}
		</h2>

		<div class="consent-stats">
			<div class="stat-card">
				<span class="stat-number">{{ consentStore.consentStats.total }}</span>
				<span class="stat-label">{{ t('docudesk', 'Total') }}</span>
			</div>
			<div class="stat-card stat-pending">
				<span class="stat-number">{{ consentStore.consentStats.pending }}</span>
				<span class="stat-label">{{ t('docudesk', 'Pending') }}</span>
			</div>
			<div class="stat-card stat-approved">
				<span class="stat-number">{{ consentStore.consentStats.approved }}</span>
				<span class="stat-label">{{ t('docudesk', 'Approved') }}</span>
			</div>
			<div class="stat-card stat-objected">
				<span class="stat-number">{{ consentStore.consentStats.objected }}</span>
				<span class="stat-label">{{ t('docudesk', 'Objected') }}</span>
			</div>
		</div>

		<div v-if="consentStore.loading" class="loading">
			<NcLoadingIcon :size="64" appearance="dark" :name="t('docudesk', 'Loading consents')" />
		</div>

		<div v-else-if="consentStore.consents.length === 0" class="empty-state">
			<NcEmptyContent :name="t('docudesk', 'No consent records')" :description="t('docudesk', 'No publication consent records found. Consent records are created when entities are detected in documents.')">
				<template #icon>
					<AccountCheck :size="64" />
				</template>
			</NcEmptyContent>
		</div>

		<div v-else class="consent-list">
			<table class="consent-table">
				<thead>
					<tr>
						<th>{{ t('docudesk', 'Entity') }}</th>
						<th>{{ t('docudesk', 'Type') }}</th>
						<th>{{ t('docudesk', 'Consent Status') }}</th>
						<th>{{ t('docudesk', 'Notification') }}</th>
						<th>{{ t('docudesk', 'Deadline') }}</th>
						<th>{{ t('docudesk', 'Decision') }}</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="consent in consentStore.consents"
						:key="consent.id || consent.uuid"
						class="consent-row"
						@click="viewConsent(consent)">
						<td>{{ consent.entityText }}</td>
						<td>
							<span class="badge" :class="'badge-' + (consent.entityType || '').toLowerCase()">
								{{ consent.entityType }}
							</span>
						</td>
						<td>
							<span class="badge" :class="'status-' + (consent.consentStatus || 'pending')">
								{{ formatStatus(consent.consentStatus) }}
							</span>
						</td>
						<td>
							<span class="badge" :class="'notification-' + (consent.notificationStatus || 'pending')">
								{{ formatStatus(consent.notificationStatus) }}
							</span>
						</td>
						<td>{{ formatDate(consent.objectionDeadline) }}</td>
						<td>
							<span class="badge" :class="'decision-' + (consent.publicationDecision || 'pending')">
								{{ formatDecision(consent.publicationDecision) }}
							</span>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</template>

<script>
import { NcLoadingIcon, NcEmptyContent } from '@nextcloud/vue'
import AccountCheck from 'vue-material-design-icons/AccountCheck.vue'

export default {
	name: 'ConsentIndex',
	components: {
		NcLoadingIcon,
		NcEmptyContent,
		AccountCheck,
	},
	mounted() {
		consentStore.fetchConsents()
	},
	methods: {
		viewConsent(consent) {
			consentStore.setConsentItem(consent)
			navigationStore.setSelected('consentDetail')
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
.consent-index {
	padding: 20px;
}

.consent-stats {
	display: flex;
	gap: 16px;
	margin-bottom: 24px;
	flex-wrap: wrap;
}

.stat-card {
	padding: 16px 24px;
	border-radius: 8px;
	border: 1px solid var(--color-border);
	background-color: var(--color-main-background);
	text-align: center;
	min-width: 120px;
}

.stat-number {
	display: block;
	font-size: 2rem;
	font-weight: bold;
	color: var(--color-main-text);
}

.stat-label {
	display: block;
	font-size: 0.85rem;
	color: var(--color-text-maxcontrast);
	margin-top: 4px;
}

.stat-pending { border-left: 4px solid var(--color-warning); }
.stat-approved { border-left: 4px solid var(--color-success); }
.stat-objected { border-left: 4px solid var(--color-error); }

.consent-table {
	width: 100%;
	border-collapse: collapse;
}

.consent-table th {
	text-align: left;
	padding: 12px 8px;
	border-bottom: 2px solid var(--color-border);
	font-weight: bold;
	color: var(--color-main-text);
}

.consent-row {
	cursor: pointer;
}

.consent-row:hover {
	background-color: var(--color-background-hover);
}

.consent-row td {
	padding: 10px 8px;
	border-bottom: 1px solid var(--color-border);
}

.badge {
	display: inline-block;
	padding: 2px 8px;
	border-radius: 12px;
	font-size: 0.8rem;
	font-weight: 500;
}

.badge-person { background-color: var(--color-warning); color: white; }
.badge-organization { background-color: var(--color-primary); color: white; }

.status-pending { background-color: var(--color-background-dark); color: var(--color-main-text); }
.status-consent_given { background-color: var(--color-success); color: white; }
.status-objection_received { background-color: var(--color-error); color: white; }
.status-no_response { background-color: var(--color-warning); color: white; }
.status-anonymized { background-color: var(--color-primary); color: white; }

.notification-pending { background-color: var(--color-background-dark); color: var(--color-main-text); }
.notification-sent { background-color: var(--color-primary); color: white; }
.notification-delivered { background-color: var(--color-success); color: white; }
.notification-failed { background-color: var(--color-error); color: white; }

.decision-pending { background-color: var(--color-background-dark); color: var(--color-main-text); }
.decision-publish_with_consent { background-color: var(--color-success); color: white; }
.decision-publish_anonymized { background-color: var(--color-primary); color: white; }
.decision-anonymize { background-color: var(--color-warning); color: white; }
.decision-reject { background-color: var(--color-error); color: white; }

.loading {
	display: flex;
	justify-content: center;
	padding: 40px;
}

.empty-state {
	padding: 40px;
}
</style>
