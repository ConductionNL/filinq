<script setup>
import { translate as t } from '@nextcloud/l10n'
import { consentStore } from '../../store/store.js'
</script>

<template>
	<CnDetailPage
		:title="consentStore.consentItem?.entityText || t('docudesk', 'Consent Detail')"
		:loading="consentStore.loading"
		:loading-label="t('docudesk', 'Loading consent record...')"
		:error="!consentStore.consentItem"
		:error-message="t('docudesk', 'No consent record selected.')"
		:stats-title="consentStore.consentItem ? t('docudesk', 'Entity Information') : ''"
		:stats-columns="consentStore.consentItem ? [
			{ key: 'field', label: t('docudesk', 'Field') },
			{ key: 'value', label: t('docudesk', 'Value') },
		] : []">
		<!-- Back button in header -->
		<template #header-actions>
			<NcButton type="tertiary" @click="goBack">
				<template #icon>
					<ArrowLeft :size="20" />
				</template>
				{{ t('docudesk', 'Back to Consents') }}
			</NcButton>
		</template>

		<!-- Error actions -->
		<template #error-actions>
			<NcButton @click="goBack">
				{{ t('docudesk', 'Back to Consents') }}
			</NcButton>
		</template>

		<!-- Entity info stats rows -->
		<template v-if="consentStore.consentItem" #stats-rows>
			<tr>
				<td>{{ t('docudesk', 'Entity Text') }}</td>
				<td>{{ consentStore.consentItem.entityText }}</td>
			</tr>
			<tr>
				<td>{{ t('docudesk', 'Entity Type') }}</td>
				<td>
					<CnStatusBadge
						:label="consentStore.consentItem.entityType || t('docudesk', 'Unknown')"
						:color-map="entityTypeColorMap" />
				</td>
			</tr>
			<tr v-if="consentStore.consentItem.entityKey">
				<td>{{ t('docudesk', 'Entity Key') }}</td>
				<td>{{ consentStore.consentItem.entityKey }}</td>
			</tr>
			<tr v-if="consentStore.consentItem.contactEmail">
				<td>{{ t('docudesk', 'Contact Email') }}</td>
				<td>{{ consentStore.consentItem.contactEmail }}</td>
			</tr>
			<tr v-if="consentStore.consentItem.contactAddress">
				<td>{{ t('docudesk', 'Contact Address') }}</td>
				<td>{{ consentStore.consentItem.contactAddress }}</td>
			</tr>
		</template>

		<!-- Policy-driven anonymisation toggle (§6.1, §6.2) -->
		<div v-if="consentStore.consentItem" class="detail-section">
			<h3>{{ t('docudesk', 'Anonymisation') }}</h3>
			<div class="anonymisation-toggle">
				<NcCheckboxRadioSwitch
					v-model="anonymiseToggle"
					type="switch"
					:disabled="toggleLocked"
					@update:checked="onToggleAnonymise">
					{{ t('docudesk', 'Anonymise this entity in the published document') }}
				</NcCheckboxRadioSwitch>
				<p v-if="policyMatchKind === 'prohibition'" class="toggle-note toggle-note-locked">
					{{ t('docudesk', 'This entity is on the publication prohibition list. The decision is locked.') }}
				</p>
				<p v-else-if="policyMatchKind === 'standing_consent'" class="toggle-note">
					{{ t('docudesk', 'A standing publication consent applies. You may override to anonymise anyway; the override is audit-logged.') }}
				</p>
				<p v-else-if="consentStore.consentItem.policyMatch" class="toggle-note">
					{{ t('docudesk', 'Pre-empted by policy match {ref}.', { ref: consentStore.consentItem.policyMatch }) }}
				</p>
			</div>
		</div>

		<!-- Consent status section -->
		<div v-if="consentStore.consentItem" class="detail-section">
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

		<!-- Objection reason -->
		<div v-if="consentStore.consentItem?.objectionReason" class="detail-section">
			<h3>{{ t('docudesk', 'Objection Reason') }}</h3>
			<p class="notes-text">
				{{ consentStore.consentItem.objectionReason }}
			</p>
		</div>

		<!-- Notes -->
		<div v-if="consentStore.consentItem?.notes" class="detail-section">
			<h3>{{ t('docudesk', 'Notes') }}</h3>
			<p class="notes-text">
				{{ consentStore.consentItem.notes }}
			</p>
		</div>

		<!-- Save button -->
		<div v-if="consentStore.consentItem" class="detail-actions">
			<NcButton type="primary" :disabled="consentStore.loading" @click="saveChanges">
				<template #icon>
					<NcLoadingIcon v-if="consentStore.loading" :size="20" />
					<ContentSave v-else :size="20" />
				</template>
				{{ t('docudesk', 'Save Changes') }}
			</NcButton>
		</div>
	</CnDetailPage>
</template>

<script>
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { NcButton, NcCheckboxRadioSwitch, NcSelect, NcLoadingIcon } from '@nextcloud/vue'
import { CnDetailPage, CnStatusBadge } from '@conduction/nextcloud-vue'
import ArrowLeft from 'vue-material-design-icons/ArrowLeft.vue'
import ContentSave from 'vue-material-design-icons/ContentSave.vue'
import { showSuccess, showError } from '@nextcloud/dialogs'

export default {
	name: 'ConsentDetail',
	components: {
		NcButton,
		NcCheckboxRadioSwitch,
		NcSelect,
		NcLoadingIcon,
		CnDetailPage,
		CnStatusBadge,
		ArrowLeft,
		ContentSave,
	},
	props: {
		consentId: {
			type: String,
			default: '',
		},
	},
	data() {
		return {
			editData: {
				consentStatus: null,
				notificationStatus: null,
				publicationDecision: null,
			},
			anonymiseToggle: false,
			policyMatchKind: null,
			entityTypeColorMap: {
				person: 'warning',
				organization: 'primary',
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
	computed: {
		toggleLocked() {
			return this.policyMatchKind === 'prohibition'
		},
	},
	watch: {
		'consentStore.consentItem': {
			immediate: true,
			/**
			 * Sync the editable form fields when the selected consent record changes.
			 *
			 * @param item
			 * @spec openspec/specs/consent-management/spec.md#requirement-consent-ui-req-cons-10
			 */
			handler(item) {
				if (item) {
					this.editData.consentStatus = this.consentStatusOptions.find(o => o.value === item.consentStatus) || null
					this.editData.notificationStatus = this.notificationStatusOptions.find(o => o.value === item.notificationStatus) || null
					this.editData.publicationDecision = this.publicationDecisionOptions.find(o => o.value === item.publicationDecision) || null
					this.refreshPolicyMatch()
				}
			},
		},
	},
	created() {
		if (this.consentId && !consentStore.consentItem) {
			consentStore.fetchConsent(this.consentId)
		}
	},
	methods: {
		/**
		 * Clear the selected consent and return to the consent list.
		 *
		 * @spec openspec/specs/consent-management/spec.md#requirement-consent-ui-req-cons-10
		 */
		goBack() {
			consentStore.clearConsentItem()
			this.$router.push({ name: 'Consent' })
		},
		/**
		 * Resolve the policyMatch UUID into a kind for toggle behaviour.
		 *
		 * Toggle rules per spec §UI:
		 *   - referent is a prohibition  → ON + locked
		 *   - referent is a standing consent (scope=entity) → OFF + interactive
		 *   - no policyMatch → driven by consentStatus (legacy UX)
		 */
		async refreshPolicyMatch() {
			const item = consentStore.consentItem
			this.policyMatchKind = null
			if (!item?.policyMatch) {
				this.anonymiseToggle = (item?.publicationDecision === 'anonymize')
				return
			}

			try {
				await axios.get(
					generateUrl(`/apps/docudesk/api/policy/prohibitions/${item.policyMatch}`),
				)
				this.policyMatchKind = 'prohibition'
				this.anonymiseToggle = true
				return
			} catch (err) {
				// 404 / other → falls through to standing-consent probe.
			}

			try {
				await axios.get(
					generateUrl(`/apps/docudesk/api/policy/standing-consents/${item.policyMatch}`),
				)
				this.policyMatchKind = 'standing_consent'
				// Default OFF for standing consent; user may override.
				this.anonymiseToggle = (item.publicationDecision === 'anonymize')
				return
			} catch (err) {
				// Dangling reference — fall through to legacy.
			}

			this.anonymiseToggle = (item?.publicationDecision === 'anonymize')
		},
		/**
		 * Handle toggle clicks. For standing-consent matches, flipping ON
		 * records an override: publicationDecision=anonymize while consentStatus
		 * stays consent_given and policyMatch is preserved. The audit trail
		 * comes from OpenRegister's mapper-level history.
		 * @param checked
		 */
		async onToggleAnonymise(checked) {
			if (this.policyMatchKind === 'prohibition') {
				// Should be impossible due to disabled; defensive.
				this.anonymiseToggle = true
				return
			}

			const id = consentStore.consentItem?.['@self']?.id || consentStore.consentItem?.id || consentStore.consentItem?.uuid
			if (!id) return

			const update = {
				publicationDecision: checked ? 'anonymize' : 'publish_with_consent',
			}
			try {
				await consentStore.updateConsent(id, update)
				showSuccess(t('docudesk', 'Anonymisation decision updated'))
			} catch (err) {
				showError(t('docudesk', 'Failed to update anonymisation decision'))
				this.anonymiseToggle = !checked
			}
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
				return new Date(dateStr).toLocaleString()
			} catch (e) {
				return dateStr
			}
		},
		/**
		 * Persist edited consent status/decision fields for the record.
		 *
		 * @spec openspec/specs/consent-management/spec.md#requirement-consent-status-lifecycle-req-cons-02
		 */
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

.anonymisation-toggle {
	display: flex;
	flex-direction: column;
	gap: 8px;
}

.toggle-note {
	margin: 0;
	font-size: 13px;
	color: var(--color-text-maxcontrast);
}

.toggle-note-locked {
	font-weight: 600;
	color: var(--color-error);
}

.detail-actions {
	display: flex;
	justify-content: flex-end;
	gap: 8px;
	margin-top: 16px;
}
</style>
