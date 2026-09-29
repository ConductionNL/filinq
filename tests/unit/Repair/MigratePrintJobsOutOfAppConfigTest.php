<?php

/**
 * Unit tests for MigratePrintJobsOutOfAppConfig
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Repair
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/specs/print-preview/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\Repair;

require_once __DIR__ . '/../Service/PrintJobDoubles.php';

use OCA\Filinq\Repair\MigratePrintJobsOutOfAppConfig;
use OCA\Filinq\Tests\Unit\Service\PrintJobDoubles;
use OCP\IAppConfig;
use OCP\Migration\IOutput;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Legacy print jobs leave app configuration once they are stored elsewhere.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Repair
 * @author   Conduction Development Team <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class MigratePrintJobsOutOfAppConfigTest extends TestCase {
	use PrintJobDoubles;

	/**
	 * The app configuration, as key => value.
	 *
	 * @var array<string, string>
	 */
	private array $config = [];

	/**
	 * An app config over $this->config.
	 *
	 * @return IAppConfig
	 */
	private function appConfig(): IAppConfig {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getKeys')->willReturnCallback(fn (string $app) => array_keys($this->config));
		$config->method('getValueString')->willReturnCallback(
			fn (string $app, string $key, string $default = '') => $this->config[$key] ?? $default
		);
		$config->method('deleteKey')->willReturnCallback(
			function (string $app, string $key): void {
				unset($this->config[$key]);
			}
		);

		return $config;

	}//end appConfig()

	/**
	 * Seed one single job and one two-letter batch the old way.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->config = [
			'print_job_aaa' => (string) json_encode(['status' => 'completed', 'total' => 1, 'filename' => 'a.pdf', 'ownerUserId' => 'u1', 'externalStatus' => 'printed']),
			'print_job_pdf_aaa' => base64_encode('%PDF-a'),
			'print_job_bbb' => (string) json_encode(['status' => 'completed', 'total' => 2, 'ownerUserId' => 'u2']),
			'print_job_pdf_bbb-1' => base64_encode('%PDF-b1'),
			'print_job_pdf_bbb-0' => base64_encode('%PDF-b0'),
			'other_setting' => 'keep',
		];

	}//end setUp()

	/**
	 * Every job moves under its own id with its PDFs, and its entries go.
	 *
	 * @return void
	 */
	public function testJobsMoveAndTheirEntriesGo(): void {
		$step = new MigratePrintJobsOutOfAppConfig($this->appConfig(), $this->printJobRepository(), $this->printJobFileStore(), $this->createMock(LoggerInterface::class));

		$step->run($this->createMock(IOutput::class));

		$this->assertSame(['other_setting' => 'keep'], $this->config);
		$this->assertSame('printed', $this->rows['aaa']['status']);
		$this->assertSame('u1', $this->rows['aaa']['requestedBy']);
		$this->assertSame('queued', $this->rows['bbb']['status']);
		$this->assertSame(['%PDF-b0', '%PDF-b1'], [$this->stored[$this->rows['bbb']['files'][0]], $this->stored[$this->rows['bbb']['files'][1]]]);

	}//end testJobsMoveAndTheirEntriesGo()

	/**
	 * Before the register import nothing can be stored, so nothing is deleted.
	 *
	 * @return void
	 */
	public function testNothingIsDeletedWhenTheJobCannotBeStored(): void {
		$before = $this->config;
		$step = new MigratePrintJobsOutOfAppConfig($this->appConfig(), $this->printJobRepository(true), $this->printJobFileStore(), $this->createMock(LoggerInterface::class));

		$step->run($this->createMock(IOutput::class));

		$this->assertSame($before, $this->config);

	}//end testNothingIsDeletedWhenTheJobCannotBeStored()
}//end class
