<template>
	<div class="entity-review">
		<div class="summary-bar">
			{{ selectedCount }} of {{ entities.length }} entities selected across {{ fileCount }} files
		</div>
		<div class="filter-bar">
			<input v-model="searchQuery" type="text" placeholder="Search entities...">
			<select v-model="typeFilter">
				<option value="">
					All types
				</option><option v-for="t in availableTypes" :key="t" :value="t">
					{{ t }}
				</option>
			</select>
		</div>
		<div class="bulk-actions">
			<NcButton type="tertiary" @click="$emit('bulk-select', filteredEntities.map(i => i.idx))">
				Select All Visible
			</NcButton>
			<NcButton type="tertiary" @click="$emit('bulk-deselect', filteredEntities.map(i => i.idx))">
				Deselect All Visible
			</NcButton>
			<NcButton v-if="defaultBases.length > 0" type="tertiary" @click="applyDefaultBasesToVisible">
				Apply dossier grondslagen to visible
			</NcButton>
		</div>
		<table class="entity-table">
			<thead>
				<tr>
					<th /><th @click="sortBy('type')">
						Type
					</th><th @click="sortBy('value')">
						Value
					</th><th @click="sortBy('highestConfidence')">
						Confidence
					</th><th @click="sortBy('fileCount')">
						Files
					</th><th class="bases-col">
						Grondslag (bases)
					</th><th>
						Skip
					</th>
				</tr>
			</thead>
			<tbody>
				<tr v-for="item in filteredEntities" :key="item.idx">
					<td><input type="checkbox" :checked="item.e.included" @change="$emit('toggle', item.idx)"></td>
					<td><span class="badge">{{ item.e.type }}</span></td><td>{{ item.e.value }}</td>
					<td>{{ ((item.e.highestConfidence||0)*100).toFixed(1) }}%</td><td>{{ item.e.fileCount }}</td>
					<td class="bases-cell">
						<NcSelect
							:value="item.e._decisionBases || []"
							:options="basesOptions"
							:multiple="true"
							:input-label="t('docudesk', 'Grondslagen')"
							:placeholder="t('docudesk', 'Pick grondslagen…')"
							:disabled="!Array.isArray(item.e.relationIds) || item.e.relationIds.length === 0"
							@input="onBasesChange(item.idx, $event)" />
						<div v-if="!Array.isArray(item.e.relationIds) || item.e.relationIds.length === 0" class="warn-text">
							{{ t('docudesk', '(no relation ids — grondslagen will not persist)') }}
						</div>
						<div v-if="item.e._patchError" class="error-text" :title="item.e._patchError">
							{{ item.e._patchError }}
						</div>
					</td>
					<td>
						<NcCheckboxRadioSwitch
							:checked="!!item.e._decisionSkip"
							:disabled="!Array.isArray(item.e.relationIds) || item.e.relationIds.length === 0"
							@update:checked="onSkipChange(item.idx, $event)" />
					</td>
				</tr>
			</tbody>
		</table>
	</div>
</template>
<script>
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcCheckboxRadioSwitch, NcSelect } from '@nextcloud/vue'

// Woo Art. 5 grondslagen seeded by the dossier register (Wave 1.1).
// Hardcoded here to match AnonymizationWidget — a production page
// would fetch /apps/openregister/api/objects/dossier/base so that
// custom bases added by tenants surface too.
const BASES_OPTIONS = [
	'persoonsgegevens',
	'bijzondere-persoonsgegevens',
	'strafrechtelijk',
	'bedrijfs-fabricagegegevens',
	'onevenredige-benadeling',
	'nationale-veiligheid',
]

export default {
	name: 'EntityReviewTable',
	components: { NcButton, NcCheckboxRadioSwitch, NcSelect },
	props: {
		entities: { type: Array, required: true },
		fileCount: { type: Number, default: 0 },
		// Pre-fill option for the dossier's default grondslagen. When the
		// operator clicks "Apply dossier grondslagen to visible", every
		// visible entity's _decisionBases gets set to this list.
		defaultBases: { type: Array, default: () => [] },
	},
	emits: ['toggle', 'bulk-select', 'bulk-deselect', 'bases-change', 'skip-change', 'confidence-change'],
	data() {
		return {
			searchQuery: '',
			typeFilter: '',
			sf: 'highestConfidence',
			sa: false,
			basesOptions: BASES_OPTIONS,
		}
	},
	computed: {
		selectedCount() { return this.entities.filter(e => e.included).length },
		availableTypes() { return [...new Set(this.entities.map(e => e.type))].sort() },
		filteredEntities() {
			const q = this.searchQuery.toLowerCase()
			return this.entities.map((e, i) => ({ e, idx: i }))
				.filter(({ e }) => (!q || e.value.toLowerCase().includes(q)) && (!this.typeFilter || e.type === this.typeFilter))
				.sort((a, b) => { const va = a.e[this.sf]; const vb = b.e[this.sf]; const c = typeof va === 'string' ? va.localeCompare(vb) : (va || 0) - (vb || 0); return this.sa ? c : -c })
		},
	},
	methods: {
		t,
		sortBy(f) { if (this.sf === f) { this.sa = !this.sa } else { this.sf = f; this.sa = false } },
		onBasesChange(idx, value) {
			this.$emit('bases-change', { idx, bases: Array.isArray(value) ? value : [] })
		},
		onSkipChange(idx, checked) {
			this.$emit('skip-change', { idx, skip: !!checked })
		},
		applyDefaultBasesToVisible() {
			const visibleIdx = this.filteredEntities.map(i => i.idx)
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
