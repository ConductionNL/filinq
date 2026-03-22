<script setup>
import { translate as t, translatePlural as n } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
</script>

<template>
	<div class="file-entities-widget">
		<!-- Loading state -->
		<div v-if="loading" class="loading-area">
			<NcLoadingIcon :size="28" />
			<p class="loading-text">
				{{ t('docudesk', 'Loading files...') }}
			</p>
		</div>

		<!-- Error state -->
		<div v-else-if="error" class="error-area">
			<p class="error-text">
				{{ error }}
			</p>
		</div>

		<!-- Empty state -->
		<div v-else-if="files.length === 0" class="empty-area">
			<FileDocumentOutline :size="32" class="empty-icon" />
			<p class="empty-text">
				{{ t('docudesk', 'No processed files yet') }}
			</p>
		</div>

		<!-- Files table -->
		<div v-else class="results-area">
			<table class="results-table">
				<thead>
					<tr>
						<th>{{ t('docudesk', 'File') }}</th>
						<th class="col-number">
							{{ t('docudesk', 'Entities') }}
						</th>
						<th class="col-risk">
							{{ t('docudesk', 'Risk') }}
						</th>
						<th class="col-status">
							{{ t('docudesk', 'Status') }}
						</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="file in files" :key="file.fileId">
						<td class="col-file">
							<a
								:href="fileLink(file.filePath)"
								target="_blank"
								class="file-link"
								:title="file.fileName">
								{{ file.fileName }}
							</a>
						</td>
						<td class="col-number">
							{{ file.entityCount }}
						</td>
						<td class="col-risk">
							<span :class="'risk-badge risk-' + (file.riskLevel || 'none')">
								{{ riskLevelLabel(file.riskLevel) }}
							</span>
						</td>
						<td class="col-status">
							<span :class="'status-badge status-' + file.status">
								{{ statusLabel(file.status) }}
							</span>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<!-- Footer -->
		<a class="widget-footer" :href="appUrl">
			{{ t('docudesk', 'Open DocuDesk') }}
		</a>
	</div>
</template>

<script>
import { NcLoadingIcon } from '@nextcloud/vue'
import FileDocumentOutline from 'vue-material-design-icons/FileDocumentOutline.vue'

export default {
	name: 'FileEntitiesDashboardWidget',
	components: {
		NcLoadingIcon,
		FileDocumentOutline,
	},
	props: {
		title: {
			type: String,
			required: true,
		},
	},
	data() {
		return {
			files: [],
			loading: true,
			error: null,
		}
	},
	computed: {
		appUrl() {
			return generateUrl('/apps/docudesk')
		},
	},
	mounted() {
		this.fetchFiles()
	},
	methods: {
		async fetchFiles() {
			this.loading = true
			this.error = null
			try {
				const response = await axios.get(
					generateUrl('/apps/docudesk/api/anonymization/files'),
				)
				this.files = response.data
			} catch (e) {
				console.error('[DocuDesk] Failed to fetch processed files:', e)
				this.error = t('docudesk', 'Failed to load files')
			} finally {
				this.loading = false
			}
		},
		fileLink(filePath) {
			const parts = filePath.split('/')
			const filesIndex = parts.indexOf('files')
			if (filesIndex >= 0) {
				const relativePath = '/' + parts.slice(filesIndex + 1).join('/')
				const dir = relativePath.substring(0, relativePath.lastIndexOf('/')) || '/'
				const file = relativePath.substring(relativePath.lastIndexOf('/') + 1)
				return generateUrl('/apps/files/?dir={dir}&scrollto={file}', { dir, file })
			}
			return generateUrl('/apps/files')
		},
		riskLevelLabel(level) {
			const labels = {
				none: t('docudesk', 'None'),
				low: t('docudesk', 'Low'),
				medium: t('docudesk', 'Medium'),
				high: t('docudesk', 'High'),
				very_high: t('docudesk', 'Very High'),
			}
			return labels[level] || labels.none
		},
		statusLabel(status) {
			const labels = {
				uploaded: t('docudesk', 'Uploaded'),
				extracted: t('docudesk', 'Extracted'),
				anonymized: t('docudesk', 'Anonymized'),
			}
			return labels[status] || status
		},
	},
}
</script>

<style scoped>
.file-entities-widget {
	display: flex;
	flex-direction: column;
	height: 100%;
	padding: 0;
}

/* Loading */
.loading-area {
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 24px;
}

.loading-text {
	margin-top: 8px;
	color: var(--color-text-maxcontrast);
	font-size: 0.85rem;
}

/* Error */
.error-area {
	flex: 1;
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 24px;
}

.error-text {
	color: var(--color-error);
	font-size: 0.85rem;
}

/* Empty */
.empty-area {
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 24px;
}

.empty-icon {
	color: var(--color-text-maxcontrast);
}

.empty-text {
	margin-top: 8px;
	color: var(--color-text-maxcontrast);
	font-size: 0.85rem;
}

/* Results table */
.results-area {
	flex: 1;
	overflow-y: auto;
	padding: 0 8px;
}

.results-table {
	width: 100%;
	border-collapse: collapse;
	font-size: 0.85rem;
}

.results-table th {
	text-align: left;
	font-weight: 600;
	padding: 4px 6px;
	border-bottom: 1px solid var(--color-border);
	color: var(--color-text-maxcontrast);
	font-size: 0.8rem;
	white-space: nowrap;
}

.results-table td {
	padding: 4px 6px;
	border-bottom: 1px solid var(--color-border-dark, var(--color-border));
	vertical-align: middle;
}

.col-file {
	max-width: 140px;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}

.col-number {
	text-align: center;
	width: 60px;
}

.col-risk {
	text-align: center;
	width: 80px;
	white-space: nowrap;
}

.col-status {
	text-align: right;
	width: 90px;
	white-space: nowrap;
}

.file-link {
	color: var(--color-primary);
	text-decoration: none;
}

.file-link:hover {
	text-decoration: underline;
}

/* Status badges */
.status-badge {
	display: inline-block;
	padding: 2px 8px;
	border-radius: var(--border-radius-pill);
	font-size: 0.75rem;
	font-weight: 500;
}

.status-uploaded {
	background-color: var(--color-background-dark);
	color: var(--color-text-maxcontrast);
}

.status-extracted {
	background-color: var(--color-warning-element-light, #fef3cd);
	color: var(--color-warning-text, #856404);
}

.status-anonymized {
	background-color: var(--color-success-element-light, #d4edda);
	color: var(--color-success-text, #155724);
}

/* Risk badges */
.risk-badge {
	display: inline-block;
	padding: 2px 8px;
	border-radius: var(--border-radius-pill);
	font-size: 0.75rem;
	font-weight: 500;
}

.risk-none {
	background-color: var(--color-background-dark);
	color: var(--color-text-maxcontrast);
}

.risk-low {
	background-color: var(--color-info-element-light, #cce5ff);
	color: var(--color-info-text, #004085);
}

.risk-medium {
	background-color: var(--color-warning-element-light, #fef3cd);
	color: var(--color-warning-text, #856404);
}

.risk-high {
	background-color: var(--color-error-element-light, #f8d7da);
	color: var(--color-error-text, #721c24);
}

.risk-very_high {
	background-color: var(--color-error);
	color: white;
}

/* Footer link */
.widget-footer {
	display: block;
	text-align: center;
	padding: 8px;
	border-top: 1px solid var(--color-border);
	color: var(--color-text-maxcontrast);
	font-size: 0.85rem;
	text-decoration: none;
}

.widget-footer:hover {
	background-color: var(--color-background-hover);
	color: var(--color-main-text);
}
</style>
