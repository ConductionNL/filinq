<script setup>
import { translate as t } from '@nextcloud/l10n'
import { consentStore } from '../../store/store.js'
</script>

<template>
	<CnDetailPage
		:title="
			consentStore.consentItem?.entityText || t('filinq', 'Consent Detail')
		"
		:loading="consentStore.loading"
		:loadingLabel="t('filinq', 'Loading consent record...')"
		:error="!consentStore.consentItem"
		:errorMessage="t('filinq', 'No consent record selected.')">
		<!--
			ENTITY INFORMATION IS A DEFAULT-SLOT SECTION, NOT A STATS TABLE, AND
			THAT IS LOAD-BEARING.

			CnDetailPage renders the stats table and the default slot as
			`v-if="hasStats"` / `v-else` — they are mutually exclusive:

			    <div v-if="hasStats" class="cn-detail-page__stats"> … </div>
			    <div v-else class="cn-detail-page__content"><slot /></div>

			    hasStats() { return this.statsColumns.length > 0
			        && (this.statsRows.length > 0 || !!this.$slots['stats-rows']) }

			This page used to pass BOTH `statsColumns` + `#stats-rows` AND put
			the anonymisation toggle, the consent-status form and the Save
			Changes button in the default slot. `statsColumns` and `#stats-rows`
			are non-empty exactly when `consentItem` is set — which is exactly
			when the form is supposed to exist — so the stats branch always won
			and the entire operator UI below it rendered nothing at all. Vue
			reports no error for a slot that is never evaluated, so the page
			looked complete: it simply stopped after the Entity Information
			table.

			Rendering these rows as an ordinary section keeps `hasStats` false,
			which lets the default slot through. The visible information and its
			order are unchanged.
		-->
		<!-- Back button in header -->
		<template #header-actions>
			<NcButton variant="tertiary" @click="goBack">
				<template #icon>
					<ArrowLeft :size="20" />
				</template>
				{{ t('filinq', 'Back to Consents') }}
			</NcButton>
		</template>

		<!-- Error actions -->
		<template #error-actions>
			<NcButton @click="goBack">
				{{ t('filinq', 'Back to Consents') }}
			</NcButton>
		</template>

		<!-- Entity information -->
		<div v-if="consentStore.consentItem" class="detail-section">
			<h3>{{ t('filinq', 'Entity Information') }}</h3>
			<!--
				A name/value grid: every row's first cell IS the name of that
				row, so it is a row header. `<th scope="row">` states what the
				markup already means (WCAG 1.3.1), matching the Consent Status
				table below.
			-->
			<table class="detail-table">
				<tr>
					<th scope="row" class="label">
						{{ t('filinq', 'Entity Text') }}
					</th>
					<td>{{ consentStore.consentItem.entityText }}</td>
				</tr>
				<tr>
					<th scope="row" class="label">
						{{ t('filinq', 'Entity Type') }}
					</th>
					<td>
						<CnStatusBadge
							:label="
								consentStore.consentItem.entityType
								|| t('filinq', 'Unknown')
							"
							:colorMap="entityTypeColorMap" />
					</td>
				</tr>
				<tr v-if="consentStore.consentItem.entityKey">
					<th scope="row" class="label">
						{{ t('filinq', 'Entity Key') }}
					</th>
					<td>{{ consentStore.consentItem.entityKey }}</td>
				</tr>
				<tr v-if="consentStore.consentItem.contactEmail">
					<th scope="row" class="label">
						{{ t('filinq', 'Contact Email') }}
					</th>
					<td>{{ consentStore.consentItem.contactEmail }}</td>
				</tr>
				<tr v-if="consentStore.consentItem.contactAddress">
					<th scope="row" class="label">
						{{ t('filinq', 'Contact Address') }}
					</th>
					<td>{{ consentStore.consentItem.contactAddress }}</td>
				</tr>
			</table>
		</div>

		<!-- Policy-driven anonymisation toggle (§6.1, §6.2) -->
		<div v-if="consentStore.consentItem" class="detail-section">
			<h3>{{ t('filinq', 'Anonymisation') }}</h3>
			<div class="anonymisation-toggle">
				<NcCheckboxRadioSwitch
					v-model="anonymiseToggle"
					type="switch"
					:disabled="toggleLocked"
					@update:modelValue="onToggleAnonymise">
					{{
						t(
							'filinq',
							'Anonymise this entity in the published document',
						)
					}}
				</NcCheckboxRadioSwitch>
				<p
					v-if="policyMatchKind === 'prohibition'"
					class="toggle-note toggle-note-locked">
					{{
						t(
							'filinq',
							'This entity is on the publication prohibition list. The decision is locked.',
						)
					}}
				</p>
				<p
					v-else-if="policyMatchKind === 'standing_consent'"
					class="toggle-note">
					{{
						t(
							'filinq',
							'A standing publication consent applies. You may override to anonymise anyway; the override is audit-logged.',
						)
					}}
				</p>
				<p
					v-else-if="consentStore.consentItem.policyMatch"
					class="toggle-note">
					{{
						t('filinq', 'Pre-empted by policy match {ref}.', {
							ref: consentStore.consentItem.policyMatch,
						})
					}}
				</p>
			</div>
		</div>

		<!-- Consent status section -->
		<div v-if="consentStore.consentItem" class="detail-section">
			<h3>{{ t('filinq', 'Consent Status') }}</h3>
			<!--
				This is a name/value grid: every row's first cell IS the name of
				that row, so it is a row header, not data. It shipped as a table
				of nothing but <td>, which left the values with no header to be
				announced against at all (WCAG 1.3.1). <th scope="row"> states
				what was already true of the markup.
			-->
			<table class="detail-table">
				<tr>
					<th scope="row" class="label">
						{{ t('filinq', 'Consent Status') }}
					</th>
					<td>
						<NcSelect
							v-model="editData.consentStatus"
							:options="consentStatusOptions"
							:inputLabel="t('filinq', 'Consent Status')" />
					</td>
				</tr>
				<tr>
					<th scope="row" class="label">
						{{ t('filinq', 'Notification Status') }}
					</th>
					<td>
						<NcSelect
							v-model="editData.notificationStatus"
							:options="notificationStatusOptions"
							:inputLabel="t('filinq', 'Notification Status')" />
					</td>
				</tr>
				<tr>
					<th scope="row" class="label">
						{{ t('filinq', 'Publication Decision') }}
					</th>
					<td>
						<NcSelect
							v-model="editData.publicationDecision"
							:options="publicationDecisionOptions"
							:inputLabel="t('filinq', 'Publication Decision')" />
					</td>
				</tr>
				<tr>
					<th scope="row" class="label">
						{{ t('filinq', 'Objection Deadline') }}
					</th>
					<td>
						{{ formatDate(consentStore.consentItem.objectionDeadline) }}
					</td>
				</tr>
				<tr v-if="consentStore.consentItem.objectionReceivedAt">
					<th scope="row" class="label">
						{{ t('filinq', 'Objection Received') }}
					</th>
					<td>
						{{
							formatDate(consentStore.consentItem.objectionReceivedAt)
						}}
					</td>
				</tr>
				<tr v-if="consentStore.consentItem.legalBasis">
					<th scope="row" class="label">
						{{ t('filinq', 'Legal Basis') }}
					</th>
					<td>{{ consentStore.consentItem.legalBasis }}</td>
				</tr>
			</table>
		</div>

		<!-- Objection reason -->
		<div v-if="consentStore.consentItem?.objectionReason" class="detail-section">
			<h3>{{ t('filinq', 'Objection Reason') }}</h3>
			<p class="notes-text">
				{{ consentStore.consentItem.objectionReason }}
			</p>
		</div>

		<!-- Notes -->
		<div v-if="consentStore.consentItem?.notes" class="detail-section">
			<h3>{{ t('filinq', 'Notes') }}</h3>
			<p class="notes-text">
				{{ consentStore.consentItem.notes }}
			</p>
		</div>

		<!-- Save button -->
		<div v-if="consentStore.consentItem" class="detail-actions">
			<NcButton
				variant="primary"
				:disabled="consentStore.loading"
				@click="saveChanges">
				<template #icon>
					<NcLoadingIcon v-if="consentStore.loading" :size="20" />
					<ContentSave v-else :size="20" />
				</template>
				{{ t('filinq', 'Save Changes') }}
			</NcButton>
		</div>
	</CnDetailPage>
</template>

<script>
import { CnDetailPage, CnStatusBadge } from '@conduction/nextcloud-vue'
import axios from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { generateUrl } from '@nextcloud/router'
import {
	NcButton,
	NcCheckboxRadioSwitch,
	NcLoadingIcon,
	NcSelect,
} from '@nextcloud/vue'
import ArrowLeft from 'vue-material-design-icons/ArrowLeft.vue'
import ContentSave from 'vue-material-design-icons/ContentSave.vue'

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
		/**
		 * The consent record to show.
		 *
		 * MUST be named `id`, because that is the name of the route
		 * parameter. src/main.js builds this route with `props: true` for
		 * any path containing a `:`, and vue-router's `props: true` passes
		 * `route.params` through BY NAME. The manifest route is
		 * `/consent/:id`, so a prop called anything else is simply never
		 * supplied — this was declared `consentId` with `default: ''`, so
		 * the falsy guard in `created()` never fired, `fetchConsent()` was
		 * never called, and every deep link or page refresh on
		 * `/consent/<uuid>` rendered the "No consent record selected."
		 * error state instead of the record. The page only ever appeared to
		 * work when reached by clicking a row, because ConsentIndex calls
		 * `setConsentItem()` before routing. Same defect, same fix as
		 * SigningRequestDetail's `requestId` → `id`.
		 */
		id: {
			type: String,
			required: true,
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
				{ label: t('filinq', 'Pending'), value: 'pending' },
				{ label: t('filinq', 'Consent Given'), value: 'consent_given' },
				{
					label: t('filinq', 'Objection Received'),
					value: 'objection_received',
				},
				{ label: t('filinq', 'No Response'), value: 'no_response' },
				{ label: t('filinq', 'Anonymized'), value: 'anonymized' },
			],

			notificationStatusOptions: [
				{ label: t('filinq', 'Pending'), value: 'pending' },
				{ label: t('filinq', 'Sent'), value: 'sent' },
				{ label: t('filinq', 'Delivered'), value: 'delivered' },
				{ label: t('filinq', 'Failed'), value: 'failed' },
				{ label: t('filinq', 'Skipped'), value: 'skipped' },
			],

			publicationDecisionOptions: [
				{ label: t('filinq', 'Pending'), value: 'pending' },
				{ label: t('filinq', 'Anonymize'), value: 'anonymize' },
				{
					label: t('filinq', 'Publish with Consent'),
					value: 'publish_with_consent',
				},
				{
					label: t('filinq', 'Publish Anonymized'),
					value: 'publish_anonymized',
				},
				{ label: t('filinq', 'Reject'), value: 'reject' },
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
					this.editData.consentStatus =
						this.consentStatusOptions.find(
							(o) => o.value === item.consentStatus,
						) || null
					this.editData.notificationStatus =
						this.notificationStatusOptions.find(
							(o) => o.value === item.notificationStatus,
						) || null
					this.editData.publicationDecision =
						this.publicationDecisionOptions.find(
							(o) => o.value === item.publicationDecision,
						) || null
					this.refreshPolicyMatch()
				}
			},
		},
	},

	/**
	 * Load the consent record named by the route parameter.
	 *
	 * The guard compares the id rather than testing for "any item at all":
	 * `consentStore.consentItem` is module-level state that survives
	 * navigation, so a bare truthiness check would keep the PREVIOUS
	 * record on screen when the operator moves straight from one consent
	 * to another.
	 *
	 * @spec openspec/specs/consent-management/spec.md#requirement-consent-ui-req-cons-10
	 */
	created() {
		if (this.id && consentStore.consentItem?.id !== this.id) {
			consentStore.fetchConsent(this.id)
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
		 *
		 * @return {Promise<void>}
		 * @spec openspec/specs/entity-publication-policies/spec.md#requirement-ui-toggle-behavior-must-be-derived-from-policymatch-referent-type
		 */
		async refreshPolicyMatch() {
			const item = consentStore.consentItem
			this.policyMatchKind = null
			if (!item?.policyMatch) {
				this.anonymiseToggle = item?.publicationDecision === 'anonymize'
				return
			}

			try {
				await axios.get(
					generateUrl(
						`/apps/filinq/api/policy/prohibitions/${item.policyMatch}`,
					),
				)
				this.policyMatchKind = 'prohibition'
				this.anonymiseToggle = true
				return
			} catch (err) {
				// 404 / other → falls through to standing-consent probe.
			}

			try {
				await axios.get(
					generateUrl(
						`/apps/filinq/api/policy/standing-consents/${item.policyMatch}`,
					),
				)
				this.policyMatchKind = 'standing_consent'
				// Default OFF for standing consent; user may override.
				this.anonymiseToggle = item.publicationDecision === 'anonymize'
				return
			} catch (err) {
				// Dangling reference — fall through to legacy.
			}

			this.anonymiseToggle = item?.publicationDecision === 'anonymize'
		},

		/**
		 * Handle toggle clicks. For standing-consent matches, flipping ON
		 * records an override: publicationDecision=anonymize while consentStatus
		 * stays consent_given and policyMatch is preserved. The audit trail
		 * comes from OpenRegister's mapper-level history.
		 *
		 * @param checked
		 * @spec openspec/specs/entity-publication-policies/spec.md#requirement-ui-toggle-behavior-must-be-derived-from-policymatch-referent-type
		 */
		async onToggleAnonymise(checked) {
			if (this.policyMatchKind === 'prohibition') {
				// Should be impossible due to disabled; defensive.
				this.anonymiseToggle = true
				return
			}

			const id =
				consentStore.consentItem?.['@self']?.id
				|| consentStore.consentItem?.id
				|| consentStore.consentItem?.uuid
			if (!id) return

			const update = {
				publicationDecision: checked ? 'anonymize' : 'publish_with_consent',
			}
			try {
				await consentStore.updateConsent(id, update)
				showSuccess(t('filinq', 'Anonymisation decision updated'))
			} catch (err) {
				showError(t('filinq', 'Failed to update anonymisation decision'))
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
				updateData.notificationStatus =
					this.editData.notificationStatus.value
			}
			if (this.editData.publicationDecision?.value) {
				updateData.publicationDecision =
					this.editData.publicationDecision.value
			}

			const result = await consentStore.updateConsent(id, updateData)
			if (result) {
				showSuccess(t('filinq', 'Consent record updated successfully'))
			} else {
				showError(t('filinq', 'Failed to update consent record'))
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
	border-radius: var(--dd-radius-md);
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

.detail-table td,
.detail-table th {
	padding: 8px 4px;
	vertical-align: middle;
}

/* The row headers are <th> now; neutralise the UA's centred default so the
   rendering is unchanged. */
.detail-table .label {
	text-align: start;
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
