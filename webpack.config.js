const path = require('path')
const webpackConfig = require('@nextcloud/webpack-vue-config')

const buildMode = process.env.NODE_ENV
const isDev = buildMode === 'development'
webpackConfig.devtool = isDev ? 'cheap-source-map' : 'source-map'

webpackConfig.stats = {
	colors: true,
	modules: false,
}

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

webpackConfig.resolve.alias = {
	...webpackConfig.resolve.alias,
	'@conduction/nextcloud-vue': path.resolve(__dirname, '../nextcloud-vue/src'),
}

module.exports = webpackConfig
