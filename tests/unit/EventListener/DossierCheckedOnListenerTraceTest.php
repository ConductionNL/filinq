<?php

/**
 * Characterization tests for DossierCheckedOnListener's update path
 *
 * Pins when a dossier update regenerates the summary, which dossier it
 * renders, and how a failure is logged.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/anonymisation-grondslagen-summary-rendering/tasks.md#task-7
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\EventListener;

use OCA\Filinq\EventListener\DossierCheckedOnListener;
use OCA\Filinq\Kernel\Contracts\LegalBasesSummaryServiceInterface;
use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Event\ObjectUpdatedEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Characterization of the checkedOn auto-regeneration.
 *
 * @covers \OCA\Filinq\EventListener\DossierCheckedOnListener
 */
class DossierCheckedOnListenerTraceTest extends TestCase {

	private LegalBasesSummaryServiceInterface&MockObject $summary;

	private LoggerInterface&MockObject $logger;

	/**
	 * Fresh doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->summary = $this->createMock(LegalBasesSummaryServiceInterface::class);
		$this->logger = $this->createMock(LoggerInterface::class);

	}//end setUp()

	/**
	 * A changed checkedOn on a dossier renders that dossier and logs it.
	 *
	 * @return void
	 */
	public function testChangedCheckedOnRegenerates(): void {
		$this->summary->expects($this->once())->method('renderDossierSummary')->with('42');
		$this->logger->expects($this->once())->method('info')->with(
			'Filinq: Auto-regenerating dossier grondslagen summary on checkedOn update',
			['dossierId' => 42]
		);

		$this->dispatch(
			new: $this->entity(data: ['checkedOn' => '2026-11-01'], id: 42),
			old: $this->entity(data: ['checkedOn' => '2026-10-01'])
		);

	}//end testChangedCheckedOnRegenerates()

	/**
	 * Without an old object a first checkedOn regenerates.
	 *
	 * @return void
	 */
	public function testNoOldObjectRegenerates(): void {
		$this->summary->expects($this->once())->method('renderDossierSummary')->with('7');

		$this->dispatch(
			new: $this->entity(data: ['checkedOn' => 'x'], id: 7),
			old: null
		);

	}//end testNoOldObjectRegenerates()

	/**
	 * The cases that must not regenerate.
	 *
	 * @return void
	 */
	public function testCasesThatDoNotRegenerate(): void {
		$this->summary->expects($this->never())->method('renderDossierSummary');
		$this->logger->expects($this->never())->method('info');
		$this->logger->expects($this->never())->method('error');

		$old = $this->entity(data: ['checkedOn' => 'a']);
		$cases = [
			'other register' => [$this->entity(data: ['checkedOn' => 'b'], id: 1, register: 'other'), $old],
			'other schema' => [$this->entity(data: ['checkedOn' => 'b'], id: 1, schema: 'case'), $old],
			'unchanged' => [$this->entity(data: ['checkedOn' => 'a'], id: 1), $old],
			'cleared' => [$this->entity(data: [], id: 1), $old],
			'opted out' => [$this->entity(data: ['checkedOn' => 'b', 'configuration' => ['grondslagen' => ['autoRegenOnReview' => false]]], id: 1), $old],
			'opt-out not strictly true' => [$this->entity(data: ['checkedOn' => 'b', 'configuration' => ['grondslagen' => ['autoRegenOnReview' => 1]]], id: 1), $old],
			'no id' => [$this->entity(data: ['checkedOn' => 'b'], id: null), $old],
			'no new object' => [null, $old],
		];
		foreach ($cases as [$new, $oldObject]) {
			$this->dispatch(new: $new, old: $oldObject);
		}

	}//end testCasesThatDoNotRegenerate()

	/**
	 * Malformed configuration falls back to regenerating.
	 *
	 * @return void
	 */
	public function testMalformedConfigurationRegenerates(): void {
		$this->summary->expects($this->exactly(3))->method('renderDossierSummary');

		foreach ([['configuration' => 'x'], ['configuration' => ['grondslagen' => 'x']], ['configuration' => ['grondslagen' => ['autoRegenOnReview' => true]]]] as $extra) {
			$this->dispatch(new: $this->entity(data: ['checkedOn' => 'b'] + $extra, id: 3), old: $this->entity(data: []));
		}

	}//end testMalformedConfigurationRegenerates()

	/**
	 * A failing render is logged with the dossier id and not rethrown.
	 *
	 * @return void
	 */
	public function testRenderFailureIsLogged(): void {
		$this->summary->method('renderDossierSummary')->willThrowException(new RuntimeException('no template'));
		$this->logger->expects($this->once())->method('error')->with(
			'Filinq: LegalBasesSummaryService::renderDossierSummary (auto-regen) failed: no template',
			$this->callback(
				fn (array $ctx): bool => $ctx['dossierId'] === 9 && $ctx['exception'] === 'no template' && isset($ctx['file'], $ctx['line'])
			)
		);

		$this->dispatch(new: $this->entity(data: ['checkedOn' => 'b'], id: 9), old: null);

	}//end testRenderFailureIsLogged()

	/**
	 * Dispatch one update through the listener.
	 *
	 * @param ObjectEntity|null $new The new object.
	 * @param ObjectEntity|null $old The old object.
	 *
	 * @return void
	 */
	private function dispatch(?ObjectEntity $new, ?ObjectEntity $old): void {
		(new DossierCheckedOnListener(logger: $this->logger, summaryService: $this->summary))
			->handle(new ObjectUpdatedEvent($new, $old));
	}//end dispatch()

	/**
	 * An object entity double.
	 *
	 * @param array<string, mixed> $data The object data.
	 * @param int|null $id The id.
	 * @param string $register The register slug.
	 * @param string $schema The schema slug.
	 *
	 * @return ObjectEntity The double.
	 */
	private function entity(array $data, ?int $id = null, string $register = 'filinq', string $schema = 'dossier'): ObjectEntity {
		$entity = $this->createMock(ObjectEntity::class);
		$entity->method('getObject')->willReturn($data);
		$entity->method('getId')->willReturn($id);
		$entity->method('getRegister')->willReturn($register);
		$entity->method('getSchema')->willReturn($schema);

		return $entity;
	}//end entity()
}//end class
