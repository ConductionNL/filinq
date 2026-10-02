<?php

/**
 * OfficeTemplateService tests
 *
 * Upload, refusal, tag check against the bound schema, ODT normalisation,
 * new source revisions under the lock, duplication and the field mapping.
 * Every template payload is checked against the real `template` fragment of
 * filinq_register.json (Opis) before the TemplateService double accepts it.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\OfficeTemplate
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\OfficeTemplate;

require_once __DIR__ . '/OfficeTemplateDoubles.php';

use OCA\Filinq\Service\Conversion\LibreOfficeHeadlessBackend;
use OCA\Filinq\Service\Conversion\PhpWordIo;
use OCA\Filinq\Service\OfficeTemplate\OfficeConverter;
use OCA\Filinq\Service\OfficeTemplate\OfficeSourceInspector;
use OCA\Filinq\Service\OfficeTemplate\OfficeTemplateRefused;
use OCA\Filinq\Service\OfficeTemplate\OfficeTemplateService;
use OCA\Filinq\Service\OfficeTemplate\TagClassifier;
use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\TemplateService;
use OCA\Filinq\Tests\Unit\Service\Wizard\WizardObjectStore;
use OCA\OpenRegister\Db\SchemaMapper;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OCA\Filinq\Service\OfficeTemplate\OfficeTemplateService
 * @covers \OCA\Filinq\Service\OfficeTemplate\OfficeSourceInspector
 * @covers \OCA\Filinq\Service\OfficeTemplate\TagClassifier
 */
class OfficeTemplateServiceTest extends TestCase {

	/**
	 * Templates by id, as the TemplateService double holds them.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private array $templates = [];

	private MemorySourceStore $store;

	/**
	 * App config values.
	 *
	 * @var array<string, string>
	 */
	private array $config = [];

	/**
	 * The ODT conversions asked of LibreOffice.
	 *
	 * @var int
	 */
	private int $odtConversions = 0;

	protected function setUp(): void {
		$this->templates = [];
		$this->config = [];
		$this->odtConversions = 0;
		$this->store = new MemorySourceStore();
	}

	private function service(): OfficeTemplateService {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(
			fn (string $app, string $key, string $default = ''): string => $this->config[$key] ?? $default
		);
		$schemas = $this->createMock(SchemaMapper::class);
		$schemas->method('find')->willReturnCallback(static function (mixed $id): object {
			if ($id !== 'dossier') {
				throw new \RuntimeException('no schema ' . $id);
			}

			return new class {
				public function getProperties(): array {
					return [
						'aanvrager' => ['type' => 'object', 'properties' => ['naam' => ['type' => 'string'], 'adres' => ['type' => 'string']]],
						'besluit' => ['type' => 'object', 'properties' => ['datum' => ['type' => 'string']]],
						'zaaknummer' => ['type' => 'string'],
					];
				}
			};
		});
		$libreOffice = $this->createMock(LibreOfficeHeadlessBackend::class);
		$libreOffice->method('convertOffice')->willReturnCallback(function (string $bytes, string $fromExtension, string $toExtension): string {
			$this->odtConversions++;

			return OfficeFixtures::bytes('zaak-as-docx.docx');
		});

		return new OfficeTemplateService(
			inspector: new OfficeSourceInspector(config: $config),
			converter: new OfficeConverter(libreOffice: $libreOffice, phpWord: new PhpWordIo(), pdfService: $this->createMock(PdfService::class)),
			classifier: new TagClassifier(schemas: $schemas, config: $config),
			store: $this->store,
			templates: $this->templateService()
		);
	}

	/**
	 * A TemplateService double that validates every write against the real template fragment.
	 */
	private function templateService(): TemplateService {
		$service = $this->createMock(TemplateService::class);
		$service->method('createTemplate')->willReturnCallback(function (array $data): array {
			WizardObjectStore::assertValid(schema: 'template', payload: $data);
			$id = 'tpl-' . (count($this->templates) + 1);
			$this->templates[$id] = $data;

			return $data + ['id' => $id];
		});
		$service->method('getTemplate')->willReturnCallback(fn (string $id): array => $this->templates[$id] + ['id' => $id]);
		$service->method('updateTemplate')->willReturnCallback(function (string $id, array $data): array {
			unset($data['_changelog']);
			$merged = array_merge($this->templates[$id], $data);
			WizardObjectStore::assertValid(schema: 'template', payload: $merged);
			$this->templates[$id] = $merged;

			return $merged + ['id' => $id];
		});
		$service->method('duplicateTemplate')->willReturnCallback(function (string $id, array $extra = []): array {
			$copy = array_merge(['name' => $this->templates[$id]['name'] . ' (kopie)', 'namespace' => $this->templates[$id]['namespace'], 'content' => $this->templates[$id]['content']], $extra);
			WizardObjectStore::assertValid(schema: 'template', payload: $copy);

			return $copy + ['id' => 'tpl-copy'];
		});

		return $service;
	}

	private function upload(string $file, array $meta = []): array {
		return $this->service()->createFromUpload(
			fileName: $file,
			bytes: OfficeFixtures::bytes($file),
			meta: $meta + ['name' => 'Beschikking parkeervergunning', 'namespace' => 'filinq']
		);
	}

	public function testCommunicationsOfficerUploadsADocxHouseStyleTemplate(): void {
		$result = $this->upload('beschikking-parkeervergunning.docx');
		$template = $result['template'];

		$this->assertSame('office', $template['templateType']);
		$this->assertArrayHasKey($template['sourceFileId'], $this->store->files);
		$this->assertSame(hash('sha256', OfficeFixtures::bytes('beschikking-parkeervergunning.docx')), $template['contentHash']);
		$this->assertSame(['aanvrager.naam', 'aanvrager.adres', 'besluit.datum', 'fragment:ondertekening-burgemeester'], $template['mergeFields']);
		$this->assertFalse($result['converted']);
		$this->assertStringContainsString('Beschikking parkeervergunning', $template['content']);
	}

	public function testMacroEnabledUploadIsRejected(): void {
		foreach (['macro-vba.docx', 'brief.docm'] as $name) {
			try {
				$this->service()->createFromUpload(fileName: $name, bytes: OfficeFixtures::bytes('macro-vba.docx'), meta: ['name' => 'X', 'namespace' => 'filinq']);
				$this->fail('macro upload accepted: ' . $name);
			} catch (OfficeTemplateRefused $e) {
				$this->assertSame(422, $e->getCode());
				$this->assertSame('macro', $e->getReason());
			}
		}

		$this->assertSame([], $this->templates, 'no template object');
		$this->assertSame([], $this->store->files, 'no stored file');
	}

	public function testOversizedAndMislabelledUploadsAreRejected(): void {
		$this->config[OfficeSourceInspector::MAX_BYTES_KEY] = '100';
		try {
			$this->upload('beschikking-parkeervergunning.docx');
			$this->fail('oversized upload accepted');
		} catch (OfficeTemplateRefused $e) {
			$this->assertSame('size', $e->getReason());
		}

		$this->config = [];
		try {
			$this->service()->createFromUpload(fileName: 'brief.docx', bytes: '%PDF-1.7 not a docx', meta: ['name' => 'X', 'namespace' => 'filinq']);
			$this->fail('a PDF named .docx was accepted');
		} catch (OfficeTemplateRefused $e) {
			$this->assertSame(422, $e->getCode());
			$this->assertSame('mime', $e->getReason());
		}

		$this->assertSame([], $this->store->files);
	}

	public function testACorruptDocxIsRefused(): void {
		$this->expectException(OfficeTemplateRefused::class);
		$this->expectExceptionCode(422);
		$this->upload('corrupt.docx');
	}

	public function testUnknownTagIsReportedOnUpload(): void {
		$result = $this->upload('brief-ontvangstbevestiging.docx', ['boundRegister' => 'filinq', 'boundSchema' => 'dossier']);
		$report = $result['template']['tagReport'];

		$this->assertTrue($report['validated']);
		$this->assertSame('warning', $report['severity']);
		$this->assertSame(['zaaknummer'], $report['known']);
		$this->assertSame(['naam_aanvrager', 'aanvraagr.naam'], $report['unknown']);
	}

	public function testBlockingSeverityRefusesTheUpload(): void {
		$this->config[TagClassifier::SEVERITY_KEY] = 'blocking';
		try {
			$this->upload('brief-ontvangstbevestiging.docx', ['boundRegister' => 'filinq', 'boundSchema' => 'dossier']);
			$this->fail('blocking severity accepted unknown tags');
		} catch (OfficeTemplateRefused $e) {
			$this->assertSame(422, $e->getCode());
			$this->assertSame(['naam_aanvrager', 'aanvraagr.naam'], $e->toResponse()['unknownTags']);
		}

		$this->assertSame([], $this->templates);
		$this->assertSame([], $this->store->files);
	}

	public function testAnUnboundTemplateIsNeverBlocked(): void {
		$this->config[TagClassifier::SEVERITY_KEY] = 'blocking';
		$report = $this->upload('brief-ontvangstbevestiging.docx')['template']['tagReport'];

		$this->assertFalse($report['validated']);
		$this->assertSame(TagClassifier::NOT_VALIDATED, $report['notice']);
	}

	public function testAFieldMapMakesAnUnknownTagKnown(): void {
		$result = $this->upload('brief-ontvangstbevestiging.docx', ['boundSchema' => 'dossier', 'fieldMap' => ['naam_aanvrager' => 'aanvrager.naam']]);

		$this->assertSame(['naam_aanvrager', 'zaaknummer'], $result['template']['tagReport']['known']);
		$this->assertSame(['aanvraagr.naam'], $result['template']['tagReport']['unknown']);

		$updated = $this->service()->updateFieldMap(templateId: 'tpl-1', fieldMap: ['naam_aanvrager' => 'aanvrager.naam', 'aanvraagr.naam' => 'aanvrager.naam'], userId: 'alice');
		$this->assertSame([], $updated['tagReport']['unknown']);
	}

	public function testOdtUploadIsNormalisedAndFlagged(): void {
		$result = $this->upload('zaak.odt');
		$template = $result['template'];

		$this->assertTrue($result['converted']);
		$this->assertSame(1, $this->odtConversions);
		$this->assertSame('docx', $this->store->files[$template['sourceFileId']][0]);
		$this->assertSame('odt', $this->store->files[$template['originalFileId']][0]);
		$this->assertSame(['zaaknummer'], $template['mergeFields']);
	}

	public function testNewSourceUploadIsRefusedWhileAnotherUserHoldsTheLock(): void {
		$this->upload('beschikking-parkeervergunning.docx');
		$this->templates['tpl-1']['lockedBy'] = 'bob';
		$this->templates['tpl-1']['lockedAt'] = (new \DateTimeImmutable())->format(DATE_ATOM);

		try {
			$this->service()->replaceSource(templateId: 'tpl-1', fileName: 'v2.docx', bytes: OfficeFixtures::bytes('brief-ontvangstbevestiging.docx'), userId: 'alice');
			$this->fail('replaced under another user\'s lock');
		} catch (OfficeTemplateRefused $e) {
			$this->assertSame(409, $e->getCode());
		}

		$result = $this->service()->replaceSource(templateId: 'tpl-1', fileName: 'v2.docx', bytes: OfficeFixtures::bytes('brief-ontvangstbevestiging.docx'), userId: 'bob');
		$this->assertSame(hash('sha256', OfficeFixtures::bytes('brief-ontvangstbevestiging.docx')), $result['template']['contentHash']);
	}

	public function testDuplicateCopiesTheSourceFile(): void {
		$original = $this->upload('beschikking-parkeervergunning.docx')['template'];
		$copy = $this->service()->duplicate(templateId: 'tpl-1');

		$this->assertNotSame($original['sourceFileId'], $copy['sourceFileId']);
		$this->assertSame($original['contentHash'], hash('sha256', $this->store->read(fileId: $copy['sourceFileId'])));
		$this->assertArrayNotHasKey('lockedBy', $copy);
	}

	public function testATwigTemplateCannotTakeAnOfficeSource(): void {
		$this->templates['tpl-9'] = ['name' => 'Brief', 'namespace' => 'filinq', 'content' => '<p>{{ x }}</p>'];

		$this->expectException(OfficeTemplateRefused::class);
		$this->expectExceptionCode(400);
		$this->service()->replaceSource(templateId: 'tpl-9', fileName: 'v.docx', bytes: OfficeFixtures::bytes('beschikking-parkeervergunning.docx'), userId: 'alice');
	}
}
