<?php

/**
 * Filinq Templates Leaf Provider
 *
 * The data half of the `filinq-templates` leaf (ADR-066): lists the templates
 * that apply to a host object, filtered on the object's register and schema,
 * in Filinq's own DI context. Read only: there is no create or get, and the
 * provider calls nothing in the consuming app.
 *
 * @category Integration
 * @package  OCA\Filinq\Integration
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/template-library-leaf/tasks.md#task-2.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Integration;

use OCA\Filinq\Kernel\AppIdentity;
use OCA\Filinq\Kernel\Contracts\TemplateServiceInterface;
use OCA\OpenRegister\Service\Integration\IntegrationProvider;
use OCP\IL10N;

/**
 * Lists the templates that apply to a host object.
 *
 * @category Integration
 * @package  OCA\Filinq\Integration
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/template-library-leaf/tasks.md#task-2.1
 */
class TemplatesLeafProvider implements IntegrationProvider {

	/**
	 * The leaf id, shared with the descriptor.
	 */
	public const ID = 'filinq-templates';

	/**
	 * The leaf icon.
	 */
	public const ICON = 'FileDocumentEditOutline';

	/**
	 * The leaf group.
	 */
	public const GROUP = 'documents';

	/**
	 * Constructor.
	 *
	 * @param TemplateServiceInterface $templates The template store, namespace enforcement included.
	 * @param IL10N           $l10n      Translates the label.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly TemplateServiceInterface $templates,
		private readonly IL10N $l10n,
	) {

	}//end __construct()

	/**
	 * {@inheritDoc}
	 *
	 * @return string The id.
	 *
	 * @spec openspec/changes/template-library-leaf/tasks.md#task-2.1
	 */
	public function getId(): string {
		return self::ID;

	}//end getId()

	/**
	 * {@inheritDoc}
	 *
	 * @return string The label.
	 *
	 * @spec openspec/changes/template-library-leaf/tasks.md#task-2.1
	 */
	public function getLabel(): string {
		return $this->l10n->t('Templates');

	}//end getLabel()

	/**
	 * {@inheritDoc}
	 *
	 * @return string The icon.
	 *
	 * @spec openspec/changes/template-library-leaf/tasks.md#task-2.1
	 */
	public function getIcon(): string {
		return self::ICON;

	}//end getIcon()

	/**
	 * {@inheritDoc}
	 *
	 * @return string|null The group.
	 *
	 * @spec openspec/changes/template-library-leaf/tasks.md#task-2.1
	 */
	public function getGroup(): ?string {
		return self::GROUP;

	}//end getGroup()

	/**
	 * {@inheritDoc}
	 *
	 * @return string|null Filinq.
	 *
	 * @spec openspec/changes/template-library-leaf/tasks.md#task-2.1
	 */
	public function getRequiredApp(): ?string {
		return AppIdentity::APP_ID;

	}//end getRequiredApp()

	/**
	 * The templates live in Filinq: OpenRegister persists nothing for this leaf.
	 *
	 * @return string `app-local`.
	 *
	 * @spec openspec/changes/template-library-leaf/tasks.md#task-2.1
	 */
	public function getStorageStrategy(): string {
		return 'app-local';

	}//end getStorageStrategy()

	/**
	 * {@inheritDoc}
	 *
	 * @return string|null None.
	 *
	 * @spec openspec/changes/template-library-leaf/tasks.md#task-2.1
	 */
	public function getOpenConnectorSource(): ?string {
		return null;

	}//end getOpenConnectorSource()

	/**
	 * {@inheritDoc}
	 *
	 * @return bool Always.
	 *
	 * @spec openspec/changes/template-library-leaf/tasks.md#task-2.1
	 */
	public function isEnabled(): bool {
		return true;

	}//end isEnabled()

	/**
	 * {@inheritDoc}
	 *
	 * @return string|null None beyond Filinq's own template access.
	 *
	 * @spec openspec/changes/template-library-leaf/tasks.md#task-2.1
	 */
	public function requiresPermission(): ?string {
		return null;

	}//end requiresPermission()

	/**
	 * {@inheritDoc}
	 *
	 * @return array<string, mixed> No outside credentials.
	 *
	 * @spec openspec/changes/template-library-leaf/tasks.md#task-2.1
	 */
	public function authRequirements(): array {
		return ['type' => 'none'];

	}//end authRequirements()

	/**
	 * {@inheritDoc}
	 *
	 * @return array<string, mixed> Healthy: the data is local.
	 *
	 * @spec openspec/changes/template-library-leaf/tasks.md#task-2.1
	 */
	public function health(): array {
		return ['status' => 'ok', 'authStatus' => 'configured', 'message' => null];

	}//end health()

	/**
	 * The templates that apply to an object of this register and schema.
	 *
	 * @param string               $register The host object's register slug.
	 * @param string               $schema   The host object's schema slug.
	 * @param string               $objectId The host object's id; scope is per schema, so unused.
	 * @param array<string, mixed> $filters  Unused.
	 *
	 * @return array<int, array{id: string, name: string, format: string, description: string}> The templates.
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter) The leaf interface passes the object id and filters; templates are scoped per schema.
	 *
	 * @spec openspec/changes/template-library-leaf/tasks.md#task-2.1
	 */
	public function list(string $register, string $schema, string $objectId, array $filters=[]): array {
		$rows = [];
		foreach ($this->templates->listForObject(register: $register, schema: $schema) as $template) {
			$rows[] = TemplateLeafRow::fromTemplate(template: $template);
		}

		return $rows;

	}//end list()
}//end class
