<?php
/**
 * B2BMarkt XML Parser
 */

namespace App\Importers\Parsers;

use App\Importers\Models\NormalizedProduct;

class B2BMarktParser extends AbstractParser {
    protected $supplier_name = 'B2BMarkt';

    /**
     * Map of ProductId → ['width','length','height'] extracted from the ORIGINAL
     * b2bmarkt.xml. The Python AI processor rewrites ExtendedDescription and
     * strips the "ΔΙΑΣΤΑΣΕΙΣ:" block, so we have to re-read the source XML.
     * Lazy-built on first access.
     */
    private $original_dimensions_index = null;

    /**
     * Per-process cache of dimension indexes, keyed by "{path}:{mtime}".
     * The realtime importer creates a fresh parser instance per batch poll;
     * without this cache we would re-stream the 50MB original XML each time
     * (~1s and ~1.5MB temporary work), turning a long import into a much
     * longer one. The cache survives for the lifetime of the WP-CLI process.
     */
    private static $index_cache = [];

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
        $raw_description = $this->getNodeValue($node->ExtendedDescription);
        $product->description = $this->cleanDescription($raw_description);

        // Product dimensions are read exclusively from the ORIGINAL b2bmarkt.xml
        // (multi-source extraction: ΔΙΑΣΤΑΣΕΙΣ block → title → Filter "Διαστάσεις"
        // → labeled patterns). The AI-enhanced description rewrites usually strip
        // the dimensions block, so we don't trust the enhanced node for this.
        $product_id = (string) $node->ProductId;
        $dims = $this->getOriginalDimensions($product_id);
        if ($dims) {
            if (!empty($dims['width']))  $product->width  = $dims['width'];
            if (!empty($dims['length'])) $product->length = $dims['length'];
            if (!empty($dims['height'])) $product->height = $dims['height'];
        }

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
            $attributeRenames = [
                'Απόχρωση' => 'χρώμα',
            ];
            // Filter groups that must NOT become product attributes — their data
            // is consumed elsewhere (dimensions go into ύψος/πλάτος/μήκος globals).
            $skipGroups = ['Διαστάσεις'];

            foreach ($node->Filters->Filter as $filter) {
                $group = $this->getNodeValue($filter->Group);
                $value = $this->getNodeValue($filter->Value);
                if (in_array($group, $skipGroups, true)) {
                    continue;
                }
                if (!empty($group) && !empty($value)) {
                    $product->attributes[] = [
                        'name' => $attributeRenames[$group] ?? $group,
                        'value' => $value
                    ];
                }
            }

            // Ensure "υλικό" is always the first attribute.
            usort($product->attributes, function (array $a, array $b): int {
                $aIsYliko = mb_strtolower($a['name']) === 'υλικό';
                $bIsYliko = mb_strtolower($b['name']) === 'υλικό';
                if ($aIsYliko && !$bIsYliko) return -1;
                if (!$aIsYliko && $bIsYliko) return 1;
                return 0;
            });
        }

        // Weight
        $product->weight = $this->parsePrice($this->getNodeValue($node->Weight));

        // Brand: extract from filters if available, otherwise use supplier name
        if (empty($product->manufacturer)) {
            $product->manufacturer = 'B2BMarkt';
        }

        // AI-Enhanced Fields (woo_category, tags, tech_specs)
        $this->parseAIFields($product, $node);

        return $product;
    }

    /**
     * Look up dimensions for a product by its ProductId in the original
     * b2bmarkt.xml. Returns ['width','length','height'] or null.
     */
    private function getOriginalDimensions(string $product_id): ?array {
        if ($this->original_dimensions_index === null) {
            $this->buildOriginalDimensionsIndex();
        }
        return $this->original_dimensions_index[$product_id] ?? null;
    }

    /**
     * Locate the original (non-enhanced) XML and index every product's
     * dimensions by ProductId. Called once, lazily. Uses XMLReader streaming
     * because the original feed is ~50MB and we only need two child nodes.
     */
    private function buildOriginalDimensionsIndex(): void {
        $this->original_dimensions_index = [];

        $original_path = $this->resolveOriginalXMLPath();
        if (!$original_path || !file_exists($original_path)) {
            error_log("B2BMarktParser: Original XML not found (looked at {$original_path}); dimensions will fall back to enhanced description only.");
            return;
        }

        // Cache hit: another parser instance in this process has already indexed
        // this file. Realtime imports go through importProducts() once per batch
        // poll, and rebuilding the index every time would dominate the runtime.
        $cache_key = $original_path . ':' . (@filemtime($original_path) ?: 0);
        if (isset(self::$index_cache[$cache_key])) {
            $this->original_dimensions_index = self::$index_cache[$cache_key];
            return;
        }

        $reader = new \XMLReader();
        if (!$reader->open($original_path)) {
            error_log("B2BMarktParser: XMLReader failed to open {$original_path}");
            return;
        }

        while ($reader->read()) {
            if ($reader->nodeType !== \XMLReader::ELEMENT || $reader->name !== 'Product') {
                continue;
            }
            $product_xml = $reader->readOuterXML();
            $reader->next(); // skip past this Product subtree
            if ($product_xml === '') {
                continue;
            }
            $node = simplexml_load_string($product_xml, 'SimpleXMLElement', LIBXML_NOCDATA);
            if ($node === false) {
                continue;
            }
            $pid = (string) $node->ProductId;
            if ($pid === '') {
                continue;
            }
            $dims = $this->extractDimensionsFromNode($node);
            if ($dims) {
                $this->original_dimensions_index[$pid] = $dims;
            }
        }
        $reader->close();

        self::$index_cache[$cache_key] = $this->original_dimensions_index;
        error_log("B2BMarktParser: Indexed dimensions for " . count($this->original_dimensions_index) . " products from original XML.");
    }

    /**
     * Map an enhanced-XML path to its original counterpart:
     *   …/scripts/xml_files/enhanced/b2bmarkt-enhanced.xml
     * → …/scripts/xml_files/gr/b2bmarkt.xml
     */
    private function resolveOriginalXMLPath(): ?string {
        $path = $this->file_path;
        if (strpos($path, '/enhanced/') === false) {
            // Already pointing at the original — same file.
            return $path;
        }
        $original = str_replace('/enhanced/', '/gr/', $path);
        $original = preg_replace('/-enhanced\.xml$/', '.xml', $original);
        return $original;
    }

    /**
     * Extract dimensions from a Product node, trying multiple sources and
     * preferring 3-value triples over 2-value pairs.
     *
     * B2BMarkt convention: Π × Β × Υ (width × depth × height) in cm.
     * 2-value sources are interpreted as Π × Μ (width × length, no height).
     *
     * @return array|null ['width','length','height'] — values may be null
     *                    when the source provided fewer dimensions.
     */
    private function extractDimensionsFromNode($node): ?array {
        $title    = (string) $node->Name;
        $desc     = (string) $node->ExtendedDescription;
        $filterDs = $this->getFilterDimensionsValue($node);
        $dimBlock = $this->getDimensionsBlock($desc);

        // Sources we trust, in priority order. Free description text is excluded
        // because it often mentions the diameter of unrelated parts (e.g. tubes).
        $sources = [$dimBlock, $title, $filterDs];

        // Pass 1: 3-value-equivalent matches (triple OR diameter+height).
        // Within each source we pick whichever pattern STARTS EARLIEST so that
        // a primary "Διαστάσεις: Φ152x74" wins over a later "Κλειστό 152x152x6",
        // while a primary triple "70x70x72" still wins over an incidental
        // "Φ25x0.8mm" tube spec that comes after it.
        foreach ($sources as $text) {
            if ($text === '') continue;
            $best = $this->earliest([
                $this->findTripleWithPos($text),
                $this->findDiameterHeightWithPos($text),
            ]);
            if ($best !== null) {
                return $best['result'];
            }
        }

        // Pass 2: 2-value matches — a pair OR a diameter alone (width=length=Ø).
        foreach ($sources as $text) {
            if ($text === '') continue;
            $best = $this->earliest([
                $this->findPairWithPos($text),
                $this->findDiameterAloneWithPos($text),
            ]);
            if ($best !== null) {
                return $best['result'];
            }
        }

        // Final fallback: labeled "Μήκος / Πλάτος / Ύψος" patterns in description.
        $labeled = $this->parseLabeledDims($desc);
        if (is_array($labeled)) {
            return $this->dimsToWLH($labeled);
        }

        // Last resort: labeled diameter "διάμετρο X μέτρα" in description (umbrellas).
        $labeledDia = $this->parseLabeledDiameter($desc);
        if ($labeledDia !== null) {
            return $labeledDia;
        }

        return null;
    }

    /**
     * Find a labeled diameter mention in free text. Three phrasings:
     *   "διάμετρο 2.20 μέτρα"   — explicit "diameter X metres"
     *   "στρόγγυλη 2,50 μέτρα"  — round-shape declaration with size
     *   "φ2,00μ" / "Φ 3 μέτρα"  — Φ-prefixed value with metre unit
     *
     * Restricted to metres (μέτρα/μ) so we don't pick up tube/pole diameters
     * (e.g. "Φ50mm") which use mm/cm units.
     *
     * If the same text also contains "ύψος X μέτρα/μ" (in metres), that value
     * is captured as height. Height in cm/mm units is intentionally ignored
     * here because B2BMarkt has known typos like "ύψος 2,50 εκ." for a 2.5m
     * umbrella — accepting only metres avoids those.
     */
    private function parseLabeledDiameter(string $text): ?array {
        if ($text === '') {
            return null;
        }
        $diameterPatterns = [
            '/διάμετρ[οους]{0,2}\s+(\d+(?:[.,]\d+)?)\s*(μέτρα|μ\.?)/iu',
            '/στρόγγυλ[οηος]?\s+(\d+(?:[.,]\d+)?)\s*(μέτρα|μ\.?)/iu',
            '/[Φφ]\s*(\d+(?:[.,]\d+)?)\s*(μέτρα|μ\.?)/iu',
        ];
        $diameter = null;
        foreach ($diameterPatterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                $diameter = $this->convertOneToCm($this->toFloat($m[1]), $m[2] ?? '');
                break;
            }
        }
        if ($diameter === null) {
            return null;
        }

        $height = null;
        if (preg_match('/ύψος\s+(\d+(?:[.,]\d+)?)\s*(μέτρα|μ\.?)/iu', $text, $m)) {
            $height = $this->convertOneToCm($this->toFloat($m[1]), $m[2] ?? '');
        }

        return ['width' => $diameter, 'length' => $diameter, 'height' => $height];
    }

    /**
     * Convert a single value to centimetres based on its captured unit suffix.
     */
    private function convertOneToCm(float $v, string $unit): float {
        $u = trim(rtrim($unit, '.'));
        if ($u === 'μ' || $u === 'μέτρα' || $u === 'm') return $v * 100;
        if ($u === 'mm') return $v / 10;
        return $v;
    }

    /**
     * Description substring starting from a "Διαστ" anchor (case-insensitive),
     * or empty string if no such anchor exists. Matches the full word
     * "ΔΙΑΣΤΑΣΕΙΣ"/"Διαστάσεις" as well as abbreviated forms like
     * "Διαστ κρεβατιού: 186x80 εκ." that B2BMarkt uses sporadically.
     * Sub-dimension labels (e.g. "Διαστάσεις καθίσματος") are still inside the
     * returned haystack, but the regex picks the first triple/pair it finds.
     */
    private function getDimensionsBlock(string $text): string {
        if ($text === '') {
            return '';
        }
        $offset = mb_stripos($text, 'Διαστ', 0, 'UTF-8');
        return $offset !== false ? mb_substr($text, $offset, null, 'UTF-8') : '';
    }

    /**
     * Pull the Value of <Filters><Filter><Group>Διαστάσεις</Group> if present.
     */
    private function getFilterDimensionsValue($node): string {
        if (!isset($node->Filters->Filter)) {
            return '';
        }
        foreach ($node->Filters->Filter as $f) {
            if ((string) $f->Group === 'Διαστάσεις') {
                return (string) $f->Value;
            }
        }
        return '';
    }

    /**
     * Common regex fragments used by the position-aware finders.
     * Separators: Latin x/X, Greek Χ/χ, ×, or *. Decimals: . or ,.
     * Units: μ/μέτρα → ×100, mm → ÷10, others → as-is.
     */
    private const RE_NUM  = '(\d+(?:[.,]\d+)?)';
    private const RE_SEP  = '[xXΧχ×*]';
    private const RE_UNIT = '(?:\s*(μέτρα|μ\.?|mm|cm|εκ\.?|ΕΚ\.?|m\.?))?';
    // Optional Y/H height marker glued to a unit, e.g. "51Υεκ".
    private const RE_UNIT_H = '(?:\s*[ΥΗ]?\s*(μέτρα|μ\.?|mm|cm|εκ\.?|ΕΚ\.?|m\.?))?';

    /**
     * Pick the candidate that starts at the earliest byte offset in its source.
     * Each candidate is ['pos' => int, 'result' => array] or null.
     */
    private function earliest(array $candidates): ?array {
        $best = null;
        foreach ($candidates as $c) {
            if ($c === null) continue;
            if ($best === null || $c['pos'] < $best['pos']) {
                $best = $c;
            }
        }
        return $best;
    }

    /** Find first 3-value triple in $text. */
    private function findTripleWithPos(string $text): ?array {
        if ($text === '') return null;
        $re = '/' . self::RE_NUM . '\s*' . self::RE_SEP . '\s*'
                  . self::RE_NUM . '\s*' . self::RE_SEP . '\s*'
                  . self::RE_NUM . self::RE_UNIT . '/u';
        if (preg_match($re, $text, $m, PREG_OFFSET_CAPTURE)) {
            $vals = [$this->toFloat($m[1][0]), $this->toFloat($m[2][0]), $this->toFloat($m[3][0])];
            $vals = $this->applyUnit($vals, $m[4][0] ?? '');
            return ['pos' => $m[0][1], 'result' => $this->dimsToWLH($vals)];
        }
        return null;
    }

    /** Find first 2-value pair (used when no triple was found). */
    private function findPairWithPos(string $text): ?array {
        if ($text === '') return null;
        $re = '/' . self::RE_NUM . '\s*' . self::RE_SEP . '\s*'
                  . self::RE_NUM . self::RE_UNIT . '/u';
        if (preg_match($re, $text, $m, PREG_OFFSET_CAPTURE)) {
            $vals = [$this->toFloat($m[1][0]), $this->toFloat($m[2][0])];
            $vals = $this->applyUnit($vals, $m[3][0] ?? '');
            return ['pos' => $m[0][1], 'result' => $this->dimsToWLH($vals)];
        }
        return null;
    }

    /** Find first Φ N [unit] × M [unit] pattern (diameter + height). */
    private function findDiameterHeightWithPos(string $text): ?array {
        if ($text === '') return null;
        $re = '/[Φφ]\s*' . self::RE_NUM . self::RE_UNIT . '\s*' . self::RE_SEP
                         . '\s*' . self::RE_NUM . self::RE_UNIT_H . '/u';
        if (preg_match($re, $text, $m, PREG_OFFSET_CAPTURE)) {
            $d = $this->convertOneToCm($this->toFloat($m[1][0]), $m[2][0] ?? '');
            $h = $this->convertOneToCm($this->toFloat($m[3][0]), $m[4][0] ?? '');
            return [
                'pos' => $m[0][1],
                'result' => ['width' => $d, 'length' => $d, 'height' => $h],
            ];
        }
        return null;
    }

    /** Find first Φ N [unit] alone (diameter only). */
    private function findDiameterAloneWithPos(string $text): ?array {
        if ($text === '') return null;
        $re = '/[Φφ]\s*' . self::RE_NUM . self::RE_UNIT . '/u';
        if (preg_match($re, $text, $m, PREG_OFFSET_CAPTURE)) {
            $d = $this->convertOneToCm($this->toFloat($m[1][0]), $m[2][0] ?? '');
            return [
                'pos' => $m[0][1],
                'result' => ['width' => $d, 'length' => $d, 'height' => null],
            ];
        }
        return null;
    }

    /**
     * Normalize values to centimetres based on a captured unit string.
     */
    private function applyUnit(array $vals, string $unit): array {
        $u = trim(rtrim($unit, '.'));
        if ($u === 'μ' || $u === 'μέτρα' || $u === 'm') {
            return array_map(fn($v) => $v * 100, $vals);
        }
        if ($u === 'mm') {
            return array_map(fn($v) => $v / 10, $vals);
        }
        return $vals;
    }

    /**
     * Parse labeled dimensions like:
     *   "Μήκος (1,80μ)\nΠλάτος (1,80μ)\nΎψος (X)"
     * Returns values in [width, length, height] order. Height is omitted
     * when not labeled. Values in metres ("μ") are converted to centimetres.
     */
    private function parseLabeledDims(string $text): ?array {
        if ($text === '') {
            return null;
        }
        $patterns = [
            'length' => '/Μήκος[\s:]*\(?\s*(\d+(?:[.,]\d+)?)\s*(μ|εκ|cm|mm)?/u',
            'width'  => '/Πλάτος[\s:]*\(?\s*(\d+(?:[.,]\d+)?)\s*(μ|εκ|cm|mm)?/u',
            'height' => '/Ύψος[\s:]*\(?\s*(\d+(?:[.,]\d+)?)\s*(μ|εκ|cm|mm)?/u',
        ];
        $vals = [];
        foreach ($patterns as $key => $regex) {
            if (preg_match($regex, $text, $m)) {
                $v = $this->toFloat($m[1]);
                if (isset($m[2]) && $m[2] === 'μ') {
                    $v *= 100; // metres → centimetres
                } elseif (isset($m[2]) && $m[2] === 'mm') {
                    $v /= 10;  // millimetres → centimetres
                }
                $vals[$key] = $v;
            }
        }
        if (!isset($vals['width']) && !isset($vals['length'])) {
            return null;
        }
        // Return only labeled values, in declaration order width-length-height,
        // skipping any missing slot. Caller treats 2-value result as W×L.
        $result = [];
        foreach (['width', 'length', 'height'] as $k) {
            if (isset($vals[$k])) {
                $result[] = $vals[$k];
            }
        }
        return $result;
    }

    /**
     * Map a numeric array to ['width','length','height'] using the B2BMarkt
     * convention (Π × Β × Υ for 3-value, Π × Μ for 2-value).
     */
    private function dimsToWLH(array $dims): array {
        return [
            'width'  => $dims[0] ?? null,
            'length' => $dims[1] ?? null,
            'height' => $dims[2] ?? null,
        ];
    }

    private function toFloat(string $raw): float {
        return (float) str_replace(',', '.', $raw);
    }
}
