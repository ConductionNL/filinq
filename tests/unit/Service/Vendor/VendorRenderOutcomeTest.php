<?php

/**
 * Unit tests for VendorRenderOutcome and VendorRenderedListener
 *
 * integriq is stubbed at the event boundary: its outcome event is a double
 * with the constructor and readers of integriq's DocumentRenderedEvent, and
 * the register is OpenRegister's ObjectService doubled on findAll and
 * saveObject.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Vendor
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/template-engine-renders-through-a-vendor/tasks.md#2-backend
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Vendor;

require_once __DIR__ . '/RenderedEventDouble.php';

use OCA\Filinq\EventListener\VendorRenderedListener;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\DocumentStorageService;
use OCA\Filinq\Service\Vendor\VendorRenderOutcome;
use OCA\OpenRegister\Service\ObjectService;
use OCP\EventDispatcher\Event;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Exception;
use RuntimeException;

/**
 * A failed vendor render settles its pending entry; nothing else is written.
 */
#[CoversClass(VendorRenderOutcome::class)]
#[CoversClass(VendorRenderedListener::class)]
class VendorRenderOutcomeTest extends TestCase {

	/**
	 * The rows findAll answers.
	 *
	 * @var array<int, mixed>
	 */
	private array $rows = [];

	/**
	 * The configs findAll was asked with.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $searches = [];

	/**
	 * The saves: [object, uuid].
	 *
	 * @var array<int, array{0: array<string, mixed>, 1: ?string}>
	 */
	private array $saved = [];

	/**
	 * Whether findAll throws.
	 *
	 * @var boolean
	 */
	private bool $findThrows = false;

	/**
	 * The logger double.
	 *
	 * @var LoggerInterface&\PHPUnit\Framework\MockObject\MockObject
	 */
	private LoggerInterface $logger;

	/**
	 * The documents filed: [userId, targetPath, filename, content].
	 *
	 * @var array<int, array{0: string, 1: string, 2: string, 3: string}>
	 */
	private array $filed = [];

	/**
	 * Whether filing throws.
	 *
	 * @var boolean
	 */
	private bool $storeThrows = false;

	/**
	 * A storage double that records what it files.
	 *
	 * @return DocumentStorageService
	 */
	private function storage(): DocumentStorageService {
		$storage = $this->createMock(DocumentStorageService::class);
		$storage->method('store')->willReturnCallback(
			function (string $userId, string $targetPath, string $filename, string $content): array {
				if ($this->storeThrows === true) {
					throw new Exception('Failed to store generated document in Files: disk full', 507);
				}

				$this->filed[] = [$userId, $targetPath, $filename, $content];
				return ['fileId' => 4711, 'path' => '/' . $userId . '/files/' . $targetPath . '/' . $filename, 'name' => $filename, 'size' => strlen($content)];
			}
		);

		return $storage;
	}//end storage()

	/**
	 * Build the outcome service over doubled OpenRegister.
	 *
	 * @param boolean $withStorage Whether the service can file documents.
	 *
	 * @return VendorRenderOutcome
	 */
	private function outcome(bool $withStorage = false): VendorRenderOutcome {
		$objects = $this->getMockBuilder(ObjectService::class)->onlyMethods(['findAll', 'saveObject'])->getMock();
		$objects->method('findAll')->willReturnCallback(
			function (array $config = []): array {
				if ($this->findThrows === true) {
					throw new RuntimeException('register down');
				}

				$this->searches[] = $config;
				return $this->rows;
			}
		);
		$objects->method('saveObject')->willReturnCallback(
			function (array $object, $options = null): array {
				$this->saved[] = [$object, $options?->uuid];
				return $object + ['id' => (string) $options?->uuid];
			}
		);

		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objects);

		$this->logger = $this->createMock(LoggerInterface::class);

		if ($withStorage === true) {
			return new VendorRenderOutcome($resolver, $this->logger, $this->storage());
		}

		return new VendorRenderOutcome($resolver, $this->logger);
	}//end outcome()

	/**
	 * A pending entry recorded for job-1.
	 *
	 * @return array<string, mixed>
	 */
	private static function pending(): array {
		return [
			'id' => 'entry-1',
			'@self' => ['id' => 'entry-1'],
			'templateId' => 'tpl-1',
			'status' => 'pending',
			'vendorJobId' => 'job-1',
			'engine' => 'vendor:smartdocuments',
			'errorMessage' => null,
		];
	}//end pending()

	/**
	 * A failed outcome marks the pending entry failed with integriq's error.
	 *
	 * @return void
	 */
	public function testAFailedRenderMarksTheEntryFailedWithTheError(): void {
		$this->rows = [self::pending()];
		$written    = $this->outcome()->settle(jobId: 'job-1', status: 'failed', error: 'Template not found at the vendor');

		self::assertCount(1, $this->saved);
		[$object, $uuid] = $this->saved[0];
		self::assertSame('entry-1', $uuid);
		self::assertSame('failed', $object['status']);
		self::assertSame('Template not found at the vendor', $object['errorMessage']);
		self::assertSame('job-1', $object['vendorJobId']);
		self::assertArrayNotHasKey('id', $object);
		self::assertArrayNotHasKey('@self', $object);
		self::assertSame('failed', $written['status']);

		$filters = $this->searches[0]['filters'];
		self::assertSame(['register' => 'filinq', 'schema' => 'generatedDocument', 'vendorJobId' => 'job-1', 'status' => 'pending'], $filters);
	}//end testAFailedRenderMarksTheEntryFailedWithTheError()

	/**
	 * A failure without a reason is written with a sentence, never left empty.
	 *
	 * @return void
	 */
	public function testAFailureWithoutAReasonSaysSo(): void {
		$this->rows = [self::pending()];
		$this->outcome()->settle(jobId: 'job-1', status: 'failed', error: '  ');

		self::assertSame(VendorRenderOutcome::NO_REASON, $this->saved[0][0]['errorMessage']);
	}//end testAFailureWithoutAReasonSaysSo()

	/**
	 * A rendered outcome leaves the entry pending: integriq hands over no document.
	 *
	 * @return void
	 */
	public function testARenderedOutcomeLeavesTheEntryPending(): void {
		$this->rows = [self::pending()];
		$outcome    = $this->outcome();
		$this->logger->expects(self::once())->method('info');

		self::assertNull($outcome->settle(jobId: 'job-1', status: 'rendered', error: ''));
		self::assertSame([], $this->saved);
	}//end testARenderedOutcomeLeavesTheEntryPending()

	/**
	 * A job filinq has no pending entry for is another app's, and nothing is written.
	 *
	 * @return void
	 */
	public function testAnUnknownJobWritesNothing(): void {
		$this->rows = [];
		self::assertNull($this->outcome()->settle(jobId: 'job-9', status: 'failed', error: 'x'));
		self::assertSame([], $this->saved);
	}//end testAnUnknownJobWritesNothing()

	/**
	 * A row the register answers for a different job or an already settled entry is not touched.
	 *
	 * @return void
	 */
	public function testARowOfAnotherJobOrSettledIsNotTouched(): void {
		$other  = ['vendorJobId' => 'job-2'] + self::pending();
		$done   = ['status' => 'generated'] + self::pending();
		$noId   = self::pending();
		unset($noId['id'], $noId['@self']);
		$this->rows = [$other, $done, $noId, 'not a row'];

		self::assertNull($this->outcome()->settle(jobId: 'job-1', status: 'failed', error: 'x'));
		self::assertSame([], $this->saved);
	}//end testARowOfAnotherJobOrSettledIsNotTouched()

	/**
	 * An entity row is read through jsonSerialize.
	 *
	 * @return void
	 */
	public function testAnEntityRowIsRead(): void {
		$row = self::pending();
		unset($row['id']);
		$entity = new class($row) implements \JsonSerializable {
			/**
			 * @param array<string, mixed> $row The row.
			 */
			public function __construct(private readonly array $row) {
			}

			/**
			 * @return array<string, mixed>
			 */
			public function jsonSerialize(): array {
				return $this->row;
			}
		};
		$this->rows = [$entity];

		$this->outcome()->settle(jobId: 'job-1', status: 'failed', error: 'x');
		self::assertSame('entry-1', $this->saved[0][1]);
	}//end testAnEntityRowIsRead()

	/**
	 * An empty job id is not looked up.
	 *
	 * @return void
	 */
	public function testAnEmptyJobIdIsIgnored(): void {
		self::assertNull($this->outcome()->settle(jobId: ' ', status: 'failed', error: 'x'));
		self::assertSame([], $this->searches);
	}//end testAnEmptyJobIdIsIgnored()

	/**
	 * A register failure is logged and never thrown at integriq.
	 *
	 * @return void
	 */
	public function testARegisterFailureIsLoggedNotThrown(): void {
		$this->findThrows = true;
		$outcome          = $this->outcome();
		$this->logger->expects(self::once())->method('error');

		self::assertNull($outcome->settle(jobId: 'job-1', status: 'failed', error: 'x'));
	}//end testARegisterFailureIsLoggedNotThrown()

	/**
	 * The listener hands integriq's outcome to the service, and the entry is settled.
	 *
	 * @return void
	 */
	public function testTheListenerSettlesFromIntegriqsEvent(): void {
		$this->rows = [self::pending()];
		$listener   = new VendorRenderedListener($this->outcome());

		$listener->handle(new RenderedEventDouble('job-1', 'failed', 'alice', '', 'Vendor timeout'));

		self::assertSame('failed', $this->saved[0][0]['status']);
		self::assertSame('Vendor timeout', $this->saved[0][0]['errorMessage']);
	}//end testTheListenerSettlesFromIntegriqsEvent()

	/**
	 * Another event, without the outcome readers, is ignored.
	 *
	 * @return void
	 */
	public function testTheListenerIgnoresAnUnrelatedEvent(): void {
		$listener = new VendorRenderedListener($this->outcome());
		$listener->handle(new Event());

		self::assertSame([], $this->searches);
	}//end testTheListenerIgnoresAnUnrelatedEvent()

	/**
	 * An event with no error reader settles with the no-reason sentence.
	 *
	 * @return void
	 */
	public function testAnEventWithoutAnErrorReaderStillSettles(): void {
		$this->rows = [self::pending()];
		$listener   = new VendorRenderedListener($this->outcome());
		$event      = new class extends Event {
			/**
			 * @return string
			 */
			public function getJobId(): string {
				return 'job-1';
			}

			/**
			 * @return string
			 */
			public function getStatus(): string {
				return 'failed';
			}
		};

		$listener->handle($event);
		self::assertSame(VendorRenderOutcome::NO_REASON, $this->saved[0][0]['errorMessage']);
	}//end testAnEventWithoutAnErrorReaderStillSettles()

	/**
	 * The listener is keyed by integriq's class name.
	 *
	 * @return void
	 */
	public function testTheEventNameIsIntegriqs(): void {
		self::assertSame('OCA\Integriq\Event\DocumentRenderedEvent', VendorRenderedListener::RENDERED_EVENT);
	}//end testTheEventNameIsIntegriqs()

	/**
	 * A pending entry that knows who it is for and where it goes.
	 *
	 * @return array<string, mixed>
	 */
	private static function pendingWithTarget(): array {
		return self::pending() + [
			'generatedBy' => 'alice',
			'format' => 'pdf',
			'outputFolder' => 'DocuDesk/besluiten',
			'outputName' => 'besluit.pdf',
		];
	}//end pendingWithTarget()

	/**
	 * A rendered outcome with the document files it and marks the entry generated with the file.
	 *
	 * @return void
	 */
	public function testARenderedDocumentIsFiledAndTheEntryGenerated(): void {
		$this->rows = [self::pendingWithTarget()];
		$written    = $this->outcome(withStorage: true)->settle(jobId: 'job-1', status: 'rendered', error: '', document: '%PDF-1.4 besluit');

		self::assertSame([['alice', 'DocuDesk/besluiten', 'besluit.pdf', '%PDF-1.4 besluit']], $this->filed);
		self::assertCount(1, $this->saved);
		[$object, $uuid] = $this->saved[0];
		self::assertSame('entry-1', $uuid);
		self::assertSame('generated', $object['status']);
		self::assertSame(4711, $object['fileId']);
		self::assertSame('/alice/files/DocuDesk/besluiten/besluit.pdf', $object['filePath']);
		self::assertNull($object['errorMessage']);
		self::assertSame('generated', $written['status']);
	}//end testARenderedDocumentIsFiledAndTheEntryGenerated()

	/**
	 * An entry without a recorded target is filed in the default folder under a name for its format.
	 *
	 * @return void
	 */
	public function testAnEntryWithoutATargetIsFiledByDefault(): void {
		$this->rows = [self::pending() + ['generatedBy' => 'alice', 'format' => 'docx']];
		$this->outcome(withStorage: true)->settle(jobId: 'job-1', status: 'rendered', error: '', document: 'PK docx');

		self::assertSame(['alice', VendorRenderOutcome::DEFAULT_FOLDER, 'document.docx', 'PK docx'], $this->filed[0]);
		self::assertSame('generated', $this->saved[0][0]['status']);
	}//end testAnEntryWithoutATargetIsFiledByDefault()

	/**
	 * Each format is named as a filinq render names it.
	 *
	 * @return void
	 */
	public function testTheDefaultNameFollowsTheFormat(): void {
		foreach (['odf' => 'document.odt', 'html' => 'document.html', '' => 'document.pdf'] as $format => $name) {
			$this->rows  = [self::pending() + ['generatedBy' => 'alice', 'format' => $format]];
			$this->filed = [];
			$this->outcome(withStorage: true)->settle(jobId: 'job-1', status: 'rendered', error: '', document: 'x');
			self::assertSame($name, $this->filed[0][2]);
		}
	}//end testTheDefaultNameFollowsTheFormat()

	/**
	 * A document that cannot be filed marks the entry failed with the reason.
	 *
	 * @return void
	 */
	public function testADocumentThatCannotBeFiledFailsTheEntry(): void {
		$this->rows        = [self::pendingWithTarget()];
		$this->storeThrows = true;
		$this->outcome(withStorage: true)->settle(jobId: 'job-1', status: 'rendered', error: '', document: 'x');

		self::assertSame('failed', $this->saved[0][0]['status']);
		self::assertStringContainsString('could not be filed', $this->saved[0][0]['errorMessage']);
		self::assertStringContainsString('disk full', $this->saved[0][0]['errorMessage']);
	}//end testADocumentThatCannotBeFiledFailsTheEntry()

	/**
	 * A render that names no user is failed, never filed in somebody's Files.
	 *
	 * @return void
	 */
	public function testARenderWithoutAUserIsNotFiled(): void {
		$this->rows = [['generatedBy' => '  '] + self::pendingWithTarget()];
		$this->outcome(withStorage: true)->settle(jobId: 'job-1', status: 'rendered', error: '', document: 'x');

		self::assertSame([], $this->filed);
		self::assertSame('failed', $this->saved[0][0]['status']);
		self::assertStringContainsString('names no user', $this->saved[0][0]['errorMessage']);
	}//end testARenderWithoutAUserIsNotFiled()

	/**
	 * Without a way to file, a rendered document leaves the entry pending.
	 *
	 * @return void
	 */
	public function testWithoutStorageARenderedDocumentStaysPending(): void {
		$this->rows = [self::pendingWithTarget()];
		$outcome    = $this->outcome();
		$this->logger->expects(self::once())->method('info');

		self::assertNull($outcome->settle(jobId: 'job-1', status: 'rendered', error: '', document: 'x'));
		self::assertSame([], $this->saved);
	}//end testWithoutStorageARenderedDocumentStaysPending()

	/**
	 * An outcome that arrives before its entry is settled once the entry is written.
	 *
	 * @return void
	 */
	public function testAnEarlyOutcomeIsSettledOnceTheEntryExists(): void {
		$outcome    = $this->outcome(withStorage: true);
		$this->rows = [];
		self::assertNull($outcome->settle(jobId: 'job-1', status: 'rendered', error: '', document: '%PDF early'));
		self::assertSame([], $this->saved);

		$this->rows = [self::pendingWithTarget()];
		$written    = $outcome->settleEarly(jobId: 'job-1');

		self::assertSame('generated', $written['status']);
		self::assertSame('%PDF early', $this->filed[0][3]);
		self::assertNull($outcome->settleEarly(jobId: 'job-1'), 'An early outcome is settled once.');
	}//end testAnEarlyOutcomeIsSettledOnceTheEntryExists()

	/**
	 * A job without a kept outcome settles nothing early.
	 *
	 * @return void
	 */
	public function testNoEarlyOutcomeSettlesNothing(): void {
		self::assertNull($this->outcome()->settleEarly(jobId: 'job-1'));
		self::assertSame([], $this->searches);
	}//end testNoEarlyOutcomeSettlesNothing()

	/**
	 * Only the latest outcomes of unknown jobs are kept.
	 *
	 * @return void
	 */
	public function testOnlyTheLatestEarlyOutcomesAreKept(): void {
		$outcome    = $this->outcome();
		$this->rows = [];
		for ($i = 0; $i <= VendorRenderOutcome::EARLY_LIMIT; $i++) {
			$outcome->settle(jobId: 'job-' . $i, status: 'failed', error: 'e' . $i);
		}

		$this->rows = [['vendorJobId' => 'job-' . VendorRenderOutcome::EARLY_LIMIT] + self::pending()];
		self::assertNull($outcome->settleEarly(jobId: 'job-0'), 'The oldest outcome was dropped.');
		self::assertNotNull($outcome->settleEarly(jobId: 'job-' . VendorRenderOutcome::EARLY_LIMIT));
	}//end testOnlyTheLatestEarlyOutcomesAreKept()

	/**
	 * The listener hands integriq's document over, and it is filed.
	 *
	 * @return void
	 */
	public function testTheListenerFilesTheDocumentIntegriqHandsOver(): void {
		$this->rows = [self::pendingWithTarget()];
		$listener   = new VendorRenderedListener($this->outcome(withStorage: true));

		$listener->handle(new RenderedEventDouble('job-1', 'rendered', 'alice', 'sd-doc-1', '', '%PDF from the vendor'));

		self::assertSame('%PDF from the vendor', $this->filed[0][3]);
		self::assertSame('generated', $this->saved[0][0]['status']);
	}//end testTheListenerFilesTheDocumentIntegriqHandsOver()
}//end class
