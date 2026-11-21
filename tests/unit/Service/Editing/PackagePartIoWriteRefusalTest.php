<?php

/**
 * Unit tests for PackagePartIo::writePart on bytes that are not a package.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Editing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Editing;

use OCA\Filinq\Service\Editing\PackagePartIo;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Writing into something that is not a zip is refused with a readable message.
 *
 * @covers \OCA\Filinq\Service\Editing\PackagePartIo
 */
class PackagePartIoWriteRefusalTest extends TestCase {

	/**
	 * Plain text cannot be opened as a package to write into.
	 *
	 * @return void
	 */
	public function testWritingIntoNonPackageBytesIsRefused(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('The file is not a writable document package.');

		(new PackagePartIo())->writePart(packageBytes: 'plain text, not a zip', part: 'content.xml', xml: '<x/>');
	}//end testWritingIntoNonPackageBytesIsRefused()
}//end class
