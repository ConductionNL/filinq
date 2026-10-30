<?php

/**
 * StripsFileExtension — a file name without its last extension.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
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

namespace OCA\Filinq\Service;

/**
 * Strips the last extension from a file name, for naming converted output.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude file-name helper — carries no product behaviour of its own
 */
trait StripsFileExtension {
	/**
	 * The name up to its last dot, or the name itself when it has none.
	 *
	 * @param string $name The file name.
	 *
	 * @return string The name without its extension.
	 */
	private function stripExtension(string $name): string {
		$dotPos = strrpos($name, '.');
		if ($dotPos === false) {
			return $name;
		}

		return substr($name, 0, $dotPos);
	}//end stripExtension()
}//end trait
