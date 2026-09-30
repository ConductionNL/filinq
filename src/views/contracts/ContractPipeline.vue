<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2
@spec openspec/changes/contract-lifecycle-management/tasks.md#3-2
@visual exclude No pixel baseline yet: the page is driven through its /contracts/pipeline route by
	tests/e2e/spec-coverage/contracts.spec.ts, which reaches it by URL. Its buckets move with the
	date, so a baseline needs a fixed clock.
-->
<template>
	<div class="contract-pipeline">
		<div class="contract-pipeline__header">
			<div>
				<h2>{{ t('filinq', 'Renewal pipeline') }}</h2>
				<p class="contract-pipeline__subtitle">
					{{
						t(
							'filinq',
							'Active contracts by what needs attention first: ended, notice due, ending soon, and later.',
						)
					}}
				</p>
			</div>
			<NcButton variant="secondary" @click="$router.push({ name: 'Contracts' })">
				{{ t('filinq', 'All contracts') }}
			</NcButton>
		</div>

		<NcNoteCard v-if="error" type="error">
			{{ error }}
		</NcNoteCard>
		<NcNoteCard v-if="notice" type="success">
			{{ notice }}
		</NcNoteCard>
		<NcLoadingIcon v-if="loading && contracts.length === 0" :size="32" />
		<NcEmptyContent
			v-else-if="!loading && activeCount === 0 && !error"
			:name="t('filinq', 'No active contracts')"
			:description="t('filinq', 'A contract shows here once it is put in force.')" />
		<template v-else>
			<section
				v-for="bucket in BUCKETS"
				:key="bucket"
				class="contract-pipeline__bucket"
				:data-testid="`contract-bucket-${bucket}`">
				<h3>
					{{ bucketLabel(bucket) }}
					<span class="contract-pipeline__count">{{ buckets[bucket].length }}</span>
				</h3>
				<p v-if="buckets[bucket].length === 0" class="contract-pipeline__none">
					{{ t('filinq', 'None') }}
				</p>
				<table v-else class="contract-pipeline__table">
					<thead>
						<tr>
							<th scope="col">{{ t('filinq', 'Contract') }}</th>
							<th scope="col">{{ t('filinq', 'Notice deadline') }}</th>
							<th scope="col">{{ t('filinq', 'End date') }}</th>
							<th scope="col">{{ t('filinq', 'Actions') }}</th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="contract in buckets[bucket]" :key="contractId(contract)">
							<td>
								<router-link
									:to="{ name: 'ContractDetail', params: { id: contractId(contract) } }">
									{{ contract.title }}
								</router-link>
							</td>
							<td>{{ contract.noticeDeadline || '-' }}</td>
							<td>{{ contract.endDate || '-' }}</td>
							<td class="contract-pipeline__actions">
								<NcButton
									variant="secondary"
									:disabled="busy"
									:aria-label="t('filinq', 'Renew {title}', { title: contract.title })"
									@click="renew(contract)">
									{{ t('filinq', 'Renew') }}
								</NcButton>
								<NcButton
									variant="tertiary"
									:disabled="busy"
									:aria-label="t('filinq', 'End {title}', { title: contract.title })"
									@click="ending = contract">
									{{ t('filinq', 'End contract') }}
								</NcButton>
							</td>
						</tr>
					</tbody>
				</table>
			</section>
		</template>

		<TerminateContractDialog
			v-if="ending"
			:contractId="contractId(ending)"
			:contractTitle="ending.title"
			@close="ending = null"
			@terminated="onTerminated" />
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import {
	NcButton,
	NcEmptyContent,
	NcLoadingIcon,
	NcNoteCard,
} from '@nextcloud/vue'
import TerminateContractDialog from '../../dialogs/TerminateContractDialog.vue'
import { bucketLabel } from '../../services/contractLabels.js'
import { BUCKETS, bucketContracts } from '../../services/contractPipeline.js'
import {
	contractId,
	listContracts,
	renewContract,
} from '../../services/contracts.js'

/**
 * The renewal pipeline: the active contracts the caller can read, bucketed
 * by urgency in the browser, with renew and end on every row.
 *
 * @spec openspec/changes/contract-lifecycle-management/tasks.md#3-2
 */
export default {
	name: 'ContractPipeline',
	components: {
		NcButton,
		NcEmptyContent,
		NcLoadingIcon,
		NcNoteCard,
		TerminateContractDialog,
	},

	data() {
		return {
			BUCKETS,
			contracts: [],
			loading: true,
			busy: false,
			error: '',
			notice: '',
			ending: null,
		}
	},

	computed: {
		/**
		 * The contracts per bucket.
		 *
		 * @return {object}
		 * @spec openspec/changes/contract-lifecycle-management/tasks.md#3-2
		 */
		buckets() {
			return bucketContracts(this.contracts)
		},

		/**
		 * How many contracts are in any bucket.
		 *
		 * @return {number}
		 * @spec openspec/changes/contract-lifecycle-management/tasks.md#3-2
		 */
		activeCount() {
			return BUCKETS.reduce((sum, bucket) => sum + this.buckets[bucket].length, 0)
		},
	},

	/**
	 * Load on mount.
	 *
	 * @return {Promise<void>}
	 * @spec openspec/changes/contract-lifecycle-management/tasks.md#3-2
	 */
	mounted() {
		return this.load()
	},

	methods: {
		t,
		bucketLabel,
		contractId,
		/**
		 * Read the contracts.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/contract-lifecycle-management/tasks.md#3-2
		 */
		async load() {
			this.loading = true
			const answer = await listContracts()
			this.loading = false
			if (!answer.ok) {
				this.error = answer.error
				return
			}
			this.contracts = answer.data
		},

		/**
		 * Renew a contract; its draft successor is not active, so it leaves the buckets.
		 *
		 * @param {object} contract The contract.
		 * @return {Promise<void>}
		 * @spec openspec/changes/contract-lifecycle-management/tasks.md#3-2
		 */
		async renew(contract) {
			this.busy = true
			this.error = ''
			this.notice = ''
			const answer = await renewContract(contractId(contract))
			this.busy = false
			if (!answer.ok) {
				this.error = answer.error
				return
			}
			this.notice = t('filinq', '"{title}" is renewed. Its successor is a draft.', { title: contract.title })
			await this.load()
		},

		/**
		 * After the end dialog.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/contract-lifecycle-management/tasks.md#3-2
		 */
		async onTerminated() {
			this.notice = t('filinq', '"{title}" has ended.', { title: this.ending.title })
			this.ending = null
			await this.load()
		},
	},
}
</script>

<style scoped>
.contract-pipeline {
	padding: calc(var(--default-grid-baseline) * 5);
	max-width: 1100px;
}

.contract-pipeline__header {
	display: flex;
	flex-wrap: wrap;
	justify-content: space-between;
	gap: calc(var(--default-grid-baseline) * 3);
}

.contract-pipeline__subtitle,
.contract-pipeline__none {
	color: var(--color-text-maxcontrast);
}

.contract-pipeline__bucket {
	margin-top: calc(var(--default-grid-baseline) * 6);
}

.contract-pipeline__count {
	display: inline-block;
	min-width: 24px;
	margin-inline-start: calc(var(--default-grid-baseline) * 2);
	padding: 0 8px;
	border-radius: var(--border-radius-pill);
	background-color: var(--color-background-dark);
	text-align: center;
	font-weight: normal;
}

.contract-pipeline__table {
	width: 100%;
	border-collapse: collapse;
}

.contract-pipeline__table th,
.contract-pipeline__table td {
	padding: calc(var(--default-grid-baseline) * 2);
	text-align: start;
	border-bottom: 1px solid var(--color-border);
}

.contract-pipeline__actions {
	display: flex;
	flex-wrap: wrap;
	gap: calc(var(--default-grid-baseline) * 2);
}
</style>
