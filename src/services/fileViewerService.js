/* eslint-disable no-console */
import axios from '@nextcloud/axios'
import { generateRemoteUrl, generateUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'
import { odfXmlToText } from './odfToHtml.js'

/**
 * Build the WebDAV URL for a file inside the current user's storage.
 *
 * Accepts both user-relative paths (e.g. /DocuDesk/foo.pdf) and Nextcloud's
 * internal `Node::getPath()` format (e.g. /admin/files/DocuDesk/foo.pdf).
 * The internal prefix `/<uid>/files/` is stripped so backend endpoints that
 * forward the raw node path (upload, anonymise) don't need to know about
 * the DAV layout.
 *
 * @param {string} path Absolute path — user-relative or Nextcloud-internal.
 * @return {string} Full WebDAV URL.
 */
export function buildWebdavUrl(path) {
	const user = getCurrentUser()
	if (!user) {
		throw new Error('User not authenticated')
	}
	const normalised = toUserRelativePath(path, user.uid)
	const segments = normalised
		.split('/')
		.map((s) => encodeURIComponent(s))
		.join('/')
	return generateRemoteUrl(`dav/files/${user.uid}${segments}`)
}

/**
 * Normalise a Nextcloud internal node path to the user-relative form used by
 * the WebDAV layout. `/admin/files/DocuDesk/foo.pdf` becomes `/DocuDesk/foo.pdf`;
 * already user-relative paths pass through unchanged.
 *
 * @param {string} path Raw path.
 * @param {string} uid  Current user id, used to match the internal prefix.
 * @return {string} User-relative path starting with `/`.
 */
function toUserRelativePath(path, uid) {
	if (typeof path !== 'string' || path.length === 0) {
		return '/'
	}
	const prefix = `/${uid}/files`
	if (path === prefix || path.startsWith(`${prefix}/`)) {
		return path.slice(prefix.length) || '/'
	}
	return path.startsWith('/') ? path : `/${path}`
}

/**
 * Fetch a file as an ArrayBuffer (for pdfjs / mammoth).
 *
 * @param {string} path Absolute path inside the user's storage.
 * @return {Promise<ArrayBuffer>}
 */
export async function fetchFileAsArrayBuffer(path) {
	const url = buildWebdavUrl(path)
	const response = await axios.get(url, { responseType: 'arraybuffer' })
	return response.data
}

/**
 * Fetch an arbitrary same-origin URL as an ArrayBuffer (for pdfjs).
 *
 * Used for content that isn't a plain WebDAV file — e.g. the server-rendered
 * EML preview PDF served by DocuDesk's `eml_preview#preview` endpoint.
 *
 * @param {string} url Absolute or app-relative URL returning binary data.
 * @return {Promise<ArrayBuffer>}
 */
export async function fetchUrlAsArrayBuffer(url) {
	const response = await axios.get(url, { responseType: 'arraybuffer' })
	return response.data
}

/**
 * Build the URL of the server-rendered PDF preview for an original EML file.
 *
 * @param {number} fileId Nextcloud file id of the source .eml.
 * @return {string} App-relative URL of the preview endpoint.
 */
export function emlPreviewUrl(fileId) {
	return generateUrl('/apps/docudesk/api/anonymization/eml-preview/{fileId}', {
		fileId,
	})
}

/**
 * Fetch a file as plain text.
 *
 * @param {string} path Absolute path inside the user's storage.
 * @return {Promise<string>}
 */
export async function fetchFileAsText(path) {
	const url = buildWebdavUrl(path)
	const response = await axios.get(url, { responseType: 'text' })
	return response.data
}

// Lazy module promises — pdfjs (~2MB) and mammoth are only pulled in when a
// binary document actually needs its text layer read. Mirrors the loaders in
// PdfViewer.vue / WordViewer.vue so we don't double-bundle.
let pdfjsLibPromise = null
let mammothPromise = null
let jsZipPromise = null

/**
 * Lazy-load pdfjs-dist plus its worker.
 *
 * @return {Promise<object>} pdfjsLib module.
 */
async function loadPdfjs() {
	if (!pdfjsLibPromise) {
		pdfjsLibPromise = (async () => {
			// eslint-disable-next-line import/no-unresolved
			const pdfjsLib = await import('pdfjs-dist/build/pdf.mjs')
			const workerUrl = new URL(
				'pdfjs-dist/build/pdf.worker.min.mjs',
				import.meta.url,
			).toString()
			pdfjsLib.GlobalWorkerOptions.workerSrc = workerUrl
			return pdfjsLib
		})()
	}
	return pdfjsLibPromise
}

/**
 * Lazy-load mammoth (docx → text).
 *
 * @return {Promise<object>} mammoth module.
 */
async function loadMammoth() {
	if (!mammothPromise) {
		// eslint-disable-next-line import/no-unresolved
		mammothPromise = import('mammoth/mammoth.browser.js')
	}
	return mammothPromise
}

/**
 * Lazy-load JSZip (odt is a ZIP container).
 *
 * @return {Promise<object>} JSZip module.
 */
async function loadJsZip() {
	if (!jsZipPromise) {
		jsZipPromise = import('jszip')
	}
	return jsZipPromise
}

/**
 * Whether a MIME type / file name looks like a PDF.
 *
 * @param {string} mime MIME type.
 * @param {string} name File name.
 * @return {boolean}
 */
function isPdf(mime, name) {
	return mime.includes('pdf') || /\.pdf$/i.test(name)
}

/**
 * Whether a MIME type / file name looks like a Word .docx document.
 *
 * @param {string} mime MIME type.
 * @param {string} name File name.
 * @return {boolean}
 */
function isWord(mime, name) {
	return (
		mime.includes('wordprocessingml')
		|| mime.includes('msword')
		|| /\.docx?$/i.test(name)
	)
}

/**
 * Whether a MIME type / file name looks like an OpenDocument Text (.odt).
 *
 * @param {string} mime MIME type.
 * @param {string} name File name.
 * @return {boolean}
 */
function isOdt(mime, name) {
	return mime.includes('opendocument.text') || /\.odt$/i.test(name)
}

/**
 * Extract the plain-text layer of a document regardless of format. Used to
 * scan an anonymised file for `[<TYPE>: <entity_id>]` placeholders.
 *
 * - text/* (and json/xml/markdown): fetched verbatim over WebDAV.
 * - PDF: every page's text content concatenated via pdfjs.
 * - Word (.docx): raw text via mammoth.
 * - ODT: content.xml + styles.xml text via JSZip (the ZIP's transport bytes
 *   would otherwise be fetched as garbage text).
 * - Anything else: best-effort WebDAV text fetch.
 *
 * @param {object} file File descriptor.
 * @param {string} file.path     Absolute path inside the user's storage.
 * @param {string} [file.mimeType] MIME type (used to pick the extractor).
 * @param {string} [file.fileName] File name (fallback for extension sniffing).
 * @return {Promise<string>} The document's plain text (may be empty).
 */
export async function extractDocumentText(file) {
	const mime = (file.mimeType || '').toLowerCase()
	const name = file.fileName || ''

	if (isPdf(mime, name)) {
		const [pdfjsLib, data] = await Promise.all([
			loadPdfjs(),
			fetchFileAsArrayBuffer(file.path),
		])
		const pdf = await pdfjsLib.getDocument({ data }).promise
		const pages = []
		for (let pageNumber = 1; pageNumber <= pdf.numPages; pageNumber++) {
			const page = await pdf.getPage(pageNumber)
			const textContent = await page.getTextContent()
			pages.push(textContent.items.map((item) => item.str).join(' '))
		}
		return pages.join('\n')
	}

	if (isWord(mime, name)) {
		const [mammothModule, arrayBuffer] = await Promise.all([
			loadMammoth(),
			fetchFileAsArrayBuffer(file.path),
		])
		const mammoth = mammothModule.default || mammothModule
		const result = await mammoth.extractRawText({ arrayBuffer })
		return result.value || ''
	}

	if (isOdt(mime, name)) {
		const [jsZipModule, arrayBuffer] = await Promise.all([
			loadJsZip(),
			fetchFileAsArrayBuffer(file.path),
		])
		const JSZip = jsZipModule.default || jsZipModule
		const zip = await JSZip.loadAsync(arrayBuffer)
		const parts = []
		for (const part of ['content.xml', 'styles.xml']) {
			const entry = zip.file(part)
			if (entry) {
				parts.push(odfXmlToText(await entry.async('string')))
			}
		}
		return parts.filter((p) => p !== '').join('\n')
	}

	// text/plain, markdown, json, xml, or unknown — fetch verbatim.
	return fetchFileAsText(file.path)
}
