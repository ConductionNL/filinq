<?php

/**
 * A download of a document filinq owns lands in that document's trail.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Audit
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Audit;

use OCA\Filinq\Service\Audit\DocumentActionAudit;
use OCA\Filinq\Service\Audit\DocumentDownloadLog;
use OCA\Filinq\Service\DocumentDownloadRecorder;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Tests for DocumentDownloadLog.
 *
 * @covers \OCA\Filinq\Service\Audit\DocumentDownloadLog
 * @uses   \OCA\Filinq\Service\DocumentDownloadRecorder
 *
 * @spec openspec/changes/archive/2026-11-26-documents-in-and-out-of-the-building/specs/filinq-notifications/spec.md#requirement-a-download-is-recorded-with-its-route-and-its-identity-req-dio-06
 */
class DocumentDownloadLogTest extends TestCase {

	/**
	 * A session for one user, or none.
	 *
	 * @param string|null $uid The user, or null for no session.
	 *
	 * @return IUserSession The session.
	 */
	private function session(?string $uid): IUserSession {
		$session = $this->createMock(IUserSession::class);
		$user = null;
		if ($uid !== null) {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn($uid);
		}

		$session->method('getUser')->willReturn($user);
		return $session;
	}//end session()

	/**
	 * A handler's download is one `downloaded` entry naming file, version, route and handler.
	 *
	 * @return void
	 */
	public function testAHandlersDownloadIsOneTrailEntry(): void {
		$audit = $this->createMock(DocumentActionAudit::class);
		$audit->expects($this->once())->method('record')->with(
			42,
			'downloaded',
			$this->callback(
				static fn (array $context): bool => $context['fileId'] === 42
					&& $context['version'] === '1700000000'
					&& $context['route'] === 'ui'
					&& $context['identity'] === 'alice'
					&& strtotime((string) $context['downloadedAt']) !== false
			)
		)->willReturn(true);

		$log = new DocumentDownloadLog(new DocumentDownloadRecorder(), $audit, $this->session('alice'), $this->createMock(LoggerInterface::class));

		$this->assertTrue($log->downloaded(fileId: 42, version: '1700000000', route: DocumentDownloadLog::ROUTE_UI));
	}//end testAHandlersDownloadIsOneTrailEntry()

	/**
	 * Without a session the entry names the link, never a person.
	 *
	 * @return void
	 */
	public function testWithoutASessionTheLinkIsNamed(): void {
		$audit = $this->createMock(DocumentActionAudit::class);
		$audit->expects($this->once())->method('record')->with(
			42,
			'downloaded',
			$this->callback(static fn (array $context): bool => $context['identity'] === 'link:abc' && $context['route'] === 'public-link')
		)->willReturn(true);

		$log = new DocumentDownloadLog(new DocumentDownloadRecorder(), $audit, $this->session(null), $this->createMock(LoggerInterface::class));

		$this->assertTrue($log->downloaded(fileId: 42, version: '', route: 'public-link', linkId: 'abc'));
	}//end testWithoutASessionTheLinkIsNamed()

	/**
	 * A failing write is logged and answered false; it never escapes to the download.
	 *
	 * @return void
	 */
	public function testAFailedWriteDoesNotFailTheDownload(): void {
		$audit = $this->createMock(DocumentActionAudit::class);
		$audit->method('record')->willThrowException(new RuntimeException('trail down'));
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('warning');

		$log = new DocumentDownloadLog(new DocumentDownloadRecorder(), $audit, $this->session('alice'), $logger);

		$this->assertFalse($log->downloaded(fileId: 42, version: '', route: DocumentDownloadLog::ROUTE_UI));
	}//end testAFailedWriteDoesNotFailTheDownload()

	/**
	 * No file, no entry.
	 *
	 * @return void
	 */
	public function testNoFileWritesNothing(): void {
		$audit = $this->createMock(DocumentActionAudit::class);
		$audit->expects($this->never())->method('record');

		$log = new DocumentDownloadLog(new DocumentDownloadRecorder(), $audit, $this->session('alice'), $this->createMock(LoggerInterface::class));

		$this->assertFalse($log->downloaded(fileId: 0, version: '', route: DocumentDownloadLog::ROUTE_UI));
	}//end testNoFileWritesNothing()
}//end class
