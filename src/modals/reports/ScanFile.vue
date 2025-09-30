<script setup>
import { reportStore, navigationStore } from '../../store/store.js'
</script>

<template>
	<NcModal label-id="Scan File modal"
		@close="closeDialog">
		<div class="modal__content">
			<div class="scan-file">
				<h3>Scan File: {{ reportStore.reportItem.name }}</h3>

				<div class="scan-details">
					<p><strong>Report:</strong> {{ reportStore.reportItem.name }}</p>
					<p><strong>Summary:</strong> {{ reportStore.reportItem.summary }}</p>
					<p><strong>Rules:</strong> {{ reportStore.reportItem.rules?.length || 0 }}</p>
				</div>

				<div class="file-upload">
					<h4>Upload File to Scan</h4>
					<input type="file" @change="handleFileUpload">
				</div>

				<div v-if="scanResults" class="scan-results">
					<h4>Scan Results:</h4>
					<div class="scan-results-container">
						<div class="score">
							<p><b>Score:</b> {{ scanResults.score }}</p>
							<p><b>Status:</b> {{ scanResults.status }}</p>
						</div>

						<div v-if="scanResults.matches" class="matches">
							<h4>Rule Matches:</h4>
							<div class="matches-table-container">
								<table class="matches-table">
									<thead>
										<tr>
											<th>Rule</th>
											<th>Location</th>
											<th>Match</th>
										</tr>
									</thead>
									<tbody>
										<tr v-for="(match, index) in scanResults.matches" :key="index">
											<td><strong>{{ match.rule }}</strong></td>
											<td>{{ match.location }}</td>
											<td>{{ match.text }}</td>
										</tr>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="button-container">
				<NcButton type="primary"
					:disabled="!selectedFile"
					@click="scanFile">
					<template #icon>
						<Scan :size="20" />
					</template>
					Scan File
				</NcButton>

				<NcButton @click="closeDialog">
					<template #icon>
						<Cancel :size="20" />
					</template>
					Close
				</NcButton>
			</div>
		</div>
	</NcModal>
</template>

<script>
import {
	NcModal,
	NcButton,
} from '@nextcloud/vue'

import Cancel from 'vue-material-design-icons/Cancel.vue'
import Scan from 'vue-material-design-icons/FileSearch.vue'

export default {
	name: 'ScanFile',
	components: {
		NcModal,
		NcButton,
		Cancel,
		Scan,
	},
	data() {
		return {
			selectedFile: null,
			scanResults: null,
		}
	},
	methods: {
		closeDialog() {
			navigationStore.setModal(null)
			this.selectedFile = null
			this.scanResults = null
		},
		handleFileUpload(event) {
			this.selectedFile = event.target.files[0]
		},
		async scanFile() {
			if (!this.selectedFile) return

			const formData = new FormData()
			formData.append('file', this.selectedFile)
			formData.append('reportId', reportStore.reportItem.id)

			try {
				const response = await fetch('/reports/scan', {
					method: 'POST',
					body: formData,
				})

				if (!response.ok) {
					throw new Error('Scan failed')
				}

				this.scanResults = await response.json()
			} catch (error) {
				console.error('Error scanning file:', error)
			}
		},
	},
}
</script>

<style scoped>
.modal__content {
	margin: 0.8rem;
}

.scan-file {
	border-bottom: 1px solid #ccc;
	padding: 0 0 10px 0;
	margin: 0 0 10px 0;
}

.scan-file > *:not(:last-child) {
	margin-bottom: 1rem;
}

.button-container {
	display: flex;
	gap: 10px;
	justify-content: flex-end;
}

.matches-table-container {
	max-height: 350px;
	overflow-y: auto;
}

.matches-table thead {
	position: sticky;
	top: 0;
	background-color: var(--color-main-background);
}

.matches-table th {
	font-weight: bold;
	font-size: 1rem;
}

.matches-table th,
.matches-table td {
	padding: 0.5rem;
	text-align: left;
}

.score {
	display: flex;
	gap: 2rem;
	justify-content: center;
	font-size: 1.2rem;
}
</style>
