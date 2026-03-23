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
    public static function generateDisplayName($productName, $variantName = '')
    {
        // 1. Keyword stripping (Corporate Brands)
        $brands = ["bonite", "sbc", "tcc", "tbl", "sbl", "factory", "tanzania", "distillers", "limited", "ltd", "azam"];
        
        // 2. Metadata stripping (Packaging)
        $packaging = ["crate", "pieces", "pcs", "unit", "btl", "bottle", "carton", "ctn", "pkg", "package"];
        
        // Step A: Extract Core from Product Name (Priority Rules)
        // If "Brand (Item)", extract "Item"
        $core = $productName;
        if (preg_match('/\((.*?)\)/', $productName, $matches)) {
            $core = $matches[1];
        } else {
            // Remove brands from productName if not in parentheses (using word boundaries)
            foreach ($brands as $brand) {
                $core = preg_replace('/\b' . preg_quote($brand, '/') . '\b/i', '', $core);
            }
        }
        $core = trim(preg_replace('/\s+/', ' ', $core));

        // Handle null or empty variantName
        if (empty($variantName)) {
            return $core;
        }

        // Step B: Handle Variant Name & Size Preservation
        $variantParts = explode('-', $variantName);
        $cleanVariantParts = [];
        $size = null;

        foreach ($variantParts as $part) {
            $part = trim($part);
            $lowerPart = strtolower($part);
            
            // Filter out packaging keywords
            $isPkg = false;
            foreach ($packaging as $pkg) {
                if (str_contains($lowerPart, $pkg)) {
                    $isPkg = true;
                    break;
                }
            }
            if ($isPkg) continue;

            // Check for sizes: digits followed by opt unit (e.g. 350, 350ml, 1.5l)
            if (preg_match('/^(\d+(\.\d+)?\s*(ml|l|g|kg|btl|pcs)?)$/i', $part)) {
                $size = $part;
            } else {
                if ($part !== '') {
                    $cleanVariantParts[] = $part;
                }
            }
        }

        // Step C: Combine Item + Size + Remaining Variant
        $displayName = $core;
        
        // Add remaining variant parts (like "Full", "Half", "Special")
        $variantText = implode(' - ', $cleanVariantParts);
        // De-duplicate: only add if variant text is NOT already in core name
        if ($variantText && stripos($displayName, $variantText) === false) {
             $displayName .= ' - ' . $variantText;
        }

        // Add size in parentheses if found
        if ($size) {
            $displayName .= ' (' . $size . ')';
        }

        return trim($displayName);
    }
}
