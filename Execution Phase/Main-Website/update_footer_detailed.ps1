$files = Get-ChildItem -Path "public\*.html" -File
foreach ($file in $files) {
    $content = Get-Content -Path $file.FullName -Raw -Encoding UTF8

    $newHtml = @"
<footer class="site-footer">
<div class="page-wrap footer-newsletter">
    <div class="newsletter-content">
        <h3>Subscribe to our fresh weekly newsletter</h3>
        <p>Get the latest updates on seasonal produce, local farms, and exclusive offers directly to your inbox.</p>
    </div>
    <form class="newsletter-form">
        <input type="email" placeholder="Your email address" required>
        <button type="submit">Subscribe</button>
    </form>
</div>
<div class="page-wrap footer-grid">
    <div class="footer-brand">
        <div class="brand-row"><img src="assets/logo.png" alt="HuddersHub logo"><span class="brand-name">HuddersHub</span></div>
        <p class="footer-tagline">Local food, trusted traders, and fresh picks curated for Huddersfield.</p>
        <p class="footer-slogan">Eat Fresh. Buy Local.</p>
        <div class="social-links">
            <a href="#" aria-label="Facebook"><span class="material-icons-outlined">facebook</span></a>
            <a href="#" aria-label="Instagram"><span class="material-icons-outlined">camera_alt</span></a>
        </div>
    </div>
    <div class="footer-col">
        <h4>Shop</h4>
        <a href="category.html?type=greengrocer">Green Grocer</a>
        <a href="category.html?type=butcher">The Butcher</a>
        <a href="category.html?type=bakery">Bakery</a>
        <a href="category.html?type=delicatessen">Delicatessen</a>
        <a href="category.html?type=dairy">Dairy & Eggs</a>
    </div>
    <div class="footer-col">
        <h4>Company</h4>
        <a href="about.html">About HuddersHub</a>
        <a href="register-trader.html">Become a Trader</a>
        <a href="faq.html">Help Center</a>
        <a href="refund.html">Returns & Refunds</a>
    </div>
    <div class="footer-col">
        <h4>Contact</h4>
        <p><span class="material-icons-outlined" style="font-size:16px;">location_on</span> Huddersfield, UK</p>
        <p><span class="material-icons-outlined" style="font-size:16px;">mail</span> support@huddershub.test</p>
        <p><span class="material-icons-outlined" style="font-size:16px;">phone</span> +44 1484 000 000</p>
        <div class="payment-methods">
            <span class="material-icons-outlined">credit_card</span>
            <span class="material-icons-outlined">payments</span>
            <span class="material-icons-outlined">account_balance_wallet</span>
        </div>
    </div>
</div>
<div class="page-wrap footer-bottom">
    <div class="footer-bottom-left">
        <span>&copy; 2026 HuddersHub. All rights reserved.</span>
    </div>
    <div class="footer-bottom-links">
        <a href="privacy.html">Privacy Policy</a>
        <a href="terms.html">Terms of Service</a>
    </div>
</div>
</footer>
"@

    $htmlRegex = '(?s)<footer class="site-footer">.*?</footer>'
    $content = $content -replace $htmlRegex, $newHtml

    $newCss = @"
.site-footer { 
    background-color: #0b0f0b;
    background-image: 
        radial-gradient(circle at 15% 0%, rgba(255, 94, 58, 0.08) 0%, transparent 35%),
        radial-gradient(circle at 85% 100%, rgba(202, 237, 149, 0.06) 0%, transparent 35%),
        linear-gradient(135deg, #1a2219 0%, #050705 100%);
    color: #FFFFFF; 
    padding: 64px 0 24px; 
    margin-top: auto; 
    position: relative;
    overflow: hidden;
}
.site-footer a { transition: color 0.3s; }
.footer-newsletter { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 24px; padding-bottom: 40px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 40px; position: relative; z-index: 1;}
.newsletter-content h3 { font-size: 24px; font-weight: 700; color: #fff; margin-bottom: 8px; }
.newsletter-content p { color: rgba(255,255,255,0.7); font-size: 15px; }
.newsletter-form { display: flex; gap: 12px; width: 100%; max-width: 400px; }
.newsletter-form input { flex: 1; padding: 12px 16px; border: 1px solid rgba(255,255,255,0.2); background: rgba(255,255,255,0.05); color: #fff; border-radius: 4px; font-size: 14px; outline: none; }
.newsletter-form input:focus { border-color: #FF5E3A; background: rgba(255,255,255,0.1); }
.newsletter-form button { padding: 12px 24px; background: #FF5E3A; border: none; color: #fff; font-weight: 600; cursor: pointer; border-radius: 4px; transition: background 0.3s; white-space: nowrap; }
.newsletter-form button:hover { opacity: 0.9; }
.footer-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1.2fr; gap: 32px; align-items: start; position: relative; z-index: 1; }
.brand-row { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
.footer-brand img { width: 56px; height: 56px; object-fit: contain; }
.brand-name { font-weight: 700; font-style: italic; font-size: 28px; }
.footer-tagline { color: rgba(255,255,255,0.8); font-size: 15px; line-height: 1.6; margin-bottom: 12px; }
.footer-slogan { font-size: 14px; font-weight: 700; letter-spacing: 0.8px; text-transform: uppercase; color: #CAED95; margin-bottom: 24px;}
.social-links { display: flex; gap: 16px; margin-bottom: 24px; }
.social-links a { display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; background: rgba(255,255,255,0.1); border-radius: 50%; color: #fff; text-decoration: none;}
.social-links a:hover { background: #FF5E3A; transform: translateY(-2px); }
.footer-col h4 { font-size: 16px; margin-bottom: 20px; letter-spacing: 0.5px; text-transform: uppercase; color: #fff; font-weight: 700; }
.footer-col a { display: block; color: rgba(255,255,255,0.7); text-decoration: none; font-size: 14px; margin-bottom: 12px; }
.footer-col a:hover { color: #FF5E3A; padding-left: 4px; }
.footer-col p { color: rgba(255,255,255,0.7); font-size: 14px; margin-bottom: 14px; display: flex; align-items: center; gap: 10px; }
.payment-methods { display: flex; gap: 12px; margin-top: 20px; opacity: 0.7; }
.payment-methods .material-icons-outlined { font-size: 28px; }
.footer-bottom { margin-top: 48px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 24px; display: flex; justify-content: space-between; align-items: center; z-index: 1; position: relative;}
.footer-bottom-left { color: rgba(255,255,255,0.6); font-size: 13px; }
.footer-bottom-links { display: flex; gap: 24px; }
.footer-bottom-links a { color: rgba(255,255,255,0.6); font-size: 13px; text-decoration: none; }
.footer-bottom-links a:hover { color: #fff; }

@media (max-width: 900px) {
    .footer-grid { grid-template-columns: 1fr 1fr; }
    .footer-brand { grid-column: span 2; margin-bottom: 24px; margin-right: 0;}
}
@media (max-width: 768px) {
    .footer-newsletter { flex-direction: column; align-items: flex-start; }
    .newsletter-form { max-width: 100%; }
    .footer-bottom { flex-direction: column; gap: 16px; align-items: center; text-align: center; }
}
@media (max-width: 480px) {
    .footer-grid { grid-template-columns: 1fr; }
    .footer-brand { grid-column: span 1; }
    .footer-bottom-links { justify-content: center; gap: 12px; }
}
"@

    $cssFind = '(?s)\.site-footer\s*\{.*?(?:\.footer-bottom\s*\{[^}]*\})'
    $content = $content -replace $cssFind, $newCss

    [IO.File]::WriteAllText($file.FullName, $content, [System.Text.Encoding]::UTF8)
}
Write-Output "Global detailed footer update applied."
