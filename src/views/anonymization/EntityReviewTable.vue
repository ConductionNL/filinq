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
					</th>
				</tr>
			</thead>
			<tbody>
				<tr v-for="item in filteredEntities" :key="item.idx">
					<td><input type="checkbox" :checked="item.e.included" @change="$emit('toggle', item.idx)"></td>
					<td><span class="badge">{{ item.e.type }}</span></td><td>{{ item.e.value }}</td>
					<td>{{ ((item.e.highestConfidence||0)*100).toFixed(1) }}%</td><td>{{ item.e.fileCount }}</td>
				</tr>
			</tbody>
		</table>
	</div>
</template>
<script>
import { NcButton } from '@nextcloud/vue'

export default {
	name: 'EntityReviewTable',
	components: { NcButton },
	props: { entities: { type: Array, required: true }, fileCount: { type: Number, default: 0 } },
	emits: ['toggle', 'bulk-select', 'bulk-deselect', 'confidence-change'],
	data() { return { searchQuery: '', typeFilter: '', sf: 'highestConfidence', sa: false } },
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
	methods: { sortBy(f) { if (this.sf === f) { this.sa = !this.sa } else { this.sf = f; this.sa = false } } },
}
</script>
<style scoped>
.entity-review {
	margin: 16px 0;
}

.summary-bar {
	padding: 12px;
	background: var(--color-primary-element-light);
	border-radius: 8px;
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
}

.entity-table th {
	cursor: pointer;
}

.badge {
	padding: 2px 8px;
	border-radius: 12px;
	font-size: 0.8rem;
	background: var(--color-primary-element-light);
	color: var(--color-primary-element);
}

</style>
