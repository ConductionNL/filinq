/* eslint-disable no-console */
import { defineStore } from 'pinia'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export const useConsentStore     = defineStore(
        'consent',
        {
            state: () => ({
                consents: [],
                consentItem: null,
                loading: false,
                error: null,
            }),
        getters: {
            pendingConsents: (state) => state.consents.filter(c => c.consentStatus === 'pending'),
            approvedConsents: (state) => state.consents.filter(c => c.consentStatus === 'consent_given'),
            objectedConsents: (state) => state.consents.filter(c => c.consentStatus === 'objection_received'),
            consentStats: (state) => {
                const total      = state.consents.length
                const pending    = state.consents.filter(c => c.consentStatus === 'pending').length
                const approved   = state.consents.filter(c => c.consentStatus === 'consent_given').length
                const objected   = state.consents.filter(c => c.consentStatus === 'objection_received').length
                const noResponse = state.consents.filter(c => c.consentStatus === 'no_response').length
                const anonymized = state.consents.filter(c => c.consentStatus === 'anonymized').length
                return { total, pending, approved, objected, noResponse, anonymized }
            },
            },
            actions: {
                async fetchConsents() {
                    this.loading = true
                    this.error   = null
                    try {
                        const response = await axios.get(generateUrl('/apps/docudesk/api/consents'))
                        this.consents  = response.data
                    } catch (err) {
                        console.error('Failed to fetch consents:', err)
                        this.error = err.message
                    } finally {
                        this.loading = false
                    }
                },
                async fetchConsent(id) {
                    this.loading     = true
                    this.error       = null
                    try {
                        const response   = await axios.get(generateUrl(` / apps / docudesk / api / consents / ${id}`))
                        this.consentItem = response.data
                        return response.data
                    } catch (err) {
                        console.error('Failed to fetch consent:', err)
                        this.error = err.message
                        return null
                    } finally {
                        this.loading = false
                    }
                },
                async updateConsent(id, data) {
                    this.loading     = true
                    this.error       = null
                    try {
                        const response = await axios.put(generateUrl(` / apps / docudesk / api / consents / ${id}`), data)
                        // Update in local list.
                        const index = this.consents.findIndex(c => (c.id || c.uuid) === id)
                        if (index !== -1) {
                            this.consents[index] = response.data
                        }

                        this.consentItem = response.data
                        return response.data
                    } catch (err) {
                        console.error('Failed to update consent:', err)
                        this.error = err.message
                        return null
                    } finally {
                        this.loading = false
                    }
                },
                async fetchConsentsByDocument(documentId) {
                    this.loading     = true
                    this.error       = null
                    try {
                        const response = await axios.get(generateUrl(` / apps / docudesk / api / consents / document / ${documentId}`))
                        return response.data
                    } catch (err) {
                        console.error('Failed to fetch consents for document:', err)
                        this.error = err.message
                        return []
                    } finally {
                        this.loading = false
                    }
                },
                setConsentItem(consent) {
                    this.consentItem = consent
                },
                clearConsentItem() {
                    this.consentItem = null
                },
            },
}
        )
