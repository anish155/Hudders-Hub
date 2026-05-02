<?php
$activePage = 'bakery';
$categoryName = 'Bakery';
$categoryIcon = 'bakery_dining';
$categoryDescription = 'Artisan breads, pastries, and cakes baked fresh every morning.';
$categoryAccent = '#EF6C00';
$categoryBg = '#FFF3E0';

include 'nav-bar.php';
$subcategories = ['All', 'Bread', 'Pastries', 'Cakes'];

$products = [
  ['name' => 'Sourdough Loaf', 'price' => '£3.50', 'original_price' => '', 'image' => 'Asstes/Item-image/sourdoughloaf.png', 'stock' => 'in-stock', 'unit' => 'per loaf', 'is_sale' => false, 'is_new' => true, 'subcategory' => 'Bread'],
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