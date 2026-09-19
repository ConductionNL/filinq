<?php

/**
 * Generation with a plain-language counterpart, end to end through DocumentService.
 *
 * 🔴 THE ORDERING IS THE WHOLE POINT AND IT IS INVISIBLE IN THE UNIT UNDER IT.
 * PlainLanguageRenditionService can refuse all it likes; whether the formal
 * letter is already on disk when it does depends on WHERE DocumentService calls
 * it, and nothing in that service's own tests can see the difference. So the
 * refusal test here asserts that the storage was never touched, which is the
 * only place "neither rendition is filed" is actually decided.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
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

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Exception\PlainRenditionRefusedException;
use OCA\Filinq\Service\DataResolverService;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\DocumentRenderPipeline;
use OCA\Filinq\Service\DocumentService;
use OCA\Filinq\Service\DocumentStorageService;
use OCA\Filinq\Service\GeneratedDocumentLogger;
use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\PlainLanguageRenditionService;
use OCA\Filinq\Service\PlainRenditionAcceptanceGate;
use OCA\Filinq\Service\TemplateRenderer;
use OCA\Filinq\Service\TemplateService;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use OCP\BackgroundJob\IJobList;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;

/**
 * `DocumentService` with a plain-language counterpart.
 *
 * @covers \OCA\Filinq\Service\DocumentService
 *
 * @uses \OCA\Filinq\Exception\PlainRenditionRefusedException
 * @uses \OCA\Filinq\Service\DocumentObjectServiceResolver
 * @uses \OCA\Filinq\Service\DocumentRenderPipeline
 * @uses \OCA\Filinq\Service\GeneratedDocumentLogger
 * @uses \OCA\Filinq\Service\PlainLanguageRenditionService
 * @uses \OCA\Filinq\Service\PlainRenditionAcceptanceGate
 */
class DocumentServicePlainRenditionTest extends TestCase {

	/**
	 * The template store double.
	 *
	 * @var TemplateService
	 */
	private TemplateService $templates;

	/**
	 * The storage double, which the refusal test asserts is never touched.
	 *
	 * @var DocumentStorageService
	 */
	private DocumentStorageService $storage;

	/**
	 * The formal template, declaring a plain counterpart.
	 *
	 * @var array<string, mixed>
	 */
	private array $formalTemplate = [
		'name' => 'Besluit',
		'content' => 'De formele tekst.',
		'namespace' => 'besluiten',
		'version' => 3,
		'plainLanguage' => [
			'templateId' => 'plain-besluit',
			'requiredStatements' => ['besluit', 'bezwaartermijn'],
			'source' => 'template',
		],
	];

	/**
	 * Build a service whose collaborators answer as told.
	 *
	 * @param array<string, mixed> $data The resolved generation data.
	 *
	 * @return DocumentService The service.
	 */
	private function service(array $data): DocumentService {
		$this->templates = $this->getMockBuilder(TemplateService::class)
			->disableOriginalConstructor()
			->onlyMethods(['getTemplate'])
			->getMock();
		$this->templates->method('getTemplate')->willReturnCallback(
			fn (string $id): array => ($id === 'plain-besluit'
				? ['name' => 'Besluit in gewone taal', 'content' => 'Wij hebben nee gezegd.', 'namespace' => 'besluiten']
				: $this->formalTemplate)
		);

		$resolver = $this->getMockBuilder(DataResolverService::class)
			->disableOriginalConstructor()
			->onlyMethods(['resolve'])
			->getMock();
		$resolver->method('resolve')->willReturn(['data' => $data, 'warnings' => [], 'errors' => []]);

		$renderer = $this->createMock(TemplateRenderer::class);
		$renderer->method('renderTemplate')->willReturnCallback(
			static fn (string $content): string => '<p>' . $content . '</p>'
		);
		$renderer->method('getLastRenderWarnings')->willReturn([]);

		$this->storage = $this->getMockBuilder(DocumentStorageService::class)
			->disableOriginalConstructor()
			->onlyMethods(['store'])
			->getMock();
		$this->storage->method('store')->willReturnCallback(
			static fn (string $userId, string $targetPath, string $filename): array => [
				'fileId' => (str_contains($filename, 'gewone-taal') === true ? 42 : 41),
				'path' => $targetPath . '/' . $filename,
				'name' => $filename,
				'size' => 100,
			]
		);

		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturn(null);
		$appManager = $this->createMock(IAppManager::class);
		$appManager->method('getInstalledApps')->willReturn([]);
		$objectResolver = new DocumentObjectServiceResolver($container, $appManager);

		return new DocumentService(
			$this->templates,
			$resolver,
			new DocumentRenderPipeline(
				$renderer,
				$this->createMock(PdfService::class),
				$objectResolver,
				new NullLogger()
			),
			$this->storage,
			new GeneratedDocumentLogger($objectResolver, new NullLogger()),
			$container,
			$this->createMock(IJobList::class),
			new NullLogger(),
			new PlainLanguageRenditionService(
				$this->templates,
				new PlainRenditionAcceptanceGate(),
				new NullLogger()
			)
		);
	}//end service()

	/**
	 * Both renditions come out of one generation, and the plain one names the formal document.
	 *
	 * @return void
	 */
	public function testBothRenditionsComeFromOneGeneration(): void {
		$service = $this->service(['besluit' => 'afgewezen', 'bezwaartermijn' => '6 weken']);

		$result = $service->generateDocument(
			templateId: 'formal-besluit',
			dataRefs: [],
			options: [
				'format' => 'html',
				'userId' => 'anne',
				'filename' => 'besluit',
				'output' => ['mode' => 'files', 'targetPath' => 'DocuDesk/besluiten'],
			]
		);

		self::assertNotNull($result['plainRendition']);
		self::assertSame(42, $result['plainRendition']['fileId']);
		self::assertSame('plain-besluit', $result['plainRendition']['templateId']);

		// The plain letter names the formal document it explains. Somebody gets
		// two letters about one decision; without this they cannot be matched.
		self::assertSame($result['output']['path'], $result['plainRendition']['explains']);
		self::assertStringContainsString('gewone-taal', (string)$result['plainRendition']['path']);
	}//end testBothRenditionsComeFromOneGeneration()

	/**
	 * A template with no counterpart generates the formal rendition only.
	 *
	 * @return void
	 */
	public function testNoCounterpartGeneratesTheFormalRenditionOnly(): void {
		unset($this->formalTemplate['plainLanguage']);
		$service = $this->service([]);

		$result = $service->generateDocument(
			templateId: 'formal-besluit',
			dataRefs: [],
			options: ['format' => 'html', 'userId' => 'anne']
		);

		self::assertNull($result['plainRendition']);
	}//end testNoCounterpartGeneratesTheFormalRenditionOnly()

	/**
	 * An unresolved statement refuses the generation before anything is filed.
	 *
	 * @return void
	 */
	public function testARefusalFilesNeitherRendition(): void {
		$service = $this->service(['besluit' => 'afgewezen']);

		// 🔴 THIS IS THE ASSERTION THAT MATTERS. The refusal itself is asserted
		// in PlainLanguageRenditionServiceTest; what only this test can see is
		// that the formal letter never reached the folder.
		$this->storage->expects(self::never())->method('store');

		$this->expectException(PlainRenditionRefusedException::class);
		$this->expectExceptionMessage('bezwaartermijn');

		$service->generateDocument(
			templateId: 'formal-besluit',
			dataRefs: [],
			options: [
				'format' => 'html',
				'userId' => 'anne',
				'output' => ['mode' => 'files', 'targetPath' => 'DocuDesk/besluiten'],
			]
		);
	}//end testARefusalFilesNeitherRendition()
}//end class
