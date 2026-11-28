<?php

/**
 * The Woo decision letter requested through the command event, as dossiq
 * sends it: a REAL DocumentGenerationRequestedEvent into the REAL listener,
 * request service, contract builder, generator and Twig renderer. Only the
 * template store, the data resolver, Files storage and the outgoing
 * dispatcher are doubled.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 * @spec openspec/changes/archive/2026-11-26-woo-request-workflow/specs/woo-request-workflow/spec.md#requirement-the-woo-decision-data-contract-is-checked-before-anything-is-rendered-req-ddwrw-010
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\EventListener;

use OCA\Filinq\Event\DocumentGeneratedEvent;
use OCA\Filinq\Event\DocumentGenerationRequestedEvent;
use OCA\Filinq\EventListener\DocumentGenerationRequestedListener;
use OCA\Filinq\Service\Charts\ChartSvgRenderer;
use OCA\Filinq\Service\Charts\SvgRasterizer;
use OCA\Filinq\Service\Charts\TableHtmlRenderer;
use OCA\Filinq\Service\DataResolverService;
use OCA\Filinq\Service\DocumentGenerationRequestService;
use OCA\Filinq\Service\DocumentJobStore;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\DocumentRenderPipeline;
use OCA\Filinq\Service\DocumentService;
use OCA\Filinq\Service\DocumentStorageService;
use OCA\Filinq\Service\GeneratedDocumentLogger;
use OCA\Filinq\Service\Grounds\RefusalGroundsResolver;
use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\Support\DocumentOutput;
use OCA\Filinq\Service\Support\DocumentProduction;
use OCA\Filinq\Service\Support\PdfRendering;
use OCA\Filinq\Service\Support\TemplateLookup;
use OCA\Filinq\Service\Support\TemplateSources;
use OCA\Filinq\Service\TemplateRenderer;
use OCA\Filinq\Service\TemplateService;
use OCA\Filinq\Service\TemplateSlugResolver;
use OCA\Filinq\Service\Disclosure\DisclosureDecisionContextBuilder;
use OCP\App\IAppManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;

/**
 * `woo-besluit` through the command event.
 *
 * @covers \OCA\Filinq\EventListener\DocumentGenerationRequestedListener
 * @covers \OCA\Filinq\Service\DocumentGenerationRequestService
 * @uses   \OCA\Filinq\Event\DocumentGenerationRequestedEvent
 * @uses   \OCA\Filinq\Event\DocumentGeneratedEvent
 * @uses   \OCA\Filinq\Exception\DisclosureDecisionContractException
 * @uses   \OCA\Filinq\Service\DocumentGenerationRequestRules
 * @uses   \OCA\Filinq\Service\DocumentService
 * @uses   \OCA\Filinq\Service\Disclosure\DisclosureDecisionContextBuilder
 * @uses   \OCA\Filinq\Service\Grounds\RefusalGroundsResolver
 * @uses   \OCA\Filinq\Service\TemplateRenderer
 * @uses   \OCA\Filinq\Service\Charts\ChartSvgRenderer
 * @uses   \OCA\Filinq\Service\Charts\TableHtmlRenderer
 * @uses   \OCA\Filinq\Service\DocumentRenderPipeline
 * @uses   \OCA\Filinq\Service\DocumentGenerationRecord
 * @uses   \OCA\Filinq\Service\GeneratedDocumentLogger
 * @uses   \OCA\Filinq\Service\DocumentObjectServiceResolver
 * @uses   \OCA\Filinq\Service\Support\DocumentOutput
 * @uses   \OCA\Filinq\Service\Support\DocumentProduction
 * @uses   \OCA\Filinq\Service\Support\PdfRendering
 * @uses   \OCA\Filinq\Service\Support\TemplateLookup
 * @uses   \OCA\Filinq\Service\Support\TemplateSources
 */
final class DocumentGenerationRequestedListenerWooTest extends TestCase {

	/**
	 * What was filed, by file name.
	 *
	 * @var array<string, string>
	 */
	private array $filed = [];

	/**
	 * Every event the request service dispatched.
	 *
	 * @var array<int, object>
	 */
	private array $dispatched = [];

	/**
	 * The listener over the real request service and generator.
	 *
	 * @return DocumentGenerationRequestedListener
	 */
	private function listener(): DocumentGenerationRequestedListener {
		$this->filed      = [];
		$this->dispatched = [];

		$register = json_decode((string) file_get_contents(__DIR__.'/../../../lib/Settings/filinq_register.json'), true);
		$seeded   = [];
		foreach ($register['components']['objects'] as $object) {
			if (($object['@self']['schema'] ?? '') === 'template') {
				$seeded[(string) $object['@self']['slug']] = $object + ['id' => 'template-'.$object['@self']['slug']];
			}
		}

		$slugs = $this->createMock(TemplateSlugResolver::class);
		$slugs->method('resolve')->willReturnCallback(static function (string $namespace, string $slug) use ($seeded): array {
			self::assertSame('woo', $namespace);
			return $seeded[$slug];
		});

		$apps = $this->createMock(IAppManager::class);
		$apps->method('isEnabledForAnyone')->willReturn(false);
		$apps->method('getInstalledApps')->willReturn([]);
		$objects  = new DocumentObjectServiceResolver($this->createMock(ContainerInterface::class), $apps);
		$renderer = new TemplateRenderer(new NullLogger(), new ChartSvgRenderer(), new TableHtmlRenderer());

		$data = $this->getMockBuilder(DataResolverService::class)->disableOriginalConstructor()->onlyMethods(['resolve'])->getMock();
		$data->method('resolve')->willReturnCallback(static fn (array $dataRefs, array $listRefs=[], array $adHocData=[]): array => ['data' => $adHocData, 'warnings' => [], 'errors' => []]);
		$storage = $this->getMockBuilder(DocumentStorageService::class)->disableOriginalConstructor()->onlyMethods(['store'])->getMock();
		$storage->method('store')->willReturnCallback(function (string $userId, string $targetPath, string $filename, string $content): array {
			$this->filed[$filename] = $content;
			return ['fileId' => 77, 'path' => $targetPath.'/'.$filename, 'name' => $filename, 'size' => strlen($content)];
		});
		$rasterizer = $this->createMock(SvgRasterizer::class);
		$rasterizer->method('rasterizeInlineSvg')->willReturnCallback(static fn (string $html): array => ['html' => $html, 'warnings' => []]);
		$templates = $this->createMock(TemplateService::class);

		$documents = new DocumentService(
			new TemplateSources(templates: $templates, data: $data),
			new DocumentProduction(pipeline: new DocumentRenderPipeline(
				new PdfRendering(renderer: $renderer, pdf: $this->createMock(PdfService::class)),
				$objects,
				new NullLogger(),
				$rasterizer
			), jobs: $this->createMock(DocumentJobStore::class)),
			new DocumentOutput(storage: $storage, log: new GeneratedDocumentLogger($objects, new NullLogger()))
		);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('behandelaar');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);
		$dispatcher = $this->createMock(IEventDispatcher::class);
		$dispatcher->method('dispatchTyped')->willReturnCallback(function (object $event): void {
			$this->dispatched[] = $event;
		});

		$service = new DocumentGenerationRequestService(
			documents: $documents,
			lookup: new TemplateLookup(templates: $templates, slugs: $slugs),
			renderer: $renderer,
			objectResolver: $objects,
			userSession: $session,
			eventDispatcher: $dispatcher,
			wooDecisions: new DisclosureDecisionContextBuilder(
				grounds: new RefusalGroundsResolver(
					appManager: $apps,
					container: $this->createMock(originalClassName: ContainerInterface::class),
					logger: new NullLogger()
				)
			)
		);

		return new DocumentGenerationRequestedListener(generator: $service, logger: new NullLogger());

	}//end listener()

	/**
	 * The request dossiq sends for the letter.
	 *
	 * @param array<string, mixed> $wooDecision The decision.
	 *
	 * @return DocumentGenerationRequestedEvent
	 */
	private static function request(array $wooDecision): DocumentGenerationRequestedEvent {
		return new DocumentGenerationRequestedEvent(
			request: [
				'templateSlug' => 'woo-besluit',
				'templateNamespace' => 'woo',
				'format' => 'html',
				'filename' => 'Woo-besluit',
				'data' => ['wooDecision' => $wooDecision],
			],
			requestingApp: 'dossiq'
		);

	}//end request()

	/**
	 * The fixture decision.
	 *
	 * @return array<string, mixed>
	 */
	private static function fixture(): array {
		return json_decode((string) file_get_contents(__DIR__.'/../../fixtures/woo-decision.json'), true);

	}//end fixture()

	/**
	 * The fixture is filed as a letter and comes back on the event.
	 *
	 * @return void
	 */
	public function testTheLetterIsFiledAndHandedBack(): void {
		$event = self::request(self::fixture());

		$this->listener()->handle($event);

		$this->assertNull($event->getError());
		$this->assertTrue($event->isHandled());
		$this->assertSame(77, $event->getResult()['fileId']);
		$this->assertCount(1, $this->filed);
		$letter = (string) array_values($this->filed)[0];
		$this->assertStringContainsString('Kenmerk: WOO-2026-0042', $letter);
		$this->assertStringContainsString('Eerbiediging van de persoonlijke levenssfeer (artikel 5.1 Woo, grond 5.1.2.e)', $letter);
		$this->assertCount(1, $this->dispatched);
		$this->assertInstanceOf(DocumentGeneratedEvent::class, $this->dispatched[0]);
		$this->assertSame('dossiq', $this->dispatched[0]->getRequestingApp());

	}//end testTheLetterIsFiledAndHandedBack()

	/**
	 * A withheld document without a ground is refused on the event, and nothing is filed.
	 *
	 * @return void
	 */
	public function testAWithheldDocumentWithoutAGroundIsRefusedAndNothingIsFiled(): void {
		$decision = self::fixture();
		$decision['documents'][2]['groundCodes'] = [];
		$event = self::request($decision);

		$this->listener()->handle($event);

		$this->assertFalse($event->isHandled());
		$this->assertNull($event->getResult());
		$this->assertStringContainsString('Document 3 is assessed withhold but cites no ground.', (string) $event->getError());
		$this->assertSame([], $this->filed);
		$this->assertSame([], $this->dispatched);

	}//end testAWithheldDocumentWithoutAGroundIsRefusedAndNothingIsFiled()
}//end class
