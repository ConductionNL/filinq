<script setup>
import { translate as t, translatePlural as n } from '@nextcloud/l10n'
import { anonymizationStore } from '../../store/store.js'
</script>

<template>
	<div class="docudesk-dashboard-widget">
		<!-- Results table -->
		<div v-if="anonymizationStore.hasFiles" class="results-area">
			<table class="results-table">
				<thead>
					<tr>
						<th>{{ t('docudesk', 'File') }}</th>
						<th class="col-number">
							{{ t('docudesk', 'Entities') }}
						</th>
						<th class="col-number">
							{{ t('docudesk', 'Removed') }}
						</th>
						<th class="col-action">
							{{ t('docudesk', 'Result') }}
						</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="file in anonymizationStore.files" :key="file.id">
						<!-- Original file name / link -->
						<td class="col-file">
							<a
								v-if="file.filePath"
								:href="fileLink(file.filePath)"
								target="_blank"
								class="file-link"
								:title="file.name">
								{{ file.name }}
							</a>
							<span v-else :title="file.name">{{ file.name }}</span>
						</td>

						<!-- Entities detected -->
						<td class="col-number">
							<template v-if="file.status === 'completed' || file.status === 'anonymizing'">
								{{ file.entityCount }}
							</template>
							<template v-else-if="file.status === 'error'">
								&mdash;
							</template>
							<NcLoadingIcon v-else :size="16" />
						</td>

						<!-- Entities removed -->
						<td class="col-number">
							<template v-if="file.status === 'completed'">
								{{ file.replacementCount }}
							</template>
							<template v-else-if="file.status === 'error'">
								&mdash;
							</template>
							<NcLoadingIcon v-else-if="file.status === 'anonymizing'" :size="16" />
							<template v-else>
								&mdash;
							</template>
						</td>

						<!-- Download / status -->
						<td class="col-action">
							<a
								v-if="file.status === 'completed' && file.anonymizedFilePath"
								:href="downloadUrl(file.anonymizedFilePath)"
								download
								class="download-link">
								{{ t('docudesk', 'Download') }}
							</a>
							<span v-else-if="file.status === 'completed' && !file.anonymizedFilePath" class="status-clean">
								{{ t('docudesk', 'Clean') }}
							</span>
							<span v-else-if="file.status === 'error'" class="status-error" :title="file.error">
								{{ t('docudesk', 'Error') }}
							</span>
							<span v-else class="status-label">
								{{ statusLabel(file.status) }}
							</span>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<!-- Drop zone (always shown when not processing, or below table) -->
		<div class="upload-area" :class="{ compact: anonymizationStore.hasFiles }">
			<div
				class="drop-zone"
				:class="{ dragging: isDragging }"
				@dragover.prevent="isDragging = true"
				@dragleave.prevent="isDragging = false"
				@drop.prevent="handleDrop"
				@click="$refs.fileInput.click()">
				<Upload :size="24" />
				<p class="drop-text">
					{{ anonymizationStore.hasFiles
						? t('docudesk', 'Drop more files to anonymize')
						: t('docudesk', 'Drop files to anonymize')
					}}
				</p>
				<input
					ref="fileInput"
					type="file"
					multiple
					class="file-input"
					@change="handleFileSelect">
			</div>
		</div>

		<!-- Footer -->
		<a class="widget-footer" :href="appUrl">
			{{ t('docudesk', 'Open DocuDesk') }}
		</a>
	</div>
</template>

<script>
import { NcLoadingIcon } from '@nextcloud/vue'
import { generateUrl, generateRemoteUrl } from '@nextcloud/router'

import Upload from 'vue-material-design-icons/Upload.vue'

export default {
	name: 'AnonymizationDashboardWidget',
	components: {
		NcLoadingIcon,
		Upload,
	},
	props: {
		title: {
			type: String,
			required: true,
		},
	},
	data() {
		return {
			isDragging: false,
		}
	},
	computed: {
		appUrl() {
			return generateUrl('/apps/docudesk')
		},
	},
	methods: {
		handleDrop(event) {
			this.isDragging = false
			const files = event.dataTransfer?.files
			if (files && files.length > 0) {
				anonymizationStore.addFiles(files)
			}
		},
		handleFileSelect(event) {
			const files = event.target?.files
			if (files && files.length > 0) {
				anonymizationStore.addFiles(files)
			}
			// Reset input so same files can be re-selected
			event.target.value = ''
		},
		fileLink(filePath) {
			// filePath is like /admin/files/DocuDesk/file.txt
			// Nextcloud files app URL: /index.php/apps/files/?dir=/DocuDesk&file=file.txt
			// Or simply link to the files app with the directory
			const parts = filePath.split('/')
			// Remove user prefix (e.g. /admin/files/) to get the relative path
			const filesIndex = parts.indexOf('files')
			if (filesIndex >= 0) {
				const relativePath = '/' + parts.slice(filesIndex + 1).join('/')
				const dir = relativePath.substring(0, relativePath.lastIndexOf('/')) || '/'
				const file = relativePath.substring(relativePath.lastIndexOf('/') + 1)
				return generateUrl('/apps/files/?dir={dir}&scrollto={file}', { dir, file })
			}
			return generateUrl('/apps/files')
		},
		downloadUrl(filePath) {
			// filePath is like /admin/files/DocuDesk/file_anonymized.txt
			// WebDAV download: /remote.php/webdav/DocuDesk/file_anonymized.txt
			const parts = filePath.split('/')
			const filesIndex = parts.indexOf('files')
			if (filesIndex >= 0) {
				const relativePath = parts.slice(filesIndex + 1).join('/')
				return generateRemoteUrl('webdav') + '/' + relativePath
			}
			return generateRemoteUrl('webdav')
		},
		statusLabel(status) {
			const labels = {
				queued: t('docudesk', 'Queued'),
				uploading: t('docudesk', 'Uploading...'),
				extracting: t('docudesk', 'Detecting...'),
				anonymizing: t('docudesk', 'Anonymizing...'),
			}
			return labels[status] || status
		},
	},
}
</script>

<style scoped>
.docudesk-dashboard-widget {
	display: flex;
	flex-direction: column;
	height: 100%;
	padding: 0;
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

.col-action {
	text-align: right;
	width: 80px;
	white-space: nowrap;
}

.file-link {
	color: var(--color-primary);
	text-decoration: none;
}

.file-link:hover {
	text-decoration: underline;
}

.download-link {
	color: var(--color-success);
	text-decoration: none;
	font-weight: 500;
}

.download-link:hover {
	text-decoration: underline;
}

.status-clean {
	color: var(--color-text-maxcontrast);
	font-style: italic;
}

.status-error {
	color: var(--color-error);
	cursor: help;
}

.status-label {
	color: var(--color-text-maxcontrast);
}

/* Upload area */
.upload-area {
	padding: 12px;
}

.upload-area.compact {
	padding: 6px 12px 8px;
}

.drop-zone {
	width: 100%;
	border: 2px dashed var(--color-border);
	border-radius: var(--border-radius-large);
	padding: 16px 12px;
	text-align: center;
	cursor: pointer;
	transition: border-color 0.2s, background-color 0.2s;
}

.upload-area.compact .drop-zone {
	padding: 8px 12px;
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 8px;
}

.upload-area.compact .drop-text {
	margin: 0;
}

.drop-zone:hover,
.drop-zone.dragging {
	border-color: var(--color-primary);
	background-color: var(--color-primary-element-light);
}

.drop-text {
	margin: 4px 0 0 0;
	color: var(--color-text-maxcontrast);
	font-size: 0.85rem;
}

.file-input {
	display: none;
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
