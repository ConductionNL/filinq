/**
 * @copyright Copyright (c) 2024 Conduction B.V. <info@conduction.nl>
 * @license EUPL-1.2
 */

export type TTemplate = {
    id?: string
    name?: string
    content?: string
    category?: string
    outputFormat?: string
    variables?: Array<string>
    description?: string
    author?: string
    version?: string
    updated?: string
    created?: string
}
