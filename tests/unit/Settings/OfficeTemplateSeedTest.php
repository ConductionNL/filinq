<?php

/**
 * The office template data model and its demo data hold together: the new
 * schema fields are pinned, and the demo office template names the seed
 * DOCX's checksum and exactly the tags that file carries.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Settings
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

namespace OCA\Filinq\Tests\Unit\Settings;

use OCA\Filinq\Service\OfficeTemplate\MergeTagReader;
use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
class OfficeTemplateSeedTest extends TestCase {

	/**
	 * The seed DOCX.
	 */
	private const SEED = __DIR__.'/../../sample-documents/beschikking-parkeervergunning.docx';

	/**
	 * A register file, decoded.
	 *
	 * @param string $file The file under lib/Settings.
	 *
	 * @return array<string, mixed>
	 */
	private function descriptor(string $file): array {
		return json_decode((string) file_get_contents(__DIR__.'/../../../lib/Settings/'.$file), true);
	}//end descriptor()

	/**
	 * The office fields, fragments and import jobs are in the register, and
	 * an absent type reads as Twig because the field is not required.
	 *
	 * @return void
	 */
	public function testTheOfficeDataModelIsPinned(): void {
		$schemas = $this->descriptor(file: 'filinq_register.json')['components']['schemas'];

		$this->assertSame(['twig', 'office'], $schemas['template']['properties']['templateType']['enum']);
		$this->assertNotContains('templateType', (array) ($schemas['template']['required'] ?? []));
		foreach (['sourceFileId', 'contentHash', 'boundRegister', 'boundSchema', 'mergeFields', 'fieldMap', 'tagReport'] as $field) {
			$this->assertArrayHasKey($field, $schemas['template']['properties'], $field);
		}

		$this->assertArrayHasKey('sourceFileId', $schemas['templateVersion']['properties']);
		$this->assertArrayHasKey('contentHash', $schemas['templateVersion']['properties']);
		$this->assertSame(['name', 'slug', 'content', 'namespace'], $schemas['textFragment']['required']);
		$this->assertSame(['queued', 'running', 'completed', 'failed'], $schemas['templateImportJob']['properties']['status']['enum']);
		foreach (['skipped', 'namespace', 'archiveFileId', 'sourcePath', 'startedAt', 'finishedAt'] as $field) {
			$this->assertArrayHasKey($field, $schemas['templateImportJob']['properties'], $field);
		}
	}//end testTheOfficeDataModelIsPinned()

	/**
	 * The demo office template carries the seed file's checksum and tags.
	 *
	 * @return void
	 */
	public function testTheDemoOfficeTemplateMatchesTheSeedFile(): void {
		$demo = null;
		foreach ($this->descriptor(file: 'filinq_mock_register.json')['components']['objects'] as $row) {
			if (($row['@self']['slug'] ?? '') === 'template-beschikking-parkeervergunning') {
				$demo = $row;
			}
		}

		$this->assertNotNull($demo);
		$this->assertSame('office', $demo['templateType']);
		$this->assertSame(hash_file('sha256', self::SEED), $demo['contentHash']);
		$this->assertSame(
			(new MergeTagReader())->tags(docx: (string) file_get_contents(self::SEED)),
			array_column($demo['mergeFields'], 'path')
		);
	}//end testTheDemoOfficeTemplateMatchesTheSeedFile()
}//end class
