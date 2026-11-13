<?php

/**
 * Unit tests for WizardBindingCheck.
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

use OCA\Filinq\Service\Wizard\WizardBindingCheck;
use PHPUnit\Framework\TestCase;

/**
 * Warnings when wizard questions do not fit the template's bound schema.
 *
 * @covers \OCA\Filinq\Service\Wizard\WizardBindingCheck
 */
class WizardBindingCheckTest extends TestCase {

	/**
	 * The bound template.
	 *
	 * @var array<string, string>
	 */
	private const TEMPLATE = ['boundRegister' => 'zaken', 'boundSchema' => 'zaak'];

	/**
	 * No template or no bound schema means no warnings.
	 *
	 * @return void
	 */
	public function testUnboundTemplateHasNoWarnings(): void {
		$check = new WizardBindingCheck();
		$question = ['type' => 'text', 'mapsTo' => 'anything'];

		$this->assertSame([], $check->warnings(question: $question, name: 'q', template: null, boundProperties: ['x']));
		$this->assertSame([], $check->warnings(question: $question, name: 'q', template: ['boundSchema' => '  '], boundProperties: ['x']));
	}//end testUnboundTemplateHasNoWarnings()

	/**
	 * A register-object question must pick from the bound register and schema.
	 *
	 * @return void
	 */
	public function testRegisterObjectQuestion(): void {
		$check = new WizardBindingCheck();
		$fits = ['type' => 'registerObject', 'register' => 'zaken', 'schema' => 'zaak'];
		$wrong = ['type' => 'registerObject', 'register' => 'crm', 'schema' => 'klant'];

		$this->assertSame([], $check->warnings(question: $fits, name: 'pick', template: self::TEMPLATE, boundProperties: null));
		$this->assertSame(
			['Question "pick" picks from crm/klant, not the template\'s zaken/zaak.'],
			$check->warnings(question: $wrong, name: 'pick', template: self::TEMPLATE, boundProperties: null)
		);

		$anyRegister = ['boundRegister' => '', 'boundSchema' => 'zaak'];
		$otherRegister = ['type' => 'registerObject', 'register' => 'other', 'schema' => 'zaak'];
		$this->assertSame([], $check->warnings(question: $otherRegister, name: 'p', template: $anyRegister, boundProperties: null));
	}//end testRegisterObjectQuestion()

	/**
	 * A mapped path must start with a bound property, optionally after the schema name.
	 *
	 * @return void
	 */
	public function testMappedPathQuestion(): void {
		$check = new WizardBindingCheck();
		$props = ['title', 'amount'];

		$this->assertSame([], $check->warnings(question: ['mapsTo' => 'title'], name: 'a', template: self::TEMPLATE, boundProperties: $props));
		$this->assertSame([], $check->warnings(question: ['mapsTo' => 'zaak.amount.value'], name: 'b', template: self::TEMPLATE, boundProperties: $props));
		$this->assertSame([], $check->warnings(question: ['mapsTo' => ' '], name: 'c', template: self::TEMPLATE, boundProperties: $props));
		$this->assertSame([], $check->warnings(question: ['mapsTo' => 'missing'], name: 'd', template: self::TEMPLATE, boundProperties: null));
		$this->assertSame(
			['Question "e" maps to "owner.name", which the template\'s schema zaak does not have.'],
			$check->warnings(question: ['mapsTo' => 'owner.name'], name: 'e', template: self::TEMPLATE, boundProperties: $props)
		);
		$this->assertSame(
			['Question "f" maps to "zaak", which the template\'s schema zaak does not have.'],
			$check->warnings(question: ['mapsTo' => 'zaak'], name: 'f', template: self::TEMPLATE, boundProperties: $props)
		);
	}//end testMappedPathQuestion()

	/**
	 * Writing into a picked object's schema warns once per offending question.
	 *
	 * @return void
	 */
	public function testCollisionWarnings(): void {
		$questions = [
			['key' => 'pickZaak', 'type' => 'registerObject', 'schema' => 'zaak'],
			['key' => 'blank', 'type' => 'registerObject', 'schema' => ' '],
			['key' => 'title', 'type' => 'text', 'mapsTo' => 'zaak.title'],
			['key' => 'other', 'type' => 'text', 'mapsTo' => 'klant.name'],
			['key' => 'none', 'type' => 'text'],
		];

		$this->assertSame(
			['Question "title" writes into zaak, which replaces the whole zaak picked in question "pickZaak" in the template data.'],
			(new WizardBindingCheck())->collisionWarnings(questions: $questions)
		);
	}//end testCollisionWarnings()
}//end class
