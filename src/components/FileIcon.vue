<template>
	<div class="file-icon">
		<FileOutline :size="size" />
	</div>
</template>

<script>
/**
 * Component for displaying file icons
 *
 * @package
 * @author Conduction B.V. <info@conduction.nl>
 * @copyright Copyright (c) 2024 Conduction B.V.
 * @license EUPL-1.2
 * @version 1.0.0
 */
import FileOutline from 'vue-material-design-icons/FileOutline.vue'

export default {
	name: 'FileIcon',
	components: {
		FileOutline,
	},
	props: {
		/**
		 * File extension to determine which icon to display (currently not used)
		 */
		fileExtension: {
			type: String,
			default: '',
		},
		/**
		 * Size of the icon
		 */
		size: {
			type: Number,
			default: 24,
		},
	},
}
</script>

<style scoped>
.file-icon {
  display: flex;
  align-items: center;
  justify-content: center;
}
</style>
