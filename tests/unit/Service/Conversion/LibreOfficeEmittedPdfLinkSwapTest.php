<?php

/**
 * Reading the emitted file while its link changes target.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Conversion
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/pdf-conversion-service/tasks.md#task-6
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Conversion;

use PHPUnit\Framework\TestCase;

/**
 * readEmittedPdf() reads the path it checked, not the link it was given.
 *
 * One helper process keeps pointing the emitted file's link at a file inside
 * the sandbox and at one outside it, while another reads it. Every read must
 * either return the inside bytes or be refused.
 *
 * @covers \OCA\Filinq\Service\Conversion\LibreOfficeHeadlessBackend
 * @uses   \OCA\Filinq\Exception\ConversionFailedException
 * @uses   \OCA\Filinq\Service\Conversion\SofficeProcessRunner
 */
class LibreOfficeEmittedPdfLinkSwapTest extends TestCase {

	/**
	 * The case's base directory.
	 *
	 * @var string
	 */
	private string $base = '';

	/**
	 * Remove the case's directories.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		if ($this->base !== '' && is_dir($this->base) === true) {
			exec('rm -rf ' . escapeshellarg($this->base));
		}

		parent::tearDown();
	}//end tearDown()

	/**
	 * No read returns the bytes of the file outside the sandbox.
	 *
	 * The reads run in a PHP process without a realpath cache, as a cache
	 * filled by the check would otherwise hide which path the read resolves.
	 *
	 * @return void
	 */
	public function testAReadNeverFollowsTheLinkOutOfTheSandbox(): void {
		$this->base = sys_get_temp_dir() . '/filinq_link_swap_' . bin2hex(random_bytes(6));
		$sandbox = $this->base . '/sandbox';
		mkdir($sandbox, 0700, true);
		file_put_contents($sandbox . '/real.pdf', '%PDF-inside');
		file_put_contents($this->base . '/outside.pdf', '%PDF-outside');
		symlink($sandbox . '/real.pdf', $sandbox . '/input.pdf');

		$swapper = proc_open([PHP_BINARY, '-r', self::SWAPPER, $sandbox, $this->base . '/outside.pdf'], [], $pipes);
		$reader = proc_open(
			[PHP_BINARY, '-d', 'realpath_cache_size=0', '-r', self::READER, dirname(__DIR__, 4) . '/vendor/autoload.php', $sandbox],
			[1 => ['pipe', 'w']],
			$readerPipes
		);
		$this->assertIsResource($swapper);
		$this->assertIsResource($reader);

		$seen = json_decode((string)stream_get_contents($readerPipes[1]), true);
		proc_close($reader);
		proc_close($swapper);

		$this->assertIsArray($seen);
		$this->assertGreaterThan(0, $seen['%PDF-inside'] ?? 0, (string)json_encode($seen));
		$this->assertSame(0, $seen['%PDF-outside'] ?? 0, (string)json_encode($seen));
	}//end testAReadNeverFollowsTheLinkOutOfTheSandbox()

	/**
	 * Swaps $argv[1]/input.pdf between real.pdf and $argv[2] for one second.
	 */
	private const SWAPPER = '$d = $argv[1]; $end = microtime(true) + 1; $i = 0;'
		. ' while (microtime(true) < $end) { $t = $d . "/.swap"; @unlink($t);'
		. ' symlink(($i++ % 2) === 1 ? $argv[2] : $d . "/real.pdf", $t); rename($t, $d . "/input.pdf"); }';

	/**
	 * Reads $argv[2]/input.pdf through readEmittedPdf() for one second and prints the counts.
	 */
	private const READER = 'require $argv[1]; $c = \\OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend::class;'
		. ' $o = (new \\ReflectionClass($c))->newInstanceWithoutConstructor(); $m = new \\ReflectionMethod($c, "readEmittedPdf");'
		. ' $seen = []; $end = microtime(true) + 1;'
		. ' while (microtime(true) < $end) { try { $b = $m->invoke($o, $argv[2], "input", "pdf"); }'
		. ' catch (\\Throwable) { $b = "refused"; } $seen[$b] = ($seen[$b] ?? 0) + 1; }'
		. ' echo json_encode($seen);';
}//end class
