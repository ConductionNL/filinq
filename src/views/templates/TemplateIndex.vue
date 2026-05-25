<template>
	<div class="template-index">
		<div class="template-index__header">
			<h2>{{ t('docudesk', 'Templates') }}</h2>
			<NcButton type="primary" @click="openNewTemplate">
				{{ t('docudesk', 'New template') }}
			</NcButton>
		</div>

		<div class="template-index__filters">
			<NcSelect v-model="selectedCategory"
				:options="categoryOptions"
				:placeholder="t('docudesk', 'Filter by category')"
				:input-label="t('docudesk', 'Category filter')"
				class="template-index__filter-select"
				@update:modelValue="applyFilters" />
			<NcTextField :value.sync="searchQuery"
				:label="t('docudesk', 'Search templates')"
				:placeholder="t('docudesk', 'Search by name...')"
				class="template-index__search"
				@update:value="applyFilters" />
		</div>

		<NcLoadingIcon v-if="templateStore.loading" />

		<table v-else-if="templateStore.templates.length > 0" class="template-index__table">
			<thead>
				<tr>
					<th>{{ t('docudesk', 'Name') }}</th>
					<th>{{ t('docudesk', 'Category') }}</th>
					<th>{{ t('docudesk', 'Namespace') }}</th>
					<th>{{ t('docudesk', 'Tags') }}</th>
					<th>{{ t('docudesk', 'Status') }}</th>
					<th>{{ t('docudesk', 'Actions') }}</th>
				</tr>
			</thead>
			<tbody>
				<tr v-for="tmpl in templateStore.templates"
					:key="tmpl.id"
					class="template-index__row"
					@click="openTemplate(tmpl)">
					<td>{{ tmpl.name }}</td>
					<td>{{ tmpl.category || '-' }}</td>
					<td>{{ tmpl.namespace }}</td>
					<td>
						<span v-for="tag in (tmpl.tags || [])"
							:key="tag"
							class="template-index__tag">
							{{ tag }}
						</span>
					</td>
					<td>
						<span v-if="tmpl.lockedBy" class="template-index__locked">
							{{ t('docudesk', 'Locked by {user}', { user: tmpl.lockedBy }) }}
						</span>
					</td>
					<td @click.stop>
						<NcButton type="tertiary"
							:aria-label="t('docudesk', 'Edit template')"
							@click="openTemplate(tmpl)">
							{{ t('docudesk', 'Edit') }}
						</NcButton>
						<NcButton type="tertiary"
							:aria-label="t('docudesk', 'Duplicate template')"
							@click="duplicateTemplate(tmpl)">
							{{ t('docudesk', 'Duplicate') }}
						</NcButton>
						<NcButton type="error"
							:aria-label="t('docudesk', 'Delete template')"
							@click="confirmDelete(tmpl)">
							{{ t('docudesk', 'Delete') }}
						</NcButton>
					</td>
				</tr>
			</tbody>
		</table>

		<NcEmptyContent v-else
			:name="t('docudesk', 'No templates found')"
			:description="t('docudesk', 'Create your first template to get started.')" />
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcEmptyContent, NcLoadingIcon, NcSelect, NcTextField } from '@nextcloud/vue'
import { useTemplateStore } from '../../store/modules/template.js'
import { navigationStore } from '../../store/store.js'

export default {
	name: 'TemplateIndex',
	components: { NcButton, NcEmptyContent, NcLoadingIcon, NcSelect, NcTextField },
	data() {
		return {
			navigationStore,
			selectedCategory: null,
			searchQuery: '',
		}
	},
	computed: {
		templateStore() { return useTemplateStore() },
		categoryOptions() {
			const cats = new Set(
				this.templateStore.templates
					.map(t => t.category)
					.filter(Boolean),
			)
			return [
				{ label: t('docudesk', 'All categories'), value: '' },
				...[...cats].map(c => ({ label: c, value: c })),
			]
		},
	},
	mounted() {
		this.templateStore.fetchTemplates()
	},
	methods: {
		t,
		applyFilters() {
			const filters = {}
			if (this.selectedCategory?.value) {
				filters.category = this.selectedCategory.value
			}
			if (this.searchQuery) {
				filters._search = this.searchQuery
			}
			this.templateStore.fetchTemplates(filters)
		},
		openTemplate(tmpl) {
			this.templateStore.templateItem = tmpl
			navigationStore.setSelected('templateDetail')
		},
		openNewTemplate() {
			this.templateStore.templateItem = null
			navigationStore.setSelected('templateDetail')
		},
		async duplicateTemplate(tmpl) {
			const result = await this.templateStore.duplicateTemplate(tmpl.id)
			if (result) {
				await this.templateStore.fetchTemplates()
			}
		},
		async confirmDelete(tmpl) {
			if (!window.confirm(t('docudesk', 'Delete template "{name}"?', { name: tmpl.name }))) {
				return
			}
			await this.templateStore.deleteTemplate(tmpl.id)
			await this.templateStore.fetchTemplates()
		},
	},
}
</script>

<style scoped>
.template-index__header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 16px;
}

.template-index__filters {
	display: flex;
	gap: 12px;
	margin-bottom: 16px;
	align-items: flex-end;
}

.template-index__filter-select {
	min-width: 200px;
}

.template-index__search {
	flex: 1;
}

.template-index__table {
	width: 100%;
	border-collapse: collapse;
}

.template-index__table th,
.template-index__table td {
	padding: 8px 12px;
	border-bottom: 1px solid var(--color-border);
	text-align: left;
}

.template-index__row {
	cursor: pointer;
}

.template-index__row:hover {
	background: var(--color-background-hover);
}

.template-index__tag {
	display: inline-block;
	background: var(--color-primary-light);
	color: var(--color-primary-text);
	border-radius: 12px;
	padding: 2px 8px;
	font-size: 12px;
	margin-right: 4px;
}

.template-index__locked {
	color: var(--color-warning);
	font-size: 12px;
}
</style>
