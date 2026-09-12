<template>
	<div class="dossier-detail">
		<NcEmptyContent
			v-if="!dossierStore.dossier && !dossierStore.loading"
			:name="t('filinq', 'Dossier not found')"
			:description="
				dossierStore.error
				|| t('filinq', 'This dossier does not exist, or you cannot open it.')
			">
			<template #icon>
				<FolderAccount :size="64" />
			</template>
		</NcEmptyContent>

		<template v-else-if="dossierStore.dossier">
			<header class="dossier-header">
				<!--
					Inline rename. The field is the title: clicking it edits in
					place, blur saves. The save is a FULL-payload update
					server-side, so renaming cannot null the bases or the
					review date the way a name-only PUT would.
				-->
				<div class="dossier-title">
					<input
						v-if="renaming"
						ref="titleInput"
						v-model="draftName"
						class="dossier-title-input"
						:aria-label="t('filinq', 'Dossier name')"
						@blur="commitRename"
						@keyup.enter="commitRename"
						@keyup.esc="cancelRename" />
					<h2
						v-else
						tabindex="0"
						@click="startRename"
						@keyup.enter="startRename">
						{{ dossierStore.dossier.name }}
					</h2>

					<CnStatusBadge
						:label="statusLabel(dossierStore.dossier.status)"
						:colorMap="statusColorMap" />
				</div>

				<p
					v-if="dossierStore.dossier.description"
					class="dossier-description">
					{{ dossierStore.dossier.description }}
				</p>

				<NcNoteCard v-if="folderWarning" type="warning">
					{{ folderWarning }}
				</NcNoteCard>
				<NcNoteCard v-if="dossierStore.error" type="error">
					{{ dossierStore.error }}
				</NcNoteCard>

				<div class="dossier-actions">
					<!--
						Only the transitions the declared lifecycle allows from
						the current status. Offering all five and letting the
						operator discover the rule by being refused is the
						alternative this avoids; OpenRegister still guards the
						write regardless of what is offered here.
					-->
					<NcButton
						v-for="target in offeredTransitions"
						:key="target"
						variant="secondary"
						:disabled="dossierStore.saving"
						@click="transition(target)">
						{{ transitionLabel(target) }}
					</NcButton>

					<NcButton
						variant="secondary"
						:disabled="generatingPdf"
						@click="generateGrondslagenPdf">
						<template #icon>
							<FilePdfBox :size="20" />
						</template>
						{{ t('filinq', 'Generate legal-bases PDF') }}
					</NcButton>

					<NcButton variant="secondary" @click="startBatch">
						<template #icon>
							<EyeOffOutline :size="20" />
						</template>
						{{ t('filinq', 'Anonymise this dossier') }}
					</NcButton>
				</div>
			</header>

			<section class="dossier-section">
				<h3>{{ t('filinq', 'Legal bases') }}</h3>
				<p v-if="!dossierStore.dossier.bases.length" class="dossier-muted">
					{{ t('filinq', 'No legal bases set for this dossier yet.') }}
				</p>
				<CnStatusBadge
					v-for="base in dossierStore.dossier.bases"
					:key="base.slug"
					:label="base.label"
					:colorMap="{
						[base.label]: base.known ? 'primary' : 'warning',
					}" />
				<p v-if="hasUnknownBase" class="dossier-muted">
					{{
						t(
							'filinq',
							'A legal basis shown in orange is not in the vocabulary. It is kept and shown rather than dropped.',
						)
					}}
				</p>
			</section>

			<section class="dossier-section dossier-documents">
				<div class="dossier-section-head">
					<h3>{{ t('filinq', 'Documents') }}</h3>
					<NcButton variant="secondary" @click="addDocument">
						<template #icon>
							<Plus :size="20" />
						</template>
						{{ t('filinq', 'Add document') }}
					</NcButton>
				</div>

				<p
					v-if="!dossierStore.dossier.documents.length"
					class="dossier-muted">
					{{ t('filinq', 'This dossier holds no documents yet.') }}
				</p>

				<div v-else class="dossier-documents-split">
					<ul class="dossier-document-list">
						<li
							v-for="doc in dossierStore.dossier.documents"
							:key="doc.id"
							:class="{
								'is-selected':
									doc.id === dossierStore.selectedDocumentId,
								'is-missing': doc.missing,
							}">
							<!--
								Selecting a document swaps the viewer via store
								state, NOT a route push — the requirement is
								explicitly that switching causes no reload.
							-->
							<button
								class="dossier-document-button"
								:disabled="doc.missing"
								@click="dossierStore.selectDocument(doc.id)">
								<span class="dossier-document-name">
									{{
										doc.missing
											? t('filinq', 'Missing document')
											: doc.name
									}}
								</span>
								<CnStatusBadge
									v-if="doc.missing"
									:label="t('filinq', 'Unavailable')"
									:colorMap="{
										[t('filinq', 'Unavailable')]: 'error',
									}" />
								<CnStatusBadge
									v-else-if="doc.referenced"
									:label="t('filinq', 'Linked')"
									:colorMap="{
										[t('filinq', 'Linked')]: 'default',
									}" />
							</button>

							<NcActions>
								<template #icon>
									<DotsHorizontal :size="20" />
								</template>
								<NcActionButton
									closeAfterClick
									@click="confirmRemove(doc)">
									<template #icon>
										<Delete :size="20" />
									</template>
									{{ t('filinq', 'Remove from dossier') }}
								</NcActionButton>
							</NcActions>
						</li>
					</ul>

					<div class="dossier-viewer">
						<iframe
							v-if="viewerSrc"
							:src="viewerSrc"
							:title="t('filinq', 'Document preview')"
							class="dossier-viewer-frame" />
						<p v-else class="dossier-muted">
							{{ t('filinq', 'Select a document to preview it.') }}
						</p>
					</div>
				</div>
			</section>

			<section class="dossier-section">
				<h3>{{ t('filinq', 'Anonymisation runs') }}</h3>
				<p
					v-if="!dossierStore.dossier.batchRuns.length"
					class="dossier-muted">
					{{
						t('filinq', 'No anonymisation has run on this dossier yet.')
					}}
				</p>
				<ul v-else class="dossier-run-list">
					<li v-for="run in dossierStore.dossier.batchRuns" :key="run.id">
						<CnStatusBadge
							:label="run.status"
							:colorMap="{ [run.status]: 'primary' }" />
						{{ n('filinq', '%n file', '%n files', run.fileCount) }}
					</li>
				</ul>
			</section>

			<section class="dossier-section">
				<h3>{{ t('filinq', 'Publication') }}</h3>
				<!--
					Presence-gated: hidden, not broken. Without the publication
					pipeline the section says the capability is absent rather
					than offering a publish action that would fail.
				-->
				<p class="dossier-muted">
					{{
						dossierStore.dossier.capabilities.publicationPipeline
							? t('filinq', 'This dossier has not been published yet.')
							: t(
									'filinq',
									'Publishing is not available on this instance. The Woo publication pipeline is not installed.',
								)
					}}
				</p>
			</section>
		</template>

		<ConfirmActionDialog
			v-if="removeTarget"
			:name="t('filinq', 'Remove document')"
			:message="removeMessage"
			:busy="dossierStore.saving"
			@confirm="executeRemove"
			@cancel="removeTarget = null" />
	</div>
</template>

<script>
import { CnStatusBadge } from '@conduction/nextcloud-vue'
import axios from '@nextcloud/axios'
import { translatePlural as n, translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import {
	NcActionButton,
	NcActions,
	NcButton,
	NcEmptyContent,
	NcNoteCard,
} from '@nextcloud/vue'
import Delete from 'vue-material-design-icons/Delete.vue'
import DotsHorizontal from 'vue-material-design-icons/DotsHorizontal.vue'
import EyeOffOutline from 'vue-material-design-icons/EyeOffOutline.vue'
import FilePdfBox from 'vue-material-design-icons/FilePdfBox.vue'
import FolderAccount from 'vue-material-design-icons/FolderAccount.vue'
import Plus from 'vue-material-design-icons/Plus.vue'
import ConfirmActionDialog from '../../dialogs/ConfirmActionDialog.vue'
import { dossierStore } from '../../store/store.js'

/**
 * Dossier detail.
 *
 * Aggregates what the server already knows about one dossier — its documents
 * (home folder ∪ explicit references), legal bases, anonymisation runs and
 * publication state — and wires the dossier-level actions to the capabilities
 * that already exist. Nothing here reimplements batch, report or publication
 * logic; each action calls the endpoint that owns it.
 */
export default {
	name: 'DossierDetail',
	components: {
		CnStatusBadge,
		ConfirmActionDialog,
		Delete,
		DotsHorizontal,
		EyeOffOutline,
		FilePdfBox,
		FolderAccount,
		NcActionButton,
		NcActions,
		NcButton,
		NcEmptyContent,
		NcNoteCard,
		Plus,
	},

	data() {
		return {
			dossierStore,
			renaming: false,
			draftName: '',
			folderWarning: '',
			generatingPdf: false,
			removeTarget: null,
			removeMode: 'unlink',
			statusColorMap: {
				[t('filinq', 'Open')]: 'default',
				[t('filinq', 'In review')]: 'warning',
				[t('filinq', 'Processed')]: 'primary',
				[t('filinq', 'Published')]: 'success',
				[t('filinq', 'Closed')]: 'default',
			},
		}
	},

	computed: {
		dossierId() {
			return this.$route.params.id
		},

		hasUnknownBase() {
			return (this.dossierStore.dossier?.bases || []).some((b) => !b.known)
		},

		/**
		 * The lifecycle transitions this instance can actually carry out.
		 *
		 * `published` is dropped when the Woo publication pipeline is absent.
		 * The section below already says publishing is unavailable here, and
		 * the button offered it anyway: pressing it moved the dossier to
		 * `published` without publishing anything, leaving a record that says
		 * a Woo request was answered when nothing left the instance. That is
		 * worse than a missing button, because the next reader has no way to
		 * tell the two apart.
		 *
		 * The server still guards every write. This only stops the UI
		 * offering one it knows means nothing here.
		 *
		 * @return {Array<string>} The targets to render a button for.
		 *
		 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
		 */
		offeredTransitions() {
			const targets = this.dossierStore.dossier?.availableTransitions || []
			if (this.dossierStore.dossier?.capabilities?.publicationPipeline) {
				return targets
			}
			return targets.filter((target) => target !== 'published')
		},

		/**
		 * The preview URL for the open document.
		 *
		 * Nextcloud's canonical `/f/{fileId}` route, not a Filinq endpoint:
		 * this app ships no per-file preview route, and inventing a URL for one
		 * would give a viewer that 404s on every document while looking
		 * perfectly wired. `/f/` resolves under the caller's own ACLs, so a
		 * file the operator cannot read does not render here either.
		 *
		 * @return {string} The URL, or '' when nothing is open.
		 */
		viewerSrc() {
			const doc = this.dossierStore.selectedDocument
			if (!doc || doc.missing) {
				return ''
			}
			return generateUrl(`/f/${doc.id}`)
		},

		/**
		 * The removal confirmation, naming the actual consequence.
		 *
		 * Trashing a file and dropping a link are different operations behind
		 * one verb. A confirmation that reads the same for both is how an
		 * operator deletes a file they meant to unlink.
		 *
		 * @return {string} The message.
		 */
		removeMessage() {
			if (!this.removeTarget) {
				return ''
			}

			return this.removeMode === 'trash'
				? t(
						'filinq',
						'"{name}" lives in this dossier and no other dossier uses it. It will be moved to the trash, where you can still restore it.',
						{ name: this.removeTarget.name },
					)
				: t(
						'filinq',
						'"{name}" will be removed from this dossier only. The file itself is left untouched.',
						{ name: this.removeTarget.name },
					)
		},
	},

	mounted() {
		this.dossierStore.fetchDossier(this.dossierId)
	},

	methods: {
		t,
		n,

		statusLabel(status) {
			const labels = {
				open: t('filinq', 'Open'),
				'in-review': t('filinq', 'In review'),
				processed: t('filinq', 'Processed'),
				published: t('filinq', 'Published'),
				closed: t('filinq', 'Closed'),
			}
			return labels[status] || t('filinq', 'Open')
		},

		transitionLabel(target) {
			const labels = {
				open: t('filinq', 'Reopen'),
				'in-review': t('filinq', 'Start review'),
				processed: t('filinq', 'Complete review'),
				published: t('filinq', 'Publish'),
				closed: t('filinq', 'Close'),
			}
			return labels[target] || target
		},

		startRename() {
			this.draftName = this.dossierStore.dossier.name
			this.renaming = true
			this.$nextTick(() => this.$refs.titleInput?.focus())
		},

		cancelRename() {
			this.renaming = false
		},

		async commitRename() {
			if (!this.renaming) {
				return
			}
			this.renaming = false

			const name = this.draftName.trim()
			if (name === '' || name === this.dossierStore.dossier.name) {
				return
			}

			const updated = await this.dossierStore.renameDossier(
				this.dossierId,
				name,
			)
			// The object rename always stands; the bound folder is best-effort
			// and reports rather than blocks, so the warning is surfaced here.
			this.folderWarning = updated?.folderWarning || ''
		},

		async transition(target) {
			await this.dossierStore.transitionDossier(this.dossierId, target)
		},

		async generateGrondslagenPdf() {
			this.generatingPdf = true
			try {
				await axios.post(
					generateUrl(
						`/apps/filinq/api/anonymization/dossier/${this.dossierId}/grondslagen-pdf`,
					),
				)
				await this.dossierStore.fetchDossier(this.dossierId)
			} finally {
				this.generatingPdf = false
			}
		},

		startBatch() {
			this.$router.push({ name: 'FolderAnonymization' })
		},

		addDocument() {
			// The picker lives on the folder-analysis surface, which already
			// owns upload and folder selection. Sending the operator there
			// rather than growing a second uploader here.
			this.$router.push({ name: 'FolderAnonymization' })
		},

		async confirmRemove(doc) {
			this.removeMode = await this.dossierStore.removalMode(
				this.dossierId,
				doc.id,
			)
			this.removeTarget = doc
		},

		async executeRemove() {
			const doc = this.removeTarget
			this.removeTarget = null
			await this.dossierStore.removeDocument(this.dossierId, doc.id)
		},
	},
}
</script>

<style scoped>
.dossier-detail {
	padding: 1rem;
}

.dossier-header {
	display: flex;
	flex-direction: column;
	gap: 0.5rem;
	margin-block-end: 1.5rem;
}

.dossier-title {
	display: flex;
	align-items: center;
	gap: 0.75rem;
}

.dossier-title h2 {
	margin: 0;
	cursor: text;
}

.dossier-title-input {
	font-size: 1.5rem;
	font-weight: bold;
	width: 100%;
	max-width: 32rem;
}

.dossier-description {
	color: var(--color-text-maxcontrast);
	margin: 0;
}

.dossier-actions {
	display: flex;
	flex-wrap: wrap;
	gap: 0.5rem;
	margin-block-start: 0.5rem;
}

.dossier-section {
	margin-block-end: 2rem;
}

.dossier-section-head {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 1rem;
}

.dossier-section h3 {
	margin-block: 0 0.5rem;
}

.dossier-documents-split {
	display: grid;
	grid-template-columns: minmax(14rem, 20rem) 1fr;
	gap: 1rem;
	align-items: start;
}

@media (max-width: 720px) {
	.dossier-documents-split {
		grid-template-columns: 1fr;
	}
}

.dossier-document-list,
.dossier-run-list {
	list-style: none;
	margin: 0;
	padding: 0;
	display: flex;
	flex-direction: column;
	gap: 0.25rem;
}

.dossier-document-list li {
	display: flex;
	align-items: center;
	gap: 0.25rem;
	border-radius: var(--border-radius);
}

.dossier-document-list li.is-selected {
	background-color: var(--color-primary-element-light);
}

.dossier-document-list li.is-missing {
	opacity: 0.7;
}

.dossier-document-button {
	flex: 1;
	display: flex;
	align-items: center;
	gap: 0.5rem;
	background: none;
	border: none;
	text-align: start;
	padding: 0.5rem;
	cursor: pointer;
	min-width: 0;
}

.dossier-document-button:disabled {
	cursor: not-allowed;
}

.dossier-document-name {
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}

.dossier-viewer {
	min-height: 24rem;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	display: flex;
	align-items: center;
	justify-content: center;
}

.dossier-viewer-frame {
	inline-size: 100%;
	block-size: 100%;
	min-height: 24rem;
	border: 0;
}

.dossier-muted {
	color: var(--color-text-maxcontrast);
}
</style>
