<?php

/**
 * Wire-contract tests for the four newly-exposed case-document endpoints
 *
 * Covers `caseDocuments#linkDomain` (POST api/case-documents/{uuid}/domains),
 * `caseDocuments#unlinkDomain` (DELETE api/case-documents/{uuid}/domains),
 * `caseDocuments#mine` (GET api/case-documents/mine) and
 * `caseDocuments#uploadPolicy` (GET api/case-documents/upload-policy).
 *
 * Each is asserted on what goes over the wire: the status code, the shape of
 * the body, the arguments the controller hands the service, and the refusal an
 * anonymous caller gets. All four carry `#[NoAdminRequired]`, so every one of
 * them is reachable by any signed-in user and the 401 is the only thing
 * between them and an anonymous request.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\CaseDocumentsController;
use OCA\Filinq\Service\DocumentDomainService;
use OCA\Filinq\Service\FlatFileListService;
use OCA\Filinq\Service\UploadPolicyService;
use OCP\AppFramework\Http;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Tests the wire contract of the four case-document endpoints.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class CaseDocumentsControllerContractTest extends TestCase {

	/**
	 * What the domain service was asked, in order.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $asked = [];

	/**
	 * A controller over doubles that record what they were asked.
	 *
	 * @param bool $signedIn Whether somebody is logged in.
	 * @param DocumentDomainService|null $domains The domain service to use, or null for a recording one.
	 * @param UploadPolicyService|null $policy The policy service to use, or null for one with no policy.
	 *
	 * @return CaseDocumentsController The controller.
	 */
	private function controller(
		bool $signedIn = true,
		?DocumentDomainService $domains = null,
		?UploadPolicyService $policy = null,
	): CaseDocumentsController {
		$session = $this->createMock(IUserSession::class);
		if ($signedIn === true) {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn('anna');
			$session->method('getUser')->willReturn($user);
		} else {
			$session->method('getUser')->willReturn(null);
		}

		return new CaseDocumentsController(
			'filinq',
			$this->createMock(IRequest::class),
			$this->createMock(FlatFileListService::class),
			($domains ?? $this->recordingDomains()),
			($policy ?? $this->policyService(active: null)),
			$session
		);

	}//end controller()

	/**
	 * A domain service that records the arguments it was handed.
	 *
	 * @return DocumentDomainService The double.
	 */
	private function recordingDomains(): DocumentDomainService {
		$domains = $this->createMock(DocumentDomainService::class);
		$domains->method('link')->willReturnCallback(
			function (string $uuid, array $domain): array {
				$this->asked[] = ['call' => 'link', 'uuid' => $uuid, 'domain' => $domain];

				return ['uuid' => $uuid, 'domains' => [$domain]];
			}
		);
		$domains->method('unlink')->willReturnCallback(
			function (string $uuid, array $domain): array {
				$this->asked[] = ['call' => 'unlink', 'uuid' => $uuid, 'domain' => $domain];

				return ['uuid' => $uuid, 'domains' => []];
			}
		);
		$domains->method('listMine')->willReturn(
			[['uuid' => 'doc-1'], ['uuid' => 'doc-2']]
		);

		return $domains;

	}//end recordingDomains()

	/**
	 * An upload policy service answering with the given policy.
	 *
	 * @param array<string, mixed>|null $active The policy in force, or null when none is declared.
	 *
	 * @return UploadPolicyService The double.
	 */
	private function policyService(?array $active): UploadPolicyService {
		$policy = $this->createMock(UploadPolicyService::class);
		$policy->method('activePolicy')->willReturn($active);

		return $policy;

	}//end policyService()

	/**
	 * Linking a domain answers 200 with the record, and hands the service the three fields.
	 *
	 * @return void
	 */
	public function testLinkDomainPassesTheWholeDomainThrough(): void {
		$response = $this->controller()->linkDomain(
			uuid: 'doc-1',
			register: 'zaken',
			schema: 'zaak',
			id: 'zaak-9'
		);

		$this->assertSame(Http::STATUS_OK, $response->getStatus(), 'a linked domain answers 200');
		$this->assertSame(
			[['call' => 'link', 'uuid' => 'doc-1', 'domain' => ['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-9']]],
			$this->asked,
			'all three domain fields reach the service, under their own names'
		);

	}//end testLinkDomainPassesTheWholeDomainThrough()

	/**
	 * A refused link is a 400 carrying the reason, not a 500.
	 *
	 * @return void
	 */
	public function testARefusedLinkIsABadRequestWithTheReason(): void {
		$domains = $this->createMock(DocumentDomainService::class);
		$domains->method('link')->willThrowException(new RuntimeException('That domain does not exist.'));

		$response = $this->controller(domains: $domains)->linkDomain(uuid: 'doc-1', register: 'zaken');

		$this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus(), 'a refusal is a 400');
		$this->assertSame(
			'That domain does not exist.',
			($response->getData()['error'] ?? ''),
			'and the body says why, so the surface can show it'
		);

	}//end testARefusedLinkIsABadRequestWithTheReason()

	/**
	 * Unlinking answers 200 and reaches the service as an unlink, not a link.
	 *
	 * @return void
	 */
	public function testUnlinkDomainReachesTheServiceAsAnUnlink(): void {
		$response = $this->controller()->unlinkDomain(
			uuid: 'doc-1',
			register: 'zaken',
			schema: 'zaak',
			id: 'zaak-9'
		);

		$this->assertSame(Http::STATUS_OK, $response->getStatus(), 'an unlink answers 200');
		$this->assertSame('unlink', ($this->asked[0]['call'] ?? ''), 'the unlink endpoint unlinks');
		$this->assertSame(
			['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-9'],
			($this->asked[0]['domain'] ?? []),
			'and names the same domain it was given'
		);

	}//end testUnlinkDomainReachesTheServiceAsAnUnlink()

	/**
	 * The mine endpoint answers results and a total that agrees with them.
	 *
	 * @return void
	 */
	public function testMineAnswersResultsAndAMatchingTotal(): void {
		$data = $this->controller()->mine()->getData();

		$this->assertSame(['doc-1', 'doc-2'], array_column($data['results'], 'uuid'), 'the records come back');
		$this->assertSame(
			count($data['results']),
			$data['total'],
			'the total counts the rows in the same answer, so a paging client cannot be lied to'
		);

	}//end testMineAnswersResultsAndAMatchingTotal()

	/**
	 * The upload policy endpoint answers the policy under its own key.
	 *
	 * @return void
	 */
	public function testUploadPolicyAnswersThePolicyInForce(): void {
		$declared = ['name' => 'Standaard', 'allowedExtensions' => ['pdf'], 'maxSizeBytes' => 1024];

		$response = $this->controller(policy: $this->policyService(active: $declared))->uploadPolicy();

		$this->assertSame(Http::STATUS_OK, $response->getStatus(), 'the policy answers 200');
		$this->assertSame($declared, $response->getData()['policy'], 'and is carried verbatim under `policy`');

	}//end testUploadPolicyAnswersThePolicyInForce()

	/**
	 * No declared policy is an answer, not an error.
	 *
	 * @return void
	 */
	public function testNoDeclaredPolicyIsStillATwoHundred(): void {
		$response = $this->controller()->uploadPolicy();

		$this->assertSame(
			Http::STATUS_OK,
			$response->getStatus(),
			'an instance that declared no policy answers 200, so the upload surface still loads'
		);
		$this->assertNull($response->getData()['policy'], 'and says there is none rather than inventing one');

	}//end testNoDeclaredPolicyIsStillATwoHundred()

	/**
	 * Every one of the four refuses an anonymous caller with 401.
	 *
	 * All four are `#[NoAdminRequired]`, which means Nextcloud lets any signed-in
	 * user through and checks nothing else. The 401 below is the whole of the
	 * access control on an anonymous request, so it is asserted per endpoint
	 * rather than once for the controller.
	 *
	 * @return void
	 */
	public function testAllFourRefuseAnAnonymousCaller(): void {
		$controller = $this->controller(signedIn: false);

		$responses = [
			'linkDomain' => $controller->linkDomain(uuid: 'doc-1'),
			'unlinkDomain' => $controller->unlinkDomain(uuid: 'doc-1'),
			'mine' => $controller->mine(),
			'uploadPolicy' => $controller->uploadPolicy(),
		];

		foreach ($responses as $endpoint => $response) {
			$this->assertSame(
				Http::STATUS_UNAUTHORIZED,
				$response->getStatus(),
				$endpoint . ' refuses an anonymous caller'
			);
		}

		$this->assertSame([], $this->asked, 'and nothing reached the domain service on the way');

	}//end testAllFourRefuseAnAnonymousCaller()
}//end class
