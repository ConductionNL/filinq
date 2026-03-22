<template>
	<div class="template-editor">
		<div v-if="!sourceMode" class="template-editor__toolbar">
			<NcButton type="tertiary"
				:class="{ active: isBold }"
				@click="toggleBold">
				<template #icon>
					<FormatBold :size="20" />
				</template>
			</NcButton>
			<NcButton type="tertiary"
				:class="{ active: isItalic }"
				@click="toggleItalic">
				<template #icon>
					<FormatItalic :size="20" />
				</template>
			</NcButton>
			<NcButton type="tertiary"
				:class="{ active: isUnderline }"
				@click="toggleUnderline">
				<template #icon>
					<FormatUnderline :size="20" />
				</template>
			</NcButton>
			<span class="template-editor__separator" />
			<NcSelect v-model="selectedHeading"
				:options="headingOptions"
				class="template-editor__heading-select"
				@input="setHeading" />
			<span class="template-editor__separator" />
			<NcButton type="tertiary" @click="toggleBulletList">
				<template #icon>
					<FormatListBulleted :size="20" />
				</template>
			</NcButton>
			<NcButton type="tertiary" @click="toggleOrderedList">
				<template #icon>
					<FormatListNumbered :size="20" />
				</template>
			</NcButton>
			<NcButton type="tertiary" @click="insertTable">
				<template #icon>
					<TableLarge :size="20" />
				</template>
			</NcButton>
			<span class="template-editor__separator" />
			<NcButton type="tertiary" @click="showMergeFieldMenu = !showMergeFieldMenu">
				<template #icon>
					<CodeBraces :size="20" />
				</template>
				{{ t('docudesk', 'Merge field') }}
			</NcButton>
			<NcButton type="tertiary" @click="showConditionalDialog = true">
				<template #icon>
					<CodeTags :size="20" />
				</template>
				{{ t('docudesk', 'Conditional') }}
			</NcButton>
			<span class="template-editor__separator" />
			<NcButton type="tertiary" @click="sourceMode = true">
				<template #icon>
					<Xml :size="20" />
				</template>
				{{ t('docudesk', 'Source') }}
			</NcButton>
		</div>

		<div v-if="!sourceMode" class="template-editor__content">
			<div ref="editorElement" class="template-editor__editable" />
		</div>

		<div v-else class="template-editor__source">
			<div class="template-editor__source-toolbar">
				<NcButton type="tertiary" @click="applySourceChanges">
					<template #icon>
						<Check :size="20" />
					</template>
					{{ t('docudesk', 'Apply & switch to visual') }}
				</NcButton>
				<NcButton type="tertiary" @click="cancelSourceChanges">
					{{ t('docudesk', 'Cancel') }}
				</NcButton>
			</div>
			<textarea v-model="sourceContent" class="template-editor__source-textarea" />
		</div>

		<MergeFieldMenu v-if="showMergeFieldMenu"
			@insert="insertMergeField"
			@close="showMergeFieldMenu = false" />

		<ConditionalSectionDialog v-if="showConditionalDialog"
			@apply="applyConditionalSection"
			@close="showConditionalDialog = false" />
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcSelect } from '@nextcloud/vue'
import FormatBold from 'vue-material-design-icons/FormatBold.vue'
import FormatItalic from 'vue-material-design-icons/FormatItalic.vue'
import FormatUnderline from 'vue-material-design-icons/FormatUnderline.vue'
import FormatListBulleted from 'vue-material-design-icons/FormatListBulleted.vue'
import FormatListNumbered from 'vue-material-design-icons/FormatListNumbered.vue'
import TableLarge from 'vue-material-design-icons/TableLarge.vue'
import CodeBraces from 'vue-material-design-icons/CodeBraces.vue'
import CodeTags from 'vue-material-design-icons/CodeTags.vue'
import Xml from 'vue-material-design-icons/Xml.vue'
import Check from 'vue-material-design-icons/Check.vue'
import MergeFieldMenu from './MergeFieldMenu.vue'
import ConditionalSectionDialog from './ConditionalSectionDialog.vue'

export default {
	name: 'TemplateEditor',
	components: {
		NcButton,
		NcSelect,
		FormatBold,
		FormatItalic,
		FormatUnderline,
		FormatListBulleted,
		FormatListNumbered,
		TableLarge,
		CodeBraces,
		CodeTags,
		Xml,
		Check,
		MergeFieldMenu,
		ConditionalSectionDialog,
	},
	props: {
		value: {
			type: String,
			default: '',
		},
	},
	data() {
		return {
			sourceMode: false,
			sourceContent: '',
			showMergeFieldMenu: false,
			showConditionalDialog: false,
			isBold: false,
			isItalic: false,
			isUnderline: false,
			selectedHeading: { id: 'paragraph', label: t('docudesk', 'Paragraph') },
			headingOptions: [
				{ id: 'paragraph', label: t('docudesk', 'Paragraph') },
				{ id: 'h1', label: t('docudesk', 'Heading 1') },
				{ id: 'h2', label: t('docudesk', 'Heading 2') },
				{ id: 'h3', label: t('docudesk', 'Heading 3') },
				{ id: 'h4', label: t('docudesk', 'Heading 4') },
			],
		}
	},
	watch: {
		value(newVal) {
			if (this.$refs.editorElement && this.$refs.editorElement.innerHTML !== newVal) {
				this.$refs.editorElement.innerHTML = newVal
			}
		},
	},
	mounted() {
		this.initEditor()
	},
	methods: {
		t,
		initEditor() {
			const el = this.$refs.editorElement
			if (!el) return
			el.contentEditable = 'true'
			el.innerHTML = this.value
			el.addEventListener('input', this.onEditorInput)
			el.addEventListener('keyup', this.updateToolbarState)
			el.addEventListener('mouseup', this.updateToolbarState)
		},
		onEditorInput() {
			const html = this.$refs.editorElement.innerHTML
			this.$emit('input', html)
		},
		updateToolbarState() {
			this.isBold = document.queryCommandState('bold')
			this.isItalic = document.queryCommandState('italic')
			this.isUnderline = document.queryCommandState('underline')
		},
		toggleBold() {
			document.execCommand('bold')
			this.updateToolbarState()
		},
		toggleItalic() {
			document.execCommand('italic')
			this.updateToolbarState()
		},
		toggleUnderline() {
			document.execCommand('underline')
			this.updateToolbarState()
		},
		setHeading(option) {
			if (!option) return
			if (option.id === 'paragraph') {
				document.execCommand('formatBlock', false, 'p')
			} else {
				document.execCommand('formatBlock', false, option.id)
			}
		},
		toggleBulletList() {
			document.execCommand('insertUnorderedList')
		},
		toggleOrderedList() {
			document.execCommand('insertOrderedList')
		},
		insertTable() {
			const table = '<table><tr><td>&nbsp;</td><td>&nbsp;</td></tr><tr><td>&nbsp;</td><td>&nbsp;</td></tr></table>'
			document.execCommand('insertHTML', false, table)
		},
		insertMergeField(fieldName) {
			const html = '<span class="merge-field" contenteditable="false">{{ ' + fieldName + ' }}</span>&nbsp;'
			document.execCommand('insertHTML', false, html)
			this.showMergeFieldMenu = false
			this.onEditorInput()
		},
		applyConditionalSection(condition) {
			const selection = window.getSelection()
			if (!selection.rangeCount) return

			const range = selection.getRangeAt(0)
			const selectedContent = range.extractContents()
			const wrapper = document.createElement('div')
			wrapper.setAttribute('data-condition-field', condition.field)
			wrapper.setAttribute('data-condition-op', condition.operator)
			if (condition.value) {
				wrapper.setAttribute('data-condition-value', condition.value)
			}
			wrapper.classList.add('conditional-section')
			wrapper.appendChild(selectedContent)
			range.insertNode(wrapper)

			this.showConditionalDialog = false
			this.onEditorInput()
		},
		applySourceChanges() {
			this.$refs.editorElement.innerHTML = this.sourceContent
			this.sourceMode = false
			this.onEditorInput()
		},
		cancelSourceChanges() {
			this.sourceMode = false
		},
	},
	beforeDestroy() {
		const el = this.$refs.editorElement
		if (el) {
			el.removeEventListener('input', this.onEditorInput)
			el.removeEventListener('keyup', this.updateToolbarState)
			el.removeEventListener('mouseup', this.updateToolbarState)
		}
	},
}
</script>

<style scoped lang="scss">
.template-editor {
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius-large);

	&__toolbar {
		display: flex;
		flex-wrap: wrap;
		gap: 2px;
		padding: 4px 8px;
		border-bottom: 1px solid var(--color-border);
		background: var(--color-background-dark);

		.active {
			background: var(--color-primary-element-light);
		}
	}

	&__separator {
		width: 1px;
		background: var(--color-border);
		margin: 4px 4px;
	}

	&__heading-select {
		width: 150px;
	}

	&__content {
		min-height: 400px;
	}

	&__editable {
		min-height: 400px;
		padding: 16px;
		outline: none;

		:deep(.merge-field) {
			display: inline-block;
			padding: 2px 8px;
			border-radius: 12px;
			background: var(--color-primary-element-light);
			color: var(--color-primary-element);
			font-family: monospace;
			font-size: 13px;
		}

		:deep(.conditional-section) {
			border-left: 3px solid var(--color-warning);
			padding-left: 12px;
			margin: 8px 0;
			position: relative;

			&::before {
				content: 'Conditional';
				position: absolute;
				top: -10px;
				left: 0;
				font-size: 10px;
				color: var(--color-warning);
				text-transform: uppercase;
			}
		}
	}

	&__source {
		display: flex;
		flex-direction: column;
	}

	&__source-toolbar {
		display: flex;
		gap: 8px;
		padding: 8px;
		border-bottom: 1px solid var(--color-border);
		background: var(--color-background-dark);
	}

	&__source-textarea {
		width: 100%;
		min-height: 400px;
		padding: 16px;
		font-family: monospace;
		font-size: 13px;
		border: none;
		outline: none;
		resize: vertical;
	}
}
</style>
