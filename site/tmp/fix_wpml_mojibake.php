cat > /tmp/fix_wpml_mojibake.php <<'PHP'
<?php
global $wpdb;

$tables = [
  'wp_3_icl_languages',
  'wp_3_icl_languages_translations',
  'wp_3_icl_strings',
  'wp_3_icl_string_translations',
];

function looks_mojibake($s) {
  // κλασικοί δείκτες mojibake από Greek/UTF-8 που διαβάστηκε ως latin1/cp1252
  return (bool)preg_match('/[ÃÂÎÏ]/u', $s) || (strpos($s, 'â') !== false);
}

function has_greek($s) {
  return (bool)preg_match('/[Α-Ωα-ω]/u', $s);
}

function fix_once($s) {
  // 1) ISO-8859-1 -> UTF-8 (συχνά αρκεί, π.χ. Î‘ -> Α)
  $a = @utf8_encode($s);

  // 2) Αν δεν έπιασε, δοκίμασε Windows-1252 -> UTF-8 (αγνοώντας άκυρα bytes)
  if ($a === $s || looks_mojibake($a)) {
    $b = @iconv('Windows-1252', 'UTF-8//IGNORE', $s);
    if (is_string($b) && $b !== '') $a = $b;
  }

  // 3) Τελικό fallback: ISO-8859-1 -> UTF-8 με IGNORE
  if ($a === $s || looks_mojibake($a)) {
    $c = @iconv('ISO-8859-1', 'UTF-8//IGNORE', $s);
    if (is_string($c) && $c !== '') $a = $c;
  }

  return $a;
}

$wpdb->hide_errors();

foreach ($tables as $t) {
  $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $t));
  if (!$exists) { WP_CLI::log("Skip (missing): $t"); continue; }

  // Πάρε text/varchar columns
  $cols = $wpdb->get_col("
    SELECT COLUMN_NAME
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = '{$t}'
      AND DATA_TYPE IN ('varchar','text','mediumtext','longtext')
  ");

  if (!$cols) { WP_CLI::log("No text cols: $t"); continue; }

  $pk = $wpdb->get_var("
    SELECT COLUMN_NAME
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = '{$t}'
      AND COLUMN_KEY='PRI'
    LIMIT 1
  ");

  if (!$pk) { WP_CLI::log("No PK found, skip: $t"); continue; }

  WP_CLI::log("Fixing $t (pk=$pk) cols=[".implode(',', $cols)."]");

  // Διάβασε σε batches για να μην βαράμε μνήμη
  $last = 0;
  $fixed_count = 0;

  while (true) {
    $rows = $wpdb->get_results($wpdb->prepare(
      "SELECT * FROM `$t` WHERE `$pk` > %d ORDER BY `$pk` ASC LIMIT 500",
      $last
    ), ARRAY_A);

    if (!$rows) break;

    foreach ($rows as $r) {
      $last = (int)$r[$pk];
      $updates = [];

      foreach ($cols as $c) {
        $val = $r[$c];
        if (!is_string($val) || $val === '') continue;

        // Μην πειράζεις ήδη σωστά ελληνικά
        if (has_greek($val) && !looks_mojibake($val)) continue;

        // Πείραξε μόνο αν δείχνει mojibake
        if (!looks_mojibake($val)) continue;

        $new = fix_once($val);

        // κράτα αλλαγή μόνο αν βελτιώθηκε (λιγότερο mojibake) ή απέκτησε ελληνικά
        if ($new !== $val && (has_greek($new) || (!looks_mojibake($new) && looks_mojibake($val)))) {
          $updates[$c] = $new;
        }
      }

      if ($updates) {
        $wpdb->update($t, $updates, [$pk => $last]);
        $fixed_count++;
      }
    }
  }

  WP_CLI::success("Done $t. Rows updated: $fixed_count");
}

WP_CLI::success("All done.");