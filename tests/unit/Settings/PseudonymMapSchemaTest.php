<?php

/**
 * Unit tests for the pseudonymMap schema
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Settings
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-5.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Settings;
use OCA\Filinq\Tests\Unit\Service\Pseudonymisation\PseudonymDoubles;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;
use PHPUnit\Framework\TestCase;

/**
 * What the register declares about the key, and that every payload the app
 * writes validates against it.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Settings
 * @author   Conduction Development Team <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class PseudonymMapSchemaTest extends TestCase {
	use PseudonymDoubles;

	/**
	 * A parsed settings file.
	 *
	 * @param string $file The file under lib/Settings.
	 *
	 * @return array<string, mixed> The descriptor.
	 */
	private function descriptor(string $file = 'filinq_register.json'): array {
		$parsed = json_decode((string) file_get_contents(__DIR__ . '/../../../lib/Settings/' . $file), true);
		$this->assertIsArray($parsed);

		return $parsed;

	}//end descriptor()

	/**
	 * Validate a payload against a schema of the register, as OpenRegister's hard validation does.
	 *
	 * @param string $slug The schema slug.
	 * @param array<string, mixed> $payload The payload.
	 *
	 * @return void
	 */
	private function assertValidAgainst(string $slug, array $payload): void {
		$schema = $this->descriptor()['components']['schemas'][$slug];
		$properties = [];
		foreach ($schema['properties'] as $name => $property) {
			unset($property['required'], $property['visible'], $property['order'], $property['facetable'], $property['x-enum-labels'], $property['title']);
			$properties[$name] = $property;
		}

		$json = (string) json_encode(['type' => 'object', 'required' => $schema['required'], 'properties' => $properties, 'additionalProperties' => false]);
		$result = (new Validator())->validate(json_decode((string) json_encode($payload)), $json);
		$message = '';
		if ($result->isValid() === false) {
			$message = (string) json_encode((new ErrorFormatter())->format($result->error()));
		}

		$this->assertTrue($result->isValid(), $slug . ': ' . $message);

	}//end assertValidAgainst()

	/**
	 * The register lists the schema, the payload is writeOnly, and only admins reach it through the API.
	 *
	 * @return void
	 */
	public function testTheKeyIsWriteOnlyAndAdminOnly(): void {
		$descriptor = $this->descriptor();
		$schema = $descriptor['components']['schemas']['pseudonymMap'];

		$this->assertContains('pseudonymMap', $descriptor['components']['registers']['filinq']['schemas']);
		$this->assertTrue(version_compare((string) $descriptor['info']['version'], '8.27.0', '>='));
		$this->assertTrue($schema['hardValidation']);
		$this->assertTrue($schema['properties']['mappings']['writeOnly']);
		$this->assertFalse($schema['properties']['mappings']['visible']);
		foreach (['read', 'create', 'update', 'delete'] as $action) {
			$this->assertSame(['admin'], $schema['authorization'][$action], $action);
		}

	}//end testTheKeyIsWriteOnlyAndAdminOnly()

	/**
	 * The link gains `mappingRef` and nothing else.
	 *
	 * @return void
	 */
	public function testTheLinkOnlyGainsAPointer(): void {
		$link = $this->descriptor()['components']['schemas']['anonymizationLink'];

		// 1.3.0 since accessible-redaction-output (structurePreservation).
		$this->assertSame('1.3.0', $link['version']);
		$this->assertSame(['sourceFileId', 'anonymizedFileId'], $link['required']);
		$this->assertSame(
			[
				'sourceFileId', 'sourceFileName', 'sourceFilePath', 'anonymizedFileId', 'anonymizedFileName', 'anonymizedFilePath',
				'outputFormat', 'status', 'replacementCount', 'runCount', 'anonymizedAt', 'anonymizedBy', 'verificationVerdict',
				'verificationOutputMode', 'verificationRoutes', 'verificationLeakRoutes', 'verifiedAt', 'mappingRef',
				'structurePreservation',
			],
			array_keys($link['properties'])
		);

	}//end testTheLinkOnlyGainsAPointer()

	/**
	 * What a reversible run writes, map and link both, validates against the real schemas.
	 *
	 * @return void
	 */
	public function testEveryPayloadTheRunWritesValidates(): void {
		$this->rows['anonymizationLink']['link-1'] = ['sourceFileId' => 812200, 'anonymizedFileId' => 812201, 'runCount' => 1, 'status' => 'anonymized'];
		$this->entityRows = ['Jan Jansen' => ['id' => 41, 'type' => 'PERSON']];
		$this->recorder()->record(
			resultInfo: ['anonymizedFileId' => 812201, 'anonymizationLinkId' => 'link-1'],
			run: [
				'fileId' => 812200,
				'entities' => [['text' => 'Jan Jansen', 'entityType' => 'PERSON']],
				'placeholderMap' => ['41' => '[PERSOON: 1]'],
				'reversible' => true,
				'scope' => 'document',
				'userId' => 'alice',
			]
		);

		$this->assertValidAgainst(slug: 'pseudonymMap', payload: $this->rows['pseudonymMap']['pseudonymMap-1']);
		$this->assertValidAgainst(slug: 'anonymizationLink', payload: $this->rows['anonymizationLink']['link-1']);

		// And the irreversible rerun that clears the pointer.
		$this->recorder()->record(
			resultInfo: ['anonymizedFileId' => 812201, 'anonymizationLinkId' => 'link-1'],
			run: ['fileId' => 812200, 'entities' => [], 'placeholderMap' => [], 'reversible' => false]
		);
		$this->assertSame('', $this->rows['anonymizationLink']['link-1']['mappingRef']);
		$this->assertValidAgainst(slug: 'anonymizationLink', payload: $this->rows['anonymizationLink']['link-1']);

	}//end testEveryPayloadTheRunWritesValidates()

	/**
	 * The three demo rows validate, and none of them holds anything that decrypts.
	 *
	 * @return void
	 */
	public function testTheDemoRowsValidateAndHoldNoKey(): void {
		$demo = array_values(
			array_filter(
				$this->descriptor(file: 'filinq_mock_register.json')['components']['objects'],
				static fn (array $row): bool => ($row['@self']['schema'] ?? '') === 'pseudonymMap'
			)
		);

		$this->assertCount(3, $demo);
		foreach ($demo as $row) {
			unset($row['@self']);
			$this->assertValidAgainst(slug: 'pseudonymMap', payload: $row);
			$this->assertStringStartsNotWith('enc:', $row['mappings']);
		}

	}//end testTheDemoRowsValidateAndHoldNoKey()
}//end class
