<?php

/**
 * Unit tests for ContractController
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#4-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

require_once __DIR__ . '/../Service/Contract/InMemoryContracts.php';

use OCA\Filinq\Controller\ContractController;
use OCA\Filinq\Service\Contract\ContractDocumentText;
use OCA\Filinq\Service\Contract\ContractParties;
use OCA\Filinq\Service\Contract\ContractService;
use OCA\Filinq\Service\Contract\ContractSigningLink;
use OCA\Filinq\Service\Contract\ContractTermSuggestionService;
use OCA\Filinq\Service\SigningService;
use OCA\Filinq\Tests\Unit\Service\Contract\InMemoryContracts;
use OCP\Contacts\IManager;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * The action routes answer 404 for a contract the caller cannot read and
 * change nothing; the ordinary outcomes map to their status codes.
 */
class ContractControllerTest extends TestCase {

	/**
	 * The store behind the controller.
	 *
	 * @var InMemoryContracts
	 */
	private InMemoryContracts $store;

	/**
	 * The controller under test.
	 *
	 * @var ContractController
	 */
	private ContractController $controller;

	/**
	 * Set up.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->store = new InMemoryContracts();
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('bob');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);
		$suggestions = new ContractTermSuggestionService(
			repository: $this->store,
			text: new ContractDocumentText(rootFolder: $this->createMock(IRootFolder::class)),
			appConfig: $this->createMock(IAppConfig::class)
		);
		$this->controller = new ContractController(
			appName: 'filinq',
			request: $this->createMock(IRequest::class),
			contracts: new ContractService(repository: $this->store),
			suggestions: $suggestions,
			userSession: $session,
			logger: $this->createMock(LoggerInterface::class),
			signingLink: new ContractSigningLink(contracts: $this->store, signing: $this->createMock(SigningService::class), userSession: $session),
			parties: new ContractParties(contacts: $this->createMock(IManager::class))
		);

	}//end setUp()

	/**
	 * Renew, terminate, suggest and decide on somebody else's contract: 404, nothing written.
	 *
	 * @return void
	 */
	public function testAContractTheCallerCannotReadIsNotFound(): void {
		$this->assertSame(404, $this->controller->renew(id: 'not-mine')->getStatus());
		$this->assertSame(404, $this->controller->terminate(id: 'not-mine', reason: 'x')->getStatus());
		$this->assertSame(404, $this->controller->suggest(id: 'not-mine')->getStatus());
		$this->assertSame(404, $this->controller->decideSuggestion(id: 'not-mine', index: 0, decision: 'accepted')->getStatus());
		$this->assertSame(404, $this->controller->linkSigning(id: 'not-mine', signingRequestId: 'req-1')->getStatus());
		$this->assertSame(404, $this->controller->parties(id: 'not-mine')->getStatus());
		$this->assertSame([], $this->store->writes);

	}//end testAContractTheCallerCannotReadIsNotFound()

	/**
	 * Terminate without a reason is 400, a draft is 409, with a reason is 200.
	 *
	 * @return void
	 */
	public function testTerminateAnswers(): void {
		$active = $this->store->seed(['title' => 'A', 'status' => 'active']);
		$draft = $this->store->seed(['title' => 'D', 'status' => 'draft']);

		$this->assertSame(400, $this->controller->terminate(id: $active)->getStatus());
		$this->assertSame(409, $this->controller->terminate(id: $draft, reason: 'x')->getStatus());
		$done = $this->controller->terminate(id: $active, reason: 'Opgezegd');
		$this->assertSame(200, $done->getStatus());
		$this->assertSame('terminated', $done->getData()['status']);

	}//end testTerminateAnswers()

	/**
	 * Renewal answers both contracts.
	 *
	 * @return void
	 */
	public function testRenewAnswersBothContracts(): void {
		$active = $this->store->seed(['title' => 'A', 'status' => 'active']);

		$data = $this->controller->renew(id: $active)->getData();

		$this->assertSame('renewed', $data['contract']['status']);
		$this->assertSame('draft', $data['successor']['status']);

	}//end testRenewAnswersBothContracts()

	/**
	 * A signing request the caller cannot read is 404 on a contract they can; no id is 400.
	 *
	 * @return void
	 */
	public function testLinkSigningAnswers(): void {
		$active = $this->store->seed(['title' => 'A', 'status' => 'active']);

		$this->assertSame(400, $this->controller->linkSigning(id: $active)->getStatus());
		$this->assertSame(404, $this->controller->linkSigning(id: $active, signingRequestId: 'someone-elses')->getStatus());
		$this->assertSame([], $this->store->writes);

	}//end testLinkSigningAnswers()

	/**
	 * The parties of a readable contract, with the stored name when no contact answers.
	 *
	 * @return void
	 */
	public function testPartiesAnswers(): void {
		$uuid = $this->store->seed(['title' => 'A', 'status' => 'draft', 'parties' => [['role' => 'opdrachtgever', 'displayName' => 'Gemeente Demostad']]]);

		$data = $this->controller->parties(id: $uuid)->getData();

		$this->assertSame('Gemeente Demostad', $data[0]['displayName']);
		$this->assertFalse($data[0]['linked']);

	}//end testPartiesAnswers()
}//end class
