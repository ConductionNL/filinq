<?php

/**
 * The veraPDF process runner: a binary that cannot run is refused before
 * proc_open, a finished command reports its exit code and both streams, and
 * a command past its budget is killed and reported as timed out.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\VeraPdf
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\VeraPdf;

use OCA\Filinq\Exception\VeraPdfException;
use OCA\Filinq\Service\VeraPdf\VeraPdfProcess;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OCA\Filinq\Service\VeraPdf\VeraPdfProcess
 * @uses   \OCA\Filinq\Exception\VeraPdfException
 */
class VeraPdfProcessTest extends TestCase {

	/**
	 * A POSIX shell by absolute path.
	 *
	 * @return string The path.
	 */
	private function shell(): string {
		if (is_executable('/bin/sh') === false) {
			$this->markTestSkipped('No /bin/sh on this host.');
		}

		return '/bin/sh';

	}//end shell()

	/**
	 * Run and expect an unavailable refusal naming the binary.
	 *
	 * @param array<int, string> $argv     The command.
	 * @param string             $expected The expected message.
	 *
	 * @return void
	 */
	private function assertRefused(array $argv, string $expected): void {
		try {
			(new VeraPdfProcess())->run(argv: $argv, seconds: 5);
			$this->fail('Expected a VeraPdfException.');
		} catch (VeraPdfException $e) {
			$this->assertSame(VeraPdfException::REASON_UNAVAILABLE, $e->getReason());
			$this->assertSame($expected, $e->getMessage());
		}

	}//end assertRefused()

	/**
	 * A path that does not exist is refused as unavailable.
	 *
	 * @return void
	 */
	public function testAMissingPathIsUnavailable(): void {
		$this->assertRefused(
			argv: ['/nonexistent/filinq/verapdf', '--version'],
			expected: 'veraPDF could not be started: /nonexistent/filinq/verapdf'
		);

	}//end testAMissingPathIsUnavailable()

	/**
	 * A file that exists but may not run is refused as unavailable.
	 *
	 * @return void
	 */
	public function testANonExecutableFileIsUnavailable(): void {
		$path = (string) tempnam(sys_get_temp_dir(), 'filinq-verapdf-noexec-');
		chmod($path, 0644);
		try {
			$this->assertRefused(argv: [$path], expected: 'veraPDF could not be started: ' . $path);
		} finally {
			unlink($path);
		}

	}//end testANonExecutableFileIsUnavailable()

	/**
	 * A bare name that is not on the PATH is refused as unavailable.
	 *
	 * @return void
	 */
	public function testANameNotOnThePathIsUnavailable(): void {
		$this->assertRefused(
			argv: ['filinq-no-such-verapdf-binary'],
			expected: 'veraPDF could not be started: filinq-no-such-verapdf-binary'
		);

	}//end testANameNotOnThePathIsUnavailable()

	/**
	 * An empty binary is refused as unavailable.
	 *
	 * @return void
	 */
	public function testAnEmptyBinaryIsUnavailable(): void {
		$this->assertRefused(argv: [''], expected: 'veraPDF could not be started: ');

	}//end testAnEmptyBinaryIsUnavailable()

	/**
	 * A finished command reports its exit code and what it wrote to each stream.
	 *
	 * @return void
	 */
	public function testAFinishedCommandReportsExitCodeAndBothStreams(): void {
		$result = (new VeraPdfProcess())->run(
			argv: [$this->shell(), '-c', 'printf "report"; printf "warning" >&2; exit 3'],
			seconds: 10
		);

		$this->assertSame(
			['exitCode' => 3, 'stdout' => 'report', 'stderr' => 'warning', 'timedOut' => false],
			$result
		);

	}//end testAFinishedCommandReportsExitCodeAndBothStreams()

	/**
	 * The arguments reach the binary as given, without a shell reading them.
	 *
	 * @return void
	 */
	public function testArgumentsArePassedVerbatim(): void {
		$result = (new VeraPdfProcess())->run(
			argv: [$this->shell(), '-c', 'printf "%s|" "$@"', 'sh', 'a b', '$HOME', '; rm -rf x'],
			seconds: 10
		);

		$this->assertSame(0, $result['exitCode']);
		$this->assertSame('a b|$HOME|; rm -rf x|', $result['stdout']);

	}//end testArgumentsArePassedVerbatim()

	/**
	 * A bare name found on the PATH runs.
	 *
	 * @return void
	 */
	public function testABareNameOnThePathRuns(): void {
		$this->shell();
		$result = (new VeraPdfProcess())->run(argv: ['sh', '-c', 'printf ok'], seconds: 10);

		$this->assertSame(0, $result['exitCode']);
		$this->assertSame('ok', $result['stdout']);
		$this->assertFalse($result['timedOut']);

	}//end testABareNameOnThePathRuns()

	/**
	 * Output larger than one read is collected whole.
	 *
	 * @return void
	 */
	public function testLargeOutputIsCollectedWhole(): void {
		$result = (new VeraPdfProcess())->run(
			argv: [$this->shell(), '-c', 'i=0; while [ $i -lt 4000 ]; do printf "0123456789012345678901234567890123456789\n"; i=$((i+1)); done'],
			seconds: 20
		);

		$this->assertSame(0, $result['exitCode']);
		$this->assertSame(4000 * 41, strlen($result['stdout']));

	}//end testLargeOutputIsCollectedWhole()

	/**
	 * A command past its budget is killed and reported as timed out, well
	 * before it would have finished.
	 *
	 * @return void
	 */
	public function testACommandPastItsBudgetIsKilledAndTimedOut(): void {
		$start = hrtime(true);
		$result = (new VeraPdfProcess())->run(
			argv: [$this->shell(), '-c', 'printf started; exec sleep 30'],
			seconds: 1
		);
		$elapsed = ((hrtime(true) - $start) / 1000000000);

		$this->assertTrue($result['timedOut']);
		$this->assertNotSame(0, $result['exitCode']);
		$this->assertLessThan(15, $elapsed);

	}//end testACommandPastItsBudgetIsKilledAndTimedOut()
}//end class
