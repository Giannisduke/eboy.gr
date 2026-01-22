<?php
/**
 * B2BMarkt XML Parser
 */

namespace App\Importers\Parsers;

use App\Importers\Models\NormalizedProduct;

class B2BMarktParser extends AbstractParser {
    protected $supplier_name = 'B2BMarkt';

    protected function getProductNodes() {
        return $this->xml->Product;
    }

    public function parseProducts() {
        $this->loadXML();
        $products = [];

        foreach ($this->getProductNodes() as $productNode) {
            try {
                $product = $this->parseProduct($productNode);
                if ($product->validate() === true) {
                    $products[] = $product;
                }
            } catch (\Exception $e) {
                error_log("B2BMarkt Parser Error: " . $e->getMessage());
            }
        }

        return $products;
    }

    protected function parseProduct($node) {
        $product = new NormalizedProduct();

        $product->supplier = $this->supplier_name;
        $product->supplier_product_id = $this->getNodeValue($node->ProductId);
        $product->sku = $this->getNodeValue($node->ProductCode);
        $product->barcode = $this->getNodeValue($node->BarcodeMain);
        $product->name = $this->getNodeValue($node->Name);
        $product->description = $this->cleanDescription($this->getNodeValue($node->ExtendedDescription));

        // Prices
        $product->wholesale_price = $this->parsePrice($this->getNodeValue($node->ZoneFourUnitPrice));
        $product->retail_price = $this->parsePrice($this->getNodeValue($node->RetailCurrentPrice));
        $product->sale_price = $this->parsePrice($this->getNodeValue($node->MarketPrice));

        // Stock
        $stock = $this->parsePrice($this->getNodeValue($node->Stock));
        $availability = $this->getNodeValue($node->AvailabilityTypeName);
        $product->stock_quantity = $stock;
        $product->stock_status = $this->parseStockStatus($stock, $availability);

        // Images
        if (isset($node->ImagesLocation->image)) {
            $images = [];
            foreach ($node->ImagesLocation->image as $imageUrl) {
                $images[] = $this->getNodeValue($imageUrl);
            }
            if (!empty($images)) {
                $product->main_image_url = $images[0];
                $product->gallery_image_urls = array_slice($images, 1);
            }
        }

        // Categories
        if (isset($node->Categories->Category)) {
            foreach ($node->Categories->Category as $cat) {
                $product->categories[] = [
                    'id' => $this->getNodeAttribute($cat, 'id'),
                    'level' => $this->getNodeAttribute($cat, 'level'),
                    'name' => $this->getNodeValue($cat)
                ];
            }
        }

        // Filters as attributes
        if (isset($node->Filters->Filter)) {
            foreach ($node->Filters->Filter as $filter) {
                $group = $this->getNodeValue($filter->Group);
                $value = $this->getNodeValue($filter->Value);
                if (!empty($group) && !empty($value)) {
                    $product->attributes[] = [
                        'name' => $group,
                        'value' => $value
                    ];
                }
            }
        }

        // Weight
        $product->weight = $this->parsePrice($this->getNodeValue($node->Weight));

        return $product;
    }
}
