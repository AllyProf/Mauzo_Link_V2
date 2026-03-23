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
        // Keywords to strip (Corporate Brands / Suffixes)
        $brands = ["bonite", "sbc", "tcc", "tbl", "sbl", "factory", "tanzania", "distillers", "limited", "ltd", "azam", "company"];
        
        // Metadata keywords to strip
        $packaging = ["crate", "pieces", "pcs", "unit", "btl", "bottle", "carton", "ctn", "pkg", "package"];
        
        // Specific identifiers to "Protect" and prioritize as the core name (Flavors/Specific Items)
        $protectedItems = [
            "fanta", "sprite", "krest", "stoney", "orange", "water", "grand malt", "tangawizi", 
            "aloe", "kilimanjaro", "safari", "serengeti", "mirinda", "pepsi", "coca-cola", 
            "coke", "embe", "nanasi", "passion", "apple", "mango", "citrus", "tonic", 
            "soda water", "ginger ale", "red bull", "hennessy", "jack daniel", "whisky", "brandy"
        ];

        // Step 1: Analyze Variant Name to find specific flavors or sizes
        $variantParts = explode('-', $variantName);
        $cleanVariantParts = [];
        $size = null;
        $foundIdentity = null;

        foreach ($variantParts as $part) {
            $part = trim($part);
            $lowerPart = strtolower($part);
            if ($part === '') continue;
            
            // Check for packaging metadata (Skip these)
            $isPkg = false;
            foreach ($packaging as $pkg) {
                if (str_contains($lowerPart, $pkg)) { $isPkg = true; break; }
            }
            if ($isPkg) continue;

            // Determine if the product is likely a drink/liquid to apply specific size formatting
            $isLiquid = false;
            $checkTitle = strtolower($productName . ' ' . $variantName);
            foreach ($protectedItems as $pi) {
                if (str_contains($checkTitle, $pi)) { $isLiquid = true; break; }
            }

            // Extract Size (Matches "350", "350ml", "1.5l", etc. OR common size words for liquids)
            $liquidSizeWords = ["large", "small", "medium", "normal", "big", "mini", "short", "tall"];
            $isNumericSize = preg_match('/^(\d+(\.\d+)?\s*(ml|l|g|kg|btl|pcs)?)$/i', $part);
            $isWordSize = ($isLiquid && in_array($lowerPart, $liquidSizeWords));

            if ($isNumericSize || $isWordSize) {
                $size = str_replace(' ', '', $part); // Standardize: no space between number and unit
                continue;
            }

            // Check if this part of the variant contains a specific product identity
            foreach ($protectedItems as $item) {
                if (str_contains($lowerPart, $item)) {
                    $foundIdentity = $part; 
                    break;
                }
            }

            if ($part !== '') {
                $cleanVariantParts[] = $part;
            }
        }

        // Step 2: Analyze Product Name for Brand mapping
        $parentInBrackets = null;
        if (preg_match('/\((.*?)\)/', $productName, $matches)) {
            $parentInBrackets = $matches[1];
        }

        // Step 3: Determine the "Core Title" using Priority Rules
        // Priority 1: Specific identity found in the variant field
        if ($foundIdentity) {
            $coreTitle = $foundIdentity;
        } 
        // Priority 2: The descriptive name inside brackets (e.g. Coca-Cola)
        elseif ($parentInBrackets) {
            $coreTitle = $parentInBrackets;
        } 
        // Priority 3: Fallback to the product name itself
        else {
            $coreTitle = $productName;
        }

        // Step 4: Final Cleanup (Remove Brand Prefixes like SBC, Bonite from the final result)
        foreach ($brands as $brand) {
            $coreTitle = preg_replace('/\b' . preg_quote($brand, '/') . '\b/i', '', $coreTitle);
        }
        $coreTitle = trim(preg_replace('/\s+/', ' ', $coreTitle));

        // Step 5: Construction
        $displayName = $coreTitle;
        
        // Add remaining variant info (like "Full", "Half") if not duplicating the title
        $variantText = implode(' - ', $cleanVariantParts);
        if ($variantText && stripos($displayName, $variantText) === false) {
             $displayName .= ' - ' . $variantText;
        }

        // If coreTitle ended up being just "Beer" or "Soda" because of stripping, 
        // fallback to original if nothing was found
        if (empty($displayName)) {
            $displayName = $productName;
        }

        // Append standardized size metadata
        if ($size) {
            $displayName .= ' (' . $size . ')';
        }

        return trim($displayName);
    }
}
