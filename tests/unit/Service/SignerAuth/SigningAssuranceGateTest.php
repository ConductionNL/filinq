<?php

/**
 * Unit tests for SigningAssuranceGate
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\SignerAuth
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\SignerAuth;

require_once __DIR__ . '/AssuranceGateHarness.php';

use DateTimeImmutable;
use InvalidArgumentException;
use OCA\Filinq\Exception\StepUpRequiredException;
use OCA\Filinq\Service\SignerAuth\IdentityEvidence;
use OCA\Filinq\Service\SignerAuth\SigningAssuranceGate;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;

/**
 * REQ-DDSIR-002 (floors at creation), REQ-DDSIR-003 (the gate), the guardian's
 * stronger check (REQ-DDSIR-009 with task 3.1) and REQ-DDSIR-007 (resolved assurance).
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\SignerAuth
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class SigningAssuranceGateTest extends TestCase {
	use AssuranceGateHarness;

	/**
	 * The moment every act happens at.
	 *
	 * @var DateTimeImmutable
	 */
	private DateTimeImmutable $now;

	/**
	 * A session for alice.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->now = new DateTimeImmutable();

	}//end setUp()

	/**
	 * The gate over alice's session.
	 *
	 * @return SigningAssuranceGate
	 */
	private function gate(): SigningAssuranceGate {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);

		return $this->assuranceGate(userSession: $session);

	}//end gate()

	/**
	 * Broker evidence at an assurance and age, kept for request r1 and signer s1.
	 *
	 * @param string $assurance The assurance.
	 * @param int $ageSeconds How long ago the signer authenticated.
	 * @param string $provider The provider id.
	 *
	 * @return void
	 */
	private function stepUp(string $assurance, int $ageSeconds = 30, string $provider = 'oidc-broker'): void {
		$this->evidenceStore()->put(
			requestId: 'r1',
			signerId: 's1',
			evidence: new IdentityEvidence(
				provider: $provider,
				means: 'digid',
				assurance: $assurance,
				subjectPseudonym: 'ps-abc',
				authenticatedAt: $this->now->modify('-' . $ageSeconds . ' seconds'),
				evidenceHash: str_repeat('b', 64)
			)
		);

	}//end stepUp()

	/**
	 * Run the gate for signer s1 on request r1.
	 *
	 * @param array<string, mixed> $request The request fields.
	 * @param array<string, mixed> $signer The signer fields.
	 * @param array<string, mixed>|null $actor The verified portal actor.
	 *
	 * @return IdentityEvidence
	 */
	private function act(array $request, array $signer = [], ?array $actor = null): IdentityEvidence {
		return $this->gate()->evidenceForAct(
			request: ['id' => 'r1'] + $request,
			signer: ['id' => 's1', 'signingRequestId' => 'r1', 'userId' => 'alice'] + $signer,
			verifiedActor: $actor,
			actorUserId: 'alice',
			now: $this->now
		);

	}//end act()

	/**
	 * Expect a step-up refusal with a reason.
	 *
	 * @param callable $act The act.
	 * @param string $reason The reason.
	 * @param string $required The assurance named in the hint.
	 *
	 * @return void
	 */
	private function assertStepUp(callable $act, string $reason, string $required): void {
		try {
			$act();
			$this->fail('The act should have been refused');
		} catch (StepUpRequiredException $e) {
			$this->assertSame(403, $e->getCode());
			$this->assertSame($reason, $e->stepUp()['reason']);
			$this->assertSame($required, $e->stepUp()['requiredAssurance']);
			$this->assertTrue($e->stepUp()['required']);
		}

	}//end assertStepUp()

	/**
	 * A request from before the rails, or at SES, signs with a Nextcloud login as before.
	 *
	 * @return void
	 */
	public function testALowRequestSignsWithTheNextcloudSession(): void {
		$evidence = $this->act(request: ['signatureLevel' => 'SES']);

		$this->assertSame('nextcloud-session', $evidence->provider);
		$this->assertSame('low', $evidence->assurance);

	}//end testALowRequestSignsWithTheNextcloudSession()

	/**
	 * A substantial request refuses a session-only signer with a step-up hint.
	 *
	 * @return void
	 */
	public function testASubstantialRequestRefusesASessionOnlySigner(): void {
		$this->appConfig['signer_auth_provider'] = 'oidc-broker';

		$this->assertStepUp(fn () => $this->act(request: ['requiredAssurance' => 'substantial']), 'insufficient', 'substantial');

	}//end testASubstantialRequestRefusesASessionOnlySigner()

	/**
	 * Step-up at DigiD substantial within the window unlocks the act.
	 *
	 * @return void
	 */
	public function testStepUpUnlocksTheAct(): void {
		$this->stepUp(assurance: 'substantial');

		$evidence = $this->act(request: ['requiredAssurance' => 'substantial']);

		$this->assertSame('oidc-broker', $evidence->provider);
		$this->assertSame('substantial', $evidence->assurance);

	}//end testStepUpUnlocksTheAct()

	/**
	 * Evidence older than the window does not carry over.
	 *
	 * @return void
	 */
	public function testStaleEvidenceIsRefused(): void {
		$this->stepUp(assurance: 'high', ageSeconds: 16 * 60);

		$this->assertStepUp(fn () => $this->act(request: ['requiredAssurance' => 'substantial']), 'stale', 'substantial');

	}//end testStaleEvidenceIsRefused()

	/**
	 * The window is a setting.
	 *
	 * @return void
	 */
	public function testTheWindowIsASetting(): void {
		$this->appConfig['signer_auth_evidence_max_age_minutes'] = '30';
		$this->stepUp(assurance: 'substantial', ageSeconds: 20 * 60);

		$this->assertSame('substantial', $this->act(request: ['requiredAssurance' => 'substantial'])->assurance);

	}//end testTheWindowIsASetting()

	/**
	 * An unknown acr gave low evidence, and low evidence does not sign a substantial request.
	 *
	 * @return void
	 */
	public function testLowBrokerEvidenceDoesNotSignASubstantialRequest(): void {
		$this->stepUp(assurance: 'low');

		$this->assertStepUp(fn () => $this->act(request: ['requiredAssurance' => 'substantial']), 'insufficient', 'substantial');

	}//end testLowBrokerEvidenceDoesNotSignASubstantialRequest()

	/**
	 * Evidence from a provider nobody registered is refused.
	 *
	 * @return void
	 */
	public function testEvidenceFromAnUnregisteredProviderIsRefused(): void {
		$this->stepUp(assurance: 'high', provider: 'eudi-wallet');

		$this->assertStepUp(fn () => $this->act(request: []), 'unregistered', 'low');

	}//end testEvidenceFromAnUnregisteredProviderIsRefused()

	/**
	 * A QES request is held at high, whatever evidence the signer brings.
	 *
	 * @return void
	 */
	public function testAQesRequestNeedsHighEvenWhenItAsksForLow(): void {
		$this->stepUp(assurance: 'substantial');

		$this->assertStepUp(fn () => $this->act(request: ['signatureLevel' => 'QES', 'requiredAssurance' => 'low']), 'insufficient', 'high');

	}//end testAQesRequestNeedsHighEvenWhenItAsksForLow()

	/**
	 * A guardian is held to the request's guardian assurance while the minor signs with a login.
	 *
	 * @return void
	 */
	public function testAGuardianIsHeldToTheStrongerGuardianAssurance(): void {
		$request = ['requiredAssurance' => 'low', 'guardianRequiredAssurance' => 'substantial'];

		$this->assertSame('low', $this->act(request: $request, signer: ['birthDate' => '2012-06-01'])->assurance);
		$this->assertStepUp(fn () => $this->act(request: $request, signer: ['role' => 'guardian']), 'insufficient', 'substantial');

		$this->stepUp(assurance: 'substantial');
		$this->assertSame('substantial', $this->act(request: $request, signer: ['role' => 'guardian'])->assurance);

	}//end testAGuardianIsHeldToTheStrongerGuardianAssurance()

	/**
	 * The admin's guardian minimum applies to every request.
	 *
	 * @return void
	 */
	public function testTheAdminGuardianMinimumAppliesToEveryRequest(): void {
		$this->appConfig['signer_auth_guardian_min_assurance'] = 'substantial';

		$this->assertStepUp(fn () => $this->act(request: [], signer: ['role' => 'guardian']), 'insufficient', 'substantial');
		$this->assertSame('low', $this->act(request: [])->assurance, 'Other signers are untouched');

	}//end testTheAdminGuardianMinimumAppliesToEveryRequest()

	/**
	 * A guardian never needs less than the request itself.
	 *
	 * @return void
	 */
	public function testAGuardianNeverNeedsLessThanTheRequest(): void {
		$this->assertSame(
			'high',
			$this->gate()->requiredFor(request: ['requiredAssurance' => 'high', 'guardianRequiredAssurance' => 'low'], signer: ['role' => 'guardian'])
		);

	}//end testAGuardianNeverNeedsLessThanTheRequest()

	/**
	 * A portal signer's verified trust is the evidence; below the request it is refused.
	 *
	 * @return void
	 */
	public function testAPortalSignersTrustIsTheEvidence(): void {
		$actor = ['email' => 'p@example.nl', 'subjectRef' => 'portal-sub-123456782', 'trust' => 'substantial', 'jti' => 'j1'];

		$evidence = $this->act(request: ['requiredAssurance' => 'substantial'], actor: $actor);
		$this->assertSame('portaliq', $evidence->provider);
		$this->assertSame('substantial', $evidence->assurance);
		$this->assertStringNotContainsString('123456782', (string)json_encode($evidence->toArray()));

		$this->assertStepUp(fn () => $this->act(request: ['requiredAssurance' => 'high'], actor: $actor), 'insufficient', 'high');
		$this->assertStepUp(fn () => $this->act(request: ['requiredAssurance' => 'substantial'], actor: ['trust' => 'bogus'] + $actor), 'insufficient', 'substantial');

	}//end testAPortalSignersTrustIsTheEvidence()

	/**
	 * Evidence kept for one act cannot be spent on another signer's act.
	 *
	 * @return void
	 */
	public function testEvidenceIsBoundToItsAct(): void {
		$this->stepUp(assurance: 'high');

		$this->assertStepUp(
			fn () => $this->gate()->evidenceForAct(
				request: ['id' => 'r1', 'requiredAssurance' => 'substantial'],
				signer: ['id' => 's2', 'signingRequestId' => 'r1'],
				verifiedActor: null,
				actorUserId: 'alice',
				now: $this->now
			),
			'insufficient',
			'substantial'
		);

	}//end testEvidenceIsBoundToItsAct()

	/**
	 * Consuming the evidence forgets it.
	 *
	 * @return void
	 */
	public function testConsumedEvidenceIsGone(): void {
		$this->stepUp(assurance: 'substantial');
		$this->gate()->consume(requestId: 'r1', signerId: 's1');

		$this->assertNull($this->evidenceStore()->get(requestId: 'r1', signerId: 's1'));

	}//end testConsumedEvidenceIsGone()

	/**
	 * Creation normalises upward and refuses a value off the scale.
	 *
	 * @return void
	 */
	public function testCreationNormalisesUpwardAndRefusesNonsense(): void {
		$request = $this->gate()->applyToRequest(request: ['signatureLevel' => 'QES'], data: ['requiredAssurance' => 'low', 'guardianRequiredAssurance' => 'substantial']);
		$this->assertSame('high', $request['requiredAssurance']);
		$this->assertSame('high', $request['guardianRequiredAssurance']);

		$plain = $this->gate()->applyToRequest(request: ['signatureLevel' => 'SES'], data: []);
		$this->assertSame('low', $plain['requiredAssurance']);
		$this->assertArrayNotHasKey('guardianRequiredAssurance', $plain);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionCode(400);
		$this->gate()->applyToRequest(request: ['signatureLevel' => 'SES'], data: ['requiredAssurance' => 'maximal']);

	}//end testCreationNormalisesUpwardAndRefusesNonsense()

	/**
	 * The resolved assurance is the weakest recorded one; a pre-rails signer counts as low.
	 *
	 * @return void
	 */
	public function testTheResolvedAssuranceIsTheWeakestRecordedOne(): void {
		$tuple = static fn (string $assurance): array => [
			'provider' => 'oidc-broker', 'means' => 'digid', 'assurance' => $assurance,
			'subjectPseudonym' => 'ps-x', 'authenticatedAt' => '2026-09-28T10:00:00+00:00', 'evidenceHash' => str_repeat('c', 64),
		];

		$both = $this->gate()->withResolvedAssurance(
			request: [],
			signers: ['a' => ['identityEvidence' => $tuple('high')], 'b' => ['identityEvidence' => $tuple('substantial')]]
		);
		$this->assertSame('substantial', $both['resolvedAssurance']);
		$this->assertSame(['a', 'b'], array_column($both['signerEvidence'], 'signerId'));
		$this->assertSame('high', $both['signerEvidence'][0]['assurance']);

		$legacy = $this->gate()->withResolvedAssurance(request: [], signers: ['a' => ['identityEvidence' => $tuple('high')], 'b' => []]);
		$this->assertSame('low', $legacy['resolvedAssurance']);

	}//end testTheResolvedAssuranceIsTheWeakestRecordedOne()
}//end class
