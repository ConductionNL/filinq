<?php

/**
 * Unit tests for the file-extension stripping shared by the conversion services.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
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

namespace OCA\Filinq\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;

/**
 * Pins what each conversion service makes of a file name.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @covers \OCA\Filinq\Service\Conversion\EmlBackend
 * @covers \OCA\Filinq\Service\Conversion\LibreOfficeHeadlessBackend
 * @covers \OCA\Filinq\Service\Conversion\MpdfBackend
 * @covers \OCA\Filinq\Service\Conversion\OfficeAppBackend
 * @covers \OCA\Filinq\Service\Conversion\PhpWordBackend
 * @covers \OCA\Filinq\Service\EmlAnonymizationService
 * @covers \OCA\Filinq\Service\Pdfa3ConversionService
 */
class StripsFileExtensionTest extends TestCase {

	/**
	 * @return array<string, array{0: class-string}>
	 */
	public static function services(): array {
		$cases = [];
		foreach ([
			\OCA\Filinq\Service\Conversion\EmlBackend::class,
			\OCA\Filinq\Service\Conversion\LibreOfficeHeadlessBackend::class,
			\OCA\Filinq\Service\Conversion\MpdfBackend::class,
			\OCA\Filinq\Service\Conversion\OfficeAppBackend::class,
			\OCA\Filinq\Service\Conversion\PhpWordBackend::class,
			\OCA\Filinq\Service\EmlAnonymizationService::class,
			\OCA\Filinq\Service\Pdfa3ConversionService::class,
		] as $class) {
			$cases[substr(strrchr($class, '\\'), 1)] = [$class];
		}
		return $cases;
	}

	/**
	 * @dataProvider services
	 *
	 * @param class-string $class The service.
	 */
	public function testStripsOnlyTheLastExtension(string $class): void {
		$method = new \ReflectionMethod($class, 'stripExtension');
		$service = (new \ReflectionClass($class))->newInstanceWithoutConstructor();
		$seen = [];
		foreach (['brief.pdf', 'jaar.verslag.docx', 'zonder', '.verborgen', 'map.v1/bestand', '', 'eindigt.'] as $name) {
			$seen[$name] = $method->invoke($service, $name);
		}

		$this->assertSame(
			['brief.pdf' => 'brief', 'jaar.verslag.docx' => 'jaar.verslag', 'zonder' => 'zonder', '.verborgen' => '', 'map.v1/bestand' => 'map', '' => '', 'eindigt.' => 'eindigt'],
			$seen
		);
	}
}
