/* eslint-disable no-undef */
/**
 * Webpack runtime bootstrap — MUST be the first import of every entry point.
 *
 * Two globals have to be set before any other module evaluates:
 *
 * 1. `__webpack_public_path__` — webpack bakes a default of `/apps/docudesk/js/`,
 *    but DocuDesk is installed under a non-default apps path (`apps-extra`), so
 *    the real webroot is `/apps-extra/docudesk/js/`. `generateFilePath` resolves
 *    the correct path at runtime. This must run BEFORE the entry's CSS imports:
 *    `asset/resource` URLs (our bundled fonts) are computed as
 *    `__webpack_require__.p + '<hash>.woff2'` at the moment the importing CSS
 *    module evaluates. Setting the path inside the entry body — after the
 *    `import './assets/fonts.css'` line — is too late and the fonts 404. A
 *    dedicated first-imported module is the only ordering that runs early
 *    enough, because ES imports evaluate before the entry body's statements.
 *
 * 2. `__webpack_nonce__` — CSP nonce for any dynamically injected chunk
 *    (lazy-loaded pdfjs / mammoth), otherwise Nextcloud's strict CSP blocks it.
 */
import { generateFilePath } from '@nextcloud/router'

__webpack_nonce__ = btoa(OC.requestToken)
__webpack_public_path__ = generateFilePath('docudesk', '', 'js/')
