<!--
  - SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
  - SPDX-License-Identifier: EUPL-1.2
-->
<template>
	<div class="entity-review">
		<div class="summary-bar">
			{{
				t(
					'filinq',
					'{selected} of {total} entities selected across {files} files',
					{
						selected: selectedCount,
						total: entities.length,
						files: fileCount,
					},
				)
			}}
		</div>
		<div
			v-if="certainFrom !== null"
			class="threshold-panel"
			data-testid="review-thresholds">
			<p>
				{{
					t('filinq', 'Found from {floor} (OpenRegister), certain from {certainFrom} (filinq)', {
						floor: thresholds.floor,
						certainFrom,
					})
				}}
			</p>
			<p v-if="thresholds.floorConfigurable === false" class="threshold-panel__note">
				{{ t('filinq', 'This OpenRegister does not make its detection floor configurable; {floor} is its default.', { floor: thresholds.floor }) }}
			</p>
			<p class="threshold-panel__count" data-testid="uncertain-count">
				{{ n('filinq', '{count} uncertain finding', '{count} uncertain findings', uncertainCount, { count: uncertainCount }) }}
			</p>
		</div>
		<div class="filter-bar">
			<input
				v-model="searchQuery"
				type="text"
				:aria-label="t('filinq', 'Search entities')"
				:placeholder="t('filinq', 'Search entities...')" />
			<select v-model="typeFilter">
				<option value="">
					{{ t('filinq', 'All types') }}
				</option>
				<option v-for="t in availableTypes" :key="t" :value="t">
					{{ entityTypeLabel(t) }}
				</option>
			</select>
			<NcCheckboxRadioSwitch
				v-if="certainFrom !== null"
				v-model="uncertainOnly"
				data-testid="uncertain-filter">
				{{ t('filinq', 'Uncertain findings only') }}
			</NcCheckboxRadioSwitch>
		</div>
		<div class="bulk-actions">
			<NcButton
				variant="tertiary"
				@click="
					$emit(
						'bulk-select',
						filteredEntities.map((i) => i.idx),
					)
				">
				{{ t('filinq', 'Select All Visible') }}
			</NcButton>
			<NcButton
				variant="tertiary"
				@click="
					$emit(
						'bulk-deselect',
						filteredEntities.map((i) => i.idx),
					)
				">
				{{ t('filinq', 'Deselect All Visible') }}
			</NcButton>
			<NcButton
				v-if="defaultBases.length > 0"
				variant="tertiary"
				@click="applyDefaultBasesToVisible">
				{{ t('filinq', 'Apply dossier grondslagen to visible') }}
			</NcButton>
		</div>
		<table class="entity-table">
			<thead>
				<tr>
					<th />
					<th scope="col" @click="sortBy('type')">
						{{ t('filinq', 'Type') }}
					</th>
					<th scope="col" @click="sortBy('value')">
						{{ t('filinq', 'Value') }}
					</th>
					<th scope="col" @click="sortBy('highestConfidence')">
						{{ t('filinq', 'Confidence') }}
					</th>
					<th scope="col" @click="sortBy('fileCount')">
						{{ t('filinq', 'Files') }}
					</th>
					<th scope="col" class="bases-col">
						{{ t('filinq', 'Legal basis') }}
					</th>
					<th scope="col">
						{{ t('filinq', 'Skip') }}
					</th>
				</tr>
			</thead>
			<tbody>
				<tr v-for="item in filteredEntities" :key="item.idx">
					<td>
						<input
							type="checkbox"
							:aria-label="
								t('filinq', 'Include {entity} in anonymisation', {
									entity: item.e.value,
								})
							"
							:checked="item.e.included"
							@change="onIncludeToggle(item)" />
					</td>
					<td>
						<span class="badge">{{ entityTypeLabel(item.e.type) }}</span>
					</td>
					<td>
						{{ item.e.value }}
						<router-link
							v-for="badge in policyBadges(item.e)"
							:key="badge.kind"
							:to="badge.route"
							class="policy-badge" :class="['policy-badge--' + badge.kind]"
							:data-testid="'policy-badge-' + badge.kind">
							{{
								badge.kind === 'prohibition'
									? t('filinq', 'Always anonymise: {rule}', { rule: badge.ruleName })
									: t('filinq', 'Never anonymise: {rule}', { rule: badge.ruleName })
							}}
						</router-link>
					</td>
					<td>
						{{ ((item.e.highestConfidence || 0) * 100).toFixed(1) }}%
						<span
							v-if="certainFrom !== null"
							class="certainty-badge"
							:class="isUncertain(item.e, certainFrom) ? 'certainty-badge--uncertain' : 'certainty-badge--certain'"
							:data-testid="'certainty-' + (isUncertain(item.e, certainFrom) ? 'uncertain' : 'certain')">
							{{ isUncertain(item.e, certainFrom) ? t('filinq', 'Uncertain') : t('filinq', 'Certain') }}
						</span>
						<NcButton
							v-if="isUncertain(item.e, certainFrom) && item.e._explicitDecision !== true && item.e.included"
							variant="tertiary"
							:aria-label="t('filinq', 'Confirm that {entity} is anonymised', { entity: item.e.value })"
							data-testid="confirm-uncertain"
							@click="confirmIncluded(item)">
							{{ t('filinq', 'Confirm') }}
						</NcButton>
					</td>
					<td>{{ item.e.fileCount }}</td>
					<td class="bases-cell">
						<NcSelect
							:modelValue="item.e._decisionBases || []"
							:options="basesOptions"
							label="label"
							:reduce="(o) => o.value"
							:multiple="true"
							:inputLabel="t('filinq', 'Legal bases')"
							:placeholder="t('filinq', 'Pick grondslagen…')"
							:disabled="
								!Array.isArray(item.e.relationIds)
								|| item.e.relationIds.length === 0
							"
							@update:modelValue="onBasesChange(item.idx, $event)" />
						<div
							v-if="basesAreProposed(item.e)"
							class="proposed-text"
							data-testid="bases-proposed">
							{{ t('filinq', 'Proposed') }}
						</div>
						<div
							v-if="
								!Array.isArray(item.e.relationIds)
								|| item.e.relationIds.length === 0
							"
							class="warn-text">
							{{
								t(
									'filinq',
									'(no relation ids — grondslagen will not persist)',
								)
							}}
						</div>
						<div
							v-if="item.e._patchError"
							class="error-text"
							:title="item.e._patchError">
							{{ item.e._patchError }}
						</div>
					</td>
					<td>
						<NcCheckboxRadioSwitch
							:aria-label="
								t('filinq', 'Skip {entity}', {
									entity: item.e.value,
								})
							"
							:modelValue="!!item.e._decisionSkip"
							:disabled="
								!Array.isArray(item.e.relationIds)
								|| item.e.relationIds.length === 0
								|| !!(
									item.e.prohibitionMatch
									&& item.e.prohibitionMatch.highConfidence
								)
							"
							@update:modelValue="onSkipChange(item.idx, $event)" />
					</td>
				</tr>
			</tbody>
		</table>
		<ProhibitionBlockedDialog
			:open="blockOpen"
			:block="blockInfo"
			@update:open="blockOpen = $event"
			@force="onForceSkip" />
	</div>
</template>

<script>
import { translatePlural as n, translate as t } from '@nextcloud/l10n'
import { NcButton, NcCheckboxRadioSwitch, NcSelect } from '@nextcloud/vue'
import ProhibitionBlockedDialog from '../../dialogs/ProhibitionBlockedDialog.vue'
import { fetchBaseOptions } from '../../services/bases.js'
import { basesAreProposed, isUncertain, policyBadges } from '../../services/documentReview.js'
import { entityTypeLabel } from '../../services/entityTypes.js'
import { anonymizationStore } from '../../store/store.js'

export default {
	name: 'EntityReviewTable',
	components: {
		NcButton,
		NcCheckboxRadioSwitch,
		NcSelect,
		ProhibitionBlockedDialog,
	},

	props: {
		entities: { type: Array, required: true },
		fileCount: { type: Number, default: 0 },
		// Pre-fill option for the dossier's default grondslagen. When the
		// operator clicks "Apply dossier grondslagen to visible", every
		// visible entity's _decisionBases gets set to this list.
		defaultBases: { type: Array, default: () => [] },
		// The two confidence numbers (anonymization-review-workbench REQ-DDARW-013):
		// `{ floor, certainFrom, floorConfigurable }`. Null leaves out the
		// certain/uncertain split, its badge, filter and counter.
		thresholds: { type: Object, default: null },
	},

	emits: [
		'toggle',
		'bulk-select',
		'bulk-deselect',
		'bases-change',
		'skip-change',
		'confidence-change',
		'decide',
	],

	data() {
		return {
			searchQuery: '',
			typeFilter: '',
			uncertainOnly: false,
			sf: 'highestConfidence',
			sa: false,
			basesOptions: [],
			blockOpen: false,
			blockInfo: null,
			pendingSkipIdx: null,
		}
	},

	computed: {
		/**
		 * The organisation's certain-from, or null when no thresholds were given.
		 *
		 * @return {number|null}
		 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.3
		 */
		certainFrom() {
			const value = this.thresholds?.certainFrom
			return typeof value === 'number' ? value : null
		},

		/**
		 * How many findings are uncertain.
		 *
		 * @return {number}
		 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.3
		 */
		uncertainCount() {
			return this.entities.filter((e) => isUncertain(e, this.certainFrom)).length
		},

		/**
		 * Count of entities currently included for anonymization.
		 *
		 * @spec openspec/specs/anonymization-entity-review/spec.md
		 */
		selectedCount() {
			return this.entities.filter((e) => e.included).length
		},

		/**
		 * Distinct sorted list of entity types for the type filter dropdown.
		 *
		 * @spec openspec/specs/anonymization-entity-review/spec.md
		 */
		availableTypes() {
			return [...new Set(this.entities.map((e) => e.type))].sort()
		},

		/**
		 * Entities filtered by search query and type, then sorted.
		 *
		 * @spec openspec/specs/anonymization-entity-review/spec.md
		 */
		filteredEntities() {
			const q = this.searchQuery.toLowerCase()
			return this.entities
				.map((e, i) => ({ e, idx: i }))
				.filter(
					({ e }) =>
						(!q || e.value.toLowerCase().includes(q))
						&& (!this.typeFilter || e.type === this.typeFilter)
						&& (!this.uncertainOnly || this.certainFrom === null || isUncertain(e, this.certainFrom)),
				)
				.sort((a, b) => {
					const va = a.e[this.sf]
					const vb = b.e[this.sf]
					const c =
						typeof va === 'string'
							? va.localeCompare(vb)
							: (va || 0) - (vb || 0)
					return this.sa ? c : -c
				})
		},
	},

	async created() {
		// Grondslagen options come from the register (label = name, value = slug),
		// with a slug fallback on error. See services/bases.js.
		this.basesOptions = await fetchBaseOptions()
	},

	methods: {
		t,
		n,
		isUncertain,
		entityTypeLabel,
		policyBadges,
		basesAreProposed,
		sortBy(f) {
			if (this.sf === f) {
				this.sa = !this.sa
			} else {
				this.sf = f
				this.sa = false
			}
		},

		/**
		 * Persist a reviewer's grondslag pick; it confirms a proposal, so the
		 * row is no longer marked as proposed.
		 *
		 * @param {number} idx The row's index.
		 * @param {Array<string>} value The chosen grondslagen.
		 * @return {void}
		 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-3.3
		 */
		onBasesChange(idx, value) {
			const entity = this.entities[idx]
			if (!entity) {
				return
			}
			// The reviewer's own pick wins over a proposal and is no longer one.
			entity._basesConfirmed = true
			this.persistBases(entity, Array.isArray(value) ? value : [])
		},

		// Persist a bases change on every relation immediately (bases are never
		// prohibition-guarded); skipAnonymization is left at its current value.
		persistBases(entity, bases) {
			entity._decisionBases = bases
			this.$emit('bases-change', { idx: this.entities.indexOf(entity), bases })
			const relationIds =
				Array.isArray(entity.relationIds) && entity.relationIds.length > 0
					? entity.relationIds
					: entity.relationId != null
						? [entity.relationId]
						: []
			if (relationIds.length === 0) {
				return Promise.resolve({ ok: false, status: 0, body: {} })
			}
			return Promise.all(
				relationIds.map((rid) =>
					anonymizationStore.setRelationSkip(
						rid,
						!!entity._decisionSkip,
						false,
						bases,
					),
				),
			).then((results) => {
				const bad = results.find((r) => !r.ok)
				if (bad) {
					entity._patchError =
						bad.body?.error || 'Failed to save grondslagen'
					return bad
				}
				entity.bases = [...bases]
				entity._patchError = null
				return { ok: true, status: 200, body: {} }
			})
		},

		/**
		 * The include checkbox: the reviewer's explicit decision on this finding.
		 *
		 * @param {{e: object, idx: number}} item The row.
		 * @return {void}
		 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.3
		 */
		onIncludeToggle(item) {
			item.e._explicitDecision = true
			this.$emit('toggle', item.idx)
		},

		/**
		 * Keep an uncertain finding anonymised as pre-selected, as an explicit decision.
		 *
		 * @param {{e: object, idx: number}} item The row.
		 * @return {void}
		 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.3
		 */
		confirmIncluded(item) {
			item.e._explicitDecision = true
			this.$emit('decide', item.idx)
		},

		// The "included" checkbox is the skip control: unchecking = skip.
		onIncludedChange(idx, checked) {
			return this.onSkipChange(idx, !checked)
		},

		async onSkipChange(idx, checked) {
			const entity = this.entities[idx]
			if (!entity) {
				return
			}
			const res = await this.persistSkip(entity, !!checked, false)
			if (!res.ok && res.status === 422) {
				this.pendingSkipIdx = idx
				this.blockInfo = res.body
				this.blockOpen = true
			}
		},

		/**
		 * Persist one entity's skip/include across all its relations through
		 * the guarded endpoint; only mutate local state on success so a blocked
		 * skip leaves the toggle reverted. A saved skip or include is the
		 * reviewer's explicit decision on the finding.
		 *
		 * @param {object} entity The review row.
		 * @param {boolean} skip Whether to skip it.
		 * @param {boolean} force Release a sub-threshold prohibition match.
		 * @return {Promise<{ok: boolean, status: number, body: object}>}
		 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.3
		 */
		persistSkip(entity, skip, force) {
			const relationIds =
				Array.isArray(entity.relationIds) && entity.relationIds.length > 0
					? entity.relationIds
					: entity.relationId != null
						? [entity.relationId]
						: []
			if (relationIds.length === 0) {
				return Promise.resolve({ ok: false, status: 0, body: {} })
			}
			return Promise.all(
				relationIds.map((rid) =>
					anonymizationStore.setRelationSkip(rid, skip, force),
				),
			).then((results) => {
				const bad = results.find((r) => !r.ok)
				if (bad) {
					return bad
				}
				entity._decisionSkip = skip
				entity._explicitDecision = true
				entity.skipAnonymization = skip
				entity.included = !skip
				entity._patchError = null
				this.$emit('skip-change', {
					idx: this.entities.indexOf(entity),
					skip,
				})
				return { ok: true, status: 200, body: {} }
			})
		},

		// Dialog force action: retry the pending skip with force=true.
		onForceSkip() {
			const idx = this.pendingSkipIdx
			if (idx == null || !this.entities[idx]) {
				return
			}
			this.persistSkip(this.entities[idx], true, true).then((res) => {
				if (!res.ok) {
					this.blockInfo = res.body
					this.blockOpen = res.status === 422
				}
			})
		},

		applyDefaultBasesToVisible() {
			const visibleIdx = this.filteredEntities.map((i) => i.idx)
			for (const idx of visibleIdx) {
				this.$emit('bases-change', { idx, bases: [...this.defaultBases] })
			}
		},
	},
}
</script>

<style scoped>
.entity-review {
	margin: 16px 0;
}

.summary-bar {
	padding: 12px;
	background: var(--color-primary-element-light);
	border-radius: var(--dd-radius-md);
	margin-bottom: 16px;
}

.filter-bar {
	display: flex;
	gap: 12px;
	margin-bottom: 12px;
}

.bulk-actions {
	display: flex;
	gap: 8px;
	margin-bottom: 12px;
	flex-wrap: wrap;
}

.entity-table {
	width: 100%;
	border-collapse: collapse;
}

.entity-table th,
.entity-table td {
	padding: 10px 12px;
	border-bottom: 1px solid var(--color-border);
	text-align: start;
	vertical-align: top;
}

.entity-table th {
	cursor: pointer;
}

.entity-table th.bases-col {
	cursor: default;
}

.policy-badge {
	display: inline-block;
	margin-inline-start: 6px;
	padding: 0 6px;
	border-radius: var(--border-radius-pill);
	font-size: 0.85em;
	text-decoration: underline;
}

.policy-badge--prohibition,
.policy-badge--standingConsent {
	/* Main text on the main background keeps AA contrast in both themes;
	 * the kind is told by the border and the wording, not by colour alone. */
	background: var(--color-main-background);
	color: var(--color-main-text);
}

.policy-badge--prohibition {
	border: 2px solid var(--color-warning);
}

.policy-badge--standingConsent {
	border: 2px solid var(--color-primary-element);
}

.threshold-panel {
	padding: 8px 12px;
	margin-bottom: 12px;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius-large);
}

.threshold-panel p {
	margin: 0;
}

.threshold-panel__note {
	color: var(--color-text-maxcontrast);
	font-size: 0.85em;
}

.certainty-badge {
	display: inline-block;
	margin-inline-start: 6px;
	padding: 0 6px;
	border-radius: var(--border-radius-pill);
	font-size: 0.85em;
	/* Main text on the main background keeps AA contrast in both themes;
	 * the state is told by the border and the word, not by colour alone. */
	background: var(--color-main-background);
	color: var(--color-main-text);
}

.certainty-badge--uncertain {
	border: 2px solid var(--color-warning);
}

.certainty-badge--certain {
	border: 1px solid var(--color-border);
}

.proposed-text {
	font-size: 0.85em;
	color: var(--color-text-maxcontrast);
}

.badge {
	padding: 2px 8px;
	border-radius: var(--border-radius-large);
	font-size: 0.8rem;
	background: var(--color-primary-element-light);
	color: var(--color-primary-element);
}

.bases-cell {
	min-width: 280px;
}

.warn-text {
	color: var(--color-warning, #b58900);
	font-size: 0.75rem;
	margin-top: 4px;
}

.error-text {
	color: var(--color-error, #cc3333);
	font-size: 0.75rem;
	margin-top: 4px;
}
</style>
