<?php

/**
 * The cascade reports what each backend can do without converting anything.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/multi-format-output/tasks.md#task-2.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Exception\ConversionFailedException;
use OCA\Filinq\Service\Conversion\ConversionBackendInterface;
use OCA\Filinq\Service\PdfConversionService;
use OCP\Files\File;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * getCapabilities() has the exception report's shape and never throws.
 */
class PdfConversionCapabilitiesTest extends TestCase {

	/**
	 * A backend double.
	 *
	 * @param string   $name      Its name.
	 * @param bool     $available What isAvailable() answers.
	 * @param string[] $handles   The extensions canHandle() accepts.
	 *
	 * @return ConversionBackendInterface
	 */
	private function backend(string $name, bool $available, array $handles): ConversionBackendInterface {
		$backend = $this->createMock(ConversionBackendInterface::class);
		$backend->method('name')->willReturn($name);
		$backend->method('isAvailable')->willReturn($available);
		$backend->method('canHandle')->willReturnCallback(static fn (string $mime, string $ext): bool => in_array($ext, $handles, true));
		$backend->expects($this->never())->method('convert');

		return $backend;
	}

	public function testGetCapabilitiesShape(): void {
		$service = new PdfConversionService(
			[
				$this->backend(name: 'office_app', available: false, handles: ['docx', 'odt']),
				$this->backend(name: 'libreoffice_headless', available: true, handles: ['html', 'docx', 'odt']),
			],
			new NullLogger()
		);

		$report = $service->getCapabilities();

		$this->assertSame(['office_app', 'libreoffice_headless'], array_column($report, 'name'));
		$this->assertSame(
			['name' => 'office_app', 'available' => false, 'supports' => false, 'inputs' => ['docx', 'odt'], 'reason' => 'backend disabled or prerequisites not present'],
			$report[0]
		);
		$this->assertSame(
			['name' => 'libreoffice_headless', 'available' => true, 'supports' => true, 'inputs' => ['html', 'docx', 'odt'], 'reason' => null],
			$report[1]
		);

		// The keys a failed conversion reports its attempts with are all here.
		$failure = null;
		$source = $this->createMock(File::class);
		$source->method('getName')->willReturn('a.xyz');
		$source->method('getMimeType')->willReturn('application/x-unknown');
		try {
			(new PdfConversionService([$this->backend(name: 'x', available: false, handles: [])], new NullLogger()))->convertToPdf($source);
		} catch (ConversionFailedException $e) {
			$failure = $e->getAttempts()[0];
		}

		$this->assertNotNull($failure);
		$this->assertSame([], array_diff(array_keys($failure), array_keys($report[0])));
	}

	public function testProbeFailureDegrades(): void {
		$broken = $this->createMock(ConversionBackendInterface::class);
		$broken->method('name')->willReturn('libreoffice_headless');
		$broken->method('isAvailable')->willThrowException(new RuntimeException('binary path unreadable'));

		$report = (new PdfConversionService([$broken], new NullLogger()))->getCapabilities();

		$this->assertFalse($report[0]['available']);
		$this->assertSame('availability probe failed: binary path unreadable', $report[0]['reason']);
	}
}//end class
