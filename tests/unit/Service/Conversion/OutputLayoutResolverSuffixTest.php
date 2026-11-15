<?php

/**
 * Unit tests for OutputLayoutResolver's file-name suffix check.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Conversion
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Conversion;

use OCA\Filinq\Service\Conversion\OutputLayoutResolver;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Whether a full file name is itself an earlier anonymisation output.
 *
 * @covers \OCA\Filinq\Service\Conversion\OutputLayoutResolver
 */
class OutputLayoutResolverSuffixTest extends TestCase {

	/**
	 * The extension is ignored; only a base name ending in _anonymized (once or more) counts.
	 *
	 * @return void
	 */
	public function testOnlyABaseNameEndingInTheSuffixCounts(): void {
		$resolver = new OutputLayoutResolver($this->createMock(IAppConfig::class), $this->createMock(LoggerInterface::class));

		self::assertTrue($resolver->hasAnonymizedSuffix(fileName: 'brief_anonymized.pdf'));
		self::assertTrue($resolver->hasAnonymizedSuffix(fileName: 'brief_anonymized_anonymized.docx'));
		self::assertTrue($resolver->hasAnonymizedSuffix(fileName: 'brief_anonymized'));
		self::assertFalse($resolver->hasAnonymizedSuffix(fileName: 'brief.anonymized.pdf'));
		self::assertFalse($resolver->hasAnonymizedSuffix(fileName: 'brief_anonymized_v2.pdf'));
		self::assertFalse($resolver->hasAnonymizedSuffix(fileName: 'brief.pdf'));
	}//end testOnlyABaseNameEndingInTheSuffixCounts()
}//end class
