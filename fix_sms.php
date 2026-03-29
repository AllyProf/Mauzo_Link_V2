<?php
$c = file_get_contents('app/Services/StockTransferSmsService.php');
$c = preg_replace('/\$productName = \$item->productVariant->product->name \?\? \'N\/A\';/', '$productName = $item->productVariant->display_name ?? ($item->productVariant->product->name ?? \'N/A\');', $c);
$c = preg_replace('/Product: \{\$product->name\}\\\\n\s*Variant: \{\$variant->measurement\} - \{\$variant->packaging\}\\\\n/', "Item: {\$variant->display_name}\\n", $c);
$c = preg_replace('/- \{\$product->name\} \(\{\$variant->measurement\}\): \{\$transfer->quantity_requested\} \{\$pkg\}\\\\n/', "- {\$variant->display_name}: {\$transfer->quantity_requested} {\$pkg}\\n", $c);
file_put_contents('app/Services/StockTransferSmsService.php', $c);
