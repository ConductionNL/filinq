<?php

/**
 * An office document converts to another editable format through soffice:
 * the source opens with soffice's own detection, the output is the named
 * format, and without LibreOffice nothing runs.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Service\Conversion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Conversion;

use OCA\Filinq\Exception\ConversionFailedException;
use OCA\Filinq\Service\Conversion\LibreOfficeHeadlessBackend;
use OCP\IAppConfig;
use OCP\Lock\ILockingProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \OCA\Filinq\Service\Conversion\LibreOfficeHeadlessBackend
 * @uses   \OCA\Filinq\Service\Conversion\SofficeExport
 * @uses   \OCA\Filinq\Service\Conversion\SofficeCommand
 * @uses   \OCA\Filinq\Exception\ConversionFailedException
 */
class LibreOfficeDocumentConversionTest extends TestCase {

	/**
	 * A backend on a runner, with soffice "installed" as /bin/sh.
	 *
	 * @param DiskLikeSofficeRunner $runner  The runner.
	 * @param bool                  $enabled Whether the backend is switched on.
	 *
	 * @return LibreOfficeHeadlessBackend
	 */
	private function backend(DiskLikeSofficeRunner $runner, bool $enabled=true): LibreOfficeHeadlessBackend {
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getValueString')->willReturnCallback(
			static fn (string $app, string $key, string $default='') => match ($key) {
				'filinq.conversion.libreoffice_binary_path' => '/bin/sh',
				'filinq.conversion.backends.libreoffice_enabled' => ($enabled === true ? 'true' : 'false'),
				default => $default,
			}
		);

		return new LibreOfficeHeadlessBackend(
			appConfig: $appConfig,
			lockingProvider: $this->createMock(ILockingProvider::class),
			logger: $this->createMock(LoggerInterface::class),
			processRunner: $runner,
		);
	}//end backend()

	/**
	 * An ODT becomes a DOCX: soffice gets the ODT as input, the Word export filter, and no HTML input filter.
	 *
	 * @return void
	 */
	public function testAnOdtBecomesADocx(): void {
		$runner = new DiskLikeSofficeRunner('docx from soffice');

		$bytes = $this->backend($runner)->convertDocument(bytes: 'odt bytes', fromExtension: 'ODT', toExtension: 'docx');

		$this->assertSame('docx from soffice', $bytes);
		$argv = $runner->runs[0];
		$this->assertSame('docx:MS Word 2007 XML', $argv[(int) array_search('--convert-to', $argv, true) + 1]);
		$this->assertSame('input.odt', basename((string) end($argv)));
		$this->assertSame(['odt bytes'], $runner->inputs);
		$this->assertNotContains('--infilter=HTML (StarWriter)', $argv);
	}//end testAnOdtBecomesADocx()

	/**
	 * Without LibreOffice, or for an output it cannot make, the conversion fails with 503 and nothing runs.
	 *
	 * @return void
	 */
	public function testWithoutLibreOfficeNothingRuns(): void {
		$runner = new DiskLikeSofficeRunner();
		foreach ([[false, 'docx'], [true, 'pdf']] as [$enabled, $to]) {
			try {
				$this->backend($runner, enabled: $enabled)->convertDocument(bytes: 'odt bytes', fromExtension: 'odt', toExtension: $to);
				$this->fail('Expected the conversion to fail.');
			} catch (ConversionFailedException $e) {
				$this->assertSame(503, $e->getCode());
				$this->assertSame(LibreOfficeHeadlessBackend::UNAVAILABLE_REASON, $e->getMessage());
				$this->assertSame('libreoffice_headless', $e->getAttempts()[0]['name']);
			}
		}

		$this->assertSame([], $runner->runs);
	}//end testWithoutLibreOfficeNothingRuns()
}//end class
