<?php

/**
 * What the register declares about governed documents and their types.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Settings
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/document-register/specs/document-register/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Settings;

use PHPUnit\Framework\TestCase;

/**
 * The `document` and `documentType` schemas: the register lists them, the
 * lifecycle is one OpenRegister accepts (field, initial and transitions over
 * the status enum), retention is declared, relations are references resolved
 * at read time, and the seeds classify their documents with seeded types.
 */
class DocumentRegisterSchemaTest extends TestCase {

	/**
	 * The parsed register.
	 *
	 * @return array<string, mixed> The descriptor.
	 */
	private function register(): array {
		$parsed = json_decode((string) file_get_contents(__DIR__ . '/../../../lib/Settings/filinq_register.json'), true);
		$this->assertIsArray($parsed);

		return $parsed;

	}//end register()

	/**
	 * The seeded objects of one schema, by slug.
	 *
	 * @param string $schema The schema slug.
	 *
	 * @return array<string, array<string, mixed>> The objects.
	 */
	private function seeds(string $schema): array {
		$rows = [];
		foreach ($this->register()['components']['objects'] as $row) {
			if (($row['@self']['schema'] ?? null) === $schema) {
				$rows[(string) $row['@self']['slug']] = $row;
			}
		}

		return $rows;

	}//end seeds()

	/**
	 * The register lists both schemas, moved its version, and validates them hard.
	 *
	 * @return void
	 */
	public function testTheRegisterCarriesBothSchemas(): void {
		$register = $this->register();
		$schemas = $register['components']['schemas'];

		$this->assertContains('document', $register['components']['registers']['filinq']['schemas']);
		$this->assertContains('documentType', $register['components']['registers']['filinq']['schemas']);
		$this->assertTrue(version_compare($register['info']['version'], '8.52.0', '>='));
		$this->assertTrue($schemas['document']['hardValidation']);
		$this->assertTrue($schemas['documentType']['hardValidation']);
		$this->assertSame('title', $schemas['document']['configuration']['objectNameField']);
		$this->assertSame('name', $schemas['documentType']['configuration']['objectNameField']);

	}//end testTheRegisterCarriesBothSchemas()

	/**
	 * The document's fields follow the VNG information object, with the confidentiality domain and default.
	 *
	 * @return void
	 */
	public function testTheDocumentFieldsFollowTheInformationObject(): void {
		$document = $this->register()['components']['schemas']['document'];
		$properties = $document['properties'];

		$this->assertSame(
			['title', 'identifier', 'description', 'author', 'language', 'format', 'creationDate', 'receiptDate', 'sendDate', 'status', 'confidentiality', 'documentType', 'retentionClass', 'relatedCases', 'relatedObjects'],
			array_keys($properties)
		);
		$this->assertContains('title', $document['required']);
		$this->assertSame(['openbaar', 'intern', 'vertrouwelijk', 'geheim'], $properties['confidentiality']['enum']);
		$this->assertSame('intern', $properties['confidentiality']['default']);
		$this->assertSame(['type' => 'string', '$ref' => 'case', 'x-external-register' => 'procest'], $properties['relatedCases']['items']);
		$this->assertSame(['type' => 'string'], $properties['relatedObjects']['items']);
		$this->assertSame('string', $properties['documentType']['type']);
		$this->assertArrayNotHasKey('$ref', $properties['documentType']);

	}//end testTheDocumentFieldsFollowTheInformationObject()

	/**
	 * The lifecycle runs concept to gearchiveerd over the status enum, with every transition between enum states.
	 *
	 * @return void
	 */
	public function testTheLifecycleIsOneOpenRegisterAccepts(): void {
		$document = $this->register()['components']['schemas']['document'];
		$lifecycle = $document['x-openregister-lifecycle'];
		$enum = $document['properties']['status']['enum'];

		$this->assertSame(['concept', 'in_bewerking', 'ter_vaststelling', 'definitief', 'gearchiveerd'], $enum);
		$this->assertSame('status', $lifecycle['field']);
		$this->assertSame('concept', $lifecycle['initial']);
		$this->assertSame(['gearchiveerd'], $lifecycle['final']);
		$this->assertSame($enum, array_keys($lifecycle['states']));
		foreach ($lifecycle['transitions'] as $name => $transition) {
			$this->assertContains($transition['from'], $enum, $name);
			$this->assertContains($transition['to'], $enum, $name);
		}

		$reachable = ['concept'];
		foreach ($lifecycle['transitions'] as $transition) {
			if (in_array($transition['from'], $reachable, true) === true && in_array($transition['to'], $reachable, true) === false) {
				$reachable[] = $transition['to'];
			}
		}

		$this->assertSame($enum, $reachable, 'every state is reached in order from concept');

	}//end testTheLifecycleIsOneOpenRegisterAccepts()

	/**
	 * Retention is declared on the document; a type's identifier is unique.
	 *
	 * @return void
	 */
	public function testRetentionIsDeclaredAndTypesAreUnique(): void {
		$schemas = $this->register()['components']['schemas'];
		$archival = $schemas['document']['x-openregister-archival'];

		$this->assertSame('P10Y', $archival['retention']['default']);
		$this->assertSame('destroy', $archival['action']);
		$this->assertSame([['identifier']], $schemas['documentType']['x-openregister-unique']);
		$this->assertTrue($schemas['documentType']['properties']['active']['default']);

	}//end testRetentionIsDeclaredAndTypesAreUnique()

	/**
	 * The canonical types are seeded with an ISO 8601 retention period and a matching identifier.
	 *
	 * @return void
	 */
	public function testTheCanonicalTypesAreSeeded(): void {
		$types = $this->seeds(schema: 'documentType');
		$pattern = '/' . $this->register()['components']['schemas']['documentType']['properties']['retentionPeriod']['pattern'] . '/';

		foreach (['brief', 'besluit', 'rapport', 'factuur', 'contract', 'notulen', 'beleidsstuk'] as $slug) {
			$this->assertArrayHasKey($slug, $types);
			$this->assertSame($slug, $types[$slug]['identifier']);
			$this->assertMatchesRegularExpression($pattern, $types[$slug]['retentionPeriod'], $slug);
		}

	}//end testTheCanonicalTypesAreSeeded()

	/**
	 * The sample documents differ in status and confidentiality, name seeded types, and relate to something.
	 *
	 * @return void
	 */
	public function testTheSampleDocumentsAreClassifiedAndRelated(): void {
		$documents = $this->seeds(schema: 'document');
		$types = $this->seeds(schema: 'documentType');
		$dossiers = $this->seeds(schema: 'dossier');
		$schema = $this->register()['components']['schemas']['document'];

		$this->assertGreaterThanOrEqual(3, count($documents));
		$this->assertLessThanOrEqual(5, count($documents));
		$this->assertGreaterThan(1, count(array_unique(array_column($documents, 'status'))));
		$this->assertGreaterThan(1, count(array_unique(array_column($documents, 'confidentiality'))));

		$related = 0;
		foreach ($documents as $slug => $document) {
			$this->assertArrayHasKey($document['documentType'], $types, $slug);
			$this->assertContains($document['status'], $schema['properties']['status']['enum'], $slug);
			$this->assertSame([], array_diff($schema['required'], array_keys($document)), $slug);
			foreach (($document['relatedObjects'] ?? []) as $object) {
				$this->assertArrayHasKey($object, $dossiers, $slug);
			}

			$related += count($document['relatedCases'] ?? []) + count($document['relatedObjects'] ?? []);
		}

		$this->assertGreaterThan(0, $related);

	}//end testTheSampleDocumentsAreClassifiedAndRelated()
}//end class
