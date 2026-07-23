module.exports = {
	transform: {
		'^.+\\.vue$': '@vue/vue2-jest',
		'^.+\\.js$': 'babel-jest',
		'^.+\\.ts$': 'ts-jest',
		'.+\\.(css|styl|less|sass|scss|png|jpg|ttf|woff|woff2)$': 'jest-transform-stub',
	},
	moduleFileExtensions: ['js', 'json', 'vue', 'ts'],
	testEnvironment: 'jest-environment-jsdom',
	// Playwright e2e specs have their own runner (npm run test:e2e).
	// tests/unit/reachability.spec.js has its own runner too (npm run
	// test:unit / vitest.config.js) — it's a pure Node fs/path static
	// analysis suite with no DOM/`.vue` dependency, and imports from
	// 'vitest' rather than using Jest's globals.
	testPathIgnorePatterns: ['/node_modules/', '<rootDir>/tests/e2e/', '<rootDir>/tests/unit/reachability.spec.js'],
	moduleNameMapper: {
		'^@/(.*)$': '<rootDir>/src/$1',
	},
	coveragePathIgnorePatterns: [
		'index.js',
		'index.ts',
	],
	coverageDirectory: '<rootDir>/coverage-frontend/',
}
