<?php

/**
 * Filinq PdfUaVerdictTagger
 *
 * Puts the PDF/UA verdict on a file as a Nextcloud system tag, so the person
 * who works with the file sees it in any app that lists files: exactly one of
 * `pdfua-conform`, `pdfua-niet-conform` or `pdfua-niet-gecontroleerd`, the
 * other two removed. The tags are visible to users but only the server
 * assigns them, so a label cannot claim a check that did not happen.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\VeraPdf
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-every-pdf-attached-to-an-openregister-object-is-checked-for-pdfua-req-ddpum-006
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\VeraPdf;

use InvalidArgumentException;
use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\SystemTag\TagAlreadyExistsException;
use OCP\SystemTag\TagNotFoundException;

/**
 * Sets one verdict tag on a file and removes the others.
 */
class PdfUaVerdictTagger {

	public const CONFORM = 'pdfua-conform';

	public const NOT_CONFORM = 'pdfua-niet-conform';

	public const NOT_CHECKED = 'pdfua-niet-gecontroleerd';

	/**
	 * The verdict tags, in a fixed order.
	 */
	public const TAGS = [self::CONFORM, self::NOT_CONFORM, self::NOT_CHECKED];

	/**
	 * The system tag object type of a file.
	 */
	private const OBJECT_TYPE = 'files';

	/**
	 * Constructor.
	 *
	 * @param ISystemTagManager      $tags    Finds and creates the tags.
	 * @param ISystemTagObjectMapper $objects Assigns them to files.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly ISystemTagManager $tags,
		private readonly ISystemTagObjectMapper $objects,
	) {

	}//end __construct()

	/**
	 * The verdict tag for a report, or NOT_CHECKED without one.
	 *
	 * @param array<string, mixed>|null $report The stored PDF/UA report.
	 *
	 * @return string One of TAGS.
	 *
	 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-every-pdf-attached-to-an-openregister-object-is-checked-for-pdfua-req-ddpum-006
	 */
	public static function verdictOf(?array $report): string {
		if ($report === null || is_bool($report['compliant'] ?? null) === false) {
			return self::NOT_CHECKED;
		}

		if ($report['compliant'] === true) {
			return self::CONFORM;
		}

		return self::NOT_CONFORM;

	}//end verdictOf()

	/**
	 * Tag a file with one verdict, removing the other two.
	 *
	 * @param int    $fileId  The file id.
	 * @param string $verdict One of TAGS.
	 *
	 * @return void
	 *
	 * @throws InvalidArgumentException For a verdict that is not one of TAGS.
	 *
	 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-every-pdf-attached-to-an-openregister-object-is-checked-for-pdfua-req-ddpum-006
	 */
	public function tag(int $fileId, string $verdict): void {
		if (in_array($verdict, self::TAGS, true) === false) {
			throw new InvalidArgumentException(message: 'Unknown PDF/UA verdict tag: ' . $verdict);
		}

		$objectId = (string) $fileId;
		$others = [];
		foreach (self::TAGS as $name) {
			if ($name !== $verdict) {
				$others[] = $this->tagFor(name: $name)->getId();
			}
		}

		$this->objects->unassignTags($objectId, self::OBJECT_TYPE, $others);
		$this->objects->assignTags($objectId, self::OBJECT_TYPE, [$this->tagFor(name: $verdict)->getId()]);

	}//end tag()

	/**
	 * The system tag with this name, created when it is not there yet.
	 *
	 * @param string $name The tag name.
	 *
	 * @return ISystemTag The tag.
	 */
	private function tagFor(string $name): ISystemTag {
		try {
			return $this->tags->getTag($name, true, false);
		} catch (TagNotFoundException) {
			try {
				return $this->tags->createTag($name, true, false);
			} catch (TagAlreadyExistsException) {
				// Another process made it between the two calls.
				return $this->tags->getTag($name, true, false);
			}
		}

	}//end tagFor()
}//end class
