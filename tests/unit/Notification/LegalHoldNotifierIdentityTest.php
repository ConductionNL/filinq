<?php

/**
 * The legal hold notifier's identity, language and refusals.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Notification
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Notification;

use OCA\Filinq\Notification\LegalHoldNotifier;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use OCP\Notification\UnknownNotificationException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests for LegalHoldNotifier's identity and language handling.
 */
#[CoversClass(LegalHoldNotifier::class)]
class LegalHoldNotifierIdentityTest extends TestCase {

	/**
	 * The languages the factory was asked for, in order.
	 *
	 * @var array<int, array<int, string>>
	 */
	private array $languages = [];

	/**
	 * A notifier whose translator prefixes each text with the language asked for.
	 *
	 * @return LegalHoldNotifier
	 */
	private function notifier(): LegalHoldNotifier {
		$factory = $this->createMock(IFactory::class);
		$factory->method('get')->willReturnCallback(
			function (string $app, ?string $language = null): IL10N {
				$this->languages[] = [$app, (string) $language];
				$l10n = $this->createMock(IL10N::class);
				$l10n->method('t')->willReturnCallback(
					static fn (string $text, array $parameters = []): string => '[' . ($language ?? 'user') . '] ' . vsprintf($text, $parameters)
				);
				return $l10n;
			}
		);

		$urls = $this->createMock(IURLGenerator::class);
		$urls->method('imagePath')->willReturn('/img.svg');
		$urls->method('linkToRoute')->willReturn('/app/');
		$urls->method('getAbsoluteURL')->willReturnArgument(0);

		return new LegalHoldNotifier(l10nFactory: $factory, urls: $urls);
	}//end notifier()

	/**
	 * The notifier registers under the app's legal-hold id and a translated name.
	 *
	 * @return void
	 */
	public function testTheNotifierNamesItself(): void {
		$notifier = $this->notifier();

		$this->assertSame('filinq-legal-hold', $notifier->getID());
		$this->assertSame('[user] Legal holds', $notifier->getName());
		$this->assertSame([['filinq', '']], $this->languages);
	}//end testTheNotifierNamesItself()

	/**
	 * Another subject of the app itself is refused, untranslated.
	 *
	 * @return void
	 */
	public function testAnUnknownSubjectOfTheAppIsRefused(): void {
		$notification = $this->createMock(INotification::class);
		$notification->method('getApp')->willReturn('filinq');
		$notification->method('getSubject')->willReturn('legal_hold_extended');
		$notification->expects($this->never())->method('setParsedSubject');

		try {
			$this->notifier()->prepare($notification, 'nl');
			$this->fail('An unknown subject was prepared.');
		} catch (UnknownNotificationException) {
			$this->assertSame([], $this->languages);
		}
	}//end testAnUnknownSubjectOfTheAppIsRefused()

	/**
	 * The text is rendered in the recipient's language, and a hold without a
	 * name renders an empty name rather than failing.
	 *
	 * @return void
	 */
	public function testTheTextIsInTheRecipientsLanguage(): void {
		$parsed = null;
		$notification = $this->createMock(INotification::class);
		$notification->method('getApp')->willReturn('filinq');
		$notification->method('getSubject')->willReturn('legal_hold_released');
		$notification->method('getSubjectParameters')->willReturn([]);
		$notification->method('setParsedSubject')->willReturnCallback(
			function (string $text) use (&$parsed, $notification): INotification {
				$parsed = $text;
				return $notification;
			}
		);
		$notification->method('setIcon')->willReturnSelf();
		$notification->method('setLink')->willReturnSelf();

		$this->assertSame($notification, $this->notifier()->prepare($notification, 'de'));

		$this->assertSame([['filinq', 'de']], $this->languages);
		$this->assertSame(
			'[de] Legal hold "" is released: records in it may be destroyed or deleted again when their term allows.',
			$parsed
		);
	}//end testTheTextIsInTheRecipientsLanguage()
}//end class
