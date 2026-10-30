<?php

/**
 * Unit tests for WizardRefused
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Exception
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#2-4
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\Exception;

use OCA\Filinq\Exception\WizardRefused;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for WizardRefused
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Exception
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class WizardRefusedTest extends TestCase {

	/**
	 * A refusal without question errors adds nothing to the error body.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#2-4
	 */
	public function testRefusalWithoutErrorsHasNoErrorDetails(): void {
		$e = new WizardRefused(message: 'Not found.', code: 404);

		$this->assertSame(expected: 'Not found.', actual: $e->getMessage());
		$this->assertSame(expected: 404, actual: $e->getCode());
		$this->assertSame(expected: [], actual: $e->getErrors());
		$this->assertSame(expected: [], actual: $e->getErrorDetails());

	}//end testRefusalWithoutErrorsHasNoErrorDetails()

	/**
	 * A refusal with question errors names them under `errors`.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#2-4
	 */
	public function testRefusalWithErrorsNamesThemByQuestion(): void {
		$errors = ['startDate' => 'Required.', 'amount' => 'Must be a number.'];
		$e      = new WizardRefused(message: 'Invalid answers.', code: 422, errors: $errors);

		$this->assertSame(expected: 422, actual: $e->getCode());
		$this->assertSame(expected: $errors, actual: $e->getErrors());
		$this->assertSame(expected: ['errors' => $errors], actual: $e->getErrorDetails());

	}//end testRefusalWithErrorsNamesThemByQuestion()

}//end class
