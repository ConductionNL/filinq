<?php

/**
 * The rule that tells the registrars a document could not be read.
 *
 * 🔴 A SCANNER THAT JAMS AT 02:00 FAILS EVERY PAGE IT FEEDS. Four hundred
 * notifications is the same silence with noise in front of it, and the
 * registrar turns the channel off. So the rule coalesces and digests.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Settings
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/intake-failure-reaches-someone/specs/filinq-notifications/spec.md
 */

namespace OCA\Filinq\Tests\Unit\Settings;

use PHPUnit\Framework\TestCase;

/**
 * Asserts the declared notification rule on intakeDocument.
 */
class IntakeFailureNotificationTest extends TestCase {

	/**
	 * The declared rule.
	 *
	 * @return array<string, mixed> The rule.
	 */
	private function rule(): array {
		$path = dirname(__DIR__, 3).'/lib/Settings/filinq_register.json';
		$decoded = json_decode((string)file_get_contents($path), true);
		$this->assertIsArray($decoded);

		$notifications = $decoded['components']['schemas']['intakeDocument']['x-openregister-notifications'];
		$this->assertArrayHasKey('readingFailed', $notifications);

		return $notifications['readingFailed'];
	}//end rule()

	/**
	 * The rule fires on the failed reading state and nothing else.
	 *
	 * @return void
	 */
	public function testTheRuleFiresOnAFailedReading(): void {
		$rule = $this->rule();

		$this->assertTrue($rule['enabled']);
		$this->assertSame('failed', $rule['trigger']['filter']['readingState']);
	}//end testTheRuleFiresOnAFailedReading()

	/**
	 * 🔴 A STORM IS ONE MESSAGE, NOT HUNDREDS. Both halves are declared: a
	 * coalescing window for the burst, and a daily digest for the rest.
	 *
	 * @return void
	 */
	public function testAStormProducesOneMessageRatherThanHundreds(): void {
		$rule = $this->rule();

		$this->assertArrayHasKey('coalesce', $rule);
		$this->assertGreaterThan(0, $rule['coalesce']['windowSeconds']);
		$this->assertSame('daily', $rule['digest']['schedule']);
		$this->assertSame('08:30', $rule['digest']['at']);
	}//end testAStormProducesOneMessageRatherThanHundreds()

	/**
	 * The digest names a timezone, so 08:30 is 08:30 where the registrars are
	 * rather than wherever the server thinks it is.
	 *
	 * @return void
	 */
	public function testTheDigestNamesATimezone(): void {
		$this->assertSame('Europe/Amsterdam', $this->rule()['digest']['timezone']);
	}//end testTheDigestNamesATimezone()

	/**
	 * 🔴 THE RECIPIENT NAMES A GROUP RATHER THAN AN EMPTY LIST. openregister
	 * now refuses a recipient that can never resolve at declaration time, so an
	 * empty list here would fail the import.
	 *
	 * @return void
	 */
	public function testTheRecipientNamesAGroupAndNotAnEmptyList(): void {
		$recipients = $this->rule()['recipients'];

		$this->assertNotSame([], $recipients);
		$this->assertSame('groups', $recipients[0]['kind']);
		$this->assertNotSame([], $recipients[0]['groups']);
		$this->assertContains('docudesk-woo-officers', $recipients[0]['groups']);
	}//end testTheRecipientNamesAGroupAndNotAnEmptyList()

	/**
	 * The message says the documents are still in the inbox and assignable by
	 * hand, because the text is what failed, not the document, and a registrar
	 * meeting this at 08:30 needs to know they can still work.
	 *
	 * @return void
	 */
	public function testTheMessageSaysTheDocumentsAreStillWorkable(): void {
		$message = $this->rule()['message'];

		$this->assertStringContainsString('postbus', $message['nl']);
		$this->assertStringContainsString('still in the inbox', $message['en']);
		$this->assertStringContainsString('by hand', $message['en']);
	}//end testTheMessageSaysTheDocumentsAreStillWorkable()

	/**
	 * Both languages are present, since the registrars read Dutch.
	 *
	 * @return void
	 */
	public function testTheSubjectAndMessageAreInBothLanguages(): void {
		$rule = $this->rule();

		foreach (['subject', 'message'] as $field) {
			foreach (['nl', 'en'] as $locale) {
				$this->assertNotSame('', trim((string)$rule[$field][$locale]), $field.'.'.$locale);
			}
		}
	}//end testTheSubjectAndMessageAreInBothLanguages()

	/**
	 * The reading state the rule filters on exists as a property, or the filter
	 * matches nothing for ever.
	 *
	 * @return void
	 */
	public function testTheStateTheRuleFiltersOnIsADeclaredProperty(): void {
		$path = dirname(__DIR__, 3).'/lib/Settings/filinq_register.json';
		$properties = json_decode((string)file_get_contents($path), true)
			['components']['schemas']['intakeDocument']['properties'];

		$this->assertArrayHasKey('readingState', $properties);
		$this->assertContains('failed', $properties['readingState']['enum']);
		$this->assertArrayHasKey('readingError', $properties);
	}//end testTheStateTheRuleFiltersOnIsADeclaredProperty()

	/**
	 * The descriptor version is bumped, or the rule never reaches an install.
	 *
	 * @return void
	 */
	public function testTheDescriptorVersionIsBumped(): void {
		$path = dirname(__DIR__, 3).'/lib/Settings/filinq_register.json';
		$version = (string)json_decode((string)file_get_contents($path), true)['info']['version'];

		$this->assertTrue(version_compare($version, '8.13.0', '>'));
	}//end testTheDescriptorVersionIsBumped()
}//end class
