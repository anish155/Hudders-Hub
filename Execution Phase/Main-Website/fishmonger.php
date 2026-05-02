<?php
/**
 * HuddersHub - Fishmonger Category Page
 */

$activePage = 'fishmonger';
$categoryName = 'Fishmonger';
$categoryIcon = 'set_meal';
$categoryDescription = 'Sustainably caught fish & seafood, delivered fresh to our counter daily.';
$categoryAccent = '#0277BD';
$categoryBg = '#E1F5FE';

include 'nav-bar.php';

$subcategories = ['All', 'White Fish', 'Oily Fish', 'Shellfish', 'Smoked & Cured'];

$products = [
  // White Fish
  ['name' => 'Cod Fillet',             'price' => '£5.50', 'original_price' => '£7.00', 'image' => 'Asstes/Item-image/cod-fillet.png', 'stock' => 'in-stock',     'unit' => 'per 300g',    'is_sale' => true,  'is_new' => false, 'subcategory' => 'White Fish',    'reviews' => 398],
  ['name' => 'Haddock Fillet',         'price' => '£4.80', 'original_price' => '',       'image' => 'Asstes/Item-image/haddockfillet.png',             'stock' => 'in-stock',     'unit' => 'per 300g',    'is_sale' => false, 'is_new' => false, 'subcategory' => 'White Fish',    'reviews' => 312],
  ['name' => 'Pollock Fillet',         'price' => '£3.20', 'original_price' => '',       'image' => 'Asstes/Item-image/pollock.png', 'stock' => 'in-stock',     'unit' => 'per 300g',    'is_sale' => false, 'is_new' => true,  'subcategory' => 'White Fish',    'reviews' => 145],
  ['name' => 'Sea Bass Fillet',        'price' => '£6.90', 'original_price' => '£8.50', 'image' => 'Asstes/Item-image/seabass.png','stock' => 'in-stock',     'unit' => 'per 250g',    'is_sale' => true,  'is_new' => false, 'subcategory' => 'White Fish',    'reviews' => 227],
  ['name' => 'Plaice Fillet',          'price' => '£4.20', 'original_price' => '',       'image' => 'Asstes/Item-image/plaice.png', 'stock' => 'out-of-stock', 'unit' => 'per 250g',    'is_sale' => false, 'is_new' => false, 'subcategory' => 'White Fish',    'reviews' => 178],
  // Oily Fish
  ['name' => 'Atlantic Salmon',        'price' => '£7.20', 'original_price' => '£9.00', 'image' => 'Asstes/Item-image/green-broccoli.jpg',             'stock' => 'in-stock',     'unit' => 'per 400g',    'is_sale' => true,  'is_new' => false, 'subcategory' => 'Oily Fish',     'reviews' => 521],
  ['name' => 'Fresh Mackerel',         'price' => '£2.80', 'original_price' => '',       'image' => 'Asstes/Item-image/green-bell-pepper-isolated.jpg', 'stock' => 'in-stock',     'unit' => 'per fish',    'is_sale' => false, 'is_new' => false, 'subcategory' => 'Oily Fish',     'reviews' => 203],
  ['name' => 'Rainbow Trout',          'price' => '£5.60', 'original_price' => '',       'image' => 'Asstes/Item-image/green-broccoli.jpg',             'stock' => 'in-stock',     'unit' => 'whole fish',  'is_sale' => false, 'is_new' => true,  'subcategory' => 'Oily Fish',     'reviews' => 167],
  ['name' => 'Fresh Tuna Steak',       'price' => '£8.50', 'original_price' => '£11.00','image' => 'Asstes/Item-image/green-bell-pepper-isolated.jpg', 'stock' => 'in-stock',     'unit' => 'per 250g',    'is_sale' => true,  'is_new' => false, 'subcategory' => 'Oily Fish',     'reviews' => 289],
  // Shellfish
  ['name' => 'King Prawns',            'price' => '£6.40', 'original_price' => '',       'image' => 'Asstes/Item-image/green-broccoli.jpg',             'stock' => 'in-stock',     'unit' => '300g pack',   'is_sale' => false, 'is_new' => false, 'subcategory' => 'Shellfish',     'reviews' => 445],
  ['name' => 'Mussels',                'price' => '£3.50', 'original_price' => '£4.50', 'image' => 'Asstes/Item-image/green-bell-pepper-isolated.jpg', 'stock' => 'in-stock',     'unit' => '500g net',    'is_sale' => true,  'is_new' => false, 'subcategory' => 'Shellfish',     'reviews' => 312],
  ['name' => 'Scottish Langoustines',  'price' => '£12.00','original_price' => '',       'image' => 'Asstes/Item-image/green-broccoli.jpg',             'stock' => 'out-of-stock', 'unit' => 'per 6',       'is_sale' => false, 'is_new' => false, 'subcategory' => 'Shellfish',     'reviews' => 189],
  // Smoked
  ['name' => 'Smoked Salmon Slices',   'price' => '£4.50', 'original_price' => '£6.00', 'image' => 'Asstes/Item-image/green-bell-pepper-isolated.jpg', 'stock' => 'in-stock',     'unit' => '150g pack',   'is_sale' => true,  'is_new' => false, 'subcategory' => 'Smoked & Cured','reviews' => 604],
  ['name' => 'Smoked Mackerel Fillet', 'price' => '£3.20', 'original_price' => '',       'image' => 'Asstes/Item-image/green-broccoli.jpg',             'stock' => 'in-stock',     'unit' => 'per fillet',  'is_sale' => false, 'is_new' => true,  'subcategory' => 'Smoked & Cured','reviews' => 278],
  ['name' => 'Kippers',                'price' => '£2.60', 'original_price' => '',       'image' => 'Asstes/Item-image/green-bell-pepper-isolated.jpg', 'stock' => 'in-stock',     'unit' => 'pair',        'is_sale' => false, 'is_new' => false, 'subcategory' => 'Smoked & Cured','reviews' => 134],
];

function format_discount($price, $originalPrice) {
  $p = floatval(preg_replace('/[^0-9.]/', '', $price));
  $o = floatval(preg_replace('/[^0-9.]/', '', $originalPrice));
  if ($p <= 0 || $o <= 0 || $p >= $o) return '';
  return (int)round((1 - ($p / $o)) * 100) . '% off';
}
?>

<?php include_once 'category-page-template.php'; ?>
<?php renderCategoryPage($categoryName, $categoryIcon, $categoryDescription, $categoryAccent, $categoryBg, $subcategories, $products, 'format_discount'); ?>

<?php include 'footer.php'; ?>