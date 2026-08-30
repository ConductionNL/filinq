<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

@spec openspec/changes/anonymiser-backend-warning/tasks.md#task-5
-->

<template>
	<div v-if="visible" class="anonymiser-backend-warning">
		<NcNoteCard type="warning" class="anonymiser-backend-warning__card">
			<div class="anonymiser-backend-warning__body">
				<p
					v-if="!appApiInstalled"
					class="anonymiser-backend-warning__appapi-line">
					{{
						t(
							'filinq',
							'AppAPI is not installed. Install AppAPI from the App Store before installing OpenAnonymiser.',
						)
					}}
				</p>
				<p>
					{{
						t(
							'filinq',
							'Entity recognition is running in regex-only mode. For higher-quality anonymisation, install one of the supported backends:',
						)
					}}
				</p>
				<ul class="anonymiser-backend-warning__links">
					<li>
						<a
							:href="appStoreUrl('openanonymiser_light')"
							class="anonymiser-backend-warning__link">
							{{ t('filinq', 'OpenAnonymiser Light (CPU)') }}
						</a>
						{{ t('filinq', '— lightweight, no GPU required') }}
					</li>
					<li>
						<a
							:href="appStoreUrl('openanonymiser')"
							class="anonymiser-backend-warning__link">
							{{ t('filinq', 'OpenAnonymiser (GPU)') }}
						</a>
						{{ t('filinq', '— high accuracy, requires a GPU') }}
					</li>
					<li>
						<a
							href="/settings/admin/openregister"
							class="anonymiser-backend-warning__link">
							{{
								t(
									'filinq',
									'Configure a custom anonymisation endpoint',
								)
							}}
						</a>
						{{ t('filinq', '— via OpenRegister settings') }}
					</li>
				</ul>
				<div class="anonymiser-backend-warning__actions">
					<NcButton variant="tertiary" @click="dismissWarning">
						{{ t('filinq', 'Dismiss') }}
					</NcButton>
				</div>
			</div>
		</NcNoteCard>
	</div>
</template>

<script>
import { showError } from '@nextcloud/dialogs'
import { NcButton, NcNoteCard } from '@nextcloud/vue'

export default {
	name: 'AnonymiserBackendWarning',

	components: {
		NcNoteCard,
		NcButton,
	},

	props: {
		/**
		 * Whether the warning banner is visible.
		 * True when method === 'regex' and the admin has not dismissed it.
		 */
		showWarning: {
			type: Boolean,
			default: false,
		},

		/**
		 * Whether AppAPI is installed on this Nextcloud instance.
		 * When false, a leading notice instructs the admin to install AppAPI first.
		 */
		appApiInstalled: {
			type: Boolean,
			default: false,
		},
	},

	emits: ['dismissed'],

	data() {
		return {
			visible: this.showWarning,
			dismissing: false,
		}
	},

	watch: {
		showWarning(newVal) {
			this.visible = newVal
		},
	},

	methods: {
		/**
		 * Build the Nextcloud App Store deep-link URL for a given app id.
		 * Uses /settings/apps/discover/{appId} which auto-opens the App Store
		 * sidebar with the app's details and "Download and enable" action.
		 *
		 * @param {string} appId The Nextcloud app store ID.
		 * @return {string} The full deep-link URL.
		 *
		 * @spec openspec/changes/anonymiser-backend-warning/tasks.md#task-9
		 */
		appStoreUrl(appId) {
			return '/settings/apps/discover/' + encodeURIComponent(appId)
		},

		/**
		 * Dismiss the banner for the current admin by calling the dismiss endpoint.
		 * The dismissal is persisted per-admin via IConfig user values.
		 *
		 * @spec openspec/changes/anonymiser-backend-warning/tasks.md#task-8
		 */
		async dismissWarning() {
			this.dismissing = true
			try {
				const response = await fetch(
					'/index.php/apps/filinq/api/admin/anonymiser-warning/dismiss',
					{ method: 'POST' },
				)
				if (response.ok === false) {
					throw new Error('HTTP ' + response.status)
				}
				this.visible = false
				this.$emit('dismissed')
			} catch (err) {
				showError(
					t('filinq', 'Failed to dismiss the anonymiser backend warning'),
				)
			} finally {
				this.dismissing = false
			}
		},
	},
}
</script>

<style scoped>
.anonymiser-backend-warning {
	margin-bottom: 16px;
}

.anonymiser-backend-warning__card {
	width: 100%;
}

.anonymiser-backend-warning__body {
	display: flex;
	flex-direction: column;
	gap: 8px;
}

.anonymiser-backend-warning__appapi-line {
	font-weight: bold;
}

.anonymiser-backend-warning__links {
	list-style: disc;
	margin-left: 20px;
}

.anonymiser-backend-warning__links li {
	margin-bottom: 4px;
}

.anonymiser-backend-warning__link {
	color: var(--color-primary);
	text-decoration: underline;
}

.anonymiser-backend-warning__link:focus-visible {
	outline: 2px solid var(--color-primary);
	outline-offset: 2px;
	border-radius: 2px;
}

.anonymiser-backend-warning__actions {
	display: flex;
	justify-content: flex-end;
	margin-top: 4px;
}
</style>
