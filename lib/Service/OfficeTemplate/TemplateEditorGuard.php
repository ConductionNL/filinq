<?php

/**
 * Template editor guard
 *
 * The explicit check in front of every change to an existing template or
 * text fragment: the caller is an admin or in the template editors group,
 * the same group the `template` and `textFragment` schemas grant update and
 * delete to. OpenRegister checks it again when the object is written.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\OfficeTemplate
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/tasks.md#3-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\OfficeTemplate;

use OCP\IGroupManager;

/**
 * Refuses template changes to callers outside the editors group.
 *
 * @spec openspec/changes/office-template-authoring/tasks.md#3-1
 */
class TemplateEditorGuard {

	/**
	 * The group the template schemas grant update and delete to.
	 *
	 * @var string
	 */
	public const EDITORS_GROUP = 'docudesk-template-editors';

	/**
	 * Constructor.
	 *
	 * @param IGroupManager $groups Group membership.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IGroupManager $groups,
	) {

	}//end __construct()

	/**
	 * Refuse a caller who may not change templates.
	 *
	 * @param string $userId The caller.
	 *
	 * @return void
	 *
	 * @throws OfficeTemplateRefused 403 when the caller is neither admin nor editor.
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#3-1
	 */
	public function requireTemplateEditor(string $userId): void {
		if ($this->groups->isAdmin($userId) === true || $this->groups->isInGroup($userId, self::EDITORS_GROUP) === true) {
			return;
		}

		throw new OfficeTemplateRefused(message: 'Only template editors can change templates and text fragments.', reason: 'forbidden', code: 403);

	}//end requireTemplateEditor()
}//end class
