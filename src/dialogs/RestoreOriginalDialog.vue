<template>
	<NcDialog
		:name="t('filinq', 'Restore original')"
		:noClose="busy"
		@closing="$emit('close')">
		<template #default>
			<div class="restore-original-dialog">
				<NcNoteCard v-if="!result" type="warning">
					{{
						t(
							'filinq',
							'Restoring puts the real names back. Your name, the time and this document are written to the audit trail before anything is restored.',
						)
					}}
				</NcNoteCard>
				<p v-if="!result" class="restore-original-dialog__count">
					{{
						n(
							'filinq',
							'This copy has a key for %n placeholder.',
							'This copy has a key for %n placeholders.',
							entryCount,
						)
					}}
				</p>
				<NcNoteCard v-if="error" type="error">
					{{ error }}
				</NcNoteCard>
				<p v-if="result && result.mode === 'copy'">
					{{
						t(
							'filinq',
							'Saved as {name}, next to the anonymised copy. The anonymised copy is unchanged.',
							{ name: result.fileName },
						)
					}}
				</p>
				<template v-if="result && result.mode === 'report'">
					<p>
						{{
							t(
								'filinq',
								'This format cannot be rewritten safely, so no file was made. These are the names behind the placeholders.',
							)
						}}
					</p>
					<table class="restore-original-dialog__report">
						<caption class="hidden-visually">
							{{
								t(
									'filinq',
									'Placeholders and the values they replaced',
								)
							}}
						</caption>
						<thead>
							<tr>
								<th scope="col">
									{{ t('filinq', 'Placeholder') }}
								</th>
								<th scope="col">
									{{ t('filinq', 'Original value') }}
								</th>
							</tr>
						</thead>
						<tbody>
							<tr
								v-for="entry in result.entries"
								:key="entry.placeholder">
								<td>{{ entry.placeholder }}</td>
								<td>{{ entry.originalValue }}</td>
							</tr>
						</tbody>
					</table>
				</template>
			</div>
		</template>
		<template #actions>
			<NcButton :disabled="busy" @click="$emit('close')">
				{{ result ? t('filinq', 'Close') : t('filinq', 'Cancel') }}
			</NcButton>
			<NcButton
				v-if="!result"
				variant="warning"
				:disabled="busy"
				@click="onConfirm">
				<template v-if="busy" #icon>
					<NcLoadingIcon :size="20" />
				</template>
				{{ t('filinq', 'Restore and log') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<script>
import { translatePlural as n, translate as t } from '@nextcloud/l10n'
import { NcButton, NcDialog, NcLoadingIcon, NcNoteCard } from '@nextcloud/vue'
import { restoreOriginal } from '../services/pseudonymisation.js'

/**
 * Confirm and run the restore of one reversibly anonymised copy.
 *
 * The dialog says the restore is audit-logged before the user confirms, and the
 * confirm button says it too. Lives in src/dialogs/ per ADR-004.
 *
 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-4.2
 */
export default {
	name: 'RestoreOriginalDialog',
	components: { NcButton, NcDialog, NcLoadingIcon, NcNoteCard },
	props: {
		/** The anonymisation link to restore. */
		linkId: {
			type: String,
			required: true,
		},

		/** How many placeholders the key covers. */
		entryCount: {
			type: Number,
			default: 0,
		},
	},

	emits: ['close', 'restored'],
	data() {
		return {
			busy: false,
			error: '',
			result: null,
		}
	},

	methods: {
		t,
		n,
		/**
		 * Ask the server to restore, and show what came back.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-4.2
		 */
		async onConfirm() {
			this.busy = true
			this.error = ''
			const answer = await restoreOriginal(this.linkId)
			this.busy = false
			if (!answer.ok) {
				this.error = answer.error
				return
			}
			this.result = answer.result
			this.$emit('restored', answer.result)
		},
	},
}
</script>

<style scoped>
.restore-original-dialog {
	display: flex;
	flex-direction: column;
	gap: var(--default-grid-baseline, 4px);
}

.restore-original-dialog__report {
	width: 100%;
	border-collapse: collapse;
}

.restore-original-dialog__report th,
.restore-original-dialog__report td {
	text-align: start;
	padding: calc(var(--default-grid-baseline, 4px) * 2);
	border-bottom: 1px solid var(--color-border);
}
</style>
