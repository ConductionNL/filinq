<?php

/**
 * Unit tests for IntegrityScanNotifier.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Notification
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-11-20-whole-collection-integrity-scan/tasks.md#task-2-nightly-job-occ-command-and-notification
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Notification;

use OCA\Filinq\Notification\IntegrityScanNotifier;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use OCP\Notification\UnknownNotificationException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * The findings notification names the counts and links to the administration section.
 */
#[CoversClass(IntegrityScanNotifier::class)]
class IntegrityScanNotifierTest extends TestCase {

	/**
	 * What the notification was given.
	 *
	 * @var array<string, string>
	 */
	private array $parsed = [];

	/**
	 * The notifier over a pass-through translator.
	 *
	 * @return IntegrityScanNotifier
	 */
	private function notifier(): IntegrityScanNotifier {
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(static fn (string $text, array $parameters = []): string => vsprintf($text, $parameters));
		$factory = $this->createMock(IFactory::class);
		$factory->method('get')->willReturn($l10n);
		$urls = $this->createMock(IURLGenerator::class);
		$urls->method('imagePath')->willReturn('/img/app-dark.svg');
		$urls->method('linkToRoute')->willReturnCallback(
			static fn (string $route, array $parameters = []): string => '/settings/admin/' . ($route === 'settings.AdminSettings.index' ? $parameters['section'] : 'wrong')
		);
		$urls->method('getAbsoluteURL')->willReturnCallback(static fn (string $path): string => 'https://cloud.example' . $path);

		return new IntegrityScanNotifier($factory, $urls);
	}//end notifier()

	/**
	 * A notification double.
	 *
	 * @param string $app     The app.
	 * @param string $subject The subject.
	 *
	 * @return INotification
	 */
	private function notification(string $app, string $subject): INotification {
		$notification = $this->createMock(INotification::class);
		$notification->method('getApp')->willReturn($app);
		$notification->method('getSubject')->willReturn($subject);
		$notification->method('getSubjectParameters')->willReturn(['missing' => 2, 'changed' => 3, 'orphaned' => 0]);
		foreach (['setParsedSubject' => 'subject', 'setLink' => 'link', 'setIcon' => 'icon'] as $setter => $key) {
			$notification->method($setter)->willReturnCallback(
				function (string $value) use ($key, $notification): INotification {
					$this->parsed[$key] = $value;
					return $notification;
				}
			);
		}

		return $notification;
	}//end notification()

	/**
	 * The counts per kind, and a link to Filinq's administration section.
	 *
	 * @return void
	 */
	public function testTheFindingsAreCountedAndLinked(): void {
		$this->notifier()->prepare($this->notification(app: 'filinq', subject: IntegrityScanNotifier::SUBJECT), 'nl');

		$this->assertSame('The integrity check found 2 missing, 3 changed and 0 orphaned files.', $this->parsed['subject']);
		$this->assertSame('https://cloud.example/settings/admin/filinq', $this->parsed['link']);
	}//end testTheFindingsAreCountedAndLinked()

	/**
	 * Another subject or app is not this notifier's.
	 *
	 * @return void
	 */
	public function testAnotherNotificationIsNotOurs(): void {
		$this->expectException(UnknownNotificationException::class);

		$this->notifier()->prepare($this->notification(app: 'filinq', subject: 'legal_hold_placed'), 'nl');
	}//end testAnotherNotificationIsNotOurs()
}//end class
