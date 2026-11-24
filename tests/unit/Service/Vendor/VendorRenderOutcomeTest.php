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
use OCA\Filinq\Service\Vendor\VendorRenderOutcome;
use OCA\OpenRegister\Service\ObjectService;
use OCP\EventDispatcher\Event;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
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
	 * Build the outcome service over doubled OpenRegister.
	 *
	 * @return VendorRenderOutcome
	 */
	private function outcome(): VendorRenderOutcome {
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
}//end class
