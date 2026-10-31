import { getCurrentUser } from '@nextcloud/auth'
import axios from '@nextcloud/axios'
import { generateRemoteUrl, generateUrl } from '@nextcloud/router'
import { odfXmlToText } from './odfToHtml.js'
import { loadPdfjs } from './pdfjsLoader.js'

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
 * EML preview PDF served by Filinq's `eml_preview#preview` endpoint.
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
 * @spec openspec/changes/archive/2026-10-09-eml-viewer-preview/specs/eml-preview/spec.md#requirement-the-file-viewer-must-render-eml-via-the-server-rendered-preview
 */
export function emlPreviewUrl(fileId) {
	return generateUrl('/apps/filinq/api/anonymization/eml-preview/{fileId}', {
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

/**
 * Fetch an arbitrary same-origin URL as plain text.
 *
 * @param {string} url Absolute or app-relative URL returning text.
 * @return {Promise<string>}
 * @spec openspec/changes/anonymization-review-workbench/specs/anonymization-review-workbench/spec.md#requirement-the-split-view-is-mountable-by-another-app-req-ddarw-014
 */
export async function fetchUrlAsText(url) {
	const response = await axios.get(url, { responseType: 'text' })
	return response.data
}

// Lazy module promises: mammoth and JSZip are only pulled in when a binary
// document actually needs its text layer read.
let mammothPromise = null
let jsZipPromise = null

/**
 * Lazy-load mammoth (docx → text).
 *
 * @return {Promise<object>} mammoth module.
 */
async function loadMammoth() {
	if (!mammothPromise) {
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
 * Every page's text content of a PDF, read with pdfjs.
 *
 * @param {string} path Absolute path inside the user's storage.
 * @return {Promise<string>}
 */
async function pdfText(path) {
	const [pdfjsLib, data] = await Promise.all([
		loadPdfjs(),
		fetchFileAsArrayBuffer(path),
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

/**
 * The raw text of a Word document, read with mammoth.
 *
 * @param {string} path Absolute path inside the user's storage.
 * @return {Promise<string>}
 */
async function wordText(path) {
	const [mammothModule, arrayBuffer] = await Promise.all([
		loadMammoth(),
		fetchFileAsArrayBuffer(path),
	])
	const mammoth = mammothModule.default || mammothModule
	const result = await mammoth.extractRawText({ arrayBuffer })
	return result.value || ''
}

/**
 * The text of an ODT's content.xml and styles.xml, read with JSZip.
 *
 * @param {string} path Absolute path inside the user's storage.
 * @return {Promise<string>}
 */
async function odtText(path) {
	const [jsZipModule, arrayBuffer] = await Promise.all([
		loadJsZip(),
		fetchFileAsArrayBuffer(path),
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

/**
 * The text extractors in the order they are tried.
 *
 * @type {Array<{matches: function(string, string): boolean, extract: function(string): Promise<string>}>}
 */
const TEXT_EXTRACTORS = [
	{ matches: isPdf, extract: pdfText },
	{ matches: isWord, extract: wordText },
	{ matches: isOdt, extract: odtText },
]

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
	const extractor = TEXT_EXTRACTORS.find((x) => x.matches(mime, name))
	// text/plain, markdown, json, xml, or unknown: fetch verbatim.
	return extractor ? extractor.extract(file.path) : fetchFileAsText(file.path)
}
