<?php

/**
 * Filinq Generate Document Flow Node, directly invokable
 *
 * The same `filinq.generate-document` node, opted in to OpenRegister's direct
 * invocation (`POST /api/flows/{flowId}/nodes/{nodeId}/run`, or-flow-run-node):
 * an app button can generate one document on one object without running the
 * flow around the step. OpenRegister lets that through only when the caller
 * may update the subject, and the node files the document on that subject
 * and nowhere else (see GenerateDocumentNode::execute()).
 *
 * A subclass rather than `implements` on GenerateDocumentNode, because an
 * OpenRegister that predates direct invocation has no such interface and a
 * class naming it would not load: FilinqFlowNodeListener registers this
 * variant only where the interface exists, and the plain node elsewhere.
 *
 * @category Flow
 * @package  OCA\Filinq\Flow
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/generate-document-runs-directly-on-an-object/specs/flow-document-generation/spec.md#requirement-the-generate-document-step-can-be-run-directly-on-one-object
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Flow;

use OCA\OpenRegister\Service\Flow\IFlowDirectlyInvokable;

/**
 * Generates a document on one object, run directly by an app.
 *
 * @category Flow
 * @package  OCA\Filinq\Flow
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/generate-document-runs-directly-on-an-object/specs/flow-document-generation/spec.md#requirement-the-generate-document-step-can-be-run-directly-on-one-object
 */
class DirectGenerateDocumentNode extends GenerateDocumentNode implements IFlowDirectlyInvokable {
}//end class
