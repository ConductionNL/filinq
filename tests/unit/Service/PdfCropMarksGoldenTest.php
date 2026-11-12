<?php

/**
 * Golden output of PdfService::buildCropMarksHtml
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

use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\TemplateRenderer;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * The crop-mark wrapper, byte for byte, compared with a recorded one.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class PdfCropMarksGoldenTest extends TestCase {

	/**
	 * The wrapped HTML matches the recorded bytes.
	 *
	 * @return void
	 */
	public function testCropMarksWrapperMatchesTheRecordedOne(): void {
		$service = new PdfService($this->createMock(LoggerInterface::class), $this->createMock(TemplateRenderer::class));
		$wrapped = $service->buildCropMarksHtml(html: '<p>Brief</p>');

		$path = __DIR__ . '/golden/pdf-crop-marks.html';
		if (getenv('GOLDEN_RECORD') === '1') {
			file_put_contents($path, $wrapped);
		}

		$this->assertSame((string)file_get_contents($path), $wrapped);

	}//end testCropMarksWrapperMatchesTheRecordedOne()
}//end class
