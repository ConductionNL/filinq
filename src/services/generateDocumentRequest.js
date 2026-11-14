/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The request behind the generate-document leaf: one template, the host
 * object as its data, stored as a file. Kept free of Vue so the body and the
 * reading of the answer are tested without mounting anything.
 *
 * @spec openspec/changes/template-library-leaf/tasks.md#task-2.2
 */

/**
 * The body `POST /apps/filinq/api/documents/generate` reads for one template
 * on one host object. The document is stored (`output.mode: files`) rather
 * than downloaded, so it stays with the object.
 *
 * @param {object} args The template and the host object.
 * @param {{id: string, name: string}} args.template The chosen template.
 * @param {{register: string, schema: string, objectId: string}} args.host The host object.
 *
 * @return {object} The request body.
 *
 * @spec openspec/changes/template-library-leaf/tasks.md#task-2.2
 */
export function buildGenerateRequest({ template, host }) {
	return {
		templateId: template.id,
		dataRefs: [{ register: host.register, schema: host.schema, id: host.objectId }],
		options: { output: { mode: 'files' } },
		filename: (template.name || '').trim() || 'document',
	}
}

/**
 * Whether a template can be generated on this host object.
 *
 * @param {{id?: string}|null} template The chosen template.
 * @param {{register: string, schema: string, objectId: string}} host The host object.
 *
 * @return {boolean} True with a template and a complete host object.
 *
 * @spec openspec/changes/template-library-leaf/tasks.md#task-2.2
 */
export function canGenerate(template, host) {
	return Boolean(template && template.id && host.register && host.schema && host.objectId)
}

/**
 * The stored file named in the server's answer, or null when there is none.
 *
 * @param {object} body The parsed answer.
 *
 * @return {{fileId: number, name: string}|null} The stored file.
 *
 * @spec openspec/changes/template-library-leaf/tasks.md#task-2.2
 */
export function storedFile(body) {
	if (!body || body.fileId === null || body.fileId === undefined) {
		return null
	}
	return { fileId: Number(body.fileId), name: String(body.name || '') }
}
