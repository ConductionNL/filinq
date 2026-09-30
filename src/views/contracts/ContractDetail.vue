<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2
@spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#3-1
@visual exclude No pixel baseline yet: the page is driven through its /contracts/:id route by
	tests/e2e/workflows/contract-lifecycle.spec.ts and tests/e2e/spec-coverage/contracts.spec.ts, which
	reach it by URL. A baseline needs the seeded contracts on a running instance.
-->
<template>
	<div class="contract-detail">
		<NcLoadingIcon v-if="loading && !contract" :size="44" />
		<NcNoteCard v-else-if="!contract" type="error">
			{{
				error
				|| t('filinq', 'This contract is not there, or you cannot see it.')
			}}
		</NcNoteCard>
		<template v-else>
			<div class="contract-detail__header">
				<div>
					<h2>{{ contract.title }}</h2>
					<span
						class="contract-detail__status"
						:class="`contract-detail__status--${shownStatus}`"
						data-testid="contract-status">
						{{ statusLabel(shownStatus) }}
					</span>
				</div>
				<div class="contract-detail__actions">
					<NcButton
						v-if="contract.status === 'draft'"
						variant="primary"
						:disabled="busy"
						data-testid="contract-activate"
						@click="activate">
						{{ t('filinq', 'Put in force') }}
					</NcButton>
					<NcButton
						v-if="contract.status === 'active'"
						variant="primary"
						:disabled="busy"
						data-testid="contract-renew"
						@click="renew">
						{{ t('filinq', 'Renew') }}
					</NcButton>
					<NcButton
						v-if="contract.status === 'active'"
						variant="warning"
						:disabled="busy"
						data-testid="contract-terminate"
						@click="terminating = true">
						{{ t('filinq', 'End contract') }}
					</NcButton>
					<NcButton
						variant="tertiary"
						@click="$router.push({ name: 'ContractPipeline' })">
						{{ t('filinq', 'Renewal pipeline') }}
					</NcButton>
				</div>
			</div>

			<NcNoteCard v-if="error" type="error">
				{{ error }}
			</NcNoteCard>
			<NcNoteCard v-if="notice" type="success">
				{{ notice }}
			</NcNoteCard>

			<section class="contract-detail__section">
				<h3>{{ t('filinq', 'Terms') }}</h3>
				<dl class="contract-detail__terms">
					<template v-for="term in terms" :key="term.key">
						<dt>{{ term.label }}</dt>
						<dd :data-testid="`contract-term-${term.key}`">
							{{ term.value || '-' }}
						</dd>
					</template>
				</dl>
				<p v-if="contract.renews" class="contract-detail__link">
					{{ t('filinq', 'This contract renews an earlier one.') }}
					<router-link
						:to="{
							name: 'ContractDetail',
							params: { id: contract.renews },
						}">
						{{ t('filinq', 'Open the earlier contract') }}
					</router-link>
				</p>
				<p v-if="contract.renewedBy" class="contract-detail__link">
					{{ t('filinq', 'A later contract replaces this one.') }}
					<router-link
						:to="{
							name: 'ContractDetail',
							params: { id: contract.renewedBy },
						}"
						data-testid="contract-successor-link">
						{{ t('filinq', 'Open the later contract') }}
					</router-link>
				</p>
			</section>

			<section class="contract-detail__section">
				<h3>{{ t('filinq', 'Parties') }}</h3>
				<p v-if="parties.length === 0">
					{{ t('filinq', 'No parties yet.') }}
				</p>
				<ul
					v-else
					class="contract-detail__list"
					data-testid="contract-parties">
					<li v-for="(party, index) in parties" :key="index">
						<strong>{{
							party.displayName || t('filinq', 'Unnamed party')
						}}</strong>
						<span v-if="party.role"> ({{ party.role }})</span>
						<span v-if="party.linked" class="contract-detail__hint">
							{{ t('filinq', 'from contacts') }}
							<template v-if="party.email"
								>, {{ party.email }}</template
							>
						</span>
					</li>
				</ul>
			</section>

			<section class="contract-detail__section">
				<h3>{{ t('filinq', 'Documents') }}</h3>
				<p v-if="documents.length === 0">
					{{ t('filinq', 'No documents yet.') }}
				</p>
				<ul
					v-else
					class="contract-detail__list"
					data-testid="contract-documents">
					<li v-for="fileId in documents" :key="fileId">
						<a :href="fileUrl(fileId)" target="_blank" rel="noopener">
							{{ t('filinq', 'File {id}', { id: fileId }) }}
						</a>
						<span
							v-if="
								String(contract.signedDocumentRef || '') === fileId
							"
							class="contract-detail__hint">
							{{ t('filinq', 'signed') }}
						</span>
					</li>
				</ul>
				<div v-if="editable" class="contract-detail__actions">
					<NcButton
						:disabled="busy"
						data-testid="contract-attach"
						@click="attach">
						{{ t('filinq', 'Attach files') }}
					</NcButton>
					<NcButton
						:disabled="busy"
						data-testid="contract-generate"
						@click="generating = true">
						{{ t('filinq', 'Generate from a template') }}
					</NcButton>
					<NcButton
						:disabled="busy || documents.length === 0"
						data-testid="contract-send-for-signature"
						@click="sending = true">
						{{ t('filinq', 'Send for signature') }}
					</NcButton>
				</div>
			</section>

			<section class="contract-detail__section">
				<h3>{{ t('filinq', 'Signing') }}</h3>
				<p v-if="!contract.signingRequestRef">
					{{ t('filinq', 'No document was sent for signature yet.') }}
				</p>
				<p v-else data-testid="contract-signing-status">
					<template v-if="signing && signing.signed">
						{{
							t(
								'filinq',
								'Signed. The signed document is linked to the contract.',
							)
						}}
					</template>
					<template v-else-if="signing">
						{{
							t('filinq', 'Signing request status: {status}', {
								status: signing.status,
							})
						}}
					</template>
					<template v-else>
						{{ t('filinq', 'The signing request could not be read.') }}
					</template>
					<router-link
						:to="{
							name: 'SigningRequestDetail',
							params: { id: contract.signingRequestRef },
						}">
						{{ t('filinq', 'Open the signing request') }}
					</router-link>
				</p>
			</section>

			<section
				v-if="extractionEnabled"
				class="contract-detail__section"
				data-testid="contract-suggestions">
				<h3>{{ t('filinq', 'Suggested terms') }}</h3>
				<p class="contract-detail__hint">
					{{
						t(
							'filinq',
							'Read from the contract documents. A suggestion changes nothing until you accept it.',
						)
					}}
				</p>
				<p v-if="suggestions.length === 0">
					{{ t('filinq', 'No suggestions yet.') }}
				</p>
				<table v-else class="contract-detail__table">
					<thead>
						<tr>
							<th scope="col">{{ t('filinq', 'Field') }}</th>
							<th scope="col">{{ t('filinq', 'Value') }}</th>
							<th scope="col">{{ t('filinq', 'Confidence') }}</th>
							<th scope="col">{{ t('filinq', 'Decision') }}</th>
						</tr>
					</thead>
					<tbody>
						<tr
							v-for="row in suggestions"
							:key="row.index"
							:data-testid="`contract-suggestion-${row.field}`">
							<td>{{ suggestionFieldLabel(row.field) }}</td>
							<td>{{ row.value }}</td>
							<td>{{ confidence(row.confidence) }}</td>
							<td>
								<template
									v-if="row.status === 'proposed' && editable">
									<NcButton
										variant="primary"
										:disabled="busy"
										:aria-label="
											t('filinq', 'Accept {field}', {
												field: suggestionFieldLabel(
													row.field,
												),
											})
										"
										@click="decide(row.index, 'accepted')">
										{{ t('filinq', 'Accept') }}
									</NcButton>
									<NcButton
										variant="tertiary"
										:disabled="busy"
										:aria-label="
											t('filinq', 'Reject {field}', {
												field: suggestionFieldLabel(
													row.field,
												),
											})
										"
										@click="decide(row.index, 'rejected')">
										{{ t('filinq', 'Reject') }}
									</NcButton>
								</template>
								<span v-else>{{ decisionLabel(row.status) }}</span>
							</td>
						</tr>
					</tbody>
				</table>
				<NcButton
					v-if="editable && documents.length > 0"
					:disabled="busy"
					data-testid="contract-read-terms"
					@click="readTerms">
					{{ t('filinq', 'Read the documents for terms') }}
				</NcButton>
			</section>
		</template>

		<TerminateContractDialog
			v-if="terminating"
			:contractId="id"
			:contractTitle="contract ? contract.title : ''"
			@close="terminating = false"
			@terminated="onTerminated" />
		<GenerateContractDocumentDialog
			v-if="generating"
			:contractId="id"
			:contractTitle="contract ? contract.title : ''"
			@close="generating = false"
			@generated="onGenerated" />
		<SendContractForSignatureDialog
			v-if="sending"
			:contractId="id"
			:contractTitle="contract ? contract.title : ''"
			:documents="documents"
			@close="sending = false"
			@sent="onSent" />
	</div>
</template>

<script>
import { getFilePickerBuilder } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { NcButton, NcLoadingIcon, NcNoteCard } from '@nextcloud/vue'
import GenerateContractDocumentDialog from '../../dialogs/GenerateContractDocumentDialog.vue'
import SendContractForSignatureDialog from '../../dialogs/SendContractForSignatureDialog.vue'
import TerminateContractDialog from '../../dialogs/TerminateContractDialog.vue'
import {
	money,
	statusLabel,
	suggestionFieldLabel,
} from '../../services/contractLabels.js'
import { displayStatus } from '../../services/contractPipeline.js'
import {
	activateContract,
	attachDocuments,
	contractParties,
	decideSuggestion,
	getContract,
	linkSigningRequest,
	renewContract,
	suggestTerms,
} from '../../services/contracts.js'
import { useSettingsStore } from '../../store/modules/settings.js'

/**
 * One contract: its terms, parties, documents, signing status and suggested
 * terms, with the lifecycle actions. The contract is read from OpenRegister's
 * object API as the caller; the actions go through the app's routes.
 *
 * @spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#3-1
 */
export default {
	name: 'ContractDetail',
	components: {
		GenerateContractDocumentDialog,
		NcButton,
		NcLoadingIcon,
		NcNoteCard,
		SendContractForSignatureDialog,
		TerminateContractDialog,
	},

	props: {
		/** The contract, named after the route parameter. */
		id: { type: String, required: true },
	},

	data() {
		return {
			contract: null,
			parties: [],
			signing: null,
			loading: true,
			busy: false,
			error: '',
			notice: '',
			terminating: false,
			generating: false,
			sending: false,
		}
	},

	computed: {
		/**
		 * The status shown: an active contract past its end date shows expired.
		 *
		 * @return {string}
		 * @spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#3-1
		 */
		shownStatus() {
			return displayStatus(this.contract)
		},

		/**
		 * Whether documents and suggestions can still change.
		 *
		 * @return {boolean}
		 * @spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#3-1
		 */
		editable() {
			return ['draft', 'active'].includes(this.contract?.status)
		},

		/**
		 * The contract's document file ids.
		 *
		 * @return {Array<string>}
		 * @spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#3-1
		 */
		documents() {
			return (this.contract?.documents || []).map(String)
		},

		/**
		 * Whether suggested terms are read at all (the admin setting).
		 *
		 * @return {boolean}
		 * @spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#3-1
		 */
		extractionEnabled() {
			return (
				useSettingsStore().config?.enable_contract_term_extraction !== false
			)
		},

		/**
		 * The suggestions with their position, which the decision route takes.
		 *
		 * @return {Array<object>}
		 * @spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#3-1
		 */
		suggestions() {
			return (this.contract?.keyTermSuggestions || []).map((row, index) => ({
				...row,
				index,
			}))
		},

		/**
		 * The terms as label and shown value.
		 *
		 * @return {Array<object>}
		 * @spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#3-1
		 */
		terms() {
			const c = this.contract || {}
			const rows = [
				{
					key: 'contractType',
					label: t('filinq', 'Type'),
					value: c.contractType,
				},
				{
					key: 'internalOwner',
					label: t('filinq', 'Owner'),
					value: c.internalOwner,
				},
				{
					key: 'startDate',
					label: t('filinq', 'Start date'),
					value: c.startDate,
				},
				{ key: 'endDate', label: t('filinq', 'End date'), value: c.endDate },
				{
					key: 'noticePeriodDays',
					label: t('filinq', 'Notice period in days'),
					value:
						c.noticePeriodDays === null
						|| c.noticePeriodDays === undefined
							? ''
							: String(c.noticePeriodDays),
				},
				{
					key: 'noticeDeadline',
					label: t('filinq', 'Notice deadline'),
					value: c.noticeDeadline,
				},
				{
					key: 'value',
					label: t('filinq', 'Value'),
					value: money(c.value, c.currency),
				},
				{
					key: 'renewalType',
					label: t('filinq', 'Renewal'),
					value:
						c.renewalType === 'manual'
							? t('filinq', 'Renewed by hand')
							: t('filinq', 'Not renewed'),
				},
			]
			if (c.terminationReason) {
				rows.push({
					key: 'terminationReason',
					label: t('filinq', 'Reason for ending'),
					value: c.terminationReason,
				})
			}
			return rows
		},
	},

	watch: {
		/**
		 * Load the next contract when the route moves to it (after a renewal).
		 *
		 * @spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#3-1
		 */
		id() {
			this.load()
		},
	},

	/**
	 * Load the contract on mount.
	 *
	 * @return {Promise<void>}
	 * @spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#3-1
	 */
	mounted() {
		return this.load()
	},

	methods: {
		t,
		statusLabel,
		suggestionFieldLabel,
		/**
		 * Read the contract, its parties and its signing status.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#3-1
		 */
		async load() {
			this.loading = true
			this.error = ''
			const answer = await getContract(this.id)
			if (!answer.ok) {
				this.loading = false
				this.contract = null
				this.error =
					answer.status === 404
						? t(
								'filinq',
								'This contract is not there, or you cannot see it.',
							)
						: answer.error
				return
			}
			this.contract = answer.data
			const parties = await contractParties(this.id)
			this.parties = parties.ok ? parties.data : this.contract.parties || []
			await this.readSigning()
			this.loading = false
		},

		/**
		 * The signing request's status; a completed request links the signed document back.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#3-1
		 */
		async readSigning() {
			this.signing = null
			if (!this.contract?.signingRequestRef) {
				return
			}
			const answer = await linkSigningRequest(
				this.id,
				this.contract.signingRequestRef,
			)
			if (answer.ok) {
				this.signing = answer.data.signingRequest
				this.contract = { ...this.contract, ...answer.data.contract }
			}
		},

		/**
		 * Run an action, show its refusal, reload on success.
		 *
		 * @param {() => Promise<object>} call The action.
		 * @param {string} done The sentence shown after it.
		 * @return {Promise<object|null>} The data, or null.
		 * @spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#3-1
		 */
		async act(call, done) {
			this.busy = true
			this.error = ''
			this.notice = ''
			const answer = await call()
			this.busy = false
			if (!answer.ok) {
				this.error = answer.error
				return null
			}
			this.notice = done
			return answer.data
		},

		/**
		 * Put the draft in force.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#3-1
		 */
		async activate() {
			if (
				await this.act(
					() => activateContract(this.contract),
					t('filinq', 'The contract is in force.'),
				)
			) {
				await this.load()
			}
		},

		/**
		 * Renew and open the successor draft.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#3-1
		 */
		async renew() {
			const data = await this.act(
				() => renewContract(this.id),
				t('filinq', 'Renewed. This is the new draft contract.'),
			)
			const successor = data?.successor?.uuid || data?.successor?.id
			if (successor) {
				this.$router.push({
					name: 'ContractDetail',
					params: { id: String(successor) },
				})
			} else if (data) {
				await this.load()
			}
		},

		/**
		 * After the end dialog.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#3-1
		 */
		async onTerminated() {
			this.terminating = false
			this.notice = t('filinq', 'The contract has ended.')
			await this.load()
		},

		/**
		 * Pick files and add them to the contract; then read them for terms.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#3-1
		 */
		async attach() {
			let nodes
			try {
				nodes = await getFilePickerBuilder(
					t('filinq', 'Attach files to the contract'),
				)
					.setMultiSelect(true)
					.allowDirectories(false)
					.build()
					.pickNodes()
			} catch {
				return
			}
			const fileIds = nodes
				.map((node) => node.fileid)
				.filter((id) => id !== undefined && id !== null)
			await this.addDocuments(fileIds, t('filinq', 'The files are attached.'))
		},

		/**
		 * After the generate dialog.
		 *
		 * @param {string} fileId The generated file.
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#3-1
		 */
		async onGenerated(fileId) {
			this.generating = false
			await this.addDocuments(
				[fileId],
				t('filinq', 'The document is generated and attached.'),
			)
		},

		/**
		 * Store new documents on the contract, then propose terms from them.
		 *
		 * @param {Array<string|number>} fileIds The files.
		 * @param {string} done The sentence shown after it.
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#3-1
		 */
		async addDocuments(fileIds, done) {
			if (fileIds.length === 0) {
				return
			}
			if (
				await this.act(() => attachDocuments(this.contract, fileIds), done)
			) {
				if (this.extractionEnabled) {
					await suggestTerms(this.id)
				}
				await this.load()
			}
		},

		/**
		 * After the send-for-signature dialog.
		 *
		 * @param {object} data { contract, signingRequest }.
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#3-1
		 */
		async onSent(data) {
			this.sending = false
			this.notice = t(
				'filinq',
				'The signing request is created and linked to the contract.',
			)
			this.contract = { ...this.contract, ...data.contract }
			this.signing = data.signingRequest
		},

		/**
		 * Read the documents for suggested terms now.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#3-1
		 */
		async readTerms() {
			const data = await this.act(() => suggestTerms(this.id), '')
			if (data) {
				this.notice =
					data.added > 0
						? t('filinq', 'New suggestions: {count}', {
								count: data.added,
							})
						: t('filinq', 'No new suggestions in the documents.')
				await this.load()
			}
		},

		/**
		 * Accept or reject one suggestion.
		 *
		 * @param {number} index The suggestion.
		 * @param {string} decision accepted or rejected.
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#3-1
		 */
		async decide(index, decision) {
			const done =
				decision === 'accepted'
					? t('filinq', 'Accepted. The value is written on the contract.')
					: t('filinq', 'Rejected. The contract is unchanged.')
			if (
				await this.act(
					() => decideSuggestion(this.id, index, decision),
					done,
				)
			) {
				await this.load()
			}
		},

		/**
		 * The link to a file in Files.
		 *
		 * @param {string} fileId The file.
		 * @return {string} The URL.
		 * @spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#3-1
		 */
		fileUrl(fileId) {
			return generateUrl('/f/{fileId}', { fileId })
		},

		/**
		 * A confidence as a percentage.
		 *
		 * @param {number} value 0 to 1.
		 * @return {string}
		 * @spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#3-1
		 */
		confidence(value) {
			return typeof value === 'number' ? `${Math.round(value * 100)}%` : '-'
		},

		/**
		 * A decided suggestion's state.
		 *
		 * @param {string} status accepted, rejected or proposed.
		 * @return {string}
		 * @spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#3-1
		 */
		decisionLabel(status) {
			return (
				{
					accepted: t('filinq', 'Accepted'),
					rejected: t('filinq', 'Rejected'),
					proposed: t('filinq', 'Proposed'),
				}[status] || status
			)
		},
	},
}
</script>

<style scoped>
.contract-detail {
	padding: calc(var(--default-grid-baseline) * 5);
	max-width: 960px;
}

.contract-detail__header {
	display: flex;
	flex-wrap: wrap;
	justify-content: space-between;
	gap: calc(var(--default-grid-baseline) * 3);
}

.contract-detail__actions {
	display: flex;
	flex-wrap: wrap;
	gap: calc(var(--default-grid-baseline) * 2);
	margin-top: calc(var(--default-grid-baseline) * 2);
}

.contract-detail__status {
	display: inline-block;
	padding: 2px 10px;
	border-radius: var(--border-radius-pill);
	background-color: var(--color-background-dark);
	color: var(--color-main-text);
}

.contract-detail__status--active {
	background-color: var(--color-success);
	color: var(--color-primary-element-text);
}

.contract-detail__status--expired,
.contract-detail__status--terminated {
	background-color: var(--color-error);
	color: var(--color-primary-element-text);
}

.contract-detail__section {
	margin-top: calc(var(--default-grid-baseline) * 6);
}

.contract-detail__terms {
	display: grid;
	grid-template-columns: max-content 1fr;
	gap: calc(var(--default-grid-baseline) * 2)
		calc(var(--default-grid-baseline) * 6);
}

.contract-detail__terms dt {
	font-weight: bold;
}

.contract-detail__terms dd {
	margin: 0;
}

.contract-detail__list {
	list-style: disc;
	padding-inline-start: calc(var(--default-grid-baseline) * 5);
}

.contract-detail__hint {
	color: var(--color-text-maxcontrast);
}

.contract-detail__table {
	width: 100%;
	border-collapse: collapse;
	margin-bottom: calc(var(--default-grid-baseline) * 3);
}

.contract-detail__table th,
.contract-detail__table td {
	padding: calc(var(--default-grid-baseline) * 2);
	text-align: start;
	border-bottom: 1px solid var(--color-border);
}
</style>
