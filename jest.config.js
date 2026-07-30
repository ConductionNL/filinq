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
	// tests/vitest/** belongs to the vitest runner (see vitest.config.js
	// `include`). Jest was picking those specs up too and failing on them —
	// they import from 'vitest', which cannot be required from a CommonJS
	// module. Ignore the whole directory rather than listing files one by one.
	testPathIgnorePatterns: [
		'/node_modules/',
		'<rootDir>/tests/e2e/',
		'<rootDir>/tests/vitest/',
		'<rootDir>/tests/unit/reachability.spec.js',
	],
	moduleNameMapper: {
		'^@/(.*)$': '<rootDir>/src/$1',
		// `@nextcloud/axios` is type:module and declares only an `import`
		// export condition, so Jest's CJS resolver cannot load it. Specs mock
		// it, but jest.mock still needs the path to resolve — redirect it to a
		// local stub so resolution succeeds and the spec's factory takes over.
		'^@nextcloud/axios$': '<rootDir>/tests/__mocks__/nextcloud-axios.js',
	},
	coveragePathIgnorePatterns: [
		'index.js',
		'index.ts',
	],
	coverageDirectory: '<rootDir>/coverage-frontend/',
}
