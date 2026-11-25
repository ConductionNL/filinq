<?php

/**
 * Tests for AppConfigFlag.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/files-confidential-labels/specs/files-confidential-labels/spec.md#requirement-optionally-suggest-batchfolder-analysis-priority-req-ddfcl-003
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Support;

use OCA\Filinq\Service\Support\AppConfigFlag;
use OCP\Exceptions\AppConfigTypeConflictException;
use OCP\IAppConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * An on/off setting is read whatever type it was stored with.
 */
class AppConfigFlagTest extends TestCase {

	/**
	 * The strings the admin settings store, and what they mean.
	 *
	 * @return array<string, array{0: string, 1: bool}>
	 */
	public static function storedStrings(): array {
		return [
			'one'   => ['1', true],
			'true'  => ['true', true],
			'TRUE'  => [' TRUE ', true],
			'yes'   => ['yes', true],
			'zero'  => ['0', false],
			'false' => ['false', false],
			'other' => ['maybe', false],
		];
	}//end storedStrings()

	/**
	 * A flag the settings page saved as a string is read as that string,
	 * without the typed boolean read that threw on it.
	 *
	 * @param string $stored   What is stored.
	 * @param bool   $expected Whether that is on.
	 *
	 * @return void
	 */
	#[DataProvider('storedStrings')]
	public function testAStoredStringIsReadAsAFlag(string $stored, bool $expected): void {
		$config = $this->createMock(originalClassName: IAppConfig::class);
		$config->method('getValueString')->willReturn($stored);
		$config->expects($this->never())->method('getValueBool');

		$this->assertSame(expected: $expected, actual: (new AppConfigFlag(config: $config))->isOn(app: 'filinq', key: 'k'));
	}//end testAStoredStringIsReadAsAFlag()

	/**
	 * A flag stored as a typed boolean is read as one.
	 *
	 * @return void
	 */
	public function testATypedBooleanIsReadAsOne(): void {
		$config = $this->createMock(originalClassName: IAppConfig::class);
		$config->method('getValueString')->willThrowException(new AppConfigTypeConflictException('typed'));
		$config->method('getValueBool')->willReturn(true);

		$this->assertTrue(condition: (new AppConfigFlag(config: $config))->isOn(app: 'filinq', key: 'k'));
	}//end testATypedBooleanIsReadAsOne()

	/**
	 * An unset flag is off.
	 *
	 * @return void
	 */
	public function testAnUnsetFlagIsOff(): void {
		$config = $this->createMock(originalClassName: IAppConfig::class);
		$config->method('getValueString')->willReturn('');
		$config->method('getValueBool')->willReturnArgument(2);

		$this->assertFalse(condition: (new AppConfigFlag(config: $config))->isOn(app: 'filinq', key: 'k'));
	}//end testAnUnsetFlagIsOff()
}//end class
