<?php

/**
 * Unit tests for SofficeProcessRunner.
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

use OCA\Filinq\Exception\ConversionFailedException;
use OCA\Filinq\Service\Conversion\SofficeProcessRunner;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Running a child process with a deadline, driven by short /bin/sh scripts.
 *
 * @covers \OCA\Filinq\Service\Conversion\SofficeProcessRunner
 * @uses   \OCA\Filinq\Exception\ConversionFailedException
 */
class SofficeProcessRunnerTest extends TestCase {

	/**
	 * The logger double.
	 *
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface&MockObject $logger;

	/**
	 * Skip when there is no shell to drive.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		if (is_executable('/bin/sh') === false || function_exists('proc_open') === false) {
			$this->markTestSkipped('needs /bin/sh and proc_open');
		}

		$this->logger = $this->createMock(LoggerInterface::class);
	}//end setUp()

	/**
	 * Run a shell script through the runner.
	 *
	 * @param string $script  The script.
	 * @param int    $timeout Timeout in seconds.
	 *
	 * @return int
	 */
	private function runScript(string $script, int $timeout = 10): int {
		return (new SofficeProcessRunner(logger: $this->logger))->run(
			argv: ['/bin/sh', '-c', $script],
			timeout: $timeout,
			tmpDir: '/tmp/x',
			backendName: 'libreoffice'
		);
	}//end runScript()

	/**
	 * A clean exit returns 0; stdout is discarded and nothing is logged.
	 *
	 * @return void
	 */
	public function testCleanExitReturnsZero(): void {
		$this->logger->expects($this->never())->method('debug');
		$this->logger->expects($this->never())->method('warning');

		$this->assertSame(0, $this->runScript(script: 'echo converted'));
	}//end testCleanExitReturnsZero()

	/**
	 * The child's exit code is returned and its stderr logged at debug level.
	 *
	 * @return void
	 */
	public function testExitCodeAndStderrAreReported(): void {
		$this->logger->expects($this->once())->method('debug')->with(
			'[LibreOfficeHeadlessBackend] soffice stderr',
			['stderr' => "bad input\n"]
		);

		$this->assertSame(3, $this->runScript(script: 'echo "bad input" >&2; exit 3'));
	}//end testExitCodeAndStderrAreReported()

	/**
	 * Logged stderr is capped at 500 bytes.
	 *
	 * @return void
	 */
	public function testStderrIsTruncatedInTheLog(): void {
		$this->logger->expects($this->once())->method('debug')->with(
			'[LibreOfficeHeadlessBackend] soffice stderr',
			['stderr' => str_repeat('e', 500)]
		);

		$this->assertSame(0, $this->runScript(script: 'printf "%0800d" 0 | tr 0 e >&2'));
	}//end testStderrIsTruncatedInTheLog()

	/**
	 * A child killed by a signal has no exit status; the runner reports it as failure 1.
	 *
	 * @return void
	 */
	public function testASignalledChildIsReportedAsFailure(): void {
		$this->logger->expects($this->never())->method('warning');

		$this->assertSame(1, $this->runScript(script: 'kill -9 $$'));
	}//end testASignalledChildIsReportedAsFailure()

	/**
	 * A child that outlives the timeout is killed and reported as a failed attempt.
	 *
	 * @return void
	 */
	public function testTimeoutKillsAndThrows(): void {
		$this->logger->expects($this->once())->method('warning')->with(
			'[LibreOfficeHeadlessBackend] soffice timed out',
			$this->callback(static fn (array $context): bool => $context['timeout'] === 1 && $context['tmpDir'] === '/tmp/x')
		);

		$started = microtime(true);
		try {
			$this->runScript(script: 'sleep 5', timeout: 1);
			$this->fail('Expected ConversionFailedException');
		} catch (ConversionFailedException $e) {
			$this->assertSame('soffice timed out after 1 seconds.', $e->getMessage());
			$this->assertSame(
				[['name' => 'libreoffice', 'available' => true, 'supports' => true, 'reason' => 'timeout after 1 seconds']],
				$e->getAttempts()
			);
		}

		$this->assertLessThan(4.5, microtime(true) - $started);
	}//end testTimeoutKillsAndThrows()
}//end class
