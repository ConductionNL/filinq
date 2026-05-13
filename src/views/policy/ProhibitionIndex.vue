<script setup>
import { translate as t } from '@nextcloud/l10n'
import { prohibitionStore } from '../../store/store.js'
</script>

<template>
	<div>
		<CnIndexPage
			ref="indexPage"
			:title="t('docudesk', 'Publication Prohibitions')"
			:description="t('docudesk', 'Entity-level deny rules. A matched entity is always anonymised, regardless of the per-document consent workflow.')"
			:show-title="true"
			:objects="prohibitionStore.prohibitions"
			:columns="tableColumns"
			:pagination="paginationData"
			:loading="prohibitionStore.loading"
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
						:count="prohibitionStore.prohibitionStats.total"
						:count-label="t('docudesk', 'rules')"
						variant="default"
						horizontal
						show-zero-count />
					<CnStatsBlock
						:title="t('docudesk', 'Active')"
						:count="prohibitionStore.prohibitionStats.active"
						:count-label="t('docudesk', 'active')"
						variant="error"
						horizontal
						show-zero-count />
					<CnStatsBlock
						:title="t('docudesk', 'Inactive')"
						:count="prohibitionStore.prohibitionStats.inactive"
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

			<template #column-severity="{ row }">
				<CnStatusBadge
					:label="row.severity || '-'"
					:color-map="severityColorMap" />
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
			<div class="prohibition-form">
				<NcTextField
					:value.sync="form.primaryName"
					:label="t('docudesk', 'Primary name (Dutch)')"
					required />
				<NcSelect
					v-model="form.entityType"
					:options="entityTypeOptions"
					:label="t('docudesk', 'Entity type')"
					required />
				<NcTextField
					:value.sync="form.reason"
					:label="t('docudesk', 'Reason (markdown allowed)')"
					required />
				<NcTextField
					:value.sync="form.legalAuthority"
					:label="t('docudesk', 'Legal authority (court order, statute, …)')" />
				<NcTextField
					:value.sync="form.caseReference"
					:label="t('docudesk', 'Case reference (optional)')" />
				<NcSelect
					v-model="form.severity"
					:options="severityOptions"
					:label="t('docudesk', 'Severity')" />
				<NcTextField
					:value.sync="form.jurisdiction"
					:label="t('docudesk', 'Jurisdiction (optional)')" />
				<NcTextField
					:value.sync="form.validUntil"
					:label="t('docudesk', 'Valid until (ISO 8601, optional)')" />
				<NcCheckboxRadioSwitch
					v-model="form.active"
					type="switch">
					{{ t('docudesk', 'Active') }}
				</NcCheckboxRadioSwitch>

				<h4>{{ t('docudesk', 'Match rules') }}</h4>
				<div v-if="!form.matchRules?.length" class="form-warning">
					{{ t('docudesk', 'Add at least one match rule. Prefer stable identifiers (BSN/KvK) over name-only matches — names alone produce false positives.') }}
				</div>
				<div v-for="(rule, idx) in form.matchRules" :key="idx" class="match-rule-row">
					<NcSelect
						v-model="rule.type"
						:options="matchTypeOptions"
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

				<div v-if="onlyNameRules" class="form-warning">
					{{ t('docudesk', 'Warning: only name-based rules are present. Names alone often produce false positives — consider adding a BSN or KvK match.') }}
				</div>

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
	primaryName: '',
	entityType: 'PERSON',
	reason: '',
	legalAuthority: '',
	caseReference: '',
	severity: 'standard',
	jurisdiction: '',
	validUntil: '',
	active: true,
	matchRules: [],
})

export default {
	name: 'ProhibitionIndex',
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
			severityColorMap: {
				low: 'default',
				standard: 'primary',
				high: 'warning',
				critical: 'error',
			},
			activeColorMap: {
				[t('docudesk', 'Active')]: 'error',
				[t('docudesk', 'Inactive')]: 'default',
			},
			entityTypeOptions: ['PERSON', 'ORGANIZATION', 'OTHER'],
			severityOptions: ['low', 'standard', 'high', 'critical'],
			matchTypeOptions: ['exact', 'normalized', 'bsn', 'kvk'],
		}
	},
	computed: {
		tableColumns() {
			return [
				{ key: 'primaryName', label: t('docudesk', 'Primary name'), sortable: true },
				{ key: 'entityType', label: t('docudesk', 'Type'), sortable: true },
				{ key: 'matchRules', label: t('docudesk', 'Match rules') },
				{ key: 'severity', label: t('docudesk', 'Severity'), sortable: true },
				{ key: 'reason', label: t('docudesk', 'Reason') },
				{ key: 'active', label: t('docudesk', 'Status'), sortable: true },
			]
		},
		paginationData() {
			const total = prohibitionStore.prohibitions.length
			const pages = Math.ceil(total / this.pageSize)
			return { page: this.currentPage, pages, total, limit: this.pageSize }
		},
		emptyText() {
			if (prohibitionStore.error) {
				return prohibitionStore.error
			}
			return t('docudesk', 'No publication prohibitions defined.')
		},
		dialogTitle() {
			return this.editing
				? t('docudesk', 'Edit prohibition')
				: t('docudesk', 'Add prohibition')
		},
		canSubmit() {
			return this.form.primaryName.trim() !== ''
				&& this.form.reason.trim() !== ''
				&& this.form.matchRules.length > 0
				&& this.form.matchRules.every(r => r.type && r.value !== '')
		},
		onlyNameRules() {
			if (this.form.matchRules.length === 0) {
				return false
			}
			return this.form.matchRules.every(r => r.type === 'exact' || r.type === 'normalized')
		},
	},
	mounted() {
		prohibitionStore.fetchProhibitions()
	},
	methods: {
		async handleRefresh() {
			this.isRefreshing = true
			try {
				await prohibitionStore.fetchProhibitions()
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
				primaryName: row.primaryName || '',
				entityType: row.entityType || 'PERSON',
				reason: row.reason || '',
				legalAuthority: row.legalAuthority || '',
				caseReference: row.caseReference || '',
				severity: row.severity || 'standard',
				jurisdiction: row.jurisdiction || '',
				validUntil: row.validUntil || '',
				active: row.active !== false,
				matchRules: Array.isArray(row.matchRules)
					? row.matchRules.map(r => ({ type: r.type, value: r.value }))
					: [],
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
					await prohibitionStore.updateProhibition(this.editing, this.form)
				} else {
					await prohibitionStore.createProhibition(this.form)
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
			const name = row.primaryName || t('docudesk', 'this prohibition')
			// eslint-disable-next-line no-alert
			if (!window.confirm(t('docudesk', 'Delete "{name}"? This cannot be undone.', { name }))) {
				return
			}
			try {
				await prohibitionStore.deleteProhibition(id)
			} catch (err) {
				console.error('Failed to delete prohibition:', err)
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
.prohibition-form {
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
	border-radius: 4px;
	font-size: 13px;
}
.form-error {
	background: var(--color-error, #ffd1d1);
	color: var(--color-text-maxcontrast, #333);
	padding: 8px 12px;
	border-radius: 4px;
	font-size: 13px;
}
</style>
