<?php
$activePage = 'delicatessen';
$categoryName = 'Delicatessen';
$categoryIcon = 'lunch_dining';
$categoryDescription = 'Fine cheeses, cured meats, and gourmet pantry essentials.';
$categoryAccent = '#6A1B9A';
$categoryBg = '#F3E5F5';

include 'nav-bar.php';
$subcategories = ['All', 'Cheese', 'Charcuterie', 'Olives & Preserves'];

$products = [
  ['name' => 'Mature Cheddar', 'price' => '£4.20', 'original_price' => '£5.50', 'image' => 'Asstes/Item-image/cheddar.png', 'stock' => 'in-stock', 'unit' => 'per 200g', 'is_sale' => true, 'is_new' => false, 'subcategory' => 'Cheese'],
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