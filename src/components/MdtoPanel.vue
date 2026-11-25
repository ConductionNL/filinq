<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

The MDTO panel of a document or dossier: the core archival attributes from
the record's retention block (read only), the supplement with Filinq's
proposals to accept or reject, the completeness banner, and the export and
overbrengen actions.

@spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/tasks.md#3-frontend
-->

<template>
	<section
		v-if="objectId"
		class="mdto-panel"
		data-testid="mdto-panel">
		<h3 class="mdto-panel__title">
			{{ t('filinq', 'MDTO metadata') }}
		</h3>

		<NcNoteCard v-if="loadError" type="error">
			{{ loadError }}
		</NcNoteCard>

		<template v-else-if="status">
			<dl class="mdto-panel__core" data-testid="mdto-core">
				<template v-for="row in coreRows" :key="row.key">
					<dt>{{ row.label }}</dt>
					<dd>{{ row.value || t('filinq', 'Not recorded') }}</dd>
				</template>
			</dl>

			<NcNoteCard
				v-if="transferred"
				type="info"
				data-testid="mdto-transferred">
				{{ t('filinq', 'Transferred to the e-depot. This record is read only: it can no longer be changed.') }}
			</NcNoteCard>

			<dl class="mdto-panel__supplement" data-testid="mdto-supplement">
				<template v-for="row in supplementRows" :key="row.key">
					<dt>{{ row.label }}</dt>
					<dd>
						{{ row.value || t('filinq', 'Not recorded') }}
						<span
							v-if="row.unconfirmed"
							class="mdto-panel__unconfirmed">
							{{ t('filinq', '(proposal, not confirmed)') }}
						</span>
					</dd>
				</template>
			</dl>

			<ul
				v-if="fieldProposals.length || restrictionProposals.length"
				class="mdto-panel__proposals"
				data-testid="mdto-proposals">
				<li
					v-for="proposal in fieldProposals"
					:key="proposal.field"
					class="mdto-panel__chip">
					<span>{{ proposal.label }}: {{ proposal.value }}</span>
					<NcButton
						variant="tertiary"
						:disabled="busy || transferred"
						:data-testid="`mdto-accept-${proposal.field}`"
						@click="accept(proposal)">
						{{ t('filinq', 'Accept') }}
					</NcButton>
					<NcButton
						variant="tertiary"
						:disabled="busy || transferred"
						@click="editing = true">
						{{ t('filinq', 'Edit') }}
					</NcButton>
				</li>
				<li
					v-for="restriction in restrictionProposals"
					:key="`${restriction.source}-${restriction.type}-${restriction.grondslag}`"
					class="mdto-panel__chip"
					data-testid="mdto-restriction-proposal">
					<span>{{ restrictionLabel(restriction) }}</span>
					<NcButton
						variant="tertiary"
						:disabled="busy || transferred"
						data-testid="mdto-restriction-accept"
						@click="decide(restriction, true)">
						{{ t('filinq', 'Accept') }}
					</NcButton>
					<NcButton
						variant="tertiary"
						:disabled="busy || transferred"
						data-testid="mdto-restriction-reject"
						@click="decide(restriction, false)">
						{{ t('filinq', 'Reject') }}
					</NcButton>
				</li>
			</ul>

			<ul
				v-if="confirmedRestrictions.length"
				class="mdto-panel__restrictions"
				data-testid="mdto-restrictions">
				<li v-for="restriction in confirmedRestrictions" :key="`${restriction.type}-${restriction.grondslag}`">
					{{ restrictionLabel(restriction) }}
				</li>
			</ul>

			<NcNoteCard
				v-if="!status.complete"
				type="warning"
				data-testid="mdto-incomplete">
				<p>{{ t('filinq', 'This record cannot be transferred yet. Missing MDTO fields:') }}</p>
				<ul class="mdto-panel__missing">
					<li v-for="field in status.missing" :key="field">
						<code>{{ field }}</code>
					</li>
				</ul>
			</NcNoteCard>

			<NcNoteCard
				v-if="status.mayTransfer && !status.transportConfigured"
				type="info"
				data-testid="mdto-manual-delivery">
				{{ t('filinq', 'No e-depot transport is configured in OpenRegister. Download the sidecar and deliver it to the e-depot by hand.') }}
			</NcNoteCard>

			<NcNoteCard
				v-if="transferMessage"
				:type="transferMessage.type"
				data-testid="mdto-transfer-result">
				{{ transferMessage.text }}
			</NcNoteCard>

			<div class="mdto-panel__actions">
				<NcButton :disabled="busy || transferred" @click="editing = true">
					{{ t('filinq', 'Edit MDTO metadata') }}
				</NcButton>
				<template v-if="status.mayTransfer">
					<NcButton
						:href="xmlUrl"
						data-testid="mdto-export-xml">
						{{ t('filinq', 'Export MDTO XML') }}
					</NcButton>
					<NcButton :href="jsonUrl">
						{{ t('filinq', 'Export MDTO JSON') }}
					</NcButton>
					<NcButton
						v-if="status.transportConfigured"
						variant="primary"
						:disabled="busy || !status.complete || transferred"
						data-testid="mdto-transfer"
						@click="transfer">
						{{ t('filinq', 'Transfer to e-depot') }}
					</NcButton>
				</template>
			</div>
		</template>

		<NcLoadingIcon v-else />

		<MdtoSupplementModal
			v-if="editing"
			:supplement="status ? status.supplement : {}"
			:subject="subject"
			@saved="onSaved"
			@close="editing = false" />
	</section>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcLoadingIcon, NcNoteCard } from '@nextcloud/vue'
import { defineAsyncComponent } from 'vue'
import {
	acceptProposal,
	decideRestriction,
	fetchMdtoStatus,
	isTransferred,
	openRestrictionProposals,
	saveSupplement,
	sidecarUrl,
	transferRecord,
} from '../services/mdto.js'

const SCALAR_PROPOSALS = ['aggregatieniveau', 'dekkingInTijdBegin', 'dekkingInTijdEind']

// Loaded when the form opens: the form library is only needed then, and the
// panel sits in the file sidebar, which loads on every file view.
const MdtoSupplementModal = defineAsyncComponent(() => import('../modals/MdtoSupplementModal.vue'))

export default {
	name: 'MdtoPanel',
	components: { MdtoSupplementModal, NcButton, NcLoadingIcon, NcNoteCard },

	props: {
		/** The record (document or dossier) uuid. */
		objectId: { type: String, default: '' },
		/** `document` or `dossier`. */
		objectType: { type: String, default: 'document' },
	},

	emits: ['transferred'],

	data() {
		return {
			status: null,
			loadError: '',
			busy: false,
			editing: false,
			transferMessage: null,
		}
	},

	computed: {
		/**
		 * Whether the record was transferred to the e-depot. OpenRegister
		 * refuses every change to it, so the panel offers none.
		 *
		 * @return {boolean}
		 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-transfer-to-archive-state-for-permanent-records-req-ddare-006
		 */
		transferred() {
			return isTransferred(this.status?.retention)
		},

		/**
		 * The record the supplement describes.
		 *
		 * @return {{objectRef: string, objectType: string}}
		 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/tasks.md#3-frontend
		 */
		subject() {
			return { objectRef: this.objectId, objectType: this.objectType }
		},

		/**
		 * The core archival attributes, read from the retention block only.
		 *
		 * @return {Array<{key: string, label: string, value: string}>}
		 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/tasks.md#3-frontend
		 */
		coreRows() {
			const retention = this.status?.retention || {}
			return [
				{ key: 'archiefnominatie', label: t('filinq', 'Appraisal'), value: retention.archiefnominatie },
				{ key: 'bewaartermijn', label: t('filinq', 'Retention period'), value: retention.bewaartermijn },
				{ key: 'archiefactiedatum', label: t('filinq', 'Disposal date'), value: retention.archiefactiedatum },
				{ key: 'archiefstatus', label: t('filinq', 'Archival status'), value: retention.archiefstatus },
				{ key: 'classificatie', label: t('filinq', 'Classification'), value: retention.classificatie },
			]
		},

		/**
		 * The supplement fields, each flagged when it is an unconfirmed proposal.
		 *
		 * @return {Array<{key: string, label: string, value: string, unconfirmed: boolean}>}
		 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/tasks.md#3-frontend
		 */
		supplementRows() {
			const supplement = this.status?.supplement || {}
			const unconfirmed = supplement.proposals || []
			const coverage = [supplement.dekkingInTijdBegin, supplement.dekkingInTijdEind].filter(Boolean).join(' – ')
			return [
				{ key: 'aggregatieniveau', label: t('filinq', 'Aggregation level'), value: supplement.aggregatieniveau, unconfirmed: unconfirmed.includes('aggregatieniveau') },
				{ key: 'omschrijving', label: t('filinq', 'Description'), value: supplement.omschrijving, unconfirmed: false },
				{ key: 'taal', label: t('filinq', 'Language'), value: supplement.taal, unconfirmed: false },
				{ key: 'dekkingInTijd', label: t('filinq', 'Coverage in time'), value: coverage, unconfirmed: unconfirmed.includes('dekkingInTijdBegin') },
				{ key: 'archiefvormer', label: t('filinq', 'Archive creator'), value: supplement.archiefvormerOverride || t('filinq', 'The organisation configured in OpenRegister'), unconfirmed: false },
			]
		},

		/**
		 * The derived field values the supplement lacks or holds unconfirmed.
		 *
		 * @return {Array<{field: string, label: string, value: string}>}
		 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/tasks.md#3-frontend
		 */
		fieldProposals() {
			const supplement = this.status?.supplement || {}
			const labels = {
				aggregatieniveau: t('filinq', 'Aggregation level'),
				dekkingInTijdBegin: t('filinq', 'Coverage start'),
				dekkingInTijdEind: t('filinq', 'Coverage end'),
			}
			return SCALAR_PROPOSALS.filter((field) => {
				const proposal = this.status?.proposals?.[field]
				return proposal
					&& (!supplement[field] || (supplement.proposals || []).includes(field))
			}).map((field) => ({ field, label: labels[field], value: this.status.proposals[field].value }))
		},

		/**
		 * The use restrictions Filinq proposes that nobody decided on yet.
		 *
		 * @return {object[]}
		 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/tasks.md#3-frontend
		 */
		restrictionProposals() {
			return openRestrictionProposals(this.status)
		},

		/**
		 * The confirmed use restrictions: the ones the sidecar exports.
		 *
		 * @return {object[]}
		 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/tasks.md#3-frontend
		 */
		confirmedRestrictions() {
			return (this.status?.supplement?.beperkingGebruik || []).filter((entry) => entry.proposed !== true)
		},

		/**
		 * The XML sidecar download.
		 *
		 * @return {string}
		 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/tasks.md#3-frontend
		 */
		xmlUrl() {
			return sidecarUrl(this.objectId, 'xml')
		},

		/**
		 * The JSON sidecar download.
		 *
		 * @return {string}
		 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/tasks.md#3-frontend
		 */
		jsonUrl() {
			return sidecarUrl(this.objectId, 'json')
		},
	},

	watch: {
		objectId: {
			/**
			 * Load the record's MDTO status.
			 *
			 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/tasks.md#3-frontend
			 */
			handler() {
				this.transferMessage = null
				this.load()
			},

			immediate: true,
		},
	},

	methods: {
		t,

		/**
		 * Load the status; a stale answer for another record is dropped. The
		 * parent hears whether the record was transferred, so it can switch
		 * its own changes off too.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/tasks.md#3-frontend
		 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-transfer-to-archive-state-for-permanent-records-req-ddare-006
		 */
		async load() {
			const objectId = this.objectId
			if (!objectId) {
				return
			}
			const result = await fetchMdtoStatus(objectId)
			if (objectId !== this.objectId) {
				return
			}
			this.loadError = result.ok ? '' : result.error
			this.status = result.ok ? result.data : null
			this.$emit('transferred', this.transferred)
		},

		/**
		 * One use restriction as text: its type, legal ground and description.
		 *
		 * @param {object} restriction The restriction.
		 * @return {string}
		 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/tasks.md#3-frontend
		 */
		restrictionLabel(restriction) {
			return [restriction.type, restriction.grondslag, restriction.omschrijving].filter(Boolean).join(' — ')
		},

		/**
		 * Accept a proposed field value.
		 *
		 * @param {{field: string, value: string}} proposal The proposal.
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/tasks.md#3-frontend
		 */
		accept(proposal) {
			return this.save(acceptProposal(this.status.supplement, proposal.field, proposal.value))
		},

		/**
		 * Accept or reject a proposed use restriction.
		 *
		 * @param {object} restriction The proposal.
		 * @param {boolean} accepted Whether it is accepted.
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/tasks.md#3-frontend
		 */
		decide(restriction, accepted) {
			return this.save(decideRestriction(this.status.supplement, restriction, accepted))
		},

		/**
		 * Save supplement changes and reload the verdict.
		 *
		 * @param {object} changes The fields to change.
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/tasks.md#3-frontend
		 */
		async save(changes) {
			this.busy = true
			const result = await saveSupplement(this.status.supplement || {}, changes, this.subject)
			this.busy = false
			if (!result.ok) {
				this.transferMessage = { type: 'error', text: result.error }
				return
			}
			await this.load()
		},

		/**
		 * After the form saved: close it and reload.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/tasks.md#3-frontend
		 */
		async onSaved() {
			this.editing = false
			await this.load()
		},

		/**
		 * Transfer the record and say what happened.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/tasks.md#3-frontend
		 */
		async transfer() {
			this.busy = true
			const result = await transferRecord(this.objectId)
			this.busy = false
			const outcome = result.ok ? result.data.status : 'error'
			const messages = {
				transferred: { type: 'success', text: t('filinq', 'The record was transferred to the e-depot. Its archival status is now overgebracht.') },
				manual: { type: 'info', text: t('filinq', 'No e-depot transport is configured in OpenRegister. Download the sidecar and deliver it to the e-depot by hand.') },
				blocked: { type: 'warning', text: t('filinq', 'This record cannot be transferred yet. Missing MDTO fields:') + ' ' + (result.data?.missing || []).join(', ') },
				failed: { type: 'error', text: t('filinq', 'The e-depot did not confirm the transfer. The record was not changed.') },
				error: { type: 'error', text: result.error },
			}
			this.transferMessage = messages[outcome] || messages.failed
			await this.load()
		},
	},
}
</script>

<style scoped>
.mdto-panel {
	display: flex;
	flex-direction: column;
	gap: calc(var(--default-grid-baseline, 4px) * 2);
}

.mdto-panel__core,
.mdto-panel__supplement {
	display: grid;
	grid-template-columns: max-content 1fr;
	gap: var(--default-grid-baseline, 4px) calc(var(--default-grid-baseline, 4px) * 3);
	margin: 0;
}

.mdto-panel__core dt,
.mdto-panel__supplement dt {
	color: var(--color-text-maxcontrast);
}

.mdto-panel__core dd,
.mdto-panel__supplement dd {
	margin: 0;
}

.mdto-panel__unconfirmed {
	color: var(--color-warning-text);
}

.mdto-panel__proposals,
.mdto-panel__restrictions,
.mdto-panel__missing {
	margin: 0;
	padding-inline-start: 0;
	list-style: none;
}

.mdto-panel__chip {
	display: flex;
	flex-wrap: wrap;
	align-items: center;
	gap: var(--default-grid-baseline, 4px);
	padding: var(--default-grid-baseline, 4px) calc(var(--default-grid-baseline, 4px) * 2);
	border: 1px solid var(--color-border-dark);
	border-radius: var(--border-radius-large);
	margin-block-end: var(--default-grid-baseline, 4px);
}

.mdto-panel__actions {
	display: flex;
	flex-wrap: wrap;
	gap: calc(var(--default-grid-baseline, 4px) * 2);
}
</style>
