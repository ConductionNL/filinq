<?php

/**
 * Multi-tenant guardrails: the source-tree shape REQ-DDMTH-001/-002 require
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
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Scans the source tree: no bypass flag in the services this change cleared,
 * no new organisation filter bypass outside the system seam, and no app-local
 * tenant construct.
 *
 * Runs fully offline: it only reads files on disk. A failure names the
 * offending file and line.
 *
 * @coversNothing
 *
 * @spec openspec/changes/multi-tenant-hardening/specs/multi-tenant-hardening/spec.md#requirement-openregister-enforcement-is-on-by-default-bypasses-are-removed-req-ddmth-002
 */
class MultiTenantGuardrailsTest extends TestCase {

	/**
	 * The services REQ-DDMTH-002 names: they pass no bypass flag at all.
	 *
	 * A file that no longer exists is skipped, so a rename does not turn
	 * this into a false failure; the rest of the scan still covers it.
	 *
	 * @var array<int, string>
	 */
	private const CLEARED_SERVICES = [
		'lib/Service/BasesResolverService.php',
		'lib/Service/ConsentService.php',
		'lib/Service/GrondslagenSummaryService.php',
		'lib/Service/GrondslagProposalService.php',
		'lib/Service/PolicyCrudService.php',
		'lib/Service/PolicyMatchService.php',
		'lib/Service/PolicyRetroactiveService.php',
	];

	/**
	 * The only file allowed to relax organisation filtering without a record.
	 *
	 * @var string
	 */
	private const SEAM = 'lib/Service/SystemContextSeam.php';

	/**
	 * `_multitenancy: false` call sites outside the seam, keyed by file with their count.
	 *
	 * Each predates this change and keeps its own compensating control
	 * (an owner check, a background job with no session, a lookup of an
	 * instance-wide catalogue); each is recorded in
	 * docs/authorization-decisions.md. The set may only shrink: a new site
	 * belongs in the seam, where it is logged with a reason. Removing one
	 * fails too, so this list is updated with the code.
	 *
	 * @var array<string, int>
	 */
	private const RECORDED_ORGANISATION_BYPASSES = [
		'lib/Controller/PortalSigningReceiverController.php' => 1,
		'lib/EventListener/DocumentRegistrationWriteGuard.php' => 1,
		'lib/Service/BaseLabelResolver.php' => 1,
		'lib/Service/BatchStateRepository.php' => 3,
		'lib/Service/Classification/ClassificationResultRepository.php' => 2,
		'lib/Service/Classification/ClassificationSources.php' => 1,
		'lib/Service/CustomDictionaryRepository.php' => 4,
		'lib/Service/DossierObjectRepository.php' => 3,
		'lib/Service/EmailIngestion/EmailDocumentRepository.php' => 3,
		'lib/Service/LegalBasisCatalog.php' => 1,
		'lib/Service/Redaction/AnonymizationLinkReader.php' => 1,
		'lib/Service/SubjectErasure/SubjectErasureStore.php' => 1,
	];

	/**
	 * Schema properties that are named like a tenant but are not one.
	 *
	 * `template.tenantId` predates this change: it picks a customer variant
	 * of a template within its namespace (TemplateSlugResolver) and is not
	 * stamped from a session or used to filter access. Recorded here so a
	 * new tenant-shaped property still fails.
	 *
	 * @var array<int, string>
	 */
	private const RECORDED_TENANT_NAMED_PROPERTIES = ['template.tenantId'];

	/**
	 * Repository root.
	 *
	 * @var string
	 */
	private string $root;

	/**
	 * Locate the repository root.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->root = dirname(__DIR__, 3);
	}//end setUp()

	/**
	 * The cleared services pass neither `_rbac: false` nor `_multitenancy: false`.
	 *
	 * @return void
	 */
	public function testTheClearedServicesPassNoBypassFlag(): void {
		$checked = 0;
		$hits    = [];
		foreach (self::CLEARED_SERVICES as $relative) {
			if (is_file($this->root . '/' . $relative) === false) {
				continue;
			}

			$checked++;
			foreach ($this->flagLines(relative: $relative, pattern: '/_(rbac|multitenancy):\s*false/') as $line) {
				$hits[] = $relative . ':' . $line;
			}
		}

		$this->assertGreaterThan(0, $checked, 'None of the cleared services was found; the scan inspected nothing.');
		$this->assertSame(
			[],
			$hits,
			"A bypass flag came back in a service multi-tenant-hardening cleared:\n" . implode("\n", $hits)
			. "\nA user-request path runs under OpenRegister's enforcing defaults; a system path goes through SystemContextSeam."
		);
	}//end testTheClearedServicesPassNoBypassFlag()

	/**
	 * No new `_multitenancy: false` outside the seam; the recorded set only shrinks.
	 *
	 * @return void
	 */
	public function testOrganisationBypassesOutsideTheSeamAreRecorded(): void {
		$found = [];
		$where = [];
		foreach ($this->phpFiles() as $relative) {
			if ($relative === self::SEAM) {
				continue;
			}

			$lines = $this->flagLines(relative: $relative, pattern: '/_multitenancy:\s*false/');
			if ($lines !== []) {
				$found[$relative] = count($lines);
				foreach ($lines as $line) {
					$where[] = $relative . ':' . $line;
				}
			}
		}

		$expected = self::RECORDED_ORGANISATION_BYPASSES;
		ksort($expected);
		ksort($found);

		$this->assertSame(
			$expected,
			$found,
			"The set of `_multitenancy: false` call sites outside SystemContextSeam changed.\nFound:\n"
			. implode("\n", $where)
			. "\nA new cross-organisation read goes through SystemContextSeam with a literal reason."
			. ' A removed one: delete its entry from RECORDED_ORGANISATION_BYPASSES and its record.'
		);
	}//end testOrganisationBypassesOutsideTheSeamAreRecorded()

	/**
	 * The seam itself passes no flag: it elevates through OpenRegister's runAsSystem().
	 *
	 * @return void
	 */
	public function testTheSeamPassesNoFlag(): void {
		$this->assertFileExists($this->root . '/' . self::SEAM);
		$this->assertSame([], $this->flagLines(relative: self::SEAM, pattern: '/_(rbac|multitenancy):\s*false/'));
		$this->assertStringContainsString('runAsSystem(', (string)file_get_contents($this->root . '/' . self::SEAM));
	}//end testTheSeamPassesNoFlag()

	/**
	 * No app-local tenant class, service or middleware exists.
	 *
	 * @return void
	 */
	public function testNoAppLocalTenantClassExists(): void {
		$tenantFiles = [];
		foreach ($this->phpFiles() as $relative) {
			if (preg_match('/(^|\/)Tenant[A-Za-z]*\.php$/', $relative) === 1) {
				$tenantFiles[] = $relative;
			}

			foreach (file($this->root . '/' . $relative) as $number => $line) {
				if (preg_match('/^\s*(final\s+|abstract\s+)?(class|interface|trait)\s+Tenant\w*/', $line) === 1) {
					$tenantFiles[] = $relative . ':' . ($number + 1);
				}
			}
		}

		$this->assertSame([], $tenantFiles, "Tenancy is OpenRegister's; Filinq holds no tenant construct:\n" . implode("\n", $tenantFiles));
	}//end testNoAppLocalTenantClassExists()

	/**
	 * No schema declares an organisation or tenant property.
	 *
	 * @return void
	 */
	public function testNoSchemaDeclaresAnOrganisationProperty(): void {
		$register = json_decode((string)file_get_contents($this->root . '/lib/Settings/filinq_register.json'), true);
		$schemas  = ($register['components']['schemas'] ?? []);
		$this->assertNotSame([], $schemas, 'The register declares no schemas; the scan inspected nothing.');

		$offending = [];
		foreach ($schemas as $slug => $schema) {
			foreach (array_keys((array)($schema['properties'] ?? [])) as $property) {
				$name = $slug . '.' . $property;
				if (preg_match('/^(organi[sz]ation(id|uuid)?|tenant\w*)$/i', (string)$property) === 1
					&& in_array($name, self::RECORDED_TENANT_NAMED_PROPERTIES, true) === false
				) {
					$offending[] = $name;
				}
			}
		}

		$this->assertSame(
			[],
			$offending,
			"An object's organisation lives in OpenRegister's envelope, never in a schema property:\n" . implode("\n", $offending)
		);
	}//end testNoSchemaDeclaresAnOrganisationProperty()

	/**
	 * The recorded tenant-named properties still exist; a stale record hides nothing.
	 *
	 * @return void
	 */
	public function testRecordedTenantNamedPropertiesStillExist(): void {
		$register = json_decode((string)file_get_contents($this->root . '/lib/Settings/filinq_register.json'), true);
		foreach (self::RECORDED_TENANT_NAMED_PROPERTIES as $name) {
			[$slug, $property] = explode('.', $name, 2);
			$this->assertArrayHasKey(
				$property,
				(array)($register['components']['schemas'][$slug]['properties'] ?? []),
				$name . ' is gone; remove it from RECORDED_TENANT_NAMED_PROPERTIES.'
			);
		}
	}//end testRecordedTenantNamedPropertiesStillExist()

	/**
	 * Every PHP file under lib/, repository-relative.
	 *
	 * @return array<int, string>
	 */
	private function phpFiles(): array {
		$files    = [];
		$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->root . '/lib'));
		foreach ($iterator as $file) {
			if ($file->isFile() === true && $file->getExtension() === 'php') {
				$files[] = substr($file->getPathname(), (strlen($this->root) + 1));
			}
		}

		sort($files);

		return $files;
	}//end phpFiles()

	/**
	 * Line numbers in a file where the pattern matches code, not a comment.
	 *
	 * @param string $relative Repository-relative path.
	 * @param string $pattern  The regular expression.
	 *
	 * @return array<int, int> 1-based line numbers.
	 */
	private function flagLines(string $relative, string $pattern): array {
		$lines = [];
		foreach (file($this->root . '/' . $relative) as $number => $line) {
			$trimmed = ltrim($line);
			if (str_starts_with($trimmed, '*') === true
				|| str_starts_with($trimmed, '//') === true
				|| str_starts_with($trimmed, '/*') === true
			) {
				continue;
			}

			if (preg_match($pattern, $trimmed) === 1) {
				$lines[] = ($number + 1);
			}
		}

		return $lines;
	}//end flagLines()
}//end class
