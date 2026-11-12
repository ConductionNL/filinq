<?php

/**
 * Unit tests for how the policy services read a validity date
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use DateTimeImmutable;
use OCA\Filinq\Service\PolicyRetroactiveService;
use OCA\Filinq\Service\PolicyRuleNormaliser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Pins parseDateTime(): an empty or unreadable value is no date, anything
 * DateTimeImmutable reads is that date.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class PolicyDateParsingCharacterisationTest extends TestCase {

	/**
	 * Both policy services that read validity dates.
	 *
	 * @return array<string,array{0:class-string}>
	 */
	public static function services(): array {
		return [
			'retroactive' => [PolicyRetroactiveService::class],
			'normaliser'  => [PolicyRuleNormaliser::class],
		];

	}//end services()

	/**
	 * Empty and unreadable values are no date; readable ones are kept exactly.
	 *
	 * @param class-string $class The service under test.
	 *
	 * @return void
	 */
	#[DataProvider('services')]
	public function testParseDateTime(string $class): void {
		$this->assertNull($this->parse($class, ''));
		$this->assertNull($this->parse($class, 'not a date'));

		$parsed = $this->parse($class, '2026-03-01T10:15:00+01:00');
		$this->assertInstanceOf(DateTimeImmutable::class, $parsed);
		$this->assertSame('2026-03-01T10:15:00+01:00', $parsed->format(DATE_ATOM));

	}//end testParseDateTime()

	/**
	 * Call the service's parseDateTime() on an instance built without its constructor.
	 *
	 * @param class-string $class The service under test.
	 * @param string       $value The value to read.
	 *
	 * @return mixed The date, or null.
	 */
	private function parse(string $class, string $value): mixed {
		$reflection = new ReflectionClass($class);
		return $reflection->getMethod('parseDateTime')->invoke($reflection->newInstanceWithoutConstructor(), $value);

	}//end parse()

}//end class
