<?php

/**
 * The homes the register gives OpenRegister's archival stack.
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
 * @spec openspec/changes/archiefwet-retention-engine/tasks.md#task-1.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Settings;

use OCA\Filinq\Tests\Unit\Service\Wizard\WizardObjectStore;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Service/Wizard/WizardDoubles.php';

/**
 * The selectielijstEntry, destructionList and destructionCertificate schemas
 * hold exactly what OpenRegister reads and writes.
 *
 * The literals below were read on OpenRegister `development`:
 * `SelectielijstResolver::lookupSelectielijstEntry()` and
 * `SelectielijstImportService` for the selectielijst row,
 * `DestructionService`, `DestructionReviewService` and `DestructionListRun`
 * for the list, and `RetentionService::generateDestructionCertificate()` for
 * the certificate. When an OpenRegister checkout sits next to this app, the
 * last test reads the import's keys from its source instead.
 */
class ArchiefRegisterSchemaTest extends TestCase {

	/**
	 * The fields OpenRegister reads from, or writes onto, a selectielijst row.
	 */
	private const SELECTIELIJST_FIELDS = ['categorie', 'archiefnominatie', 'bewaartermijn', 'bron', 'selectielijstVersie'];

	/**
	 * The statuses OpenRegister gives a destruction list.
	 */
	private const LIST_STATUSES = ['in_review', 'awaiting_second_approval', 'approved', 'rejected', 'completed', 'executing', 'executed'];

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
	 * One schema of the register.
	 *
	 * @param string $slug The schema slug.
	 *
	 * @return array<string, mixed> The schema.
	 */
	private function schema(string $slug): array {
		return $this->register()['components']['schemas'][$slug];

	}//end schema()

	/**
	 * Whether a payload passes the schema, closed: a key the schema does not
	 * declare fails, as it would once OpenRegister validates hard.
	 *
	 * @param string               $slug    The schema slug.
	 * @param array<string, mixed> $payload The object data.
	 *
	 * @return string|null Null when valid, else the formatted error.
	 */
	private function violation(string $slug, array $payload): ?string {
		$schema = $this->schema(slug: $slug);
		$properties = [];
		foreach ($schema['properties'] as $name => $property) {
			unset($property['required'], $property['visible'], $property['order'], $property['facetable'], $property['x-enum-labels'], $property['title']);
			$properties[$name] = $property;
		}

		$closed = json_decode(
			(string) json_encode(
				[
					'type'                 => 'object',
					'required'             => ($schema['required'] ?? []),
					'properties'           => (object) $properties,
					'additionalProperties' => false,
				]
			)
		);
		$result = (new Validator())->validate(
			json_decode((string) json_encode($payload)),
			(string) json_encode(WizardObjectStore::widenOptionalToNull(schema: $closed))
		);

		if ($result->isValid() === true) {
			return null;
		}

		return (string) json_encode((new ErrorFormatter())->format($result->error()));

	}//end violation()

	/**
	 * The consolidated register lists the three schemas, which validate hard,
	 * and the descriptor bumps its version. There is no separate archief
	 * register: the app declares exactly one (DossierRegisterConfigTest).
	 *
	 * @return void
	 */
	public function testTheRegisterCarriesTheThreeSchemas(): void {
		$register = $this->register();

		$this->assertTrue(version_compare($register['info']['version'], '8.99.0', '>='));
		$this->assertArrayNotHasKey('archief', $register['components']['registers']);
		foreach (['selectielijstEntry', 'destructionList', 'destructionCertificate'] as $slug) {
			$this->assertContains($slug, $register['components']['registers']['filinq']['schemas']);
			$this->assertTrue($register['components']['schemas'][$slug]['hardValidation'], $slug . ' validates hard');
			$this->assertArrayNotHasKey('x-openregister-archival', $register['components']['schemas'][$slug]);
		}

	}//end testTheRegisterCarriesTheThreeSchemas()

	/**
	 * Lists and certificates are permanent records, so no destruction list can hold one.
	 *
	 * @return void
	 */
	public function testListsAndCertificatesAreKept(): void {
		$this->assertSame('bewaren', $this->schema(slug: 'destructionList')['archive']['defaultNominatie']);
		$this->assertSame('bewaren', $this->schema(slug: 'destructionCertificate')['archive']['defaultNominatie']);

	}//end testListsAndCertificatesAreKept()

	/**
	 * Every field OpenRegister reads from a selectielijst row is declared.
	 *
	 * @return void
	 */
	public function testTheSelectielijstEntryHoldsEveryFieldOpenRegisterReads(): void {
		$schema = $this->schema(slug: 'selectielijstEntry');

		foreach (self::SELECTIELIJST_FIELDS as $field) {
			$this->assertArrayHasKey($field, $schema['properties'], $field . ' is read by OpenRegister');
		}

		$this->assertSame(['bewaren', 'vernietigen'], $schema['properties']['archiefnominatie']['enum']);
		$this->assertSame(['categorie', 'archiefnominatie'], $schema['required']);

	}//end testTheSelectielijstEntryHoldsEveryFieldOpenRegisterReads()

	/**
	 * An imported row is accepted, and a term that is not an ISO-8601 duration is not.
	 *
	 * @return void
	 */
	public function testAnImportedRowValidatesAndAProseTermDoesNot(): void {
		$row = [
			'categorie'           => '1.1',
			'omschrijving'        => 'Correspondentie',
			'archiefnominatie'    => 'vernietigen',
			'bewaartermijn'       => 'P7Y',
			'bron'                => 'selectielijst.csv',
			'selectielijstVersie' => '2020-1',
		];

		$this->assertNull($this->violation(slug: 'selectielijstEntry', payload: $row));
		$this->assertNull($this->violation(slug: 'selectielijstEntry', payload: ['categorie' => '1.2', 'archiefnominatie' => 'bewaren']));
		$this->assertNotNull($this->violation(slug: 'selectielijstEntry', payload: array_merge($row, ['bewaartermijn' => '7 jaar'])));
		$this->assertNotNull($this->violation(slug: 'selectielijstEntry', payload: array_merge($row, ['archiefnominatie' => 'misschien'])));

	}//end testAnImportedRowValidatesAndAProseTermDoesNot()

	/**
	 * The list accepts every status OpenRegister gives it.
	 *
	 * @return void
	 */
	public function testTheDestructionListKnowsEveryStatus(): void {
		$this->assertSame(self::LIST_STATUSES, $this->schema(slug: 'destructionList')['properties']['status']['enum']);

	}//end testTheDestructionListKnowsEveryStatus()

	/**
	 * A list as OpenRegister draws it up, reviews it and executes it validates
	 * with no key left undeclared.
	 *
	 * @return void
	 */
	public function testAListThroughItsLifeValidates(): void {
		$drawnUp = [
			'status'      => 'in_review',
			'createdAt'   => '2026-10-01T09:00:00+00:00',
			'objectCount' => 1,
			'objects'     => [['uuid' => 'a1', 'schema' => 'correspondence', 'classification' => '1.1']],
			'approvals'   => [],
			'rejections'  => [],
		];
		$reviewed = array_merge(
			$drawnUp,
			[
				'status'          => 'approved',
				'approvals'       => [['userId' => 'archivist', 'approvedAt' => '2026-10-02T09:00:00+00:00', 'action' => 'approve']],
				'decisions'       => [['entry' => 'a1', 'decision' => 'destroy']],
				'excludedObjects' => [['uuid' => 'a2', 'reason' => 'Still needed']],
			]
		);
		$executed = array_merge(
			$reviewed,
			[
				'status'           => 'executed',
				'executingAt'      => '2026-10-03T02:00:00+00:00',
				'executedAt'       => '2026-10-03T02:01:00+00:00',
				'destroyedCount'   => 1,
				'skippedHolds'     => 0,
				'skippedErrors'    => 0,
				'skippedDecisions' => 0,
				'certificateUuid'  => 'c1',
			]
		);

		foreach ([$drawnUp, $reviewed, $executed] as $list) {
			$this->assertNull($this->violation(slug: 'destructionList', payload: $list));
		}

		$this->assertNotNull($this->violation(slug: 'destructionList', payload: array_merge($drawnUp, ['status' => 'shredded'])));

	}//end testAListThroughItsLifeValidates()

	/**
	 * OpenRegister stores the certificate in the destruction-list schema, so
	 * that schema accepts it; the certificate schema accepts it as well.
	 *
	 * @return void
	 */
	public function testTheCertificateValidatesInBothHomes(): void {
		$certificate = [
			'type'                => 'verklaring_van_vernietiging',
			'destructionDate'     => '2026-10-03T02:01:00+00:00',
			'approvedBy'          => ['archivist'],
			'destructionListUuid' => 'l1',
			'totalDestroyed'      => 1,
			'groupedBySchema'     => [['schema' => 'correspondence', 'classification' => '1.1', 'count' => 1]],
			'selectielijstBron'   => ['Selectielijst gemeenten 2020'],
			'complianceStatement' => 'Vernietiging conform Archiefwet 1995 en Archiefbesluit 1995',
			'immutable'           => true,
		];

		$this->assertNull($this->violation(slug: 'destructionList', payload: $certificate));
		$this->assertNull($this->violation(slug: 'destructionCertificate', payload: $certificate));
		$this->assertNull($this->violation(slug: 'destructionCertificate', payload: array_merge($certificate, ['destructionListUuid' => null])));
		$this->assertNotNull($this->violation(slug: 'destructionCertificate', payload: array_diff_key($certificate, ['destructionDate' => true])));

	}//end testTheCertificateValidatesInBothHomes()

	/**
	 * Three demo rows per schema, in the filinq register, each accepted by its
	 * schema; the demo categories say they are not real ones.
	 *
	 * @return void
	 */
	public function testTheDemoRowsValidate(): void {
		$parsed = json_decode((string) file_get_contents(__DIR__ . '/../../../lib/Settings/filinq_mock_register.json'), true);
		foreach (['selectielijstEntry', 'destructionList', 'destructionCertificate'] as $slug) {
			$rows = array_values(array_filter($parsed['components']['objects'], static fn (array $row): bool => ($row['@self']['schema'] ?? '') === $slug));
			$this->assertCount(3, $rows, $slug);
			foreach ($rows as $row) {
				$this->assertSame('filinq', $row['@self']['register']);
				if ($slug === 'selectielijstEntry') {
					$this->assertStringStartsWith('DEMO-', $row['categorie']);
				}

				unset($row['@self']);
				$this->assertNull($this->violation(slug: $slug, payload: $row), $slug);
			}
		}

	}//end testTheDemoRowsValidate()

	/**
	 * With an OpenRegister checkout beside the app, the import's own keys are
	 * the ones the schema declares.
	 *
	 * @return void
	 */
	public function testTheImportKeysMatchOpenRegistersSource(): void {
		$source = __DIR__ . '/../../../../openregister/lib/Service/Archival/SelectielijstImportService.php';
		if (is_file($source) === false) {
			$this->markTestSkipped('No OpenRegister checkout beside the app; the literals above stand in for it.');
		}

		$code = (string) file_get_contents($source);
		$properties = $this->schema(slug: 'selectielijstEntry')['properties'];
		foreach (['VERSION_KEY', 'CATEGORY_KEY'] as $constant) {
			$this->assertSame(1, preg_match('/const ' . $constant . " = '([^']+)'/", $code, $match), $constant . ' is in the source');
			$this->assertArrayHasKey($match[1], $properties);
		}

	}//end testTheImportKeysMatchOpenRegistersSource()
}//end class
