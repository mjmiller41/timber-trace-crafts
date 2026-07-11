<?php
/**
 * etsy-set-attributes.php — set Etsy listing *properties* (structured attributes
 * like Capacity, Primary color, Holiday, Shape, Recipient, dimensions) that the
 * normal catalog push (App\Services\Etsy\EtsyProductSync) does NOT carry.
 *
 * WHY THIS EXISTS: Etsy structured attributes live behind the taxonomy "listing
 * properties" API (PUT /shops/{shop}/listings/{listing}/properties/{property_id}),
 * separate from title/description/tags/materials. They are keyed by numeric
 * property_id + value_id + scale_id that differ per category (taxonomy_id), so
 * you must resolve them per listing. This script does that by NAME.
 *
 * ─── RUN IT FROM PROD ───────────────────────────────────────────────────────
 * The Etsy OAuth refresh token is encrypted with the *prod* APP_KEY and only
 * decryptable on the server. Run locally and EtsyClient throws "No Etsy refresh
 * token". So scp it up and run via tinker on prod (same channel deploy.sh uses):
 *
 *   HOST=u903552178@5.183.10.138 ; PORT=65002
 *   DIR=domains/timbertracecrafts.com/public_html
 *   scp -P $PORT scripts/etsy-set-attributes.php $HOST:/tmp/e.php
 *   ssh -p $PORT $HOST "cd $DIR && MODE=discover php artisan tinker /tmp/e.php"   # explore
 *   ssh -p $PORT $HOST "cd $DIR && DRY=1 MODE=apply php artisan tinker /tmp/e.php" # dry run
 *   ssh -p $PORT $HOST "cd $DIR && MODE=apply php artisan tinker /tmp/e.php"       # apply all
 *   ssh -p $PORT $HOST "cd $DIR && ONLY=<listing_id> MODE=apply php artisan tinker /tmp/e.php"
 *   ssh -p $PORT $HOST "rm -f /tmp/e.php"                                          # clean up
 *
 * MODE=discover prints every active listing's listing_id + taxonomy_id, its
 * current properties, and — per taxonomy — the available property names with
 * their value/scale names. Copy those NAMES into $PLAN (matched case-insensitively).
 *
 * ─── GOTCHAS (learned 2026-07-11; do not relearn them) ──────────────────────
 *  1. PHP casts numeric array keys to INT. Compare listing ids as strings —
 *     (string)$lid === $ONLY — never a strict $lid === $ONLY, or it skips all.
 *  2. SCALED / numeric properties (Volume, dimensions) need value_ids => [] (an
 *     EMPTY array) + scale_id + values => ["<number>"].  Omitting value_ids ->
 *     400 "Missing input parameter: [value_ids]"; sending [0] -> 400 "IDs must
 *     be >= 1".  CHOICE properties use value_ids => [<id>], values => ["<Name>"].
 *  3. VERIFY IN THE BROWSER EDITOR, not the read API. Etsy's GET .../properties
 *     is cache-laggy and returns stale-empty right after a write even though the
 *     value is live in Shop Manager. The PUT's 200 response (it echoes the saved
 *     property) is the reliable success signal; the Shop Manager listing editor
 *     is the source of truth.
 *
 * The $PLAN below is the real set applied to the 6 live listings on 2026-07-11 —
 * keep it as a worked example, or replace it for a new job.
 */

use App\Models\Setting;
use App\Services\Etsy\EtsyClient;

$c = app(EtsyClient::class);
$shopId = Setting::get('etsy.shop_id');
$MODE = getenv('MODE') ?: 'discover';
$DRY = getenv('DRY') === '1';
$ONLY = getenv('ONLY') ?: null;

// Which taxonomy each listing belongs to is discovered at runtime (MODE=discover),
// but apply needs it up front. Leave empty to auto-fetch each listing's taxonomy.
$listingTax = [];

// ── EDIT FOR A NEW JOB. Keys are listing_ids (strings). ──────────────────────
// 'choice' => ['Property name' => ['Value name', ...]]
// 'scaled' => ['Property name' => ['Scale name', '<number-as-string>']]
$earringsButterfly = ['choice' => [
    'Primary color' => ['Brown'], 'Jewelry theme' => ['Bugs & insects'],
    'Jewelry closure type' => ['Ear wire'], 'Earring location' => ['Earlobe'],
    'Jewelry style' => ['Boho & hippie'], 'Recipient' => ['Women'],
    'Occasion' => ['Birthday'], 'Can be personalized' => ['No'], 'Material multi' => ['Wood'],
]];
$earringsTeardrop = $earringsButterfly;
unset($earringsTeardrop['choice']['Jewelry theme']);

$PLAN = [
    '4517004325' => [ // America 250 Tumbler (taxonomy 11275 Travel Mugs)
        'choice' => [
            'Primary color' => ['Gray'], 'Holiday' => ['Independence Day'],
            'Dishwasher safe' => ['Yes'], 'Microwave safe' => ['No'], 'Handle' => ['No'],
            'Insulated' => ['Yes'], 'Home graphic' => ['Animal'], 'Material multi' => ['Stainless steel'],
        ],
        'scaled' => ['Volume' => ['Fluid Ounces', '20']],
    ],
    '4511088718' => [ // Heart Jewelry Box (taxonomy 6102)
        'choice' => [
            'Primary color' => ['Brown'], 'Shape' => ['Heart'], 'Jewelry theme' => ['Love & friendship'],
            'Recipient' => ['Women'], 'Can be personalized' => ['Yes'], 'Material multi' => ['Wood'],
        ],
        'scaled' => ['Box bag length' => ['Inches', '6.25'], 'Box bag width' => ['Inches', '7'], 'Box bag height' => ['Inches', '2.875']],
    ],
    '4507368334' => $earringsButterfly, // Butterfly earrings (taxonomy 1208)
    '4507325946' => $earringsButterfly,
    '4506611612' => $earringsButterfly,
    '4505102326' => $earringsTeardrop,  // Teardrop earrings
];
// ─────────────────────────────────────────────────────────────────────────────

$taxCache = [];
$taxProps = function ($tax) use ($c, &$taxCache) {
    if (isset($taxCache[$tax])) return $taxCache[$tax];
    $tp = $c->get("/application/seller-taxonomy/nodes/$tax/properties");
    $map = [];
    foreach (($tp['results'] ?? []) as $p) {
        $vals = [];
        foreach (($p['possible_values'] ?? []) as $v) $vals[strtolower($v['name'])] = $v['value_id'];
        $scales = [];
        foreach (($p['scales'] ?? []) as $s) $scales[strtolower($s['display_name'])] = $s['scale_id'];
        $map[strtolower($p['name'])] = ['pid' => $p['property_id'], 'name' => $p['name'], 'values' => $vals, 'scales' => $scales, 'raw' => $p];
    }
    return $taxCache[$tax] = $map;
};

$activeListings = function () use ($c, $shopId) {
    return $c->get("/application/shops/$shopId/listings/active", ['limit' => 100])['results'] ?? [];
};

if ($MODE === 'discover') {
    echo "shop_id=$shopId\n";
    $seenTax = [];
    foreach ($activeListings() as $l) {
        $lid = $l['listing_id']; $tax = $l['taxonomy_id'] ?? null; $seenTax[$tax] = true;
        echo "\n### $lid  tax=$tax  ".substr($l['title'] ?? '', 0, 55)."\n";
        foreach (($c->get("/application/shops/$shopId/listings/$lid/properties")['results'] ?? []) as $p) {
            echo "   cur '{$p['property_name']}' = [".implode(',', array_map(fn ($v) => $v['value'] ?? '', $p['values'] ?? []))."]\n";
        }
    }
    foreach (array_keys($seenTax) as $tax) {
        if (! $tax) continue;
        echo "\n===== AVAILABLE (taxonomy $tax) =====\n";
        foreach ($taxProps($tax) as $p) {
            $scales = implode(',', array_map(fn ($n, $id) => "$n", array_keys($p['scales']), $p['scales']));
            $vals = array_slice(array_keys($p['values']), 0, 24);
            echo "'{$p['name']}'".($p['scales'] ? " scales[".implode(',', array_keys($p['scales']))."]" : '')."\n";
            if ($vals) echo "    values: ".implode(' | ', $vals)."\n";
        }
    }
    echo "\nDONE (discover)\n";
    return;
}

// MODE=apply — resolve taxonomy per listing if not pre-mapped.
$idToTax = [];
foreach ($activeListings() as $l) $idToTax[(string) $l['listing_id']] = $l['taxonomy_id'] ?? null;

$put = function ($lid, $pid, $body, $label) use ($c, $shopId, $DRY) {
    echo "  $label";
    if ($DRY) { echo "  [dry]\n"; return; }
    try { $c->put("/application/shops/$shopId/listings/$lid/properties/$pid", $body); echo "  OK\n"; }
    catch (\Throwable $e) { echo "  ERR ".substr($e->getMessage(), 0, 160)."\n"; }
};

foreach ($PLAN as $lid => $spec) {
    $lid = (string) $lid;
    if ($ONLY && $lid !== (string) $ONLY) continue;      // GOTCHA #1: string compare
    $tax = $listingTax[$lid] ?? $idToTax[$lid] ?? null;
    if (! $tax) { echo "\n### $lid — no taxonomy found, skipping\n"; continue; }
    $props = $taxProps($tax);
    echo "\n### $lid (tax $tax)\n";
    foreach (($spec['choice'] ?? []) as $pname => $valNames) {
        $pn = strtolower($pname);
        if (! isset($props[$pn])) { echo "  SKIP '$pname' (no such property)\n"; continue; }
        $vids = []; $vn = [];
        foreach ($valNames as $v) {
            $k = strtolower($v);
            if (! isset($props[$pn]['values'][$k])) { echo "  ! '$pname' value '$v' not found\n"; continue; }
            $vids[] = $props[$pn]['values'][$k]; $vn[] = $v;
        }
        if (! $vids) continue;
        $put($lid, $props[$pn]['pid'], ['value_ids' => $vids, 'values' => $vn], "$pname = ".implode(',', $vn));
    }
    foreach (($spec['scaled'] ?? []) as $pname => $sv) {
        $pn = strtolower($pname); [$scaleName, $num] = $sv;
        if (! isset($props[$pn])) { echo "  SKIP '$pname' (no such property)\n"; continue; }
        $sid = $props[$pn]['scales'][strtolower($scaleName)] ?? null;
        if (! $sid) { echo "  ! '$pname' scale '$scaleName' not found\n"; continue; }
        // GOTCHA #2: scaled props need value_ids => [] (empty), not omitted, not [0].
        $put($lid, $props[$pn]['pid'], ['value_ids' => [], 'values' => [(string) $num], 'scale_id' => $sid], "$pname = $num $scaleName");
    }
}
echo "\nDONE (apply".($DRY ? ', DRY' : '').") — verify in the Shop Manager listing editor, not the read API.\n";
