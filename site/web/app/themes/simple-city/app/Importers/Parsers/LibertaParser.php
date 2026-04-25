<?php
/**
 * Libertab2b XML Parser
 */

namespace App\Importers\Parsers;

use App\Importers\Models\NormalizedProduct;

class LibertaParser extends AbstractParser {
    protected $supplier_name = 'Liberta';

    protected function getProductNodes() {
        return $this->xml->product;
    }

    public function parseProducts() {
        $this->loadXML();
        $products   = [];
        $seen_skus  = [];

        foreach ($this->getProductNodes() as $productNode) {
            try {
                $product = $this->parseProduct($productNode);
                if ($product->validate() !== true) {
                    continue;
                }
                if (isset($seen_skus[$product->sku])) {
                    error_log("LibertaParser: Skipping duplicate SKU: {$product->sku}");
                    continue;
                }
                $seen_skus[$product->sku] = true;
                $products[] = $product;
            } catch (\Exception $e) {
                error_log("Liberta Parser Error: " . $e->getMessage());
            }
        }

        return $products;
    }

    protected function parseProduct($node) {
        $product = new NormalizedProduct();

        $product->supplier = $this->supplier_name;
        $product->sku = $this->getNodeValue($node->sku);
        $product->barcode = $this->getNodeValue($node->barcode);
        $product->name = $this->getNodeValue($node->name);
        $product->name_en = $this->getNodeValue($node->{'name-en'});
        $product->description = $this->cleanDescription($this->getNodeValue($node->description));
        $product->description_en = $this->cleanDescription($this->getNodeValue($node->{'description-en'}));

        // Prices
        $product->retail_price = $this->parsePrice($this->getNodeValue($node->{'retail-price'}));
        $product->sale_price = $this->parsePrice($this->getNodeValue($node->{'discounted-price'}));

        // Stock
        $stock = $this->parsePrice($this->getNodeValue($node->quantity));
        $product->stock_quantity = $stock;
        $product->stock_status = $stock > 0 ? 'instock' : 'outofstock';

        // Images
        $product->main_image_url = $this->getNodeValue($node->photo);
        if (isset($node->photos->item)) {
            foreach ($node->photos->item as $imageUrl) {
                $product->gallery_image_urls[] = $this->getNodeValue($imageUrl);
            }
        }

        // Categories
        if (isset($node->categories->item)) {
            foreach ($node->categories->item as $cat) {
                $product->categories[] = [
                    'name' => $this->getNodeValue($cat)
                ];
            }
        }

        // Attributes
        $material = $this->getNodeValue($node->material);
        $color = $this->getNodeValue($node->color);
        $collection = $this->getNodeValue($node->collection->item);

        if (!empty($material)) {
            $product->material = $material;
            $product->attributes[] = [
                'name' => 'υλικό',
                'value' => $material
            ];
        }

        if (!empty($color)) {
            $product->color = $color;
            $product->attributes[] = [
                'name' => 'χρώμα',
                'value' => $color
            ];
        }

        if (!empty($collection)) {
            $product->attributes[] = [
                'name' => 'Collection',
                'value' => $collection
            ];
        }

        // Dimensions & Weight
        $product->dimensions_text = $this->getNodeValue($node->dimensions);
        $dimensions = $this->parseDimensions($product->dimensions_text);
        $product->length = $dimensions['length'];
        $product->width = $dimensions['width'];
        $product->height = $dimensions['height'];
        $product->weight = $this->parsePrice($this->getNodeValue($node->weight));

        // Additional
        $product->assembly_manual_url = $this->getNodeValue($node->{'assembly-instructions'});

        // Comments as specifications
        $comments = $this->getNodeValue($node->comments);
        if (!empty($comments)) {
            $product->specifications[] = $comments;
        }

        // Brand
        $product->manufacturer = 'Liberta';

        // AI-Enhanced Fields (woo_category, tags, tech_specs)
        $this->parseAIFields($product, $node);

        return $product;
    }
}
