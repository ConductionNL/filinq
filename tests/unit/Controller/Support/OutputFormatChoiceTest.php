<?php

/**
 * Unit tests for OutputFormatChoice.
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
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller\Support;

use OCA\Filinq\Controller\Support\OutputFormatChoice;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;

/**
 * The effective output format of an anonymisation run: the per-call value
 * when given, else the tenant default, else pdf-only.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @covers \OCA\Filinq\Controller\Support\OutputFormatChoice
 */
class OutputFormatChoiceTest extends TestCase {

	/**
	 * A choice over a tenant whose stored default is $stored.
	 *
	 * @param string $stored The value the app config answers.
	 *
	 * @return OutputFormatChoice
	 */
	private function choice(string $stored): OutputFormatChoice {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')
			->with('filinq', 'filinq.anonymisation.default_output_format', 'pdf-only')
			->willReturn($stored);
		return new OutputFormatChoice(appConfig: $config);

	}//end choice()

	/**
	 * Every supported per-call value is taken as given, without reading the tenant.
	 *
	 * @return void
	 */
	public function testASupportedPerCallValueWinsWithoutReadingTheTenant(): void {
		$config = $this->createMock(IAppConfig::class);
		$config->expects($this->never())->method('getValueString');
		$choice = new OutputFormatChoice(appConfig: $config);

		foreach (['pdf-only', 'pdf', 'preserve'] as $format) {
			$this->assertSame($format, $choice->resolve(params: ['outputFormat' => $format]));
		}

	}//end testASupportedPerCallValueWinsWithoutReadingTheTenant()

	/**
	 * An unsupported per-call value is refused with null, even when the
	 * tenant default is valid: the caller answers 400 rather than guessing.
	 *
	 * @return void
	 */
	public function testAnUnsupportedPerCallValueIsRefused(): void {
		$choice = $this->choice(stored: 'pdf');

		foreach (['docx', 'PDF', '', null, 1, ['pdf']] as $value) {
			$this->assertNull($choice->resolve(params: ['outputFormat' => $value]), var_export($value, true));
		}

	}//end testAnUnsupportedPerCallValueIsRefused()

	/**
	 * Without a per-call value the tenant default applies.
	 *
	 * @return void
	 */
	public function testTheTenantDefaultAppliesWithoutAPerCallValue(): void {
		$this->assertSame('preserve', $this->choice(stored: 'preserve')->resolve(params: ['other' => 'x']));
		$this->assertSame('pdf', $this->choice(stored: 'pdf')->resolve(params: []));

	}//end testTheTenantDefaultAppliesWithoutAPerCallValue()

	/**
	 * A malformed tenant default falls back to pdf-only instead of refusing.
	 *
	 * @return void
	 */
	public function testAMalformedTenantDefaultFallsBackToPdfOnly(): void {
		$this->assertSame('pdf-only', $this->choice(stored: 'tiff')->resolve(params: []));
		$this->assertSame('pdf-only', $this->choice(stored: '')->resolve(params: []));

	}//end testAMalformedTenantDefaultFallsBackToPdfOnly()

	/**
	 * The supported formats and the config key are part of the contract.
	 *
	 * @return void
	 */
	public function testTheSupportedFormatsAndKey(): void {
		$this->assertSame(['pdf-only', 'pdf', 'preserve'], OutputFormatChoice::VALID_OUTPUT_FORMATS);
		$this->assertSame('filinq.anonymisation.default_output_format', OutputFormatChoice::DEFAULT_OUTPUT_FORMAT_KEY);

	}//end testTheSupportedFormatsAndKey()
}//end class
