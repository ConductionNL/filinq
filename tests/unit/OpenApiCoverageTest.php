<?php

/**
 * Unit tests that the published OpenAPI document covers the routes and is the one the docs site shows.
 *
 * @category Test
 * @package  OCA\Filinq\Tests\Unit
 * @author   Conduction Development Team <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/connect-openapi-documentation/specs/openapi-documentation/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * The route-by-route match of openapi.json and appinfo/routes.php is pinned by
 * OpenApiDocumentTest. This test adds what that match cannot see on its own:
 * that the routes were read at all, that no path of another app is published,
 * and that the docs site renders the same document the app serves.
 *
 * @coversNothing
 */
class OpenApiCoverageTest extends TestCase {

	/**
	 * Fewer routes than this means routes.php was not read, not that the app shrank.
	 */
	private const MINIMUM_ROUTES = 150;

	/**
	 * Routes.php is read through its own fallback branch and yields the app's full route list.
	 *
	 * @return void
	 */
	public function testTheRoutesAreReadInFull(): void {
		$declared = require self::root().'/appinfo/routes.php';

		$this->assertIsArray(actual: $declared['routes'] ?? null);
		$this->assertGreaterThanOrEqual(
			expected: self::MINIMUM_ROUTES,
			actual: count($declared['routes']),
			message: 'appinfo/routes.php yielded too few routes; the coverage check would pass on an empty read'
		);
	}//end testTheRoutesAreReadInFull()

	/**
	 * The document publishes Filinq's own paths only, and the operations it documents are many.
	 *
	 * @return void
	 */
	public function testTheDocumentHoldsFilinqsPathsOnly(): void {
		$document   = self::decode(file: self::root().'/openapi.json');
		$operations = 0;
		foreach ($document['paths'] as $path => $item) {
			$this->assertStringNotContainsString(needle: 'dsonextcloud', haystack: (string) $path);
			$this->assertStringNotContainsString(needle: '/ocs/', haystack: (string) $path);
			$operations += count(array_intersect(array_keys($item), ['get', 'put', 'post', 'delete', 'patch']));
		}

		$this->assertGreaterThanOrEqual(expected: self::MINIMUM_ROUTES, actual: $operations);
		$this->assertArrayHasKey(key: '/documents/generate', array: $document['paths']);
		$this->assertArrayHasKey(key: '/extraction/financial', array: $document['paths']);
	}//end testTheDocumentHoldsFilinqsPathsOnly()

	/**
	 * The docs site renders the same document, and no hand-written one.
	 *
	 * @return void
	 */
	public function testTheDocsSiteRendersTheSameDocument(): void {
		$root = self::root();

		$this->assertSame(
			expected: self::decode(file: $root.'/openapi.json'),
			actual: self::decode(file: $root.'/docs/static/oas/filinq-api.json'),
			message: 'docs/static/oas/filinq-api.json differs from openapi.json: copy openapi.json over it'
		);
		$this->assertFileDoesNotExist(filename: $root.'/docs/static/oas/filinq-api.yaml');
		$this->assertStringContainsString(
			needle: "spec: 'static/oas/filinq-api.json'",
			haystack: (string) file_get_contents($root.'/docs/docusaurus.config.js')
		);
	}//end testTheDocsSiteRendersTheSameDocument()

	/**
	 * The repository root.
	 *
	 * @return string
	 */
	private static function root(): string {
		return dirname(__DIR__, 2);
	}//end root()

	/**
	 * A decoded JSON file.
	 *
	 * @param string $file The file.
	 *
	 * @return array<string,mixed>
	 */
	private static function decode(string $file): array {
		$decoded = json_decode((string) file_get_contents($file), true);
		self::assertIsArray(actual: $decoded, message: $file.' is not a JSON object');

		return $decoded;
	}//end decode()
}//end class
