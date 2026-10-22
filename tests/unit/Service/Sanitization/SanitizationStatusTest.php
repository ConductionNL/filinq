<?php

/**
 * Unit tests for SanitizationStatus
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Sanitization
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/document-sanitization/tasks.md#3-5
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Sanitization;

use InvalidArgumentException;
use OCA\Filinq\Service\Sanitization\SanitizationRecordRepository;
use OCA\Filinq\Service\Sanitization\SanitizationStatus;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use PHPUnit\Framework\TestCase;

/**
 * A file is sanitized when a record names it as the derivative; the runs on
 * a source carry counts only.
 */
class SanitizationStatusTest extends TestCase {

	/**
	 * The status over the given records, for a user who can read files 10 and 11.
	 *
	 * @param list<array<string, mixed>> $records The records.
	 *
	 * @return SanitizationStatus
	 */
	private function statusOver(array $records): SanitizationStatus {
		$folder = $this->createMock(Folder::class);
		$folder->method('getById')->willReturnCallback(fn (int $id): array => in_array($id, [10, 11], true) ? [$this->createMock(File::class)] : []);
		$root = $this->createMock(IRootFolder::class);
		$root->method('getUserFolder')->willReturn($folder);
		$repo = $this->createMock(SanitizationRecordRepository::class);
		$repo->method('forSourceFile')->willReturnCallback(fn (int $id): array => array_values(array_filter($records, fn (array $r): bool => $r['fileId'] === $id)));
		$repo->method('forSanitizedFile')->willReturnCallback(fn (int $id): array => array_values(array_filter($records, fn (array $r): bool => $r['sanitizedFileId'] === $id)));

		return new SanitizationStatus(rootFolder: $root, records: $repo);

	}//end statusOver()

	/**
	 * The derivative is sanitized, the source is not, and the source lists its run.
	 *
	 * @return void
	 */
	public function testTheSignalFollowsTheDerivative(): void {
		$status = $this->statusOver(
			[['fileId' => 10, 'sanitizedFileId' => 11, 'trigger' => 'manual', 'engine' => 'OfficeDocumentSanitizer', 'report' => ['commentsRemoved' => 4], 'sanitizedAt' => '2026-09-30T10:00:00Z', 'sanitizedBy' => 'alice']]
		);

		$source = $status->forFile(fileId: 10, userId: 'alice');
		$this->assertFalse($source['sanitized']);
		$this->assertSame(11, $source['runs'][0]['sanitizedFileId']);
		$this->assertSame(4, $source['runs'][0]['report']['commentsRemoved']);
		$this->assertTrue($status->forFile(fileId: 11, userId: 'alice')['sanitized']);
		$this->assertSame([], $status->forFile(fileId: 11, userId: 'alice')['runs']);

	}//end testTheSignalFollowsTheDerivative()

	/**
	 * A file the user cannot read is 404.
	 *
	 * @return void
	 */
	public function testAnUnreadableFileIsNotFound(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionCode(404);
		$this->statusOver([])->forFile(fileId: 99, userId: 'alice');

	}//end testAnUnreadableFileIsNotFound()
}//end class
