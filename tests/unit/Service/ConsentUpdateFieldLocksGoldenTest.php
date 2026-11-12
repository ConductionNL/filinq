<?php

/**
 * Consent update: the server-owned fields and the policy lock, every allowed and refused case.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/consent-management/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use InvalidArgumentException;
use OCA\Filinq\Service\ConsentUpdateHandler;
use OCP\App\IAppManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use ReflectionMethod;

/**
 * Pins the two field locks of a consent update: allowed updates pass, refused ones throw the exact message.
 */
class ConsentUpdateFieldLocksGoldenTest extends TestCase {

	/**
	 * Run one guard and answer 'allowed' or the refusal message.
	 *
	 * @param string               $guard    The guard method.
	 * @param array<string, mixed> $existing The record's current data.
	 * @param array<string, mixed> $data     The proposed update.
	 *
	 * @return string
	 */
	private function verdict(string $guard, array $existing, array $data): string {
		$handler = new ConsentUpdateHandler(
			$this->createMock(LoggerInterface::class),
			$this->createMock(ContainerInterface::class),
			$this->createMock(IAppManager::class)
		);

		try {
			(new ReflectionMethod($handler, $guard))->invoke($handler, $existing, $data);
		} catch (InvalidArgumentException $e) {
			return $e->getMessage();
		}

		return 'allowed';

	}//end verdict()

	/**
	 * Server-owned fields may be echoed or left empty, never changed or fabricated.
	 *
	 * @return void
	 */
	public function testServerControlledFields(): void {
		$guard = 'guardServerControlledFields';
		$existing = ['policyMatch' => 'rule-1', 'matchKind' => 'prohibition', 'consentStatus' => 'pending'];

		$this->assertSame('allowed', $this->verdict($guard, $existing, ['consentStatus' => 'x']));
		$this->assertSame('allowed', $this->verdict($guard, $existing, ['policyMatch' => 'rule-1', 'matchKind' => 'prohibition']));
		$this->assertSame('allowed', $this->verdict($guard, [], ['policyMatch' => null, 'matchKind' => '']));
		$this->assertSame('allowed', $this->verdict($guard, ['policyMatch' => ''], ['policyMatch' => null]));
		$this->assertSame('allowed', $this->verdict($guard, ['policyMatch' => null], ['policyMatch' => '']));

		$refusal = '%s is server-controlled and cannot be modified by the caller.';
		$this->assertSame(sprintf($refusal, 'policyMatch'), $this->verdict($guard, $existing, ['policyMatch' => 'rule-2']));
		$this->assertSame(sprintf($refusal, 'policyMatch'), $this->verdict($guard, $existing, ['policyMatch' => null, 'matchKind' => 'x']));
		$this->assertSame(sprintf($refusal, 'matchKind'), $this->verdict($guard, $existing, ['matchKind' => 'standing_consent']));
		$this->assertSame(sprintf($refusal, 'policyMatch'), $this->verdict($guard, [], ['policyMatch' => 'rule-9']));
		$this->assertSame(sprintf($refusal, 'matchKind'), $this->verdict($guard, ['matchKind' => 0], ['matchKind' => '0']));
		$this->assertSame(sprintf($refusal, 'policyMatch'), $this->verdict($guard, ['policyMatch' => ''], ['policyMatch' => 0]));

	}//end testServerControlledFields()

	/**
	 * An unmatched record is free; a prohibition locks status and decision; standing consent frees the decision only.
	 *
	 * @return void
	 */
	public function testPolicyPreemptedTransition(): void {
		$guard = 'guardPolicyPreemptedTransition';
		$prohibited = ['policyMatch' => 'rule-1', 'matchKind' => 'prohibition', 'consentStatus' => 'anonymized', 'publicationDecision' => 'anonymize'];
		$standing = ['policyMatch' => 'rule-2', 'matchKind' => 'standing_consent', 'consentStatus' => 'consent_given', 'publicationDecision' => 'publish'];

		$this->assertSame('allowed', $this->verdict($guard, ['consentStatus' => 'pending'], ['consentStatus' => 'consent_given']));
		$this->assertSame('allowed', $this->verdict($guard, ['policyMatch' => '', 'consentStatus' => 'a'], ['consentStatus' => 'b']));
		$this->assertSame('allowed', $this->verdict($guard, $prohibited, ['note' => 'x', 'consentStatus' => 'anonymized']));
		$this->assertSame('allowed', $this->verdict($guard, $standing, ['publicationDecision' => 'anonymize']));
		$this->assertSame('allowed', $this->verdict($guard, $standing, ['publicationDecision' => 'anonymize', 'consentStatus' => 'consent_given']));

		$this->assertSame(
			'consentStatus "consent_given" rejected on policy-pre-empted record (policyMatch=rule-1, current=anonymized).',
			$this->verdict($guard, $prohibited, ['consentStatus' => 'consent_given', 'publicationDecision' => 'publish'])
		);
		$this->assertSame(
			'publicationDecision "publish" rejected on policy-pre-empted record (policyMatch=rule-1, current=anonymize).',
			$this->verdict($guard, $prohibited, ['publicationDecision' => 'publish'])
		);
		$this->assertSame(
			'consentStatus "withdrawn" rejected on policy-pre-empted record (policyMatch=rule-2, current=consent_given).',
			$this->verdict($guard, $standing, ['consentStatus' => 'withdrawn'])
		);
		$this->assertSame(
			'consentStatus "withdrawn" rejected on policy-pre-empted record (policyMatch=7, current=).',
			$this->verdict($guard, ['policyMatch' => 7], ['consentStatus' => 'withdrawn', 'publicationDecision' => 'x'])
		);

	}//end testPolicyPreemptedTransition()
}//end class
