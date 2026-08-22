// SPDX-License-Identifier: EUPL-1.2
// Copyright (C) 2026 Conduction B.V.
//
// Icon registry for filinq (ADR-077 semantic icon vocabulary).
//
// CnAppNav, CnIcon, CnIndexPage / CnDetailPage headers and empty states resolve
// an `icon` by PascalCase name through the registry that `registerIcons()`
// populates. A name that is not registered renders NO icon in the navigation —
// not a fallback glyph — so this file must cover every `icon` the manifests and
// register files name. Keep it in sync when you add a menu entry.
//
// Generated from the app's own manifests; every name is verified to exist in
// vue-material-design-icons.

import AccountCheck from 'vue-material-design-icons/AccountCheck.vue'
import AccountEdit from 'vue-material-design-icons/AccountEdit.vue'
import BookAlphabet from 'vue-material-design-icons/BookAlphabet.vue'
import BookOpenVariant from 'vue-material-design-icons/BookOpenVariant.vue'
import BookOpenVariantOutline from 'vue-material-design-icons/BookOpenVariantOutline.vue'
import Cash from 'vue-material-design-icons/Cash.vue'
import ClipboardCheckOutline from 'vue-material-design-icons/ClipboardCheckOutline.vue'
import Domain from 'vue-material-design-icons/Domain.vue'
import EmailMultipleOutline from 'vue-material-design-icons/EmailMultipleOutline.vue'
import EmailOutline from 'vue-material-design-icons/EmailOutline.vue'
import EyeOffOutline from 'vue-material-design-icons/EyeOffOutline.vue'
import FileCheckOutline from 'vue-material-design-icons/FileCheckOutline.vue'
import FileDocument from 'vue-material-design-icons/FileDocument.vue'
import FileDocumentCheck from 'vue-material-design-icons/FileDocumentCheck.vue'
import FileDocumentMultipleOutline from 'vue-material-design-icons/FileDocumentMultipleOutline.vue'
import FileLinkOutline from 'vue-material-design-icons/FileLinkOutline.vue'
import FileReplaceOutline from 'vue-material-design-icons/FileReplaceOutline.vue'
import FileSign from 'vue-material-design-icons/FileSign.vue'
import FolderAccount from 'vue-material-design-icons/FolderAccount.vue'
import FolderOutline from 'vue-material-design-icons/FolderOutline.vue'
import FormatListBulleted from 'vue-material-design-icons/FormatListBulleted.vue'
import Gavel from 'vue-material-design-icons/Gavel.vue'
import History from 'vue-material-design-icons/History.vue'
import MapMarkerPath from 'vue-material-design-icons/MapMarkerPath.vue'
import Palette from 'vue-material-design-icons/Palette.vue'
import Plus from 'vue-material-design-icons/Plus.vue'
import ShieldCheck from 'vue-material-design-icons/ShieldCheck.vue'
import ShieldLockOutline from 'vue-material-design-icons/ShieldLockOutline.vue'
import SignatureFreehand from 'vue-material-design-icons/SignatureFreehand.vue'
import TagMultiple from 'vue-material-design-icons/TagMultiple.vue'
import ViewDashboardOutline from 'vue-material-design-icons/ViewDashboardOutline.vue'

export default {
	AccountCheck,
	AccountEdit,
	BookAlphabet,
	// Used by the `product` schema's rate card in the register. Unregistered,
	// it rendered as NOTHING — not a fallback glyph, just an empty cell where
	// the icon should be (ADR-077 rule 3).
	Cash,
	BookOpenVariant,
	BookOpenVariantOutline,
	ClipboardCheckOutline,
	Domain,
	EmailMultipleOutline,
	EmailOutline,
	EyeOffOutline,
	FileCheckOutline,
	FileDocument,
	FileDocumentCheck,
	FileDocumentMultipleOutline,
	FileLinkOutline,
	FileReplaceOutline,
	FileSign,
	FolderAccount,
	FolderOutline,
	FormatListBulleted,
	Gavel,
	History,
	MapMarkerPath,
	Palette,
	Plus,
	ShieldCheck,
	ShieldLockOutline,
	SignatureFreehand,
	TagMultiple,
	ViewDashboardOutline,
}
