<?php
/**
 * HuddersHub Navigation Bar (Logged In)
 * Uses breadcrumb navigation in the sub nav bar.
 */

// Demo variables
$userName = 'John';
$cartCount = 3;
$wishlistCount = 5;
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

  <!-- Google Material Icons Outlined -->
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">

  <style>
    :root {
      --primary-orange: #FF5E3A;
      --primary-orange-light: #FF8A6A;
      --primary-orange-dark: #E84A2A;

      --primary-green: #0F260B;
      --primary-green-light: rgba(15, 38, 11, 0.12);
      --primary-green-dark: #0B1C08;

      --bg-white: #FFFFFF;
      --bg-light: #FAF9F6;
      --bg-gray: #F5F5F5;
      --border-light: #e0e0e0;
      --bg-gradient: linear-gradient(135deg, #fafafa 0%, #ffffff 100%);

      --text-black: #000000;
      --text-dark-gray: #222222;
      --text-medium-gray: #888888;

      --badge-bg: #000000;
      --badge-text: #ffffff;

      --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.04);
      --shadow-md: 0 2px 8px rgba(0, 0, 0, 0.06);
      --shadow-lg: 0 4px 16px rgba(0, 0, 0, 0.1);

      --transition-smooth: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
      --radius-sm: 6px;
      --radius-md: 8px;
    }

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
      padding-top: 140px;
    }

    header {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 1000;
      background: var(--bg-gradient);
      backdrop-filter: blur(10px);
      transition: var(--transition-smooth);
    }

    header.scrolled {
      box-shadow: var(--shadow-md);
      background: var(--bg-white);
    }

    .top-bar {
      background: #FFFFFF;
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

    .nav-bar {
      background: #F3F4F6;
      border-bottom: 1px solid var(--border-light);
      padding: 10px 0;
      transition: var(--transition-smooth);
    }

    header.scrolled .nav-bar {
      padding: 8px 0;
      background: #F3F4F6;
    }

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
      filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.08));
    }

    .brand-text {
      font-family: 'Google Sans Flex', sans-serif;
      font-weight: 700;
      font-style: italic;
      font-size: 36px;
      letter-spacing: 0.5px;
      color: #0F260B;
    }

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
      border-radius: 50px;
      border: 1px solid #111111;
      background: var(--bg-white);
      font-size: 14px;
      font-weight: 500;
      color: var(--text-black);
      outline: none;
      transition: var(--transition-smooth);
    }

    .search-bar input:focus {
      border-color: var(--primary-orange);
      box-shadow: 0 0 0 2px rgba(255, 111, 60, 0.15);
    }

    .search-bar input::placeholder {
      color: #000000;
      opacity: 0.5;
    }

    .search-icon {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 18px;
      color: #000000;
      opacity: 0.5;
      transition: var(--transition-smooth);
    }

    .search-bar input:focus + .search-icon {
      color: #000000;
      opacity: 1;
    }

    .actions {
      display: flex;
      align-items: center;
      gap: 16px;
      white-space: nowrap;
    }

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
      background: transparent;
      color: var(--primary-green);
    }

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
      border: none;
      box-shadow: none;
      outline: none;
    }

    .icon-with-badge:hover {
      background: transparent;
      color: var(--primary-green);
    }

    .icon-with-badge:focus,
    .icon-with-badge:focus-visible {
      outline: none;
      box-shadow: none;
    }

    .icon-with-badge .material-icons-outlined {
      font-size: 24px;
      color: inherit;
    }

    .badge {
      position: absolute;
      top: 0px;
      right: 0px;
      background: var(--badge-bg);
      color: var(--badge-text);
      border-radius: 4px;
      padding: 2px 5px;
      font-size: 10px;
      font-weight: 600;
      line-height: 1;
      min-width: 16px;
      text-align: center;
    }

    .breadcrumb {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 13px;
      color: var(--text-medium-gray);
    }

    .breadcrumb a {
      color: var(--text-dark-gray);
      text-decoration: none;
    }

    .breadcrumb a:hover {
      color: var(--primary-green);
    }

    .breadcrumb-sep {
      color: #9AA19A;
    }

    .breadcrumb-current {
      color: var(--primary-green);
      font-weight: 600;
    }
  </style>
</head>
<body>
  <header>
    <div class="top-bar">
      <div class="page-wrap top-bar-inner">
        <div class="brand">
          <a href="index.php" style="display: flex; align-items: center; gap: 12px; text-decoration: none; color: inherit;">
            <img src="Asstes/logo.png" alt="HuddersHub logo">
            <span class="brand-text">HuddersHub</span>
          </a>
        </div>

        <div class="search-wrap">
          <div class="search-bar">
            <input type="text" placeholder="Search">
            <span class="search-icon material-icons-outlined">search</span>
          </div>
        </div>

        <div class="actions">
          <a class="user-menu" href="account.php">
            <span class="material-icons-outlined" style="font-size: 24px;">account_circle</span>
            <span>Welcome, <?php echo htmlspecialchars($userName); ?></span>
          </a>

          <a class="icon-with-badge" href="cart.php" aria-label="Cart">
            <span class="material-icons-outlined">shopping_cart</span>
            <?php if ($cartCount > 0): ?>
              <span class="badge"><?php echo $cartCount; ?></span>
            <?php endif; ?>
          </a>

          <a class="icon-with-badge" href="wishlist.php" aria-label="Wishlist">
            <span class="material-icons-outlined">favorite_border</span>
            <?php if ($wishlistCount > 0): ?>
              <span class="badge"><?php echo $wishlistCount; ?></span>
            <?php endif; ?>
          </a>

          <a class="icon-with-badge moon" href="#" aria-label="Dark mode">
            <span class="material-icons-outlined" style="font-size: 20px;">dark_mode</span>
          </a>
        </div>
      </div>
    </div>

    <nav class="nav-bar">
      <div class="page-wrap">
        <div class="breadcrumb">
          <a href="homepage.php">Home</a>
          <span class="breadcrumb-sep">/</span>
          <a href="category.php?cat=greengrocer">GreenGrocer</a>
          <span class="breadcrumb-sep">/</span>
          <a href="category.php?type=fruits-veg">Fresh Vegetable</a>
          <span class="breadcrumb-sep">/</span>
          <span class="breadcrumb-current">Fresh Spinach</span>
        </div>
      </div>
    </nav>
  </header>

  <script>
    window.addEventListener('scroll', function() {
      const header = document.querySelector('header');
      if (window.scrollY > 50) {
        header.classList.add('scrolled');
      } else {
        header.classList.remove('scrolled');
      }
    });
  </script>
