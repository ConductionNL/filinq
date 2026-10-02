<?php

/**
 * The ways a redacted value survives in a file that looks redacted.
 *
 * 🔴 EVERY ONE OF THESE HAS SHIPPED SOMEWHERE AS A PUBLISHED WOO DOCUMENT.
 * The commonest is the first: a black rectangle is drawn over the text and the
 * text is still there, selectable, copyable, and returned by any extractor. The
 * document looks redacted to the person who made it and to everyone who
 * approves it, because the only surface any of them look at is the rendered
 * page.
 *
 * The others are quieter and no less complete: a thumbnail generated before the
 * mark was applied, the author's name in XMP, GPS in EXIF, the previous
 * revision left behind by an incremental save, an annotation whose /Contents
 * still holds what the mark covers, a form field's value, an attached
 * spreadsheet nobody opened.
 *
 * 🔴 SO THIS IS A CLOSED LIST WITH A GUARD, NOT A CHECKLIST SOMEBODY EXTENDS.
 * An output mode that is not verified against every route fails the suite. A
 * route added here with no check fails too. The failure this whole change
 * exists to prevent is a verification that reports success for a route it never
 * looked at, which is indistinguishable from a clean document.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Redaction
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Redaction;

/**
 * The leak routes a written copy is verified against.
 */
final class RedactionLeakRoute {

	/**
	 * The text is still under the black rectangle.
	 *
	 * @var string
	 */
	public const TEXT_UNDER_MARK = 'text_under_mark';

	/**
	 * A thumbnail or preview stream drawn before the mark was applied.
	 *
	 * @var string
	 */
	public const EMBEDDED_PREVIEW = 'embedded_preview';

	/**
	 * XMP packets: title, author, subject, keywords, history.
	 *
	 * @var string
	 */
	public const XMP = 'xmp';

	/**
	 * EXIF and document properties.
	 *
	 * @var string
	 */
	public const EXIF = 'exif';

	/**
	 * An incremental save that kept the whole previous revision.
	 *
	 * @var string
	 */
	public const INCREMENTAL_UPDATE = 'incremental_update';

	/**
	 * Annotation contents and form field values.
	 *
	 * @var string
	 */
	public const ANNOTATIONS_AND_FIELDS = 'annotations_and_fields';

	/**
	 * A file attached inside the document.
	 *
	 * @var string
	 */
	public const EMBEDDED_ATTACHMENT = 'embedded_attachment';

	/**
	 * Every route, and the suite fails for an output mode missing any of them.
	 *
	 * @var string[]
	 */
	public const ALL = [
		self::TEXT_UNDER_MARK,
		self::EMBEDDED_PREVIEW,
		self::XMP,
		self::EXIF,
		self::INCREMENTAL_UPDATE,
		self::ANNOTATIONS_AND_FIELDS,
		self::EMBEDDED_ATTACHMENT,
	];

	/**
	 * What each route means, for the operator reading a failed verification.
	 *
	 * A route name is a developer's word. "The text is still under the mark"
	 * is what tells an archivist the document cannot be published.
	 *
	 * @var array<string, string>
	 */
	public const DESCRIPTIONS = [
		self::TEXT_UNDER_MARK => 'the redacted text is still in the file, underneath the mark',
		self::EMBEDDED_PREVIEW => 'a preview image inside the file still shows the unredacted page',
		self::XMP => 'the redacted value is still in the XMP metadata',
		self::EXIF => 'the redacted value is still in the EXIF or document properties',
		self::INCREMENTAL_UPDATE => 'an earlier revision of the file is still inside it',
		self::ANNOTATIONS_AND_FIELDS => 'an annotation or form field still holds the redacted value',
		self::EMBEDDED_ATTACHMENT => 'a file attached inside this document still holds the redacted value',
	];
}//end class
