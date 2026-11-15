<?php

/**
 * Golden tests for the write refusal and the acting person of FinalDocumentService
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\FinalDocumentRepository;
use OCA\Filinq\Service\FinalDocumentService;
use OCP\Files\IRootFolder;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Pins which writes are refused (and which are not) and who is recorded as the acting person.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction Development Team <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class FinalDocumentServiceGuardGoldenTest extends TestCase {

	private const UNREACHABLE = 'Filinq could not check whether this document is final, because the document '
		. 'register is unreachable. The document is left as it is.';

	/**
	 * The service over a repository answering `$records` and a session holding `$user`.
	 *
	 * @param array<int, array<string, mixed>>|null $records The records, or null for an unreachable register.
	 * @param IUser|null                            $user    The session user.
	 *
	 * @return FinalDocumentService
	 */
	private function service(?array $records = [], ?IUser $user = null): FinalDocumentService {
		$repository = $this->createMock(FinalDocumentRepository::class);
		$repository->method('findForFile')->willReturn($records);

		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);

		return new FinalDocumentService(
			$repository,
			$this->createMock(IRootFolder::class),
			$session,
			$this->createMock(LoggerInterface::class)
		);

	}//end service()

	/**
	 * A session user.
	 *
	 * @param string $uid         The user id.
	 * @param string $displayName The display name.
	 *
	 * @return IUser
	 */
	private function user(string $uid, string $displayName): IUser {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn($uid);
		$user->method('getDisplayName')->willReturn($displayName);

		return $user;

	}//end user()

	/**
	 * A stored final record about the current content.
	 *
	 * @param array<string, mixed> $overrides Fields to override.
	 *
	 * @return array<string, mixed>
	 */
	private function record(array $overrides = []): array {
		return array_merge(
			[
				'versionLabel' => '',
				'documentName' => 'Besluit.docx',
				'status' => 'final',
				'finalisedBy' => 'anna',
				'finalisedByName' => 'Anna de Boer',
				'finalisedAt' => '2026-09-03T14:05:00+00:00',
			],
			$overrides
		);

	}//end record()

	/**
	 * No file id, an empty register, versioned records and non-final records are not refused.
	 *
	 * @return void
	 */
	public function testWritesThatAreAllowed(): void {
		$this->assertNull($this->service(null)->refusalFor(0));
		$this->assertNull($this->service(null)->refusalFor(-3, 'edit this document'));
		$this->assertNull($this->service([])->refusalFor(4711));

		$notCurrent = [
			$this->record(['versionLabel' => '1756900000']),
			$this->record(['status' => 'draft']),
			$this->record(['status' => '']),
		];
		$this->assertNull($this->service($notCurrent)->refusalFor(4711));

	}//end testWritesThatAreAllowed()

	/**
	 * An unreachable register and a final current record refuse; the first final current record speaks.
	 *
	 * @return void
	 */
	public function testWritesThatAreRefused(): void {
		$this->assertSame(self::UNREACHABLE, $this->service(null)->refusalFor(4711));

		$first = $this->record(['finalisedByName' => 'Bert', 'status' => 'FINAL']);
		$second = $this->record(['finalisedByName' => 'Carla']);
		$records = [$this->record(['versionLabel' => '1', 'finalisedByName' => 'Old']), $this->record(['status' => 'draft']), $first, $second];

		$expected = $this->service([$first])->refusalFor(4711, 'edit this document');
		$this->assertIsString($expected);
		$this->assertStringContainsString('Bert', $expected);
		$this->assertSame($expected, $this->service($records)->refusalFor(4711, 'edit this document'));

		$unlabelled = $this->record(['finalisedByName' => 'Dirk']);
		unset($unlabelled['versionLabel']);
		$this->assertStringContainsString('Dirk', (string) $this->service([$unlabelled])->refusalFor(4711));

	}//end testWritesThatAreRefused()

	/**
	 * The named actor wins; a missing name comes from the session only when it is the same person.
	 *
	 * @return void
	 */
	public function testTheActingPersonIsResolved(): void {
		$anna = $this->user('anna', 'Anna de Boer');

		$this->assertSame(['id' => 'bob', 'name' => 'Bob B'], $this->service([], $anna)->resolveActor('bob', 'Bob B'));
		$this->assertSame(['id' => 'bob', 'name' => 'bob'], $this->service([], $anna)->resolveActor('bob', null));
		$this->assertSame(['id' => 'anna', 'name' => 'Anna de Boer'], $this->service([], $anna)->resolveActor('anna', ''));
		$this->assertSame(['id' => 'anna', 'name' => 'Anna de Boer'], $this->service([], $anna)->resolveActor(null, null));
		$this->assertSame(['id' => 'anna', 'name' => 'Zelf'], $this->service([], $anna)->resolveActor('', 'Zelf'));
		$this->assertSame(['id' => 'bob', 'name' => 'bob'], $this->service([], null)->resolveActor('bob', null));
		$this->assertSame(['id' => 'cas', 'name' => 'cas'], $this->service([], $this->user('cas', ''))->resolveActor(null, null));

	}//end testTheActingPersonIsResolved()

	/**
	 * Without a named actor and without a session user, nobody can be recorded.
	 *
	 * @return void
	 */
	public function testNoActingPersonIsRefused(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Making a document final needs a named person, and there is no acting user.');

		$this->service([], null)->resolveActor('', 'Naam');

	}//end testNoActingPersonIsRefused()

	/**
	 * A session user with an empty id is no acting person either.
	 *
	 * @return void
	 */
	public function testAnEmptySessionIdIsRefused(): void {
		$this->expectException(RuntimeException::class);

		$this->service([], $this->user('', 'Leeg'))->resolveActor(null, null);

	}//end testAnEmptySessionIdIsRefused()
}//end class
