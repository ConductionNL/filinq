<?php

/**
 * wizardDefinition schema and the generatedDocument wizardContext
 *
 * Pins the register declarations the wizard services write against: the new
 * schema in the filinq register, its question model, and the additive
 * wizardContext on generatedDocument. A drift here would make every wizard
 * save or wizard generation fail the register's hard validation.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Settings
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/guided-document-wizard/tasks.md#1-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Settings;

use PHPUnit\Framework\TestCase;

/**
 * The wizard declarations in the register.
 */
class WizardDefinitionSchemaTest extends TestCase {

	/**
	 * The decoded register.
	 *
	 * @var array<string, mixed>
	 */
	private array $register;

	/**
	 * Load the register.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->register = json_decode((string) file_get_contents(__DIR__ . '/../../../lib/Settings/filinq_register.json'), true);

	}//end setUp()

	/**
	 * The schema is in the filinq register, which moved to 8.43.0.
	 *
	 * @return void
	 */
	public function testTheSchemaIsDeclaredInTheFilinqRegister(): void {
		$schemas = $this->register['components']['schemas'];
		$this->assertArrayHasKey('wizardDefinition', $schemas);
		$this->assertContains('wizardDefinition', $this->register['components']['registers']['filinq']['schemas']);
		$this->assertTrue(version_compare($this->register['info']['version'], '8.43.0', '>='));

		$schema = $schemas['wizardDefinition'];
		$this->assertSame(['name', 'templateId', 'questions'], $schema['required']);
		$this->assertTrue($schema['hardValidation']);
		$this->assertSame(['authenticated'], $schema['authorization']['read']);

	}//end testTheSchemaIsDeclaredInTheFilinqRegister()

	/**
	 * A question carries the four types, the three operators and requires key, label and type.
	 *
	 * @return void
	 */
	public function testTheQuestionModelMatchesTheDesign(): void {
		$question = $this->register['components']['schemas']['wizardDefinition']['properties']['questions']['items'];
		$this->assertSame(['key', 'label', 'type'], $question['required']);
		$this->assertSame(['text', 'choice', 'date', 'registerObject'], $question['properties']['type']['enum']);
		$this->assertSame(['equals', 'notEquals', 'answered'], $question['properties']['condition']['properties']['operator']['enum']);
		foreach (['helpText', 'required', 'choices', 'register', 'schema', 'mapsTo'] as $field) {
			$this->assertArrayHasKey($field, $question['properties']);
		}

	}//end testTheQuestionModelMatchesTheDesign()

	/**
	 * generatedDocument gains an optional wizardContext and stays additive.
	 *
	 * @return void
	 */
	public function testGeneratedDocumentGainsAnOptionalWizardContext(): void {
		$generated = $this->register['components']['schemas']['generatedDocument'];
		$this->assertTrue(version_compare($generated['version'], '1.6.0', '>='));
		$this->assertNotContains('wizardContext', $generated['required']);

		$context = $generated['properties']['wizardContext'];
		$this->assertSame('object', $context['type']);
		$this->assertSame(['wizardId', 'wizardVersion', 'answers'], array_keys($context['properties']));

	}//end testGeneratedDocumentGainsAnOptionalWizardContext()
}//end class
