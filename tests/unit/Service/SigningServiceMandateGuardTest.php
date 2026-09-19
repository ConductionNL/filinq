<?php

/**
 * Unit tests for the mandate guard on the direct signing path
 *
 * The folder leaves out what a signer has no mandate for; this is the other
 * half of REQ-SFC-04, where the same person tries the API directly.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\SettingsService;
use OCA\Filinq\Service\SignedArtifactProducer;
use OCA\Filinq\Service\SigningActorResolver;
use OCA\Filinq\Service\SigningAuditService;
use OCA\Filinq\Service\SigningConclusionEmitter;
use OCA\Filinq\Service\SigningMandateService;
use OCA\Filinq\Service\SigningRequestValidator;
use OCA\Filinq\Service\SigningService;
use OCA\OpenRegister\Service\ObjectService;
use OCP\IAppConfig;
use OCP\IGroupManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * The direct signing attempt is refused outside the declared mandate.
 *
 * Both tests drive the real SigningService against a real
 * SigningMandateService, and stop at loadAuthorisedSigner(): a sentinel
 * thrown there says the guard let the caller through, and its absence says
 * the guard refused before anything was written.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class SigningServiceMandateGuardTest extends TestCase {

	/**
	 * The sentinel thrown where the signer record would be loaded.
	 *
	 * @var string
	 */
	private const REACHED_THE_SIGNER_PATH = 'REACHED THE SIGNER PATH';

	/**
	 * @var SigningActorResolver|MockObject
	 */
	private SigningActorResolver|MockObject $actorResolver;

	/**
	 * @var SigningMandateService
	 */
	private SigningMandateService $mandateService;

	/**
	 * @var IGroupManager|MockObject
	 */
	private IGroupManager|MockObject $groupManager;

	/**
	 * @var SigningService
	 */
	private SigningService $service;

	/**
	 * Build a signing service whose signer path is a sentinel.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$request = [
			'id' => 'req-001',
			'status' => 'PENDING',
			'signatureLevel' => 'AdES',
			'provider' => 'native',
			'initiatorUserId' => 'griffier',
			'signerIds' => ['signer-001'],
			'sourceApp' => 'dossiq',
			'subjectSchema' => 'besluit',
		];

		$objectService = $this->getMockBuilder(ObjectService::class)
			->disableOriginalConstructor()
			->disableOriginalClone()
			->disableArgumentCloning()
			->disallowMockingUnknownTypes()
			->onlyMethods(['find', 'saveObject'])
			->getMock();
		$objectService->method('find')->willReturn($request);

		$settingsService = $this->createMock(SettingsService::class);
		$settingsService->method('getObjectService')->willReturn($objectService);
		$settingsService->method('resolveSigningRequestBinding')
			->willReturn(['register' => 'filinq', 'schema' => 'signingRequest']);
		$settingsService->method('resolveSignerRecordBinding')
			->willReturn(['register' => 'filinq', 'schema' => 'signerRecord']);

		$this->actorResolver = $this->getMockBuilder(SigningActorResolver::class)
			->disableOriginalConstructor()
			->onlyMethods(['resolveActingIdentity', 'loadAuthorisedSigner', 'getClientIp', 'actorAuditMetadata'])
			->getMock();
		$this->actorResolver->method('resolveActingIdentity')
			->willReturn(['beleidsmedewerker', 'Bea Beleidsmedewerker']);
		$this->actorResolver->method('getClientIp')->willReturn('192.0.2.10');
		$this->actorResolver->method('loadAuthorisedSigner')
			->willThrowException(new RuntimeException(self::REACHED_THE_SIGNER_PATH));

		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturn(
			json_encode(
				[
					'dossiq/besluit' => [
						'groups' => ['portefeuillehouders'],
						'rule' => 'Only the portefeuillehouder signs a besluit',
					],
				]
			)
		);

		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->mandateService = new SigningMandateService(
			config: $config,
			groupManager: $this->groupManager
		);

		$this->service = new SigningService(
			settingsService: $settingsService,
			auditService: $this->createMock(SigningAuditService::class),
			artifactProducer: $this->createMock(SignedArtifactProducer::class),
			validator: $this->createMock(SigningRequestValidator::class),
			actorResolver: $this->actorResolver,
			emitter: $this->createMock(SigningConclusionEmitter::class),
			mandateService: $this->mandateService
		);

	}//end setUp()

	/**
	 * The direct attempt outside the mandate is refused, and the rule is named.
	 *
	 * @return void
	 */
	public function testTheDirectAttemptOutsideTheMandateIsRefusedNamingTheRule(): void {
		$this->groupManager->method('isInGroup')->willReturn(false);
		$this->actorResolver->expects($this->never())->method('loadAuthorisedSigner');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Only the portefeuillehouder signs a besluit');

		$this->service->sign(requestId: 'req-001', signerId: 'signer-001');

	}//end testTheDirectAttemptOutsideTheMandateIsRefusedNamingTheRule()

	/**
	 * The person who holds the mandate is let through to sign as before.
	 *
	 * Without this control, a guard that refused everybody would pass the
	 * test above and nobody would be able to sign anything.
	 *
	 * @return void
	 */
	public function testTheHolderOfTheMandateIsLetThrough(): void {
		$this->groupManager->method('isInGroup')->willReturn(true);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage(self::REACHED_THE_SIGNER_PATH);

		$this->service->sign(requestId: 'req-001', signerId: 'signer-001');

	}//end testTheHolderOfTheMandateIsLetThrough()
}//end class
