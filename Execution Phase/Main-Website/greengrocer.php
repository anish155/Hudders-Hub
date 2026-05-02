<?php
$activePage = 'greengrocer';
$categoryName = 'Greengrocer';
$categoryIcon = 'eco';
$categoryDescription = 'Farm-fresh organic vegetables and seasonal fruits harvested locally.';
$categoryAccent = '#2E7D32';
$categoryBg = '#E8F5E9';

include 'nav-bar.php';
$subcategories = ['All', 'Vegetables', 'Fruits', 'Organic', 'Exotic'];

$products = [
  ['name' => 'Green Bell Pepper', 'price' => '£0.90', 'original_price' => '£1.50', 'image' => 'Asstes/Item-image/green-bell-pepper-isolated.jpg', 'stock' => 'in-stock', 'unit' => 'per piece', 'is_sale' => true, 'is_new' => false, 'subcategory' => 'Vegetables'],
  ['name' => 'Green Broccoli', 'price' => '£1.20', 'original_price' => '', 'image' => 'Asstes/Item-image/green-broccoli.jpg', 'stock' => 'in-stock', 'unit' => 'per head', 'is_sale' => false, 'is_new' => true, 'subcategory' => 'Vegetables'],
];

function format_discount($price, $originalPrice) {
  $p = floatval(preg_replace('/[^0-9.]/', '', $price));
  $o = floatval(preg_replace('/[^0-9.]/', '', $originalPrice));
  return ($p > 0 && $o > $p) ? (int)round((1 - ($p / $o)) * 100) . '% off' : '';
}

include_once 'category-page-template.php';
renderCategoryPage($categoryName, $categoryIcon, $categoryDescription, $categoryAccent, $categoryBg, $subcategories, $products, 'format_discount');
include 'footer.php';
?>