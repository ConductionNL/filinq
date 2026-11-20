/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Editor delivery (openspec/changes/edit-through-the-open-editor): the page
 * side that applies an agent edit through an OPEN office editor. The editors'
 * client APIs are the boundary, so they are replaced by recording fakes.
 */

import { readFileSync } from 'node:fs'
import path from 'node:path'
import { describe, expect, it, vi } from 'vitest'
import {
	deliverEdit,
	EDITOR_ADAPTERS,
	flushOpenEditor,
	flushThenRead,
	replacementsOf,
	resolveAdapter,
} from '../../src/services/editorDelivery.js'

/**
 * A fake ONLYOFFICE-family connector over a document text.
 *
 * It runs each command against a tiny stand-in for the Document Builder
 * `Api`, so a command that names the wrong method or the wrong text fails.
 *
 * @param {string} text The open document's text.
 * @return {object}
 */
function fakeConnector(text) {
	const state = { text, saved: 0, commands: [] }
	const Api = {
		Save: () => { state.saved++ },
		GetDocument: () => ({
			Search: (needle, matchCase) => {
				expect(matchCase).toBe(true)
				return state.text.split(needle).length > 1 ? new Array(state.text.split(needle).length - 1).fill({}) : []
			},
			SearchAndReplace: ({ searchString, replaceString, matchCase }) => {
				expect(matchCase).toBe(true)
				state.text = state.text.split(searchString).join(replaceString)
			},
		}),
	}
	return {
		state,
		callCommand(command, callback) {
			state.commands.push(String(command))
			// The editor receives the command's source and a JSON copy of Asc.scope,
			// and runs the source with only its own Api and that scope in reach.
			const scope = JSON.parse(JSON.stringify(globalThis.Asc?.scope ?? {}))
			const run = new Function('Api', 'Asc', `return (${String(command)})()`)
			callback(run(Api, { scope }))
		},
	}
}

/**
 * A fake Collabora editor frame that answers a save.
 *
 * @param {{success?: boolean, answer?: boolean}} [options] How it answers.
 * @return {object}
 */
function fakeFrame({ success = true, answer = true } = {}) {
	const listeners = new Set()
	const events = {
		addEventListener: (type, fn) => listeners.add(fn),
		removeEventListener: (type, fn) => listeners.delete(fn),
	}
	const sent = []
	const frame = {
		postMessage: (data, origin) => {
			sent.push({ message: JSON.parse(data), origin })
			if (JSON.parse(data).MessageId === 'Action_Save' && answer === true) {
				const reply = { origin: 'https://office.example', data: JSON.stringify({ MessageId: 'Action_Save_Resp', Values: { success } }) }
				// A frame of another origin answering must be ignored.
				listeners.forEach((fn) => fn({ origin: 'https://evil.example', data: reply.data }))
				listeners.forEach((fn) => fn(reply))
			}
		},
	}
	return { sent, listeners, target: { window: frame, origin: 'https://office.example', events, timeoutMs: 20 } }
}

const delivery = (adapter, replacements, suite = 'Some Suite') => ({ delivery: 'editor', adapter, suite, replacements })

describe('resolveAdapter', () => {
	it('returns the declared adapters', () => {
		expect(resolveAdapter('connector')).toBe(EDITOR_ADAPTERS.connector)
		expect(resolveAdapter('postMessage')).toBe(EDITOR_ADAPTERS.postMessage)
	})

	it('refuses an undeclared adapter, naming the suite and the direct path', () => {
		expect(() => resolveAdapter('telepathy', 'Acme Office')).toThrow(
			'The document is open in Acme Office, which offers no way to apply an edit through the editor, '
			+ 'so it was not changed. Close the document and the edit can be made directly.',
		)
		expect(() => resolveAdapter(null)).toThrow('open in this editor')
		expect(() => resolveAdapter('toString')).toThrow('open in toString')
	})

	it('declares exactly the adapters the backend registry names', () => {
		const registry = JSON.parse(readFileSync(path.resolve(__dirname, '../../lib/Settings/editor-delivery.json'), 'utf8'))
		const declared = [...new Set(Object.values(registry.editors).map((entry) => entry.adapter))].sort()
		expect(Object.keys(EDITOR_ADAPTERS).sort()).toEqual(declared)
	})
})

describe('flushThenRead', () => {
	it('reads only after the editor saved', async () => {
		const connector = fakeConnector('x')
		const read = vi.fn(async () => ({ savedBeforeRead: connector.state.saved }))
		await expect(flushThenRead({ adapter: 'connector' }, connector, read)).resolves.toEqual({ savedBeforeRead: 1 })
	})

	it('does not read when the save failed', async () => {
		const read = vi.fn()
		await expect(flushThenRead({ adapter: 'postMessage' }, fakeFrame({ success: false }).target, read)).rejects.toThrow('refused to save')
		expect(read).not.toHaveBeenCalled()
	})
})

describe('replacementsOf', () => {
	it('keeps text-to-match replacements', () => {
		expect(replacementsOf(delivery('connector', [{ find: 'Zaanstad', replace: 'Haarlem' }]))).toEqual([
			{ find: 'Zaanstad', replace: 'Haarlem' },
		])
	})

	it('refuses an answer that is not an editor delivery', () => {
		expect(() => replacementsOf({ delivery: 'file', replacements: [] })).toThrow('not meant for the open editor')
		expect(() => replacementsOf(delivery('connector', []))).toThrow('carries no replacements')
	})

	it('refuses anything positional or without a target', () => {
		expect(() => replacementsOf(delivery('connector', [{ find: 'a', replace: 'b', offset: 4 }]))).toThrow(
			'must name the text to find and its replacement, and nothing else',
		)
		expect(() => replacementsOf(delivery('connector', [{ find: 1, replace: 'b' }]))).toThrow('and nothing else')
		expect(() => replacementsOf(delivery('connector', [{ find: '  ', replace: 'b' }]))).toThrow('has no text to find')
	})
})

describe('connector adapter (ONLYOFFICE, Euro-Office)', () => {
	it('flushes through Api.Save', async () => {
		const connector = fakeConnector('x')
		await flushOpenEditor({ adapter: 'connector', suite: 'Euro-Office' }, connector)
		expect(connector.state.saved).toBe(1)
	})

	it('applies a replacement found exactly once', async () => {
		const connector = fakeConnector('Gemeente Zaanstad besluit.')
		const result = await deliverEdit(delivery('connector', [{ find: 'Zaanstad', replace: 'Haarlem' }], 'Euro-Office'), connector)
		expect(result).toEqual({ applied: 1, suite: 'Euro-Office' })
		expect(connector.state.text).toBe('Gemeente Haarlem besluit.')
	})

	it('survives typing elsewhere, because it matches text not positions', async () => {
		const connector = fakeConnector('Gemeente Zaanstad besluit.')
		connector.state.text = 'Nieuwe zin. ' + connector.state.text
		await deliverEdit(delivery('connector', [{ find: 'Zaanstad', replace: 'Haarlem' }]), connector)
		expect(connector.state.text).toBe('Nieuwe zin. Gemeente Haarlem besluit.')
	})

	it('fails loudly naming a target that is gone, and changes nothing', async () => {
		const connector = fakeConnector('Gemeente Haarlem besluit. Tweede zin.')
		await expect(
			deliverEdit(delivery('connector', [
				{ find: 'Tweede zin.', replace: 'Andere zin.' },
				{ find: 'Zaanstad', replace: 'Haarlem' },
			]), connector),
		).rejects.toThrow('The text "Zaanstad" is no longer in the open document, so nothing was changed. Read it again.')
		expect(connector.state.text).toBe('Gemeente Haarlem besluit. Tweede zin.')
	})

	it('refuses a target that now occurs twice', async () => {
		const connector = fakeConnector('Zaanstad en Zaanstad')
		await expect(deliverEdit(delivery('connector', [{ find: 'Zaanstad', replace: 'X' }]), connector)).rejects.toThrow(
			'The text "Zaanstad" now occurs 2 times in the open document, so nothing was changed.',
		)
		expect(connector.state.text).toBe('Zaanstad en Zaanstad')
	})

	it('passes texts with quotes intact', async () => {
		const connector = fakeConnector('Hij zei "ja"\'s avonds.')
		await deliverEdit(delivery('connector', [{ find: '"ja"\'s', replace: '"nee"\'s' }]), connector)
		expect(connector.state.text).toBe('Hij zei "nee"\'s avonds.')
	})

	it('sends fixed commands and passes the texts only through Asc.scope', async () => {
		const connector = fakeConnector('Gemeente Zaanstad besluit.')
		await deliverEdit(delivery('connector', [{ find: 'Zaanstad', replace: 'Haarlem' }]), connector)
		expect(connector.state.commands.length).toBeGreaterThan(0)
		for (const source of connector.state.commands) {
			expect(source).not.toContain('Zaanstad')
			expect(source).not.toContain('Haarlem')
		}

		const other = fakeConnector('Gemeente Utrecht besluit.')
		await deliverEdit(delivery('connector', [{ find: 'Utrecht', replace: 'Breda' }]), other)
		expect(other.state.commands).toEqual(connector.state.commands)
	})

	it('treats text that looks like code as text', async () => {
		const code = '"); Api.Save(); ("'
		const connector = fakeConnector(`Voor ${code} na.`)
		await deliverEdit(delivery('connector', [{ find: code, replace: 'X' }]), connector)
		expect(connector.state.text).toBe('Voor X na.')
		expect(connector.state.saved).toBe(0)
	})

	it('refuses a handle that is not a connector', async () => {
		await expect(deliverEdit(delivery('connector', [{ find: 'a', replace: 'b' }]), {})).rejects.toThrow(
			'exposes no automation connector',
		)
	})
})

describe('postMessage adapter (Collabora Online)', () => {
	it('flushes with Action_Save and waits for the answer from the editor origin', async () => {
		const frame = fakeFrame()
		await flushOpenEditor({ adapter: 'postMessage', suite: 'Collabora Online' }, frame.target)
		expect(frame.sent).toHaveLength(1)
		expect(frame.sent[0].origin).toBe('https://office.example')
		expect(frame.sent[0].message).toMatchObject({
			MessageId: 'Action_Save',
			Values: { DontTerminateEdit: true, DontSaveIfUnmodified: true, Notify: true },
		})
		expect(frame.listeners.size).toBe(0)
	})

	it('rejects a refused save and a silent editor', async () => {
		await expect(flushOpenEditor({ adapter: 'postMessage' }, fakeFrame({ success: false }).target)).rejects.toThrow(
			'refused to save the document',
		)
		const silent = fakeFrame({ answer: false })
		await expect(flushOpenEditor({ adapter: 'postMessage' }, silent.target)).rejects.toThrow('did not confirm it saved')
		expect(silent.listeners.size).toBe(0)
	})

	it('refuses an unreachable frame without leaving a listener behind', async () => {
		const frame = fakeFrame()
		await expect(flushOpenEditor({ adapter: 'postMessage' }, { ...frame.target, origin: '' })).rejects.toThrow('cannot be reached')
		expect(frame.listeners.size).toBe(0)
	})

	it('applies by search-and-replace, then confirms against the saved document', async () => {
		const frame = fakeFrame()
		const read = vi.fn().mockResolvedValue({ blocks: [{ text: 'Gemeente Haarlem besluit.' }] })
		const result = await deliverEdit(delivery('postMessage', [{ find: 'Zaanstad', replace: 'Haarlem' }], 'Collabora Online'), frame.target, { read })
		expect(result).toEqual({ applied: 1, suite: 'Collabora Online' })
		expect(frame.sent.map((entry) => entry.message.MessageId)).toEqual(['Send_UNO_Command', 'Action_Save'])
		expect(frame.sent[0].message.Values).toEqual({
			Command: '.uno:ExecuteSearch',
			Args: {
				'SearchItem.SearchString': { type: 'string', value: 'Zaanstad' },
				'SearchItem.ReplaceString': { type: 'string', value: 'Haarlem' },
				'SearchItem.MatchCase': { type: 'boolean', value: true },
				'SearchItem.Command': { type: 'long', value: 3 },
			},
		})
		expect(read).toHaveBeenCalledTimes(1)
	})

	it('fails loudly when the saved document still holds the target', async () => {
		const read = vi.fn().mockResolvedValue([{ text: 'Gemeente Zaanstad besluit.' }])
		await expect(
			deliverEdit(delivery('postMessage', [{ find: 'Zaanstad', replace: 'Haarlem' }]), fakeFrame().target, { read }),
		).rejects.toThrow('The text "Zaanstad" was not changed in the open document. Read it again and retry.')
	})

	it('refuses to apply blind when the document cannot be re-read', async () => {
		const frame = fakeFrame()
		await expect(
			deliverEdit(delivery('postMessage', [{ find: 'Zaanstad', replace: 'Haarlem' }], 'Collabora Online'), frame.target),
		).rejects.toThrow('Collabora Online cannot report whether the text to change is still there')
		expect(frame.sent).toHaveLength(0)
	})
})

describe('ADR-087 §5 boundary', () => {
	it('the edit plan, which decides WHAT changes, names no suite', () => {
		const plan = readFileSync(path.resolve(__dirname, '../../lib/Service/Editing/EditorEditPlan.php'), 'utf8').toLowerCase()
		for (const suite of ['collabora', 'onlyoffice', 'euro-office', 'eurooffice', 'richdocuments', 'postmessage', 'connector']) {
			expect(plan).not.toContain(suite)
		}
	})
})
