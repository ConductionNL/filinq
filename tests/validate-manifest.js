#!/usr/bin/env node
// SPDX-License-Identifier: EUPL-1.2
// Copyright (C) 2026 Conduction B.V.
//
// validate-manifest.js — schema-validates src/manifest.json against the
// @conduction/nextcloud-vue v2 app-manifest schema using Ajv.
//
// Usage:
//   node tests/validate-manifest.js
//
// Exit codes:
//   0 — manifest validates against the schema with zero errors
//   1 — manifest fails validation (or schema/manifest cannot be loaded)
//
// src/manifest.json declares the v2 schema ($schema → app-manifest-v2.schema.json),
// so we validate against v2. The canonical v2 schema is vendored under
// tests/schemas/ so the gate is self-contained (CI does not depend on a fresh
// node_modules copy, and the published @conduction/nextcloud-vue v2 schema can
// lag the canonical hydra one — e.g. the metric `cacheTtl` property).
//
// Schema lookup order (first hit wins):
//   1. Env var APP_MANIFEST_SCHEMA — explicit absolute path to a schema JSON
//   2. tests/schemas/app-manifest-v2.schema.json (vendored canonical v2)
//   3. node_modules/@conduction/nextcloud-vue/src/schemas/app-manifest-v2.schema.json
//   4. ../nextcloud-vue/src/schemas/app-manifest-v2.schema.json (sibling worktree)

'use strict'

const fs = require('fs')
const path = require('path')

const REPO_ROOT = path.resolve(__dirname, '..')

const MANIFEST_PATH = path.join(REPO_ROOT, 'src', 'manifest.json')

const SCHEMA_CANDIDATES = [
	process.env.APP_MANIFEST_SCHEMA,
	path.join(REPO_ROOT, 'tests', 'schemas', 'app-manifest-v2.schema.json'),
	path.join(REPO_ROOT, 'node_modules', '@conduction', 'nextcloud-vue', 'src', 'schemas', 'app-manifest-v2.schema.json'),
	path.join(REPO_ROOT, '..', 'nextcloud-vue', 'src', 'schemas', 'app-manifest-v2.schema.json'),
].filter(Boolean)

function findSchemaPath() {
	for (const candidate of SCHEMA_CANDIDATES) {
		try {
			if (fs.existsSync(candidate) && fs.statSync(candidate).isFile()) {
				return candidate
			}
		} catch (_) {
			// continue to next candidate
		}
	}
	return null
}

function loadJson(file) {
	const raw = fs.readFileSync(file, 'utf8')
	return JSON.parse(raw)
}

function loadAjv() {
	// The canonical schema uses JSON Schema draft 2020-12 (`$schema`:
	// "https://json-schema.org/draft/2020-12/schema"). Standard Ajv (v7+)
	// does not auto-load the 2020 meta-schema; we need the `ajv/dist/2020`
	// entry point. Prefer Ajv 8 (`ajv/dist/2020`) when available; otherwise
	// fall back to whichever ajv resolves first.
	let Ajv2020 = null
	let addFormats = null
	const ajvCandidates = [
		'ajv/dist/2020',
		path.join(REPO_ROOT, 'node_modules', 'ajv-formats', 'node_modules', 'ajv', 'dist', '2020.js'),
		'ajv',
	]
	for (const candidate of ajvCandidates) {
		try {
			const mod = require(candidate)
			Ajv2020 = mod.default || mod
			break
		} catch (_) {
			// next candidate
		}
	}
	if (!Ajv2020) {
		console.error('[validate-manifest] Ajv not installed in node_modules.')
		console.error('[validate-manifest] Install with: npm i -D ajv ajv-formats')
		console.error('[validate-manifest] Falling back to a structural lint pass.')
		return { Ajv: null, addFormats: null }
	}
	try {
		const mod = require('ajv-formats')
		addFormats = mod.default || mod
	} catch (_) {
		addFormats = null
	}
	return { Ajv: Ajv2020, addFormats }
}

function structuralLint(manifest) {
	const errors = []
	if (!manifest.version || typeof manifest.version !== 'string') {
		errors.push('top-level: version (string) is required')
	}
	if (!Array.isArray(manifest.menu)) errors.push('top-level: menu (array) is required')
	if (!Array.isArray(manifest.pages)) errors.push('top-level: pages (array) is required')
	const allowedTypes = new Set(['index', 'detail', 'dashboard', 'logs', 'settings', 'chat', 'files', 'custom'])
	const seenIds = new Set()
	for (let i = 0; i < (manifest.pages || []).length; i++) {
		const page = manifest.pages[i]
		if (!page || typeof page !== 'object') {
			errors.push(`pages[${i}]: must be an object`)
			continue
		}
		for (const required of ['id', 'route', 'type', 'title']) {
			if (!page[required] || typeof page[required] !== 'string') {
				errors.push(`pages[${i}]: missing required string field "${required}"`)
			}
		}
		if (page.type && !allowedTypes.has(page.type)) {
			errors.push(`pages[${i}].type: "${page.type}" not in v1.2 enum`)
		}
		if (page.id) {
			if (seenIds.has(page.id)) errors.push(`pages[${i}].id: duplicate "${page.id}"`)
			seenIds.add(page.id)
		}
		if (page.type === 'custom' && !page.component) {
			errors.push(`pages[${i}]: type=custom requires component field`)
		}
	}
	return errors
}

function main() {
	if (!fs.existsSync(MANIFEST_PATH)) {
		console.error(`[validate-manifest] manifest not found: ${MANIFEST_PATH}`)
		process.exit(1)
	}

	const manifest = loadJson(MANIFEST_PATH)
	console.log(`[validate-manifest] manifest: ${MANIFEST_PATH}`)
	console.log(`[validate-manifest] manifest.version: ${manifest.version}`)
	console.log(`[validate-manifest] pages: ${(manifest.pages || []).length}`)

	const schemaPath = findSchemaPath()
	if (!schemaPath) {
		console.warn('[validate-manifest] no schema candidate resolved; falling back to structural lint.')
		const errors = structuralLint(manifest)
		if (errors.length === 0) {
			console.log('[validate-manifest] structural lint: PASS (0 issues)')
			process.exit(0)
		}
		console.error('[validate-manifest] structural lint: FAIL')
		for (const err of errors) console.error(`  - ${err}`)
		process.exit(1)
	}
	console.log(`[validate-manifest] schema: ${schemaPath}`)
	const schema = loadJson(schemaPath)
	console.log(`[validate-manifest] schema.version: ${schema.version || '(unset)'}`)

	const { Ajv, addFormats } = loadAjv()
	if (!Ajv) {
		const errors = structuralLint(manifest)
		if (errors.length === 0) {
			console.log('[validate-manifest] structural lint (no Ajv): PASS (0 issues)')
			process.exit(0)
		}
		console.error('[validate-manifest] structural lint (no Ajv): FAIL')
		for (const err of errors) console.error(`  - ${err}`)
		process.exit(1)
	}

	const ajv = new Ajv({ allErrors: true, strict: false })
	if (addFormats) {
		try {
			addFormats(ajv)
		} catch (err) {
			console.warn(`[validate-manifest] ajv-formats failed to attach (${err.message}); continuing without format validation`)
		}
	}
	const validate = ajv.compile(schema)
	const ok = validate(manifest)
	if (ok) {
		console.log('[validate-manifest] Ajv validation: PASS (0 errors)')
		process.exit(0)
	}
	console.error('[validate-manifest] Ajv validation: FAIL')
	for (const err of validate.errors || []) {
		console.error(`  - ${err.instancePath || '(root)'} ${err.message} (keyword=${err.keyword})`)
	}
	process.exit(1)
}

main()
