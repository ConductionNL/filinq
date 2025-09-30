<script setup>
import { navigationStore } from '../store/store.js'
</script>

<template>
	<!-- Modal Container -->
	<div>
		<ScanFile v-if="navigationStore.modal === 'scanFile'" />
	</div>
</template>

<script>
import ScanFile from './reports/ScanFile.vue'

export default {
	name: 'Modals',
	components: {
		ScanFile,
	},
}
</script>
