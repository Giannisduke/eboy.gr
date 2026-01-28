<?php
/**
 * Pakoworld XML Parser
 */

namespace App\Importers\Parsers;

use App\Importers\Models\NormalizedProduct;

class PakoworldParser extends AbstractParser {
    protected $supplier_name = 'Pakoworld';

    protected function getProductNodes() {
        return $this->xml->products->product;
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
                // Log error and continue
                error_log("Pakoworld Parser Error: " . $e->getMessage());
            }
        }

        return $products;
    }

    protected function parseProduct($node) {
        $product = new NormalizedProduct();

        $product->supplier = $this->supplier_name;
        $product->supplier_product_id = $this->getNodeAttribute($node, 'id');
        $product->sku = $this->getNodeValue($node->model);
        $product->barcode = $this->getNodeValue($node->ean);
        $product->name = $this->getNodeValue($node->name);
        $product->description = $this->cleanDescription($this->getNodeValue($node->description));
        $product->manufacturer = $this->getNodeValue($node->manufacturer);

        // Prices
        $product->retail_price = $this->parsePrice($this->getNodeValue($node->retail_price_with_vat));
        $product->wholesale_price = $this->parsePrice($this->getNodeValue($node->net_price));
        $product->sale_price = $this->parsePrice($this->getNodeValue($node->weboffer_price_with_vat));

        // Images
        $product->main_image_url = $this->getNodeValue($node->main_image);

        // Categories
        if (isset($node->categories->category)) {
            foreach ($node->categories->category as $cat) {
                $product->categories[] = [
                    'id' => $this->getNodeAttribute($cat, 'id'),
                    'name' => $this->getNodeValue($cat)
                ];
            }
        }

        // AI-Enhanced Fields (if available)
        if (isset($node->woo_category)) {
            $product->woo_category = $this->getNodeValue($node->woo_category);
        }

        if (isset($node->tags)) {
            $tagsString = $this->getNodeValue($node->tags);
            if (!empty($tagsString)) {
                $product->tags = array_map('trim', explode(',', $tagsString));
            }
        }

        // Attributes
        if (isset($node->attributes->attribute)) {
            foreach ($node->attributes->attribute as $attr) {
                $attrValue = $this->getNodeValue($attr);
                if (!empty($attrValue)) {
                    $product->attributes[] = [
                        'id' => $this->getNodeAttribute($attr, 'id'),
                        'value' => $attrValue
                    ];
                }
            }
        }

        // Dimensions
        $product->length = $this->parsePrice($this->getNodeValue($node->length));
        $product->width = $this->parsePrice($this->getNodeValue($node->width));
        $product->height = $this->parsePrice($this->getNodeValue($node->height));
        $product->weight = $this->parsePrice($this->getNodeValue($node->weight));

        // Additional
        if (isset($node->assembly_manual)) {
            $product->assembly_manual_url = $this->getNodeValue($node->assembly_manual);
        }

        if (isset($node->related_products->related_model)) {
            foreach ($node->related_products->related_model as $relatedModel) {
                $product->related_skus[] = $this->getNodeValue($relatedModel);
            }
        }

        // Stock - Pakoworld doesn't provide stock, set as backorder
        $product->stock_status = 'onbackorder';
        $product->stock_quantity = 0;

        return $product;
    }
}
