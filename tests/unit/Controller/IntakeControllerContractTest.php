<?php

/**
 * Wire-contract tests for the three newly-exposed intake endpoints
 *
 * Covers `intake#detached` (GET api/intake/detached),
 * `intake#declareRouting` (POST api/intake/routing-rules) and
 * `intake#decideParty` (POST api/intake/party-decisions).
 *
 * `decideParty` is the one with a check of its own: the decision is a closed
 * vocabulary, and a value outside it is refused with a 400 before the service
 * is reached at all. That matters because a correction stored under an unknown
 * decision would rank future suggestions against something nobody can read.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\IntakeController;
use OCA\Filinq\Exception\IntakeRefusedException;
use OCA\Filinq\Service\IntakeDetachmentService;
use OCA\Filinq\Service\IntakeRoutingService;
use OCA\Filinq\Service\IntakeService;
use OCA\Filinq\Service\PartySuggestionService;
use OCP\AppFramework\Http;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;

/**
 * Tests the wire contract of the three intake endpoints.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class IntakeControllerContractTest extends TestCase {

	/**
	 * What the services were asked, in order.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $asked = [];

	/**
	 * The intake service double.
	 *
	 * @var IntakeService
	 */
	private IntakeService $intake;

	/**
	 * The routing service double.
	 *
	 * @var IntakeRoutingService
	 */
	private IntakeRoutingService $routing;

	/**
	 * The party suggestion service double.
	 *
	 * @var PartySuggestionService
	 */
	private PartySuggestionService $parties;

	/**
	 * Build the recording services once per test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->intake = $this->createMock(IntakeService::class);
		$this->intake->method('listDetached')->willReturnCallback(
			function (): array {
				$this->asked[] = ['call' => 'listDetached'];

				return [['uuid' => 'intake-1'], ['uuid' => 'intake-2']];
			}
		);

		$this->routing = $this->createMock(IntakeRoutingService::class);
		$this->routing->method('declare')->willReturnCallback(
			function (string $declaringApp, string $typeReference, string $routeTo, bool $requiresAcceptance): array {
				$this->asked[] = [
					'call' => 'declare',
					'declaringApp' => $declaringApp,
					'typeReference' => $typeReference,
					'routeTo' => $routeTo,
					'requiresAcceptance' => $requiresAcceptance,
				];

				return [
					'uuid' => 'rule-1',
					'declaringApp' => $declaringApp,
					'typeReference' => $typeReference,
					'routeTo' => $routeTo,
					'requiresAcceptance' => $requiresAcceptance,
				];
			}
		);

		$this->parties = $this->createMock(PartySuggestionService::class);
		$this->parties->method('recordDecision')->willReturnCallback(
			function (string $sender, string $decision, array $suggested, array $accepted = [], string $intakeDocument = ''): array {
				$this->asked[] = [
					'call' => 'recordDecision',
					'sender' => $sender,
					'decision' => $decision,
					'suggested' => $suggested,
					'accepted' => $accepted,
					'intakeDocument' => $intakeDocument,
				];

				return ['uuid' => 'correction-1', 'decision' => $decision];
			}
		);

	}//end setUp()

	/**
	 * A controller over the recording services.
	 *
	 * @param bool $signedIn Whether somebody is logged in.
	 *
	 * @return IntakeController The controller.
	 */
	private function controller(bool $signedIn = true): IntakeController {
		$session = $this->createMock(IUserSession::class);
		if ($signedIn === true) {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn('anna');
			$session->method('getUser')->willReturn($user);
		} else {
			$session->method('getUser')->willReturn(null);
		}

		return new IntakeController(
			'filinq',
			$this->createMock(IRequest::class),
			$this->intake,
			$this->createMock(IntakeDetachmentService::class),
			$this->routing,
			$this->parties,
			$session
		);

	}//end controller()

	/**
	 * The detached worklist answers results and a total that agrees with them.
	 *
	 * @return void
	 */
	public function testDetachedAnswersTheWorklistAndAMatchingTotal(): void {
		$response = $this->controller()->detached();

		$this->assertSame(Http::STATUS_OK, $response->getStatus(), 'the worklist answers 200');
		$data = $response->getData();
		$this->assertSame(['intake-1', 'intake-2'], array_column($data['results'], 'uuid'), 'the rows come back');
		$this->assertSame(count($data['results']), $data['total'], 'and the total counts the rows in this same answer');

	}//end testDetachedAnswersTheWorklistAndAMatchingTotal()

	/**
	 * A declaration carries all four fields to the service and back.
	 *
	 * @return void
	 */
	public function testDeclareRoutingCarriesTheWholeDeclaration(): void {
		$response = $this->controller()->declareRouting(
			declaringApp: 'dossiq',
			typeReference: 'bezwaar',
			routeTo: 'juridische-zaken',
			requiresAcceptance: true
		);

		$this->assertSame(Http::STATUS_OK, $response->getStatus(), 'a declaration answers 200');
		$this->assertSame(
			[
				'call' => 'declare',
				'declaringApp' => 'dossiq',
				'typeReference' => 'bezwaar',
				'routeTo' => 'juridische-zaken',
				'requiresAcceptance' => true,
			],
			$this->asked[0],
			'all four fields reach the service under their own names'
		);
		$this->assertTrue(
			$response->getData()['requiresAcceptance'],
			'and the acceptance requirement comes back, so the declaring app can read what was stored'
		);

	}//end testDeclareRoutingCarriesTheWholeDeclaration()

	/**
	 * A declaration that names nothing is refused with the service's own status.
	 *
	 * @return void
	 */
	public function testAnIncompleteDeclarationIsRefusedWithItsOwnStatus(): void {
		$routing = $this->createMock(IntakeRoutingService::class);
		$routing->method('declare')->willThrowException(
			new IntakeRefusedException(
				message: 'A routing declaration names the app making it and the record type it is about.',
				status: 400
			)
		);
		$this->routing = $routing;

		$response = $this->controller()->declareRouting(routeTo: 'juridische-zaken');

		$this->assertSame(
			Http::STATUS_BAD_REQUEST,
			$response->getStatus(),
			'the refusal keeps the status the service chose, rather than becoming a 500'
		);
		$this->assertStringContainsString(
			'names the app making it',
			($response->getData()['error'] ?? ''),
			'and says what is missing'
		);

	}//end testAnIncompleteDeclarationIsRefusedWithItsOwnStatus()

	/**
	 * A party decision passes the whole correction through.
	 *
	 * @return void
	 */
	public function testDecidePartyCarriesTheWholeCorrection(): void {
		$suggested = ['name' => 'J. Jansen', 'email' => 'j@example.nl'];
		$accepted = ['name' => 'Jan Jansen', 'email' => 'j@example.nl'];

		$response = $this->controller()->decideParty(
			sender: 'post@example.nl',
			decision: 'edited',
			suggested: $suggested,
			accepted: $accepted,
			intakeDocument: 'intake-1'
		);

		$this->assertSame(Http::STATUS_OK, $response->getStatus(), 'a decision answers 200');
		$this->assertSame($suggested, ($this->asked[0]['suggested'] ?? []), 'what was proposed reaches the service');
		$this->assertSame($accepted, ($this->asked[0]['accepted'] ?? []), 'and so does what was actually filed');
		$this->assertSame(
			'intake-1',
			($this->asked[0]['intakeDocument'] ?? ''),
			'named against the document it was shown on, so the correction can be traced back'
		);

	}//end testDecidePartyCarriesTheWholeCorrection()

	/**
	 * Each of the three decisions the vocabulary allows is accepted.
	 *
	 * @return void
	 */
	public function testEveryDecisionInTheVocabularyIsAccepted(): void {
		foreach (['accepted', 'edited', 'rejected'] as $decision) {
			$response = $this->controller()->decideParty(sender: 'post@example.nl', decision: $decision);

			$this->assertSame(
				Http::STATUS_OK,
				$response->getStatus(),
				$decision . ' is one of the three the endpoint accepts'
			);
		}

		$this->assertSame(
			['accepted', 'edited', 'rejected'],
			array_column($this->asked, 'decision'),
			'and each reaches the service as itself'
		);

	}//end testEveryDecisionInTheVocabularyIsAccepted()

	/**
	 * A decision outside the vocabulary is refused before the service is reached.
	 *
	 * @return void
	 */
	public function testADecisionOutsideTheVocabularyNeverReachesTheService(): void {
		$response = $this->controller()->decideParty(sender: 'post@example.nl', decision: 'maybe');

		$this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus(), 'an unknown decision is a 400');
		$this->assertSame(
			'A party decision is accepted, edited or rejected.',
			($response->getData()['error'] ?? ''),
			'and the body names the three that are allowed'
		);
		$this->assertSame(
			[],
			$this->asked,
			'nothing was stored: a correction under an unknown decision would rank future suggestions against something unreadable'
		);

	}//end testADecisionOutsideTheVocabularyNeverReachesTheService()

	/**
	 * All three refuse an anonymous caller with 401.
	 *
	 * @return void
	 */
	public function testAllThreeRefuseAnAnonymousCaller(): void {
		$controller = $this->controller(signedIn: false);

		$responses = [
			'detached' => $controller->detached(),
			'declareRouting' => $controller->declareRouting(declaringApp: 'dossiq', typeReference: 'bezwaar'),
			'decideParty' => $controller->decideParty(sender: 'post@example.nl', decision: 'accepted'),
		];

		foreach ($responses as $endpoint => $response) {
			$this->assertSame(
				Http::STATUS_UNAUTHORIZED,
				$response->getStatus(),
				$endpoint . ' refuses an anonymous caller'
			);
		}

		$this->assertSame([], $this->asked, 'and no service was reached on the way');

	}//end testAllThreeRefuseAnAnonymousCaller()
}//end class
