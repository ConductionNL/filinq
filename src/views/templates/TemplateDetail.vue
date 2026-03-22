<template>
	<div class="template-detail">
		<div class="template-detail__header">
			<NcButton type="tertiary" @click="goBack">
				<template #icon>
					<ArrowLeft :size="20" />
				</template>
				{{ t('docudesk', 'Back to templates') }}
			</NcButton>
			<h2>{{ isNew ? t('docudesk', 'New template') : (form.name || t('docudesk', 'Edit template')) }}</h2>
		</div>

		<div v-if="lockConflict" class="template-detail__lock-warning">
			<Lock :size="20" />
			{{ t('docudesk', 'This template is being edited by {user}', { user: lockConflict }) }}
		</div>

		<NcLoadingIcon v-if="templateStore.loading && !form.name" />

		<div v-else class="template-detail__body">
			<div class="template-detail__main">
				<div class="template-detail__meta">
					<NcTextField :value.sync="form.name"
						:label="t('docudesk', 'Name')"
						:required="true" />
					<NcTextField :value.sync="form.description"
						:label="t('docudesk', 'Description')" />
					<div class="template-detail__meta-row">
						<NcTextField v-if="isNew"
							:value.sync="form.namespace"
							:label="t('docudesk', 'Namespace')"
							:required="true" />
						<NcTextField :value.sync="form.category"
							:label="t('docudesk', 'Category')" />
						<NcSelect v-model="selectedFormat"
							:options="formatOptions"
							:label="t('docudesk', 'Page format')" />
						<NcSelect v-model="selectedOrientation"
							:options="orientationOptions"
							:label="t('docudesk', 'Orientation')" />
					</div>
					<NcTextField :value.sync="tagsInput"
						:label="t('docudesk', 'Tags (comma-separated)')" />
				</div>

				<TemplateEditor v-model="form.content" />

				<div class="template-detail__footer">
					<NcButton type="primary"
						:disabled="!isValid || templateStore.loading"
						@click="onSave">
						{{ t('docudesk', 'Save') }}
					</NcButton>
					<NcButton type="tertiary" @click="goBack">
						{{ t('docudesk', 'Cancel') }}
					</NcButton>
				</div>
			</div>

			<div class="template-detail__sidebar">
				<div class="template-detail__sidebar-tabs">
					<NcButton :type="activeTab === 'preview' ? 'primary' : 'tertiary'"
						@click="activeTab = 'preview'">
						{{ t('docudesk', 'Preview') }}
					</NcButton>
					<NcButton :type="activeTab === 'versions' ? 'primary' : 'tertiary'"
						@click="activeTab = 'versions'">
						{{ t('docudesk', 'Versions') }}
					</NcButton>
				</div>

				<TemplatePreview v-if="activeTab === 'preview'"
					:content="form.content" />
				<VersionHistory v-if="activeTab === 'versions'"
					:versions="templateStore.versions"
					:loading="versionsLoading"
					@restore="onRestore" />
			</div>
		</div>
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcLoadingIcon, NcSelect, NcTextField } from '@nextcloud/vue'
import ArrowLeft from 'vue-material-design-icons/ArrowLeft.vue'
import Lock from 'vue-material-design-icons/Lock.vue'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { useTemplateStore } from '../../store/modules/template.js'
import TemplateEditor from './TemplateEditor.vue'
import TemplatePreview from './TemplatePreview.vue'
import VersionHistory from './VersionHistory.vue'

export default {
	name: 'TemplateDetail',
	components: {
		NcButton,
		NcLoadingIcon,
		NcSelect,
		NcTextField,
		ArrowLeft,
		Lock,
		TemplateEditor,
		TemplatePreview,
		VersionHistory,
	},
	props: {
		templateId: {
			type: String,
			required: true,
		},
	},
	data() {
		return {
			form: {
				name: '',
				description: '',
				content: '',
				namespace: 'docudesk',
				category: '',
				format: 'A4',
				orientation: 'P',
			},
			tagsInput: '',
			activeTab: 'preview',
			lockConflict: null,
			versionsLoading: false,
			selectedFormat: { id: 'A4', label: 'A4' },
			selectedOrientation: { id: 'P', label: t('docudesk', 'Portrait') },
			formatOptions: [
				{ id: 'A4', label: 'A4' },
				{ id: 'A3', label: 'A3' },
				{ id: 'Letter', label: 'Letter' },
				{ id: 'Legal', label: 'Legal' },
			],
			orientationOptions: [
				{ id: 'P', label: t('docudesk', 'Portrait') },
				{ id: 'L', label: t('docudesk', 'Landscape') },
			],
		}
	},
	computed: {
		templateStore() {
			return useTemplateStore()
		},
		isNew() {
			return this.templateId === 'new'
		},
		isValid() {
			return this.form.name && this.form.content && (this.isNew ? this.form.namespace : true)
		},
	},
	watch: {
		selectedFormat(val) {
			if (val) this.form.format = val.id
		},
		selectedOrientation(val) {
			if (val) this.form.orientation = val.id
		},
	},
	async mounted() {
		if (!this.isNew) {
			await this.loadTemplate()
			await this.acquireLock()
			await this.loadVersions()
		}
	},
	beforeDestroy() {
		if (!this.isNew && !this.lockConflict) {
			this.releaseLock()
		}
	},
	methods: {
		t,
		async loadTemplate() {
			const data = await this.templateStore.fetchTemplate(this.templateId)
			if (data) {
				this.form = {
					name: data.name || '',
					description: data.description || '',
					content: data.content || '',
					namespace: data.namespace || '',
					category: data.category || '',
					format: data.format || 'A4',
					orientation: data.orientation || 'P',
				}
				this.tagsInput = (data.tags || []).join(', ')
				this.selectedFormat = this.formatOptions.find(o => o.id === this.form.format) || this.formatOptions[0]
				this.selectedOrientation = this.orientationOptions.find(o => o.id === this.form.orientation) || this.orientationOptions[0]
			}
		},
		async loadVersions() {
			this.versionsLoading = true
			await this.templateStore.fetchVersions(this.templateId)
			this.versionsLoading = false
		},
		async acquireLock() {
			const result = await this.templateStore.acquireLock(this.templateId)
			if (!result) {
				this.lockConflict = this.templateStore.error || t('docudesk', 'another user')
			}
		},
		async releaseLock() {
			await this.templateStore.releaseLock(this.templateId)
		},
		async onSave() {
			const tags = this.tagsInput
				? this.tagsInput.split(',').map(t => t.trim()).filter(Boolean)
				: []

			const data = {
				...this.form,
				tags,
			}

			let result
			if (this.isNew) {
				result = await this.templateStore.createTemplate(data)
			} else {
				result = await this.templateStore.updateTemplate(this.templateId, data)
			}

			if (result) {
				showSuccess(t('docudesk', 'Template saved'))
				if (this.isNew && result.id) {
					this.$router.replace({ name: 'TemplateDetail', params: { id: result.id } })
				} else {
					await this.loadVersions()
				}
			} else {
				showError(t('docudesk', 'Failed to save template'))
			}
		},
		async onRestore(version) {
			if (confirm(t('docudesk', 'Restore to version {n}? Current changes will be saved as a new version.', { n: version.version }))) {
				const result = await this.templateStore.restoreVersion(this.templateId, version.id)
				if (result) {
					showSuccess(t('docudesk', 'Template restored'))
					await this.loadTemplate()
					await this.loadVersions()
				} else {
					showError(t('docudesk', 'Failed to restore version'))
				}
			}
		},
		goBack() {
			this.$router.push({ name: 'Templates' })
		},
	},
}
</script>

<style scoped lang="scss">
.template-detail {
	padding: 20px;

	&__header {
		display: flex;
		align-items: center;
		gap: 12px;
		margin-bottom: 20px;

		h2 {
			margin: 0;
		}
	}

	&__lock-warning {
		display: flex;
		align-items: center;
		gap: 8px;
		padding: 12px 16px;
		background: var(--color-warning);
		color: white;
		border-radius: var(--border-radius);
		margin-bottom: 16px;
	}

	&__body {
		display: grid;
		grid-template-columns: 1fr 350px;
		gap: 0;
	}

	&__main {
		display: flex;
		flex-direction: column;
		gap: 16px;
	}

	&__meta {
		display: flex;
		flex-direction: column;
		gap: 8px;
	}

	&__meta-row {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
		gap: 8px;
	}

	&__footer {
		display: flex;
		gap: 8px;
		padding: 16px 0;
	}

	&__sidebar {
		min-height: 500px;
	}

	&__sidebar-tabs {
		display: flex;
		gap: 4px;
		padding: 0 16px 12px;
		border-bottom: 1px solid var(--color-border);
		border-left: 1px solid var(--color-border);
	}
}
</style>
