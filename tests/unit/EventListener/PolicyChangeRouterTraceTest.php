<?php

/**
 * Characterization tests for PolicyChangeRouter::route()
 *
 * Pins which retroactive policy step each payload shape and change kind
 * reaches, the logging and the swallowed failure.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/metadata-enrichment/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\EventListener;

use Exception;
use OCA\Filinq\EventListener\ObjectChange;
use OCA\Filinq\EventListener\ObjectEventServices;
use OCA\Filinq\EventListener\PolicyChangeRouter;
use OCA\Filinq\Kernel\Contracts\MetadataServiceInterface;
use OCA\Filinq\Kernel\Contracts\PolicyRetroactiveServiceInterface;
use OCA\Filinq\Kernel\Contracts\SettingsServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Characterization of the policy change router.
 *
 * @covers \OCA\Filinq\EventListener\PolicyChangeRouter
 * @uses   \OCA\Filinq\EventListener\ObjectChange
 * @uses   \OCA\Filinq\EventListener\ObjectEventServices
 */
class PolicyChangeRouterTraceTest extends TestCase {

	private const PROHIBITION = ['matchRules' => [['field' => 'x']], 'reason' => 'privacy'];

	private PolicyRetroactiveServiceInterface&MockObject $retroactive;

	private LoggerInterface&MockObject $logger;

	/**
	 * Fresh doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->retroactive = $this->createMock(PolicyRetroactiveServiceInterface::class);
		$this->logger = $this->createMock(LoggerInterface::class);

	}//end setUp()

	/**
	 * A changed prohibition is applied with its payload, and resolved records are logged.
	 *
	 * @return void
	 */
	public function testProhibitionMutationIsAppliedAndLogged(): void {
		$this->retroactive->expects($this->once())->method('applyProhibitionMutation')
			->with(self::PROHIBITION)->willReturn(3);
		$this->retroactive->expects($this->never())->method('applyRuleRemoval');
		$this->retroactive->expects($this->never())->method('applyStandingConsentMutation');
		$this->logger->expects($this->once())->method('info')->with(
			'Filinq: prohibition mutation force-resolved in-flight records',
			['resolved' => 3, 'reason' => 'updated']
		);

		$this->route(data: self::PROHIBITION, reason: 'updated');

	}//end testProhibitionMutationIsAppliedAndLogged()

	/**
	 * Nothing resolved means nothing logged.
	 *
	 * @return void
	 */
	public function testProhibitionResolvingNothingLogsNothing(): void {
		$this->retroactive->expects($this->once())->method('applyProhibitionMutation')->willReturn(0);
		$this->logger->expects($this->never())->method('info');

		$this->route(data: ['matchRules' => ['r'], 'legalAuthority' => 'Woo'], reason: 'created');

	}//end testProhibitionResolvingNothingLogsNothing()

	/**
	 * A deleted prohibition removes the rule instead.
	 *
	 * @return void
	 */
	public function testDeletedProhibitionRemovesTheRule(): void {
		$this->retroactive->expects($this->once())->method('applyRuleRemoval');
		$this->retroactive->expects($this->never())->method('applyProhibitionMutation');

		$this->route(data: self::PROHIBITION, reason: 'deleted');

	}//end testDeletedProhibitionRemovesTheRule()

	/**
	 * A standing consent (scope=entity) is applied; deleted removes the rule.
	 *
	 * @return void
	 */
	public function testStandingConsentMutationAndRemoval(): void {
		$this->retroactive->expects($this->once())->method('applyStandingConsentMutation');
		$this->retroactive->expects($this->never())->method('applyRuleRemoval');
		$this->route(data: ['consentStatus' => 'granted', 'scope' => 'entity'], reason: 'updated');

		$this->retroactive = $this->createMock(PolicyRetroactiveServiceInterface::class);
		$this->retroactive->expects($this->once())->method('applyRuleRemoval');
		$this->retroactive->expects($this->never())->method('applyStandingConsentMutation');
		$this->route(data: ['consentStatus' => 'granted', 'scope' => 'entity'], reason: 'deleted');

	}//end testStandingConsentMutationAndRemoval()

	/**
	 * Document consents, consents without scope, and non-policy payloads reach no step.
	 *
	 * @return void
	 */
	public function testOtherShapesReachNoStep(): void {
		$this->retroactive->expects($this->never())->method('applyRuleRemoval');
		$this->retroactive->expects($this->never())->method('applyStandingConsentMutation');
		$this->retroactive->expects($this->never())->method('applyProhibitionMutation');
		$this->logger->expects($this->never())->method('warning');

		$payloads = [
			['consentStatus' => 'granted', 'scope' => 'document'],
			['consentStatus' => 'granted'],
			['consentStatus' => 'granted', 'scope' => 'case'],
			['consentStatus' => 'granted', 'matchRules' => ['r'], 'reason' => 'x'],
			['matchRules' => ['r']],
			['matchRules' => [], 'reason' => 'x'],
			['matchRules' => ['r'], 'reason' => ''],
			['title' => 'plain document'],
		];
		foreach ($payloads as $payload) {
			$this->route(data: $payload, reason: 'deleted');
		}

	}//end testOtherShapesReachNoStep()

	/**
	 * A failing step is logged as a warning and not rethrown.
	 *
	 * @return void
	 */
	public function testFailureIsLoggedNotThrown(): void {
		$this->retroactive->method('applyProhibitionMutation')->willThrowException(new Exception('db down'));
		$this->logger->expects($this->once())->method('warning')->with(
			'Filinq: retroactive policy dispatch failed',
			['error' => 'db down', 'reason' => 'updated']
		);

		$this->route(data: self::PROHIBITION, reason: 'updated');

	}//end testFailureIsLoggedNotThrown()

	/**
	 * A failing standing-consent step is logged too.
	 *
	 * @return void
	 */
	public function testStandingConsentFailureIsLogged(): void {
		$this->retroactive->method('applyRuleRemoval')->willThrowException(new Exception('gone'));
		$this->logger->expects($this->once())->method('warning')->with(
			'Filinq: retroactive policy dispatch failed',
			['error' => 'gone', 'reason' => 'deleted']
		);

		$this->route(data: ['consentStatus' => 'x', 'scope' => 'entity'], reason: 'deleted');

	}//end testStandingConsentFailureIsLogged()

	/**
	 * Route one change.
	 *
	 * @param array<string, mixed> $data The payload.
	 * @param string $reason The kind of change.
	 *
	 * @return void
	 */
	private function route(array $data, string $reason): void {
		$services = new ObjectEventServices(
			metadataService: $this->createMock(MetadataServiceInterface::class),
			settingsService: $this->createMock(SettingsServiceInterface::class),
			logger: $this->logger,
			retroactive: $this->retroactive,
		);

		(new PolicyChangeRouter())->route(change: new ObjectChange(objectData: $data, reason: $reason), services: $services);
	}//end route()
}//end class
