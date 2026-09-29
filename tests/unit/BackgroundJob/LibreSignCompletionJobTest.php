<?php

/**
 * Unit tests for LibreSignCompletionJob
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\BackgroundJob
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/libresign-signing-provider/tasks.md#task-3.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\BackgroundJob;

use OCA\Filinq\BackgroundJob\LibreSignCompletionJob;
use OCA\Filinq\Service\Signing\LibreSignCompletion;
use OCP\AppFramework\Utility\ITimeFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ReflectionMethod;

/**
 * The job reads the open LibreSign requests back, and is registered.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\BackgroundJob
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class LibreSignCompletionJobTest extends TestCase {

	/**
	 * The job runs the sync.
	 *
	 * @return void
	 */
	public function testTheJobReadsTheRequestsBack(): void {
		$completion = $this->createMock(LibreSignCompletion::class);
		$completion->expects($this->once())->method('syncAll')->willReturn(2);

		$job = new LibreSignCompletionJob($this->createMock(ITimeFactory::class), $completion, new NullLogger());
		(new ReflectionMethod($job, 'run'))->invoke($job, null);

	}//end testTheJobReadsTheRequestsBack()

	/**
	 * Nextcloud runs it: it is in info.xml.
	 *
	 * @return void
	 */
	public function testTheJobIsRegisteredInInfoXml(): void {
		$info = (string)file_get_contents(__DIR__ . '/../../../appinfo/info.xml');

		$this->assertStringContainsString('<job>OCA\Filinq\BackgroundJob\LibreSignCompletionJob</job>', $info);

	}//end testTheJobIsRegisteredInInfoXml()
}//end class
