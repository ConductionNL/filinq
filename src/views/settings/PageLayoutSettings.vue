<template>
	<NcSettingsSection
		:name="t('filinq', 'Page layouts')"
		:description="
			t(
				'filinq',
				'The paper a generated document is printed on: size, margins, header, footer and a different first page. Saving writes a new version; documents already made keep the version they used.',
			)
		">
		<NcLoadingIcon v-if="loading" :size="32" />
		<template v-else>
			<div class="page-layouts__field">
				<NcSelect
					v-model="selected"
					:inputLabel="t('filinq', 'Layout')"
					:options="options"
					:clearable="false"
					label="label"
					:reduce="(option) => option.id"
					@update:modelValue="choose" />
			</div>

			<NcTextField
				v-if="selected === NEW"
				v-model="form.name"
				:label="t('filinq', 'Name of the new layout')" />
			<p v-else class="page-layouts__hint">
				{{
					t('filinq', 'Version {version}. Saving makes version {next}.', {
						version: current.layoutVersion,
						next: current.layoutVersion + 1,
					})
				}}
			</p>

			<div class="page-layouts__row">
				<NcSelect
					v-model="form.paperSize"
					:inputLabel="t('filinq', 'Paper size')"
					:options="PAPER_SIZES"
					:clearable="false" />
				<NcSelect
					v-model="form.orientation"
					:inputLabel="t('filinq', 'Orientation')"
					:options="orientationOptions"
					:clearable="false"
					label="label"
					:reduce="(option) => option.id" />
			</div>

			<fieldset class="page-layouts__row">
				<legend>{{ t('filinq', 'Margins in millimetres') }}</legend>
				<NcTextField
					v-model="form.margins.top"
					type="number"
					:label="t('filinq', 'Top')" />
				<NcTextField
					v-model="form.margins.right"
					type="number"
					:label="t('filinq', 'Right')" />
				<NcTextField
					v-model="form.margins.bottom"
					type="number"
					:label="t('filinq', 'Bottom')" />
				<NcTextField
					v-model="form.margins.left"
					type="number"
					:label="t('filinq', 'Left')" />
			</fieldset>

			<NcTextField v-model="form.header" :label="t('filinq', 'Header')" />
			<NcTextField v-model="form.footer" :label="t('filinq', 'Footer')" />

			<NcCheckboxRadioSwitch v-model="form.firstPageDiffers">
				{{ t('filinq', 'The first page has its own header and footer') }}
			</NcCheckboxRadioSwitch>
			<template v-if="form.firstPageDiffers">
				<NcTextField
					v-model="form.firstPageHeader"
					:label="t('filinq', 'Header on the first page')" />
				<NcTextField
					v-model="form.firstPageFooter"
					:label="t('filinq', 'Footer on the first page')" />
			</template>

			<p :id="previewLabelId" class="page-layouts__heading">
				{{ t('filinq', 'Preview of the first and following pages') }}
			</p>
			<div
				class="page-layouts__preview"
				role="group"
				:aria-labelledby="previewLabelId">
				<div
					v-for="page in pages"
					:key="page.kind"
					class="page-layouts__page"
					:class="{
						'page-layouts__page--landscape':
							form.orientation === 'landscape',
					}"
					:style="pagePadding">
					<span class="page-layouts__page-kind">{{
						page.kind === 'first'
							? t('filinq', 'First page')
							: t('filinq', 'Following pages')
					}}</span>
					<span class="page-layouts__page-header">{{ page.header }}</span>
					<span class="page-layouts__page-body" aria-hidden="true" />
					<span class="page-layouts__page-footer">{{ page.footer }}</span>
				</div>
			</div>

			<NcButton variant="primary" :disabled="saving || !canSave" @click="save">
				{{
					selected === NEW
						? t('filinq', 'Create layout')
						: t('filinq', 'Save as a new version')
				}}
			</NcButton>

			<template v-if="versions.length > 0">
				<p class="page-layouts__heading">
					{{ t('filinq', 'Versions') }}
				</p>
				<ul class="page-layouts__versions">
					<li v-for="version in versions" :key="version.uuid">
						{{
							t('filinq', 'Version {version}', {
								version: version.layoutVersion,
							})
						}}
						<span v-if="version.active">{{
							t('filinq', '(in use)')
						}}</span>
					</li>
				</ul>
			</template>
		</template>
	</NcSettingsSection>
</template>

<script>
import axios from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import {
	NcButton,
	NcCheckboxRadioSwitch,
	NcLoadingIcon,
	NcSelect,
	NcSettingsSection,
	NcTextField,
} from '@nextcloud/vue'
import {
	formFromLayout,
	layoutPayload,
	previewPages,
} from '../../services/pageLayoutForm.js'

// The page layout admin surface (documents-from-a-template REQ-DFT-01, task 2.2).
// Writing is admin-only through the pageLayout authorization cascade; this
// section is mounted for admins only.
const NEW = '__new__'
const PAPER_SIZES = ['A4', 'A5', 'Letter', 'Legal']

export default {
	name: 'PageLayoutSettings',
	components: {
		NcButton,
		NcCheckboxRadioSwitch,
		NcLoadingIcon,
		NcSelect,
		NcSettingsSection,
		NcTextField,
	},

	data() {
		return {
			NEW,
			PAPER_SIZES,
			layouts: [],
			versions: [],
			selected: NEW,
			form: formFromLayout({}),
			loading: true,
			saving: false,
			previewLabelId: 'filinq-page-layout-preview',
		}
	},

	computed: {
		/**
		 * The layouts to choose from, and a new one.
		 *
		 * @return {Array<{id: string, label: string}>} The options.
		 *
		 * @spec openspec/specs/document-creatie-sjablonen/spec.md
		 */
		options() {
			return [
				{ id: NEW, label: t('filinq', 'New layout') },
				...this.layouts.map((layout) => ({
					id: layout.name,
					label: layout.name,
				})),
			]
		},

		/**
		 * The orientation options.
		 *
		 * @return {Array<{id: string, label: string}>} Portrait and landscape.
		 *
		 * @spec openspec/specs/document-creatie-sjablonen/spec.md
		 */
		orientationOptions() {
			return [
				{ id: 'portrait', label: t('filinq', 'Portrait') },
				{ id: 'landscape', label: t('filinq', 'Landscape') },
			]
		},

		/**
		 * The active version of the chosen layout.
		 *
		 * @return {object} The layout, or `{layoutVersion: 0}`.
		 *
		 * @spec openspec/specs/document-creatie-sjablonen/spec.md
		 */
		current() {
			return (
				this.layouts.find((layout) => layout.name === this.selected) || {
					layoutVersion: 0,
				}
			)
		},

		/**
		 * The two pages the preview draws.
		 *
		 * @return {Array<object>} The first and a following page.
		 *
		 * @spec openspec/specs/document-creatie-sjablonen/spec.md
		 */
		pages() {
			return previewPages(this.form)
		},

		/**
		 * The margins as padding, scaled from millimetres on a 210 mm wide page.
		 *
		 * @return {object} A style object.
		 *
		 * @spec openspec/specs/document-creatie-sjablonen/spec.md
		 */
		pagePadding() {
			const scale = (mm) => (Number(mm || 0) / 210) * 100 + '%'
			const margins = this.form.margins
			return {
				padding: [margins.top, margins.right, margins.bottom, margins.left]
					.map(scale)
					.join(' '),
			}
		},

		/**
		 * Whether saving makes sense: a new layout needs a name.
		 *
		 * @return {boolean} True when the form can be saved.
		 *
		 * @spec openspec/specs/document-creatie-sjablonen/spec.md
		 */
		canSave() {
			return this.selected !== NEW || this.form.name.trim() !== ''
		},
	},

	/**
	 * Load the layouts when the section mounts.
	 *
	 * @return {void}
	 *
	 * @spec openspec/specs/document-creatie-sjablonen/spec.md
	 */
	mounted() {
		this.load()
	},

	methods: {
		/**
		 * The layouts endpoint.
		 *
		 * @param {string} [suffix] A path after it.
		 * @return {string} The URL.
		 *
		 * @spec openspec/specs/document-creatie-sjablonen/spec.md
		 */
		url(suffix = '') {
			return generateUrl('/apps/filinq/api/page-layouts' + suffix)
		},

		/**
		 * Read every active layout.
		 *
		 * @return {Promise<void>} Resolves once `layouts` is set.
		 *
		 * @spec openspec/specs/document-creatie-sjablonen/spec.md
		 */
		async load() {
			try {
				const { data } = await axios.get(this.url())
				this.layouts = Array.isArray(data.results) ? data.results : []
			} catch {
				showError(t('filinq', 'The page layouts could not be loaded'))
			} finally {
				this.loading = false
			}
		},

		/**
		 * Switch to a layout, or to a new one.
		 *
		 * @param {string} id The layout name, or the new-layout marker.
		 * @return {Promise<void>} Resolves once the form and versions are set.
		 *
		 * @spec openspec/specs/document-creatie-sjablonen/spec.md
		 */
		async choose(id) {
			this.versions = []
			if (id === NEW) {
				this.form = formFromLayout({})
				return
			}
			this.form = formFromLayout(this.current)
			try {
				const { data } = await axios.get(this.url(), {
					params: { name: id },
				})
				this.versions = Array.isArray(data.results) ? data.results : []
			} catch {
				this.versions = []
			}
		},

		/**
		 * Create the layout, or write the next version of it.
		 *
		 * @return {Promise<void>} Resolves once saved and reloaded.
		 *
		 * @spec openspec/specs/document-creatie-sjablonen/spec.md
		 */
		async save() {
			this.saving = true
			const fields = layoutPayload(this.form)
			try {
				if (this.selected === NEW) {
					const name = this.form.name.trim()
					await axios.post(this.url('/new'), { name, fields })
					await this.load()
					this.selected = name
				} else {
					await axios.post(this.url(), {
						name: this.selected,
						changes: fields,
					})
					await this.load()
				}
				await this.choose(this.selected)
				showSuccess(t('filinq', 'The page layout is saved'))
			} catch (error) {
				const reason =
					error?.response?.data?.error
					|| t('filinq', 'no reason was given')
				showError(
					t('filinq', 'The page layout could not be saved: {reason}', {
						reason,
					}),
				)
			} finally {
				this.saving = false
			}
		},
	},
}
</script>

<style scoped>
.page-layouts__row {
	display: flex;
	flex-wrap: wrap;
	gap: 8px;
	border: none;
	padding: 0;
}

.page-layouts__hint {
	color: var(--color-text-maxcontrast);
}

.page-layouts__heading {
	font-weight: bold;
	margin: 12px 0 4px;
}

.page-layouts__preview {
	display: flex;
	flex-wrap: wrap;
	gap: 16px;
	margin-bottom: 12px;
}

.page-layouts__page {
	display: flex;
	flex-direction: column;
	gap: 4px;
	width: 180px;
	aspect-ratio: 210 / 297;
	box-sizing: border-box;
	border: 1px solid var(--color-border-dark);
	background: var(--color-main-background);
	font-size: 0.75em;
}

.page-layouts__page--landscape {
	width: 254px;
	aspect-ratio: 297 / 210;
}

.page-layouts__page-kind {
	color: var(--color-text-maxcontrast);
}

.page-layouts__page-body {
	flex: 1;
	border: 1px dashed var(--color-border);
}

.page-layouts__versions {
	margin: 0;
	padding-inline-start: 20px;
}
</style>
