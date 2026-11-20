<?php

/**
 * Unit tests for ConsentDecisionAgent.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/hermiq-ai-tooling/tasks.md#2-the-consent-decision-tool-code
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use InvalidArgumentException;
use OCA\Filinq\Kernel\Contracts\ConsentCrudServiceInterface;
use OCA\Filinq\Mcp\FilinqScannableServices;
use OCA\Filinq\Service\ConsentDecisionAgent;
use OCA\OpenRegister\Mcp\Attribute\McpTool;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ReflectionMethod;

/**
 * recordConsentDecision: a consent officer's decision by id, status fields
 * back, the in-app rules, and one not-found for everything out of reach.
 *
 * @spec openspec/changes/hermiq-ai-tooling/specs/filinq-mcp-surface/spec.md#requirement-an-approval-gated-consent-decision-tool-exists-req-ddhat-002
 */
#[CoversClass(ConsentDecisionAgent::class)]
#[UsesClass(FilinqScannableServices::class)]
class ConsentDecisionAgentTest extends TestCase {

	/**
	 * The stored record, citizen data included.
	 *
	 * @var array<string, mixed>
	 */
	private const RECORD = [
		'id'                 => 'c-1',
		'consentStatus'      => 'pending',
		'notificationStatus' => 'sent',
		'publicationDecision' => 'pending',
		'contactEmail'       => 'j.jansen@example.nl',
		'contactAddress'     => 'Dorpsstraat 1, Zuiddrecht',
		'entityText'         => 'Jan Jansen',
		'objectionReason'    => 'privacy',
		'@self'              => ['owner' => 'anna'],
	];

	/**
	 * Each updateConsentStatus() call: [id, register, schema, data, uid, via].
	 *
	 * @var array<int, array<int, mixed>>
	 */
	private array $updates = [];

	/**
	 * The tool for a principal, over consent records that hold `c-1` only.
	 *
	 * @param string|null           $uid     The principal, or null for none.
	 * @param list<string>          $groups  Its groups.
	 * @param \Throwable|null       $failure What the update throws, if anything.
	 * @param array<string, string>|null $config The consent register, or null when unconfigured.
	 *
	 * @return ConsentDecisionAgent
	 */
	private function agent(
		?string $uid,
		array $groups = ['filinq-woo-officers'],
		?\Throwable $failure = null,
		?array $config = ['register' => 'filinq', 'schema' => 'publicationConsent'],
	): ConsentDecisionAgent {
		$consents = $this->createMock(ConsentCrudServiceInterface::class);
		$consents->method('getConsentConfig')->willReturn($config);
		$consents->method('getConsent')->willReturnCallback(static fn (string $id): ?array => ($id === 'c-1' ? self::RECORD : null));
		$consents->method('updateConsentStatus')->willReturnCallback(
			function (string $id, string $register, string $schema, array $data, ?IUser $user = null, string $via = '') use ($failure): array {
				$this->updates[] = [$id, $register, $schema, $data, $user?->getUID(), $via];
				if ($failure !== null) {
					throw $failure;
				}

				return array_merge(self::RECORD, ['consentStatus' => $data['consentStatus']]);
			}
		);

		$session = $this->createMock(IUserSession::class);
		$user    = null;
		if ($uid !== null) {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn($uid);
		}

		$session->method('getUser')->willReturn($user);

		$groupManager = $this->createMock(IGroupManager::class);
		$groupManager->method('isAdmin')->willReturn(false);
		$groupManager->method('isInGroup')->willReturnCallback(static fn (string $who, string $group): bool => in_array($group, $groups, true));

		return new ConsentDecisionAgent(consents: $consents, userSession: $session, groups: $groupManager, logger: new NullLogger());
	}//end agent()

	/**
	 * An officer's decision goes through the in-app update with `via: mcp`
	 * and the officer as the acting user; the answer holds status fields only.
	 *
	 * @return void
	 */
	public function testAnOfficersDecisionIsRecordedWithStatusFieldsOnly(): void {
		$result = $this->agent(uid: 'ingrid')->recordConsentDecision(consentId: 'c-1', consentStatus: 'objection_received', evidence: 'brief van 2 oktober');

		$this->assertSame(
			[['c-1', 'filinq', 'publicationConsent', ['consentStatus' => 'objection_received', 'evidence' => 'brief van 2 oktober'], 'ingrid', 'mcp']],
			$this->updates
		);
		$this->assertSame(
			['status' => 'recorded', 'consentId' => 'c-1', 'consentStatus' => 'objection_received', 'notificationStatus' => 'sent', 'publicationDecision' => 'pending'],
			$result
		);

		$answer = (string)json_encode($result);
		foreach (['contactEmail', 'contactAddress', 'entityText', 'objectionReason', 'j.jansen', 'Dorpsstraat', 'Jan Jansen', 'owner'] as $leak) {
			$this->assertStringNotContainsString($leak, $answer);
		}
	}//end testAnOfficersDecisionIsRecordedWithStatusFieldsOnly()

	/**
	 * An unknown id, a caller who is no consent officer, no caller and an
	 * unconfigured register all answer the same not-found, and nothing is written.
	 *
	 * @return void
	 */
	public function testAnInaccessibleIdAnswersLikeANonexistentOne(): void {
		$missing = $this->agent(uid: 'ingrid')->recordConsentDecision(consentId: 'c-404', consentStatus: 'no_response');
		$this->assertSame(['status' => 'not_found', 'consentId' => 'c-404'], $missing);

		foreach ([
			$this->agent(uid: 'anna', groups: []),
			$this->agent(uid: null),
			$this->agent(uid: 'ingrid', config: null),
		] as $agent) {
			$this->assertSame(['status' => 'not_found', 'consentId' => 'c-404'], $agent->recordConsentDecision(consentId: 'c-404', consentStatus: 'no_response'));
			$this->assertSame(array_keys($missing), array_keys($agent->recordConsentDecision(consentId: 'c-1', consentStatus: 'no_response')));
		}

		$this->assertSame([], $this->updates);
	}//end testAnInaccessibleIdAnswersLikeANonexistentOne()

	/**
	 * A move the consent flow does not allow is refused with the rule's own
	 * sentence; a failure says failed and nothing more.
	 *
	 * @return void
	 */
	public function testAnInvalidMoveIsRefusedWithTheRule(): void {
		$refused = $this->agent(uid: 'ingrid', failure: new InvalidArgumentException('consentStatus cannot move from "pending" to "anonymized".', 400))
			->recordConsentDecision(consentId: 'c-1', consentStatus: 'anonymized');
		$this->assertSame(['status' => 'refused', 'consentId' => 'c-1', 'reason' => 'consentStatus cannot move from "pending" to "anonymized".'], $refused);

		$failed = $this->agent(uid: 'ingrid', failure: new \RuntimeException('db down: c-1 Jan Jansen'))
			->recordConsentDecision(consentId: 'c-1', consentStatus: 'no_response');
		$this->assertSame(['status' => 'failed', 'consentId' => 'c-1'], $failed);
	}//end testAnInvalidMoveIsRefusedWithTheRule()

	/**
	 * The tool is an honestly hinted, idempotent update, so Hermiq holds it
	 * for a human's approval, and it is in the scannable list.
	 *
	 * @return void
	 */
	public function testTheToolIsAnApprovalGatedIdempotentWrite(): void {
		$attributes = (new ReflectionMethod(ConsentDecisionAgent::class, 'recordConsentDecision'))->getAttributes(McpTool::class);
		$this->assertCount(1, $attributes);

		$tool = $attributes[0]->newInstance();
		$this->assertSame('recordConsentDecision', $tool->name);
		$this->assertSame('update', $tool->scope);
		$this->assertFalse($tool->readOnlyHint);
		$this->assertFalse($tool->destructiveHint);
		$this->assertTrue($tool->idempotentHint);
		$this->assertStringContainsString('approve', (string)$tool->description);
		$this->assertContains(ConsentDecisionAgent::class, (new FilinqScannableServices())->getScannableServiceClasses());
	}//end testTheToolIsAnApprovalGatedIdempotentWrite()

	/**
	 * The consent domain has exactly one tool: no other consent class carries
	 * `#[McpTool]`, so no search, list or get over consent records exists.
	 *
	 * @return void
	 */
	public function testTheConsentDomainHasExactlyOneTool(): void {
		$sources = glob(__DIR__ . '/../../../lib/Service/Consent*.php') ?: [];
		$this->assertNotEmpty($sources, 'The consent sources must be found, or this test proves nothing.');

		$attributes = 0;
		foreach ($sources as $path) {
			$count = substr_count((string)file_get_contents($path), '#[McpTool');
			if (basename($path) !== 'ConsentDecisionAgent.php') {
				$this->assertSame(0, $count, basename($path) . ' must not expose an MCP tool.');
			}

			$attributes += $count;
		}

		$this->assertSame(1, $attributes);
	}//end testTheConsentDomainHasExactlyOneTool()
}//end class
