<?php

/**
 * The verify/{...} address serves the signature page and the waarmerk page
 * without one shadowing the other.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\AppInfo
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\AppInfo;

use OCA\Filinq\Service\SignatureVerificationLinkService;
use OCA\Filinq\Service\WaarmerkService;
use PHPUnit\Framework\TestCase;

/**
 * Resolves `verify/<id>` against routes.php the way the router does: the
 * first route whose path and requirements match wins.
 *
 * @spec openspec/changes/signature-verification-portal/tasks.md#task-2.3
 */
class VerifyPageRoutingTest extends TestCase {

	/**
	 * The name of the first GET route that matches `verify/<id>`.
	 *
	 * @param string $id The last path segment.
	 *
	 * @return string The route name, or '' when none matches.
	 */
	private static function routeFor(string $id): string {
		$routes = require __DIR__ . '/../../../appinfo/routes.php';
		foreach (($routes['routes'] ?? []) as $route) {
			$url = ltrim((string) $route['url'], '/');
			if (($route['verb'] ?? 'GET') !== 'GET' || preg_match('#^verify/\{(\w+)\}$#', $url, $match) !== 1) {
				continue;
			}

			$pattern = ($route['requirements'][$match[1]] ?? '[^/]+');
			if (preg_match('#^(?:' . $pattern . ')$#', $id) === 1) {
				return (string) $route['name'];
			}
		}

		return '';

	}//end routeFor()

	/**
	 * A signature token opens the signature page its QR stamp names.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/signature-verification-portal/tasks.md#task-2.3
	 */
	public function testASignatureTokenOpensTheSignaturePage(): void {
		$token = bin2hex(random_bytes(16));

		$this->assertTrue(condition: SignatureVerificationLinkService::isWellFormed(token: $token));
		$this->assertSame(expected: 'publicVerification#page', actual: self::routeFor(id: $token));

	}//end testASignatureTokenOpensTheSignaturePage()

	/**
	 * A waarmerk code, and anything that is no token, still opens the waarmerk page.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/signature-verification-portal/tasks.md#task-2.3
	 */
	public function testAWaarmerkCodeStillOpensTheWaarmerkPage(): void {
		$uuid = '3b4c187c-d7b8-4b23-a0db-abaa2382215e';
		$code = $uuid . '-' . substr(hash('sha256', $uuid), 0, 4);

		$this->assertTrue(condition: WaarmerkService::isWellFormedCode(code: $code));
		$this->assertSame(expected: 'waarmerkVerify#verifyPage', actual: self::routeFor(id: $code));
		$this->assertSame(expected: 'waarmerkVerify#verifyPage', actual: self::routeFor(id: 'not-a-thing'));

	}//end testAWaarmerkCodeStillOpensTheWaarmerkPage()
}//end class
