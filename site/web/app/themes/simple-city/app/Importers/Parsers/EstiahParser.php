<?php
/**
 * Estiah Home Art XML Parser
 */

namespace App\Importers\Parsers;

use App\Importers\Models\NormalizedProduct;

class EstiahParser extends AbstractParser {
    protected $supplier_name = 'Estiah';

    protected function getProductNodes() {
        return $this->xml->Products->Product;
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
                error_log("Estiah Parser Error: " . $e->getMessage());
            }
        }

        return $products;
    }

    protected function parseProduct($node) {
        $product = new NormalizedProduct();

        $product->supplier = $this->supplier_name;
        $product->supplier_product_id = $this->getNodeValue($node->ProductId);
        $product->sku = $this->getNodeValue($node->SKU);
        $product->barcode = $this->getNodeValue($node->BarcodePiece);
        $product->name = $this->getNodeValue($node->Name);
        $product->name_en = $this->getNodeValue($node->NameEn);
        $product->description = $this->cleanDescription($this->getNodeValue($node->Description));
        $product->description_en = $this->cleanDescription($this->getNodeValue($node->DescriptionEn));
        $product->supplier_url = $this->getNodeValue($node->link);

        // Prices
        $product->wholesale_price = $this->parsePrice($this->getNodeValue($node->Price));
        $product->retail_price = $this->parsePrice($this->getNodeValue($node->PriceRetail));

        // Stock
        $availability = $this->getNodeValue($node->Availability);
        $product->stock_status = $this->parseStockStatus(0, $availability);

        // Images
        if (isset($node->imageurl)) {
            $images = [];
            foreach ($node->imageurl as $imageUrl) {
                $url = $this->getNodeValue($imageUrl);
                if (!empty($url)) {
                    $images[] = $url;
                }
            }
            if (!empty($images)) {
                $product->main_image_url = $images[0];
                $product->gallery_image_urls = array_slice($images, 1);
            }
        }

        // Categories
        $categoryParent = $this->getNodeValue($node->CategoryParent);
        $category = $this->getNodeValue($node->Category);
        if (!empty($categoryParent)) {
            $product->categories[] = ['name' => $categoryParent];
        }
        if (!empty($category)) {
            $product->categories[] = ['name' => $category];
        }

        // Product Specifications as attributes
        if (isset($node->ProductSpecification)) {
            foreach ($node->ProductSpecification as $spec) {
                $specValue = $this->getNodeValue($spec);
                if (!empty($specValue)) {
                    // Try to parse "Key: Value" format
                    if (strpos($specValue, ':') !== false) {
                        list($key, $value) = explode(':', $specValue, 2);
                        $product->attributes[] = [
                            'name' => trim($key),
                            'value' => trim($value)
                        ];

                        // Extract brand and color
                        $keyLower = strtolower(trim($key));
                        if ($keyLower === 'brand') {
                            $product->manufacturer = trim($value);
                        } elseif (strpos($keyLower, 'χρώμα') !== false || $keyLower === 'color') {
                            $product->color = trim($value);
                        }
                    } else {
                        $product->specifications[] = $specValue;
                    }
                }
            }
        }

        // Dimensions & Weight
        $product->length = $this->parsePrice($this->getNodeValue($node->PieceLength));
        $product->width = $this->parsePrice($this->getNodeValue($node->PieceWidth));
        $product->height = $this->parsePrice($this->getNodeValue($node->PieceHeight));
        $product->weight = $this->parsePrice($this->getNodeValue($node->NetWeight));
        $product->material = $this->getNodeValue($node->PieceMaterial);

        return $product;
    }
}
