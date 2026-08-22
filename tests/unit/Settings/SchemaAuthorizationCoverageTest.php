<?php

/**
 * Coverage ratchet for the `consumer-schema-authorization-audit` change.
 *
 * OpenRegister treats an UNCONFIGURED authorization cascade as OPEN:
 * `PermissionHandler::resolveAuthorization()` returns null and every caller is
 * admitted. `_rbac: true` means "apply the cascade"; it does not mean "a cascade
 * exists". Measured on the development instance 2026-08-16, 20 of Filinq's 21
 * schemas declared none, and the register rows carried none either — so an
 * ordinary authenticated user in no groups could read AND overwrite another
 * user's template.
 *
 * This test asserts COVERAGE, not a findings count. A count passes the moment
 * someone adds an exclusion; coverage requires an actual decision per schema, and
 * a schema added without one fails on the day it is added rather than at the next
 * audit. The count returning to 20 by accretion is the failure mode this exists to
 * prevent, and that happens one un-thought-about schema at a time.
 *
 * It also pins the set of deliberate `_rbac: false` bypasses. A cascade only
 * guards callers that go through it, so a new bypass is a new hole in the control
 * this change installed — it fails here until it is recorded in
 * docs/authorization-decisions.md with a compensating control.
 *
 * Runs fully offline: it only reads files on disk.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Settings
 *
 * @author  Conduction Development Team <info@conduction.nl>
 * @license EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/consumer-schema-authorization-audit/specs/consumer-schema-authorization-audit/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Settings;

use PHPUnit\Framework\TestCase;

/**
 * Verifies every owned schema carries an explicit authorisation decision.
 */
class SchemaAuthorizationCoverageTest extends TestCase {

	/**
	 * The four CRUD actions a cascade must decide.
	 *
	 * OpenRegister fails closed per action once a block is non-empty: an action
	 * that is not listed is denied. Omitting one is therefore a silent deny
	 * rather than a syntax error, which is exactly the kind of decision that
	 * should be written down rather than inferred from an absence.
	 *
	 * @var array<int, string>
	 */
	private const ACTIONS = ['read', 'create', 'update', 'delete'];

	/**
	 * Principals that are not Nextcloud groups.
	 *
	 * `admin` and the object owner short-circuit before any group test and are
	 * never listed in a block. `public` is anonymous; no Filinq schema grants
	 * it, and testAnonymousReadIsNeverGranted() keeps it that way.
	 *
	 * @var array<int, string>
	 */
	private const PSEUDO_PRINCIPALS = ['public', 'authenticated'];

	/**
	 * Deliberate `_rbac: false` call sites, keyed by file with their count.
	 *
	 * Each is justified in docs/authorization-decisions.md. Adding one without
	 * recording it fails testRbacBypassesAreRecorded(); removing one fails too,
	 * so a bypass cannot be deleted while its justification stays behind
	 * pretending a control exists.
	 *
	 * @var array<string, int>
	 */
	private const RECORDED_BYPASSES = [
		'lib/Controller/PortalSigningReceiverController.php' => 1,
		'lib/Service/BaseLabelResolver.php'                  => 1,
		'lib/Service/BasesResolverService.php'               => 1,
		'lib/Service/BatchStateRepository.php'               => 3,
		'lib/Service/ConsentPolicyReferentValidator.php'     => 2,
		'lib/Service/CustomDictionaryRepository.php'         => 4,
		'lib/Service/DossierObjectRepository.php'            => 3,
		'lib/Service/LegalBasisCatalog.php'                  => 1,
		'lib/Service/PolicyCrudService.php'                  => 5,
		'lib/Service/PolicyMatchService.php'                 => 2,
		'lib/Service/PolicyRetroactiveService.php'           => 2,
	];

	/**
	 * Repository root.
	 *
	 * @var string
	 */
	private string $root;

	/**
	 * Decoded register configuration.
	 *
	 * @var array<string, mixed>
	 */
	private array $register;

	/**
	 * The authorisation-decisions record, as raw markdown.
	 *
	 * @var string
	 */
	private string $decisions;

	/**
	 * Load the register and the decisions record once per test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->root = realpath(__DIR__ . '/../../..');

		$registerRaw = file_get_contents($this->root . '/lib/Settings/filinq_register.json');
		$this->assertNotFalse($registerRaw, 'filinq_register.json must be readable');

		$register = json_decode($registerRaw, true);
		$this->assertIsArray($register, 'filinq_register.json must be valid JSON');
		$this->register = $register;

		$decisionsRaw = file_get_contents($this->root . '/docs/authorization-decisions.md');
		$this->assertNotFalse(
			$decisionsRaw,
			'docs/authorization-decisions.md must exist — it is where a schema\'s authorisation decision is justified'
		);
		$this->decisions = $decisionsRaw;

	}//end setUp()

	/**
	 * The schemas Filinq owns, keyed by slug.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function schemas(): array {
		$schemas = ($this->register['components']['schemas'] ?? []);
		$this->assertNotEmpty($schemas, 'the register must declare schemas');

		return $schemas;
	}//end schemas()

	/**
	 * Every schema declares a cascade covering all four CRUD actions.
	 *
	 * @return void
	 */
	public function testEverySchemaDeclaresACascade(): void {
		$undecided = [];
		$incomplete = [];

		foreach ($this->schemas() as $slug => $schema) {
			$auth = ($schema['authorization'] ?? null);
			if (is_array($auth) === false || $auth === []) {
				$undecided[] = $slug;
				continue;
			}

			foreach (self::ACTIONS as $action) {
				if (isset($auth[$action]) === false || $auth[$action] === []) {
					$incomplete[] = $slug . '.' . $action;
				}
			}
		}

		$this->assertSame(
			[],
			$undecided,
			"Schema(s) with no authorization cascade: " . implode(', ', $undecided)
			. "\nOpenRegister treats an unconfigured cascade as OPEN — every authenticated user in the"
			. " organisation may read, update and delete every object of that schema. Declare a cascade"
			. " in lib/Settings/filinq_register.json and record why in docs/authorization-decisions.md."
		);

		$this->assertSame(
			[],
			$incomplete,
			"Cascade(s) missing an action: " . implode(', ', $incomplete)
			. "\nOnce a block is non-empty OpenRegister fails closed per action, so an omitted action"
			. " denies everyone except admins and object owners. That may well be what you want — but"
			. " state it explicitly rather than leaving it to be read out of an absence."
		);

	}//end testEverySchemaDeclaresACascade()

	/**
	 * Every schema is named in the decisions record.
	 *
	 * This is the half that makes it a coverage assertion rather than a syntax
	 * check: a cascade can be present and thoughtless. A new schema fails here
	 * until someone writes down what its data is and who should reach it.
	 *
	 * @return void
	 */
	public function testEverySchemaHasARecordedJustification(): void {
		$unrecorded = [];

		foreach (array_keys($this->schemas()) as $slug) {
			if (str_contains($this->decisions, '`' . $slug . '`') === false) {
				$unrecorded[] = $slug;
			}
		}

		$this->assertSame(
			[],
			$unrecorded,
			"Schema(s) absent from docs/authorization-decisions.md: " . implode(', ', $unrecorded)
			. "\nA cascade without a recorded reason is deleted by the next person who reasons that the"
			. " data layer covers it. ConsentCrudService carries a comment written by someone defending a"
			. " real control from exactly that fate."
		);

	}//end testEverySchemaHasARecordedJustification()

	/**
	 * No schema grants anonymous read.
	 *
	 * Filinq holds consent records, signer identities and extracted invoice
	 * content. `public` also disables multi-tenancy filtering in
	 * ObjectService::searchObjectsPaginated(), so granting it widens two axes at
	 * once — which is not obvious from reading the block.
	 *
	 * @return void
	 */
	public function testAnonymousReadIsNeverGranted(): void {
		$anonymous = [];

		foreach ($this->schemas() as $slug => $schema) {
			$read = ($schema['authorization']['read'] ?? []);
			if (in_array('public', $read, true) === true) {
				$anonymous[] = $slug;
			}
		}

		$this->assertSame(
			[],
			$anonymous,
			"Schema(s) granting anonymous read: " . implode(', ', $anonymous)
			. "\n`public` in a read rule also bypasses multi-tenancy filtering, so it widens both the"
			. " authentication and the organisation axis in one word."
		);

	}//end testAnonymousReadIsNeverGranted()

	/**
	 * Every named principal is a group, not a stray value.
	 *
	 * A typo in a group name is invisible at runtime: a group that was never
	 * created and a group nobody belongs to both deny every caller, with nothing
	 * logged. Pinning the vocabulary is the only place that mistake surfaces.
	 *
	 * @return void
	 */
	public function testEveryNamedGroupIsRecorded(): void {
		$unknown = [];

		foreach ($this->schemas() as $slug => $schema) {
			foreach (self::ACTIONS as $action) {
				foreach (($schema['authorization'][$action] ?? []) as $entry) {
					// Conditional rules carry the principal under `group`.
					$principal = $entry;
					if (is_array($entry) === true) {
						$principal = ($entry['group'] ?? null);
					}

					if (is_string($principal) === false) {
						$unknown[] = $slug . '.' . $action . ' (not a string)';
						continue;
					}

					if (in_array($principal, self::PSEUDO_PRINCIPALS, true) === true) {
						continue;
					}

					if ($this->decisionsNameToken(token: $principal) === false) {
						$unknown[] = $slug . '.' . $action . ' => ' . $principal;
					}
				}
			}
		}

		$this->assertSame(
			[],
			$unknown,
			"Principal(s) not named in docs/authorization-decisions.md: " . implode(', ', $unknown)
			. "\nA misspelt group denies everyone and logs nothing, so it is indistinguishable from a"
			. " working access control. Every group must be listed in the decisions record."
		);

	}//end testEveryNamedGroupIsRecorded()

	/**
	 * Is this exact token named in the decisions record?
	 *
	 * A substring test is not good enough, and this was measured: planting the
	 * typo `docudesk-template-editor` (singular) PASSED a `str_contains()` check,
	 * because it is a prefix of the real `docudesk-template-editors`. A near-miss
	 * group name is the single failure mode this assertion exists for, so a check
	 * that cannot see it is worse than no check — it reports a control that is not
	 * there.
	 *
	 * `\b` is not usable here: group ids contain `-` and `_`, which `\b` treats
	 * inconsistently (`_` is a word character, `-` is not), so `\bfoo_bar\b`
	 * behaves differently from `\bfoo-bar\b`. The lookarounds spell out the
	 * identifier alphabet instead.
	 *
	 * @param string $token The principal to look for.
	 *
	 * @return bool True when the record names exactly this token.
	 */
	private function decisionsNameToken(string $token): bool {
		$pattern = '/(?<![A-Za-z0-9_-])' . preg_quote($token, '/') . '(?![A-Za-z0-9_-])/';

		return preg_match($pattern, $this->decisions) === 1;
	}//end decisionsNameToken()

	/**
	 * The set of deliberate RBAC bypasses has not grown.
	 *
	 * @return void
	 */
	public function testRbacBypassesAreRecorded(): void {
		$found = $this->countRbacBypasses();

		$expected = self::RECORDED_BYPASSES;
		ksort($expected);
		ksort($found);

		$this->assertSame(
			$expected,
			$found,
			"The set of `_rbac: false` call sites has changed.\n"
			. "A cascade only guards callers that go through it, so each bypass is a hole in the control"
			. " installed by consumer-schema-authorization-audit. Add the new site to"
			. " docs/authorization-decisions.md with the compensating control that makes it safe, then"
			. " update RECORDED_BYPASSES. If a bypass was REMOVED, delete its row from the doc too —"
			. " a justification outliving its call site describes a control that no longer exists."
		);

	}//end testRbacBypassesAreRecorded()

	/**
	 * Count real `_rbac: false` call sites per file, ignoring comments.
	 *
	 * ConsentCrudService contains the exact string inside a comment that FORBIDS
	 * the bypass. Counting it would record a control as a hole — the same class
	 * of error this whole change is about.
	 *
	 * @return array<string, int> Repository-relative path => call-site count.
	 */
	private function countRbacBypasses(): array {
		$counts = [];

		foreach (['lib/Controller', 'lib/Service'] as $dir) {
			$iterator = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator($this->root . '/' . $dir)
			);

			foreach ($iterator as $file) {
				if ($file->isFile() === false || $file->getExtension() !== 'php') {
					continue;
				}

				$relative = substr($file->getPathname(), (strlen($this->root) + 1));
				$hits = 0;

				foreach (file($file->getPathname()) as $line) {
					$trimmed = ltrim($line);
					// Skip docblock continuations and line comments — a mention
					// is not a call.
					if (str_starts_with($trimmed, '*') === true
						|| str_starts_with($trimmed, '//') === true
						|| str_starts_with($trimmed, '/*') === true
					) {
						continue;
					}

					if (preg_match('/_rbac:\s*false/', $trimmed) === 1) {
						$hits++;
					}
				}

				if ($hits > 0) {
					$counts[$relative] = $hits;
				}
			}
		}

		return $counts;
	}//end countRbacBypasses()

	/**
	 * The register version advanced, or the cascade never deploys.
	 *
	 * An authorisation change that is not imported is a fix-shaped commit. The
	 * importer does compare schema content, but the app-level configuration gate
	 * is keyed on info.version, and every previous Filinq register change that
	 * forgot this was inert on every existing install with no error anywhere.
	 *
	 * @return void
	 */
	public function testRegisterVersionCoversTheCascade(): void {
		$version = ($this->register['info']['version'] ?? '0.0.0');

		$this->assertTrue(
			version_compare($version, '7.9.0', '>='),
			"Register info.version is {$version}; the authorisation cascade landed in 7.9.0."
			. " A lower version means an existing install keeps its old, open schemas."
		);

	}//end testRegisterVersionCoversTheCascade()

}//end class
