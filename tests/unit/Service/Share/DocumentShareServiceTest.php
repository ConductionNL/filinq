<?php

/**
 * Sharing one document by link: the expiry rules, the password rule, the
 * label confirmation, Nextcloud's refusal, the record and its failure, and
 * taking a link back (share-document-links).
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/share-document-links/tasks.md#task-2.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Share;

use DateTime;
use DateTimeImmutable;
use InvalidArgumentException;
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

		return new DocumentShareService($this->links, $this->store, $this->files, $this->labels, $session, $this->groups, $clock, $this->createMock(LoggerInterface::class));
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
}
