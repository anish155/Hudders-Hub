<?php
/**
 * HuddersHub Navigation Bar
 * ==========================
 * This file contains the header/navigation bar for HuddersHub.
 * 
 * FEATURES:
 * - Logo and brand name (clickable, links to home)
 * - Delivery address button
 * - Search bar
 * - User authentication (Login/Signup or Welcome message)
 * - Cart with item count badge
 * - Wishlist with item count badge
 * - Dark mode toggle
 * - Category navigation with dropdown
 * 
 * USER STATUS:
 * - When logged OUT: Shows "Login/Signup" button
 * - When logged IN: Shows "Welcome, {name}" with account icon
 * 
 * TO INTEGRATE WITH YOUR BACKEND:
 * Replace the demo variables below with your session checks:
 * 
 * $isLoggedIn = isset($_SESSION['user']);
 * $userName = $_SESSION['user']['name'] ?? 'User';
 * $cartCount = $_SESSION['cart_count'] ?? 0;
 * $wishlistCount = $_SESSION['wishlist_count'] ?? 0;
 */

// ============================================================================
// DEMO VARIABLES - Replace these with your actual session/database logic
// ============================================================================

// User login status (true = logged in, false = logged out)
$isLoggedIn = false;

// User's name (shown when logged in)
$userName = 'John Doe';

// Number of items in shopping cart (shown as badge on cart icon)
$cartCount = 3;

// Number of items in wishlist (shown as badge on heart icon)
$wishlistCount = 5;

// ============================================================================
// HTML HEADER & STYLES
// ============================================================================
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>HuddersHub Header</title>
  
  <!-- Google Fonts: Google Sans Flex for text -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Google+Sans+Flex:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Google+Sans:ital,opsz,wght@0,17..18,400..700;1,17..18,400..700&family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap" rel="stylesheet">

  <link href="Asstes/site-polish.css" rel="stylesheet">
  
  <!-- Google Material Icons Outlined -->
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
  
  <style>
    /* ========================================================================
       CSS VARIABLES - Easy theme customization
       ======================================================================== */
    :root {
      /* Primary Accent (Orange) */
      --primary-orange: #FF5E3A;
      --primary-orange-light: #FF8C70;
      --primary-orange-dark: #E3472C;

      /* Secondary Accent (Green) */
      --primary-green: #0F260B;
      --primary-green-light: rgba(15, 38, 11, 0.14);
      --primary-green-dark: #0B1C08;

      /* Grays & Backgrounds */
      --bg-white: #FFFFFF;
      --bg-light: #F7F6F3;
      --bg-gray: #F2F4F1;
      --border-light: #DCE3DA;
      --bg-gradient: linear-gradient(135deg, #F8FAF7 0%, #FFFFFF 100%);

      /* Text Colors */
      --text-black: #0B140A;
      --text-dark-gray: #1E2A1C;
      --text-medium-gray: #5E6A63;

      /* Badge */
      --badge-bg: #0F260B;
      --badge-text: #ffffff;

      /* Shadows */
      --shadow-sm: 0 2px 6px rgba(15, 38, 11, 0.08);
      --shadow-md: 0 10px 24px rgba(15, 38, 11, 0.12);
      --shadow-lg: 0 18px 36px rgba(15, 38, 11, 0.16);

      /* Transitions & Borders */
      --transition-smooth: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
      --radius-sm: 0;
      --radius-md: 0;
    }

    /* ========================================================================
       RESET & BASE STYLES
       ======================================================================== */
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Google Sans Flex', sans-serif;
      font-weight: 500;
      color: var(--text-black);
      background: var(--bg-white);
      padding-top: 140px; /* Space for fixed header */
    }

    /* ========================================================================
       HEADER CONTAINER - Fixed position, stays at top
       ======================================================================== */
    header {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 1000;
      background: var(--bg-gradient);
      backdrop-filter: blur(12px);
      transition: var(--transition-smooth);
    }

    /* Header compressed state (on scroll) */
    header.scrolled {
      box-shadow: var(--shadow-md);
      background: var(--bg-white);
    }

    /* ========================================================================
       TOP BAR - Contains logo, search, and user actions
       ======================================================================== */
    .top-bar {
      background: rgba(255, 255, 255, 0.98);
      border-bottom: 1px solid var(--border-light);
      padding: 14px 0;
      transition: var(--transition-smooth);
    }

    header.scrolled .top-bar {
      padding: 10px 0;
    }

    header.scrolled .brand img {
      width: 42px;
      height: 42px;
    }

    header.scrolled .brand-text {
      font-size: 30px;
    }

    header.scrolled .delivery-btn {
      padding: 7px 12px;
      font-size: 13px;
    }

    header.scrolled .delivery-btn .material-icons-outlined {
      font-size: 18px;
    }

    /* ========================================================================
       NAVIGATION BAR - Contains category links
       ======================================================================== */
    .nav-bar {
      background: #F1F3F0;
      border-bottom: 1px solid var(--border-light);
      padding: 10px 0;
      transition: var(--transition-smooth);
    }

    header.scrolled .nav-bar {
      padding: 8px 0;
      background: #F3F4F6;
    }

    header.scrolled .nav-item {
      padding: 5px 10px;
      font-size: 13px;
    }

    /* ========================================================================
       LAYOUT UTILITIES
       ======================================================================== */
    .page-wrap {
      width: min(1200px, 94%);
      margin: 0 auto;
    }

    .top-bar-inner {
      display: grid;
      grid-template-columns: auto 1fr auto;
      gap: 18px;
      align-items: center;
    }

    /* ========================================================================
       BRAND SECTION - Logo and HuddersHub text
       ======================================================================== */
    .brand {
      display: flex;
      align-items: center;
      gap: 14px;
      white-space: nowrap;
    }

    .brand img {
      width: 56px;
      height: 56px;
      object-fit: contain;
      transition: var(--transition-smooth);
      filter: drop-shadow(0 6px 12px rgba(15, 38, 11, 0.12));
    }

    .brand-text {
      font-family: 'Google Sans Flex', sans-serif;
      font-weight: 700;
      font-style: italic;
      font-size: 36px;
      letter-spacing: 0.6px;
      color: #0F260B;
    }

    /* ========================================================================
       DELIVERY ADDRESS BUTTON
       ======================================================================== */
    .delivery-btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      border: none;
      padding: 8px 12px;
      border-radius: var(--radius-sm);
      font-size: 13px;
      font-weight: 600;
      background: transparent;
      color: var(--text-black);
      cursor: pointer;
      text-decoration: none;
      transition: var(--transition-smooth);
    }

    .delivery-btn:hover {
      background: var(--primary-green-light);
      color: var(--text-black);
    }

    .delivery-btn .material-icons-outlined {
      font-size: 18px;
      color: var(--primary-orange);
    }

    /* ========================================================================
       SEARCH BAR
       ======================================================================== */
    .search-wrap {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .search-bar {
      position: relative;
      flex: 1;
      min-width: 280px;
    }

    .search-bar input {
      width: 100%;
      padding: 6px 44px 6px 14px;
      height: 36px;
      border-radius: 0;
      border: 1px solid #C8D1C6;
      background: var(--bg-white);
      font-size: 14px;
      font-weight: 500;
      color: var(--text-black);
      outline: none;
      transition: var(--transition-smooth);
    }

    .search-bar input:focus {
      border-color: var(--primary-orange);
      box-shadow: 0 0 0 3px rgba(255, 94, 58, 0.22);
    }

    .search-bar input::placeholder {
      color: #1B2419;
      opacity: 0.55;
    }

    .search-icon {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 18px;
      color: #1B2419;
      opacity: 0.55;
      transition: var(--transition-smooth);
    }

    .search-bar input:focus + .search-icon {
      color: #000000;
      opacity: 1;
    }

    /* ========================================================================
       USER ACTIONS - Login, Cart, Wishlist, Dark Mode
       ======================================================================== */
    .actions {
      display: flex;
      align-items: center;
      gap: 16px;
      white-space: nowrap;
    }

    /* Login/Signup button */
    .action-btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 14px;
      font-weight: 600;
      color: var(--text-medium-gray);
      text-decoration: none;
      transition: var(--transition-smooth);
      cursor: pointer;
      padding: 8px 12px;
      border-radius: var(--radius-sm);
    }

    .action-btn:hover {
      background: rgba(15, 38, 11, 0.06);
      color: var(--primary-green);
    }

    /* User menu (when logged in) */
    .user-menu {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 14px;
      font-weight: 600;
      color: var(--text-black);
      text-decoration: none;
      transition: var(--transition-smooth);
      cursor: pointer;
      padding: 8px 12px;
      border-radius: var(--radius-sm);
    }

    .user-menu:hover {
      background: rgba(15, 38, 11, 0.06);
      color: var(--primary-green);
    }

    /* Icons with badge counters (Cart, Wishlist) */
    .icon-with-badge {
      position: relative;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: var(--transition-smooth);
      text-decoration: none;
      color: var(--text-black);
      padding: 6px;
      border-radius: 0;
      background: transparent;
      border: 1px solid transparent;
      box-shadow: none;
      outline: none;
    }

    .icon-with-badge:hover {
      background: rgba(15, 38, 11, 0.06);
      color: var(--primary-green);
    }

    .icon-with-badge:focus,
    .icon-with-badge:focus-visible {
      outline: none;
      box-shadow: none;
    }

    /* ========================================================================
       CART DRAWER
       ======================================================================== */
    .cart-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.35);
      opacity: 0;
      visibility: hidden;
      transition: var(--transition-smooth);
      z-index: 1200;
    }

    .cart-drawer {
      position: fixed;
      top: 0;
      right: 0;
      height: 100vh;
      width: min(420px, 92vw);
      background: #FFFFFF;
      box-shadow: -16px 0 40px rgba(15, 38, 11, 0.18);
      transform: translateX(100%);
      transition: var(--transition-smooth);
      z-index: 1300;
      display: flex;
      flex-direction: column;
    }

    .cart-drawer.open {
      transform: translateX(0);
    }

    .cart-overlay.open {
      opacity: 1;
      visibility: visible;
    }

    .cart-header {
      padding: 20px 22px 16px;
      border-bottom: 1px solid var(--border-light);
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .cart-header h3 {
      font-size: 20px;
      font-weight: 700;
      color: var(--text-dark-gray);
    }

    .cart-close {
      border: none;
      background: transparent;
      font-size: 20px;
      cursor: pointer;
      color: var(--text-dark-gray);
    }

    .cart-body {
      padding: 18px 22px;
      flex: 1;
      display: grid;
      gap: 14px;
      align-content: start;
      overflow-y: auto;
    }

    .cart-item {
      display: grid;
      grid-template-columns: 64px 1fr;
      gap: 14px;
      align-items: flex-start;
      border: 1px solid var(--border-light);
      border-radius: 0;
      padding: 12px;
      background: #FFFFFF;
      box-shadow: var(--shadow-sm);
    }

    .cart-item img {
      width: 56px;
      height: 56px;
      object-fit: cover;
      border-radius: 0;
      background: var(--bg-gray);
    }

    .cart-item-details {
      display: grid;
      gap: 10px;
    }

    .cart-item-top {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 10px;
    }

    .cart-item h4 {
      font-size: 14px;
      margin-bottom: 4px;
      color: var(--text-dark-gray);
    }

    .cart-item p {
      font-size: 12px;
      color: var(--text-medium-gray);
    }

    .cart-price {
      font-weight: 700;
      color: var(--primary-orange);
      font-size: 14px;
      white-space: nowrap;
    }

    .cart-item-actions {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
    }

    .cart-qty {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      background: var(--bg-gray);
      padding: 6px 10px;
      border-radius: 0;
    }

    .qty-btn {
      border: none;
      background: #FFFFFF;
      width: 22px;
      height: 22px;
      border-radius: 0;
      font-size: 14px;
      font-weight: 700;
      cursor: pointer;
      color: var(--text-dark-gray);
      box-shadow: var(--shadow-sm);
    }

    .qty-val {
      font-size: 12px;
      font-weight: 700;
      min-width: 12px;
      text-align: center;
    }

    .cart-remove {
      border: none;
      background: transparent;
      color: var(--text-medium-gray);
      font-size: 12px;
      cursor: pointer;
      text-decoration: underline;
    }

    .cart-empty {
      text-align: center;
      color: var(--text-medium-gray);
      font-size: 13px;
      padding: 24px 0 10px;
    }

    .cart-footer {
      border-top: 1px solid var(--border-light);
      padding: 16px 22px 20px;
      display: grid;
      gap: 12px;
    }

    .cart-total {
      display: flex;
      justify-content: space-between;
      font-size: 14px;
      font-weight: 700;
      color: var(--text-dark-gray);
    }

    .cart-actions {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 14px;
    }

    .cart-link {
      font-size: 13px;
      color: var(--text-medium-gray);
      text-decoration: none;
      border-bottom: 1px solid var(--border-light);
      padding-bottom: 2px;
    }

    .cart-link:hover {
      color: var(--primary-green);
      border-bottom-color: var(--primary-green);
    }

    .cart-checkout {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 10px 16px;
      border-radius: 0;
      border: none;
      background: linear-gradient(135deg, var(--primary-orange) 0%, var(--primary-orange-light) 100%);
      color: #FFFFFF;
      font-size: 13px;
      font-weight: 700;
      cursor: pointer;
      box-shadow: 0 10px 22px rgba(255, 94, 58, 0.28);
    }

    .cart-checkout:hover {
      transform: translateY(-1px);
    }

    .icon-with-badge .material-icons-outlined {
      font-size: 24px;
      color: inherit;
    }

    /* Badge counter (shows number of items) */
    .badge {
      position: absolute;
      top: 0px;
      right: 0px;
      background: var(--badge-bg);
      color: var(--badge-text);
      border-radius: 0;
      padding: 2px 5px;
      font-size: 10px;
      font-weight: 600;
      line-height: 1;
      min-width: 16px;
      text-align: center;
    }

    /* ========================================================================
       BUTTON STYLES - Consistent across all pages
       ======================================================================== */
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      padding: 12px 24px;
      border-radius: 0;
      font-size: 16px;
      font-weight: 600;
      font-family: 'Google Sans Flex', sans-serif;
      cursor: pointer;
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
      border: none;
      text-decoration: none;
      line-height: 1;
    }

    .btn-primary {
      background: var(--primary-orange);
      color: #ffffff;
    }

    .btn-primary:hover {
      background: var(--primary-orange-dark);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(255, 111, 60, 0.3);
    }

    .btn-secondary {
      background: var(--primary-green);
      color: #FFFFFF;
    }

    .btn-secondary:hover {
      background: var(--primary-green-dark);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(202, 237, 149, 0.3);
    }

    .btn-outline {
      background: transparent;
      border: 2px solid var(--primary-orange);
      color: var(--primary-orange);
    }

    .btn-outline:hover {
      background: var(--primary-orange);
      color: #ffffff;
      transform: translateY(-2px);
    }

    .btn:active {
      transform: translateY(0);
    }

    /* ========================================================================
       CARD STYLES - Consistent across all pages
       ======================================================================== */
    .card {
      background: var(--bg-white);
      border-radius: 0;
      box-shadow: var(--shadow-sm);
      border: 1px solid var(--border-light);
      overflow: hidden;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .card:hover {
      box-shadow: var(--shadow-lg);
      transform: translateY(-4px);
      border-color: var(--primary-orange);
    }

    .card-body {
      padding: 16px;
    }

    .card-title {
      font-size: 16px;
      font-weight: 600;
      color: var(--text-black);
      margin-bottom: 8px;
    }

    .card-text {
      font-size: 14px;
      color: var(--text-medium-gray);
      line-height: 1.6;
      margin-bottom: 16px;
    }

    .card-action {
      margin-top: 16px;
    }

    /* ========================================================================
       PRODUCT CARD STYLES
       ======================================================================== */
    .product-card {
      background: var(--bg-white);
      border-radius: 0;
      box-shadow: var(--shadow-sm);
      border: 1px solid var(--border-light);
      overflow: hidden;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      cursor: pointer;
    }

    .product-card:hover {
      box-shadow: var(--shadow-lg);
      transform: translateY(-4px);
      border-color: var(--primary-orange);
    }

    .product-card-image {
      width: 100%;
      aspect-ratio: 1/1;
      object-fit: cover;
      background: var(--bg-gray);
    }

    .product-card-body {
      padding: 16px;
    }

    .product-card-name {
      font-size: 16px;
      font-weight: 600;
      color: var(--text-black);
      margin-bottom: 8px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .product-card-rating {
      display: flex;
      align-items: center;
      gap: 4px;
      font-size: 14px;
      color: var(--text-medium-gray);
      margin-bottom: 8px;
    }

    .product-card-rating .star {
      color: #FFA500;
    }

    .product-card-price {
      font-size: 18px;
      font-weight: 700;
      color: var(--primary-orange);
      margin-bottom: 8px;
    }

    .product-card-stock {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 12px;
      font-weight: 600;
      color: var(--primary-green-dark);
      margin-bottom: 16px;
    }

    .product-card-stock .dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: var(--primary-green);
    }

    .product-card-action {
      margin-top: 16px;
    }

    /* ========================================================================
       NAVIGATION ITEMS - Category links
       ======================================================================== */
    .nav-list {
      display: flex;
      align-items: center;
      gap: 8px;
      flex-wrap: wrap;
      font-size: 14px;
      font-weight: 400;
    }

    .nav-item {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 8px 14px;
      border-radius: 0;
      border: none;
      cursor: pointer;
      text-decoration: none;
      color: var(--text-black);
      transition: var(--transition-smooth);
      font-size: 15px;
      font-weight: 400;
      font-family: 'Google Sans Flex', sans-serif;
    }

    .nav-item:not(.primary):hover {
      background: rgba(15, 38, 11, 0.08);
      color: var(--text-black);
    }

    .nav-item.primary {
      background: transparent;
      color: #0F260B;
      font-weight: 600;
      font-size: 14px;
      border-radius: 0;
      box-shadow: none;
    }

    .nav-item.is-active {
      position: relative;
    }

    .nav-item.is-active::after {
      content: '';
      position: absolute;
      left: 0;
      right: 0;
      bottom: -6px;
      height: 2px;
      background: #0F260B;
    }

    .nav-item.primary:hover {
      background: transparent;
      color: #0B1C08;
      box-shadow: none;
    }

    header.scrolled .nav-item.primary {
      font-size: 14px;
    }

    .moon {
      font-size: 20px;
    }

    /* ========================================================================
       NAVIGATION SEPARATOR
       ======================================================================== */
    .nav-separator {
      width: 1px;
      height: 24px;
      background: var(--border-light);
      margin: 0 6px;
      display: inline-block;
    }

    /* ========================================================================
       CATEGORIES DROPDOWN
       ======================================================================== */
    .categories-wrapper {
      position: relative;
    }

    .categories-dropdown {
      position: absolute;
      top: 100%;
      left: 0;
      background: var(--bg-white);
      border: 1px solid var(--border-light);
      border-radius: 0;
      box-shadow: var(--shadow-lg);
      padding: 0;
      min-width: 240px;
      display: none;
      z-index: 1000;
      margin-top: 0;
      overflow: visible;
      transform: translateY(-8px);
      opacity: 0;
      transition: opacity 0.2s ease, transform 0.2s ease;
    }

    /* Keep dropdown open when hovering over it */
    .categories-wrapper:hover .categories-dropdown,
    .categories-dropdown:hover {
      display: block;
      transform: translateY(0);
      opacity: 1;
    }

    /* Invisible bridge to prevent dropdown from closing */
    .categories-dropdown::before {
      content: '';
      position: absolute;
      top: -15px;
      left: 0;
      right: 0;
      height: 15px;
    }

    /* Dropdown Sections */
    .dropdown-section {
      padding: 12px 0;
    }

    .dropdown-section-title {
      padding: 8px 16px;
      font-size: 11px;
      font-weight: 700;
      color: #6b7280;
      text-transform: uppercase;
      letter-spacing: 0.8px;
    }

    .dropdown-divider {
      height: 1px;
      background: var(--border-light);
      margin: 8px 16px;
    }

    /* Dropdown Items */
    .dropdown-item {
      display: block;
      padding: 10px 16px;
      color: var(--text-black);
      text-decoration: none;
      font-size: 14px;
      font-weight: 400;
      transition: var(--transition-smooth);
      border-left: 3px solid transparent;
    }

    .dropdown-item:hover {
      background: rgba(15, 38, 11, 0.08);
      color: var(--text-black);
      border-left-color: var(--primary-green);
    }

    /* All Categories Special Style */
    .dropdown-item.all-categories {
      font-weight: 600;
      background: linear-gradient(135deg, #f9fafb 0%, #ffffff 100%);
      padding: 12px 16px;
    }

    .dropdown-item.all-categories:hover {
      background: var(--primary-orange);
      color: var(--bg-white);
      border-left-color: var(--primary-orange);
    }

    /* ========================================================================
       RESPONSIVE DESIGN - Mobile adjustments
       ======================================================================== */
    @media (max-width: 900px) {
      .top-bar-inner {
        grid-template-columns: 1fr;
      }

      .search-wrap {
        width: 100%;
      }

      .actions {
        justify-content: space-between;
        flex-wrap: wrap;
      }

      .nav-separator {
        display: none;
      }
    }
  </style>
</head>
<body class="theme-organic">
  <!-- =========================================================================
       HEADER START
       Contains: Top Bar (logo, search, actions) + Nav Bar (categories)
       ========================================================================= -->
  <header>
    <!-- TOP BAR: Logo, Search, User Actions -->
    <div class="top-bar">
      <div class="page-wrap top-bar-inner">

        <!-- BRAND SECTION: Logo + HuddersHub text + Delivery button -->
        <div class="brand">
          <a href="homepage.php" style="display: flex; align-items: center; gap: 12px; text-decoration: none; color: inherit;">
            <img src="Asstes/logo.png" alt="HuddersHub logo">
            <span class="brand-text">HuddersHub</span>
          </a>
        </div>

        <!-- SEARCH BAR -->
        <div class="search-wrap">
          <div class="search-bar">
            <input type="text" placeholder="Search">
            <span class="search-icon material-icons-outlined">search</span>
          </div>
        </div>

        <!-- USER ACTIONS: Login/Welcome, Cart, Wishlist, Dark Mode -->
        <div class="actions">
          <?php if ($isLoggedIn): ?>
            <!-- LOGGED IN: Show welcome message with user name -->
            <a class="user-menu" href="account.php">
              <span class="material-icons-outlined" style="font-size: 24px;">account_circle</span>
              <span>Welcome, <?php echo htmlspecialchars($userName); ?></span>
            </a>
          <?php else: ?>
            <!-- NOT LOGGED IN: Show Login/Signup button -->
            <span class="action-btn" id="loginBtn" style="cursor: pointer;">
              <span class="material-icons-outlined" style="font-size: 24px;">person</span>
              <span id="loginBtnText">Login/Signup</span>
            </span>
          <?php endif; ?>

          <!-- CART: Shows item count badge when items exist -->
          <a class="icon-with-badge" href="cart.php" aria-label="Cart" id="cartTrigger">
            <span class="material-icons-outlined">shopping_cart</span>
            <?php if ($cartCount > 0): ?>
              <span class="badge"><?php echo $cartCount; ?></span>
            <?php endif; ?>
          </a>

          <!-- WISHLIST: Shows item count badge when items exist -->
          <a class="icon-with-badge" href="wishlist.php" aria-label="Wishlist">
            <span class="material-icons-outlined">favorite_border</span>
            <?php if ($wishlistCount > 0): ?>
              <span class="badge"><?php echo $wishlistCount; ?></span>
            <?php endif; ?>
          </a>

          <!-- DARK MODE TOGGLE -->
          <a class="icon-with-badge moon" href="#" aria-label="Dark mode">
            <span class="material-icons-outlined" style="font-size: 20px;">dark_mode</span>
          </a>
        </div>

      </div>
    </div>

    <!-- NAVIGATION BAR: Category links -->
    <nav class="nav-bar">
      <div class="page-wrap">
        <?php if (!empty($navBreadcrumb) && is_array($navBreadcrumb)): ?>
          <div class="breadcrumb">
            <?php foreach ($navBreadcrumb as $index => $crumb): ?>
              <span><?php echo htmlspecialchars($crumb); ?></span>
              <?php if ($index < count($navBreadcrumb) - 1): ?>
                <span class="breadcrumb-sep">/</span>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="nav-list">

          <!-- HOME LINK -->
          <a href="homepage.php" class="nav-item primary <?php echo ($activePage ?? '') === 'home' ? 'is-active' : ''; ?>">
            <span class="material-icons-outlined" style="font-size: 18px;">home</span>
            Home
          </a>

          <!-- SEPARATOR -->
          <span class="nav-separator"></span>

          <!-- CATEGORIES DROPDOWN -->
          <div class="categories-wrapper">
            <span class="nav-item">
              <span class="material-icons-outlined" style="font-size: 18px;">menu</span>
              Categories
            </span>
            <div class="categories-dropdown">
              <!-- Browse by Shop Section -->
              <div class="dropdown-section">
                <div class="dropdown-section-title">Browse by Shop</div>
                <div class="dropdown-divider"></div>
                <a href="butcher.php?cat=butcher" class="dropdown-item">Butcher</a>
                <a href="greengrocer.php?cat=greengrocer" class="dropdown-item">Greengrocer</a>
                <a href="fishmonger.php?cat=fishmonger" class="dropdown-item">Fishmonger</a>
                <a href="bakery.php?cat=bakery" class="dropdown-item">Bakery</a>
                <a href="delicat.php?cat=delicatessen" class="dropdown-item">Delicatessen</a>
              </div>

              <!-- Browse by Type Section -->
              <div class="dropdown-section">
                <div class="dropdown-section-title">Browse by Type</div>
                <div class="dropdown-divider"></div>
                <a href="category.php?type=meat-poultry" class="dropdown-item">Meat & Poultry</a>
                <a href="category.php?type=fish-seafood" class="dropdown-item">Fish & Seafood</a>
                <a href="category.php?type=fruits-veg" class="dropdown-item">Fruits & Veg</a>
                <a href="category.php?type=bakery-items" class="dropdown-item">Bakery Items</a>
                <a href="category.php?type=deli-foods" class="dropdown-item">Deli Foods</a>
              </div>

              <!-- All Categories Link -->
              <div class="dropdown-divider"></div>
              <a href="all-categories.php" class="dropdown-item all-categories">All Categories</a>
            </div>
          </div>

          <!-- SEPARATOR -->
          <span class="nav-separator"></span>

          <!-- ABOUT LINK -->
          <a href="about.php" class="nav-item">About</a>

          <!-- SEPARATOR -->
          <span class="nav-separator"></span>

          <!-- CONTACT LINK -->
          <a href="contact.php" class="nav-item <?php echo ($activePage ?? '') === 'contact' ? 'is-active' : ''; ?>">Contact</a>

          </div>
        <?php endif; ?>
      </div>
    </nav>
  </header>

  <div class="cart-overlay" id="cartOverlay"></div>
  <aside class="cart-drawer" id="cartDrawer" aria-hidden="true">
    <div class="cart-header">
      <h3>Your cart</h3>
      <button class="cart-close" id="cartClose" aria-label="Close cart">&times;</button>
    </div>
    <div class="cart-body">
      <div class="cart-item" data-price="0.90" data-category="Greengrocer">
        <img src="Asstes/Item-image/green-bell-pepper-isolated.jpg" alt="Green Bell Pepper">
        <div class="cart-item-details">
          <div class="cart-item-top">
            <div>
              <h4>Green Bell Pepper</h4>
              <p class="cart-meta">Greengrocer · Qty 2</p>
            </div>
            <span class="cart-price">£1.80</span>
          </div>
          <div class="cart-item-actions">
            <div class="cart-qty">
              <button class="qty-btn" data-action="decrease" type="button">-</button>
              <span class="qty-val">2</span>
              <button class="qty-btn" data-action="increase" type="button">+</button>
            </div>
            <button class="cart-remove" type="button">Remove</button>
          </div>
        </div>
      </div>
      <div class="cart-item" data-price="0.90" data-category="Greengrocer">
        <img src="Asstes/Item-image/green-broccoli.jpg" alt="Green Broccoli">
        <div class="cart-item-details">
          <div class="cart-item-top">
            <div>
              <h4>Green Broccoli</h4>
              <p class="cart-meta">Greengrocer · Qty 1</p>
            </div>
            <span class="cart-price">£0.90</span>
          </div>
          <div class="cart-item-actions">
            <div class="cart-qty">
              <button class="qty-btn" data-action="decrease" type="button">-</button>
              <span class="qty-val">1</span>
              <button class="qty-btn" data-action="increase" type="button">+</button>
            </div>
            <button class="cart-remove" type="button">Remove</button>
          </div>
        </div>
      </div>
      <div class="cart-empty" style="display:none;">Your cart is empty.</div>
    </div>
    <div class="cart-footer">
      <div class="cart-total">
        <span>Total</span>
        <span class="cart-total-value">£2.70</span>
      </div>
      <div class="cart-actions">
        <a class="cart-link" href="cart.php">Go to cart</a>
        <button class="cart-checkout" type="button">Checkout →</button>
      </div>
    </div>
  </aside>
  <!-- =========================================================================
       HEADER END
       ========================================================================= -->

  <!-- =========================================================================
       JAVASCRIPT: Handle login toggle and scroll effects
       ========================================================================= -->
  <script>
    /**
     * LOGIN BUTTON CLICK HANDLER (DEMO)
     * When user clicks Login/Signup, replace it with "Welcome, John Doe"
     * In production, replace this with actual authentication logic
     */
    const loginBtn = document.getElementById('loginBtn');
    if (loginBtn) {
      loginBtn.addEventListener('click', function() {
        // Replace Login/Signup with Welcome message
        loginBtn.outerHTML = `
          <a class="user-menu" href="account.php">
            <span class="material-icons-outlined" style="font-size: 24px;">account_circle</span>
            <span>Welcome, John Doe</span>
          </a>
        `;
      });
    }

    /**
     * HEADER SCROLL EFFECT
     * Compress header when user scrolls down
     */
    window.addEventListener('scroll', function() {
      const header = document.querySelector('header');
      if (window.scrollY > 50) {
        header.classList.add('scrolled');
      } else {
        header.classList.remove('scrolled');
      }
    });

    const cartTrigger = document.getElementById('cartTrigger');
    const cartDrawer = document.getElementById('cartDrawer');
    const cartOverlay = document.getElementById('cartOverlay');
    const cartClose = document.getElementById('cartClose');

    function formatMoney(value) {
      return '£' + value.toFixed(2);
    }

    function updateCartTotals() {
      if (!cartDrawer) return;
      const items = cartDrawer.querySelectorAll('.cart-item');
      let total = 0;
      items.forEach(item => {
        const price = parseFloat(item.dataset.price || '0');
        const qtyEl = item.querySelector('.qty-val');
        const qty = parseInt(qtyEl ? qtyEl.textContent : '0', 10) || 0;
        const lineTotal = price * qty;
        const priceEl = item.querySelector('.cart-price');
        const metaEl = item.querySelector('.cart-meta');
        if (priceEl) priceEl.textContent = formatMoney(lineTotal);
        if (metaEl) {
          const cat = item.dataset.category || 'Item';
          metaEl.textContent = cat + ' · Qty ' + qty;
        }
        total += lineTotal;
      });

      const totalEl = cartDrawer.querySelector('.cart-total-value');
      if (totalEl) totalEl.textContent = formatMoney(total);

      const emptyEl = cartDrawer.querySelector('.cart-empty');
      if (emptyEl) emptyEl.style.display = items.length ? 'none' : 'block';
    }

    function openCart() {
      cartDrawer.classList.add('open');
      cartOverlay.classList.add('open');
      cartDrawer.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
    }

    function closeCart() {
      cartDrawer.classList.remove('open');
      cartOverlay.classList.remove('open');
      cartDrawer.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
    }

    if (cartTrigger) {
      cartTrigger.addEventListener('click', function(e) {
        e.preventDefault();
        openCart();
      });
    }

    if (cartDrawer) {
      cartDrawer.addEventListener('click', function(e) {
        const qtyBtn = e.target.closest('.qty-btn');
        if (qtyBtn) {
          const item = qtyBtn.closest('.cart-item');
          const qtyEl = item ? item.querySelector('.qty-val') : null;
          if (!item || !qtyEl) return;
          let qty = parseInt(qtyEl.textContent, 10) || 1;
          if (qtyBtn.dataset.action === 'increase') {
            qty += 1;
          } else {
            qty = Math.max(1, qty - 1);
          }
          qtyEl.textContent = qty;
          updateCartTotals();
          return;
        }

        const removeBtn = e.target.closest('.cart-remove');
        if (removeBtn) {
          const item = removeBtn.closest('.cart-item');
          if (item) item.remove();
          updateCartTotals();
        }
      });
    }

    if (cartOverlay) {
      cartOverlay.addEventListener('click', closeCart);
    }

    if (cartClose) {
      cartClose.addEventListener('click', closeCart);
    }

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        closeCart();
      }
    });

    updateCartTotals();
  </script>
