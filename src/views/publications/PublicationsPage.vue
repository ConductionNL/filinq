<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2
@spec openspec/specs/woo-publicatie-pipeline/spec.md
-->
<template>
	<div class="publications">
		<NcNoteCard v-if="error" type="error">
			<p>{{ error }}</p>
			<ul v-if="reasons.length">
				<li v-for="reason in reasons" :key="reason">
					{{ reason }}
				</li>
			</ul>
		</NcNoteCard>
		<NcNoteCard v-if="!platformAvailable" type="warning">
			{{
				t(
					'filinq',
					'OpenCatalogi is not installed, so nothing can be handed off for publication yet.',
				)
			}}
		</NcNoteCard>

		<!-- The list -->
		<template v-if="!id">
			<h2>{{ t('filinq', 'Publications') }}</h2>
			<p class="publications__muted">
				{{
					t(
						'filinq',
						'Documents on their way to the publication platform. Start one from a document with Publish.',
					)
				}}
			</p>
			<div class="publications__worklist">
				<label class="publications__filter">
					<input
						v-model="onlyIncomplete"
						type="checkbox"
						data-testid="publications-incomplete"
						@change="load">
					{{ t('filinq', 'Metadata incomplete') }}
				</label>
				<NcButton
					:disabled="selected.length === 0 || busy"
					data-testid="publications-set-metadata"
					@click="bulkOpen = true">
					{{ t('filinq', 'Set metadata') }}
				</NcButton>
			</div>
			<PublicationBulkMetadataDialog
				v-if="bulkOpen"
				:categories="categories"
				:busy="busy"
				@cancel="bulkOpen = false"
				@confirm="setMetadata" />
			<NcNoteCard v-if="bulkResult" type="info" data-testid="publications-bulk-result">
				<p>{{ t('filinq', 'Updated') }}: {{ bulkResult.updated.length }}</p>
				<p v-if="bulkResult.skipped.length">
					{{ t('filinq', 'Skipped') }}: {{ bulkResult.skipped.length }}
				</p>
				<ul v-if="bulkResult.skipped.length">
					<li v-for="skip in bulkResult.skipped" :key="skip.uuid">
						{{ titleOf(skip.uuid) }}: {{ skip.reason }}
					</li>
				</ul>
			</NcNoteCard>
			<NcLoadingIcon v-if="loading" :size="32" />
			<NcEmptyContent
				v-else-if="records.length === 0"
				:name="t('filinq', 'No publications yet')" />
			<table v-else class="publications__table">
				<thead>
					<tr>
						<th scope="col">
							<input
								type="checkbox"
								:aria-label="t('filinq', 'Select all')"
								:checked="allSelected"
								@change="toggleAll($event.target.checked)">
						</th>
						<th scope="col">
							{{ t('filinq', 'Official title') }}
						</th>
						<th scope="col">
							{{ t('filinq', 'Status') }}
						</th>
						<th scope="col">
							{{ t('filinq', 'Checks passed') }}
						</th>
						<th scope="col">
							{{ t('filinq', 'Missing metadata') }}
						</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="row in records" :key="row.uuid">
						<td>
							<input
								v-model="selected"
								type="checkbox"
								:value="row.uuid"
								:aria-label="t('filinq', 'Select')">
						</td>
						<td>
							<router-link
								:to="{
									name: 'Publications',
									params: { id: row.uuid },
								}">
								{{
									row.officieleTitel
									|| t('filinq', 'Document {id}', {
										id: row.documentFileRef,
									})
								}}
							</router-link>
						</td>
						<td>{{ statusLabel(row.status) }}</td>
						<td>{{ passed(row) }} / 3</td>
						<td :data-missing="missingOf(row).length">
							{{
								missingOf(row).length
									? missingOf(row).join(', ')
									: t('filinq', 'Complete')
							}}
						</td>
					</tr>
				</tbody>
			</table>
		</template>

		<!-- One publication -->
		<template v-else-if="record">
			<router-link :to="{ name: 'Publications' }">
				{{ t('filinq', 'All publications') }}
			</router-link>
			<h2>
				{{
					record.officieleTitel
					|| t('filinq', 'Document {id}', { id: record.documentFileRef })
				}}
			</h2>
			<p>
				<strong>{{ statusLabel(record.status) }}</strong>
			</p>

			<section>
				<h3>{{ t('filinq', 'May it be published?') }}</h3>
				<ol class="publications__checks">
					<li
						v-for="check in checks"
						:key="check.key"
						:data-ok="check.ok ? 'yes' : 'no'">
						{{ check.ok ? t('filinq', 'Yes') : t('filinq', 'No') }}:
						{{ check.label }}
						<router-link v-if="!check.ok" :to="{ name: check.route }">
							{{ t('filinq', 'Resolve this') }}
						</router-link>
					</li>
				</ol>
				<NcNoteCard
					v-if="accessibilityWarning"
					type="warning"
					class="publications__accessibility">
					<p>{{ accessibilityWarning.title }}</p>
					<p>{{ accessibilityWarning.detail }}</p>
				</NcNoteCard>
				<ul v-if="record.readinessReasons && record.readinessReasons.length">
					<li v-for="reason in record.readinessReasons" :key="reason">
						{{ reason }}
					</li>
				</ul>
				<p class="publications__muted">
					{{
						t('filinq', 'Checked at {time}', {
							time: formatTime(record.readinessEvaluatedAt),
						})
					}}
				</p>
				<NcButton :disabled="busy" @click="step('readiness')">
					{{ t('filinq', 'Check again') }}
				</NcButton>
			</section>

			<section>
				<h3>{{ t('filinq', 'Woo metadata') }}</h3>
				<form class="publications__form" @submit.prevent="saveMetadata">
					<NcTextField
						v-model="form.officieleTitel"
						:label="t('filinq', 'Official title')" />
					<label for="publication-category">{{
						t('filinq', 'Information category')
					}}</label>
					<select id="publication-category" v-model="form.wooCategory">
						<option value="">
							{{ t('filinq', 'Choose a category') }}
						</option>
						<option
							v-for="category in categories"
							:key="category.code"
							:value="category.code">
							{{ category.label }}
						</option>
					</select>
					<NcTextField
						v-model="form.documentsoort"
						:label="t('filinq', 'Document type')" />
					<NcTextField
						v-model="form.publisher"
						:label="t('filinq', 'Publisher (TOOI identifier)')" />
					<label for="publication-created">{{
						t('filinq', 'Created on')
					}}</label>
					<input
						id="publication-created"
						v-model="form.creatiedatum"
						type="date" />
					<label for="publication-date">{{
						t('filinq', 'Publish on')
					}}</label>
					<input
						id="publication-date"
						v-model="form.publicatiedatum"
						type="date" />
					<template v-if="accessibilityWarning">
						<label for="publication-accessibility-override">{{
							t('filinq', 'Why may it be published anyway?')
						}}</label>
						<textarea
							id="publication-accessibility-override"
							v-model="form.accessibilityOverrideReason"
							rows="3" />
					</template>
					<NcButton type="submit" :disabled="busy">
						{{ t('filinq', 'Save metadata') }}
					</NcButton>
				</form>
			</section>

			<section>
				<h3>{{ t('filinq', 'Hand off') }}</h3>
				<NcNoteCard
					v-if="sanitized === false"
					type="warning"
					data-testid="publication-not-sanitized">
					{{
						t(
							'filinq',
							'This file was not sanitized. Comments, tracked changes or author names may still be hidden in it. You can hand it off anyway.',
						)
					}}
					<router-link :to="{ name: 'MyDocuments' }">
						{{ t('filinq', 'Sanitize it in My documents') }}
					</router-link>
				</NcNoteCard>
				<p v-if="missing.length" class="publications__muted">
					{{
						t('filinq', 'Still missing: {fields}', {
							fields: missing.join(', '),
						})
					}}
				</p>
				<NcButton
					variant="primary"
					:disabled="busy || !handOffAllowed"
					@click="step('handoff')">
					{{ t('filinq', 'Hand off for publication') }}
				</NcButton>
			</section>

			<section v-if="withdrawAllowed">
				<h3>{{ t('filinq', 'Withdraw') }}</h3>
				<label for="publication-withdraw-reason">{{
					t('filinq', 'Why is it withdrawn?')
				}}</label>
				<textarea
					id="publication-withdraw-reason"
					v-model="withdrawReason"
					rows="3" />
				<NcButton
					:disabled="busy || !withdrawReason.trim()"
					@click="step('withdraw', { reason: withdrawReason })">
					{{ t('filinq', 'Withdraw publication') }}
				</NcButton>
			</section>

			<section>
				<h3>{{ t('filinq', 'Destruction date') }}</h3>
				<p v-if="record.destructionDate">
					{{ record.destructionDate }} ({{ record.destructionDateSource }})
				</p>
				<label for="publication-destruction">{{
					t('filinq', 'Destroyed on')
				}}</label>
				<input
					id="publication-destruction"
					v-model="destruction.date"
					type="date" />
				<NcTextField
					v-model="destruction.source"
					:label="t('filinq', 'Where the date comes from')" />
				<NcButton
					:disabled="busy || !destruction.date || !destruction.source"
					@click="step('destruction-date', destruction)">
					{{ t('filinq', 'Save destruction date') }}
				</NcButton>
			</section>

			<section>
				<h3>{{ t('filinq', 'History') }}</h3>
				<ol class="publications__log">
					<li v-for="entry in record.log || []" :key="entry.uuid">
						{{ formatTime(entry.timestamp) }}: {{ entry.action }} ({{
							entry.actor
						}})
						<span v-if="entry.details" class="publications__muted">{{
							entry.details
						}}</span>
					</li>
				</ol>
			</section>
		</template>
		<NcLoadingIcon v-else :size="32" />
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import {
	NcButton,
	NcEmptyContent,
	NcLoadingIcon,
	NcNoteCard,
	NcTextField,
} from '@nextcloud/vue'
import PublicationBulkMetadataDialog from '../../dialogs/PublicationBulkMetadataDialog.vue'
import {
	canHandOff,
	canWithdraw,
	missingMetadata,
	publicationStatusLabel,
	readinessChecks,
} from '../../services/publicationRecord.js'
import {
	getPublication,
	listCategories,
	listPublications,
	publicationStep,
	setMetadataMany,
} from '../../services/publications.js'
import {
	accessibilityLost,
	accessibilityNote,
} from '../../services/redactionAccessibility.js'
import { sanitizationStatus } from '../../services/sanitization.js'

const FIELDS = [
	'officieleTitel',
	'wooCategory',
	'documentsoort',
	'publisher',
	'creatiedatum',
	'publicatiedatum',
	'accessibilityOverrideReason',
]

export default {
	name: 'PublicationsPage',
	components: { NcButton, NcEmptyContent, NcLoadingIcon, NcNoteCard, NcTextField, PublicationBulkMetadataDialog },

	data() {
		return {
			records: [],
			record: null,
			categories: [],
			platformAvailable: true,
			loading: false,
			busy: false,
			error: '',
			reasons: [],
			form: {},
			withdrawReason: '',
			destruction: { date: '', source: '' },
			sanitized: null,
			onlyIncomplete: false,
			selected: [],
			bulkOpen: false,
			bulkResult: null,
		}
	},

	computed: {
		/**
		 * Whether every listed record is selected.
		 *
		 * @return {boolean} True when there are records and all are selected.
		 * @spec openspec/changes/woo-metadata-worklist/specs/woo-metadata-worklist/spec.md#requirement-a-coordinator-fills-one-value-on-a-selection-req-wmw-002
		 */
		allSelected() {
			return this.records.length > 0 && this.selected.length === this.records.length
		},

		/**
		 * The publication in the route, or empty for the list.
		 *
		 * @spec openspec/specs/woo-publicatie-pipeline/spec.md
		 */
		id() {
			return this.$route.params.id || ''
		},

		/**
		 * The three checks of the shown record.
		 *
		 * @spec openspec/specs/woo-publicatie-pipeline/spec.md
		 */
		checks() {
			return readinessChecks(this.record || {})
		},

		/**
		 * The Woo metadata still missing, counting unsaved form input.
		 *
		 * @spec openspec/specs/woo-publicatie-pipeline/spec.md
		 */
		/**
		 * The warning for a redacted copy that lost its accessibility.
		 *
		 * @return {object|null}
		 * @spec openspec/changes/archive/2026-09-29-accessible-redaction-output/tasks.md#task-3.1
		 */
		accessibilityWarning() {
			if (!accessibilityLost(this.record)) {
				return null
			}
			return accessibilityNote({ state: this.record.accessibilityState })
		},

		missing() {
			return missingMetadata({ ...this.record, ...this.form })
		},

		/**
		 * Whether the hand-off button can be used.
		 *
		 * @spec openspec/specs/woo-publicatie-pipeline/spec.md
		 */
		handOffAllowed() {
			return canHandOff(this.record || {}, this.platformAvailable)
		},

		/**
		 * Whether the record can be withdrawn.
		 *
		 * @spec openspec/specs/woo-publicatie-pipeline/spec.md
		 */
		withdrawAllowed() {
			return canWithdraw(this.record || {})
		},
	},

	watch: {
		id: {
			/**
			 * Load the list or the record when the route changes.
			 *
			 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-3.1
			 */
			handler() {
				this.load()
			},

			immediate: true,
		},
	},

	methods: {
		t,

		/**
		 * The label of a status.
		 *
		 * @param {string} status The status
		 * @return {string} The label.
		 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-3.1
		 */
		statusLabel(status) {
			return publicationStatusLabel(status)
		},

		/**
		 * How many of the three checks passed.
		 *
		 * @param {object} record The record
		 * @return {number} 0 to 3.
		 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-3.1
		 */
		passed(record) {
			return readinessChecks(record).filter((check) => check.ok).length
		},

		/**
		 * The Woo metadata a record still lacks for a hand-off.
		 *
		 * @param {object} row The record
		 * @return {string[]} The missing fields.
		 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-3.1
		 */
		missingOf(row) {
			// The server's `diwooMissing` names every mandatory DiWoo field;
			// the local check covers a record read without it.
			return Array.isArray(row.diwooMissing) ? row.diwooMissing : missingMetadata(row)
		},

		/**
		 * Select every listed record, or none.
		 *
		 * @param {boolean} on Whether to select them
		 * @spec openspec/changes/woo-metadata-worklist/specs/woo-metadata-worklist/spec.md#requirement-a-coordinator-fills-one-value-on-a-selection-req-wmw-002
		 */
		toggleAll(on) {
			this.selected = on ? this.records.map((row) => row.uuid) : []
		},

		/**
		 * The title a record is listed under, for the skipped list.
		 *
		 * @param {string} uuid The record
		 * @return {string} Its title, or the uuid when it is not listed.
		 * @spec openspec/changes/woo-metadata-worklist/specs/woo-metadata-worklist/spec.md#requirement-a-coordinator-fills-one-value-on-a-selection-req-wmw-002
		 */
		titleOf(uuid) {
			const row = this.records.find((r) => r.uuid === uuid)
			return (row && row.officieleTitel) || uuid
		},

		/**
		 * Set the chosen value on the selection, then list again so completed
		 * records leave the incomplete filter.
		 *
		 * @param {object} values One field and its value
		 * @return {Promise<void>}
		 * @spec openspec/changes/woo-metadata-worklist/specs/woo-metadata-worklist/spec.md#requirement-a-coordinator-fills-one-value-on-a-selection-req-wmw-002
		 */
		async setMetadata(values) {
			this.busy = true
			this.error = ''
			try {
				const outcome = await setMetadataMany(this.selected, values)
				this.bulkResult = { updated: outcome.updated || [], skipped: outcome.skipped || [] }
				this.bulkOpen = false
				this.selected = []
				await this.load()
			} catch (e) {
				this.error = (e && e.response && e.response.data && e.response.data.error) || t('filinq', 'Could not load the publications.')
			} finally {
				this.busy = false
			}
		},

		/**
		 * A stored time in the reader's locale.
		 *
		 * @param {string} value An ISO date-time
		 * @return {string} The time.
		 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-3.1
		 */
		formatTime(value) {
			return value ? new Date(value).toLocaleString() : ''
		},

		/**
		 * Load the list, or one record.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-3.1
		 */
		async load() {
			this.loading = true
			this.error = ''
			this.reasons = []
			try {
				if (this.id) {
					this.show(await getPublication(this.id))
					this.categories = await listCategories()
				} else {
					const data = await listPublications({ incomplete: this.onlyIncomplete })
					this.records = data.results || []
					this.platformAvailable = data.platformAvailable !== false
					this.selected = this.selected.filter((uuid) => this.records.some((row) => row.uuid === uuid))
					if (this.categories.length === 0) {
						this.categories = await listCategories().catch(() => [])
					}
				}
			} catch {
				this.error = t('filinq', 'Could not load the publications.')
			} finally {
				this.loading = false
			}
		},

		/**
		 * Show a record and fill the form from it.
		 *
		 * @param {object} record The record
		 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-3.1
		 */
		show(record) {
			this.record = record
			if (record.platformAvailable !== undefined) {
				this.platformAvailable = record.platformAvailable
			}
			this.form = Object.fromEntries(FIELDS.map((f) => [f, record[f] || '']))
			this.readSanitized(record)
		},

		/**
		 * Whether the file that would be handed off is a sanitized file.
		 * Null while unknown or unreadable: then no warning, the hand-off is not blocked either way.
		 *
		 * @param {object} record The record
		 * @return {Promise<void>}
		 * @spec openspec/changes/document-sanitization/tasks.md#4-2
		 */
		async readSanitized(record) {
			this.sanitized = null
			const fileId = record.redactedFileRef || record.documentFileRef
			if (!fileId) {
				return
			}
			const answer = await sanitizationStatus(fileId)
			if (answer.ok) {
				this.sanitized = answer.data.sanitized === true
			}
		},

		/**
		 * Save the Woo metadata.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-3.1
		 */
		async saveMetadata() {
			await this.step('metadata', this.form)
		},

		/**
		 * Run a step and show the record again, or why it was refused.
		 *
		 * @param {string} name The step
		 * @param {object} body The body
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-3.1
		 */
		async step(name, body = {}) {
			this.busy = true
			this.error = ''
			this.reasons = []
			try {
				await publicationStep(this.id, name, body)
				this.show(await getPublication(this.id))
			} catch (e) {
				this.error =
					e?.response?.data?.error
					|| t('filinq', 'The step could not be done.')
				this.reasons = e?.response?.data?.reasons || []
			} finally {
				this.busy = false
			}
		},
	},
}
</script>

<style scoped>
.publications {
	max-width: 1000px;
	padding: 24px;
}

.publications section {
	margin-top: 24px;
}

.publications__worklist {
	display: flex;
	flex-wrap: wrap;
	align-items: center;
	gap: 12px;
	margin-block: 8px;
}

.publications__filter {
	display: inline-flex;
	align-items: center;
	gap: 6px;
}

.publications__muted {
	color: var(--color-text-maxcontrast);
}

.publications__table {
	width: 100%;
	border-collapse: collapse;
}

.publications__table th,
.publications__table td {
	text-align: start;
	padding: 8px;
	border-bottom: 1px solid var(--color-border);
}

.publications__form {
	display: flex;
	flex-direction: column;
	gap: 8px;
	max-width: 480px;
}

.publications__checks li[data-ok='no'] {
	color: var(--color-error-text);
}
</style>
