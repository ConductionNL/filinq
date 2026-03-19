<script setup>
import { translate as t } from '@nextcloud/l10n'
import { consentStore, navigationStore } from '../../store/store.js'
</script>

<template>
	<div class="consent-detail">
		<div class="detail-header">
			<NcButton type="tertiary" @click="goBack">
				<template #icon>
					<ArrowLeft :size="20" />
				</template>
				{{ t('docudesk', 'Back to Consents') }}
			</NcButton>
			<h2>{{ t('docudesk', 'Consent Detail') }}</h2>
		</div>

		<div v-if="!consentStore.consentItem" class="empty-state">
			<p>{{ t('docudesk', 'No consent record selected.') }}</p>
		</div>

		<div v-else class="detail-content">
			<div class="detail-section">
				<h3>{{ t('docudesk', 'Entity Information') }}</h3>
				<table class="detail-table">
					<tr>
						<td class="label">
							{{ t('docudesk', 'Entity Text') }}
						</td>
						<td>{{ consentStore.consentItem.entityText }}</td>
					</tr>
					<tr>
						<td class="label">
							{{ t('docudesk', 'Entity Type') }}
						</td>
						<td>
							<span class="badge" :class="'badge-' + (consentStore.consentItem.entityType || '').toLowerCase()">
								{{ consentStore.consentItem.entityType }}
							</span>
						</td>
					</tr>
					<tr v-if="consentStore.consentItem.entityKey">
						<td class="label">
							{{ t('docudesk', 'Entity Key') }}
						</td>
						<td>{{ consentStore.consentItem.entityKey }}</td>
					</tr>
					<tr v-if="consentStore.consentItem.contactEmail">
						<td class="label">
							{{ t('docudesk', 'Contact Email') }}
						</td>
						<td>{{ consentStore.consentItem.contactEmail }}</td>
					</tr>
					<tr v-if="consentStore.consentItem.contactAddress">
						<td class="label">
							{{ t('docudesk', 'Contact Address') }}
						</td>
						<td>{{ consentStore.consentItem.contactAddress }}</td>
					</tr>
				</table>
			</div>

			<div class="detail-section">
				<h3>{{ t('docudesk', 'Consent Status') }}</h3>
				<table class="detail-table">
					<tr>
						<td class="label">
							{{ t('docudesk', 'Consent Status') }}
						</td>
						<td>
							<NcSelect
								v-model="editData.consentStatus"
								:options="consentStatusOptions"
								:input-label="t('docudesk', 'Consent Status')" />
						</td>
					</tr>
					<tr>
						<td class="label">
							{{ t('docudesk', 'Notification Status') }}
						</td>
						<td>
							<NcSelect
								v-model="editData.notificationStatus"
								:options="notificationStatusOptions"
								:input-label="t('docudesk', 'Notification Status')" />
						</td>
					</tr>
					<tr>
						<td class="label">
							{{ t('docudesk', 'Publication Decision') }}
						</td>
						<td>
							<NcSelect
								v-model="editData.publicationDecision"
								:options="publicationDecisionOptions"
								:input-label="t('docudesk', 'Publication Decision')" />
						</td>
					</tr>
					<tr>
						<td class="label">
							{{ t('docudesk', 'Objection Deadline') }}
						</td>
						<td>{{ formatDate(consentStore.consentItem.objectionDeadline) }}</td>
					</tr>
					<tr v-if="consentStore.consentItem.objectionReceivedAt">
						<td class="label">
							{{ t('docudesk', 'Objection Received') }}
						</td>
						<td>{{ formatDate(consentStore.consentItem.objectionReceivedAt) }}</td>
					</tr>
					<tr v-if="consentStore.consentItem.legalBasis">
						<td class="label">
							{{ t('docudesk', 'Legal Basis') }}
						</td>
						<td>{{ consentStore.consentItem.legalBasis }}</td>
					</tr>
				</table>
			</div>

			<div v-if="consentStore.consentItem.objectionReason" class="detail-section">
				<h3>{{ t('docudesk', 'Objection Reason') }}</h3>
				<p class="notes-text">
					{{ consentStore.consentItem.objectionReason }}
				</p>
			</div>

			<div v-if="consentStore.consentItem.notes" class="detail-section">
				<h3>{{ t('docudesk', 'Notes') }}</h3>
				<p class="notes-text">
					{{ consentStore.consentItem.notes }}
				</p>
			</div>

			<div class="detail-actions">
				<NcButton type="primary" :disabled="consentStore.loading" @click="saveChanges">
					<template #icon>
						<NcLoadingIcon v-if="consentStore.loading" :size="20" />
						<ContentSave v-else :size="20" />
					</template>
					{{ t('docudesk', 'Save Changes') }}
				</NcButton>
			</div>
		</div>
	</div>
</template>

<script>
import { NcButton, NcSelect, NcLoadingIcon } from '@nextcloud/vue'
import ArrowLeft from 'vue-material-design-icons/ArrowLeft.vue'
import ContentSave from 'vue-material-design-icons/ContentSave.vue'
import { showSuccess, showError } from '@nextcloud/dialogs'

export default {
	name: 'ConsentDetail',
	components: {
		NcButton,
		NcSelect,
		NcLoadingIcon,
		ArrowLeft,
		ContentSave,
	},
	data() {
		return {
			editData: {
				consentStatus: null,
				notificationStatus: null,
				publicationDecision: null,
			},
			consentStatusOptions: [
				{ label: t('docudesk', 'Pending'), value: 'pending' },
				{ label: t('docudesk', 'Consent Given'), value: 'consent_given' },
				{ label: t('docudesk', 'Objection Received'), value: 'objection_received' },
				{ label: t('docudesk', 'No Response'), value: 'no_response' },
				{ label: t('docudesk', 'Anonymized'), value: 'anonymized' },
			],
			notificationStatusOptions: [
				{ label: t('docudesk', 'Pending'), value: 'pending' },
				{ label: t('docudesk', 'Sent'), value: 'sent' },
				{ label: t('docudesk', 'Delivered'), value: 'delivered' },
				{ label: t('docudesk', 'Failed'), value: 'failed' },
				{ label: t('docudesk', 'Skipped'), value: 'skipped' },
			],
			publicationDecisionOptions: [
				{ label: t('docudesk', 'Pending'), value: 'pending' },
				{ label: t('docudesk', 'Anonymize'), value: 'anonymize' },
				{ label: t('docudesk', 'Publish with Consent'), value: 'publish_with_consent' },
				{ label: t('docudesk', 'Publish Anonymized'), value: 'publish_anonymized' },
				{ label: t('docudesk', 'Reject'), value: 'reject' },
			],
		}
	},
	watch: {
		'consentStore.consentItem': {
			immediate: true,
			handler(item) {
				if (item) {
					this.editData.consentStatus = this.consentStatusOptions.find(o => o.value === item.consentStatus) || null
					this.editData.notificationStatus = this.notificationStatusOptions.find(o => o.value === item.notificationStatus) || null
					this.editData.publicationDecision = this.publicationDecisionOptions.find(o => o.value === item.publicationDecision) || null
				}
			},
		},
	},
	methods: {
		goBack() {
			consentStore.clearConsentItem()
			navigationStore.setSelected('consent')
		},
		formatDate(dateStr) {
			if (!dateStr) return '-'
			try {
				return new Date(dateStr).toLocaleString()
			} catch (e) {
				return dateStr
			}
		},
		async saveChanges() {
			const id = consentStore.consentItem?.id || consentStore.consentItem?.uuid
			if (!id) return

			const updateData = {}
			if (this.editData.consentStatus?.value) {
				updateData.consentStatus = this.editData.consentStatus.value
			}
			if (this.editData.notificationStatus?.value) {
				updateData.notificationStatus = this.editData.notificationStatus.value
			}
			if (this.editData.publicationDecision?.value) {
				updateData.publicationDecision = this.editData.publicationDecision.value
			}

			const result = await consentStore.updateConsent(id, updateData)
			if (result) {
				showSuccess(t('docudesk', 'Consent record updated successfully'))
			} else {
				showError(t('docudesk', 'Failed to update consent record'))
			}
		},
	},
}
</script>

<style scoped>
.consent-detail {
	padding: 20px;
}

.detail-header {
	display: flex;
	align-items: center;
	gap: 16px;
	margin-bottom: 24px;
}

.detail-header h2 {
	margin: 0;
}

.detail-section {
	margin-bottom: 24px;
	padding: 16px;
	border: 1px solid var(--color-border);
	border-radius: 8px;
	background-color: var(--color-main-background);
}

.detail-section h3 {
	margin-top: 0;
	margin-bottom: 12px;
	color: var(--color-main-text);
}

.detail-table {
	width: 100%;
}

.detail-table td {
	padding: 8px 4px;
	vertical-align: middle;
}

.detail-table .label {
	font-weight: bold;
	color: var(--color-text-maxcontrast);
	width: 200px;
}

.notes-text {
	white-space: pre-wrap;
	color: var(--color-main-text);
}

.detail-actions {
	display: flex;
	justify-content: flex-end;
	gap: 8px;
	margin-top: 16px;
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
</style>
