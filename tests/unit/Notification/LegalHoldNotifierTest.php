<?php

/**
 * The legal hold notifications, rendered.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Notification
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-2.3
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Notification;

use OCA\Filinq\Notification\LegalHoldNotifier;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use OCP\Notification\UnknownNotificationException;
use PHPUnit\Framework\TestCase;

/**
 * LegalHoldNotifier names the case and the freeze, with an absolute icon.
 */
class LegalHoldNotifierTest extends TestCase {

	/**
	 * The notifier.
	 *
	 * @return LegalHoldNotifier The notifier.
	 */
	private function notifier(): LegalHoldNotifier {
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(static fn (string $text, array $parameters = []): string => vsprintf($text, $parameters));
		$factory = $this->createMock(IFactory::class);
		$factory->method('get')->willReturn($l10n);
		$urls = $this->createMock(IURLGenerator::class);
		$urls->method('imagePath')->willReturn('/apps/filinq/img/app-dark.svg');
		$urls->method('linkToRoute')->willReturn('/index.php/apps/filinq/');
		$urls->method('getAbsoluteURL')->willReturnCallback(static fn (string $path): string => 'https://cloud.example' . $path);

		return new LegalHoldNotifier(l10nFactory: $factory, urls: $urls);

	}//end notifier()

	/**
	 * A notification.
	 *
	 * @param string $app     Its app.
	 * @param string $subject Its subject.
	 * @param array  $parsed  Collects what the notifier set.
	 *
	 * @return INotification The notification.
	 */
	private function notification(string $app, string $subject, \ArrayObject $parsed): INotification {
		$notification = $this->createMock(INotification::class);
		$notification->method('getApp')->willReturn($app);
		$notification->method('getSubject')->willReturn($subject);
		$notification->method('getSubjectParameters')->willReturn(['name' => 'Bezwaar 2026-004']);
		foreach (['setParsedSubject' => 'text', 'setIcon' => 'icon', 'setLink' => 'link'] as $method => $key) {
			$notification->method($method)->willReturnCallback(
				function (string $value) use ($parsed, $key, $notification): INotification {
					$parsed[$key] = $value;
					return $notification;
				}
			);
		}

		return $notification;

	}//end notification()

	/**
	 * Somebody else's notification is not ours to render.
	 *
	 * @return void
	 */
	public function testAForeignNotificationIsRefused(): void {
		$this->expectException(UnknownNotificationException::class);
		$this->notifier()->prepare($this->notification(app: 'spreed', subject: 'legal_hold_placed', parsed: new \ArrayObject()), 'nl');

	}//end testAForeignNotificationIsRefused()

	/**
	 * Placement and release name the case and what the freeze means, with absolute URLs.
	 *
	 * @return void
	 */
	public function testPlacedAndReleasedNameTheCase(): void {
		$placed = new \ArrayObject();
		$this->notifier()->prepare($this->notification(app: 'filinq', subject: 'legal_hold_placed', parsed: $placed), 'en');
		$released = new \ArrayObject();
		$this->notifier()->prepare($this->notification(app: 'filinq', subject: 'legal_hold_released', parsed: $released), 'en');

		$this->assertSame('Legal hold "Bezwaar 2026-004" freezes records of yours: they cannot be destroyed or deleted until the hold is released.', $placed['text']);
		$this->assertStringContainsString('"Bezwaar 2026-004" is released', $released['text']);
		$this->assertSame('https://cloud.example/apps/filinq/img/app-dark.svg', $placed['icon']);
		$this->assertSame('https://cloud.example/index.php/apps/filinq/legal-holds', $placed['link']);

	}//end testPlacedAndReleasedNameTheCase()
}//end class
