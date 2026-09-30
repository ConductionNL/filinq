<?php

/**
 * Unit tests for ContractSigningLink
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Contract
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/contract-lifecycle-management/tasks.md#3-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Contract;

require_once __DIR__ . '/ContractSchemaValidation.php';
require_once __DIR__ . '/InMemoryContracts.php';

use InvalidArgumentException;
use OCA\Filinq\Service\Contract\ContractNotFoundException;
use OCA\Filinq\Service\Contract\ContractSigningLink;
use OCA\Filinq\Service\SigningService;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * A contract records the signing request sent for it, and the signed
 * document once that request completed. The request is read through the
 * signing service as the caller, so its own access rule applies.
 */
class ContractSigningLinkTest extends TestCase {
	use ContractSchemaValidation;

	/**
	 * The store.
	 *
	 * @var InMemoryContracts
	 */
	private InMemoryContracts $store;

	/**
	 * Signing requests the caller may read, by id.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private array $requests = [];

	/**
	 * The link under test.
	 *
	 * @var ContractSigningLink
	 */
	private ContractSigningLink $link;

	/**
	 * Who asked for each request, in order.
	 *
	 * @var array<int, string>
	 */
	private array $callers = [];

	/**
	 * Set up.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->store = new InMemoryContracts();
		$signing = $this->createMock(SigningService::class);
		$signing->method('getRequest')->willReturnCallback(
			function (string $requestId, string $callerUserId=''): ?array {
				$this->callers[] = $callerUserId;
				if ($requestId === 'missing') {
					throw new RuntimeException('Signing request not found');
				}

				return $this->requests[$requestId] ?? null;
			}
		);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);
		$this->link = new ContractSigningLink(contracts: $this->store, signing: $signing, userSession: $session);

	}//end setUp()

	/**
	 * An active contract with the fields a save must carry forward.
	 *
	 * @return string The uuid.
	 */
	private function contract(): string {
		return $this->store->seed(
			[
				'title' => 'Raamovereenkomst groenonderhoud',
				'contractType' => 'inkoop',
				'endDate' => '2028-12-31',
				'value' => 240000,
				'currency' => 'EUR',
				'status' => 'active',
				'documents' => ['412'],
			]
		);

	}//end contract()

	/**
	 * Sending a document for signature records the request on the contract; nothing is signed yet.
	 *
	 * @return void
	 */
	public function testTheSentRequestIsRecorded(): void {
		$uuid = $this->contract();
		$this->requests['req-1'] = ['id' => 'req-1', 'status' => 'DRAFT', 'documentName' => 'Contract.pdf', 'documentFileId' => '412'];

		$result = $this->link->link(uuid: $uuid, signingRequestId: 'req-1');

		$this->assertSame('req-1', $result['contract']['signingRequestRef']);
		$this->assertArrayNotHasKey('signedDocumentRef', $result['contract']);
		$this->assertSame('DRAFT', $result['signingRequest']['status']);
		$this->assertSame(240000, $this->store->rows[$uuid]['value'], 'a field the link does not touch survives');
		$this->assertSame(['alice'], $this->callers, 'the request is read as the caller');
		$this->assertValidContract(end($this->store->writes));

	}//end testTheSentRequestIsRecorded()

	/**
	 * A completed request links the signed document back to the contract.
	 *
	 * @return void
	 */
	public function testACompletedRequestLinksTheSignedDocument(): void {
		$uuid = $this->contract();
		$this->requests['req-2'] = ['id' => 'req-2', 'status' => 'COMPLETED', 'documentFileId' => '412', 'signedDocumentRef' => 'file:913'];

		$result = $this->link->link(uuid: $uuid, signingRequestId: 'req-2');

		$this->assertSame('file:913', $result['contract']['signedDocumentRef']);
		$this->assertSame('file:913', $this->store->rows[$uuid]['signedDocumentRef']);
		$this->assertTrue($result['signingRequest']['signed']);
		$this->assertValidContract(end($this->store->writes));

	}//end testACompletedRequestLinksTheSignedDocument()

	/**
	 * A provider that signs in place leaves no separate reference: the signed document is the sent one.
	 *
	 * @return void
	 */
	public function testACompletedRequestWithoutAReferencePointsAtTheSentFile(): void {
		$uuid = $this->contract();
		$this->requests['req-3'] = ['id' => 'req-3', 'status' => 'COMPLETED', 'documentFileId' => 412];

		$result = $this->link->link(uuid: $uuid, signingRequestId: 'req-3');

		$this->assertSame('412', $result['contract']['signedDocumentRef']);

	}//end testACompletedRequestWithoutAReferencePointsAtTheSentFile()

	/**
	 * Reading the link again changes nothing and writes nothing.
	 *
	 * @return void
	 */
	public function testAnUnchangedLinkIsNotWrittenAgain(): void {
		$uuid = $this->contract();
		$this->requests['req-2'] = ['id' => 'req-2', 'status' => 'COMPLETED', 'signedDocumentRef' => 'file:913'];
		$this->link->link(uuid: $uuid, signingRequestId: 'req-2');
		$writes = count($this->store->writes);

		$this->link->link(uuid: $uuid, signingRequestId: 'req-2');

		$this->assertCount($writes, $this->store->writes);

	}//end testAnUnchangedLinkIsNotWrittenAgain()

	/**
	 * A request the caller may not read, or that does not exist, is 404 and nothing is written.
	 *
	 * @return void
	 */
	public function testARequestTheCallerCannotReadIsRefused(): void {
		$uuid = $this->contract();

		foreach (['not-theirs', 'missing'] as $requestId) {
			try {
				$this->link->link(uuid: $uuid, signingRequestId: $requestId);
				$this->fail('linked a request the caller cannot read: ' . $requestId);
			} catch (InvalidArgumentException $e) {
				$this->assertSame(404, $e->getCode());
			}
		}

		$this->assertSame([], $this->store->writes);

	}//end testARequestTheCallerCannotReadIsRefused()

	/**
	 * No request id is 400; an unknown contract is not found.
	 *
	 * @return void
	 */
	public function testInputIsChecked(): void {
		$uuid = $this->contract();
		try {
			$this->link->link(uuid: $uuid, signingRequestId: ' ');
			$this->fail('linked an empty request id');
		} catch (InvalidArgumentException $e) {
			$this->assertSame(400, $e->getCode());
		}

		$this->expectException(ContractNotFoundException::class);
		$this->link->link(uuid: 'nope', signingRequestId: 'req-1');

	}//end testInputIsChecked()
}//end class
