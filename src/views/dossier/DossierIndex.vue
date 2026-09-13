<template>
	<div>
		<CnIndexPage
			:title="t('filinq', 'Dossiers')"
			:description="
				t(
					'filinq',
					'A dossier groups the documents that are anonymised together under one or more Woo Art. 5 legal bases.',
				)
			"
			:showTitle="true"
			:objects="dossierStore.dossiers"
			:columns="tableColumns"
			:loading="dossierStore.loading"
			:selectable="false"
			:showEditAction="false"
			:showCopyAction="false"
			:showDeleteAction="false"
			:showMassImport="false"
			:showMassExport="false"
			:showMassCopy="false"
			:showMassDelete="false"
			:showViewToggle="false"
			:showAdd="true"
			rowKey="id"
			:emptyText="
				t(
					'filinq',
					'No dossiers yet. Create one to group the documents you want to anonymise together.',
				)
			"
			:refreshing="isRefreshing"
			@refresh="handleRefresh"
			@add="dialogOpen = true"
			@rowClick="openDossier">
			<template #column-status="{ row }">
				<CnStatusBadge
					:label="statusLabel(row.status)"
					:colorMap="statusColorMap" />
			</template>

			<template #column-documentCount="{ row }">
				<span>{{ row.documentCount }}</span>
				<!--
					A membership reference whose file is gone or unreadable is
					counted separately and shown. Folding it into the total
					would make a dossier that has lost a document look intact.
				-->
				<CnStatusBadge
					v-if="row.missingCount > 0"
					class="dossier-missing"
					:label="
						n('filinq', '%n missing', '%n missing', row.missingCount)
					"
					:colorMap="{ [missingLabel(row.missingCount)]: 'error' }" />
			</template>

			<template #column-bases="{ row }">
				<span
					v-if="!row.bases || row.bases.length === 0"
					class="dossier-muted">
					{{ t('filinq', 'None set') }}
				</span>
				<CnStatusBadge
					v-for="base in row.bases"
					:key="base.slug"
					:label="base.label"
					:colorMap="{
						[base.label]: base.known ? 'primary' : 'warning',
					}" />
			</template>

			<template #column-checkedOn="{ row }">
				<span v-if="row.checkedOn">{{ formatDate(row.checkedOn) }}</span>
				<span v-else class="dossier-muted">{{
					t('filinq', 'Not reviewed')
				}}</span>
			</template>
		</CnIndexPage>

		<!-- ADR-004: the dialog lives in its own component, never inline. -->
		<DossierFormModal
			:open="dialogOpen"
			:saving="dossierStore.saving"
			:formError="dossierStore.error || ''"
			@update:open="dialogOpen = $event"
			@submit="createDossier"
			@cancel="dialogOpen = false" />
	</div>
</template>

<script>
import { CnIndexPage, CnStatusBadge } from '@conduction/nextcloud-vue'
import { translatePlural as n, translate as t } from '@nextcloud/l10n'
import DossierFormModal from '../../dialogs/DossierFormModal.vue'
import { dossierStore } from '../../store/store.js'

/**
 * Dossiers index.
 *
 * The dossier is Filinq's unit of municipal work — folder batches run on it,
 * the grondslagen summary reports on it, the Woo publication publishes it — and
 * until this page there was no way to see one. The schema, the controller and
 * the spec all existed; the surface did not.
 */
export default {
	name: 'DossierIndex',
	components: {
		CnIndexPage,
		CnStatusBadge,
		DossierFormModal,
	},

	data() {
		return {
			dossierStore,
			isRefreshing: false,
			dialogOpen: false,
			statusColorMap: {
				[t('filinq', 'Open')]: 'default',
				[t('filinq', 'In review')]: 'warning',
				[t('filinq', 'Processed')]: 'primary',
				[t('filinq', 'Published')]: 'success',
				[t('filinq', 'Closed')]: 'default',
			},
		}
	},

	computed: {
		tableColumns() {
			return [
				{ key: 'name', label: t('filinq', 'Name') },
				{ key: 'status', label: t('filinq', 'Status') },
				{ key: 'documentCount', label: t('filinq', 'Documents') },
				{ key: 'bases', label: t('filinq', 'Legal bases') },
				{ key: 'checkedOn', label: t('filinq', 'Last reviewed') },
			]
		},
	},

	mounted() {
		this.dossierStore.fetchDossiers()
	},

	methods: {
		t,
		n,

		/**
		 * Human label for a lifecycle status.
		 *
		 * A dossier created before `status` existed has none, and absence
		 * means `open` rather than missing data.
		 *
		 * @param {string} status The stored status.
		 * @return {string} The label.
		 */
		statusLabel(status) {
			const labels = {
				open: t('filinq', 'Open'),
				'in-review': t('filinq', 'In review'),
				processed: t('filinq', 'Processed'),
				published: t('filinq', 'Published'),
				closed: t('filinq', 'Closed'),
			}
			return labels[status] || t('filinq', 'Open')
		},

		/**
		 * The missing-count label, matched by the badge colour map.
		 *
		 * @param {number} count How many references could not be resolved.
		 * @return {string} The label.
		 */
		missingLabel(count) {
			return n('filinq', '%n missing', '%n missing', count)
		},

		formatDate(value) {
			return new Date(value).toLocaleDateString()
		},

		async handleRefresh() {
			this.isRefreshing = true
			await this.dossierStore.fetchDossiers()
			this.isRefreshing = false
		},

		openDossier(row) {
			this.$router.push({ name: 'DossierDetail', params: { id: row.id } })
		},

		async createDossier(payload) {
			const dossier = await this.dossierStore.createDossier(payload)
			if (dossier) {
				this.dialogOpen = false
				this.openDossier(dossier)
			}
		},
	},
}
</script>

<style scoped>
.dossier-muted {
	color: var(--color-text-maxcontrast);
}

.dossier-missing {
	margin-inline-start: 0.5rem;
}
</style>
