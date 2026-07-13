<script setup>
import { translate as t } from '@nextcloud/l10n'
import { standingConsentStore } from '../../store/store.js'
</script>

<template>
	<div>
		<CnIndexPage
			ref="indexPage"
			:title="t('docudesk', 'Publish always')"
			:description="t('docudesk', 'Entity-level allow rules. A matched entity may be published without per-document objection workflow, unless a publish-never rule also matches.')"
			:show-title="true"
			:objects="standingConsentStore.standingConsents"
			:columns="tableColumns"
			:pagination="paginationData"
			:loading="standingConsentStore.loading"
			:selectable="false"
			:show-edit-action="false"
			:show-copy-action="false"
			:show-delete-action="false"
			:show-mass-import="false"
			:show-mass-export="false"
			:show-mass-copy="false"
			:show-mass-delete="false"
			:show-view-toggle="false"
			:show-add="true"
			row-key="id"
			:empty-text="emptyText"
			:refreshing="isRefreshing"
			@refresh="handleRefresh"
			@page-changed="onPageChanged"
			@page-size-changed="onPageSizeChanged"
			@add="openCreateDialog">
			<template #above-table>
				<div class="policy-stats">
					<CnStatsBlock
						:title="t('docudesk', 'Total')"
						:count="standingConsentStore.standingConsentStats.total"
						:count-label="t('docudesk', 'rules')"
						variant="default"
						horizontal
						show-zero-count />
					<CnStatsBlock
						:title="t('docudesk', 'Active')"
						:count="standingConsentStore.standingConsentStats.active"
						:count-label="t('docudesk', 'active')"
						variant="success"
						horizontal
						show-zero-count />
					<CnStatsBlock
						:title="t('docudesk', 'Inactive')"
						:count="standingConsentStore.standingConsentStats.inactive"
						:count-label="t('docudesk', 'inactive')"
						variant="default"
						horizontal
						show-zero-count />
				</div>
			</template>

			<template #column-entityType="{ row }">
				<CnStatusBadge
					:label="row.entityType || t('docudesk', 'Unknown')"
					:color-map="entityTypeColorMap" />
			</template>

			<template #column-consentMethod="{ row }">
				<CnStatusBadge
					:label="row.consentMethod || '-'"
					:color-map="methodColorMap" />
			</template>

			<template #column-active="{ row }">
				<CnStatusBadge
					:label="row.active === false ? t('docudesk', 'Inactive') : t('docudesk', 'Active')"
					:color-map="activeColorMap" />
			</template>

			<template #column-matchRules="{ row }">
				{{ formatMatchRules(row.matchRules) }}
			</template>

			<template #row-actions="{ row }">
				<NcActions>
					<template #icon>
						<DotsHorizontal :size="20" />
					</template>
					<NcActionButton close-after-click @click="openEditDialog(row)">
						<template #icon>
							<Pencil :size="20" />
						</template>
						{{ t('docudesk', 'Edit') }}
					</NcActionButton>
					<NcActionButton close-after-click @click="confirmDelete(row)">
						<template #icon>
							<Delete :size="20" />
						</template>
						{{ t('docudesk', 'Delete') }}
					</NcActionButton>
				</NcActions>
			</template>
		</CnIndexPage>

		<NcDialog
			v-if="dialogOpen"
			:name="dialogTitle"
			:open="dialogOpen"
			size="normal"
			@update:open="dialogOpen = $event">
			<div class="standing-consent-form">
				<NcTextField
					:value.sync="form.entityText"
					:label="t('docudesk', 'Entity text (display name)')"
					required />
				<NcSelect
					v-model="form.entityType"
					:options="entityTypeOptions"
					:input-label="t('docudesk', 'Entity type')"
					:label="t('docudesk', 'Entity type')"
					required />
				<NcSelect
					v-model="form.consentMethod"
					:options="consentMethodOptions"
					:input-label="t('docudesk', 'Consent method')"
					:label="t('docudesk', 'Consent method')"
					required />
				<NcTextField
					:value.sync="form.consentDocument"
					:label="t('docudesk', 'Consent document (file id or URL)')" />
				<NcTextField
					:value.sync="form.consentScope"
					:label="t('docudesk', 'Consent scope (e.g. \'2024-2025 municipal decisions\')')" />
				<NcTextField
					:value.sync="form.legalBasis"
					:label="t('docudesk', 'Legal basis')" />
				<NcTextField
					:value.sync="form.validFrom"
					:label="t('docudesk', 'Valid from (ISO 8601, optional)')" />
				<NcTextField
					:value.sync="form.validUntil"
					:label="t('docudesk', 'Valid until (ISO 8601, optional)')" />
				<NcCheckboxRadioSwitch
					v-model="form.active"
					type="switch">
					{{ t('docudesk', 'Active') }}
				</NcCheckboxRadioSwitch>

				<div v-if="!form.validUntil" class="form-warning">
					{{ t('docudesk', 'No expiry set — this standing consent will remain in force indefinitely. Consider setting a "Valid until" date.') }}
				</div>

				<h4>{{ t('docudesk', 'Match rules') }}</h4>
				<div v-if="!form.matchRules?.length" class="form-warning">
					{{ t('docudesk', 'Add at least one match rule. Prefer stable identifiers (BSN/KvK) over name-only matches.') }}
				</div>
				<div v-for="(rule, idx) in form.matchRules" :key="idx" class="match-rule-row">
					<NcSelect
						v-model="rule.type"
						:options="matchTypeOptions"
						:input-label="t('docudesk', 'Match type')"
						:label="t('docudesk', 'Match type')" />
					<NcTextField
						:value.sync="rule.value"
						:label="t('docudesk', 'Match value')" />
					<NcButton type="tertiary" @click="removeRule(idx)">
						<template #icon>
							<Delete :size="20" />
						</template>
					</NcButton>
				</div>
				<NcButton type="secondary" @click="addRule">
					{{ t('docudesk', 'Add match rule') }}
				</NcButton>

				<div v-if="formError" class="form-error">
					{{ formError }}
				</div>
			</div>

			<template #actions>
				<NcButton type="tertiary" @click="dialogOpen = false">
					{{ t('docudesk', 'Cancel') }}
				</NcButton>
				<NcButton type="primary" :disabled="saving || !canSubmit" @click="submit">
					<template v-if="saving" #icon>
						<NcLoadingIcon :size="20" />
					</template>
					{{ editing ? t('docudesk', 'Save') : t('docudesk', 'Create') }}
				</NcButton>
			</template>
		</NcDialog>
	</div>
</template>

<script>
import {
	NcActions,
	NcActionButton,
	NcButton,
	NcCheckboxRadioSwitch,
	NcDialog,
	NcLoadingIcon,
	NcSelect,
	NcTextField,
} from '@nextcloud/vue'
import { CnIndexPage, CnStatsBlock, CnStatusBadge } from '@conduction/nextcloud-vue'
import Delete from 'vue-material-design-icons/Delete.vue'
import DotsHorizontal from 'vue-material-design-icons/DotsHorizontal.vue'
import Pencil from 'vue-material-design-icons/Pencil.vue'

const blankForm = () => ({
	entityText: '',
	entityType: 'PERSON',
	consentMethod: '',
	consentDocument: '',
	consentScope: '',
	legalBasis: '',
	validFrom: '',
	validUntil: '',
	active: true,
	matchRules: [],
	consentStatus: 'consent_given',
	publicationDecision: 'publish_with_consent',
	notificationStatus: 'skipped',
})

export default {
	name: 'StandingConsentIndex',
	components: {
		CnIndexPage,
		CnStatsBlock,
		CnStatusBadge,
		NcActions,
		NcActionButton,
		NcButton,
		NcCheckboxRadioSwitch,
		NcDialog,
		NcLoadingIcon,
		NcSelect,
		NcTextField,
		Delete,
		DotsHorizontal,
		Pencil,
	},
	data() {
		return {
			isRefreshing: false,
			currentPage: 1,
			pageSize: 20,
			dialogOpen: false,
			saving: false,
			editing: null,
			form: blankForm(),
			formError: '',
			entityTypeColorMap: {
				PERSON: 'warning',
				ORGANIZATION: 'primary',
				OTHER: 'default',
			},
			methodColorMap: {
				paper: 'default',
				digital_signature: 'primary',
				verbal_recorded: 'warning',
				opt_in_form: 'success',
			},
			activeColorMap: {
				[t('docudesk', 'Active')]: 'success',
				[t('docudesk', 'Inactive')]: 'default',
			},
			entityTypeOptions: ['PERSON', 'ORGANIZATION', 'OTHER'],
			consentMethodOptions: ['paper', 'digital_signature', 'verbal_recorded', 'opt_in_form'],
			matchTypeOptions: ['exact', 'normalized', 'bsn', 'kvk'],
		}
	},
	computed: {
		tableColumns() {
			return [
				{ key: 'entityText', label: t('docudesk', 'Entity'), sortable: true },
				{ key: 'entityType', label: t('docudesk', 'Type'), sortable: true },
				{ key: 'matchRules', label: t('docudesk', 'Match rules') },
				{ key: 'consentMethod', label: t('docudesk', 'Method'), sortable: true },
				{ key: 'validUntil', label: t('docudesk', 'Valid until'), sortable: true },
				{ key: 'active', label: t('docudesk', 'Status'), sortable: true },
			]
		},
		paginationData() {
			const total = standingConsentStore.standingConsents.length
			const pages = Math.ceil(total / this.pageSize)
			return { page: this.currentPage, pages, total, limit: this.pageSize }
		},
		emptyText() {
			if (standingConsentStore.error) {
				return standingConsentStore.error
			}
			return t('docudesk', 'No standing publication consents defined.')
		},
		dialogTitle() {
			return this.editing
				? t('docudesk', 'Edit publish-always rule')
				: t('docudesk', 'Add publish-always rule')
		},
		canSubmit() {
			return this.form.entityText.trim() !== ''
				&& this.form.consentMethod !== ''
				&& this.form.matchRules.length > 0
				&& this.form.matchRules.every(r => r.type && r.value !== '')
		},
	},
	mounted() {
		standingConsentStore.fetchStandingConsents()
	},
	methods: {
		async handleRefresh() {
			this.isRefreshing = true
			try {
				await standingConsentStore.fetchStandingConsents()
			} finally {
				this.isRefreshing = false
			}
		},
		onPageChanged(page) {
			this.currentPage = page
		},
		onPageSizeChanged(size) {
			this.pageSize = size
			this.currentPage = 1
		},
		formatMatchRules(rules) {
			if (!Array.isArray(rules) || rules.length === 0) {
				return '-'
			}
			return rules.map(r => `${r.type}:${r.value}`).join(', ')
		},
		openCreateDialog() {
			this.editing = null
			this.form = blankForm()
			this.formError = ''
			this.dialogOpen = true
		},
		openEditDialog(row) {
			this.editing = row['@self']?.id || row.id || row.uuid
			this.form = {
				entityText: row.entityText || '',
				entityType: row.entityType || 'PERSON',
				consentMethod: row.consentMethod || '',
				consentDocument: row.consentDocument || '',
				consentScope: row.consentScope || '',
				legalBasis: row.legalBasis || '',
				validFrom: row.validFrom || '',
				validUntil: row.validUntil || '',
				active: row.active !== false,
				matchRules: Array.isArray(row.matchRules)
					? row.matchRules.map(r => ({ type: r.type, value: r.value }))
					: [],
				consentStatus: row.consentStatus || 'consent_given',
				publicationDecision: row.publicationDecision || 'publish_with_consent',
				notificationStatus: row.notificationStatus || 'skipped',
			}
			this.formError = ''
			this.dialogOpen = true
		},
		addRule() {
			this.form.matchRules.push({ type: 'exact', value: '' })
		},
		removeRule(idx) {
			this.form.matchRules.splice(idx, 1)
		},
		async submit() {
			this.saving = true
			this.formError = ''
			try {
				if (this.editing) {
					await standingConsentStore.updateStandingConsent(this.editing, this.form)
				} else {
					await standingConsentStore.createStandingConsent(this.form)
				}
				this.dialogOpen = false
			} catch (err) {
				this.formError = err.response?.data?.error || err.message || t('docudesk', 'Save failed')
			} finally {
				this.saving = false
			}
		},
		async confirmDelete(row) {
			const id = row['@self']?.id || row.id || row.uuid
			const name = row.entityText || t('docudesk', 'this standing consent')
			// eslint-disable-next-line no-alert
			if (!window.confirm(t('docudesk', 'Delete "{name}"? This cannot be undone.', { name }))) {
				return
			}
			try {
				await standingConsentStore.deleteStandingConsent(id)
			} catch (err) {
				console.error('Failed to delete standing consent:', err)
			}
		},
	},
}
</script>

<style scoped>
.policy-stats {
	display: flex;
	gap: 16px;
	margin-bottom: 16px;
	flex-wrap: wrap;
}
.standing-consent-form {
	display: flex;
	flex-direction: column;
	gap: 12px;
	padding: 8px;
}
.match-rule-row {
	display: grid;
	grid-template-columns: 180px 1fr 40px;
	gap: 8px;
	align-items: center;
}
.form-warning {
	background: var(--color-warning, #fff3cd);
	color: var(--color-text-maxcontrast, #333);
	padding: 8px 12px;
	border-radius: var(--border-radius);
	font-size: 13px;
}
.form-error {
	background: var(--color-error, #ffd1d1);
	color: var(--color-text-maxcontrast, #333);
	padding: 8px 12px;
	border-radius: var(--border-radius);
	font-size: 13px;
}
</style>
