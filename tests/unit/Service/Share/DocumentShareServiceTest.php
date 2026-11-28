<?php

/**
 * Sharing one document by link: the expiry rules, the password rule, the
 * label confirmation, Nextcloud's refusal, the record and its failure, and
 * taking a link back (share-document-links).
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-11-25-share-document-links/tasks.md#task-2.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Share;

use DateTime;
use DateTimeImmutable;
use InvalidArgumentException;
use OCA\Filinq\Service\CaseArchiveService;
use OCA\Filinq\Service\CaseArchiveWriter;
use OCA\Filinq\Service\ConfidentialityLabel;
use OCA\Filinq\Service\ConfidentialityLabelService;
use OCA\Filinq\Service\ReadableFileResolver;
use OCA\Filinq\Service\Share\DocumentShareService;
use OCA\Filinq\Service\Share\DocumentShareStore;
use OCA\Filinq\Service\Share\LinkShareGateway;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\File;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * A link share gateway that keeps its shares in memory.
 */
class InMemoryLinkShares implements LinkShareGateway {

	/**
	 * The shares made, by id.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	public array $shares = [];

	/**
	 * Nextcloud's rules.
	 *
	 * @var array{maxDays: int|null, passwordRequired: bool}
	 */
	public array $policy = ['maxDays' => null, 'passwordRequired' => false];

	/**
	 * A reason to refuse every share with, or null.
	 *
	 * @var string|null
	 */
	public ?string $refuse = null;

	/**
	 * Files the user may not share.
	 *
	 * @var array<int, int>
	 */
	public array $unshareable = [];

	/**
	 * Make a link.
	 *
	 * @param int               $fileId   The file.
	 * @param string            $uid      The sharer.
	 * @param DateTimeImmutable $expires  The expiry.
	 * @param string|null       $password The password.
	 * @param string            $note     The note.
	 *
	 * @return array{id: string, url: string}
	 */
	public function createLink(int $fileId, string $uid, DateTimeImmutable $expires, ?string $password, string $note): array {
		if ($this->refuse !== null) {
			throw new RuntimeException($this->refuse, 403);
		}

		$id = (string) (count($this->shares) + 101);
		$this->shares[$id] = ['fileId' => $fileId, 'uid' => $uid, 'expires' => $expires->format('Y-m-d'), 'password' => $password, 'note' => $note];

		return ['id' => $id, 'url' => 'https://cloud.example/s/token' . $id];
	}

	/**
	 * Remove a link.
	 *
	 * @param string $shareId The share.
	 *
	 * @return void
	 */
	public function delete(string $shareId): void {
		unset($this->shares[$shareId]);
	}

	/**
	 * Whether a link exists.
	 *
	 * @param string $shareId The share.
	 *
	 * @return bool
	 */
	public function exists(string $shareId): bool {
		return isset($this->shares[$shareId]);
	}

	/**
	 * Nextcloud's rules.
	 *
	 * @return array{maxDays: int|null, passwordRequired: bool}
	 */
	public function linkPolicy(): array {
		return $this->policy;
	}

	/**
	 * Whether the user may share the file.
	 *
	 * @param int    $fileId The file.
	 * @param string $uid    The sharer.
	 *
	 * @return bool
	 */
	public function mayShare(int $fileId, string $uid): bool {
		return in_array($fileId, $this->unshareable, true) === false;
	}
}

/**
 * @covers \OCA\Filinq\Service\Share\DocumentShareService
 */
class DocumentShareServiceTest extends TestCase {

	private InMemoryLinkShares $links;

	private DocumentShareStore&MockObject $store;

	private ReadableFileResolver&MockObject $files;

	private ConfidentialityLabelService&MockObject $labels;

	private IGroupManager&MockObject $groups;

	private CaseArchiveService&MockObject $archives;

	private CaseArchiveWriter&MockObject $archiveFiles;

	private ?string $uid = 'clara';

	/**
	 * Records saved, in order.
	 *
	 * @var array<int, array{0: array<string, mixed>, 1: string|null}>
	 */
	private array $saved = [];

	protected function setUp(): void {
		$this->links = new InMemoryLinkShares();
		$this->store = $this->createMock(DocumentShareStore::class);
		$this->store->method('save')->willReturnCallback(
			function (array $record, ?string $uuid = null): array {
				$this->saved[] = [$record, $uuid];
				return $record + ['uuid' => ($uuid ?? 'share-1')];
			}
		);
		$this->files  = $this->createMock(ReadableFileResolver::class);
		$this->labels = $this->createMock(ConfidentialityLabelService::class);
		$this->groups = $this->createMock(IGroupManager::class);
		$this->archives = $this->createMock(CaseArchiveService::class);
		$this->archiveFiles = $this->createMock(CaseArchiveWriter::class);
	}

	private function service(): DocumentShareService {
		$session = $this->createMock(IUserSession::class);
		$user = null;
		if ($this->uid !== null) {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn($this->uid);
		}

		$session->method('getUser')->willReturn($user);
		$clock = $this->createMock(ITimeFactory::class);
		$clock->method('getDateTime')->willReturnCallback(static fn (): DateTime => new DateTime('2026-11-13T15:30:00+00:00'));

		return new DocumentShareService($this->links, $this->store, $this->files, $this->labels, $session, $this->groups, $clock, $this->createMock(LoggerInterface::class), $this->archives, $this->archiveFiles);
	}

	/**
	 * Let the user read exactly these files.
	 */
	private function readable(int ...$fileIds): void {
		$this->files->method('node')->willReturnCallback(
			fn (int $fileId): ?File => (in_array($fileId, $fileIds, true) ? $this->createMock(File::class) : null)
		);
	}

	private function document(): void {
		$file = $this->createMock(File::class);
		$file->method('getName')->willReturn('besluit-2026-114.pdf');
		$file->method('getEtag')->willReturn('etag-7');
		$this->files->method('node')->with(42)->willReturn($file);
	}

	public function testOneDocumentIsSharedForFourteenDaysAndRecordedWithoutItsLink(): void {
		$this->document();

		$result = $this->service()->shareDocument(42, '', null, ' Advies bezwaarcommissie ', false);

		$this->assertSame(['101' => ['fileId' => 42, 'uid' => 'clara', 'expires' => '2026-11-27', 'password' => null, 'note' => 'Advies bezwaarcommissie']], $this->links->shares);
		$this->assertSame('https://cloud.example/s/token101', $result['url']);
		$this->assertSame(['2026-11-27', false], [$result['expiresAt'], $result['expiryShortened']]);
		$this->assertSame(
			[
				'kind' => 'document',
				'files' => [['fileId' => 42, 'name' => 'besluit-2026-114.pdf', 'version' => 'etag-7', 'label' => '']],
				'shareId' => '101',
				'sharedBy' => 'clara',
				'sharedAt' => '2026-11-13T15:30:00+00:00',
				'expiresAt' => '2026-11-27',
				'note' => 'Advies bezwaarcommissie',
				'labelConfirmed' => false,
				'status' => 'active',
			],
			$this->saved[0][0]
		);
		$this->assertStringNotContainsString('token', json_encode($this->saved[0][0]));
	}

	public function testNextcloudsMaximumShortensTheExpiry(): void {
		$this->document();
		$this->links->policy = ['maxDays' => 7, 'passwordRequired' => false];

		$result = $this->service()->shareDocument(42, '2026-12-13', 'geheim', '', false);

		$this->assertSame(['2026-11-20', true], [$result['expiresAt'], $result['expiryShortened']]);
		$this->assertSame('geheim', $this->links->shares['101']['password']);
	}

	public function testAnExpiryInsideTheMaximumIsKept(): void {
		$this->document();
		$this->links->policy = ['maxDays' => 30, 'passwordRequired' => false];

		$this->assertSame(['2026-11-20', false], array_values(array_slice($this->service()->shareDocument(42, '2026-11-20', null, '', false), 2)));
	}

	/**
	 * @return array<string, array{0: string}>
	 */
	public static function badExpiries(): array {
		return ['not a date' => ['next week'], 'impossible date' => ['2026-02-30'], 'today' => ['2026-11-13'], 'past' => ['2026-01-01']];
	}

	/**
	 * @dataProvider badExpiries
	 */
	public function testAnExpiryThatIsNoFutureDateIsRefused(string $expiresOn): void {
		$this->document();

		try {
			$this->service()->shareDocument(42, $expiresOn, null, '', false);
			$this->fail('The expiry ' . $expiresOn . ' must be refused');
		} catch (InvalidArgumentException $e) {
			$this->assertSame(400, $e->getCode());
		}

		$this->assertSame([], $this->links->shares);
		$this->assertSame([], $this->saved);
	}

	public function testAnEnforcedPasswordIsRequired(): void {
		$this->document();
		$this->links->policy = ['maxDays' => null, 'passwordRequired' => true];

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Nextcloud requires a password on every link');
		$this->service()->shareDocument(42, '', '  ', '', false);
	}

	public function testALabelledDocumentNeedsAConfirmation(): void {
		$this->document();
		$this->labels->method('getLabelForFile')->with(42)->willReturn(new ConfidentialityLabel('Confidential', 3));

		try {
			$this->service()->shareDocument(42, '', null, '', false);
			$this->fail('A labelled document must not be shared without a confirmation');
		} catch (InvalidArgumentException $e) {
			$this->assertSame(409, $e->getCode());
			$this->assertSame('This document is labelled "Confidential". Confirm before sharing it by link.', $e->getMessage());
		}

		$this->assertSame([], $this->links->shares);

		$this->service()->shareDocument(42, '', null, '', true);
		$this->assertSame('Confidential', $this->saved[0][0]['files'][0]['label']);
		$this->assertTrue($this->saved[0][0]['labelConfirmed']);
	}

	public function testALabelThatCannotBeReadRefusesTheShare(): void {
		$this->document();
		$this->labels->method('getLabelForFile')->willThrowException(new RuntimeException('files_confidential down'));

		try {
			$this->service()->shareDocument(42, '', null, '', true);
			$this->fail('An unreadable label must not be skipped');
		} catch (RuntimeException $e) {
			$this->assertSame(503, $e->getCode());
		}

		$this->assertSame([], $this->links->shares);
	}

	public function testNextcloudsRefusalLeavesNoShareAndNoRecord(): void {
		$this->document();
		$this->links->refuse = 'Sharing is not allowed';

		try {
			$this->service()->shareDocument(42, '', null, '', false);
			$this->fail('A refused share must surface');
		} catch (RuntimeException $e) {
			$this->assertSame(['Sharing is not allowed', 403], [$e->getMessage(), $e->getCode()]);
		}

		$this->assertSame([], $this->saved);
	}

	public function testAFileTheClerkCannotReadOrNoUserIsRefused(): void {
		$this->files->method('node')->willReturn(null);
		try {
			$this->service()->shareDocument(42, '', null, '', false);
			$this->fail('An unreadable file must be refused');
		} catch (RuntimeException $e) {
			$this->assertSame(404, $e->getCode());
		}

		$this->uid = null;
		$this->expectExceptionCode(401);
		$this->service()->shareDocument(42, '', null, '', false);
	}

	public function testARefusedRecordTakesTheLinkBackAgain(): void {
		$this->document();
		$store = $this->createMock(DocumentShareStore::class);
		$store->method('save')->willThrowException(new RuntimeException('OpenRegister down'));
		$this->store = $store;

		try {
			$this->service()->shareDocument(42, '', null, '', false);
			$this->fail('A link without a record must not stay open');
		} catch (RuntimeException $e) {
			$this->assertSame(500, $e->getCode());
			$this->assertSame('The share could not be recorded, so the link was taken back again', $e->getMessage());
		}

		$this->assertSame([], $this->links->shares);
	}

	private function active(string $sharedBy): void {
		$this->links->shares['101'] = ['fileId' => 42];
		$this->store->method('find')->with('share-1')->willReturn(['uuid' => 'share-1', 'sharedBy' => $sharedBy, 'shareId' => '101', 'status' => 'active']);
	}

	public function testTheSharerTakesALinkBack(): void {
		$this->active(sharedBy: 'clara');

		$record = $this->service()->revoke('share-1');

		$this->assertSame([], $this->links->shares);
		$this->assertSame(['revoked', 'clara', '2026-11-13T15:30:00+00:00'], [$record['status'], $record['revokedBy'], $record['revokedAt']]);
		$this->assertSame('share-1', $this->saved[0][1]);
	}

	public function testAnAdminTakesSomebodyElsesLinkBack(): void {
		$this->active(sharedBy: 'dirk');
		$this->groups->method('isAdmin')->with('clara')->willReturn(true);

		$this->assertSame('revoked', $this->service()->revoke('share-1')['status']);
	}

	public function testSomebodyElseCannotTakeALinkBack(): void {
		$this->active(sharedBy: 'dirk');
		$this->groups->method('isAdmin')->willReturn(false);

		try {
			$this->service()->revoke('share-1');
			$this->fail('Only the sharer or an admin may take a link back');
		} catch (RuntimeException $e) {
			$this->assertSame(403, $e->getCode());
		}

		$this->assertArrayHasKey('101', $this->links->shares, 'the link still opens');
		$this->assertSame([], $this->saved);
	}

	public function testAnUnknownOrClosedShareCannotBeTakenBack(): void {
		$this->store->method('find')->willReturnMap([['gone', null], ['old', ['uuid' => 'old', 'sharedBy' => 'clara', 'shareId' => '9', 'status' => 'expired']]]);

		foreach (['gone' => 404, 'old' => 409] as $uuid => $code) {
			try {
				$this->service()->revoke($uuid);
				$this->fail($uuid . ' must be refused');
			} catch (RuntimeException $e) {
				$this->assertSame($code, $e->getCode());
			}
		}

		$this->assertSame([], $this->saved);
	}
	/**
	 * The archive CaseArchiveService builds for files 7, 8 and 9.
	 *
	 * @return array<string, mixed> The build result.
	 */
	private function built(): array {
		return [
			'included' => [
				['fileId' => 7, 'name' => 'a.pdf', 'record' => '', 'size' => 10, 'version' => 'e7'],
				['fileId' => 8, 'name' => 'b.pdf', 'record' => '', 'size' => 20, 'version' => 'e8'],
			],
			'excluded' => [['fileId' => 9, 'name' => '', 'record' => '', 'reason' => 'permission', 'explanation' => 'x']],
			'bytes' => 30,
			'archive' => ['fileId' => 9001, 'name' => 'Bezwaar 2026-031.zip', 'path' => '/DocuDesk/Shared bundles/Bezwaar 2026-031.zip'],
			'job' => ['uuid' => 'job-1', 'fileId' => 9001],
		];
	}

	public function testSeveralDocumentsLeaveAsOneArchiveThroughOneLink(): void {
		$this->readable(7, 8);
		$this->labels->method('getLabelForFile')->willReturnCallback(
			static fn (int $fileId): ?ConfidentialityLabel => ($fileId === 8 ? new ConfidentialityLabel('Confidential', 3) : null)
		);
		$this->archives->expects($this->once())->method('buildFromFiles')
			->with([7, 8, 9], 'DocuDesk/Shared bundles', 'Bezwaar 2026-031')
			->willReturn($this->built());

		$result = $this->service()->share(['7', 8, '9', 7, 'x', -1], ' Bezwaar 2026-031 ', '2026-11-20', null, 'Bezwaarcommissie', true);

		$this->assertSame(['101' => ['fileId' => 9001, 'uid' => 'clara', 'expires' => '2026-11-20', 'password' => null, 'note' => 'Bezwaarcommissie']], $this->links->shares);
		$this->assertSame(
			[
				'kind' => 'bundle',
				'files' => [
					['fileId' => 7, 'name' => 'a.pdf', 'version' => 'e7', 'label' => ''],
					['fileId' => 8, 'name' => 'b.pdf', 'version' => 'e8', 'label' => 'Confidential'],
				],
				'shareId' => '101',
				'archiveJob' => 'job-1',
				'archiveFileId' => 9001,
				'sharedBy' => 'clara',
				'sharedAt' => '2026-11-13T15:30:00+00:00',
				'expiresAt' => '2026-11-20',
				'note' => 'Bezwaarcommissie',
				'labelConfirmed' => true,
				'status' => 'active',
			],
			$this->saved[0][0]
		);
		$this->assertSame('https://cloud.example/s/token101', $result['url']);
		$this->assertSame('permission', $result['manifest']['excluded'][0]['reason']);
	}

	public function testOneChosenDocumentIsSharedAsItselfAndNoneIsRefused(): void {
		$this->document();
		$this->archives->expects($this->never())->method('buildFromFiles');

		$this->assertSame('document', $this->service()->share([42, '42'], 'ignored', '', null, '', false)['share']['kind']);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionCode(400);
		$this->service()->share(['abc', 0], '', '', null, '', false);
	}

	public function testABundleNeedsANameAndAConfirmationForItsLabels(): void {
		$this->readable(7, 8);
		$this->labels->method('getLabelForFile')->willReturnCallback(
			static fn (int $fileId): ?ConfidentialityLabel => new ConfidentialityLabel($fileId === 7 ? 'Confidential' : 'Secret', 3)
		);
		$this->archives->expects($this->never())->method('buildFromFiles');

		foreach ([['  ', 400, 'A bundle needs a name'], ['Bundel', 409, 'This bundle holds documents labelled "Confidential", "Secret". Confirm before sharing it by link.']] as [$name, $code, $message]) {
			try {
				$this->service()->share([7, 8], $name, '', null, '', false);
				$this->fail('The bundle must be refused: ' . $message);
			} catch (InvalidArgumentException $e) {
				$this->assertSame([$code, $message], [$e->getCode(), $e->getMessage()]);
			}
		}

		$this->assertSame([[], []], [$this->links->shares, $this->saved]);
	}

	public function testAFileTheClerkMayNotShareKeepsTheWholeBundleIn(): void {
		$this->readable(7, 8);
		$this->links->unshareable = [8];
		$this->archives->expects($this->never())->method('buildFromFiles');
		$this->labels->expects($this->never())->method('getLabelForFile');

		try {
			$this->service()->share([7, 8], 'Bundel', '', null, '', true);
			$this->fail('A bundle holding a file the clerk may not share must be refused');
		} catch (RuntimeException $e) {
			$this->assertSame([403, 'Nextcloud does not let you share 1 of the chosen documents by link'], [$e->getCode(), $e->getMessage()]);
		}

		$this->assertSame([[], []], [$this->links->shares, $this->saved]);
	}

	public function testARefusedJobWriteStopsTheBundleShare(): void {
		$this->archives->method('buildFromFiles')->willThrowException(new RuntimeException('The archive job could not be recorded', 500));

		try {
			$this->service()->share([7, 8], 'Bundel', '', null, '', false);
			$this->fail('A refused job write must stop the share');
		} catch (RuntimeException $e) {
			$this->assertSame('The archive job could not be recorded', $e->getMessage());
		}

		$this->assertSame([[], []], [$this->links->shares, $this->saved]);
	}

	public function testARefusedLinkOrRecordDeletesTheArchiveAgain(): void {
		$this->archives->method('buildFromFiles')->willReturn($this->built());
		$this->archiveFiles->expects($this->exactly(2))->method('discardById')->with('clara', 9001)->willReturn(true);

		$this->links->refuse = 'Sharing is not allowed';
		try {
			$this->service()->share([7, 8], 'Bundel', '', null, '', false);
			$this->fail('Nextcloud refused the link');
		} catch (RuntimeException $e) {
			$this->assertSame([403, 'Sharing is not allowed'], [$e->getCode(), $e->getMessage()]);
		}

		$this->links->refuse = null;
		$store = $this->createMock(DocumentShareStore::class);
		$store->method('save')->willThrowException(new RuntimeException('OpenRegister refused the share record', 500));
		$this->store = $store;
		try {
			$this->service()->share([7, 8], 'Bundel', '', null, '', false);
			$this->fail('The record write failed');
		} catch (RuntimeException $e) {
			$this->assertSame('The share could not be recorded, so the link was taken back again', $e->getMessage());
		}

		$this->assertSame([], $this->links->shares, 'the link was taken back again');
	}

	public function testThePreflightNamesTheRulesTheLabelsAndWhatTheBundleHolds(): void {
		$this->readable(7, 8);
		$this->links->policy = ['maxDays' => 7, 'passwordRequired' => true];
		$this->labels->method('getLabelForFile')->willReturnCallback(
			static fn (int $fileId): ?ConfidentialityLabel => ($fileId === 8 ? new ConfidentialityLabel('Confidential', 3) : null)
		);
		$this->archives->method('ceiling')->willReturn(1000);
		$this->archives->expects($this->once())->method('preflightFiles')->with([7, 8], 1000)
			->willReturn(['files' => 2, 'bytes' => 1200, 'ceilingBytes' => 1000, 'exceedsCeiling' => true, 'included' => [], 'excluded' => []]);

		$preflight = $this->service()->preflight([7, 8]);

		$this->assertSame(
			[7, true, '2026-11-20', [['fileId' => 8, 'label' => 'Confidential']], true],
			[$preflight['maxDays'], $preflight['passwordRequired'], $preflight['defaultExpiresAt'], $preflight['labels'], $preflight['bundle']['exceedsCeiling']]
		);
		$this->assertNull($this->service()->preflight([8])['bundle'], 'one document is no bundle');
	}

	public function testNoLabelIsReadForAFileTheUserCannotOpen(): void {
		$this->readable(7);
		$read = [];
		$this->labels->method('getLabelForFile')->willReturnCallback(
			static function (int $fileId) use (&$read): ?ConfidentialityLabel {
				$read[] = $fileId;
				return new ConfidentialityLabel('Secret', 3);
			}
		);
		$this->archives->method('ceiling')->willReturn(1000);
		$this->archives->method('preflightFiles')->willReturn(['files' => 1, 'bytes' => 1, 'ceilingBytes' => 1000, 'exceedsCeiling' => false, 'included' => [], 'excluded' => []]);
		$this->archives->expects($this->never())->method('buildFromFiles');

		$this->assertSame([['fileId' => 7, 'label' => 'Secret']], $this->service()->preflight([7, 8])['labels']);

		try {
			$this->service()->share([7, 8], 'Bundel', '', null, '', false);
			$this->fail('The readable labelled document still needs a confirmation');
		} catch (InvalidArgumentException $e) {
			$this->assertSame([409, 'This bundle holds documents labelled "Secret". Confirm before sharing it by link.'], [$e->getCode(), $e->getMessage()]);
		}

		$this->assertSame([7, 7], $read, 'the label of file 8, which the user cannot open, is never read');
	}

	public function testTakingABundleBackDeletesItsArchive(): void {
		$this->links->shares['101'] = ['fileId' => 9001];
		$this->store->method('find')->willReturn(['uuid' => 'share-1', 'kind' => 'bundle', 'sharedBy' => 'clara', 'shareId' => '101', 'archiveFileId' => 9001, 'status' => 'active']);
		$this->archiveFiles->expects($this->once())->method('discardById')->with('clara', 9001)->willReturn(true);

		$this->assertSame('revoked', $this->service()->revoke('share-1')['status']);
		$this->assertSame([], $this->links->shares);
	}

	public function testTheJobClosesWhatRanOutAndWhatWasRemovedInFiles(): void {
		$this->links->shares['202'] = ['fileId' => 1];
		$this->links->shares['203'] = ['fileId' => 2];
		$this->store->method('active')->willReturn(
			[
				['uuid' => 'ran-out', 'kind' => 'bundle', 'sharedBy' => 'dirk', 'shareId' => '201', 'archiveFileId' => 77, 'expiresAt' => '2026-11-12', 'status' => 'active'],
				['uuid' => 'last-day', 'kind' => 'document', 'sharedBy' => 'dirk', 'shareId' => '202', 'expiresAt' => '2026-11-13', 'status' => 'active'],
				['uuid' => 'removed', 'kind' => 'document', 'sharedBy' => 'dirk', 'shareId' => '204', 'expiresAt' => '2026-11-30', 'status' => 'active'],
				['uuid' => 'open', 'kind' => 'document', 'sharedBy' => 'dirk', 'shareId' => '203', 'expiresAt' => '2026-11-30', 'status' => 'active'],
			]
		);
		$this->archiveFiles->expects($this->once())->method('discardById')->with('dirk', 77)->willReturn(true);

		$this->assertSame(['expired' => 1, 'revoked' => 1, 'failed' => 0], $this->service()->closeLapsed());

		$this->assertSame(
			[['ran-out', 'expired', null], ['removed', 'revoked', 'removed in Files']],
			array_map(static fn (array $save): array => [$save[1], $save[0]['status'], ($save[0]['revokedBy'] ?? null)], $this->saved)
		);
		$this->assertSame('2026-11-13T15:30:00+00:00', $this->saved[1][0]['revokedAt']);
	}

	public function testOneRecordThatCannotBeClosedDoesNotStopTheOthers(): void {
		$store = $this->createMock(DocumentShareStore::class);
		$store->method('active')->willReturn(
			[
				['uuid' => 'a', 'kind' => 'document', 'shareId' => '1', 'expiresAt' => '2026-01-01', 'status' => 'active'],
				['uuid' => 'b', 'kind' => 'document', 'shareId' => '2', 'expiresAt' => '2026-01-01', 'status' => 'active'],
			]
		);
		$store->method('save')->willReturnCallback(
			static function (array $record, ?string $uuid = null): array {
				if ($uuid === 'a') {
					throw new RuntimeException('refused', 500);
				}

				return $record;
			}
		);
		$this->store = $store;

		$this->assertSame(['expired' => 1, 'revoked' => 0, 'failed' => 1], $this->service()->closeLapsed());
	}
}
