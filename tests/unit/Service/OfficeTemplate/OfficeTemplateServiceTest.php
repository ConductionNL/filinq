<?php

/**
 * An uploaded office file becomes a template only after every check passes:
 * the file, the ODT conversion, the tags against the bound schema; a refusal
 * stores nothing and creates nothing.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Service\OfficeTemplate
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\OfficeTemplate;

use Exception;
use OCA\Filinq\Exception\OfficeSourceRefusedException;
use OCA\Filinq\Kernel\Contracts\OpenRegisterResolverInterface;
use OCA\Filinq\Kernel\Contracts\TemplateServiceInterface;
use OCA\Filinq\Service\Conversion\LibreOfficeHeadlessBackend;
use OCA\Filinq\Service\OfficeTemplate\MergeTagClassifier;
use OCA\Filinq\Service\OfficeTemplate\MergeTagReader;
use OCA\Filinq\Service\OfficeTemplate\OfficeSourceGuard;
use OCA\Filinq\Service\OfficeTemplate\OfficeSourceStore;
use OCA\Filinq\Service\OfficeTemplate\OfficeSourceTools;
use OCA\Filinq\Service\OfficeTemplate\OfficeTemplateService;
use OCA\OpenRegister\Db\SchemaMapper;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

require_once __DIR__.'/OfficeFixtures.php';

/**
 * @covers \OCA\Filinq\Service\OfficeTemplate\OfficeTemplateService
 * @uses   \OCA\Filinq\Service\OfficeTemplate\OfficeSourceGuard
 * @uses   \OCA\Filinq\Service\OfficeTemplate\OfficeSourceTools
 * @uses   \OCA\Filinq\Service\OfficeTemplate\MergeTagReader
 * @uses   \OCA\Filinq\Service\OfficeTemplate\MergeTagClassifier
 * @uses   \OCA\Filinq\Exception\OfficeSourceRefusedException
 */
class OfficeTemplateServiceTest extends TestCase {

	private OfficeSourceStore&MockObject $store;

	private LibreOfficeHeadlessBackend&MockObject $libreOffice;

	private TemplateServiceInterface&MockObject $templates;

	private OpenRegisterResolverInterface&MockObject $resolver;

	private SchemaMapper&MockObject $schemas;

	/**
	 * Settings by key.
	 *
	 * @var array<string, string>
	 */
	private array $settings = [];

	private OfficeTemplateService $service;

	/**
	 * A service with the real guard, reader and classifier over mocked storage,
	 * conversion, template creation and schema lookup.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->store = $this->createMock(OfficeSourceStore::class);
		$this->libreOffice = $this->createMock(LibreOfficeHeadlessBackend::class);
		$this->templates = $this->createMock(TemplateServiceInterface::class);
		$this->resolver = $this->createMock(OpenRegisterResolverInterface::class);
		$this->schemas = $this->createMock(SchemaMapper::class);
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(
			fn (string $app, string $key, string $default): string => ($app === 'filinq' ? ($this->settings[$key] ?? $default) : $default)
		);
		$this->service = new OfficeTemplateService(
			new OfficeSourceTools(
				guard: new OfficeSourceGuard(),
				reader: new MergeTagReader(),
				classifier: new MergeTagClassifier(),
				store: $this->store,
				libreOffice: $this->libreOffice
			),
			$this->templates,
			$this->resolver,
			$this->schemas,
			$config
		);
	}//end setUp()

	/**
	 * A `dossier` schema whose properties are aanvrager (with naam) and besluit (with datum).
	 *
	 * @return void
	 */
	private function bindDossierSchema(): void {
		$schema = new class {
			/**
			 * The properties.
			 *
			 * @return array<string, mixed>
			 */
			public function getProperties(): array {
				return [
					'aanvrager' => ['type' => 'object', 'properties' => ['naam' => ['type' => 'string']]],
					'besluit' => ['type' => 'object', 'properties' => ['datum' => ['type' => 'string']]],
				];
			}//end getProperties()
		};
		$this->schemas->method('find')->with('dossier')->willReturn($schema);
	}//end bindDossierSchema()

	/**
	 * A DOCX bound to a schema becomes an office template with its source, tags and report.
	 *
	 * @return void
	 */
	public function testADocxBecomesAnOfficeTemplate(): void {
		$this->bindDossierSchema();
		$docx = OfficeFixtures::docx(paragraphs: ['Geachte ${aanvrager.naam},', 'Besluit van ${besluit.datum}.']);
		$this->resolver->expects($this->once())->method('validateNamespace')->with('filinq')->willReturn(true);
		$this->libreOffice->expects($this->never())->method('convertDocument');
		$this->store->expects($this->once())->method('store')->with($docx, 'docx', '')->willReturn(['fileId' => 42, 'contentHash' => 'abc']);
		$this->templates->expects($this->once())->method('createTemplate')->willReturnCallback(
			static fn (array $data): array => ['id' => 'uuid-1'] + $data
		);

		$result = $this->service->create(
			fields: ['name' => 'Beschikking', 'namespace' => 'filinq', 'boundRegister' => 'dossier', 'boundSchema' => 'dossier', 'templateType' => 'twig', 'lockedBy' => 'mallory'],
			fileName: 'beschikking.docx',
			bytes: $docx
		);

		$template = $result['template'];
		$this->assertFalse($result['converted']);
		$this->assertSame('uuid-1', $template['id']);
		$this->assertSame('office', $template['templateType']);
		$this->assertSame(42, $template['sourceFileId']);
		$this->assertSame('abc', $template['contentHash']);
		$this->assertSame([['path' => 'aanvrager.naam'], ['path' => 'besluit.datum']], $template['mergeFields']);
		$this->assertSame("Geachte \${aanvrager.naam},\nBesluit van \${besluit.datum}.", $template['content']);
		$this->assertSame('dossier', $template['boundSchema']);
		$this->assertArrayNotHasKey('lockedBy', $template);
		$this->assertTrue($result['tagReport']['validated']);
		$this->assertSame([], $result['tagReport']['unknown']);
		$this->assertSame($result['tagReport'], $template['tagReport']);
	}//end testADocxBecomesAnOfficeTemplate()

	/**
	 * Under the default warning severity an unknown tag is reported and the template is made.
	 *
	 * @return void
	 */
	public function testAnUnknownTagIsReportedUnderWarning(): void {
		$this->bindDossierSchema();
		$this->store->method('store')->willReturn(['fileId' => 1, 'contentHash' => 'h']);
		$this->templates->expects($this->once())->method('createTemplate')->willReturnArgument(0);

		$result = $this->service->create(
			fields: ['name' => 'Beschikking', 'namespace' => 'filinq', 'boundSchema' => 'dossier'],
			fileName: 'b.docx',
			bytes: OfficeFixtures::docx(paragraphs: ['${aanvraagr.naam} ${aanvrager.naam}'])
		);

		$this->assertSame(['aanvraagr.naam'], $result['tagReport']['unknown']);
		$this->assertSame('warning', $result['tagReport']['severity']);
	}//end testAnUnknownTagIsReportedUnderWarning()

	/**
	 * Under blocking severity an unknown tag refuses the upload: nothing is stored or created.
	 *
	 * @return void
	 */
	public function testBlockingSeverityRefusesUnknownTagsAndStoresNothing(): void {
		$this->bindDossierSchema();
		$this->settings[OfficeTemplateService::SEVERITY_KEY] = 'blocking';
		$this->store->expects($this->never())->method('store');
		$this->templates->expects($this->never())->method('createTemplate');

		try {
			$this->service->create(
				fields: ['name' => 'Beschikking', 'namespace' => 'filinq', 'boundSchema' => 'dossier'],
				fileName: 'b.docx',
				bytes: OfficeFixtures::docx(paragraphs: ['${aanvraagr.naam} ${zaak}'])
			);
			$this->fail('Expected a refusal.');
		} catch (OfficeSourceRefusedException $e) {
			$this->assertSame(422, $e->getCode());
			$this->assertSame(['reason' => 'unknownTags', 'unknownTags' => ['aanvraagr.naam', 'zaak']], $e->getErrorDetails());
			$this->assertSame('The file has merge tags the schema does not know: aanvraagr.naam, zaak.', $e->getMessage());
		}
	}//end testBlockingSeverityRefusesUnknownTagsAndStoresNothing()

	/**
	 * A template without a binding is never blocked, and a missing schema is said so.
	 *
	 * @return void
	 */
	public function testAnUnboundTemplateIsNeverBlocked(): void {
		$this->settings[OfficeTemplateService::SEVERITY_KEY] = 'blocking';
		$this->schemas->method('find')->willThrowException(new RuntimeException('no such schema'));
		$this->store->method('store')->willReturn(['fileId' => 1, 'contentHash' => 'h']);
		$this->templates->method('createTemplate')->willReturnArgument(0);
		$docx = OfficeFixtures::docx(paragraphs: ['${wat.dan.ook}']);

		$unbound = $this->service->create(fields: ['name' => 'B', 'namespace' => 'filinq'], fileName: 'b.docx', bytes: $docx);
		$this->assertFalse($unbound['tagReport']['validated']);
		$this->assertSame('Not validated against a schema: the template is not bound to one.', $unbound['tagReport']['notice']);

		$missing = $this->service->create(fields: ['name' => 'B', 'namespace' => 'filinq', 'boundSchema' => 'weg'], fileName: 'b.docx', bytes: $docx);
		$this->assertFalse($missing['tagReport']['validated']);
		$this->assertSame('Not validated: the schema "weg" was not found.', $missing['tagReport']['notice']);
	}//end testAnUnboundTemplateIsNeverBlocked()

	/**
	 * An ODT is converted to DOCX, the DOCX is the source, the ODT is kept beside it, and the result says so.
	 *
	 * @return void
	 */
	public function testAnOdtIsConvertedAndKept(): void {
		$odt = OfficeFixtures::bareOdt();
		$docx = OfficeFixtures::docx(paragraphs: ['${zaaknummer}']);
		$this->libreOffice->expects($this->once())->method('convertDocument')->with($odt, 'odt', 'docx')->willReturn($docx);
		$stored = [];
		$this->store->expects($this->exactly(2))->method('store')->willReturnCallback(
			static function (string $bytes, string $extension, string $name='') use (&$stored): array {
				$stored[] = [$bytes, $extension, $name];
				return ['fileId' => count($stored), 'contentHash' => 'hash-'.$extension];
			}
		);
		$this->templates->method('createTemplate')->willReturnArgument(0);

		$result = $this->service->create(fields: ['name' => 'Brief', 'namespace' => 'filinq'], fileName: 'brief.odt', bytes: $odt);

		$this->assertTrue($result['converted']);
		$this->assertSame([[$docx, 'docx', ''], [$odt, 'odt', 'hash-docx']], $stored);
		$this->assertSame(1, $result['template']['sourceFileId']);
		$this->assertSame([['path' => 'zaaknummer']], $result['template']['mergeFields']);
	}//end testAnOdtIsConvertedAndKept()

	/**
	 * A refused file creates nothing; the configured size cap applies.
	 *
	 * @return void
	 */
	public function testARefusedFileCreatesNothing(): void {
		$this->settings[OfficeTemplateService::MAX_BYTES_KEY] = '10';
		$this->store->expects($this->never())->method('store');
		$this->templates->expects($this->never())->method('createTemplate');

		foreach ([['b.docm', OfficeFixtures::bareDocx(), 'macro'], ['b.docx', OfficeFixtures::bareDocx(), 'size']] as [$name, $bytes, $reason]) {
			try {
				$this->service->create(fields: ['name' => 'B', 'namespace' => 'filinq'], fileName: $name, bytes: $bytes);
				$this->fail('Expected '.$name.' to be refused.');
			} catch (OfficeSourceRefusedException $e) {
				$this->assertSame($reason, $e->getReason());
			}
		}
	}//end testARefusedFileCreatesNothing()

	/**
	 * Name and namespace are required before the file is looked at.
	 *
	 * @return void
	 */
	public function testNameAndNamespaceAreRequired(): void {
		$this->store->expects($this->never())->method('store');
		foreach ([[['namespace' => 'filinq'], 'Name is required'], [['name' => 'B', 'namespace' => ' '], 'Namespace is required']] as [$fields, $message]) {
			try {
				$this->service->create(fields: $fields, fileName: 'b.docx', bytes: 'x');
				$this->fail('Expected '.$message.'.');
			} catch (OfficeSourceRefusedException $e) {
				$this->fail('Refused the file before checking the fields.');
			} catch (Exception $e) {
				$this->assertSame(400, $e->getCode());
				$this->assertSame($message, $e->getMessage());
			}
		}
	}//end testNameAndNamespaceAreRequired()

	/**
	 * A DOCX without text is stored with its name as content, which a template requires.
	 *
	 * @return void
	 */
	public function testADocxWithoutTextUsesItsNameAsContent(): void {
		$this->store->method('store')->willReturn(['fileId' => 1, 'contentHash' => 'h']);
		$this->templates->method('createTemplate')->willReturnArgument(0);

		$result = $this->service->create(fields: ['name' => 'Leeg', 'namespace' => 'filinq'], fileName: 'leeg.docx', bytes: OfficeFixtures::docx(paragraphs: []));

		$this->assertSame('Leeg', $result['template']['content']);
		$this->assertSame([], $result['template']['mergeFields']);
	}//end testADocxWithoutTextUsesItsNameAsContent()
	/**
	 * A new source becomes a new version of the template: checked against the
	 * template's own bound schema, stored, and saved through the versioning
	 * update with its changelog.
	 *
	 * @return void
	 */
	public function testANewSourceIsSavedAsANewVersion(): void {
		$this->bindDossierSchema();
		$docx = OfficeFixtures::docx(paragraphs: ['Geachte ${aanvrager.naam} ${aanvraagr.naam}']);
		$this->templates->method('getTemplate')->with('t-1')->willReturn(['id' => 't-1', 'name' => 'Brief', 'templateType' => 'office', 'boundSchema' => 'dossier']);
		$this->store->expects($this->once())->method('store')->with($docx, 'docx', '')->willReturn(['fileId' => 77, 'contentHash' => 'def']);
		$this->templates->expects($this->once())->method('updateTemplate')
			->with(
				't-1',
				$this->callback(
					static fn (array $data): bool => $data['sourceFileId'] === 77
						&& $data['contentHash'] === 'def'
						&& $data['templateType'] === 'office'
						&& $data['mergeFields'] === [['path' => 'aanvrager.naam'], ['path' => 'aanvraagr.naam']]
						&& $data['tagReport']['unknown'] === ['aanvraagr.naam']
						&& $data['content'] === 'Geachte ${aanvrager.naam} ${aanvraagr.naam}'
						&& $data['_changelog'] === 'Nieuwe huisstijl'
				)
			)
			->willReturn(['id' => 't-1', 'sourceFileId' => 77]);

		$result = $this->service->replaceSource(id: 't-1', fileName: 'brief.docx', bytes: $docx, changelog: ' Nieuwe huisstijl ');

		$this->assertSame(['id' => 't-1', 'sourceFileId' => 77], $result['template']);
		$this->assertFalse($result['converted']);
		$this->assertSame(['aanvraagr.naam'], $result['tagReport']['unknown']);
	}//end testANewSourceIsSavedAsANewVersion()

	/**
	 * A Twig template takes no source file, and nothing is stored.
	 *
	 * @return void
	 */
	public function testATwigTemplateTakesNoSource(): void {
		$this->templates->method('getTemplate')->willReturn(['id' => 't-2', 'content' => '{{ x }}']);
		$this->store->expects($this->never())->method('store');
		$this->templates->expects($this->never())->method('updateTemplate');

		try {
			$this->service->replaceSource(id: 't-2', fileName: 'brief.docx', bytes: OfficeFixtures::docx(paragraphs: ['x']));
			$this->fail('A Twig template took a source file.');
		} catch (\Exception $e) {
			$this->assertSame(['Only an office template takes a new source file', 409], [$e->getMessage(), $e->getCode()]);
		}
	}//end testATwigTemplateTakesNoSource()

	/**
	 * Under blocking severity a new source with unknown tags is refused and
	 * the template is not touched.
	 *
	 * @return void
	 */
	public function testABlockedNewSourceLeavesTheTemplateAlone(): void {
		$this->bindDossierSchema();
		$this->settings[OfficeTemplateService::SEVERITY_KEY] = 'blocking';
		$this->templates->method('getTemplate')->willReturn(['id' => 't-1', 'templateType' => 'office', 'boundSchema' => 'dossier']);
		$this->store->expects($this->never())->method('store');
		$this->templates->expects($this->never())->method('updateTemplate');

		$this->expectException(OfficeSourceRefusedException::class);

		$this->service->replaceSource(id: 't-1', fileName: 'brief.docx', bytes: OfficeFixtures::docx(paragraphs: ['${onbekend}']));
	}//end testABlockedNewSourceLeavesTheTemplateAlone()
	/**
	 * A field mapping is checked against the template's tags and saved as a
	 * template change; an empty path drops that tag's mapping.
	 *
	 * @return void
	 */
	public function testAFieldMappingIsCheckedAndSaved(): void {
		$this->templates->method('getTemplate')->willReturn(['id' => 't-1', 'templateType' => 'office', 'mergeFields' => [['path' => 'naam_aanvrager'], ['path' => 'datum']]]);
		$this->templates->expects($this->once())->method('updateTemplate')
			->with('t-1', ['fieldMap' => (object) ['naam_aanvrager' => 'aanvrager.naam'], '_changelog' => 'Field mapping changed'])
			->willReturn(['id' => 't-1']);

		$this->assertSame(['id' => 't-1'], $this->service->setFieldMap(id: 't-1', fieldMap: ['naam_aanvrager' => ' aanvrager.naam ', 'datum' => '']));
	}//end testAFieldMappingIsCheckedAndSaved()

	/**
	 * A tag the template lacks, a path that is no field path, or a Twig
	 * template is refused and nothing is saved.
	 *
	 * @return void
	 */
	public function testABadFieldMappingIsRefused(): void {
		$this->templates->method('getTemplate')->willReturnMap([
			['t-1', ['id' => 't-1', 'templateType' => 'office', 'mergeFields' => [['path' => 'naam']]]],
			['t-2', ['id' => 't-2']],
		]);
		$this->templates->expects($this->never())->method('updateTemplate');
		$cases = [
			['t-1', ['onbekend' => 'a'], 'The template has no merge tag "onbekend"', 400],
			['t-1', ['naam' => 'a b'], '"a b" is not a field path', 400],
			['t-2', ['naam' => 'a'], 'Only an office template has a field mapping', 409],
		];
		foreach ($cases as [$id, $map, $message, $code]) {
			try {
				$this->service->setFieldMap(id: $id, fieldMap: $map);
				$this->fail('Accepted: '.$message);
			} catch (\Exception $e) {
				$this->assertSame([$message, $code], [$e->getMessage(), $e->getCode()]);
			}
		}
	}//end testABadFieldMappingIsRefused()
}//end class
