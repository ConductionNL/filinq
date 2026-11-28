<?php

/**
 * Golden cases of what the external mount validator asks and reports.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\ExternalMountValidator;
use OCA\Filinq\Service\MountCapabilityProbe;
use OCA\Filinq\Service\UploadPolicyService;
use OCA\Filinq\Tests\Unit\Support\RecordsCalls;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * The probe and policy calls validate() makes, in order, and the report it
 * returns, compared with the recorded JSON.
 *
 * @covers \OCA\Filinq\Service\ExternalMountValidator
 *
 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
 */
class ExternalMountValidatorGoldenTest extends TestCase {
	use RecordsCalls;

	private const GOLDEN = __DIR__.'/golden/external-mount-validator.json';

	/**
	 * The cases: the answers of isWritable, supportsPerGroupPermissions,
	 * supportsVersions and writesPassThroughFilinq, and the upload policy
	 * (a policy, none, or unreadable).
	 *
	 * @return array<string, array{0: array<int, ?bool>, 1: string}>
	 */
	public static function cases(): array {
		return [
			'every promise kept, with a policy'    => [[true, true, true, true], 'policy'],
			'every promise kept, no policy'        => [[true, true, true, true], 'none'],
			'every answer no, with a policy'       => [[false, false, false, false], 'policy'],
			'every answer no, no policy'           => [[false, false, false, false], 'none'],
			'nobody could find out, with a policy' => [[null, null, null, null], 'policy'],
			'nobody could find out, unreadable'    => [[null, null, null, null], 'unreadable'],
			'mixed answers'                        => [[false, null, true, false], 'policy'],
			'mixed answers, reversed'              => [[true, false, null, null], 'policy'],
			'only the policy is at stake'          => [[true, true, true, false], 'unreadable'],
			'only versions are missing'            => [[true, true, false, true], 'none'],
		];

	}//end cases()

	/**
	 * The calls and the report match the recorded ones.
	 *
	 * @param array<int, ?bool> $answers The probe answers.
	 * @param string            $policy  The upload policy state.
	 *
	 * @dataProvider cases
	 *
	 * @return void
	 */
	public function testValidate(array $answers, string $policy): void {
		$probe     = $this->recorder(
			MountCapabilityProbe::class,
			[
				'isWritable'                  => $answers[0],
				'supportsPerGroupPermissions' => $answers[1],
				'supportsVersions'            => $answers[2],
				'writesPassThroughFilinq'     => $answers[3],
			]
		);
		$active    = match ($policy) {
			'policy'     => ['name' => 'p'],
			'none'       => null,
			'unreadable' => static function (): never {
				throw new RuntimeException('unreadable');
			},
		};
		$policies  = $this->recorder(UploadPolicyService::class, ['activePolicy' => $active]);
		$validator = new ExternalMountValidator(probe: $probe, uploadPolicy: $policies);
		$this->traceCall(fn () => $validator->validate(path: '/Domains/Zaken'));

		$this->assertTrace(file: self::GOLDEN, name: (string) $this->dataName());

	}//end testValidate()
}//end class
