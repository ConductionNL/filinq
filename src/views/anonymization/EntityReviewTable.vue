<template>
	<div class="entity-review">
		<div class="summary-bar">
			<span>{{ t('docudesk', '{selected} of {total} entities selected across {files} files', { selected: selectedCount, total: entities.length, files: fileCount }) }}</span>
		</div>
		<div class="filter-bar">
			<input v-model="searchQuery" type="text" class="search-input" :placeholder="t('docudesk', 'Search entities...')">
			<select v-model="typeFilter" class="type-filter">
				<option value="">{{ t('docudesk', 'All types') }}</option>
				<option v-for="et in availableTypes" :key="et" :value="et">{{ et }}</option>
			</select>
			<div class="confidence-filter">
				<label>{{ t('docudesk', 'Min confidence') }}</label>
				<input v-model.number="confidenceThreshold" type="range" min="0" max="1" step="0.05" class="confidence-slider">
				<span>{{ Math.round(confidenceThreshold * 100) }}%</span>
			</div>
		</div>
		<div class="bulk-actions">
			<NcButton type="tertiary" @click="selectAllVisible">{{ t('docudesk', 'Select All Visible') }}</NcButton>
			<NcButton type="tertiary" @click="deselectAllVisible">{{ t('docudesk', 'Deselect All Visible') }}</NcButton>
			<span class="filter-count">{{ filteredEntities.length }} / {{ entities.length }}</span>
		</div>
		<table class="entity-table">
			<thead><tr>
				<th />
				<th class="sortable" @click="sortBy('type')">{{ t('docudesk', 'Type') }}</th>
				<th class="sortable" @click="sortBy('value')">{{ t('docudesk', 'Value') }}</th>
				<th class="sortable" @click="sortBy('highestConfidence')">{{ t('docudesk', 'Confidence') }}</th>
				<th class="sortable" @click="sortBy('fileCount')">{{ t('docudesk', 'Files') }}</th>
			</tr></thead>
			<tbody>
				<tr v-for="item in filteredEntities" :key="item.originalIndex">
					<td><input type="checkbox" :checked="item.entity.included" @change="$emit('toggle', item.originalIndex)"></td>
					<td><span class="entity-type-badge">{{ item.entity.type }}</span></td>
					<td>{{ item.entity.value }}</td>
					<td>{{ ((item.entity.highestConfidence || 0) * 100).toFixed(1) }}%</td>
					<td>{{ item.entity.fileCount }}</td>
				</tr>
			</tbody>
		</table>
	</div>
</template>
<script>
import { NcButton } from '@nextcloud/vue'
import { translate as t } from '@nextcloud/l10n'
export default {
	name: 'EntityReviewTable',
	components: { NcButton },
	props: { entities: { type: Array, required: true }, fileCount: { type: Number, default: 0 } },
	emits: ['toggle', 'bulk-select', 'bulk-deselect', 'confidence-change'],
	data() { return { searchQuery: '', typeFilter: '', confidenceThreshold: 0.0, sortField: 'highestConfidence', sortAsc: false } },
	computed: {
		selectedCount() { return this.entities.filter((e) => e.included).length },
		availableTypes() { return [...new Set(this.entities.map((e) => e.type))].sort() },
		filteredEntities() {
			const q = this.searchQuery.toLowerCase()
			const items = this.entities.map((entity, index) => ({ entity, originalIndex: index }))
				.filter(({ entity }) => (!q || entity.value.toLowerCase().includes(q)) && (!this.typeFilter || entity.type === this.typeFilter))
			items.sort((a, b) => { const va = a.entity[this.sortField]; const vb = b.entity[this.sortField]; const c = typeof va === 'string' ? va.localeCompare(vb) : (va || 0) - (vb || 0); return this.sortAsc ? c : -c })
			return items
		},
	},
	watch: { confidenceThreshold(v) { this.$emit('confidence-change', v) } },
	methods: {
		t,
		sortBy(f) { if (this.sortField === f) { this.sortAsc = !this.sortAsc } else { this.sortField = f; this.sortAsc = false } },
		selectAllVisible() { this.$emit('bulk-select', this.filteredEntities.map((i) => i.originalIndex)) },
		deselectAllVisible() { this.$emit('bulk-deselect', this.filteredEntities.map((i) => i.originalIndex)) },
	},
}
</script>
<style scoped>
.entity-review { margin: 16px 0 }
.summary-bar { padding: 12px 16px; background: var(--color-primary-element-light); border-radius: 8px; margin-bottom: 16px; font-weight: 500 }
.filter-bar { display: flex; gap: 12px; align-items: center; margin-bottom: 12px; flex-wrap: wrap }
.search-input { flex: 1; min-width: 200px; padding: 8px 12px; border: 1px solid var(--color-border); border-radius: 6px }
.type-filter { padding: 8px 12px; border: 1px solid var(--color-border); border-radius: 6px }
.confidence-filter { display: flex; align-items: center; gap: 8px }
.confidence-slider { width: 120px }
.bulk-actions { display: flex; align-items: center; gap: 8px; margin-bottom: 12px }
.filter-count { margin-left: auto; font-size: 0.85rem; color: var(--color-text-maxcontrast) }
.entity-table { width: 100%; border-collapse: collapse }
.entity-table th, .entity-table td { padding: 10px 12px; text-align: left; border-bottom: 1px solid var(--color-border) }
.sortable { cursor: pointer; user-select: none }
.entity-type-badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 0.8rem; background: var(--color-primary-element-light); color: var(--color-primary-element) }
</style>
