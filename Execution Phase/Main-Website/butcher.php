<?php
$activePage = 'butcher';
$categoryName = 'Butcher';
$categoryIcon = 'restaurant';
$categoryDescription = 'Premium cuts of locally sourced meat, prepared by master butchers.';
$categoryAccent = '#C62828';
$categoryBg = '#FFEBEE';

include 'nav-bar.php';
$subcategories = ['All', 'Beef', 'Poultry', 'Lamb', 'Pork'];

$products = [
  ['name' => 'Fresh chicken breast', 'price' => '£6.50', 'original_price' => '', 'image' => 'Asstes/Item-image/chickenbreast.png', 'stock' => 'in-stock', 'unit' => 'per kg', 'is_sale' => false, 'is_new' => false, 'subcategory' => 'Poultry'],
  ['name' => 'Dry aged steak', 'price' => '£9.00', 'original_price' => '£12.00', 'image' => 'Asstes/Item-image/agedsteak.png', 'stock' => 'in-stock', 'unit' => 'per 250g', 'is_sale' => true, 'is_new' => false, 'subcategory' => 'Beef'],
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