<?php

/**
 * DocumentStampRequestedListener tests: the stamped PDF in the result slot,
 * a refusal with its code, and a failure that never reaches the caller.
 *
 * The listener runs on the real PdfStampService and the real event.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-2-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\EventListener;

use Mpdf\Mpdf;
use OCA\Filinq\Event\DocumentStampRequestedEvent;
use OCA\Filinq\EventListener\DocumentStampRequestedListener;
use OCA\Filinq\Service\ChartSvgRenderer;
use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\PdfStampService;
use OCA\Filinq\Service\TableHtmlRenderer;
use OCA\Filinq\Service\TemplateRenderer;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use setasign\Fpdi\PdfParser\PdfParser;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\PdfReader\PdfReader;

/**
 * Tests for DocumentStampRequestedListener.
 *
 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-2-1
 */
class DocumentStampRequestedListenerTest extends TestCase {

	private DocumentStampRequestedListener $listener;

	/**
	 * Build the listener on the real stamp service.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$logger = $this->createMock(LoggerInterface::class);
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getValueString')->willReturnCallback(
			static fn (string $app, string $key, string $default = ''): string => $default
		);

		$this->listener = new DocumentStampRequestedListener(
			new PdfStampService(
				new PdfService($logger, new TemplateRenderer($logger, new ChartSvgRenderer(), new TableHtmlRenderer())),
				$appConfig,
				$logger
			),
			$logger
		);

	}//end setUp()

	/**
	 * The stamped PDF lands in the result slot and the event is handled.
	 *
	 * @return void
	 */
	public function testTheStampedPdfComesBackOnTheEvent(): void {
		$mpdf = new Mpdf(['tempDir' => sys_get_temp_dir() . '/mpdf-stamp-test']);
		$mpdf->WriteHTML('<p>Agendapunt 4</p>');
		$mpdf->AddPage();
		$mpdf->WriteHTML('<p>Bijlage</p>');
		$source = $mpdf->Output('', 'S');

		$event = new DocumentStampRequestedEvent('decidiq', $source, "J. de Vries\nVertrouwelijk", 'both', 'paper-7');
		$this->listener->handle($event);

		$this->assertTrue($event->isHandled());
		$this->assertSame('', $event->getRefusalCode());
		$stamped = (string)$event->getStampedPdf();
		$this->assertStringStartsWith('%PDF-', $stamped);
		$reader = new PdfReader(new PdfParser(StreamReader::createByString($stamped)));
		$this->assertSame(2, $reader->getPageCount());

	}//end testTheStampedPdfComesBackOnTheEvent()

	/**
	 * A refusal carries its code and leaves the event unhandled.
	 *
	 * @return void
	 */
	public function testARefusalCarriesItsCode(): void {
		$event = new DocumentStampRequestedEvent('decidiq', 'not a pdf at all', 'Vertrouwelijk');
		$this->listener->handle($event);

		$this->assertFalse($event->isHandled());
		$this->assertNull($event->getStampedPdf());
		$this->assertSame('not-a-pdf', $event->getRefusalCode());
		$this->assertNotSame('', $event->getRefusalReason());

	}//end testARefusalCarriesItsCode()

	/**
	 * A failure inside filinq leaves the event unhandled with `failed`, and throws nothing.
	 *
	 * @return void
	 */
	public function testAFailureIsNeverThrownToTheCaller(): void {
		$event = new DocumentStampRequestedEvent('decidiq', '%PDF-1.4 broken', 'Vertrouwelijk', 'sideways');
		$this->listener->handle($event);

		$this->assertFalse($event->isHandled());
		$this->assertSame('failed', $event->getRefusalCode());

	}//end testAFailureIsNeverThrownToTheCaller()
}//end class
