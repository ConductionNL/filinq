<?php

/**
 * Unit tests for DossierCheckedOnListener's failure handling.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\EventListener;

use OCA\Filinq\EventListener\DossierCheckedOnListener;
use OCA\Filinq\Kernel\Contracts\LegalBasesSummaryServiceInterface;
use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Event\ObjectUpdatedEvent;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * A failure while reading the updated dossier is logged, never thrown into the write.
 *
 * @covers \OCA\Filinq\EventListener\DossierCheckedOnListener
 */
class DossierCheckedOnListenerFailureTest extends TestCase {

	/**
	 * An unreadable dossier object is logged with the failure's origin and nothing is regenerated.
	 *
	 * @return void
	 */
	public function testAnUnreadableDossierIsLoggedNotThrown(): void {
		$failure = new RuntimeException('object column corrupt');
		$dossier = $this->createMock(ObjectEntity::class);
		$dossier->method('getRegister')->willReturn('filinq');
		$dossier->method('getSchema')->willReturn('dossier');
		$dossier->method('getObject')->willThrowException($failure);

		$summary = $this->createMock(LegalBasesSummaryServiceInterface::class);
		$summary->expects(self::never())->method('renderDossierSummary');
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects(self::once())->method('error')->with(
			'Filinq: DossierCheckedOnListener::handle failed: object column corrupt',
			['exception' => 'object column corrupt', 'file' => $failure->getFile(), 'line' => $failure->getLine()]
		);

		(new DossierCheckedOnListener($logger, $summary))->handle(new ObjectUpdatedEvent($dossier, null));
	}//end testAnUnreadableDossierIsLoggedNotThrown()

	/**
	 * An object of another schema is never read.
	 *
	 * @return void
	 */
	public function testAnotherSchemaIsNotRead(): void {
		$other = $this->createMock(ObjectEntity::class);
		$other->method('getRegister')->willReturn('filinq');
		$other->method('getSchema')->willReturn('document');
		$other->expects(self::never())->method('getObject');
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects(self::never())->method('error');

		(new DossierCheckedOnListener($logger, $this->createMock(LegalBasesSummaryServiceInterface::class)))
			->handle(new ObjectUpdatedEvent($other, null));
	}//end testAnotherSchemaIsNotRead()
}//end class
