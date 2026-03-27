<?php

namespace App\Helpers;

class ProductHelper
{
    /**
     * Generate a clean display name for products and food items.
     * 
     * @param string $productName The base name of the product (e.g., "Bonite (Coca-Cola)")
     * @param string|null $variantName The specific variant/size (e.g., "350ml - Crate")
     * @return string
     */
    public static function generateDisplayName($productName, $variantDescription = '', $variantSpecificName = '')
    {
        $brands = ["bonite", "sbc", "tcc", "tbl", "sbl", "factory", "tanzania", "distillers", "limited", "ltd", "azam", "company"];
        $packaging = ["crate", "pieces", "pcs", "piece", "unit", "btl", "bottle", "carton", "ctn", "pkg", "package"];
        $flavors = [
            "fanta", "sprite", "krest", "stoney", "orange", "water", "grand malt", "tangawizi", 
            "aloe", "kilimanjaro", "safari", "serengeti", "mirinda", "pepsi", "coca-cola", 
            "coke", "embe", "nanasi", "passion", "apple", "mango", "citrus", "tonic", 
            "soda water", "ginger ale", "red bull", "hennessy", "jack daniel", "whisky", "brandy", 
            "vodka", "gin", "rum", "tequila", "liqueur", "wine", "dry", "sweet", "red", "white", "rose", 
            "dodoma", "4th street", "robertson", "selection", "claret", "hoff", "drostdy"
        ];

        // 1. Extract Parent Product Name from brackets if it exists (e.g. "Coca-Cola")
        $productTitle = $productName;
        if (preg_match('/\((.*?)\)/', $productName, $matches)) {
            $productTitle = $matches[1];
        }

        // 2. Look for specific Flavor/Identity in variant fields
        $identity = null;
        $allText = strtolower(($variantSpecificName ?? '') . ' ' . ($variantDescription ?? ''));
        foreach ($flavors as $flavor) {
            if (str_contains($allText, $flavor)) {
                // If found in variantSpecificName, use that exact string
                if ($variantSpecificName && str_contains(strtolower($variantSpecificName), $flavor)) {
                    $identity = $variantSpecificName;
                } else {
                    // Otherwise try to find the specific word in variantDescription
                    $identity = $variantDescription;
                }
                break;
            }
        }

        // 3. Determine Core (Flavor > Parent > Product Name)
        $coreTitle = $identity ?: $productTitle;

        // 4. Cleanup Core Title (Remove Brand Prefixes like SBC, Bonite)
        foreach ($brands as $brand) {
            $coreTitle = preg_replace('/\b' . preg_quote($brand, '/') . '\b/i', '', $coreTitle);
        }
        $coreTitle = trim(preg_replace('/\s+/', ' ', $coreTitle));

        // 5. Process Variant / Size
        $variantParts = explode('-', $variantDescription);
        $cleanVariantParts = [];
        $size = null;

        foreach ($variantParts as $part) {
            $part = trim($part);
            $lowerPart = strtolower($part);
            if ($part === '') continue;

            // Strip packaging metadata
            $isPkg = false;
            foreach ($packaging as $pkg) {
                if (str_contains($lowerPart, $pkg)) { $isPkg = true; break; }
            }
            if ($isPkg) continue;

            // Detect Size (Numeric or common drink sizes)
            $isNumericSize = preg_match('/^(\d+(\.\d+)?\s*(ml|l|g|kg|btl|pcs)?)$/i', $part);
            $liquidSizeWords = ["large", "small", "medium", "normal", "double", "single"];
            $isWordSize = in_array($lowerPart, $liquidSizeWords);

            if ($isNumericSize || $isWordSize) {
                $size = str_replace(' ', '', $part);
                continue;
            }

            // Only add to variant description if not already in core title
            if (stripos($coreTitle, $part) === false && stripos($part, $coreTitle) === false) {
                $cleanVariantParts[] = $part;
            }
        }

        // 6. Build Final Display Name
        $displayName = $coreTitle;
        
        $variantSuffix = implode(' - ', $cleanVariantParts);
        if (!empty($variantSuffix) && strcasecmp($displayName, $variantSuffix) !== 0) {
            $displayName .= ' - ' . $variantSuffix;
        }

        if (!empty($size)) {
            $displayName .= ' (' . $size . ')';
        }

        return trim($displayName);
    }
}
