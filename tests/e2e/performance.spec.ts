/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 */
import { test } from '@playwright/test'
import { definePerformanceSpec } from '@conduction/nextcloud-vue/testing/performance'

definePerformanceSpec(test)
