<?php

/**
 * PrintJobRequest: the requested filename and its default.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Kernel
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Kernel;

use OCA\Filinq\Kernel\PrintJobRequest;
use PHPUnit\Framework\TestCase;

/**
 * A request without a filename takes the caller's default; naming a request
 * gives a copy with the same template and options.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Kernel
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @covers \OCA\Filinq\Kernel\PrintJobRequest
 */
class PrintJobRequestTest extends TestCase {

	/**
	 * Without a filename the default is used; with one, the default is ignored.
	 *
	 * @return void
	 */
	public function testTheFilenameFallsBackToTheDefault(): void {
		$this->assertSame('document.pdf', (new PrintJobRequest(templateId: 't-1'))->filenameOr(default: 'document.pdf'));
		$this->assertSame('brief.pdf', (new PrintJobRequest(templateId: 't-1', filename: 'brief.pdf'))->filenameOr(default: 'document.pdf'));
	}//end testTheFilenameFallsBackToTheDefault()

	/**
	 * An empty filename is a chosen name, not a missing one.
	 *
	 * @return void
	 */
	public function testAnEmptyFilenameIsKept(): void {
		$this->assertSame('', (new PrintJobRequest(templateId: 't-1', filename: ''))->filenameOr(default: 'document.pdf'));
	}//end testAnEmptyFilenameIsKept()

	/**
	 * Naming returns a copy with the same template and options, leaving the
	 * original unnamed.
	 *
	 * @return void
	 */
	public function testNamingReturnsACopyWithTheSameTemplateAndOptions(): void {
		$original = new PrintJobRequest(templateId: 't-1', options: ['duplex' => true]);

		$named = $original->named(filename: 'brief.pdf');

		$this->assertNotSame($original, $named);
		$this->assertSame(['t-1', ['duplex' => true], 'brief.pdf'], [$named->templateId, $named->options, $named->filename]);
		$this->assertNull($original->filename);
	}//end testNamingReturnsACopyWithTheSameTemplateAndOptions()
}//end class
