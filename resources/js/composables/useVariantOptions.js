/**
 * Shared helpers to read a product variant's dimension options.
 * Variants store dimensions in the generic `options` array (`[{ name, value }]`).
 */

export function variantOptions(variant) {
    const options = Array.isArray(variant?.options) ? variant.options.filter(Boolean) : []

    return options.map(o => ({ name: String(o.name), value: String(o.value) }))
}

export function variantOptionMap(variant) {
    const map = {}

    for (const opt of variantOptions(variant)) {
        if (opt.name && !(opt.name in map)) {
            map[opt.name] = opt.value
        }
    }

    return map
}

export function dimensionNames(variants) {
    const names = []

    for (const variant of variants) {
        for (const opt of variantOptions(variant)) {
            if (opt.name && !names.includes(opt.name)) {
                names.push(opt.name)
            }
        }
    }

    return names
}

export function dimensionValues(variants, name) {
    const values = new Set()

    for (const variant of variants) {
        const opt = variantOptions(variant).find(o => o.name === name)

        if (opt?.value) {
            values.add(opt.value)
        }
    }

    return [...values]
}

export function findVariantByOptions(variants, selection = {}) {
    return variants.find((variant) => {
        const map = variantOptionMap(variant)

        return Object.entries(selection).every(([name, value]) => {
            if (!value) {
                return !(name in map)
            }

            return map[name] === value
        })
    })
}

export function variantLabel(variant) {
    return variantOptions(variant)
        .map(o => o.value)
        .join(' / ')
}

/** First non-empty dimension value (used for product card badges). */
export function primaryDimensionValue(variant) {
    return variantOptions(variant)[0]?.value || null
}

/** Case-insensitive check for the color dimension (drives swatch rendering). */
export function isColorDimension(name) {
    return String(name || '').toLowerCase() === 'color'
}