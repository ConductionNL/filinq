// @vitest-environment jsdom
/**
 * The correspondence store: recipient parsing, batch mode, the batch
 * request and its job polling, the JSON answer of a single letter, the
 * download name, and reset.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec exclude unit tests of an existing store's request and state handling
 */

import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'

const axios = vi.hoisted(() => ({ get: vi.fn(), post: vi.fn() }))
vi.mock('@nextcloud/axios', () => ({ default: axios }))

const { useCorrespondenceStore } = await import('../../src/store/modules/correspondence.js')

beforeEach(() => {
	setActivePinia(createPinia())
	axios.get.mockReset()
	axios.post.mockReset()
})

describe('correspondence store', () => {
	it('reads one recipient per non-empty line, and is in batch mode only without data refs', () => {
		const store = useCorrespondenceStore()
		store.recipientIdsText = ' r1 \n\n r2\n'

		expect(store.recipientIds).toEqual(['r1', 'r2'])
		expect(store.isBatchMode).toBe(true)

		store.dataRefs = [{ id: 'd1' }]
		expect(store.isBatchMode).toBe(false)

		store.dataRefs = []
		store.recipientIdsText = '   '
		expect(store.isBatchMode).toBe(false)
	})

	it('queues a batch with the recipients and options, and starts its job status', async () => {
		axios.post.mockResolvedValue({ data: { jobId: 'job 1', totalRecipients: 2 } })
		const store = useCorrespondenceStore()
		store.templateId = 'tpl-1'
		store.recipientIdsText = 'r1\nr2'
		store.huisstijlId = 'hs-1'

		await store.generateBatch('filinq', 'contact')

		expect(axios.post).toHaveBeenCalledWith('/index.php/apps/filinq/api/correspondence/generate/batch', {
			templateId: 'tpl-1',
			recipientIds: ['r1', 'r2'],
			options: { register: 'filinq', schema: 'contact', format: 'pdf', huisstijlId: 'hs-1', caseReference: undefined },
		})
		expect(store.jobId).toBe('job 1')
		expect(store.jobStatus).toEqual({ status: 'queued', total: 2, completed: 0, errors: 0 })
		expect(store.loading).toBe(false)
	})

	it('takes a finished batch answer as the job status when no job was queued', async () => {
		axios.post.mockResolvedValue({ data: { status: 'completed', total: 1, completed: 1, errors: 0 } })
		const store = useCorrespondenceStore()

		await store.generateBatch('filinq', 'contact')

		expect(store.jobId).toBeNull()
		expect(store.jobStatus).toEqual({ status: 'completed', total: 1, completed: 1, errors: 0 })
	})

	it('keeps a refused batch as the server\'s error, or the transport message', async () => {
		axios.post.mockRejectedValueOnce(Object.assign(new Error('Request failed'), { response: { data: { error: 'Template not found' } } }))
		const store = useCorrespondenceStore()

		await store.generateBatch('filinq', 'contact')
		expect(store.error).toBe('Template not found')
		expect(store.loading).toBe(false)

		axios.post.mockRejectedValueOnce(new Error('Network Error'))
		await store.generateBatch('filinq', 'contact')
		expect(store.error).toBe('Network Error')
	})

	it('polls the job only when there is one, and keeps a failed poll as the error', async () => {
		const store = useCorrespondenceStore()
		await store.pollJobStatus()
		expect(axios.get).not.toHaveBeenCalled()

		store.jobId = 'job 1'
		axios.get.mockResolvedValueOnce({ data: { status: 'running', completed: 1 } })
		await store.pollJobStatus()
		expect(axios.get).toHaveBeenCalledWith('/index.php/apps/filinq/api/correspondence/jobs/job%201')
		expect(store.jobStatus).toEqual({ status: 'running', completed: 1 })

		axios.get.mockRejectedValueOnce(new Error('timeout'))
		await store.pollJobStatus()
		expect(store.error).toBe('timeout')
		expect(store.jobStatus).toEqual({ status: 'running', completed: 1 })
	})

	it('asks a JSON preview for a non-file format and keeps its warnings', async () => {
		axios.post.mockResolvedValue({ data: { warnings: ['Missing field: naam'] } })
		const store = useCorrespondenceStore()
		store.format = 'html'
		store.caseReference = 'Z-12'

		await store.generate('brief.html')

		expect(axios.post).toHaveBeenCalledWith(
			'/index.php/apps/filinq/api/correspondence/generate',
			{ templateId: '', dataRefs: [], options: { format: 'html', huisstijlId: undefined, caseReference: 'Z-12' }, filename: 'brief.html' },
			{ responseType: 'json' },
		)
		expect(store.warnings).toEqual(['Missing field: naam'])

		axios.post.mockResolvedValue({ data: {} })
		await store.generate('brief.html')
		expect(store.warnings).toEqual([])
	})

	it('downloads a .docx under a name with the right extension', async () => {
		axios.post.mockResolvedValue({ data: new Blob(['DOCX']) })
		URL.createObjectURL = vi.fn(() => 'blob:letter')
		URL.revokeObjectURL = vi.fn()
		const clicked = []
		vi.spyOn(HTMLAnchorElement.prototype, 'click').mockImplementation(function() {
			clicked.push([this.download, this.getAttribute('href')])
		})
		const store = useCorrespondenceStore()
		store.format = 'docx'

		await store.generate('brief.pdf')

		expect(axios.post.mock.calls[0][2]).toEqual({ responseType: 'blob' })
		expect(clicked).toEqual([['brief.docx', 'blob:letter']])
		expect(URL.revokeObjectURL).toHaveBeenCalledWith('blob:letter')
		expect(document.querySelector('a[download]')).toBeNull()
	})

	it('resets every field', () => {
		const store = useCorrespondenceStore()
		Object.assign(store, { templateId: 't', dataRefs: [1], format: 'docx', huisstijlId: 'h', caseReference: 'c', recipientIdsText: 'r', error: 'e', jobId: 'j', jobStatus: {}, warnings: ['w'] })

		store.reset()

		expect(store.$state).toMatchObject({ templateId: '', dataRefs: [], format: 'pdf', huisstijlId: null, caseReference: '', recipientIdsText: '', error: null, jobId: null, jobStatus: null, warnings: [] })
	})
})
