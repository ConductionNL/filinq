<?php

/**
 * Unit tests for OutputLayoutMover.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Conversion
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link https://conduction.nl
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-10
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Conversion;

use OCA\Filinq\Service\Conversion\OutputLayoutMover;
use OCA\Filinq\Service\Conversion\OutputLayoutResolver;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * The mover runs against the REAL OutputLayoutResolver, so the destination
 * asserted here is the one the resolver computes, not a stubbed path.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class OutputLayoutMoverTest extends TestCase {

	/**
	 * @var IRootFolder|MockObject
	 */
	private IRootFolder|MockObject $rootFolder;

	/**
	 * @var IAppConfig|MockObject
	 */
	private IAppConfig|MockObject $config;

	/**
	 * Set up the mover dependencies.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->config     = $this->createMock(IAppConfig::class);
		$this->config->method('getValueString')->willReturnCallback(
			static fn (string $app, string $key, string $default): string => $default
		);

	}//end setUp()

	/**
	 * Build the mover over the real resolver.
	 *
	 * @return OutputLayoutMover
	 */
	private function mover(): OutputLayoutMover {
		$logger = $this->createMock(LoggerInterface::class);
		return new OutputLayoutMover(
			new OutputLayoutResolver($this->config, $logger),
			$this->rootFolder,
			$logger
		);

	}//end mover()

	/**
	 * Wire a redacted output named $name inside a dossier folder.
	 *
	 * @param string $name File name OpenRegister gave the output.
	 * @param Folder|MockObject $dossier The dossier folder.
	 *
	 * @return File|MockObject
	 */
	private function redactedOutput(string $name, Folder|MockObject $dossier): File|MockObject {
		$file = $this->createMock(File::class);
		$file->method('getName')->willReturn($name);
		$file->method('getParent')->willReturn($dossier);
		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('getById')->willReturnCallback(
			static fn (int $id): array => ($id === 99 ? [$file] : [])
		);
		$this->rootFolder->method('getUserFolder')->willReturn($userFolder);
		return $file;

	}//end output()

	/**
	 * A fresh output lands in a newly created `anonymised/` with a clean name.
	 *
	 * @return void
	 */
	public function testOutputMovesIntoNewSubfolderWithCleanName(): void {
		$sub     = $this->createMock(Folder::class);
		$dossier = $this->createMock(Folder::class);
		$dossier->method('getPath')->willReturn('/alice/files/Dossier');
		$dossier->method('nodeExists')->with('anonymised')->willReturn(false);
		$dossier->expects($this->once())->method('newFolder')->with('anonymised')->willReturn($sub);

		$file = $this->redactedOutput(name: 'foo_anonymized.pdf', dossier: $dossier);
		$file->expects($this->once())->method('move')->with('/alice/files/Dossier/anonymised/foo.pdf');

		$out = $this->mover()->relocate('alice', 99, '/alice/files/Dossier/foo_anonymized.pdf');

		$this->assertSame('/alice/files/Dossier/anonymised/foo.pdf', $out['path']);
		$this->assertNull($out['warning']);

	}//end testOutputMovesIntoNewSubfolderWithCleanName()

	/**
	 * A legacy `_anonymized` source re-anonymised does not keep any suffix.
	 *
	 * @return void
	 */
	public function testReAnonymisedLegacyOutputLosesEverySuffix(): void {
		$dossier = $this->createMock(Folder::class);
		$dossier->method('getPath')->willReturn('/alice/files/Dossier');
		$dossier->method('nodeExists')->willReturn(false);
		$dossier->method('newFolder')->willReturn($this->createMock(Folder::class));

		$file = $this->redactedOutput(name: 'foo_anonymized_anonymized.pdf', dossier: $dossier);
		$file->expects($this->once())->method('move')->with('/alice/files/Dossier/anonymised/foo.pdf');

		$this->assertSame(
			'/alice/files/Dossier/anonymised/foo.pdf',
			$this->mover()->relocate('alice', 99, 'legacy')['path']
		);

	}//end testReAnonymisedLegacyOutputLosesEverySuffix()

	/**
	 * A previous run's file of the same name is replaced; the folder is reused.
	 *
	 * @return void
	 */
	public function testExistingSubfolderIsReusedAndPreviousOutputReplaced(): void {
		$previous = $this->createMock(File::class);
		$previous->expects($this->once())->method('delete');
		$sub = $this->createMock(Folder::class);
		$sub->method('nodeExists')->with('foo.pdf')->willReturn(true);
		$sub->method('get')->with('foo.pdf')->willReturn($previous);

		$dossier = $this->createMock(Folder::class);
		$dossier->method('getPath')->willReturn('/alice/files/Dossier');
		$dossier->method('nodeExists')->with('anonymised')->willReturn(true);
		$dossier->method('get')->with('anonymised')->willReturn($sub);
		$dossier->expects($this->never())->method('newFolder');

		$file = $this->redactedOutput(name: 'foo_anonymized.pdf', dossier: $dossier);
		$file->expects($this->once())->method('move');

		$this->assertNull($this->mover()->relocate('alice', 99, 'legacy')['warning']);

	}//end testExistingSubfolderIsReusedAndPreviousOutputReplaced()

	/**
	 * A failed move keeps the legacy path and says so.
	 *
	 * @return void
	 */
	public function testFailedMoveKeepsLegacyPathWithWarning(): void {
		$dossier = $this->createMock(Folder::class);
		$dossier->method('getPath')->willReturn('/alice/files/Dossier');
		$dossier->method('nodeExists')->willReturn(false);
		$dossier->method('newFolder')->willReturn($this->createMock(Folder::class));

		$file = $this->redactedOutput(name: 'foo_anonymized.pdf', dossier: $dossier);
		$file->method('move')->willThrowException(new \Exception('read-only mount'));

		$out = $this->mover()->relocate('alice', 99, '/alice/files/Dossier/foo_anonymized.pdf');

		$this->assertSame('/alice/files/Dossier/foo_anonymized.pdf', $out['path']);
		$this->assertSame(OutputLayoutMover::MOVE_FAILED, $out['warning']['code']);
		$this->assertSame('read-only mount', $out['warning']['message']);

	}//end testFailedMoveKeepsLegacyPathWithWarning()

	/**
	 * An output node that cannot be found is not reported as moved.
	 *
	 * @return void
	 */
	public function testMissingNodeIsNotReportedAsMoved(): void {
		$this->redactedOutput(name: 'foo_anonymized.pdf', dossier: $this->createMock(Folder::class));

		$out = $this->mover()->relocate('alice', 12345, '/alice/files/foo_anonymized.pdf');

		$this->assertSame('/alice/files/foo_anonymized.pdf', $out['path']);
		$this->assertSame(OutputLayoutMover::MOVE_FAILED, $out['warning']['code']);

	}//end testMissingNodeIsNotReportedAsMoved()
}//end class
