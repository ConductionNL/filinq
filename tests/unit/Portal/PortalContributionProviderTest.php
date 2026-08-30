<?php

/**
 * Unit tests for the Portaliq portal contribution provider.
 *
 * Pins Filinq's ADR-046 contract-v2.1 contribution: the dependency-free
 * duck-typed shape (inert without portaliq), the v2 getAudiences() + v1
 * getAudience() pair, the per-audience read manifest (scoping map, claim
 * names, minTrust, the one-hop `via` join to signingRequest) and the
 * subject-safe field projections. Also pins every scopeField, `via` join
 * field and projected read field against the shipped register JSON at HEAD so
 * a schema drift (renamed scope property, dropped whitelist field) fails here
 * instead of silently scoping portal reads to nothing or dropping a projected
 * column.
 *
 * Also pins the contract-v2.2 `portal-signing-surface` extension
 * (REQ-DDPSS-001): the `sign`/`decline` `rowActions` on `signerSigningRequests`
 * (ids, `minTrust: substantial`, relative-endpoint shape) and the invariant
 * that `signerRecords` and the entire `data-subject` manifest carry no write
 * action. And the `portal-signing-actions` extension (REQ-DDPSA-001): the
 * SAME three acts as top-level contract-v2 A6 `endpoint` actions — `sign`,
 * `decline`, `viewDocument` — on the `signer` manifest only.
 *
 * Subjects use nil-pattern UUIDs per the change design.md Seed Data section —
 * self-evidently fake, never colliding with live data. The provider is
 * constructed directly — it is a plain dependency-free class by contract
 * (amendment A1), so no mocks and no container are involved.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Portal
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/portal-contribution/specs/portal-contribution/spec.md
 * @spec openspec/specs/portal-signing-surface/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Portal;

use OCA\Filinq\Portal\PortalContributionProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Pin the declarative portal contribution manifest.
 *
 * @spec openspec/changes/portal-contribution/specs/portal-contribution/spec.md
 */
final class PortalContributionProviderTest extends TestCase {

	/**
	 * Server-derived subject fixture for the data-subject audience (nil UUIDs).
	 *
	 * @var array<string, mixed>
	 */
	private const DATA_SUBJECT = [
		'subjectRef' => '00000000-0000-0000-0000-000000000001',
		'audience' => 'data-subject',
		'organisation' => '00000000-0000-0000-0000-000000000002',
		'trust' => 'substantial',
	];

	/**
	 * Server-derived subject fixture for the signer audience (nil UUIDs).
	 *
	 * @var array<string, mixed>
	 */
	private const SIGNER_SUBJECT = [
		'subjectRef' => '00000000-0000-0000-0000-000000000003',
		'audience' => 'signer',
		'organisation' => '00000000-0000-0000-0000-000000000002',
		'trust' => 'substantial',
	];

	/**
	 * The provider under test (direct construction — no container).
	 *
	 * @var PortalContributionProvider
	 */
	private PortalContributionProvider $provider;

	/**
	 * Construct the provider directly, as portaliq's registry would.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->provider = new PortalContributionProvider();

	}//end setUp()

	/**
	 * The provider is discoverable at its FQCN and inert without portaliq.
	 *
	 * @return void
	 */
	public function testProviderIsPlainAndDependencyFree(): void {
		$reflection = new ReflectionClass(PortalContributionProvider::class);

		$this->assertSame(
			'OCA\\Filinq\\Portal\\PortalContributionProvider',
			$reflection->getName(),
			'Provider must live at the convention FQCN portaliq probes for'
		);
		$this->assertSame([], $reflection->getInterfaceNames(), 'Duck-typed: no implements clause allowed');
		$this->assertFalse($reflection->getParentClass(), 'Provider must not extend anything');
		$this->assertNull($reflection->getConstructor(), 'Provider must have no constructor dependencies');

		$source = (string)file_get_contents((string)$reflection->getFileName());
		$stripped = (preg_replace('/\/\*.*?\*\/|\/\/[^\n]*/s', '', $source) ?? '');
		$this->assertStringNotContainsStringIgnoringCase(
			'portaliq',
			$stripped,
			'Provider code must reference no portaliq symbol (comments excluded)'
		);

	}//end testProviderIsPlainAndDependencyFree()

	/**
	 * Audiences are advertised on both contract versions.
	 *
	 * @return void
	 */
	public function testAudiencesOnBothContractVersions(): void {
		$this->assertSame(['data-subject', 'signer'], $this->provider->getAudiences());
		$this->assertSame('data-subject', $this->provider->getAudience(), 'v1 fallback must return the primary audience');
		$this->assertContains($this->provider->getAudience(), $this->provider->getAudiences());

	}//end testAudiencesOnBothContractVersions()

	/**
	 * Unknown / absent audiences yield null (fail-closed).
	 *
	 * @return void
	 */
	public function testUnknownAudienceYieldsNull(): void {
		$this->assertNull($this->provider->getContribution(['audience' => 'supplier']));
		$this->assertNull($this->provider->getContribution([]));
		$this->assertNull($this->provider->getContribution(['subjectRef' => '00000000-0000-0000-0000-000000000009']));
		$this->assertNull($this->provider->getContribution(['audience' => '']));

	}//end testUnknownAudienceYieldsNull()

	/**
	 * The data subject sees only their own consent record, projected to the
	 * subject-safe transparency + objection-rights columns, gated substantial.
	 *
	 * @return void
	 */
	public function testDataSubjectConsentCollectionIsScopedAndProjected(): void {
		$manifest = $this->provider->getContribution(self::DATA_SUBJECT);
		$this->assertIsArray($manifest);
		$this->assertSame('Filinq', $manifest['label']);
		$this->assertSame([], $manifest['actions'], 'No create/endpoint actions ship this wave');
		$this->assertSame([], $manifest['notifications']);

		$collections = $this->indexById($manifest['collections']);
		$this->assertSame(['subjectConsents'], $this->sortedKeys($collections));

		$consent = $collections['subjectConsents'];
		$this->assertSame('filinq', $consent['register'], 'the five registers collapsed into one');
		$this->assertSame('publicationConsent', $consent['schema']);
		$this->assertSame('contactRef', $consent['scopeField'], 'Scope by the contact-record reference, never PII-in-clear email');
		$this->assertSame('contactId', $consent['scopeClaim']);
		$this->assertSame('substantial', $consent['minTrust'], 'Consent/objection case files require eIDAS-substantial assurance');
		$this->assertTrue($consent['listable']);
		$this->assertSame(
			[
				'scope',
				'consentStatus',
				'objectionDeadline',
				'objectionReceivedAt',
				'objectionReason',
				'publicationDecision',
				'legalBasis',
				'validFrom',
				'validUntil',
				'consentScope',
				'consentMethod',
				'active',
			],
			$consent['fields'],
			'Only subject-safe transparency + objection-rights fields are projected'
		);

		// Staff-only / internal / other-party-linkage columns must never leak.
		$forbidden = [
			'documentId',
			'entityType',
			'entityText',
			'entityKey',
			'contactEmail',
			'contactAddress',
			'notificationStatus',
			'notificationSentAt',
			'notes',
			'matchRules',
			'policyMatch',
			'consentDocument',
		];
		foreach ($forbidden as $field) {
			$this->assertNotContains($field, $consent['fields'], "Consent projection must never expose '{$field}'");
		}

		$this->assertArrayNotHasKey(
			'rowActions',
			$consent,
			'The entire data-subject manifest carries no write action (REQ-DDPSS-001)'
		);

	}//end testDataSubjectConsentCollectionIsScopedAndProjected()

	/**
	 * The signer sees their own signerRecord, scoped by the invited email,
	 * projected to participation facts only (no signature blob / IP / userId).
	 *
	 * @return void
	 */
	public function testSignerRecordCollectionIsScopedAndProjected(): void {
		$manifest = $this->provider->getContribution(self::SIGNER_SUBJECT);
		$this->assertIsArray($manifest);
		$this->assertSame('Filinq', $manifest['label']);

		$record = $this->indexById($manifest['collections'])['signerRecords'];
		$this->assertSame('filinq', $record['register'], 'the five registers collapsed into one');
		$this->assertSame('signerRecord', $record['schema']);
		$this->assertSame('email', $record['scopeField']);
		$this->assertSame('signerEmail', $record['scopeClaim']);
		$this->assertArrayNotHasKey('via', $record, 'signerRecord is a direct scope, not a via join');
		$this->assertTrue($record['listable']);
		$this->assertSame(
			['displayName', 'status', 'order', 'signedAt', 'declineReason'],
			$record['fields'],
			'Only the signer own participation facts are projected'
		);
		$this->assertArrayNotHasKey(
			'rowActions',
			$record,
			'signerRecords is a read-only collection — no write action (REQ-DDPSS-001)'
		);

		$forbidden = ['signatureData', 'ipAddress', 'userId', 'signingRequestId', 'email'];
		foreach ($forbidden as $field) {
			$this->assertNotContains($field, $record['fields'], "signerRecord projection must never expose '{$field}'");
		}

	}//end testSignerRecordCollectionIsScopedAndProjected()

	/**
	 * The signer reaches the parent signingRequest through the one-hop via
	 * join, gated substantial, projected without any other-party column.
	 *
	 * @return void
	 */
	public function testSignerSigningRequestsUsesViaJoin(): void {
		$manifest = $this->provider->getContribution(self::SIGNER_SUBJECT);
		$this->assertIsArray($manifest);

		$collections = $this->indexById($manifest['collections']);
		$this->assertSame(['signerRecords', 'signerSigningRequests'], $this->sortedKeys($collections));

		$request = $collections['signerSigningRequests'];
		$this->assertSame('filinq', $request['register'], 'the five registers collapsed into one');
		$this->assertSame('signingRequest', $request['schema']);
		$this->assertSame('', $request['scopeField'], 'Via collections carry no direct scopeField');
		$this->assertSame('signerEmail', $request['scopeClaim']);
		$this->assertSame('substantial', $request['minTrust'], 'Binding-document metadata requires eIDAS-substantial assurance');
		$this->assertTrue($request['listable']);

		// Contract-v2.1 one-hop via join: {register, schema, scopeField, targetField}.
		$this->assertSame(
			[
				'register' => 'filinq',
				'schema' => 'signerRecord',
				'scopeField' => 'email',
				'targetField' => 'signingRequestId',
			],
			$request['via'],
			'Via join routes signerEmail -> signerRecord.email -> signingRequestId'
		);

		$this->assertSame(
			['documentName', 'signatureLevel', 'signingMode', 'status', 'deadline', 'provider'],
			$request['fields'],
			'Only the signer-relevant request facts are projected'
		);

		// Initiator identity, the full co-signer roster and the internal NC
		// file id are other-party / system-internal — never projected.
		$forbidden = ['initiatorUserId', 'signerIds', 'documentFileId'];
		foreach ($forbidden as $field) {
			$this->assertNotContains($field, $request['fields'], "signingRequest projection must never expose '{$field}'");
		}

	}//end testSignerSigningRequestsUsesViaJoin()

	/**
	 * `signerSigningRequests` carries exactly the `sign` and `decline`
	 * contract-v2.2 rowActions, each gated `minTrust: substantial`, each
	 * resolving to an instance-local relative endpoint (REQ-DDPSS-001).
	 *
	 * @return void
	 */
	public function testSignerSigningRequestsCarriesSignAndDeclineRowActions(): void {
		$manifest = $this->provider->getContribution(self::SIGNER_SUBJECT);
		$this->assertIsArray($manifest);

		$request = $this->indexById($manifest['collections'])['signerSigningRequests'];
		$this->assertArrayHasKey('rowActions', $request, 'The awaiting-signature collection must carry the sign/decline rowActions');

		$rowActions = $this->indexById($request['rowActions']);
		$this->assertSame(['decline', 'sign'], $this->sortedKeys($rowActions), 'Exactly sign + decline, no other rowAction');

		foreach (['sign', 'decline'] as $id) {
			$action = $rowActions[$id];
			$this->assertSame($id, $action['id']);
			$this->assertSame('substantial', $action['minTrust'], "rowAction '{$id}' must be eIDAS-substantial gated");
			$this->assertSame('POST', $action['method']);
			$this->assertIsString($action['endpoint']);
			$this->assertNotSame('', $action['endpoint']);
			$this->assertStringStartsWith('/', $action['endpoint'], "rowAction '{$id}' endpoint must be instance-local relative (leading slash)");
			$this->assertStringNotContainsStringIgnoringCase('://', $action['endpoint'], "rowAction '{$id}' endpoint must carry no scheme");
			$this->assertStringNotContainsString('..', $action['endpoint'], "rowAction '{$id}' endpoint must not traverse ('..')");
			$this->assertNotEmpty($action['label']);
		}

		$this->assertNotSame(
			$rowActions['sign']['endpoint'],
			$rowActions['decline']['endpoint'],
			'sign and decline must resolve to distinct endpoints'
		);

	}//end testSignerSigningRequestsCarriesSignAndDeclineRowActions()

	/**
	 * The `data-subject` audience ships NO top-level actions and no
	 * per-collection write action anywhere. The `signer` audience's ONLY
	 * top-level actions are the three A6 endpoint actions (pinned separately
	 * in `testSignerManifestDeclaresEndpointActions()`); its only
	 * per-collection write action is `signerSigningRequests`' `rowActions`.
	 *
	 * @return void
	 */
	public function testNoActionsShipThisWave(): void {
		$dataSubjectManifest = $this->provider->getContribution(self::DATA_SUBJECT);
		$this->assertIsArray($dataSubjectManifest);
		$this->assertSame([], $dataSubjectManifest['actions'], 'data-subject ships no top-level create/endpoint action');
		$this->assertSame([], $dataSubjectManifest['notifications'], 'No inbox / notification collections ship');
		foreach ($dataSubjectManifest['collections'] as $collection) {
			$this->assertArrayNotHasKey('kind', $collection, 'No inbox collections ship');
			$this->assertArrayNotHasKey('rowActions', $collection, "data-subject collection '{$collection['id']}' must carry no write action");
		}

		$signerManifest = $this->provider->getContribution(self::SIGNER_SUBJECT);
		$this->assertIsArray($signerManifest);
		$this->assertSame([], $signerManifest['notifications'], 'No inbox / notification collections ship');
		foreach ($signerManifest['collections'] as $collection) {
			$this->assertArrayNotHasKey('kind', $collection, 'No inbox collections ship');
			if ($collection['id'] !== 'signerSigningRequests') {
				$this->assertArrayNotHasKey(
					'rowActions',
					$collection,
					"Collection '{$collection['id']}' must carry no write action"
				);
			}
		}

	}//end testNoActionsShipThisWave()

	/**
	 * The `signer` manifest declares exactly the three contract-v2 A6
	 * endpoint actions — `sign`, `decline`, `viewDocument` — each
	 * `minTrust: substantial`, each an instance-local relative endpoint
	 * (portal-signing-actions REQ-DDPSA-001). The `data-subject` manifest's
	 * `actions` stays empty.
	 *
	 * @return void
	 */
	public function testSignerManifestDeclaresEndpointActions(): void {
		$manifest = $this->provider->getContribution(self::SIGNER_SUBJECT);
		$this->assertIsArray($manifest);

		$actions = $this->indexById($manifest['actions']);
		$this->assertSame(['decline', 'sign', 'viewDocument'], $this->sortedKeys($actions), 'Exactly sign + decline + viewDocument, no other action');

		$expectedMethods = ['sign' => 'POST', 'decline' => 'POST', 'viewDocument' => 'GET'];
		foreach ($expectedMethods as $id => $method) {
			$action = $actions[$id];
			$this->assertSame($id, $action['id']);
			$this->assertSame($method, $action['method']);
			$this->assertSame('substantial', $action['minTrust'], "action '{$id}' must be eIDAS-substantial gated");
			$this->assertIsString($action['endpoint']);
			$this->assertStringStartsWith('/apps/filinq/api/portal/signing/', $action['endpoint'], "action '{$id}' endpoint must be instance-local relative under the portal signing receiver path");
			$this->assertStringNotContainsStringIgnoringCase('://', $action['endpoint'], "action '{$id}' endpoint must carry no scheme");
			$this->assertStringNotContainsString('..', $action['endpoint'], "action '{$id}' endpoint must not traverse ('..')");
			$this->assertNotEmpty($action['label']);
		}

		$endpoints = array_column($actions, 'endpoint');
		$this->assertSame(count($endpoints), count(array_unique($endpoints)), 'sign/decline/viewDocument must resolve to distinct endpoints');

		$dataSubjectManifest = $this->provider->getContribution(self::DATA_SUBJECT);
		$this->assertIsArray($dataSubjectManifest);
		$this->assertSame([], $dataSubjectManifest['actions'], 'data-subject actions must stay empty');

	}//end testSignerManifestDeclaresEndpointActions()

	/**
	 * Pin the scoping map + whitelists against the shipped register JSON.
	 *
	 * Every declared scopeField, every via-join field and every projected read
	 * field must exist as a property on the declared schema in the shipped
	 * register config, so register drift breaks this test instead of silently
	 * emptying a portal scope or dropping a projected column.
	 *
	 * @return void
	 */
	public function testManifestMatchesShippedRegisterSchemas(): void {
		$schemaProperties = $this->loadRegisterSchemaProperties();

		foreach ([self::DATA_SUBJECT, self::SIGNER_SUBJECT] as $subject) {
			$manifest = $this->provider->getContribution($subject);
			$this->assertIsArray($manifest);

			foreach ($manifest['collections'] as $collection) {
				$schema = $collection['schema'];
				$this->assertArrayHasKey($schema, $schemaProperties, "Schema '{$schema}' must exist in the shipped register config");

				// A direct scopeField must exist on the target schema; a via
				// collection scopes through its join schema instead.
				if ($collection['scopeField'] !== '') {
					$this->assertContains(
						$collection['scopeField'],
						$schemaProperties[$schema],
						"scopeField '{$collection['scopeField']}' must exist on schema '{$schema}'"
					);
				}

				if (isset($collection['via']) === true) {
					$via = $collection['via'];
					$this->assertArrayHasKey($via['schema'], $schemaProperties, "via schema '{$via['schema']}' must exist");
					$this->assertContains($via['scopeField'], $schemaProperties[$via['schema']], "via scopeField '{$via['scopeField']}' must exist on '{$via['schema']}'");
					$this->assertContains($via['targetField'], $schemaProperties[$via['schema']], "via targetField '{$via['targetField']}' must exist on '{$via['schema']}'");
				}

				foreach (($collection['fields'] ?? []) as $field) {
					$this->assertContains(
						$field,
						$schemaProperties[$schema],
						"Projected field '{$field}' must exist on schema '{$schema}'"
					);
				}
			}//end foreach

			foreach ($manifest['actions'] as $action) {
				// A6 endpoint actions (portal-signing-actions REQ-DDPSA-001)
				// carry no `schema`/`fields` whitelist — they forward to a
				// receiver, they do not write an OpenRegister object directly.
				if (isset($action['schema']) === false) {
					continue;
				}

				$schema = $action['schema'];
				$this->assertArrayHasKey($schema, $schemaProperties);
				foreach (($action['fields'] ?? []) as $field) {
					$this->assertContains($field, $schemaProperties[$schema], "Whitelisted field '{$field}' must exist on schema '{$schema}'");
				}
			}
		}//end foreach

	}//end testManifestMatchesShippedRegisterSchemas()

	/**
	 * Collect schema property names from the shipped register JSON.
	 *
	 * @return array<string, array<int, string>> Map of schema name to property names.
	 */
	private function loadRegisterSchemaProperties(): array {
		$root = dirname(__DIR__, 3);
		$file = $root . '/lib/Settings/filinq_register.json';

		$decoded = json_decode((string)file_get_contents($file), true);
		$this->assertIsArray($decoded, 'Shipped register JSON must parse');

		$properties = [];
		$schemas = ($decoded['components']['schemas'] ?? []);
		foreach ($schemas as $name => $schema) {
			$properties[$name] = array_keys(($schema['properties'] ?? []));
		}

		return $properties;
	}//end loadRegisterSchemaProperties()

	/**
	 * Index manifest entries by their id.
	 *
	 * @param array<int, array<string, mixed>> $entries Collections or actions.
	 *
	 * @return array<string, array<string, mixed>> Entries keyed by id.
	 */
	private function indexById(array $entries): array {
		$indexed = [];
		foreach ($entries as $entry) {
			$indexed[$entry['id']] = $entry;
		}

		return $indexed;
	}//end indexById()

	/**
	 * Sorted key list helper for exact-set assertions.
	 *
	 * @param array<string, mixed> $entries Indexed entries.
	 *
	 * @return array<int, string> Sorted keys.
	 */
	private function sortedKeys(array $entries): array {
		$keys = array_keys($entries);
		sort($keys);

		return $keys;
	}//end sortedKeys()

}//end class
