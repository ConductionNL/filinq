/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Editor delivery: how an agent edit reaches a document that is OPEN in an
 * office editor, from the page the editor runs on.
 *
 * The backend never writes underneath an open editor: `editDocument` answers
 * `delivery: "editor"` with text replacements instead, and the editor page
 * applies them so the editor stays the only writer. This module is that page
 * side, and the companion relays the backend's answer into it.
 *
 * ⚠️ ADR-087 §5: suite divergence is confined to DELIVERY. Each adapter below
 * does one thing per operation — flush, count, replace — in its suite's own
 * client API, and is handed the text to find and the text that replaces it.
 * No adapter decides WHAT changes; that was computed once, suite-independent,
 * by the backend's edit plan. An adapter that starts computing edits has
 * crossed the line.
 *
 * The adapter names match `lib/Settings/editor-delivery.json`. An adapter that
 * is not declared here refuses and names itself, rather than guessing.
 *
 * @spec openspec/changes/edit-through-the-open-editor/tasks.md#3-deliver-the-edit-through-the-editor
 */

/**
 * How long a postMessage editor may take to answer a save before it counts as failed.
 *
 * @type {number}
 */
export const SAVE_TIMEOUT_MS = 15000

/**
 * One suite's delivery: flush, count and replace, and nothing that decides what changes.
 *
 * @typedef {object} EditorAdapter
 * @property {function(object): Promise<void>} flush Commit the editor's unsaved state.
 * @property {function(object, string): Promise<number|null>} count Occurrences of a text, or null when the suite cannot tell.
 * @property {function(object, {find: string, replace: string}): Promise<void>} replace Replace a text.
 */

/**
 * The ONLYOFFICE / Euro-Office automation connector (`docEditor.createConnector()`).
 *
 * The command runs inside the editor, so it cannot close over local values:
 * the texts are embedded as JSON literals in the command's source.
 *
 * @type {EditorAdapter}
 */
const connectorAdapter = {
	/**
	 * Commit the editor's unsaved state to the stored file.
	 *
	 * @param {object} connector The editor's automation connector.
	 * @return {Promise<void>}
	 */
	flush(connector) {
		return runCommand(connector, 'Api.Save(); return true;').then(() => undefined)
	},

	/**
	 * How often a text occurs in the open document, case-sensitively.
	 *
	 * @param {object} connector The editor's automation connector.
	 * @param {string} find The text.
	 * @return {Promise<number>}
	 */
	count(connector, find) {
		return runCommand(
			connector,
			`var found = Api.GetDocument().Search(${JSON.stringify(find)}, true); return found ? found.length : 0;`,
		).then((result) => Number(result ?? 0))
	},

	/**
	 * Replace a text in the open document.
	 *
	 * @param {object} connector The editor's automation connector.
	 * @param {{find: string, replace: string}} replacement The replacement.
	 * @return {Promise<void>}
	 */
	replace(connector, { find, replace }) {
		const options = JSON.stringify({ searchString: find, replaceString: replace, matchCase: true })
		return runCommand(
			connector,
			`Api.GetDocument().SearchAndReplace(${options}); return true;`,
		).then(() => undefined)
	},
}

/**
 * The Collabora Online postMessage API, addressed at the editor iframe.
 *
 * This API reports no match count, so `count` is null: the delivery then
 * re-reads the saved document and confirms each replacement took, instead.
 *
 * @type {EditorAdapter}
 */
const postMessageAdapter = {
	/**
	 * Commit the editor's unsaved state to the stored file, and wait for the editor to say so.
	 *
	 * @param {{window: Window, origin: string, events?: EventTarget, timeoutMs?: number}} target The editor frame.
	 * @return {Promise<void>}
	 */
	flush(target) {
		return new Promise((resolve, reject) => {
			const events = target.events ?? globalThis
			const timeoutMs = target.timeoutMs ?? SAVE_TIMEOUT_MS
			let timer = null

			const onMessage = (event) => {
				if (event.origin !== target.origin) {
					return
				}
				const message = parseMessage(event.data)
				if (message?.MessageId !== 'Action_Save_Resp') {
					return
				}
				clearTimeout(timer)
				events.removeEventListener('message', onMessage)
				if (message.Values?.success === false) {
					reject(new Error('The open editor refused to save the document, so it was not changed.'))
					return
				}
				resolve()
			}

			timer = setTimeout(() => {
				events.removeEventListener('message', onMessage)
				reject(new Error('The open editor did not confirm it saved the document, so it was not changed.'))
			}, timeoutMs)

			events.addEventListener('message', onMessage)
			try {
				post(target, {
					MessageId: 'Action_Save',
					Values: { DontTerminateEdit: true, DontSaveIfUnmodified: true, Notify: true },
				})
			} catch (error) {
				clearTimeout(timer)
				events.removeEventListener('message', onMessage)
				reject(error)
			}
		})
	},

	/**
	 * Not available through this API.
	 *
	 * @return {Promise<null>}
	 */
	count() {
		return Promise.resolve(null)
	},

	/**
	 * Replace a text in the open document.
	 *
	 * @param {{window: Window, origin: string}} target The editor frame.
	 * @param {{find: string, replace: string}} replacement The replacement.
	 * @return {Promise<void>}
	 */
	replace(target, { find, replace }) {
		post(target, {
			MessageId: 'Send_UNO_Command',
			Values: {
				Command: '.uno:ExecuteSearch',
				Args: {
					'SearchItem.SearchString': { type: 'string', value: find },
					'SearchItem.ReplaceString': { type: 'string', value: replace },
					'SearchItem.MatchCase': { type: 'boolean', value: true },
					// 3 = replace all; the plan guaranteed exactly one occurrence.
					'SearchItem.Command': { type: 'long', value: 3 },
				},
			},
		})
		return Promise.resolve()
	},
}

/**
 * The declared delivery adapters, by the name the registry gives them.
 *
 * @type {Readonly<Record<string, EditorAdapter>>}
 */
export const EDITOR_ADAPTERS = Object.freeze({
	connector: connectorAdapter,
	postMessage: postMessageAdapter,
})

/**
 * The adapter for a declared name, or a refusal that names the suite.
 *
 * @param {string|null|undefined} name The adapter name from the backend's answer.
 * @param {string} [suite] The suite, for the message.
 * @return {EditorAdapter}
 * @throws {Error} When no adapter by that name exists.
 */
export function resolveAdapter(name, suite = '') {
	const adapter = Object.hasOwn(EDITOR_ADAPTERS, String(name)) ? EDITOR_ADAPTERS[name] : null
	if (adapter === null) {
		const label = suite !== '' ? suite : String(name ?? 'this editor')
		throw new Error(
			`The document is open in ${label}, which offers no way to apply an edit through the editor, `
			+ 'so it was not changed. Close the document and the edit can be made directly.',
		)
	}

	return adapter
}

/**
 * Ask the open editor to commit unsaved state, before the agent reads the document.
 *
 * Anchors hash the STORED bytes, so a read before this describes text the
 * user may already have changed. The agent announces the save first.
 *
 * @param {{adapter: string, suite?: string}} editor The editor block from the backend.
 * @param {object} handle The suite's live handle (connector, or editor frame).
 * @return {Promise<void>}
 *
 * @spec openspec/changes/edit-through-the-open-editor/tasks.md#2-make-the-document-current-before-reading-it
 */
export function flushOpenEditor(editor, handle) {
	return resolveAdapter(editor?.adapter, editor?.suite ?? '').flush(handle)
}

/**
 * Flush the open editor, then read: the order anchors need.
 *
 * The read only starts once the editor has confirmed its save, so the
 * anchors it returns hash what the user is looking at. A failed save fails
 * the read too, rather than handing the agent stale anchors.
 *
 * @param {{adapter: string, suite?: string}} editor The editor block from the backend.
 * @param {object} handle The suite's live handle (connector, or editor frame).
 * @param {function(): Promise<object>} read Reads the stored document (the readDocument tool).
 * @return {Promise<object>} The read, taken after the flush.
 *
 * @spec openspec/changes/edit-through-the-open-editor/specs/edit-through-the-open-editor/spec.md#scenario-unsaved-changes-are-flushed-first
 */
export async function flushThenRead(editor, handle, read) {
	await flushOpenEditor(editor, handle)
	return read()
}

/**
 * Check a delivery answer is a list of text-to-match replacements, nothing positional.
 *
 * @param {object} delivery The backend's `delivery: "editor"` answer.
 * @return {Array<{find: string, replace: string}>}
 * @throws {Error} When the answer is not deliverable.
 */
export function replacementsOf(delivery) {
	if (delivery?.delivery !== 'editor') {
		throw new Error('This edit was not meant for the open editor, so it was not applied there.')
	}

	const replacements = Array.isArray(delivery.replacements) ? delivery.replacements : []
	if (replacements.length === 0) {
		throw new Error('The edit carries no replacements, so nothing was changed.')
	}

	return replacements.map((entry) => {
		const keys = Object.keys(entry ?? {}).sort()
		if (keys.join(',') !== 'find,replace' || typeof entry.find !== 'string' || typeof entry.replace !== 'string') {
			throw new Error('An edit for the open editor must name the text to find and its replacement, and nothing else.')
		}
		if (entry.find.trim() === '') {
			throw new Error('An edit for the open editor has no text to find, so nothing was changed.')
		}

		return { find: entry.find, replace: entry.replace }
	})
}

/**
 * Apply an agent edit through the open editor.
 *
 * Every target is counted first, where the suite can count, so a document
 * the user changed since the read is refused before anything is touched:
 * a target that is gone or now occurs twice fails naming the text, and is
 * never applied near it.
 *
 * Where the suite cannot count, the editor is saved after the replacements
 * and the document re-read through `read`, and every replaced text must be
 * gone. Without `read` such a suite is refused rather than trusted blind.
 *
 * @param {object} delivery The backend's `delivery: "editor"` answer.
 * @param {object} handle The suite's live handle (connector, or editor frame).
 * @param {{read?: function(): Promise<object>}} [options] `read()` resolves to the saved document's blocks.
 * @return {Promise<{applied: number, suite: string}>}
 * @throws {Error} When a target is not found exactly once, or the editor cannot take the edit.
 *
 * @spec openspec/changes/edit-through-the-open-editor/specs/edit-through-the-open-editor/spec.md#scenario-a-stale-target-fails-loudly
 */
export async function deliverEdit(delivery, handle, { read } = {}) {
	const suite = String(delivery?.suite ?? '')
	const adapter = resolveAdapter(delivery?.adapter, suite)
	const replacements = replacementsOf(delivery)

	const counts = []
	for (const { find } of replacements) {
		counts.push(await adapter.count(handle, find))
	}

	const verifiable = counts.every((count) => count !== null)
	if (verifiable === true) {
		counts.forEach((count, index) => assertFoundOnce(replacements[index].find, count))
	} else if (typeof read !== 'function') {
		throw new Error(
			`${suite !== '' ? suite : 'The open editor'} cannot report whether the text to change is still there, `
			+ 'and the document cannot be re-read to confirm it, so it was not changed.',
		)
	}

	for (const replacement of replacements) {
		await adapter.replace(handle, replacement)
	}

	if (verifiable === false) {
		await adapter.flush(handle)
		const text = textOf(await read())
		for (const { find, replace } of replacements) {
			if (replace.includes(find) === false && text.includes(find) === true) {
				throw new Error(`The text "${find}" was not changed in the open document. Read it again and retry.`)
			}
		}
	}

	return { applied: replacements.length, suite }
}

/**
 * Refuse a target the open document does not contain exactly once.
 *
 * @param {string} find The text.
 * @param {number} count Its occurrences in the open document.
 * @return {void}
 * @throws {Error} Naming the text.
 */
function assertFoundOnce(find, count) {
	if (count === 0) {
		throw new Error(`The text "${find}" is no longer in the open document, so nothing was changed. Read it again.`)
	}
	if (count > 1) {
		throw new Error(`The text "${find}" now occurs ${count} times in the open document, so nothing was changed. Read it again.`)
	}
}

/**
 * The text of a read, joined across blocks.
 *
 * @param {object|Array} read A readDocument answer, or its blocks.
 * @return {string}
 */
function textOf(read) {
	const blocks = Array.isArray(read) ? read : (Array.isArray(read?.blocks) ? read.blocks : [])
	return blocks.map((block) => String(block?.text ?? '')).join('\n')
}

/**
 * Run an automation command inside an ONLYOFFICE-family editor.
 *
 * @param {object} connector The automation connector.
 * @param {string} body The command body.
 * @return {Promise<unknown>} What the command returned.
 */
function runCommand(connector, body) {
	if (typeof connector?.callCommand !== 'function') {
		return Promise.reject(new Error('The open editor exposes no automation connector, so the document was not changed.'))
	}

	const command = new Function(body)
	return new Promise((resolve) => {
		connector.callCommand(command, (result) => resolve(result))
	})
}

/**
 * Post one message to a Collabora editor frame, in the shape its API expects.
 *
 * @param {{window: Window, origin: string}} target The editor frame.
 * @param {object} message The message.
 * @return {void}
 */
function post(target, message) {
	if (typeof target?.window?.postMessage !== 'function' || typeof target?.origin !== 'string' || target.origin === '') {
		throw new Error('The open editor frame cannot be reached, so the document was not changed.')
	}

	target.window.postMessage(JSON.stringify({ ...message, SendTime: Date.now() }), target.origin)
}

/**
 * A postMessage payload, which Collabora sends as a JSON string.
 *
 * @param {unknown} data The event data.
 * @return {object|null}
 */
function parseMessage(data) {
	if (typeof data === 'object' && data !== null) {
		return data
	}
	try {
		return JSON.parse(String(data))
	} catch {
		return null
	}
}
