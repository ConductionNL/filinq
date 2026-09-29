<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

@spec openspec/changes/anonymiser-backend-warning/tasks.md#task-5
@spec openspec/changes/anonymisation-fails-closed-without-a-detector/tasks.md#task-4
-->

<template>
	<div v-if="visible || activeLine" class="anonymiser-backend-warning">
		<p v-if="activeLine && !visible" class="anonymiser-backend-warning__active">
			{{ activeLine }}
		</p>
		<NcNoteCard
			v-if="visible"
			:type="refusing ? 'error' : 'warning'"
			class="anonymiser-backend-warning__card">
			<div class="anonymiser-backend-warning__body">
				<template v-if="refusing">
					<p>{{ refusalLine }}</p>
					<p>
						<a
							href="/settings/admin/openregister"
							class="anonymiser-backend-warning__link">
							{{ t('filinq', 'Open the OpenRegister settings') }}
						</a>
					</p>
				</template>
				<template v-else>
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
					<p v-if="fellBack">
						{{
							t(
								'filinq',
								'OpenRegister is set to {active}, but that detector is unavailable.',
								{ active: activeMethod },
							)
						}}
					</p>
					<p>
						{{
							t(
								'filinq',
								'Entity detection runs on {method}. It finds fixed patterns such as BSN, IBAN and email addresses, but no names.',
								{ method: effectiveMethod || 'regex' },
							)
						}}
					</p>
					<p>
						{{
							t(
								'filinq',
								'For better anonymisation, install one of these detectors:',
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
				</template>
			</div>
		</NcNoteCard>
	</div>
</template>

<script>
import { showError } from '@nextcloud/dialogs'
import { NcButton, NcNoteCard } from '@nextcloud/vue'
import { isRefusing } from '../services/anonymiserBackendState.js'

export default {
	name: 'AnonymiserBackendWarning',

	components: {
		NcNoteCard,
		NcButton,
	},

	props: {
		/**
		 * Whether the warning banner is visible. The server decides: a regex
		 * warning the admin dismissed is hidden, a refusal never is.
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

		/**
		 * The warning the server derived from OpenRegister's state: unknown,
		 * disabled, unavailable, regex, or null.
		 */
		warning: {
			type: String,
			default: null,
		},

		/**
		 * The method OpenRegister is set to.
		 */
		activeMethod: {
			type: String,
			default: '',
		},

		/**
		 * The method OpenRegister will actually run.
		 */
		effectiveMethod: {
			type: String,
			default: '',
		},

		/**
		 * Name the detector in use even when there is nothing to warn about.
		 */
		showActiveBackend: {
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

	computed: {
		/**
		 * Whether this warning means every anonymisation is refused.
		 *
		 * @return {boolean} True for unknown, disabled and unavailable.
		 * @spec openspec/changes/anonymisation-fails-closed-without-a-detector/tasks.md#task-4
		 */
		refusing() {
			return isRefusing(this.warning)
		},

		/**
		 * Whether OpenRegister fell back from the detector it is set to.
		 *
		 * @return {boolean} True when active and effective differ.
		 * @spec openspec/changes/anonymisation-fails-closed-without-a-detector/tasks.md#task-4
		 */
		fellBack() {
			return this.activeMethod !== '' && this.activeMethod !== this.effectiveMethod
		},

		/**
		 * Why anonymisation is refused, in one line.
		 *
		 * @return {string} The line.
		 * @spec openspec/changes/anonymisation-fails-closed-without-a-detector/tasks.md#task-4
		 */
		refusalLine() {
			if (this.warning === 'disabled') {
				return t('filinq', 'Entity detection is switched off in OpenRegister. Filinq refuses to anonymise until you switch it on.')
			}
			if (this.warning === 'unavailable') {
				return t('filinq', 'The entity detector {method} is unavailable. Filinq refuses to anonymise until it is back.', { method: this.effectiveMethod })
			}
			return t('filinq', 'Filinq cannot read which entity detector OpenRegister uses. Filinq refuses to anonymise until it can.')
		},

		/**
		 * The detector in use, named, for the settings page.
		 *
		 * @return {string} The line, or '' when not asked for or not known.
		 * @spec openspec/changes/anonymisation-fails-closed-without-a-detector/tasks.md#task-4
		 */
		activeLine() {
			if (!this.showActiveBackend || this.effectiveMethod === '' || this.refusing) {
				return ''
			}
			return t('filinq', 'Entity detector in use: {method}', { method: this.effectiveMethod })
		},
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

.anonymiser-backend-warning__active {
	color: var(--color-text-maxcontrast);
}

.anonymiser-backend-warning__appapi-line {
	font-weight: bold;
}

.anonymiser-backend-warning__links {
	list-style: disc;
	margin-inline-start: 20px;
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
