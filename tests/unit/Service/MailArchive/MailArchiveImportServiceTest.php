<?php

/**
 * Unit tests for MailArchiveImportService
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\MailArchive
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\MailArchive;

require_once __DIR__ . '/MailArchiveImportDoubles.php';
require_once __DIR__ . '/../EmailIngestion/EmailIngestionDoubles.php';

use InvalidArgumentException;
use OCA\Filinq\Event\IntakeDocumentReceivedEvent;
use OCA\Filinq\Kernel\Contracts\IntakeRepositoryInterface;
use OCA\Filinq\Kernel\Contracts\IntakeServiceInterface;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\EmailIngestion\EmailDocumentRepository;
use OCA\Filinq\Service\EmailIngestion\EmailFiling;
use OCA\Filinq\Service\EmailIngestion\EmailMessageReader;
use OCA\Filinq\Service\EmailIngestion\EmailNotFiled;
use OCA\Filinq\Service\EmailIngestion\EmailThreadHeaders;
use OCA\Filinq\Service\IntakeAuthorizationGate;
use OCA\Filinq\Service\MailArchive\MailArchiveImportRepository;
use OCA\Filinq\Service\MailArchive\MailArchiveImportService;
use OCA\Filinq\Service\MailArchive\MailArchiveMessageFiler;
use OCA\Filinq\Service\MailArchive\MailArchiveSettings;
use OCA\Filinq\Service\MailArchive\MailArchiveSplitter;
use OCA\Filinq\Service\MailArchive\MailArchiveWorkspace;
use OCA\Filinq\Service\MailArchive\ReadpstCommand;
use OCA\Filinq\Service\Support\EmailHandling;
use OCA\Filinq\Tests\Unit\Service\EmailIngestion\EmailObjectStore;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\File;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ZipArchive;

/**
 * Start, map, drain in units from a cursor, file or receive, and never twice.
 *
 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#3-1
 */
class MailArchiveImportServiceTest extends TestCase {

	private const USER = 'archivaris';

	private const ARCHIVE_ID = 9001;

	private const DOSSIER = 'dossier-brug-zuid';

	/**
	 * The scratch directory.
	 *
	 * @var string
	 */
	private string $dir;

	/**
	 * The archive the user picked.
	 *
	 * @var string
	 */
	private string $archivePath = '';

	/**
	 * The archive's name.
	 *
	 * @var string
	 */
	private string $archiveName = '';

	/**
	 * The import rows.
	 *
	 * @var MailArchiveObjectStore
	 */
	private MailArchiveObjectStore $imports;

	/**
	 * The emailDocument rows.
	 *
	 * @var EmailObjectStore
	 */
	private EmailObjectStore $emails;

	/**
	 * The messages received into the intake inbox, by source reference.
	 *
	 * @var array<string, IntakeDocumentReceivedEvent>
	 */
	private array $inbox = [];

	/**
	 * Files written beside the archive: id => {folder, name, raw, deleted}.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $staged = [];

	/**
	 * Dossier each staged file was filed to, by file id.
	 *
	 * @var array<int, string>
	 */
	private array $filedTo = [];

	/**
	 * Whether the user can read the archive.
	 *
	 * @var boolean
	 */
	private bool $canRead = true;

	/**
	 * Whether the archive still exists for the job.
	 *
	 * @var boolean
	 */
	private bool $archiveExists = true;

	/**
	 * The per-run limit.
	 *
	 * @var string
	 */
	private string $limit = '200';

	/**
	 * The PST converter.
	 *
	 * @var ReadpstCommand&MockObject
	 */
	private ReadpstCommand $readpst;

	/**
	 * Build the world.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->dir = sys_get_temp_dir() . '/filinq-mail-import-' . bin2hex(random_bytes(6));
		mkdir($this->dir);
		$this->imports = new MailArchiveObjectStore();
		$this->emails = new EmailObjectStore();
		$this->readpst = $this->createMock(ReadpstCommand::class);

	}//end setUp()

	/**
	 * Remove the scratch directory.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		foreach (glob($this->dir . '/*') ?: [] as $file) {
			@unlink($file);
		}

		@rmdir($this->dir);

	}//end tearDown()

	/**
	 * One RFC 5322 message.
	 *
	 * @param string $messageId The Message-ID, '' for none.
	 * @param string $subject   The subject.
	 * @param string $extra     Extra header lines.
	 *
	 * @return string The message.
	 */
	private static function eml(string $messageId, string $subject, string $extra = ''): string {
		$id = '';
		if ($messageId !== '') {
			$id = 'Message-ID: <' . $messageId . ">\r\n";
		}

		return "From: Jansen <jansen@demostad.nl>\r\nTo: team@demostad.nl\r\nSubject: " . $subject . "\r\n" . $id . $extra . "\r\nBody of " . $subject . "\r\n";

	}//end eml()

	/**
	 * An MBOX of five messages.
	 *
	 * @return void
	 */
	private function useMbox(): void {
		$this->archiveName = 'mailbox-jansen.mbox';
		$this->archivePath = $this->dir . '/' . $this->archiveName;
		$content = '';
		for ($i = 1; $i <= 5; $i++) {
			$content .= "From jansen@demostad.nl Mon Jan  1 00:00:00 2024\n" . str_replace("\r\n", "\n", self::eml(messageId: 'm' . $i . '@demostad.nl', subject: 'Bericht ' . $i)) . "\n";
		}

		file_put_contents($this->archivePath, $content);

	}//end useMbox()

	/**
	 * A ZIP with two mails in Projecten/Brug, three in Inbox and an Outlook .msg.
	 *
	 * @return void
	 */
	private function useZip(): void {
		$this->archiveName = 'archief-team.zip';
		$this->archivePath = $this->dir . '/' . $this->archiveName;
		$zip = new ZipArchive();
		$zip->open($this->archivePath, ZipArchive::CREATE);
		$zip->addFromString('Projecten/Brug/1.eml', self::eml(messageId: 'brug-1@demostad.nl', subject: 'Brug 1'));
		$zip->addFromString('Projecten/Brug/2.eml', self::eml(messageId: 'brug-2@demostad.nl', subject: 'Brug 2'));
		$zip->addFromString('Inbox/a.eml', self::eml(messageId: 'inbox-a@demostad.nl', subject: 'Inbox A'));
		$zip->addFromString('Inbox/b.eml', self::eml(messageId: '', subject: 'Inbox B zonder id'));
		$zip->addFromString('Inbox/c.eml', self::eml(messageId: 'inbox-c@demostad.nl', subject: 'Inbox C'));
		$zip->addFromString('Inbox/uitnodiging.msg', 'not a message');
		$zip->close();

	}//end useZip()

	/**
	 * A file mock for the archive.
	 *
	 * @return File The archive.
	 */
	private function archiveFile(): File {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(self::ARCHIVE_ID);
		$file->method('getName')->willReturn($this->archiveName);

		return $file;

	}//end archiveFile()

	/**
	 * The service, over the in-memory register and a recording workspace, inbox and filing.
	 *
	 * @return MailArchiveImportService The service.
	 */
	private function service(): MailArchiveImportService {
		$splitter = new MailArchiveSplitter(readpst: $this->readpst);
		$clock = $this->createMock(ITimeFactory::class);
		$clock->method('getTime')->willReturn(1784000000);
		$logger = $this->createMock(LoggerInterface::class);

		$importResolver = $this->createMock(DocumentObjectServiceResolver::class);
		$importResolver->method('resolve')->willReturn($this->imports);
		$emailResolver = $this->createMock(DocumentObjectServiceResolver::class);
		$emailResolver->method('resolve')->willReturn($this->emails);

		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(fn (string $app, string $key, string $default = ''): string => ($key === MailArchiveSettings::KEY_MESSAGES_PER_RUN ? $this->limit : $default));

		$gate = $this->createMock(IntakeAuthorizationGate::class);
		$gate->method('mayFileOnto')->willReturnCallback(static fn (string $register, string $schema, string $id): bool => $register === 'filinq' && $schema === 'dossier' && $id !== 'dossier-refused');

		$workspace = $this->workspace(splitter: $splitter);

		return new MailArchiveImportService(
			repository: new MailArchiveImportRepository(objectResolver: $importResolver),
			splitter: $splitter,
			workspace: $workspace,
			filer: new MailArchiveMessageFiler(
				headers: new EmailThreadHeaders(),
				handling: new EmailHandling(reader: $this->reader(), filing: $this->filing()),
				emailDocuments: new EmailDocumentRepository(objectResolver: $emailResolver),
				intake: $this->intakeRepository(),
				intakeService: $this->intakeService(),
				workspace: $workspace,
				clock: $clock,
				logger: $logger
			),
			settings: new MailArchiveSettings(appConfig: $config, readpst: $this->readpst),
			gate: $gate,
			clock: $clock,
			logger: $logger
		);

	}//end service()

	/**
	 * A workspace that serves the scratch archive and records the files written beside it.
	 *
	 * @param MailArchiveSplitter $splitter Converts a PST.
	 *
	 * @return MailArchiveWorkspace The workspace.
	 */
	private function workspace(MailArchiveSplitter $splitter): MailArchiveWorkspace {
		$workspace = $this->createMock(MailArchiveWorkspace::class);
		$workspace->method('userArchive')->willReturnCallback(fn (string $userId, int $fileId): ?File => ($this->canRead === true && $userId === self::USER && $fileId === self::ARCHIVE_ID ? $this->archiveFile() : null));
		$workspace->method('archive')->willReturnCallback(fn (int $fileId): ?File => ($this->archiveExists === true && $fileId === self::ARCHIVE_ID ? $this->archiveFile() : null));
		$workspace->method('localPath')->willReturnCallback(fn (): string => $this->archivePath);
		$workspace->method('source')->willReturnCallback(fn (File $archive, string $format, string $importKey): array => $splitter->prepare(path: $this->archivePath, format: $format, workDir: $this->dir));
		$workspace->method('stage')->willReturnCallback(
			function (File $archive, string $folder, string $name, string $raw): File {
				$id = (5000 + count($this->staged));
				$this->staged[$id] = ['folder' => $folder, 'name' => $name, 'raw' => $raw, 'deleted' => false];
				$file = $this->createMock(File::class);
				$file->method('getId')->willReturn($id);
				$file->method('getName')->willReturn($name);
				$file->method('getContent')->willReturnCallback(fn (): string => $this->staged[$id]['raw']);
				$file->method('delete')->willReturnCallback(function () use ($id): void {
					$this->staged[$id]['deleted'] = true;
				});

				return $file;
			}
		);

		return $workspace;

	}//end workspace()

	/**
	 * A reader that reads the subject and Message-ID, and refuses a message marked broken.
	 *
	 * @return EmailMessageReader The reader.
	 */
	private function reader(): EmailMessageReader {
		$reader = $this->createMock(EmailMessageReader::class);
		$reader->method('read')->willReturnCallback(
			static function (File $file): array {
				$raw = $file->getContent();
				if (str_contains($raw, 'X-Broken: yes') === true) {
					throw new EmailNotFiled('unparseable');
				}

				preg_match('/^Subject: (.*)$/m', $raw, $subject);
				$messageId = (new EmailThreadHeaders())->read(raw: $raw)['messageId'];

				return [
					'subject' => trim($subject[1] ?? ''),
					'fromAddress' => 'jansen@demostad.nl',
					'toAddresses' => ['team@demostad.nl'],
					'ccAddresses' => [],
					'messageId' => $messageId,
					'references' => [],
					'threadKey' => strtolower($messageId),
					'attachmentCount' => 0,
					'attachmentNames' => [],
				];
			}
		);

		return $reader;

	}//end reader()

	/**
	 * A filing that records the dossier and does not convert.
	 *
	 * @return EmailFiling The filing.
	 */
	private function filing(): EmailFiling {
		$filing = $this->createMock(EmailFiling::class);
		$filing->method('file')->willReturnCallback(
			function (File $email, string $dossierRef): File {
				$this->filedTo[(int) $email->getId()] = $dossierRef;

				return $email;
			}
		);
		$filing->method('convert')->willReturn('');

		return $filing;

	}//end filing()

	/**
	 * The inbox's duplicate check over what was received.
	 *
	 * @return IntakeRepositoryInterface The repository.
	 */
	private function intakeRepository(): IntakeRepositoryInterface {
		$repository = $this->createMock(IntakeRepositoryInterface::class);
		$repository->method('findBySourceRef')->willReturnCallback(fn (string $sourceRef): ?array => (isset($this->inbox[$sourceRef]) === true ? ['uuid' => 'intake-' . $sourceRef] : null));

		return $repository;

	}//end intakeRepository()

	/**
	 * An inbox that records each message by its source reference.
	 *
	 * @return IntakeServiceInterface The inbox.
	 */
	private function intakeService(): IntakeServiceInterface {
		$service = $this->createMock(IntakeServiceInterface::class);
		$service->method('receiveMessage')->willReturnCallback(
			function (IntakeDocumentReceivedEvent $message, array $attachments = []): array {
				$this->inbox[$message->getSourceRef()] = $message;

				return [['uuid' => 'intake-' . count($this->inbox)]];
			}
		);

		return $service;

	}//end intakeService()

	/**
	 * Start an import and map it.
	 *
	 * @param MailArchiveImportService $service   The service.
	 * @param array<int, mixed>        $folderMap The mapping.
	 *
	 * @return string The import's uuid.
	 */
	private function startAndMap(MailArchiveImportService $service, array $folderMap): string {
		$uuid = $service->start(userId: self::USER, fileId: self::ARCHIVE_ID)['uuid'];
		$service->map(userId: self::USER, uuid: $uuid, folderMap: $folderMap);

		return $uuid;

	}//end startAndMap()

	/**
	 * Starting lists the archive's folders with their counts and waits in mapping.
	 *
	 * @return void
	 */
	public function testStartListsTheFoldersAndWaitsForTheMapping(): void {
		$this->useZip();

		$import = $this->service()->start(userId: self::USER, fileId: self::ARCHIVE_ID);

		$this->assertSame('mapping', $import['status']);
		$this->assertSame('zip', $import['format']);
		$this->assertSame(self::USER, $import['requestedBy']);
		$this->assertSame(self::ARCHIVE_ID, $import['file']);
		$this->assertSame(
			[
				['archiveFolder' => 'Inbox', 'messages' => 4, 'dossier' => ''],
				['archiveFolder' => 'Projecten/Brug', 'messages' => 2, 'dossier' => ''],
			],
			$import['folderMap']
		);
		$this->assertSame(['found' => 6, 'filed' => 0, 'toInbox' => 0, 'skippedDuplicate' => 0, 'failed' => 0], $import['counts']);
		$this->assertSame([], $this->staged, 'listing writes no message');
		$this->assertSame([false, false], array_column($this->imports->saves, 1), 'the import is written past the cascade, by the service');

	}//end testStartListsTheFoldersAndWaitsForTheMapping()

	/**
	 * A file the user cannot read is a 404 and records nothing.
	 *
	 * @return void
	 */
	public function testAFileTheUserCannotReadIsRefused(): void {
		$this->useZip();
		$this->canRead = false;

		try {
			$this->service()->start(userId: self::USER, fileId: self::ARCHIVE_ID);
			$this->fail('expected a refusal');
		} catch (InvalidArgumentException $e) {
			$this->assertSame('file_not_found', $e->getMessage());
			$this->assertSame(404, $e->getCode());
		}

		$this->assertSame([], $this->imports->rows);

	}//end testAFileTheUserCannotReadIsRefused()

	/**
	 * A PST on a server without readpst fails at once with the message the spec names.
	 *
	 * @return void
	 */
	public function testAPstWithoutReadpstFailsAtOnce(): void {
		$this->archiveName = 'archief.pst';
		$this->archivePath = $this->dir . '/archief.pst';
		file_put_contents($this->archivePath, "!BDN\x00\x00rest of a pst");
		$this->readpst->method('isAvailable')->willReturn(false);

		$import = $this->service()->start(userId: self::USER, fileId: self::ARCHIVE_ID);

		$this->assertSame('failed', $import['status']);
		$this->assertSame('pst', $import['format']);
		$this->assertSame('readpst is not installed on this server', $import['statusReason']);
		$this->assertArrayHasKey('finishedAt', $import);

	}//end testAPstWithoutReadpstFailsAtOnce()

	/**
	 * A file that is no mail archive fails with the reason, not an error.
	 *
	 * @return void
	 */
	public function testAFileThatIsNoArchiveFails(): void {
		$this->archiveName = 'notities.txt';
		$this->archivePath = $this->dir . '/notities.txt';
		file_put_contents($this->archivePath, 'just some notes');

		$import = $this->service()->start(userId: self::USER, fileId: self::ARCHIVE_ID);

		$this->assertSame('failed', $import['status']);
		$this->assertSame(MailArchiveSplitter::UNKNOWN_FORMAT, $import['statusReason']);
		$this->assertArrayNotHasKey('format', $import);

	}//end testAFileThatIsNoArchiveFails()

	/**
	 * A folder mapped to a dossier the user may not write to refuses the whole mapping.
	 *
	 * @return void
	 */
	public function testMappingToADossierTheUserMayNotWriteIsRefused(): void {
		$this->useZip();
		$service = $this->service();
		$uuid = $service->start(userId: self::USER, fileId: self::ARCHIVE_ID)['uuid'];

		try {
			$service->map(userId: self::USER, uuid: $uuid, folderMap: [['archiveFolder' => 'Projecten/Brug', 'dossier' => 'dossier-refused']]);
			$this->fail('expected a refusal');
		} catch (InvalidArgumentException $e) {
			$this->assertSame(403, $e->getCode());
			$this->assertSame('dossier_refused', $e->getMessage());
		}

		$this->assertSame('mapping', $this->imports->rows[$uuid]['status']);

	}//end testMappingToADossierTheUserMayNotWriteIsRefused()

	/**
	 * Mapping a folder the archive does not hold, someone else's import, or one past mapping is refused.
	 *
	 * @return void
	 */
	public function testABadMappingIsRefused(): void {
		$this->useZip();
		$service = $this->service();
		$uuid = $service->start(userId: self::USER, fileId: self::ARCHIVE_ID)['uuid'];

		$refusals = [];
		foreach ([
			[self::USER, [['archiveFolder' => 'Verzonden', 'dossier' => self::DOSSIER]]],
			[self::USER, ['not a row']],
			['iemand-anders', []],
		] as [$userId, $folderMap]) {
			try {
				$service->map(userId: $userId, uuid: $uuid, folderMap: $folderMap);
			} catch (InvalidArgumentException $e) {
				$refusals[] = $e->getCode() . ' ' . $e->getMessage();
			}
		}

		$service->map(userId: self::USER, uuid: $uuid, folderMap: []);
		try {
			$service->map(userId: self::USER, uuid: $uuid, folderMap: []);
		} catch (InvalidArgumentException $e) {
			$refusals[] = $e->getCode() . ' ' . $e->getMessage();
		}

		$this->assertSame(['400 unknown_folder', '400 invalid_mapping', '404 not_found', '409 not_mapping'], $refusals);

	}//end testABadMappingIsRefused()

	/**
	 * A run stops after one unit and saves the cursor; the next run resumes there and the counts go on.
	 *
	 * @return void
	 */
	public function testOneRunTakesOneUnitAndTheNextResumesAtTheCursor(): void {
		$this->useMbox();
		$this->limit = '2';
		$uuid = $this->startAndMap(service: $this->service(), folderMap: []);

		$first = $this->service()->runUnit();
		$this->assertSame('importing', $first['status']);
		$this->assertSame(2, $first['counts']['toInbox']);
		$this->assertSame(2, $first['cursor']['ordinal']);
		$this->assertSame(['m1@demostad.nl', 'm2@demostad.nl'], array_keys($this->inbox));

		// A fresh service, as after a restart: the position comes from the stored import only.
		$second = $this->service()->runUnit();
		$this->assertSame(4, $second['counts']['toInbox']);
		$this->assertSame(4, $second['cursor']['ordinal']);

		$third = $this->service()->runUnit();
		$this->assertSame('done', $third['status']);
		$this->assertSame(['found' => 5, 'filed' => 0, 'toInbox' => 5, 'skippedDuplicate' => 0, 'failed' => 0], $third['counts']);
		$this->assertSame(['m1@demostad.nl', 'm2@demostad.nl', 'm3@demostad.nl', 'm4@demostad.nl', 'm5@demostad.nl'], array_keys($this->inbox));
		$this->assertSame($uuid, $third['uuid']);
		$this->assertNull($this->service()->runUnit(), 'nothing is importing any more');

	}//end testOneRunTakesOneUnitAndTheNextResumesAtTheCursor()

	/**
	 * Mapped folders are filed to their dossier, the rest go to the inbox as mail, a .msg fails visibly.
	 *
	 * @return void
	 */
	public function testMappedFoldersAreFiledAndTheRestGoToTheInbox(): void {
		$this->useZip();
		$this->startAndMap(service: $this->service(), folderMap: [['archiveFolder' => 'Projecten/Brug', 'dossier' => self::DOSSIER]]);

		$import = $this->service()->runUnit();

		$this->assertSame('done', $import['status']);
		$this->assertSame(['found' => 6, 'filed' => 2, 'toInbox' => 3, 'skippedDuplicate' => 0, 'failed' => 1], $import['counts']);
		$this->assertSame([['messageId' => '', 'archiveFolder' => 'Inbox', 'name' => 'uitnodiging.msg', 'reason' => 'unsupported-format']], $import['failures']);

		$this->assertSame([self::DOSSIER, self::DOSSIER], array_values($this->filedTo));
		$filed = array_values($this->emails->rows);
		$this->assertSame(['brug-1@demostad.nl', 'brug-2@demostad.nl'], array_column($filed, 'messageId'));
		$this->assertSame(['archive-import', 'archive-import'], array_column($filed, 'ingestSource'));
		$this->assertSame(['filed', 'filed'], array_column($filed, 'status'));
		$this->assertSame(hash('sha256', self::eml(messageId: 'brug-1@demostad.nl', subject: 'Brug 1')), $filed[0]['contentHash']);

		$this->assertCount(3, $this->inbox);
		$noId = 'sha256:' . hash('sha256', self::eml(messageId: '', subject: 'Inbox B zonder id'));
		$this->assertSame(['inbox-a@demostad.nl', $noId, 'inbox-c@demostad.nl'], array_keys($this->inbox));
		foreach ($this->inbox as $message) {
			$this->assertSame(IntakeDocumentReceivedEvent::CHANNEL_MAIL, $message->getChannel());
			$this->assertSame('jansen@demostad.nl', $message->getSender());
		}

		$this->assertSame('Inbox A', $this->inbox['inbox-a@demostad.nl']->getSubject());

	}//end testMappedFoldersAreFiledAndTheRestGoToTheInbox()

	/**
	 * The same archive imported twice with the same mapping files nothing the second time.
	 *
	 * @return void
	 */
	public function testASecondImportOfTheSameArchiveOnlySkipsDuplicates(): void {
		$this->useZip();
		$mapping = [['archiveFolder' => 'Projecten/Brug', 'dossier' => self::DOSSIER]];
		$this->startAndMap(service: $this->service(), folderMap: $mapping);
		$this->service()->runUnit();
		$emailsBefore = count($this->emails->rows);
		$stagedBefore = count($this->staged);

		$this->startAndMap(service: $this->service(), folderMap: $mapping);
		$second = $this->service()->runUnit();

		$this->assertSame('done', $second['status']);
		$this->assertSame(['found' => 6, 'filed' => 0, 'toInbox' => 0, 'skippedDuplicate' => 5, 'failed' => 1], $second['counts']);
		$this->assertCount($emailsBefore, $this->emails->rows, 'no new email in the dossier');
		$this->assertCount($stagedBefore, $this->staged, 'a duplicate is not written anywhere');
		$this->assertCount(3, $this->inbox);

	}//end testASecondImportOfTheSameArchiveOnlySkipsDuplicates()

	/**
	 * An unreadable message is a failure with its reason, and the file written for it is removed.
	 *
	 * @return void
	 */
	public function testAnUnreadableMessageFailsAndLeavesNoFile(): void {
		$this->archiveName = 'kapot.zip';
		$this->archivePath = $this->dir . '/kapot.zip';
		$zip = new ZipArchive();
		$zip->open($this->archivePath, ZipArchive::CREATE);
		$zip->addFromString('Inbox/kapot.eml', self::eml(messageId: 'kapot@demostad.nl', subject: 'Kapot', extra: "X-Broken: yes\r\n"));
		$zip->addFromString('Brug/kapot.eml', self::eml(messageId: 'kapot-2@demostad.nl', subject: 'Kapot 2', extra: "X-Broken: yes\r\n"));
		$zip->close();
		$this->startAndMap(service: $this->service(), folderMap: [['archiveFolder' => 'Brug', 'dossier' => self::DOSSIER]]);

		$import = $this->service()->runUnit();

		$this->assertSame(2, $import['counts']['failed']);
		$this->assertSame(['kapot@demostad.nl', 'kapot-2@demostad.nl'], array_column($import['failures'], 'messageId'));
		$this->assertSame(['unparseable', 'unparseable'], array_column($import['failures'], 'reason'));
		$this->assertSame([true, true], array_column($this->staged, 'deleted'));
		$this->assertSame([], $this->inbox);
		$this->assertSame([], $this->filedTo);

	}//end testAnUnreadableMessageFailsAndLeavesNoFile()

	/**
	 * A cancelled import is not drained further, and an ended one cannot be cancelled.
	 *
	 * @return void
	 */
	public function testCancelStopsTheImport(): void {
		$this->useMbox();
		$this->limit = '2';
		$uuid = $this->startAndMap(service: $this->service(), folderMap: []);
		$this->service()->runUnit();

		$cancelled = $this->service()->cancel(userId: self::USER, uuid: $uuid);

		$this->assertSame('cancelled', $cancelled['status']);
		$this->assertArrayHasKey('finishedAt', $cancelled);
		$this->assertNull($this->service()->runUnit());
		$this->assertCount(2, $this->inbox, 'what was received stays');

		try {
			$this->service()->cancel(userId: self::USER, uuid: $uuid);
			$this->fail('expected a refusal');
		} catch (InvalidArgumentException $e) {
			$this->assertSame(409, $e->getCode());
		}

	}//end testCancelStopsTheImport()

	/**
	 * Another user can neither read nor cancel an import, and sees none in their list.
	 *
	 * @return void
	 */
	public function testAnImportIsOnlyAnsweredToItsRequester(): void {
		$this->useZip();
		$service = $this->service();
		$uuid = $service->start(userId: self::USER, fileId: self::ARCHIVE_ID)['uuid'];

		$codes = [];
		foreach (['get', 'cancel'] as $action) {
			try {
				$service->{$action}(userId: 'iemand-anders', uuid: $uuid);
			} catch (InvalidArgumentException $e) {
				$codes[] = $e->getCode();
			}
		}

		$this->assertSame([404, 404], $codes);
		$this->assertSame([], $service->mine(userId: 'iemand-anders'));
		$this->assertSame([$uuid], array_column($service->mine(userId: self::USER), 'uuid'));
		$this->assertSame('mapping', $service->get(userId: self::USER, uuid: $uuid)['status']);

	}//end testAnImportIsOnlyAnsweredToItsRequester()

	/**
	 * An archive deleted while importing fails the import with the reason.
	 *
	 * @return void
	 */
	public function testAGoneArchiveFailsTheImport(): void {
		$this->useMbox();
		$this->startAndMap(service: $this->service(), folderMap: []);
		$this->archiveExists = false;

		$import = $this->service()->runUnit();

		$this->assertSame('failed', $import['status']);
		$this->assertSame(MailArchiveImportService::ARCHIVE_GONE, $import['statusReason']);
		$this->assertSame([], $this->inbox);

	}//end testAGoneArchiveFailsTheImport()
}//end class
