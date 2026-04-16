<template>
	<div class="entity-review">
		<div class="summary-bar">
			{{ t('docudesk', '{selected} of {total} entities selected across {files} files', { selected: selectedCount, total: entities.length, files: fileCount }) }}
		</div>
		<div class="filter-bar">
			<input v-model="searchQuery" type="text" :placeholder="t('docudesk', 'Search entities...')">
			<select v-model="typeFilter">
				<option value="">{{ t('docudesk', 'All types') }}</option>
				<option v-for="tp in availableTypes" :key="tp" :value="tp">{{ tp }}</option>
			</select>
		</div>
		<div class="bulk-actions">
			<NcButton type="tertiary" @click="$emit('bulk-select', filteredEntities.map(i => i.idx))">
				{{ t('docudesk', 'Select All Visible') }}
			</NcButton>
			<NcButton type="tertiary" @click="$emit('bulk-deselect', filteredEntities.map(i => i.idx))">
				{{ t('docudesk', 'Deselect All Visible') }}
			</NcButton>
		</div>
		<table class="entity-table">
			<thead>
				<tr>
					<th />
					<th @click="sortBy('type')">{{ t('docudesk', 'Type') }}</th>
					<th @click="sortBy('value')">{{ t('docudesk', 'Value') }}</th>
					<th @click="sortBy('highestConfidence')">{{ t('docudesk', 'Confidence') }}</th>
					<th @click="sortBy('fileCount')">{{ t('docudesk', 'Files') }}</th>
				</tr>
			</thead>
			<tbody>
				<tr v-for="item in filteredEntities" :key="item.idx">
					<td><input type="checkbox" :checked="item.e.included" @change="$emit('toggle', item.idx)"></td>
					<td><span class="badge">{{ item.e.type }}</span></td>
					<td>{{ item.e.value }}</td>
					<td>{{ ((item.e.highestConfidence || 0) * 100).toFixed(1) }}%</td>
					<td>{{ item.e.fileCount }}</td>
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
	props: {
		entities: { type: Array, required: true },
		fileCount: { type: Number, default: 0 },
	},
	emits: ['toggle', 'bulk-select', 'bulk-deselect'],
	data() {
		return {
			searchQuery: '',
			typeFilter: '',
			sf: 'highestConfidence',
			sa: false,
		}
	},
	computed: {
		selectedCount() {
			return this.entities.filter(e => e.included).length
		},
		availableTypes() {
			return [...new Set(this.entities.map(e => e.type))].sort()
		},
		filteredEntities() {
			const q = this.searchQuery.toLowerCase()
			return this.entities
				.map((e, i) => ({ e, idx: i }))
				.filter(({ e }) => (!q || e.value.toLowerCase().includes(q)) && (!this.typeFilter || e.type === this.typeFilter))
				.sort((a, b) => {
					const va = a.e[this.sf]
					const vb = b.e[this.sf]
					const c = typeof va === 'string' ? va.localeCompare(vb) : (va || 0) - (vb || 0)
					return this.sa ? c : -c
				})
		},
	},
	methods: {
		t,
		sortBy(field) {
			if (this.sf === field) {
				this.sa = !this.sa
			} else {
				this.sf = field
				this.sa = false
			}
		},
	},
}
</script>

<style scoped>
.entity-review { margin: 16px 0; }
.summary-bar { padding: 12px; background: var(--color-primary-element-light); border-radius: 8px; margin-bottom: 16px; }
.filter-bar { display: flex; gap: 12px; margin-bottom: 12px; }
.bulk-actions { display: flex; gap: 8px; margin-bottom: 12px; }
.entity-table { width: 100%; border-collapse: collapse; }
.entity-table th, .entity-table td { padding: 10px 12px; border-bottom: 1px solid var(--color-border); text-align: left; }
.entity-table th { cursor: pointer; }
.badge { padding: 2px 8px; border-radius: 12px; font-size: 0.8rem; background: var(--color-primary-element-light); color: var(--color-primary-element); }
</style>
