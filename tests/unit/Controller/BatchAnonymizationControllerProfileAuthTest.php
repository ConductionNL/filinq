<?php

/**
 * Authorization-posture tests for BatchAnonymizationController's WOO profile endpoints.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-woo-entity-category-profiles
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Controller;

use OCA\DocuDesk\Controller\BatchAnonymizationController;
use OCA\DocuDesk\Settings\DocuDeskAdmin;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Pins the asymmetry between READING and WRITING the instance-wide WOO profile.
 *
 * `WooProfileService` persists the profile through `IAppConfig`, so it is a
 * SINGLE INSTANCE-WIDE VALUE, and `EntityConsolidationService` reads it to
 * decide which entity types get redacted for every user. A non-admin who could
 * write it could move `PERSON` / `BSN` / `EMAIL` out of the `anonymize` set and
 * leave other people's PII in place.
 *
 * These tests exist because the plausible-looking repair is the dangerous one:
 * `getProfiles()` carries `@NoAdminRequired`, and copying that onto
 * `updateProfiles()` to silence a "missing auth attribute" finding would hand
 * instance-wide PII policy to every authenticated user. The write posture is
 * therefore asserted explicitly rather than left to Nextcloud's default.
 */
class BatchAnonymizationControllerProfileAuthTest extends TestCase {

	/**
	 * The WRITE endpoint must declare admin-only authorization explicitly.
	 *
	 * @return void
	 */
	public function testUpdateProfilesIsAdminOnly(): void {
		$method = new ReflectionMethod(BatchAnonymizationController::class, 'updateProfiles');
		$attributes = $method->getAttributes(AuthorizedAdminSetting::class);

		$this->assertCount(
			1,
			$attributes,
			'updateProfiles() writes the instance-wide WOO profile via IAppConfig and MUST declare '
			. '#[AuthorizedAdminSetting]. Without it the endpoint is admin-only only by Nextcloud default, '
			. 'which is an accident rather than a decision.'
		);

		$this->assertSame(
			[DocuDeskAdmin::class],
			$attributes[0]->getArguments(),
			'The admin setting must be DocuDesk\'s own, matching SettingsController and AnonymiserWarningController.'
		);

	}//end testUpdateProfilesIsAdminOnly()

	/**
	 * The WRITE endpoint must NOT be reachable by any authenticated user.
	 *
	 * This is the regression this file exists for: `#[NoAdminRequired]` (or the
	 * `@NoAdminRequired` docblock tag) on `updateProfiles()` would satisfy a
	 * naive "declare an auth posture" check while opening instance-wide PII
	 * policy to every logged-in account.
	 *
	 * @return void
	 */
	public function testUpdateProfilesIsNotUserAccessible(): void {
		$method = new ReflectionMethod(BatchAnonymizationController::class, 'updateProfiles');

		$this->assertCount(
			0,
			$method->getAttributes(NoAdminRequired::class),
			'updateProfiles() must NOT carry #[NoAdminRequired] — it writes instance-wide anonymisation policy.'
		);

		// Match the tag only where a tag can actually live — at the start of a
		// docblock line. A bare substring search would also hit the prose in
		// this method's own docblock, which NAMES the annotation in order to
		// warn against it: a checker that greps a string literal matches
		// comments as readily as code, and fails in both directions at once.
		$this->assertDoesNotMatchRegularExpression(
			'/^\s*\*\s*@NoAdminRequired\b/m',
			(string)$method->getDocComment(),
			'updateProfiles() must NOT carry the @NoAdminRequired docblock tag either; Nextcloud honours both '
			. 'spellings, so pinning only the attribute would leave the docblock route open.'
		);

	}//end testUpdateProfilesIsNotUserAccessible()

	/**
	 * Reading the profile stays user-level — the asymmetry is deliberate.
	 *
	 * Without this arm the pair above could be "satisfied" by locking the read
	 * endpoint down too, which would break the UI while still looking green.
	 *
	 * @return void
	 */
	public function testGetProfilesRemainsUserAccessible(): void {
		$method = new ReflectionMethod(BatchAnonymizationController::class, 'getProfiles');
		$doc = (string)$method->getDocComment();

		$declaresUserAccess = ($method->getAttributes(NoAdminRequired::class) !== []
			|| preg_match('/^\s*\*\s*@NoAdminRequired\b/m', $doc) === 1);

		$this->assertTrue(
			$declaresUserAccess,
			'getProfiles() is a read of policy the UI needs and must stay user-accessible.'
		);

		$this->assertCount(
			0,
			$method->getAttributes(AuthorizedAdminSetting::class),
			'getProfiles() must not be admin-gated; only the WRITE side is.'
		);

	}//end testGetProfilesRemainsUserAccessible()

}//end class
