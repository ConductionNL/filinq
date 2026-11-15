<?php

/**
 * Naming what a store cannot do, before a document is put on it.
 *
 * 🔴 "COULD NOT BE FOUND OUT" IS NOT "YES", AND IT IS NOT "NO" EITHER. A
 * validator that folds an unknown into a pass lets a mount through whose
 * permissions nobody has ever checked; one that folds it into a failure cries
 * wolf about every mount whose driver simply does not answer that question. The
 * three-valued answer is the point of this class, so it is asserted on its own.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\ExternalMountValidator;
use OCA\Filinq\Service\MountCapabilityProbe;
use OCA\Filinq\Service\UploadPolicyService;
use PHPUnit\Framework\TestCase;

/**
 * `ExternalMountValidator`.
 *
 * @covers \OCA\Filinq\Service\ExternalMountValidator
 */
class ExternalMountValidatorTest extends TestCase {

	/**
	 * A probe answering as told.
	 *
	 * @param bool|null $writable    Whether the mount accepts a write.
	 * @param bool|null $permissions Whether it holds a permission per group.
	 * @param bool|null $versions    Whether it keeps a version history.
	 * @param bool|null $throughApp  Whether every write passes filinq.
	 *
	 * @return MountCapabilityProbe The double.
	 */
	private function probe(
		?bool $writable = true,
		?bool $permissions = true,
		?bool $versions = true,
		?bool $throughApp = true,
	): MountCapabilityProbe {
		$probe = $this->createMock(MountCapabilityProbe::class);
		$probe->method('isWritable')->willReturn($writable);
		$probe->method('supportsPerGroupPermissions')->willReturn($permissions);
		$probe->method('supportsVersions')->willReturn($versions);
		$probe->method('writesPassThroughFilinq')->willReturn($throughApp);

		return $probe;
	}//end probe()

	/**
	 * An upload policy service that does or does not have one.
	 *
	 * The double is built with `onlyMethods` so it cannot answer a question the
	 * real UploadPolicyService does not have: a double free to invent
	 * `activePolicy()` would keep passing after a rename, covering a validator
	 * that reads a policy nothing provides.
	 *
	 * @param bool $declared Whether a policy is declared.
	 *
	 * @return UploadPolicyService The double.
	 */
	private function policy(bool $declared): UploadPolicyService {
		$service = $this->getMockBuilder(UploadPolicyService::class)
			->disableOriginalConstructor()
			->onlyMethods(['activePolicy'])
			->getMock();

		$service->method('activePolicy')->willReturn(
			$declared === true ? ['name' => 'Standaard', 'allowedExtensions' => ['pdf']] : null
		);

		return $service;
	}//end policy()

	/**
	 * A mount that can do everything passes with nothing to say.
	 *
	 * @return void
	 */
	public function testACapableMountPasses(): void {
		$validator = new ExternalMountValidator($this->probe(), $this->policy(true));

		$result = $validator->validate(path: 'Filinq/zaak-1');

		self::assertTrue($result['ok']);
		self::assertSame([], $result['findings']);
	}//end testACapableMountPasses()

	/**
	 * A mount without per-group permissions names the reconciliation requirement.
	 *
	 * @return void
	 */
	public function testNamesTheReconciliationRequirementItCannotMeet(): void {
		$validator = new ExternalMountValidator($this->probe(permissions: false), $this->policy(true));

		$result = $validator->validate(path: 'Filinq/zaak-1');

		self::assertFalse($result['ok']);

		$findings = array_column($result['findings'], 'requirement');
		self::assertContains('reconcile the folder to the domain', $findings);

		$finding = $result['findings'][array_search('reconcile the folder to the domain', $findings, true)];
		self::assertSame(ExternalMountValidator::CANNOT, $finding['verdict']);

		// The message says the consequence, not the property name: an
		// administrator acts on "a group removed from the domain keeps its
		// access", not on "supportsPerGroupPermissions: false".
		self::assertStringContainsString('keeps its access', $finding['message']);
	}//end testNamesTheReconciliationRequirementItCannotMeet()

	/**
	 * An unanswerable question is reported as unknown, apart from a refusal.
	 *
	 * @return void
	 */
	public function testAnUnknownIsItsOwnVerdict(): void {
		$validator = new ExternalMountValidator($this->probe(permissions: null), $this->policy(true));

		$result = $validator->validate(path: 'Filinq/zaak-1');

		self::assertFalse($result['ok']);
		self::assertSame(ExternalMountValidator::UNKNOWN, $result['findings'][0]['verdict']);
		self::assertStringContainsString('could not be found out', $result['findings'][0]['message']);
	}//end testAnUnknownIsItsOwnVerdict()

	/**
	 * With no policy declared, there is no policy to report as unenforceable.
	 *
	 * @return void
	 */
	public function testNoPolicyMeansNoEnforcementFinding(): void {
		$validator = new ExternalMountValidator($this->probe(throughApp: false), $this->policy(false));

		$result = $validator->validate(path: 'Filinq/zaak-1');

		self::assertTrue($result['ok']);
	}//end testNoPolicyMeansNoEnforcementFinding()

	/**
	 * With a policy declared, a mount writable behind filinq's back is named.
	 *
	 * @return void
	 */
	public function testABypassableMountIsNamedWhenAPolicyExists(): void {
		$validator = new ExternalMountValidator($this->probe(throughApp: false), $this->policy(true));

		$result = $validator->validate(path: 'Filinq/zaak-1');

		$findings = array_column($result['findings'], 'requirement');
		self::assertContains('enforce the upload policy', $findings);
	}//end testABypassableMountIsNamedWhenAPolicyExists()

	/**
	 * A read-only store is named first, because nothing else matters on it.
	 *
	 * @return void
	 */
	public function testAReadOnlyStoreIsNamed(): void {
		$validator = new ExternalMountValidator($this->probe(writable: false), $this->policy(true));

		$result = $validator->validate(path: 'Filinq/zaak-1');

		self::assertFalse($result['ok']);
		self::assertSame('store a document at all', $result['findings'][0]['requirement']);
	}//end testAReadOnlyStoreIsNamed()
}//end class
