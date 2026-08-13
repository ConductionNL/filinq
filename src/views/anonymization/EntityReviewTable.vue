<template>
	<div class="entity-review">
		<div class="summary-bar">
			{{
				t(
					'docudesk',
					'{selected} of {total} entities selected across {files} files',
					{
						selected: selectedCount,
						total: entities.length,
						files: fileCount,
					},
				)
			}}
		</div>
		<div class="filter-bar">
			<input
				v-model="searchQuery"
				type="text"
				:aria-label="t('docudesk', 'Search entities')"
				:placeholder="t('docudesk', 'Search entities...')" />
			<select v-model="typeFilter">
				<option value="">
					{{ t('docudesk', 'All types') }}
				</option>
				<option v-for="t in availableTypes" :key="t" :value="t">
					{{ entityTypeLabel(t) }}
				</option>
			</select>
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
				{{ t('docudesk', 'Select All Visible') }}
			</NcButton>
			<NcButton
				variant="tertiary"
				@click="
					$emit(
						'bulk-deselect',
						filteredEntities.map((i) => i.idx),
					)
				">
				{{ t('docudesk', 'Deselect All Visible') }}
			</NcButton>
			<NcButton
				v-if="defaultBases.length > 0"
				variant="tertiary"
				@click="applyDefaultBasesToVisible">
				{{ t('docudesk', 'Apply dossier grondslagen to visible') }}
			</NcButton>
		</div>
		<table class="entity-table">
			<thead>
				<tr>
					<th />
					<th scope="col" @click="sortBy('type')">
						{{ t('docudesk', 'Type') }}
					</th>
					<th scope="col" @click="sortBy('value')">
						{{ t('docudesk', 'Value') }}
					</th>
					<th scope="col" @click="sortBy('highestConfidence')">
						{{ t('docudesk', 'Confidence') }}
					</th>
					<th scope="col" @click="sortBy('fileCount')">
						{{ t('docudesk', 'Files') }}
					</th>
					<th scope="col" class="bases-col">
						{{ t('docudesk', 'Grondslag (bases)') }}
					</th>
					<th scope="col">
						{{ t('docudesk', 'Skip') }}
					</th>
				</tr>
			</thead>
			<tbody>
				<tr v-for="item in filteredEntities" :key="item.idx">
					<td>
						<input
							type="checkbox"
							:aria-label="
								t('docudesk', 'Include {entity} in anonymisation', {
									entity: item.e.value,
								})
							"
							:checked="item.e.included"
							@change="$emit('toggle', item.idx)" />
					</td>
					<td>
						<span class="badge">{{ entityTypeLabel(item.e.type) }}</span>
					</td>
					<td>{{ item.e.value }}</td>
					<td>
						{{ ((item.e.highestConfidence || 0) * 100).toFixed(1) }}%
					</td>
					<td>{{ item.e.fileCount }}</td>
					<td class="bases-cell">
						<NcSelect
							:model-value="item.e._decisionBases || []"
							:options="basesOptions"
							label="label"
							:reduce="(o) => o.value"
							:multiple="true"
							:input-label="t('docudesk', 'Grondslagen')"
							:placeholder="t('docudesk', 'Pick grondslagen…')"
							:disabled="
								!Array.isArray(item.e.relationIds)
								|| item.e.relationIds.length === 0
							"
							@update:modelValue="onBasesChange(item.idx, $event)" />
						<div
							v-if="
								!Array.isArray(item.e.relationIds)
								|| item.e.relationIds.length === 0
							"
							class="warn-text">
							{{
								t(
									'docudesk',
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
								t('docudesk', 'Skip {entity}', {
									entity: item.e.value,
								})
							"
							:model-value="!!item.e._decisionSkip"
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
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcCheckboxRadioSwitch, NcSelect } from '@nextcloud/vue'
import { entityTypeLabel } from '../../services/entityTypes.js'
import ProhibitionBlockedDialog from '../../dialogs/ProhibitionBlockedDialog.vue'
import { anonymizationStore } from '../../store/store.js'
import { fetchBaseOptions } from '../../services/bases.js'

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
	},
	emits: [
		'toggle',
		'bulk-select',
		'bulk-deselect',
		'bases-change',
		'skip-change',
		'confidence-change',
	],
	data() {
		return {
			searchQuery: '',
			typeFilter: '',
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
						&& (!this.typeFilter || e.type === this.typeFilter),
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
		entityTypeLabel,
		sortBy(f) {
			if (this.sf === f) {
				this.sa = !this.sa
			} else {
				this.sf = f
				this.sa = false
			}
		},
		onBasesChange(idx, value) {
			const entity = this.entities[idx]
			if (!entity) {
				return
			}
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
		// Persist one entity's skip/include across all its relations through
		// the guarded endpoint; only mutate local state on success so a blocked
		// skip leaves the toggle reverted.
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
	text-align: left;
	vertical-align: top;
}

.entity-table th {
	cursor: pointer;
}

.entity-table th.bases-col {
	cursor: default;
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
