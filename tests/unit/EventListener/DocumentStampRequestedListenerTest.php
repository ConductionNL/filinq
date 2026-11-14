<?php

/**
 * Tests for DocumentStampRequestedListener and its event.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\EventListener;

use Mpdf\Mpdf;
use OCA\Filinq\Event\DocumentStampRequestedEvent;
use OCA\Filinq\EventListener\DocumentStampRequestedListener;
use OCA\Filinq\Exception\PdfStampRefusal;
use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\PdfStampService;
use OCA\Filinq\Service\PdfStreamReaderFactory;
use OCP\EventDispatcher\Event;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;

/**
 * The real event and the real stamping service: the stamped PDF lands in the
 * result slot, a refusal lands as a code, and nothing is thrown at the caller.
 *
 * @covers \OCA\Filinq\EventListener\DocumentStampRequestedListener
 * @covers \OCA\Filinq\Event\DocumentStampRequestedEvent
 * @uses   \OCA\Filinq\Service\PdfStampService
 * @uses   \OCA\Filinq\Service\PdfStreamReaderFactory
 * @uses   \OCA\Filinq\Exception\PdfStampRefusal
 */
class DocumentStampRequestedListenerTest extends TestCase {

	/**
	 * The multibyte encoding before the test: mPDF in core-font mode changes it.
	 *
	 * @var string
	 */
	private string $encoding = 'UTF-8';

	protected function setUp(): void {
		parent::setUp();
		$this->encoding = (string) mb_internal_encoding();
	}//end setUp()

	protected function tearDown(): void {
		mb_internal_encoding($this->encoding);
		parent::tearDown();
	}//end tearDown()

	/**
	 * The listener over the real service, with mPDF from the given factory.
	 *
	 * @param callable|null $mpdf Builds the mPDF instance; null for a working one.
	 *
	 * @return DocumentStampRequestedListener
	 */
	private function listener(?callable $mpdf = null): DocumentStampRequestedListener {
		$pdfService = $this->createMock(PdfService::class);
		$pdfService->method('createMpdfInstance')->willReturnCallback(
			$mpdf ?? static fn (): Mpdf => new Mpdf(['tempDir' => sys_get_temp_dir() . '/mpdf-stamp-test', 'mode' => 'c'])
		);
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueInt')->willReturn(PdfStampService::DEFAULT_MAX_BYTES);

		return new DocumentStampRequestedListener(
			stamps: new PdfStampService(pdfService: $pdfService, streamReaders: new PdfStreamReaderFactory(), appConfig: $config),
			logger: $this->createMock(LoggerInterface::class)
		);
	}//end listener()

	/**
	 * A two-page PDF.
	 *
	 * @return string The bytes.
	 */
	private static function paper(): string {
		$mpdf = new Mpdf(['tempDir' => sys_get_temp_dir() . '/mpdf-stamp-test', 'mode' => 'c']);
		$mpdf->WriteHTML('<p>Paper</p>');
		$mpdf->AddPage();
		$mpdf->WriteHTML('<p>Annex</p>');

		return $mpdf->Output('', 'S');
	}//end paper()

	public function testTheStampedPdfLandsInTheResultSlot(): void {
		$event = new DocumentStampRequestedEvent(
			sourceApp: 'decidiq',
			pdfContent: self::paper(),
			text: "J. de Vries, 28-09-2026 14:05\nVertrouwelijk",
			placement: 'footer',
			correlationId: 'paper-7'
		);

		$this->listener()->handle($event);

		$this->assertTrue($event->isHandled());
		$this->assertNull($event->getRefusalCode());
		$stamped = (string) $event->getStampedPdf();
		$this->assertStringStartsWith('%PDF-', $stamped);
		$this->assertSame(2, (new Fpdi())->setSourceFile(StreamReader::createByString($stamped)));
		$this->assertNotSame(self::paper(), $stamped);
		$this->assertSame(['decidiq', 'footer', 'paper-7'], [$event->getSourceApp(), $event->getPlacement(), $event->getCorrelationId()]);
	}//end testTheStampedPdfLandsInTheResultSlot()

	public function testARefusalLandsAsItsCodeAndTheEventStaysUnhandled(): void {
		$event = new DocumentStampRequestedEvent(sourceApp: 'decidiq', pdfContent: 'not a pdf', text: 'x');

		$this->listener()->handle($event);

		$this->assertFalse($event->isHandled());
		$this->assertNull($event->getStampedPdf());
		$this->assertSame(PdfStampRefusal::NOT_A_PDF, $event->getRefusalCode());
		$this->assertSame('The file is not a PDF.', $event->getRefusalReason());
		$this->assertSame('both', $event->getPlacement());
	}//end testARefusalLandsAsItsCodeAndTheEventStaysUnhandled()

	public function testAnUnexpectedFailureIsFailedAndNeverThrown(): void {
		$event = new DocumentStampRequestedEvent(sourceApp: 'decidiq', pdfContent: self::paper(), text: 'x');

		$this->listener(mpdf: static function (): Mpdf {
			throw new RuntimeException('no temp dir');
		})->handle($event);

		$this->assertFalse($event->isHandled());
		$this->assertSame(PdfStampRefusal::FAILED, $event->getRefusalCode());
		$this->assertNull($event->getStampedPdf());
	}//end testAnUnexpectedFailureIsFailedAndNeverThrown()

	public function testAnotherEventIsIgnored(): void {
		$stamps = $this->createMock(PdfStampService::class);
		$stamps->expects($this->never())->method('stamp');

		(new DocumentStampRequestedListener(stamps: $stamps, logger: $this->createMock(LoggerInterface::class)))->handle(new Event());
	}//end testAnotherEventIsIgnored()
}//end class
