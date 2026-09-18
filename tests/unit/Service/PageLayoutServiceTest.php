<?php

/**
 * Unit tests for PageLayoutService
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
 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
 */

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\PageLayoutService;
use OCA\OpenRegister\Service\ObjectService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Asserts that a layout edit makes a new version and rewrites no history: the
 * besluit sent in March still names March's paper.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class PageLayoutServiceTest extends TestCase {

	/**
	 * Layouts the fake OpenRegister was asked to store.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $written = [];

	/**
	 * The uuids those writes were addressed to.
	 *
	 * @var array<int, string|null>
	 */
	private array $writtenTo = [];

	/**
	 * Build the service over a register holding the given layout versions.
	 *
	 * @param array<int, array<string, mixed>> $versions The stored versions.
	 *
	 * @return PageLayoutService The service under test.
	 */
	private function service(array $versions): PageLayoutService {
		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('searchObjects')->willReturnCallback(
			static function (array $query) use ($versions): array {
				$name = (string)($query['name'] ?? '');

				return array_values(
					array_filter(
						$versions,
						static fn (array $row): bool => ((string)($row['name'] ?? '') === $name)
					)
				);
			}
		);
		$objectService->method('saveObject')->willReturnCallback(
			function (...$arguments): array {
				$object = ($arguments[0] ?? []);
				$this->written[] = $object;
				$this->writtenTo[] = ($arguments[3] ?? null);

				return ($object + ['uuid' => ($arguments[3] ?? 'layout-new')]);
			}
		);

		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objectService);

		return new PageLayoutService($resolver, $this->createMock(LoggerInterface::class));

	}//end service()

	/**
	 * Version 2 of the briefpapier.
	 *
	 * @return array<string, mixed> The layout.
	 */
	private function versionTwo(): array {
		return [
			'uuid' => 'layout-2',
			'name' => 'Gemeente, besluit',
			'layoutVersion' => 2,
			'paperSize' => 'A4',
			'orientation' => 'portrait',
			'margins' => ['top' => 35, 'right' => 20, 'bottom' => 25, 'left' => 25],
			'header' => 'Gemeente',
			'footer' => 'Pagina {{page}}',
			'firstPageDiffers' => true,
			'firstPageFooter' => 'Postbus 1',
			'active' => true,
		];

	}//end versionTwo()

	/**
	 * A template naming a version gets that version, even after a newer one exists.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function testATemplateNamingAVersionGetsThatVersion(): void {
		$three = ($this->versionTwo() + []);
		$three['uuid'] = 'layout-3';
		$three['layoutVersion'] = 3;
		$two = ($this->versionTwo() + []);
		$two['active'] = false;

		$service = $this->service(versions: [$two, $three]);

		$this->assertSame('layout-2', $service->resolve(name: 'Gemeente, besluit', version: 2)['uuid']);
		$this->assertSame('layout-3', $service->resolve(name: 'Gemeente, besluit')['uuid'], 'No version named means the active one.');

	}//end testATemplateNamingAVersionGetsThatVersion()

	/**
	 * Editing a layout writes a new version and leaves the old one readable.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function testEditingALayoutWritesANewVersion(): void {
		$service = $this->service(versions: [$this->versionTwo()]);

		$next = $service->edit(name: 'Gemeente, besluit', changes: ['footer' => 'Pagina {{page}} van {{pages}}']);

		$this->assertSame(3, $next['layoutVersion']);
		$this->assertSame('layout-2', $next['supersedes']);
		$this->assertSame('Pagina {{page}} van {{pages}}', $next['footer']);

		// Two writes: the new version, and the old one marked inactive. The old
		// one is UPDATED, not replaced, so documents naming it still resolve.
		$this->assertCount(2, $this->written);
		$this->assertSame([null, 'layout-2'], $this->writtenTo);
		$this->assertFalse($this->written[1]['active']);
		$this->assertSame(2, $this->written[1]['layoutVersion'], 'The old version keeps its number.');

	}//end testEditingALayoutWritesANewVersion()

	/**
	 * An edit cannot set the version or the supersedes itself.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function testAnEditCannotChooseItsOwnVersionNumber(): void {
		$service = $this->service(versions: [$this->versionTwo()]);

		$next = $service->edit(
			name: 'Gemeente, besluit',
			changes: ['layoutVersion' => 99, 'supersedes' => 'iets-anders', 'header' => 'Gemeente Voorbeeld']
		);

		$this->assertSame(3, $next['layoutVersion']);
		$this->assertSame('layout-2', $next['supersedes']);

	}//end testAnEditCannotChooseItsOwnVersionNumber()

	/**
	 * Editing a layout nobody declared is refused.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function testEditingALayoutThatDoesNotExistIsRefused(): void {
		$service = $this->service(versions: []);

		$this->expectException(RuntimeException::class);
		$service->edit(name: 'Bestaat niet', changes: ['header' => 'x']);

	}//end testEditingALayoutThatDoesNotExistIsRefused()

	/**
	 * A generated document records the layout version it used.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function testAGeneratedDocumentRecordsTheLayoutVersion(): void {
		$service = $this->service(versions: [$this->versionTwo()]);

		$document = $service->stamp(document: ['templateName' => 'Besluit'], layout: $this->versionTwo());

		$this->assertSame('layout-2', $document['layoutId']);
		$this->assertSame(2, $document['layoutVersion']);

	}//end testAGeneratedDocumentRecordsTheLayoutVersion()

	/**
	 * The layout becomes the options the renderer takes, first page included.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function testTheLayoutBecomesRenderOptions(): void {
		$service = $this->service(versions: []);

		$options = $service->pdfOptions(layout: $this->versionTwo());

		$this->assertSame('A4', $options['format']);
		$this->assertSame('P', $options['orientation']);
		$this->assertSame(35, $options['margin']['top']);
		$this->assertSame('Postbus 1', $options['firstPage']['footer']);

	}//end testTheLayoutBecomesRenderOptions()

	/**
	 * A layout whose first page does not differ carries no first-page options.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function testALayoutWithNoSeparateFirstPageSaysSo(): void {
		$layout = ($this->versionTwo() + []);
		$layout['firstPageDiffers'] = false;

		$options = $this->service(versions: [])->pdfOptions(layout: $layout);

		$this->assertArrayNotHasKey('firstPage', $options);

	}//end testALayoutWithNoSeparateFirstPageSaysSo()
}//end class
