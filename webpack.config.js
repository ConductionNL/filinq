const path = require('path')
const fs = require('fs')
const webpackConfig = require('@nextcloud/webpack-vue-config')

const buildMode = process.env.NODE_ENV
const isDev = buildMode === 'development'
webpackConfig.devtool = isDev ? 'cheap-source-map' : 'source-map'

webpackConfig.stats = {
	colors: true,
	modules: false,
}

// Inline-SVG handling for our custom icon set:
// SVGs in src/assets/icons/ are loaded as raw source strings so DdIcon can
// inject them with v-html and let CSS `currentColor` flow through.
// All other assets (PNGs, fonts, SVGs elsewhere) keep the default
// asset/inline (data URI) behavior from @nextcloud/webpack-vue-config.
const iconsDir = path.resolve(__dirname, 'src/assets/icons')
webpackConfig.module.rules = webpackConfig.module.rules.map((rule) => {
	if (rule && rule.type === 'asset/inline') {
		return { ...rule, exclude: [iconsDir] }
	}
	return rule
})
webpackConfig.module.rules.push({
	test: /\.svg$/,
	include: [iconsDir],
	type: 'asset/source',
})

const appId = 'docudesk'
webpackConfig.entry = {
	main: {
		import: path.join(__dirname, 'src', 'main.js'),
		filename: appId + '-main.js',
	},
	adminSettings: {
		import: path.join(__dirname, 'src', 'settings.js'),
		filename: appId + '-settings.js',
	},
	dashboard: {
		import: path.join(__dirname, 'src', 'dashboard.js'),
		filename: appId + '-dashboard.js',
	},
}

// Use local source when available (monorepo dev), otherwise fall back to npm package
const localLib = path.resolve(__dirname, '../nextcloud-vue/src')
const useLocalLib = fs.existsSync(localLib)

webpackConfig.resolve.alias = {
	...webpackConfig.resolve.alias,
	...(useLocalLib ? { '@conduction/nextcloud-vue': localLib } : {}),
	'vue$': path.resolve(__dirname, 'node_modules/vue'),
	'pinia$': path.resolve(__dirname, 'node_modules/pinia'),
	'@nextcloud/vue$': path.resolve(__dirname, 'node_modules/@nextcloud/vue'),
	'@nextcloud/dialogs': path.resolve(__dirname, 'node_modules/@nextcloud/dialogs'),
}

module.exports = webpackConfig
