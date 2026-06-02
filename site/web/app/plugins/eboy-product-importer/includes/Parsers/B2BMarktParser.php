<?php
/**
 * B2BMarkt XML Parser
 */

namespace App\Importers\Parsers;

use App\Importers\Models\NormalizedProduct;
use App\Importers\ProductSync;

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
        $products       = [];
        $skipped_nostock = 0;

        foreach ($this->getProductNodes() as $productNode) {
            // Strict skip: any product whose raw <Stock> is < 1 is not imported.
            // Reading the tag directly (not the parsed stock_status) so that the
            // availability-text fallback in parseStockStatus() doesn't keep
            // out-of-stock items alive. This saves ~70s/product on import.
            $raw_stock = (float) str_replace(',', '.', (string) $productNode->Stock);
            if ($raw_stock < 1) {
                $skipped_nostock++;
                continue;
            }

            try {
                $product = $this->parseProduct($productNode);
                if ($product->validate() === true) {
                    $products[] = $product;
                }
            } catch (\Exception $e) {
                error_log("B2BMarkt Parser Error: " . $e->getMessage());
            }
        }

        if ($skipped_nostock > 0) {
            error_log("B2BMarktParser: Skipped {$skipped_nostock} products with Stock < 1.");
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
            // Sub-material groups ("Υλικό τραπεζιού", "Υλικό καρέκλας", …) are
            // folded into a single canonical "υλικό" so they land in the
            // pa_yliko global taxonomy instead of becoming per-part local attrs.
            $ylikoValues = [];

            foreach ($node->Filters->Filter as $filter) {
                $group = $this->getNodeValue($filter->Group);
                $value = $this->getNodeValue($filter->Value);
                if (in_array($group, $skipGroups, true)) {
                    continue;
                }
                if (empty($group) || empty($value)) {
                    continue;
                }
                if (mb_strpos($group, 'Υλικό', 0, 'UTF-8') === 0) {
                    $ylikoValues[] = $value;
                    continue;
                }
                $product->attributes[] = [
                    'name' => $attributeRenames[$group] ?? $group,
                    'value' => $value
                ];
            }

            if (!empty($ylikoValues)) {
                $product->attributes[] = [
                    'name'  => 'υλικό',
                    'value' => implode(', ', array_unique($ylikoValues)),
                ];
            }
        }

        // Fallback when the feed omits the Απόχρωση filter (either via
        // <Filters xsi:nil="true"/> or simply not including that group): scan
        // the <Name> for canonical color keywords. Description is intentionally
        // skipped — it often mentions material/base colors that aren't the
        // product's primary color (e.g. "βάση από φυσικό γρανίτη").
        $hasColor = false;
        foreach ($product->attributes as $existing) {
            if (mb_strtolower($existing['name'], 'UTF-8') === 'χρώμα') {
                $hasColor = true;
                break;
            }
        }
        if (!$hasColor) {
            $name = $this->getNodeValue($node->Name);
            if ($name !== '') {
                $detected = ProductSync::detectColors($name);
                if (!empty($detected)) {
                    $product->attributes[] = [
                        'name'  => 'χρώμα',
                        'value' => implode(' ', $detected),
                    ];
                }
            }
        }

        // Fallback when the feed omits a Υλικό-style group entirely: scan the
        // <Name> for canonical material keywords. ~31% of B2BMarkt in-stock
        // products have no Υλικό filter (Στόμα <Filters xsi:nil="true"/> or
        // filters that only carry Απόχρωση/Είδος/Τύπος). Description is
        // intentionally skipped — it often mentions secondary/base materials
        // that aren't the product's primary material.
        $hasMaterial = false;
        foreach ($product->attributes as $existing) {
            if (mb_strtolower($existing['name'], 'UTF-8') === 'υλικό') {
                $hasMaterial = true;
                break;
            }
        }
        if (!$hasMaterial) {
            $name = $this->getNodeValue($node->Name);
            if ($name !== '') {
                $detected = ProductSync::detectMaterials($name);
                if (!empty($detected)) {
                    $product->attributes[] = [
                        'name'  => 'υλικό',
                        'value' => implode(', ', $detected),
                    ];
                }
            }
        }

        // Ensure "υλικό" is always the first attribute (after all fallbacks).
        usort($product->attributes, function (array $a, array $b): int {
            $aIsYliko = mb_strtolower($a['name'], 'UTF-8') === 'υλικό';
            $bIsYliko = mb_strtolower($b['name'], 'UTF-8') === 'υλικό';
            if ($aIsYliko && !$bIsYliko) return -1;
            if (!$aIsYliko && $bIsYliko) return 1;
            return 0;
        });

        // Weight
        $product->weight = $this->parsePrice($this->getNodeValue($node->Weight));

        // Shipping packs: per-box data from <Packs><Pack>. These describe how
        // the product ships (one or more cartons), NOT the open product size,
        // so they go into meta rather than the WC dimensions fields. The
        // sibling <DimensionsData> node is intentionally ignored — it carries
        // the same packaging info as an HTML table and we already have the
        // structured form here.
        $product->shipping_packs = $this->parseShippingPacks($node);

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
     *   …/data/xml_files/enhanced/b2bmarkt-enhanced.xml
     * → …/data/xml_files/gr/b2bmarkt.xml
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

        $result = null;

        // Pass 0: labeled diameter in metres ("Φ3 μέτρα ... Ύψος: 2,40μ") in the
        // dimensions block. Runs first because the generic Pass-1 regex matches
        // component specs that appear later in the same block (e.g. "Φ48x1,2mm"
        // for an aluminium pole) and would otherwise win. parseLabeledDiameter
        // requires explicit metre units, so it safely skips mm/cm specs.
        $result = $this->parseLabeledDiameter($dimBlock);

        // Pass 0.5: labelled "Διάσταση: N×N(×N)? unit" line. Wins over generic
        // passes for the same reason — the labelled line is the product's own
        // declaration, while loose triples later in the same block may belong
        // to a component (radius/pole/base).
        if ($result === null) {
            $result = $this->findLabeledDimsLine($dimBlock);
        }

        // Pass 1: 3-value-equivalent matches (triple OR diameter+height).
        // Within each source we pick whichever pattern STARTS EARLIEST so that
        // a primary "Διαστάσεις: Φ152x74" wins over a later "Κλειστό 152x152x6",
        // while a primary triple "70x70x72" still wins over an incidental
        // "Φ25x0.8mm" tube spec that comes after it.
        if ($result === null) {
            foreach ($sources as $text) {
                if ($text === '') continue;
                $best = $this->earliest([
                    $this->findTripleWithPos($text),
                    $this->findDiameterHeightWithPos($text),
                ]);
                if ($best !== null) {
                    $result = $best['result'];
                    break;
                }
            }
        }

        // Pass 2: 2-value matches — a pair OR a diameter alone (width=length=Ø).
        if ($result === null) {
            foreach ($sources as $text) {
                if ($text === '') continue;
                $best = $this->earliest([
                    $this->findPairWithPos($text),
                    $this->findDiameterAloneWithPos($text),
                ]);
                if ($best !== null) {
                    $result = $best['result'];
                    break;
                }
            }
        }

        // Fallback: labeled "Μήκος / Πλάτος / Ύψος" patterns in description.
        if ($result === null) {
            $labeled = $this->parseLabeledDims($desc);
            if (is_array($labeled)) {
                $result = $this->dimsToWLH($labeled);
            }
        }

        // Last resort: labeled diameter "διάμετρο X μέτρα" in description (umbrellas).
        if ($result === null) {
            $result = $this->parseLabeledDiameter($desc);
        }

        // Post-processing: if we have width/length but no height (typical of
        // products whose dimensions come from a 2-value title), try to recover
        // an overall height from the description ("ΣΥΝΟΛΙΚΟ ΥΨΟΣ ΟΜΠΡΕΛΑΣ: N εκ.").
        if ($result !== null && empty($result['height'])) {
            $height = $this->parseTotalHeight($desc);
            if ($height !== null) {
                $result['height'] = $height;
            }
        }

        return $result;
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
        if (preg_match('/ύψος[\s:]+(\d+(?:[.,]\d+)?)\s*(μέτρα|μ\.?)/iu', $text, $m)) {
            $height = $this->convertOneToCm($this->toFloat($m[1]), $m[2] ?? '');
        }

        return ['width' => $diameter, 'length' => $diameter, 'height' => $height];
    }

    /**
     * Convert a single value to centimetres based on its captured unit suffix.
     * Unit comparison is lowercased so "Μ" (e.g. "Διάσταση: 4x4 Μ") is
     * recognised the same as "μ".
     */
    private function convertOneToCm(float $v, string $unit): float {
        $u = mb_strtolower(trim(rtrim($unit, '.')), 'UTF-8');
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
        // mb_stripos is case-insensitive but NOT tonos-insensitive — "Διάσταση"
        // (with tonos on alpha) wouldn't anchor against the literal "Διαστ".
        // Use a regex that accepts both α/ά variants for the alpha slot.
        if (preg_match('/[Δδ][ιΙ][αάΑΆ][σΣ][τΤ]/u', $text, $m, PREG_OFFSET_CAPTURE)) {
            return substr($text, $m[0][1]);
        }
        return '';
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

    /**
     * Find a labelled "Διάσταση(...): N×N(×N)? unit?" declaration. Preferred
     * over the generic Pass-1 regex because that one happily picks up component
     * triples (e.g. a tube spec "2,4χ1,4χ40εκ" inside a Radius section) when
     * the product's own labelled line is only a 2-value pair. The label can be
     * "Διάσταση", "Διαστάσεις", or any tonos/case variant; trailing Greek
     * letters between the label root and the colon (e.g. "Διαστάσεις σκίαστρου")
     * are also accepted.
     */
    private function findLabeledDimsLine(string $text): ?array {
        if ($text === '') return null;
        // Allow Greek qualifier words between the label root and the colon,
        // e.g. "Διαστ κρεβατιού: 186x80 εκ." or "Διαστάσεις σκίαστρου: 2,38x2,2".
        // Capped at 30 chars so a runaway match can't span paragraphs.
        // Optional [Φφ] flag right after the label captures diameter+height
        // patterns like "Διαστάσεις: Φ152x74 εκ." (diameter 152, height 74).
        $re = '/[Δδ][ιΙ][αάΑΆ][σΣ][τΤ][\p{Greek}\s]{0,30}:?\s*([Φφ])?\s*'
            . self::RE_NUM . '\s*' . self::RE_SEP . '\s*'
            . self::RE_NUM . '(?:\s*' . self::RE_SEP . '\s*' . self::RE_NUM . ')?'
            . self::RE_UNIT . '/iu';
        if (!preg_match($re, $text, $m)) {
            return null;
        }
        $isDiameter = !empty($m[1]);
        $unit = $m[5] ?? '';
        if ($isDiameter) {
            // "Φ N × M" → diameter (=width=length) + height. A third number,
            // if present, is ignored (B2BMarkt doesn't use Φ-prefixed triples).
            $d = $this->convertOneToCm($this->toFloat($m[2]), $unit);
            $h = $this->convertOneToCm($this->toFloat($m[3]), $unit);
            return ['width' => $d, 'length' => $d, 'height' => $h];
        }
        if (!empty($m[4])) {
            $vals = [$this->toFloat($m[2]), $this->toFloat($m[3]), $this->toFloat($m[4])];
        } else {
            $vals = [$this->toFloat($m[2]), $this->toFloat($m[3])];
        }
        $vals = $this->applyUnit($vals, $unit);
        return $this->dimsToWLH($vals);
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
     * Unit comparison is lowercased so "Μ" / "ΜΈΤΡΑ" are treated as metres.
     */
    private function applyUnit(array $vals, string $unit): array {
        $u = mb_strtolower(trim(rtrim($unit, '.')), 'UTF-8');
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
        // Character classes cover both tonos and non-tonos vowels: Unicode case
        // folding (the `i` flag) handles upper/lower for the same letter, but
        // does NOT bridge "ή" ↔ "η" or "Ύ" ↔ "Υ" — those are distinct chars.
        $patterns = [
            'length' => '/Μ[ήη]κος[\s:]*\(?\s*(\d+(?:[.,]\d+)?)\s*(μ|εκ|cm|mm)?/iu',
            'width'  => '/Π[λΛ][άα]τος[\s:]*\(?\s*(\d+(?:[.,]\d+)?)\s*(μ|εκ|cm|mm)?/iu',
            'height' => '/[ΎΥ]ψος[\s:]*\(?\s*(\d+(?:[.,]\d+)?)\s*(μ|εκ|cm|mm)?/iu',
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

    /**
     * Find an overall/total height when the primary dimension match left
     * height empty (e.g. when W×L came from the title alone). Two tiers:
     *   1) "ΣΥΝΟΛΙΚ- ΥΨΟΣ [whatever]: N εκ./μ" — preferred, used to disambiguate
     *      from component specs like "ΥΨΟΣ ΣΤΥΛΟΥ: 2,50μ".
     *   2) Strict "ΥΨΟΣ: N εκ./μ" — only when no intervening Greek words
     *      appear between the label and the value.
     */
    private function parseTotalHeight(string $text): ?float {
        if ($text === '') {
            return null;
        }
        $patterns = [
            // Tier 1: requires a "συνολικ-" qualifier somewhere before "Ύψος".
            '/συνολικ\S*\s+[ΎΥ]ψος[\p{Greek}\s]*?[\s:]+(\d+(?:[.,]\d+)?)' . self::RE_UNIT . '/iu',
            // Tier 2: strict label, no extra words between label and number.
            '/[ΎΥ]ψος[\s:]+(\d+(?:[.,]\d+)?)' . self::RE_UNIT . '/iu',
            // Tier 3: one Greek qualifier word allowed, e.g. "Ύψος στρώματος: 19
            // εκ." or "Ύψος ομπρέλας: 3.90μ". Best-effort — may pick up a
            // component height (e.g. "Ύψος μπράτσων") when the product has no
            // proper product-level label.
            '/[ΎΥ]ψος\s+\p{Greek}+[\s:]+(\d+(?:[.,]\d+)?)' . self::RE_UNIT . '/iu',
        ];
        foreach ($patterns as $re) {
            if (preg_match($re, $text, $m)) {
                $value = $this->toFloat($m[1]);
                $unit  = $m[2] ?? '';
                $cm    = $this->convertOneToCm($value, $unit);
                // Known B2BMarkt typo: "ύψος 2,50 εκ." (or "cm") where the
                // supplier meant 2,50 μ. The literal reading gives a sub-10cm
                // height for what is obviously a tall product, so auto-correct
                // by treating the value as if it were in metres — 2,50 → 250
                // (cm). Other sub-10cm matches (mm or no unit) are skipped.
                if ($cm < 10) {
                    $unitLower = mb_strtolower(trim(rtrim($unit, '.')), 'UTF-8');
                    if ($unitLower === 'εκ' || $unitLower === 'cm') {
                        return $value * 100;
                    }
                    continue;
                }
                return $cm;
            }
        }
        return null;
    }

    /**
     * Parse <Packs><Pack> entries into an array of pack records.
     * DimX/Y/Z are in metres in the feed; we store cm for consistency with the
     * rest of the dimensions in the store. Weight stays in kg, volume in m³.
     */
    private function parseShippingPacks($node): array {
        $packs = [];
        if (!isset($node->Packs->Pack)) {
            return $packs;
        }
        foreach ($node->Packs->Pack as $pack) {
            $length_m = $this->toFloat((string) $pack->DimX);
            $width_m  = $this->toFloat((string) $pack->DimY);
            $height_m = $this->toFloat((string) $pack->DimZ);
            $packs[] = [
                'description'     => (string) $pack->Description,
                'length_cm'       => $length_m > 0 ? round($length_m * 100, 2) : null,
                'width_cm'        => $width_m  > 0 ? round($width_m  * 100, 2) : null,
                'height_cm'       => $height_m > 0 ? round($height_m * 100, 2) : null,
                'gross_weight_kg' => $this->toFloat((string) $pack->GrossWeight) ?: null,
                'net_weight_kg'   => $this->toFloat((string) $pack->NetsWeight)  ?: null,
                'volume_m3'       => $this->toFloat((string) $pack->MainVolume)  ?: null,
                'qty'             => (int) ($pack->Qty ?? 1) ?: 1,
            ];
        }
        return $packs;
    }
}
