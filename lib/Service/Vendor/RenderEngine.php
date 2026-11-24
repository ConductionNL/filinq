<?php

/**
 * Render Engine
 *
 * Reads a generation's `options.engine`: absent or `filinq` is filinq's own
 * render, `vendor:<sourceSlug>` names a document generation source, and any
 * other value is refused.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Vendor
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/template-engine-renders-through-a-vendor/specs/document-creatie-sjablonen/spec.md#requirement-a-render-names-its-engine-req-tev-001
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Vendor;

use Exception;

/**
 * The engine a render names.
 *
 * @spec openspec/changes/template-engine-renders-through-a-vendor/specs/document-creatie-sjablonen/spec.md#requirement-a-render-names-its-engine-req-tev-001
 */
final class RenderEngine {

	/**
	 * Filinq's own engine.
	 *
	 * @var string
	 */
	public const FILINQ = 'filinq';

	/**
	 * The prefix of a vendor engine; the source slug follows it.
	 *
	 * @var string
	 */
	public const VENDOR_PREFIX = 'vendor:';

	/**
	 * The source slug a vendor engine names, or null for filinq's own render.
	 *
	 * @param mixed $engine The `options.engine` value as the caller sent it.
	 *
	 * @return string|null The source slug, or null when filinq renders.
	 *
	 * @throws Exception 400 naming the value when it is no engine.
	 *
	 * @spec openspec/changes/template-engine-renders-through-a-vendor/specs/document-creatie-sjablonen/spec.md#requirement-a-render-names-its-engine-req-tev-001
	 */
	public function vendorSlug(mixed $engine): ?string {
		if ($engine === null || $engine === '' || $engine === self::FILINQ) {
			return null;
		}

		if (is_string($engine) === true && str_starts_with($engine, self::VENDOR_PREFIX) === true) {
			$slug = trim(substr($engine, strlen(self::VENDOR_PREFIX)));
			if ($slug !== '') {
				return $slug;
			}
		}

		$shown = 'a non-text value';
		if (is_scalar($engine) === true) {
			$shown = '"' . (string) $engine . '"';
		}

		throw new Exception(
			message: 'options.engine ' . $shown . ' is no engine; use "filinq" or "vendor:<sourceSlug>".',
			code: 400
		);

	}//end vendorSlug()

	/**
	 * The engine name of a vendor source slug.
	 *
	 * @param string $slug The source slug.
	 *
	 * @return string `vendor:<slug>`.
	 *
	 * @spec openspec/changes/template-engine-renders-through-a-vendor/specs/document-creatie-sjablonen/spec.md#requirement-the-engines-an-instance-offers-are-listed-req-tev-004
	 */
	public function forVendor(string $slug): string {
		return self::VENDOR_PREFIX . $slug;

	}//end forVendor()
}//end class
