<?php

/**
 * Unit tests for the ReadsTypedRequestParameters controller trait.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\ReadsTypedRequestParameters;
use OCP\IRequest;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OCA\Filinq\Controller\ReadsTypedRequestParameters
 */
class ReadsTypedRequestParametersTest extends TestCase {

	/**
	 * A host over a request carrying the given parameters.
	 *
	 * Unsent parameters answer with the default the trait passes, the way
	 * IRequest::getParam() does.
	 *
	 * @param array<string, mixed> $params The sent parameters
	 *
	 * @return object
	 */
	private function host(array $params): object {
		$request = $this->createMock(IRequest::class);
		$request->method('getParam')->willReturnCallback(
			static fn (string $key, mixed $default = null): mixed => array_key_exists($key, $params) === true ? $params[$key] : $default
		);

		return new class($request) {
			use ReadsTypedRequestParameters {
				typedRequestParameters as public;
			}

			public function __construct(protected IRequest $request) {
			}
		};

	}//end host()

	public function testReadsStringsAndBooleansWithDefaults(): void {
		$host = $this->host(['path' => '/Inbox', 'recursive' => 'true']);

		$this->assertSame(
			['path' => '/Inbox', 'recursive' => true, 'profile' => 'default', 'dryRun' => false],
			$host->typedRequestParameters(
				[
					'path' => ['string', ''],
					'recursive' => ['bool', false],
					'profile' => ['string', 'default'],
					'dryRun' => ['bool', false],
				]
			)
		);

	}//end testReadsStringsAndBooleansWithDefaults()

	/**
	 * @return array<string, array{0: mixed, 1: bool}>
	 */
	public static function booleans(): array {
		return [
			'the word false' => ['false', false],
			'the word true' => ['true', true],
			'zero string' => ['0', false],
			'one string' => ['1', true],
			'empty string' => ['', false],
			'real false' => [false, false],
			'real true' => [true, true],
			'any other word' => ['no', true],
			'upper-case FALSE is not the word false' => ['FALSE', true],
		];

	}//end booleans()

	/**
	 * @dataProvider booleans
	 */
	public function testBooleanParsing(mixed $sent, bool $expected): void {
		$host = $this->host(['flag' => $sent]);

		$this->assertSame(['flag' => $expected], $host->typedRequestParameters(['flag' => ['bool', false]]));

	}//end testBooleanParsing()

	/**
	 * @return array<string, array{0: mixed}>
	 */
	public static function nonStrings(): array {
		return [
			'array' => [['a']],
			'integer' => [5],
			'null' => [null],
		];

	}//end nonStrings()

	/**
	 * @dataProvider nonStrings
	 */
	public function testANonStringForAStringParameterRefusesTheWholeRequest(mixed $sent): void {
		$host = $this->host(['ok' => 'fine', 'path' => $sent]);

		$this->assertNull($host->typedRequestParameters(['ok' => ['string', ''], 'path' => ['string', '']]));

	}//end testANonStringForAStringParameterRefusesTheWholeRequest()
}//end class
