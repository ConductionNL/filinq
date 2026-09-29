<?php

/**
 * HTML to DOCX and ODT go through the cascade's soffice backend.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Service\Conversion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/multi-format-output/tasks.md#task-2.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Conversion;

require_once __DIR__ . '/DiskLikeSofficeRunner.php';

use OCA\Filinq\Exception\ConversionFailedException;
use OCA\Filinq\Service\Conversion\HtmlToOfficeConverter;
use OCA\Filinq\Service\Conversion\LibreOfficeHeadlessBackend;
use OCP\IAppConfig;
use OCP\Lock\ILockingProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * The converter runs soffice under the lock with the Writer filters.
 */
class HtmlToOfficeConverterTest extends TestCase {

	/**
	 * A backend over the runner double; `/bin/sh` stands in for soffice.
	 *
	 * @param DiskLikeSofficeRunner $runner  The runner.
	 * @param bool                  $enabled Whether LibreOffice is switched on.
	 * @param ILockingProvider|null $locks   The lock provider.
	 *
	 * @return LibreOfficeHeadlessBackend
	 */
	private function backend(DiskLikeSofficeRunner $runner, bool $enabled = true, ?ILockingProvider $locks = null): LibreOfficeHeadlessBackend {
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getValueString')->willReturnCallback(
			static fn (string $app, string $key, string $default = '') => match ($key) {
				'filinq.conversion.libreoffice_binary_path' => '/bin/sh',
				'filinq.conversion.backends.libreoffice_enabled' => ($enabled === true ? 'true' : 'false'),
				default => $default,
			}
		);

		return new LibreOfficeHeadlessBackend(
			appConfig: $appConfig,
			lockingProvider: ($locks ?? $this->createMock(ILockingProvider::class)),
			logger: $this->createMock(LoggerInterface::class),
			processRunner: $runner,
		);
	}//end backend()

	public function testDocxIsWrittenByWriterFromTheHtmlAndReadBack(): void {
		$runner = new DiskLikeSofficeRunner('PK docx bytes');
		$locks = $this->createMock(ILockingProvider::class);
		$locks->expects($this->once())->method('acquireLock')->with('soffice:headless:convert');
		$converter = new HtmlToOfficeConverter($this->backend(runner: $runner, locks: $locks));

		$this->assertSame('PK docx bytes', $converter->toDocx(html: '<p>Besluit</p>'));
		$this->assertSame(['<p>Besluit</p>'], $runner->inputs);
		$argv = $runner->runs[0];
		$this->assertContains('--infilter=HTML (StarWriter)', $argv);
		$this->assertContains('docx:MS Word 2007 XML', $argv);
		$this->assertStringEndsWith('/input.html', (string) end($argv));
	}

	public function testOdtUsesTheWriterExport(): void {
		$runner = new DiskLikeSofficeRunner('PK odt bytes');
		$converter = new HtmlToOfficeConverter($this->backend(runner: $runner));

		$this->assertSame('PK odt bytes', $converter->toOdt(html: '<p>a</p>'));
		$this->assertContains('odt:writer8', $runner->runs[0]);
	}

	public function testWithoutLibreOfficeItIsA503WithTheMatrixReasonAndSofficeNeverRuns(): void {
		$runner = new DiskLikeSofficeRunner();
		$converter = new HtmlToOfficeConverter($this->backend(runner: $runner, enabled: false));

		$this->assertFalse($converter->isAvailable());
		try {
			$converter->toDocx(html: '<p>a</p>');
			$this->fail('A DOCX without LibreOffice must not be made.');
		} catch (ConversionFailedException $e) {
			$this->assertSame(503, $e->getCode());
			$this->assertSame(LibreOfficeHeadlessBackend::UNAVAILABLE_REASON, $e->getMessage());
		}

		$this->assertSame([], $runner->runs);
	}
}//end class
