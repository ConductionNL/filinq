<?php

/**
 * Contract of PublicationListComposer
 *
 * The public surface of the PublicationListComposer service, so callers in other
 * components depend on this contract instead of the implementation.
 *
 * @category  Kernel
 * @package   OCA\Filinq\Kernel\Contracts
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Kernel\Contracts;

/**
 * Contract of PublicationListComposer.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface PublicationListComposerInterface {
	/**
	 * The view does not resolve at all.
	 *
	 * @var string
	 */
	public const NO_SUCH_VIEW = 'no_such_view';

	/**
	 * One or more records have no redacted copy yet.
	 *
	 * @var string
	 */
	public const NOT_READY = 'not_ready';

	/**
	 * Compose the list over one saved view.
	 *
	 * @param string $view  The view id or slug.
	 * @param string $title What the list is called.
	 *
	 * @return array<string, mixed> The composed list, or a refusal carrying `refused`.
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 */
	public function compose(string $view, string $title = ''): array;
}//end interface
