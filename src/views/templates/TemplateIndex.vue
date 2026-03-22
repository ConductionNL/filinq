<template>
	<div class="template-index">
		<div class="template-index__header">
			<h2>{{ t('docudesk', 'Templates') }}</h2>
			<div class="template-index__actions">
				<NcTextField :value.sync="searchQuery"
					:label="t('docudesk', 'Search templates...')"
					@update:value="onSearch" />
				<NcSelect v-model="selectedCategory"
					:options="categoryOptions"
					:placeholder="t('docudesk', 'All categories')"
					@input="onFilterChange" />
				<NcButton type="primary" @click="onCreateNew">
					<template #icon>
						<Plus :size="20" />
					</template>
					{{ t('docudesk', 'New template') }}
				</NcButton>
			</div>
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
				<tr v-for="template in templateStore.templates"
					:key="template.id"
					class="template-index__row"
					@click="onEdit(template)">
					<td>{{ template.name }}</td>
					<td>{{ template.category || '-' }}</td>
					<td>{{ template.namespace }}</td>
					<td>
						<span v-for="tag in (template.tags || [])"
							:key="tag"
							class="template-index__tag">
							{{ tag }}
						</span>
					</td>
					<td>
						<span v-if="template.lockedBy" class="template-index__locked">
							<Lock :size="16" />
							{{ template.lockedBy }}
						</span>
					</td>
					<td class="template-index__actions-cell" @click.stop>
						<NcActions>
							<NcActionButton @click="onDuplicate(template)">
								<template #icon>
									<ContentCopy :size="20" />
								</template>
								{{ t('docudesk', 'Duplicate') }}
							</NcActionButton>
							<NcActionButton @click="confirmDelete(template)">
								<template #icon>
									<Delete :size="20" />
								</template>
								{{ t('docudesk', 'Delete') }}
							</NcActionButton>
						</NcActions>
					</td>
				</tr>
			</tbody>
		</table>

		<NcEmptyContent v-else :name="t('docudesk', 'No templates found')">
			<template #icon>
				<FileDocumentMultiple :size="64" />
			</template>
			<template #action>
				<NcButton type="primary" @click="onCreateNew">
					{{ t('docudesk', 'Create your first template') }}
				</NcButton>
			</template>
		</NcEmptyContent>
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import {
	NcActions,
	NcActionButton,
	NcButton,
	NcEmptyContent,
	NcLoadingIcon,
	NcSelect,
	NcTextField,
} from '@nextcloud/vue'
import Plus from 'vue-material-design-icons/Plus.vue'
import Delete from 'vue-material-design-icons/Delete.vue'
import ContentCopy from 'vue-material-design-icons/ContentCopy.vue'
import Lock from 'vue-material-design-icons/Lock.vue'
import FileDocumentMultiple from 'vue-material-design-icons/FileDocumentMultiple.vue'
import { useTemplateStore } from '../../store/modules/template.js'
import { showSuccess, showError } from '@nextcloud/dialogs'

export default {
	name: 'TemplateIndex',
	components: {
		NcActions,
		NcActionButton,
		NcButton,
		NcEmptyContent,
		NcLoadingIcon,
		NcSelect,
		NcTextField,
		Plus,
		Delete,
		ContentCopy,
		Lock,
		FileDocumentMultiple,
	},
	data() {
		return {
			searchQuery: '',
			selectedCategory: null,
			categoryOptions: [
				{ id: '', label: t('docudesk', 'All categories') },
				{ id: 'beschikkingen', label: t('docudesk', 'Decisions') },
				{ id: 'brieven', label: t('docudesk', 'Letters') },
				{ id: 'notities', label: t('docudesk', 'Notes') },
				{ id: 'rapporten', label: t('docudesk', 'Reports') },
			],
		}
	},
	computed: {
		templateStore() {
			return useTemplateStore()
		},
	},
	mounted() {
		this.loadTemplates()
	},
	methods: {
		t,
		async loadTemplates() {
			const filters = {}
			if (this.searchQuery) {
				filters._search = this.searchQuery
			}
			if (this.selectedCategory && this.selectedCategory.id) {
				filters.category = this.selectedCategory.id
			}
			await this.templateStore.fetchTemplates(filters)
		},
		onSearch() {
			this.loadTemplates()
		},
		onFilterChange() {
			this.loadTemplates()
		},
		onCreateNew() {
			this.$router.push({ name: 'TemplateDetail', params: { id: 'new' } })
		},
		onEdit(template) {
			this.$router.push({ name: 'TemplateDetail', params: { id: template.id } })
		},
		async onDuplicate(template) {
			const result = await this.templateStore.duplicateTemplate(template.id)
			if (result) {
				showSuccess(t('docudesk', 'Template duplicated'))
				this.loadTemplates()
			} else {
				showError(t('docudesk', 'Failed to duplicate template'))
			}
		},
		async confirmDelete(template) {
			if (confirm(t('docudesk', 'Are you sure you want to delete "{name}"?', { name: template.name }))) {
				const result = await this.templateStore.deleteTemplate(template.id)
				if (result) {
					showSuccess(t('docudesk', 'Template deleted'))
				} else {
					showError(t('docudesk', 'Failed to delete template'))
				}
			}
		},
	},
}
</script>

<style scoped lang="scss">
.template-index {
	padding: 20px;

	&__header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 20px;
		flex-wrap: wrap;
		gap: 10px;
	}

	&__actions {
		display: flex;
		gap: 10px;
		align-items: center;
	}

	&__table {
		width: 100%;
		border-collapse: collapse;

		th, td {
			padding: 10px 12px;
			text-align: left;
			border-bottom: 1px solid var(--color-border);
		}

		th {
			font-weight: bold;
			color: var(--color-text-maxcontrast);
		}
	}

	&__row {
		cursor: pointer;

		&:hover {
			background: var(--color-background-hover);
		}
	}

	&__tag {
		display: inline-block;
		padding: 2px 8px;
		margin: 2px;
		border-radius: 12px;
		background: var(--color-primary-element-light);
		font-size: 12px;
	}

	&__locked {
		display: flex;
		align-items: center;
		gap: 4px;
		color: var(--color-warning);
	}

	&__actions-cell {
		width: 50px;
	}
}
</style>
