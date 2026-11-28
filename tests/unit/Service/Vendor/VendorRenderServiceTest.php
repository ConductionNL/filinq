<?php

/**
 * Unit tests for the vendor render: RenderEngine, VendorRenderSources and VendorRenderService
 *
 * integriq is stubbed at the event boundary: the render command is a double
 * with the shape of integriq's DocumentRenderRequestedEvent, and the
 * dispatcher answers it the way integriq's listener does.
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
 * @spec openspec/changes/template-engine-renders-through-a-vendor/tasks.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Vendor;

require_once __DIR__ . '/VendorRenderDoubles.php';

use Exception;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\GeneratedDocumentLogger;
use OCA\Filinq\Service\Vendor\RenderEngine;
use OCA\Filinq\Service\Vendor\VendorRenderOutcome;
use OCA\Filinq\Service\Vendor\VendorRenderService;
use OCA\Filinq\Service\Vendor\VendorRenderSources;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use OCP\EventDispatcher\IEventDispatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * A vendor render goes to integriq, is recorded pending, and never falls back.
 */
#[CoversClass(RenderEngine::class)]
#[CoversClass(VendorRenderSources::class)]
#[CoversClass(VendorRenderService::class)]
class VendorRenderServiceTest extends TestCase {

	/**
	 * The entries written to the generatedDocument log.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $logged = [];

	/**
	 * The render commands dispatched.
	 *
	 * @var array<int, RenderRequestedEventDouble>
	 */
	private array $dispatched = [];

	/**
	 * integriq's source rows.
	 *
	 * @var array<int, mixed>
	 */
	private array $sourceRows = [];

	/**
	 * The filters integriq's sources were searched with.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $searches = [];

	/**
	 * Set up a SmartDocuments source and an empty log.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->logged     = [];
		$this->dispatched = [];
		$this->searches   = [];
		$this->sourceRows = [
			['@self' => ['id' => 'src-1'], 'slug' => 'smartdocuments', 'name' => 'SmartDocuments', 'type' => 'documentGeneration', 'isEnabled' => true, 'status' => 'ok'],
		];

	}//end setUp()

	/**
	 * The sources, over an ObjectService double answering the source rows.
	 *
	 * @param bool $integriq Whether integriq is enabled.
	 *
	 * @return VendorRenderSources The sources.
	 */
	private function sources(bool $integriq = true): VendorRenderSources {
		$objects = $this->createMock(originalClassName: ObjectService::class);
		$objects->method('searchObjectsBySlug')->willReturnCallback(
			function (string $registerSlug, string $schemaSlug, array $filters = []): array {
				$this->searches[] = ['register' => $registerSlug, 'schema' => $schemaSlug, 'filters' => $filters];
				return $this->sourceRows;
			}
		);
		$objects->method('saveObject')->willReturnCallback(function (array $object): array {
			$this->logged[] = $object;
			return $object;
		});

		$resolver = $this->createMock(originalClassName: DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objects);
		$apps = $this->createMock(originalClassName: IAppManager::class);
		$apps->method('isEnabledForAnyone')->willReturnCallback(static fn (string $app): bool => $integriq && $app === 'integriq');

		return new VendorRenderSources(appManager: $apps, objectResolver: $resolver, logger: $this->createMock(originalClassName: LoggerInterface::class));

	}//end sources()

	/**
	 * The service; the dispatcher answers each command through $answer.
	 *
	 * @param callable|null $answer   What integriq does with the command; null answers job `job-42`.
	 * @param bool          $integriq Whether integriq is enabled.
	 * @param string        $event    The command class.
	 * @param VendorRenderOutcome|null $outcome Settles an outcome that arrived before the entry.
	 *
	 * @return VendorRenderService The service.
	 */
	private function service(?callable $answer = null, bool $integriq = true, string $event = RenderRequestedEventDouble::class, ?VendorRenderOutcome $outcome = null): VendorRenderService {
		$answer ??= static function (RenderRequestedEventDouble $command): void {
			$command->setJobId('job-42');
		};
		$dispatcher = $this->createMock(originalClassName: IEventDispatcher::class);
		$dispatcher->method('dispatchTyped')->willReturnCallback(function (object $command) use ($answer): void {
			$this->dispatched[] = $command;
			$answer($command);
		});

		$sources  = $this->sources(integriq: $integriq);
		$resolver = $this->createMock(originalClassName: DocumentObjectServiceResolver::class);
		$objects  = $this->createMock(originalClassName: ObjectService::class);
		$objects->method('saveObject')->willReturnCallback(function (array $object): array {
			$this->logged[] = $object;
			return $object;
		});
		$resolver->method('resolve')->willReturn($objects);

		return new VendorRenderService(
			sources: $sources,
			dispatcher: $dispatcher,
			log: new GeneratedDocumentLogger(objectResolver: $resolver, logger: $this->createMock(originalClassName: LoggerInterface::class)),
			requestEvent: $event,
			outcome: $outcome
		);

	}//end service()

	/**
	 * A generation run of a template with a vendor template id.
	 *
	 * @param array<string, mixed> $template Overrides of the template.
	 *
	 * @return array<string, mixed> The run.
	 */
	private function generationRun(array $template = []): array {
		return [
			'templateId' => 'tpl-1',
			'template' => $template + ['name' => 'Besluit', 'version' => 3, 'vendorTemplateId' => 'besluit-standaard'],
			'dataRefs' => [['register' => 'filinq', 'schema' => 'dossier', 'id' => 'd-1']],
			'options' => ['userId' => 'alice', 'caseId' => 'zaak-9'],
			'recordFields' => ['sourceObject' => 'dossier/d-1'],
			'format' => 'pdf',
		];

	}//end generationRun()

	/**
	 * Absent, empty and `filinq` are filinq's own render; `vendor:<slug>` names the slug.
	 *
	 * @return void
	 */
	public function testTheEngineNamesFilinqOrAVendorSlug(): void {
		$this->assertNull(actual: (new RenderEngine())->vendorSlug(engine: null));
		$this->assertNull(actual: (new RenderEngine())->vendorSlug(engine: ''));
		$this->assertNull(actual: (new RenderEngine())->vendorSlug(engine: 'filinq'));
		$this->assertSame(expected: 'smartdocuments', actual: (new RenderEngine())->vendorSlug(engine: 'vendor:smartdocuments'));
		$this->assertSame(expected: 'vendor:xential', actual: (new RenderEngine())->forVendor(slug: 'xential'));

		$this->assertSame(expected: 'smartdocuments', actual: $this->service()->vendorSlug(engine: 'vendor:smartdocuments'));
		$this->assertNull(actual: $this->service()->vendorSlug(engine: null));

	}//end testTheEngineNamesFilinqOrAVendorSlug()

	/**
	 * An unknown engine is refused with 400 naming the value.
	 *
	 * @return void
	 */
	public function testAnUnknownEngineIsRefusedNamingIt(): void {
		foreach (['docmosis', 'vendor:', 'vendor:  '] as $engine) {
			try {
				(new RenderEngine())->vendorSlug(engine: $engine);
				$this->fail(message: 'The engine ' . $engine . ' was accepted.');
			} catch (Exception $e) {
				$this->assertSame(expected: 400, actual: $e->getCode());
				$this->assertStringContainsString(needle: '"' . $engine . '"', haystack: $e->getMessage());
			}
		}

		$this->expectExceptionCode(code: 400);
		$this->expectExceptionMessage(message: 'a non-text value');
		(new RenderEngine())->vendorSlug(engine: ['vendor:smartdocuments']);

	}//end testAnUnknownEngineIsRefusedNamingIt()

	/**
	 * A render reaches the source and a pending entry holds integriq's job id.
	 *
	 * @return void
	 */
	public function testARenderReachesTheSourceAndIsRecordedPending(): void {
		$result = $this->service()->render(slug: 'smartdocuments', run: $this->generationRun(), data: ['besluit' => ['uitkomst' => 'toegewezen']]);

		$this->assertCount(expectedCount: 1, haystack: $this->dispatched);
		$command = $this->dispatched[0];
		$this->assertSame(expected: 'src-1', actual: $command->sourceId);
		$this->assertSame(expected: 'besluit-standaard', actual: $command->templateId);
		$this->assertSame(expected: ['besluit' => ['uitkomst' => 'toegewezen']], actual: $command->data);
		$this->assertSame(expected: 'alice', actual: $command->requestedBy);
		$this->assertSame(expected: 'filinq', actual: $command->requestedByApp);

		$this->assertCount(expectedCount: 1, haystack: $this->logged);
		$entry = $this->logged[0];
		$this->assertSame(expected: 'pending', actual: $entry['status']);
		$this->assertSame(expected: 'job-42', actual: $entry['vendorJobId']);
		$this->assertSame(expected: 'vendor:smartdocuments', actual: $entry['engine']);
		$this->assertSame(expected: 'tpl-1', actual: $entry['templateId']);
		$this->assertSame(expected: 3, actual: $entry['templateVersion']);
		$this->assertSame(expected: 'zaak-9', actual: $entry['caseId']);
		$this->assertSame(expected: 'dossier/d-1', actual: $entry['sourceObject']);
		$this->assertNull(actual: $entry['fileId']);

		$this->assertSame(expected: 'pending', actual: $result['status']);
		$this->assertSame(expected: 'job-42', actual: $result['vendorJobId']);
		$this->assertSame(expected: 'vendor:smartdocuments', actual: $result['engine']);
		$this->assertSame(expected: '', actual: $result['content']);
		$this->assertSame(expected: $entry, actual: $result['metadata']);

	}//end testARenderReachesTheSourceAndIsRecordedPending()

	/**
	 * integriq's refusal refuses the call naming the source; nothing is recorded.
	 *
	 * @return void
	 */
	public function testARefusalRefusesTheCallNamingTheSource(): void {
		$service = $this->service(answer: static function (RenderRequestedEventDouble $command): void {
			$command->refuse(reason: 'The template is unknown at the vendor.', code: 'not-configured');
		});

		try {
			$service->render(slug: 'smartdocuments', run: $this->generationRun(), data: []);
			$this->fail(message: 'The refused render was taken.');
		} catch (Exception $e) {
			$this->assertSame(expected: 502, actual: $e->getCode());
			$this->assertStringContainsString(needle: '"smartdocuments" refused the render: The template is unknown at the vendor.', haystack: $e->getMessage());
		}

		$this->assertSame(expected: [], actual: $this->logged);

	}//end testARefusalRefusesTheCallNamingTheSource()

	/**
	 * A command nobody answers is refused; a throwing listener is refused too.
	 *
	 * @return void
	 */
	public function testAnUnansweredOrFailingRequestIsRefused(): void {
		$silent = $this->service(answer: static function (): void {
		});
		try {
			$silent->render(slug: 'smartdocuments', run: $this->generationRun(), data: []);
			$this->fail(message: 'The unanswered render was taken.');
		} catch (Exception $e) {
			$this->assertSame(expected: 502, actual: $e->getCode());
			$this->assertStringContainsString(needle: '"smartdocuments" did not answer', haystack: $e->getMessage());
		}

		$throwing = $this->service(answer: static function (): void {
			throw new RuntimeException('listener broke');
		});
		try {
			$throwing->render(slug: 'smartdocuments', run: $this->generationRun(), data: []);
			$this->fail(message: 'The failing render was taken.');
		} catch (Exception $e) {
			$this->assertSame(expected: 502, actual: $e->getCode());
			$this->assertStringContainsString(needle: 'could not take the render: listener broke', haystack: $e->getMessage());
		}

		$this->assertSame(expected: [], actual: $this->logged);

	}//end testAnUnansweredOrFailingRequestIsRefused()

	/**
	 * Without integriq, or without its event class, the render is refused with 503.
	 *
	 * @return void
	 */
	public function testWithoutIntegriqTheRenderIsRefused(): void {
		foreach ([$this->service(integriq: false), $this->service(event: 'OCA\\Integriq\\Event\\NoSuchEvent')] as $service) {
			try {
				$service->render(slug: 'smartdocuments', run: $this->generationRun(), data: []);
				$this->fail(message: 'The render was taken without integriq.');
			} catch (Exception $e) {
				$this->assertSame(expected: 503, actual: $e->getCode());
				$this->assertStringContainsString(needle: '"smartdocuments"', haystack: $e->getMessage());
			}
		}

		$this->assertSame(expected: [], actual: $this->dispatched);
		$this->assertSame(expected: [], actual: $this->logged);

	}//end testWithoutIntegriqTheRenderIsRefused()

	/**
	 * An unknown slug or a template without a vendor template id is refused with 400.
	 *
	 * @return void
	 */
	public function testAnUnknownSlugOrMissingVendorTemplateIsRefused(): void {
		try {
			$this->service()->render(slug: 'xential', run: $this->generationRun(), data: []);
			$this->fail(message: 'An unknown source was taken.');
		} catch (Exception $e) {
			$this->assertSame(expected: 400, actual: $e->getCode());
			$this->assertStringContainsString(needle: '"xential"', haystack: $e->getMessage());
		}

		try {
			$this->service()->render(slug: 'smartdocuments', run: $this->generationRun(template: ['vendorTemplateId' => ' ']), data: []);
			$this->fail(message: 'A template without a vendor template id was taken.');
		} catch (Exception $e) {
			$this->assertSame(expected: 400, actual: $e->getCode());
			$this->assertStringContainsString(needle: 'no vendorTemplateId', haystack: $e->getMessage());
		}

		$this->assertSame(expected: [], actual: $this->dispatched);

	}//end testAnUnknownSlugOrMissingVendorTemplateIsRefused()

	/**
	 * Only enabled document generation sources with an id and a slug are offered.
	 *
	 * @return void
	 */
	public function testOnlyActiveDocumentGenerationSourcesAreListed(): void {
		$entity = new class {

			/**
			 * The row as OpenRegister serialises an object.
			 *
			 * @return array<string, mixed>
			 */
			public function jsonSerialize(): array {
				return ['@self' => ['id' => 'src-2'], 'slug' => 'xential', 'name' => '', 'type' => 'documentGeneration'];
			}
		};
		$this->sourceRows = [
			['@self' => ['id' => 'src-1'], 'slug' => 'smartdocuments', 'name' => 'SmartDocuments', 'type' => 'documentGeneration', 'isEnabled' => true],
			$entity,
			['id' => 'src-3', 'slug' => 'off', 'type' => 'documentGeneration', 'isEnabled' => false],
			['id' => 'src-4', 'slug' => 'down', 'type' => 'documentGeneration', 'status' => 'disabled'],
			['id' => 'src-5', 'slug' => 'zgw', 'type' => 'api'],
			['id' => 'src-6', 'slug' => '', 'type' => 'documentGeneration'],
			['slug' => 'no-id', 'type' => 'documentGeneration'],
			'not a row',
		];

		$sources = $this->sources();
		$this->assertSame(
			expected: [
				['id' => 'src-1', 'slug' => 'smartdocuments', 'name' => 'SmartDocuments'],
				['id' => 'src-2', 'slug' => 'xential', 'name' => 'xential'],
			],
			actual: $sources->active()
		);
		$this->assertSame(expected: ['register' => 'integriq', 'schema' => 'source', 'filters' => ['type' => 'documentGeneration']], actual: $this->searches[0]);
		$this->assertSame(expected: 'src-2', actual: $sources->bySlug(slug: 'xential')['id']);
		$this->assertNull(actual: $sources->bySlug(slug: 'off'));

	}//end testOnlyActiveDocumentGenerationSourcesAreListed()

	/**
	 * Without integriq no source is read; an unreadable register lists none.
	 *
	 * @return void
	 */
	public function testWithoutIntegriqOrARegisterNoSourceIsListed(): void {
		$this->assertSame(expected: [], actual: $this->sources(integriq: false)->active());
		$this->assertSame(expected: [], actual: $this->searches);

		$resolver = $this->createMock(originalClassName: DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willThrowException(new RuntimeException('OpenRegister service is not available.'));
		$apps = $this->createMock(originalClassName: IAppManager::class);
		$apps->method('isEnabledForAnyone')->willReturn(true);
		$logger = $this->createMock(originalClassName: LoggerInterface::class);
		$logger->expects($this->once())->method('warning');
		$sources = new VendorRenderSources(appManager: $apps, objectResolver: $resolver, logger: $logger);
		$this->assertSame(expected: [], actual: $sources->active());

		$objects = $this->createMock(originalClassName: ObjectService::class);
		$objects->method('searchObjectsBySlug')->willReturn(0);
		$counting = $this->createMock(originalClassName: DocumentObjectServiceResolver::class);
		$counting->method('resolve')->willReturn($objects);
		$this->assertSame(expected: [], actual: (new VendorRenderSources(appManager: $apps, objectResolver: $counting, logger: $this->createMock(originalClassName: LoggerInterface::class)))->active());

	}//end testWithoutIntegriqOrARegisterNoSourceIsListed()

	/**
	 * An outcome integriq announced inside the request is settled right after the entry is written.
	 *
	 * @return void
	 */
	public function testAnOutcomeAnnouncedInsideTheRequestIsSettled(): void {
		$outcome = $this->createMock(originalClassName: VendorRenderOutcome::class);
		$outcome->expects($this->once())->method('settleEarly')->with('job-42')->willReturn(
			['id' => 'entry-1', 'status' => 'generated', 'fileId' => 4711, 'filePath' => '/alice/files/DocuDesk/besluit.pdf']
		);

		$result = $this->service(outcome: $outcome)->render(slug: 'smartdocuments', run: $this->generationRun(), data: []);

		$this->assertSame(expected: 'generated', actual: $result['status']);
		$this->assertSame(expected: 4711, actual: $result['output']['fileId']);
		$this->assertSame(expected: '/alice/files/DocuDesk/besluit.pdf', actual: $result['output']['path']);
		$this->assertSame(expected: 'generated', actual: $result['metadata']['status']);

	}//end testAnOutcomeAnnouncedInsideTheRequestIsSettled()

	/**
	 * A render that failed inside the request is refused naming the source and the vendor's error.
	 *
	 * @return void
	 */
	public function testARenderThatFailedInsideTheRequestIsRefused(): void {
		$outcome = $this->createMock(originalClassName: VendorRenderOutcome::class);
		$outcome->method('settleEarly')->willReturn(['id' => 'entry-1', 'status' => 'failed', 'errorMessage' => 'Template unknown at the vendor']);

		try {
			$this->service(outcome: $outcome)->render(slug: 'smartdocuments', run: $this->generationRun(), data: []);
			$this->fail(message: 'A failed render was answered as a document.');
		} catch (Exception $e) {
			$this->assertSame(expected: 502, actual: $e->getCode());
			$this->assertStringContainsString(needle: '"smartdocuments"', haystack: $e->getMessage());
			$this->assertStringContainsString(needle: 'Template unknown at the vendor', haystack: $e->getMessage());
		}

	}//end testARenderThatFailedInsideTheRequestIsRefused()

	/**
	 * Without an early outcome the render stays pending.
	 *
	 * @return void
	 */
	public function testWithoutAnEarlyOutcomeTheRenderStaysPending(): void {
		$outcome = $this->createMock(originalClassName: VendorRenderOutcome::class);
		$outcome->expects($this->once())->method('settleEarly')->with('job-42')->willReturn(null);

		$result = $this->service(outcome: $outcome)->render(slug: 'smartdocuments', run: $this->generationRun(), data: []);

		$this->assertSame(expected: 'pending', actual: $result['status']);
		$this->assertNull(actual: $result['output']['fileId']);

	}//end testWithoutAnEarlyOutcomeTheRenderStaysPending()
}//end class
