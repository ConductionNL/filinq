<?php

/**
 * Unit tests for EditorSessionProbe
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Editing
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Editing;

use OCA\Filinq\Service\Editing\EditorDeliveryRegistry;
use OCA\Filinq\Service\Editing\EditorSessionProbe;
use OCP\Files\Lock\ILock;
use OCP\Files\Lock\ILockManager;
use OCP\Files\Lock\NoLockProviderException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * An editor session is read from the app locks on a file, never taken.
 *
 * @covers \OCA\Filinq\Service\Editing\EditorSessionProbe
 * @uses   \OCA\Filinq\Service\Editing\EditorDeliveryRegistry
 *
 * @spec openspec/changes/edit-through-the-open-editor/specs/edit-through-the-open-editor/spec.md#requirement-the-editor-must-remain-the-only-writer-while-it-is-open
 */
class EditorSessionProbeTest extends TestCase {

	/**
	 * The lock manager.
	 *
	 * @var ILockManager&MockObject
	 */
	private ILockManager&MockObject $locks;

	/**
	 * The logger.
	 *
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface&MockObject $logger;

	/**
	 * A lock manager that is only read.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->locks = $this->createMock(ILockManager::class);
		$this->locks->expects($this->never())->method('lock');
		$this->locks->expects($this->never())->method('unlock');
		$this->locks->expects($this->never())->method('runInScope');
		$this->logger = $this->createMock(LoggerInterface::class);
	}//end setUp()

	/**
	 * The probe over a registry declaring one editor.
	 *
	 * @return EditorSessionProbe The probe.
	 */
	private function probe(): EditorSessionProbe {
		$path = (string)tempnam(sys_get_temp_dir(), 'editors');
		file_put_contents($path, '{"editors": {"declared-editor": {"suite": "Declared Suite", "adapter": "connector"}}}');
		$registry = new EditorDeliveryRegistry(path: $path);
		$registry->forLockOwner(owner: 'warm');
		unlink($path);

		return new EditorSessionProbe(lockManager: $this->locks, registry: $registry, logger: $this->logger);
	}//end probe()

	/**
	 * A lock.
	 *
	 * @param string $owner The owner.
	 * @param int    $type  The lock type.
	 *
	 * @return ILock The lock.
	 */
	private function lock(string $owner, int $type=ILock::TYPE_APP): ILock {
		$lock = $this->createMock(ILock::class);
		$lock->method('getOwner')->willReturn($owner);
		$lock->method('getType')->willReturn($type);

		return $lock;
	}//end lock()

	/**
	 * A declared editor's app lock names its suite and adapter.
	 *
	 * @return void
	 */
	public function testADeclaredEditorIsAttachedWithItsAdapter(): void {
		$this->locks->expects($this->once())->method('getLocks')->with(42)->willReturn([$this->lock(owner: ' declared-editor ')]);

		self::assertSame(
			['owner' => 'declared-editor', 'suite' => 'Declared Suite', 'adapter' => 'connector'],
			$this->probe()->attachedTo(fileId: 42)
		);
	}//end testADeclaredEditorIsAttachedWithItsAdapter()

	/**
	 * An undeclared app lock is still an editor, named by its owner and with no adapter.
	 *
	 * @return void
	 */
	public function testAnUndeclaredEditorIsAttachedWithoutAnAdapter(): void {
		$this->locks->method('getLocks')->willReturn([$this->lock(owner: 'other-editor')]);

		self::assertSame(['owner' => 'other-editor', 'suite' => 'other-editor', 'adapter' => null], $this->probe()->attachedTo(fileId: 7));
	}//end testAnUndeclaredEditorIsAttachedWithoutAnAdapter()

	/**
	 * Filinq's own lock, user and token locks and blank owners are not an editor session.
	 *
	 * @return void
	 */
	public function testOwnUserAndTokenLocksAreNotAnEditor(): void {
		$this->locks->method('getLocks')->willReturn(
			[
				$this->lock(owner: 'FILINQ'),
				$this->lock(owner: 'alice', type: ILock::TYPE_USER),
				$this->lock(owner: 'declared-editor', type: ILock::TYPE_TOKEN),
				$this->lock(owner: '  '),
			]
		);

		self::assertNull($this->probe()->attachedTo(fileId: 7));
	}//end testOwnUserAndTokenLocksAreNotAnEditor()

	/**
	 * The first editor lock after skipped ones wins.
	 *
	 * @return void
	 */
	public function testTheFirstEditorLockAfterSkippedOnesIsUsed(): void {
		$this->locks->method('getLocks')->willReturn([$this->lock(owner: 'filinq'), $this->lock(owner: 'declared-editor'), $this->lock(owner: 'other')]);

		self::assertSame('Declared Suite', $this->probe()->attachedTo(fileId: 7)['suite']);
	}//end testTheFirstEditorLockAfterSkippedOnesIsUsed()

	/**
	 * No lock provider means nothing is detected, silently.
	 *
	 * @return void
	 */
	public function testNoLockProviderDetectsNothing(): void {
		$this->locks->method('getLocks')->willThrowException(new NoLockProviderException());
		$this->logger->expects($this->never())->method('warning');

		self::assertNull($this->probe()->attachedTo(fileId: 7));
	}//end testNoLockProviderDetectsNothing()

	/**
	 * A failing lock read is logged and detects nothing.
	 *
	 * @return void
	 */
	public function testAFailingLockReadIsLogged(): void {
		$this->locks->method('getLocks')->willThrowException(new RuntimeException('db gone'));
		$this->logger->expects($this->once())->method('warning')
			->with($this->stringContains('could not read the locks'), ['fileId' => 9, 'exception' => 'db gone']);

		self::assertNull($this->probe()->attachedTo(fileId: 9));
	}//end testAFailingLockReadIsLogged()
}//end class
