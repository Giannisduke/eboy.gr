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
        $product->manufacturer = 'Pakoworld';

        // Prices
        $product->retail_price = $this->parsePrice($this->getNodeValue($node->retail_price_with_vat));
        $product->wholesale_price = $this->parsePrice($this->getNodeValue($node->net_price));
        $product->sale_price = $this->parsePrice($this->getNodeValue($node->weboffer_price_with_vat));

        // Images
        $product->main_image_url = $this->getNodeValue($node->main_image);

        // Gallery images
        if (isset($node->images->image)) {
            foreach ($node->images->image as $image) {
                $url = $this->getNodeValue($image);
                if (!empty($url)) {
                    $product->gallery_image_urls[] = $url;
                }
            }
        }

        // Categories
        if (isset($node->categories->category)) {
            foreach ($node->categories->category as $cat) {
                $product->categories[] = [
                    'id' => $this->getNodeAttribute($cat, 'id'),
                    'name' => $this->getNodeValue($cat)
                ];
            }
        }

        // AI-Enhanced Fields (woo_category, tags, tech_specs)
        $this->parseAIFields($product, $node);

        // Attributes - parse "Name: Value" format
        if (isset($node->attributes->attribute)) {
            foreach ($node->attributes->attribute as $attr) {
                $attrText = $this->getNodeValue($attr);
                if (empty($attrText)) continue;

                if (strpos($attrText, ':') !== false) {
                    [$name, $value] = explode(':', $attrText, 2);
                    $name  = trim($name);
                    $value = trim($value);

                    $attributeRenames = [
                        'Μεικτό Βάρος -  Gross Weight'    => 'Μεικτό Βάρος',
                        'Μεικτό Βάρος - Gross Weight'     => 'Μεικτό Βάρος',
                        'Πραγματικό Βάρος -  Net Weight'  => 'Πραγματικό Βάρος',
                        'Πραγματικό Βάρος - Net Weight'   => 'Πραγματικό Βάρος',
                    ];
                    if (isset($attributeRenames[$name])) {
                        $name = $attributeRenames[$name];
                    }
                } else {
                    $name  = 'Attribute ' . $this->getNodeAttribute($attr, 'id');
                    $value = trim($attrText);
                }

                if (!empty($name) && !empty($value)) {
                    $product->attributes[] = ['name' => $name, 'value' => $value];
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

        // Stock
        $qty = (int) $this->getNodeValue($node->quantity);
        $product->stock_quantity = $qty;
        $product->stock_status = $qty > 0 ? 'instock' : 'outofstock';

        return $product;
    }
}
