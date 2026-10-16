<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

Print separator sheets: pick the scan profile, type the case numbers, and get
one PDF with a sheet per case number to put between the documents of a batch.

@spec openspec/changes/scan-intake-with-separator-sheets/tasks.md#task-2.2
-->

<template>
	<NcDialog
		:name="t('filinq', 'Print separator sheets')"
		@closing="$emit('close')">
		<template #default>
			<p>
				{{
					t(
						'filinq',
						'Put a sheet in front of each document in the scanner batch. The batch is cut at every sheet, and each part lands in the inbox with its case number.',
					)
				}}
			</p>
			<NcNoteCard v-if="error" type="error">
				{{ error }}
			</NcNoteCard>
			<NcNoteCard v-else-if="!loading && profiles.length === 0" type="warning">
				{{
					t(
						'filinq',
						'No scan profile is set up yet. An administrator adds one in the settings.',
					)
				}}
			</NcNoteCard>
			<NcSelect
				v-model="profile"
				:options="profiles"
				label="label"
				:inputLabel="t('filinq', 'Scan profile')"
				:loading="loading"
				data-testid="separator-profile" />
			<NcTextArea
				v-model="caseNumbersText"
				:label="t('filinq', 'Case numbers, one per line')"
				data-testid="separator-case-numbers" />
		</template>
		<template #actions>
			<NcButton @click="$emit('close')">
				{{ t('filinq', 'Cancel') }}
			</NcButton>
			<NcButton
				variant="primary"
				:disabled="!canPrint"
				data-testid="separator-print"
				@click="print">
				{{
					n(
						'filinq',
						'Print %n sheet',
						'Print %n sheets',
						caseNumbers.length,
					)
				}}
			</NcButton>
		</template>
	</NcDialog>
</template>

<script>
import { translatePlural as n, translate as t } from '@nextcloud/l10n'
import { NcButton, NcDialog, NcNoteCard, NcSelect, NcTextArea } from '@nextcloud/vue'
import {
	listScanProfiles,
	parseCaseNumbers,
	renderSeparatorSheets,
} from '../services/separatorSheets.js'

export default {
	name: 'SeparatorSheetsDialog',
	components: { NcButton, NcDialog, NcNoteCard, NcSelect, NcTextArea },

	emits: ['close'],

	data() {
		return {
			profiles: [],
			profile: null,
			caseNumbersText: '',
			loading: true,
			busy: false,
			error: '',
		}
	},

	computed: {
		/**
		 * The case numbers typed so far, one sheet each.
		 *
		 * @spec openspec/changes/scan-intake-with-separator-sheets/tasks.md#task-2.2
		 */
		caseNumbers() {
			return parseCaseNumbers(this.caseNumbersText)
		},

		/**
		 * Whether a profile is chosen and at least one case number is typed.
		 *
		 * @spec openspec/changes/scan-intake-with-separator-sheets/tasks.md#task-2.2
		 */
		canPrint() {
			return (
				Boolean(this.profile?.id)
				&& this.caseNumbers.length > 0
				&& !this.busy
			)
		},
	},

	/**
	 * Read the scan profiles, and pick the only one when there is one.
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/tasks.md#task-2.2
	 */
	async mounted() {
		try {
			this.profiles = await listScanProfiles()
			if (this.profiles.length === 1) {
				this.profile = this.profiles[0]
			}
		} catch {
			this.error = t('filinq', 'The scan profiles could not be read.')
		} finally {
			this.loading = false
		}
	},

	methods: {
		t,
		n,

		/**
		 * Render the sheets and hand the PDF to the browser to save or print.
		 *
		 * @return {Promise<void>} Resolves once the download has started.
		 * @spec openspec/changes/scan-intake-with-separator-sheets/tasks.md#task-2.2
		 */
		async print() {
			this.busy = true
			this.error = ''
			try {
				const pdf = await renderSeparatorSheets(
					this.profile.id,
					this.caseNumbers,
				)
				const url = URL.createObjectURL(pdf)
				const link = document.createElement('a')
				link.href = url
				link.download = 'scheidingsvellen.pdf'
				link.click()
				URL.revokeObjectURL(url)
				this.$emit('close')
			} catch {
				this.error = t(
					'filinq',
					'The separator sheets could not be made. Check the case numbers and try again.',
				)
			} finally {
				this.busy = false
			}
		},
	},
}
</script>
