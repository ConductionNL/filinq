<?php

/**
 * No BSN and no raw token anywhere
 *
 * A broker whose subject deliberately embeds a BSN-like value, followed from
 * the callback through the signing act to the completed request: the store,
 * the saved records, the audit entries and the logs carry the pseudonym and
 * the hash only.
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
use OCA\Filinq\Service\SignerAuth\SignerAuthContext;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;

/**
 * REQ-DDSIR-004 scenario "No BSN and no raw token anywhere".
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\SignerAuth
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class EvidenceMinimisationTest extends TestCase {
	use AssuranceGateHarness;

	/**
	 * A BSN that passes the eleven-test, embedded in the broker's subject.
	 *
	 * @var string
	 */
	private const BSN = '123456782';

	/**
	 * Callback, gate and completion leave only the pseudonym and the hash behind.
	 *
	 * @return void
	 */
	public function testNothingDownstreamOfTheCallbackHoldsTheBsnOrTheToken(): void {
		$this->configureBroker();
		$provider = $this->broker();
		$challenge = $provider->initiateAuthentication(new SignerAuthContext(requestId: 'req-1', signerId: 'signer-1', requiredAssurance: 'substantial', userId: 'alice'));
		$this->nextClaims = ['sub' => 'bsn:' . self::BSN] + $this->claimsFor(
			nonce: $this->queryParam(url: $challenge->url, name: 'nonce'),
			acr: 'urn:oasis:names:tc:SAML:2.0:ac:classes:Smartcard'
		);
		$evidence = $provider->completeAuthentication(
			['code' => 'c', 'state' => $this->queryParam(url: $challenge->url, name: 'state'), 'requestId' => 'req-1', 'signerId' => 'signer-1', 'userId' => 'alice']
		);
		$this->evidenceStore()->put(requestId: 'req-1', signerId: 'signer-1', evidence: $evidence);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);
		$gate = $this->assuranceGate(userSession: $session);

		$acted = $gate->evidenceForAct(
			request: ['id' => 'req-1', 'requiredAssurance' => 'substantial'],
			signer: ['id' => 'signer-1', 'signingRequestId' => 'req-1'],
			verifiedActor: null,
			actorUserId: 'alice',
			now: new DateTimeImmutable()
		);
		$signerRecord = ['id' => 'signer-1', 'status' => 'SIGNED', 'identityEvidence' => $acted->toArray()];
		$audit = ['identityEvidence' => $acted->toArray()];
		$completed = $gate->withResolvedAssurance(request: ['id' => 'req-1'], signers: ['signer-1' => $signerRecord]);

		$everything = implode(
			"\n",
			[
				(string)json_encode($this->sessionData),
				(string)json_encode($signerRecord),
				(string)json_encode($audit),
				(string)json_encode($completed),
				implode("\n", $this->logLines),
			]
		);

		$this->assertSame('substantial', $completed['resolvedAssurance']);
		$this->assertStringNotContainsString(self::BSN, $everything);
		$this->assertStringNotContainsString($this->lastIdToken, $everything);
		$this->assertStringNotContainsString('at-not-kept', $everything);
		$this->assertStringContainsString(hash('sha256', $this->lastIdToken), $everything);
		$this->assertStringContainsString($acted->subjectPseudonym, $everything);

	}//end testNothingDownstreamOfTheCallbackHoldsTheBsnOrTheToken()
}//end class
