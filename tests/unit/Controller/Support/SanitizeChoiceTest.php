<?php

/**
 * Unit tests for SanitizeChoice.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller\Support
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/document-sanitization/tasks.md#3-4
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller\Support;

use OCA\Filinq\Controller\Support\SanitizeChoice;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;

/**
 * The effective sanitize flag of an anonymisation run: the per-call boolean
 * when given, else the tenant default, else false.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @covers \OCA\Filinq\Controller\Support\SanitizeChoice
 */
class SanitizeChoiceTest extends TestCase {

	/**
	 * A choice over a tenant whose stored default is $stored.
	 *
	 * @param string $stored The value the app config answers.
	 *
	 * @return SanitizeChoice
	 */
	private function choice(string $stored): SanitizeChoice {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')
			->with('filinq', 'filinq.sanitization.default', '0')
			->willReturn($stored);
		return new SanitizeChoice(appConfig: $config);

	}//end choice()

	/**
	 * A per-call boolean wins over the tenant default, both ways.
	 *
	 * @return void
	 */
	public function testThePerCallBooleanWinsOverTheTenant(): void {
		$this->assertTrue($this->choice(stored: '0')->resolve(params: ['sanitize' => true]));
		$this->assertFalse($this->choice(stored: '1')->resolve(params: ['sanitize' => false]));

	}//end testThePerCallBooleanWinsOverTheTenant()

	/**
	 * A per-call value that is not a boolean is refused (null), not coerced.
	 *
	 * @return void
	 */
	public function testANonBooleanPerCallValueIsRefused(): void {
		$this->assertNull($this->choice(stored: '1')->resolve(params: ['sanitize' => 'true']));
		$this->assertNull($this->choice(stored: '0')->resolve(params: ['sanitize' => 1]));
		$this->assertNull($this->choice(stored: '0')->resolve(params: ['sanitize' => null]));

	}//end testANonBooleanPerCallValueIsRefused()

	/**
	 * Without a per-call value the tenant default decides; anything unrecognised reads as off.
	 *
	 * @return void
	 */
	public function testTheTenantDefaultDecidesWhenTheCallIsSilent(): void {
		$this->assertTrue($this->choice(stored: '1')->resolve(params: []));
		$this->assertTrue($this->choice(stored: ' TRUE ')->resolve(params: []));
		$this->assertFalse($this->choice(stored: '0')->resolve(params: []));
		$this->assertFalse($this->choice(stored: 'garbage')->resolve(params: []));

	}//end testTheTenantDefaultDecidesWhenTheCallIsSilent()
}//end class
