<?php
/**
 * HuddersHub Search Results Page
 * Matches site aesthetics and reuses product card markup.
 */

$activePage = 'search';
include 'nav-bar.php';

$searchQuery = trim($_GET['q'] ?? '');
$sort = $_GET['sort'] ?? 'relevance';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 9;

$demoProducts = [
	[
		'name' => 'Green Bell Pepper',
		'price' => '£0.90',
		'original_price' => '£1.50',
		'image' => 'Asstes/Item-image/green-bell-pepper-isolated.jpg',
		'stock' => 'in-stock',
		'unit' => 'per piece',
		'is_sale' => true,
		'is_new' => false,
		'category' => 'Fresh Produce',
		'desc' => 'Crisp, juicy peppers perfect for grilling.',
		'rating' => 4.6,
		'reviews' => 216,
	],
	[
		'name' => 'Green Broccoli',
		'price' => '£0.90',
		'original_price' => '',
		'image' => 'Asstes/Item-image/green-broccoli.jpg',
		'stock' => 'out-of-stock',
		'unit' => 'per piece',
		'is_sale' => false,
		'is_new' => true,
		'category' => 'Fresh Produce',
		'desc' => 'Farm-picked florets, rich in nutrients.',
		'rating' => 4.3,
		'reviews' => 142,
	],
	[
		'name' => 'Chinese Broccoli',
		'price' => '£0.90',
		'original_price' => '',
		'image' => 'Asstes/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
		'stock' => 'in-stock',
		'unit' => 'per bunch',
		'is_sale' => false,
		'is_new' => false,
		'category' => 'Fresh Produce',
		'desc' => 'Tender stems with a mild, sweet bite.',
		'rating' => 4.5,
		'reviews' => 98,
	],
	[
		'name' => 'Vine Tomatoes',
		'price' => '£1.20',
		'original_price' => '£1.60',
		'image' => 'Asstes/Item-image/green-bell-pepper-isolated.jpg',
		'stock' => 'in-stock',
		'unit' => 'per pack',
		'is_sale' => true,
		'is_new' => false,
		'category' => 'Fresh Produce',
		'desc' => 'Juicy tomatoes, ideal for salads.',
		'rating' => 4.7,
		'reviews' => 186,
	],
	[
		'name' => 'Granny Smith Apples',
		'price' => '£2.40',
		'original_price' => '',
		'image' => 'Asstes/Item-image/green-bell-pepper-isolated.jpg',
		'stock' => 'in-stock',
		'unit' => 'per 1kg',
		'is_sale' => false,
		'is_new' => true,
		'category' => 'Fruit',
		'desc' => 'Tart, crunchy, and full of flavor.',
		'rating' => 4.8,
		'reviews' => 241,
	],
	[
		'name' => 'Golden Potatoes',
		'price' => '£1.10',
		'original_price' => '',
		'image' => 'Asstes/Item-image/green-broccoli.jpg',
		'stock' => 'in-stock',
		'unit' => 'per 1kg',
		'is_sale' => false,
		'is_new' => false,
		'category' => 'Fresh Produce',
		'desc' => 'Creamy texture, perfect for roasting.',
		'rating' => 4.2,
		'reviews' => 120,
	],
	[
		'name' => 'Local Honey Jar',
		'price' => '£4.50',
		'original_price' => '',
		'image' => 'Asstes/Item-image/green-bell-pepper-isolated.jpg',
		'stock' => 'in-stock',
		'unit' => '250g',
		'is_sale' => false,
		'is_new' => false,
		'category' => 'Pantry',
		'desc' => 'Small-batch honey from Huddersfield apiaries.',
		'rating' => 4.9,
		'reviews' => 88,
	],
	[
		'name' => 'Organic Spinach',
		'price' => '£1.35',
		'original_price' => '£1.80',
		'image' => 'Asstes/Item-image/green-broccoli.jpg',
		'stock' => 'in-stock',
		'unit' => 'per bag',
		'is_sale' => true,
		'is_new' => false,
		'category' => 'Fresh Produce',
		'desc' => 'Tender leaves for quick sautes.',
		'rating' => 4.4,
		'reviews' => 132,
	],
	[
		'name' => 'Sourdough Loaf',
		'price' => '£3.25',
		'original_price' => '',
		'image' => 'Asstes/Item-image/green-bell-pepper-isolated.jpg',
		'stock' => 'in-stock',
		'unit' => 'per loaf',
		'is_sale' => false,
		'is_new' => true,
		'category' => 'Bakery',
		'desc' => 'Crusty outside, soft inside.',
		'rating' => 4.6,
		'reviews' => 64,
	],
	[
		'name' => 'Free Range Eggs',
		'price' => '£2.10',
		'original_price' => '',
		'image' => 'Asstes/Item-image/green-broccoli.jpg',
		'stock' => 'out-of-stock',
		'unit' => '6 pack',
		'is_sale' => false,
		'is_new' => false,
		'category' => 'Dairy',
		'desc' => 'Golden yolks from local farms.',
		'rating' => 4.5,
		'reviews' => 110,
	],
	[
		'name' => 'Greek Yogurt',
		'price' => '£1.75',
		'original_price' => '£2.20',
		'image' => 'Asstes/Item-image/green-bell-pepper-isolated.jpg',
		'stock' => 'in-stock',
		'unit' => '500g',
		'is_sale' => true,
		'is_new' => false,
		'category' => 'Dairy',
		'desc' => 'Thick, creamy, and high in protein.',
		'rating' => 4.7,
		'reviews' => 156,
	],
	[
		'name' => 'Roasted Coffee Beans',
		'price' => '£6.90',
		'original_price' => '',
		'image' => 'Asstes/Item-image/green-broccoli.jpg',
		'stock' => 'in-stock',
		'unit' => '250g',
		'is_sale' => false,
		'is_new' => false,
		'category' => 'Pantry',
		'desc' => 'Medium roast with caramel notes.',
		'rating' => 4.8,
		'reviews' => 77,
	],
	[
		'name' => 'Seasonal Strawberries',
		'price' => '£2.80',
		'original_price' => '£3.50',
		'image' => 'Asstes/Item-image/green-bell-pepper-isolated.jpg',
		'stock' => 'in-stock',
		'unit' => '400g',
		'is_sale' => true,
		'is_new' => false,
		'category' => 'Fruit',
		'desc' => 'Sweet, fragrant berries for desserts.',
		'rating' => 4.9,
		'reviews' => 204,
	],
	[
		'name' => 'Herb Butter',
		'price' => '£1.95',
		'original_price' => '',
		'image' => 'Asstes/Item-image/green-broccoli.jpg',
		'stock' => 'in-stock',
		'unit' => '200g',
		'is_sale' => false,
		'is_new' => true,
		'category' => 'Dairy',
		'desc' => 'Small-batch butter with fresh herbs.',
		'rating' => 4.4,
		'reviews' => 51,
	],
];

$defaultDescription = 'Fresh, crisp, and packed with goodness.';
$defaultReviews = 216;

function format_discount_text($price, $originalPrice) {
	$priceValue = floatval(preg_replace('/[^0-9.]/', '', $price));
	$originalValue = floatval(preg_replace('/[^0-9.]/', '', $originalPrice));
	if ($priceValue <= 0 || $originalValue <= 0 || $priceValue >= $originalValue) {
		return '';
	}
	$percent = (int) round((1 - ($priceValue / $originalValue)) * 100);
	return $percent . '% off';
}

function product_price_value($price) {
	return floatval(preg_replace('/[^0-9.]/', '', $price));
}

$filteredProducts = array_values(array_filter($demoProducts, function ($product) use ($searchQuery) {
	if ($searchQuery === '') {
		return true;
	}
	$needle = strtolower($searchQuery);
	$haystack = strtolower(
		($product['name'] ?? '') . ' ' .
		($product['category'] ?? '') . ' ' .
		($product['desc'] ?? '')
	);
	return strpos($haystack, $needle) !== false;
}));

switch ($sort) {
	case 'price-asc':
		usort($filteredProducts, function ($a, $b) {
			return product_price_value($a['price']) <=> product_price_value($b['price']);
		});
		break;
	case 'price-desc':
		usort($filteredProducts, function ($a, $b) {
			return product_price_value($b['price']) <=> product_price_value($a['price']);
		});
		break;
	case 'name-asc':
		usort($filteredProducts, function ($a, $b) {
			return strcmp($a['name'], $b['name']);
		});
		break;
	case 'name-desc':
		usort($filteredProducts, function ($a, $b) {
			return strcmp($b['name'], $a['name']);
		});
		break;
	case 'rating':
		usort($filteredProducts, function ($a, $b) {
			return ($b['rating'] ?? 0) <=> ($a['rating'] ?? 0);
		});
		break;
	default:
		break;
}

$totalResults = count($filteredProducts);
$totalPages = max(1, (int) ceil($totalResults / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$pagedResults = array_slice($filteredProducts, $offset, $perPage);

$showingStart = $totalResults ? $offset + 1 : 0;
$showingEnd = $totalResults ? min($offset + $perPage, $totalResults) : 0;

function build_search_url($overrides) {
	global $searchQuery, $sort, $page;
	$params = array_merge([
		'q' => $searchQuery,
		'sort' => $sort,
		'page' => $page,
	], $overrides);
	$params = array_filter($params, function ($value) {
		return $value !== '' && $value !== null;
	});
	return 'search-page.php' . ($params ? '?' . http_build_query($params) : '');
}

$sortOptions = [
	'relevance' => 'Relevance',
	'price-asc' => 'Price: Low to High',
	'price-desc' => 'Price: High to Low',
	'rating' => 'Rating',
	'name-asc' => 'Name: A to Z',
	'name-desc' => 'Name: Z to A',
];

$queryLabel = $searchQuery !== '' ? $searchQuery : 'All items';
?>

<style>
	.search-page {
		background: #F7F6F3;
		padding: 36px 0 70px;
		min-height: 70vh;
	}

	.search-shell {
		display: grid;
		grid-template-columns: minmax(240px, 280px) 1fr;
		gap: 24px;
		align-items: start;
	}

	.results-head {
		display: flex;
		align-items: flex-end;
		justify-content: space-between;
		gap: 20px;
		margin-bottom: 22px;
	}

	.results-head .eyebrow {
		text-transform: uppercase;
		letter-spacing: 0.2em;
		font-size: 11px;
		color: #5E6A63;
		margin-bottom: 8px;
		font-weight: 700;
	}

	.results-head h1 {
		font-size: 32px;
		line-height: 1.1;
		color: #0F260B;
		margin-bottom: 6px;
	}

	.results-head .results-count {
		font-size: 14px;
		color: #5E6A63;
	}

	.results-meta {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 18px;
		background: #FFFFFF;
		border: 1px solid #DCE3DA;
		padding: 14px 16px;
		box-shadow: 0 14px 28px rgba(15, 38, 11, 0.12);
		border-radius: 0;
		margin-bottom: 18px;
	}

	.results-meta .pill {
		display: inline-flex;
		align-items: center;
		gap: 8px;
		font-size: 12px;
		font-weight: 700;
		text-transform: uppercase;
		letter-spacing: 0.16em;
		color: #0F260B;
	}

	.sort-control {
		display: inline-flex;
		align-items: center;
		gap: 10px;
		font-size: 13px;
		font-weight: 600;
		color: #111827;
	}

	.sort-control select {
		border: 1px solid #C8D1C6;
		padding: 8px 12px;
		border-radius: 0;
		background: #FFFFFF;
		font-size: 13px;
		font-weight: 600;
		color: #111827;
		cursor: pointer;
	}

	.filters-panel {
		position: sticky;
		top: 150px;
	}

	.filter-card {
		background: #FFFFFF;
		border: 1px solid #DCE3DA;
		box-shadow: 0 14px 28px rgba(15, 38, 11, 0.12);
		padding: 18px;
		border-radius: 0;
	}

	.filter-card h3 {
		font-size: 18px;
		color: #0F260B;
		margin-bottom: 18px;
	}

	.filter-group {
		margin-bottom: 18px;
		padding-bottom: 16px;
		border-bottom: 1px solid rgba(15, 38, 11, 0.08);
	}

	.filter-group:last-child {
		border-bottom: none;
		padding-bottom: 0;
		margin-bottom: 0;
	}

	.filter-group h4 {
		font-size: 13px;
		text-transform: uppercase;
		letter-spacing: 0.16em;
		color: #5E6A63;
		margin-bottom: 12px;
	}

	.filter-option {
		display: flex;
		align-items: center;
		gap: 10px;
		font-size: 14px;
		color: #111827;
		margin-bottom: 10px;
	}

	.filter-option input {
		accent-color: #0F260B;
	}

	.price-range {
		display: flex;
		gap: 10px;
	}

	.price-range input {
		width: 100%;
		border: 1px solid #C8D1C6;
		border-radius: 0;
		padding: 8px 10px;
		font-size: 13px;
	}

	.filter-actions {
		display: flex;
		gap: 10px;
	}

	.filter-actions a,
	.filter-actions button {
		flex: 1;
		text-align: center;
		padding: 10px 12px;
		border-radius: 0;
		font-size: 12px;
		font-weight: 700;
		text-transform: uppercase;
		letter-spacing: 0.12em;
		border: 1px solid #0F260B;
		background: transparent;
		color: #0F260B;
		text-decoration: none;
		cursor: pointer;
		transition: all 0.3s ease;
	}

	.filter-actions button {
		background: #0F260B;
		color: #FFFFFF;
	}

	.filter-actions a:hover {
		background: #0F260B;
		color: #FFFFFF;
	}

	.filter-actions button:hover {
		background: #091406;
	}

	.results-panel {
		display: flex;
		flex-direction: column;
		gap: 18px;
	}

	.products-grid {
		display: grid;
		grid-template-columns: repeat(3, minmax(220px, 1fr));
		gap: 20px;
		align-items: stretch;
	}

	.product-card {
		background: transparent;
		border: none;
		cursor: pointer;
		height: 100%;
	}

	.product-card.is-out-of-stock {
		opacity: 0.7;
	}

	.product-card.is-out-of-stock .product-image img {
		filter: grayscale(100%);
	}

	.product-card-inner {
		background: #FFFFFF;
		border-radius: 0;
		padding: 12px;
		transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.35s cubic-bezier(0.4, 0, 0.2, 1), border-color 0.35s cubic-bezier(0.4, 0, 0.2, 1), background-color 0.35s cubic-bezier(0.4, 0, 0.2, 1);
		box-shadow: 0 16px 30px rgba(15, 38, 11, 0.12);
		position: relative;
		overflow: hidden;
		border: 1px solid #DCE3DA;
		cursor: pointer;
		display: flex;
		flex-direction: column;
		height: 100%;
		will-change: transform, box-shadow, border-color;
	}

	.product-card:hover .product-card-inner,
	.product-card-inner:hover {
		box-shadow: 0 24px 50px rgba(15, 38, 11, 0.22);
		transform: translateY(-8px) scale(1.015);
		border-color: rgba(255, 94, 58, 0.72);
		background: #FFFDF9;
	}

	.product-image-wrapper {
		position: relative;
		margin-bottom: 12px;
		background: transparent;
		border: none;
		border-radius: 0;
		padding: 0;
	}

	.stock-badge {
		position: absolute;
		top: 10px;
		left: 10px;
		font-size: 11px;
		font-family: 'Plus Jakarta Sans', sans-serif;
		font-weight: 700;
		text-transform: uppercase;
		letter-spacing: 0.4px;
		padding: 4px 10px;
		z-index: 2;
		border-radius: 0;
		background: #0F260B;
		color: #E6F5C9;
		border: 1px solid rgba(15, 38, 11, 0.45);
	}

	.stock-badge.new {
		background: #0F260B;
		color: #D6F0A7;
	}

	.stock-badge.sale {
		background: #0F260B;
		color: #E6F5C9;
		border: 1px solid rgba(15, 38, 11, 0.45);
	}

	.stock-badge.out {
		background: #3B3B3B;
		color: #FFFFFF;
		border: 1px solid rgba(0, 0, 0, 0.35);
	}

	.favorite-btn {
		position: absolute;
		top: -6px;
		right: 6px;
		width: 32px;
		height: 32px;
		background: rgba(255, 255, 255, 0.9);
		border: none;
		cursor: pointer;
		display: flex;
		align-items: center;
		justify-content: center;
		z-index: 2;
		transition: all 0.3s ease;
		padding: 0;
		border-radius: 0;
		box-shadow: 0 8px 18px rgba(15, 38, 11, 0.12);
	}

	.favorite-btn:hover {
		transform: translateY(-1px) scale(1.05);
	}

	.favorite-btn .material-icons-outlined {
		font-size: 18px;
		color: #9E9E9E;
		transition: all 0.3s ease;
	}

	.favorite-btn:hover .material-icons-outlined {
		color: var(--secondary-color, #0F260B);
	}

	.product-image {
		width: 100%;
		aspect-ratio: 1/1;
		border-radius: 0;
		overflow: hidden;
		background: transparent;
		display: flex;
		align-items: center;
		justify-content: center;
		margin-top: 0;
	}

	.product-image img {
		width: 100%;
		height: 100%;
		object-fit: contain;
		transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
	}

	.product-card:hover .product-image img {
		transform: scale(1.08);
	}

	.product-info {
		padding: 8px 4px 2px 4px;
		display: flex;
		flex-direction: column;
		flex: 1;
	}

	.product-name {
		font-family: 'Plus Jakarta Sans', sans-serif;
		font-size: 20px;
		font-weight: 700;
		color: #0F260B;
		margin-bottom: 6px;
		letter-spacing: -0.01em;
		line-height: 1.1;
		display: -webkit-box;
		-webkit-line-clamp: 2;
		-webkit-box-orient: vertical;
		overflow: hidden;
		min-height: 2.2em;
	}

	.product-desc {
		font-size: 12px;
		color: #6B7280;
		margin-bottom: 12px;
		line-height: 1.5;
		display: -webkit-box;
		-webkit-line-clamp: 2;
		-webkit-box-orient: vertical;
		overflow: hidden;
		min-height: 3em;
	}

	.price-row {
		display: flex;
		align-items: baseline;
		justify-content: space-between;
		gap: 12px;
		margin-bottom: 12px;
	}

	.price-group {
		display: inline-flex;
		align-items: baseline;
		gap: 10px;
	}

	.original-price {
		font-family: 'Plus Jakarta Sans', sans-serif;
		font-size: 14px;
		color: #9CA3AF;
		text-decoration: line-through;
		font-weight: 400;
	}

	.current-price {
		font-family: 'Plus Jakarta Sans', sans-serif;
		font-size: 22px;
		font-weight: 700;
		color: #0F260B;
		letter-spacing: -0.02em;
	}

	.discount-text {
		font-size: 12px;
		font-weight: 700;
		color: #FF3B00;
		background: #FFE2D4;
		padding: 3px 8px;
		border-radius: 0;
		white-space: nowrap;
		text-transform: uppercase;
		letter-spacing: 0.02em;
	}

	.rating-row {
		display: inline-flex;
		align-items: center;
		gap: 8px;
		margin-bottom: 12px;
		color: #F4B740;
	}

	.rating-stars {
		display: inline-flex;
		align-items: center;
		gap: 2px;
	}

	.rating-stars .material-icons-outlined {
		font-size: 16px;
	}

	.rating-count {
		font-size: 12px;
		color: #6B7280;
	}

	.add-to-cart-btn {
		width: 100%;
		display: inline-flex;
		align-items: center;
		justify-content: center;
		gap: 8px;
		padding: 10px 12px;
		background: #0B1C08;
		color: #FFFFFF;
		border: none;
		border-radius: 0;
		font-size: 13px;
		font-weight: 600;
		cursor: pointer;
		transition: all 0.3s ease;
		letter-spacing: 0.18em;
		text-transform: uppercase;
		margin-top: auto;
	}

	.add-to-cart-btn[disabled] {
		background: #E9ECEF;
		color: #8A8A8A;
		cursor: not-allowed;
		opacity: 0.9;
	}

	.add-to-cart-btn[disabled] .material-icons-outlined {
		color: #8A8A8A;
	}

	.add-to-cart-btn:hover {
		background: #091406;
		transform: translateY(-2px);
		box-shadow: 0 10px 22px rgba(15, 38, 11, 0.18);
	}

	.pagination {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 12px;
		padding: 12px 0 0;
	}

	.pagination .page-links {
		display: flex;
		gap: 8px;
		flex-wrap: wrap;
	}

	.pagination a,
	.pagination span {
		min-width: 36px;
		height: 36px;
		display: inline-flex;
		align-items: center;
		justify-content: center;
		border: 1px solid #D1D5DB;
		background: #FFFFFF;
		color: #0F260B;
		font-weight: 700;
		text-decoration: none;
		border-radius: 0;
		font-size: 13px;
	}

	.pagination .active {
		background: #0F260B;
		color: #FFFFFF;
		border-color: #0F260B;
	}

	.pagination .ghost {
		border-color: transparent;
		background: transparent;
		color: #6B7280;
	}

	.load-more-wrap {
		display: flex;
		justify-content: center;
		margin-top: 12px;
	}

	.load-more-link {
		padding: 10px 16px;
		border: 1px solid #0F260B;
		background: transparent;
		color: #0F260B;
		font-size: 12px;
		font-weight: 700;
		text-transform: uppercase;
		letter-spacing: 0.2em;
		text-decoration: none;
		transition: all 0.3s ease;
	}

	.load-more-link:hover {
		background: #0F260B;
		color: #FFFFFF;
	}

	.empty-state {
		background: #FFFFFF;
		border: 1px solid #DCE3DA;
		box-shadow: 0 14px 28px rgba(15, 38, 11, 0.12);
		padding: 32px;
		border-radius: 0;
		text-align: center;
	}

	.empty-state h2 {
		font-size: 24px;
		color: #0F260B;
		margin-bottom: 10px;
	}

	.empty-state p {
		font-size: 14px;
		color: #6B7280;
		margin-bottom: 18px;
	}

	.empty-state a {
		display: inline-flex;
		align-items: center;
		gap: 8px;
		padding: 10px 16px;
		border-radius: 0;
		background: #0F260B;
		color: #FFFFFF;
		text-decoration: none;
		font-size: 12px;
		font-weight: 700;
		text-transform: uppercase;
		letter-spacing: 0.2em;
	}

	@media (max-width: 1200px) {
		.products-grid {
			grid-template-columns: repeat(2, minmax(220px, 1fr));
		}
	}

	@media (max-width: 980px) {
		.search-shell {
			grid-template-columns: 1fr;
		}

		.filters-panel {
			position: static;
		}
	}

	@media (max-width: 640px) {
		.results-head {
			flex-direction: column;
			align-items: flex-start;
		}

		.results-meta {
			flex-direction: column;
			align-items: flex-start;
		}

		.products-grid {
			grid-template-columns: 1fr;
		}
	}
</style>

<main class="search-page">
	<div class="page-wrap search-shell">
		<aside class="filters-panel">
			<div class="filter-card">
				<h3>Filters</h3>

				<div class="filter-group">
					<h4>Category</h4>
					<label class="filter-option">
						<input type="checkbox" checked>
						Fresh Produce
					</label>
					<label class="filter-option">
						<input type="checkbox" checked>
						Fruit
					</label>
					<label class="filter-option">
						<input type="checkbox" checked>
						Dairy
					</label>
					<label class="filter-option">
						<input type="checkbox" checked>
						Bakery
					</label>
					<label class="filter-option">
						<input type="checkbox" checked>
						Pantry
					</label>
				</div>

				<div class="filter-group">
					<h4>Price</h4>
					<div class="price-range">
						<input type="text" value="0" aria-label="Minimum price">
						<input type="text" value="10" aria-label="Maximum price">
					</div>
				</div>

				<div class="filter-group">
					<h4>Rating</h4>
					<label class="filter-option">
						<input type="checkbox" checked>
						4 stars & up
					</label>
					<label class="filter-option">
						<input type="checkbox">
						3 stars & up
					</label>
					<label class="filter-option">
						<input type="checkbox">
						2 stars & up
					</label>
				</div>

				<div class="filter-actions">
					<a href="<?php echo htmlspecialchars(build_search_url(['page' => 1])); ?>">Reset</a>
					<button type="button">Apply</button>
				</div>
			</div>
		</aside>

		<section class="results-panel">
			<div class="results-head">
				<div>
					<span class="eyebrow">Search results</span>
					<h1><?php echo htmlspecialchars($queryLabel); ?></h1>
					<p class="results-count">
						<?php if ($totalResults > 0): ?>
							Showing <?php echo $showingStart; ?> to <?php echo $showingEnd; ?> of <?php echo $totalResults; ?> results
						<?php else: ?>
							No results found
						<?php endif; ?>
					</p>
				</div>
				<div class="sort-control">
					<label for="sort-select">Sort by</label>
					<select id="sort-select" aria-label="Sort results">
						<?php foreach ($sortOptions as $value => $label): ?>
							<option value="<?php echo htmlspecialchars(build_search_url(['sort' => $value, 'page' => 1])); ?>" <?php echo $sort === $value ? 'selected' : ''; ?>>
								<?php echo htmlspecialchars($label); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>

			<div class="results-meta">
				<span class="pill">
					<span class="material-icons-outlined">tune</span>
					Filters active
				</span>
				<span class="pill">
					<span class="material-icons-outlined">search</span>
					<?php echo htmlspecialchars($queryLabel); ?>
				</span>
			</div>

			<?php if (empty($pagedResults)): ?>
				<div class="empty-state">
					<h2>No matches yet</h2>
					<p>Try a broader search or browse all items curated by local traders.</p>
					<a href="search-page.php">Browse everything</a>
				</div>
			<?php else: ?>
				<div class="products-grid">
					<?php foreach ($pagedResults as $product): ?>
						<?php
							$description = $product['desc'] ?? $defaultDescription;
							$discountText = format_discount_text($product['price'], $product['original_price']);
							$reviewCount = $product['reviews'] ?? $defaultReviews;
						?>
						<div class="product-card <?php echo $product['stock'] === 'out-of-stock' ? 'is-out-of-stock' : ''; ?>">
							<div class="product-card-inner" data-link="product.php?name=<?php echo urlencode($product['name']); ?>">
								<div class="product-image-wrapper">
									<?php if ($product['stock'] === 'out-of-stock'): ?>
										<span class="stock-badge out">OUT OF STOCK</span>
									<?php elseif (!empty($product['is_sale'])): ?>
										<span class="stock-badge sale">ON SALE</span>
									<?php elseif (!empty($product['is_new'])): ?>
										<span class="stock-badge new">NEW</span>
									<?php endif; ?>
									<button class="favorite-btn" type="button" aria-label="Add to favorites">
										<span class="material-icons-outlined">favorite_border</span>
									</button>
									<div class="product-image">
										<img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
									</div>
								</div>
								<div class="product-info">
									<h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
									<p class="product-desc"><?php echo htmlspecialchars($description); ?></p>
									<div class="price-row">
										<div class="price-group">
											<span class="current-price"><?php echo $product['price']; ?></span>
											<?php if (!empty($product['original_price'])): ?>
												<span class="original-price"><?php echo $product['original_price']; ?></span>
											<?php endif; ?>
										</div>
										<?php if (!empty($discountText)): ?>
											<span class="discount-text"><?php echo $discountText; ?></span>
										<?php endif; ?>
									</div>
									<div class="rating-row">
										<div class="rating-stars">
											<span class="material-icons-outlined">star</span>
											<span class="material-icons-outlined">star</span>
											<span class="material-icons-outlined">star</span>
											<span class="material-icons-outlined">star</span>
											<span class="material-icons-outlined">star_half</span>
										</div>
										<span class="rating-count"><?php echo '(' . (int) $reviewCount . ')'; ?></span>
									</div>
									<button class="add-to-cart-btn" <?php echo $product['stock'] === 'out-of-stock' ? 'disabled aria-disabled="true"' : ''; ?>>
										<span class="material-icons-outlined">shopping_cart</span>
										<?php echo $product['stock'] === 'out-of-stock' ? 'Out of stock' : 'Add to cart'; ?>
									</button>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>

				<div class="pagination">
					<div class="page-links">
						<?php
							$startPage = max(1, $page - 2);
							$endPage = min($totalPages, $page + 2);
						?>
						<?php if ($page > 1): ?>
							<a href="<?php echo htmlspecialchars(build_search_url(['page' => $page - 1])); ?>">Prev</a>
						<?php else: ?>
							<span class="ghost">Prev</span>
						<?php endif; ?>

						<?php for ($i = $startPage; $i <= $endPage; $i++): ?>
							<?php if ($i === $page): ?>
								<span class="active"><?php echo $i; ?></span>
							<?php else: ?>
								<a href="<?php echo htmlspecialchars(build_search_url(['page' => $i])); ?>"><?php echo $i; ?></a>
							<?php endif; ?>
						<?php endfor; ?>

						<?php if ($page < $totalPages): ?>
							<a href="<?php echo htmlspecialchars(build_search_url(['page' => $page + 1])); ?>">Next</a>
						<?php else: ?>
							<span class="ghost">Next</span>
						<?php endif; ?>
					</div>

					<div class="load-more-wrap">
						<?php if ($page < $totalPages): ?>
							<a class="load-more-link" href="<?php echo htmlspecialchars(build_search_url(['page' => $page + 1])); ?>">Load more</a>
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>
		</section>
	</div>
</main>

<script>
	const sortSelect = document.getElementById('sort-select');
	if (sortSelect) {
		sortSelect.addEventListener('change', (event) => {
			window.location.href = event.target.value;
		});
	}

	document.querySelectorAll('.product-card-inner').forEach((card) => {
		card.addEventListener('click', (event) => {
			if (event.target.closest('button')) {
				return;
			}
			const link = card.getAttribute('data-link');
			if (link) {
				window.location.href = link;
			}
		});
	});
</script>

<?php include 'footer.php'; ?>
