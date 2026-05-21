-- Run this as the Source > PL/SQL Region source in your APEX Admin Dashboard page
-- (Replace the existing PL/SQL region source with this)

DECLARE
    v_class_name VARCHAR2(100);
    v_modal_url  VARCHAR2(1000);

    CURSOR c_pending IS
        SELECT t.trader_id, 
               u.firstname || ' ' || u.lastname as owner_name, 
               s.name as shop_name, 
               u.email, 
               t.status
        FROM TRADER t
        JOIN HUDDER_USER u ON t.user_id = u.user_id
        LEFT JOIN SHOP s ON u.user_id = s.user_id
        WHERE t.status = 'Pending'
        ORDER BY t.trader_id DESC;

    CURSOR c_active IS
        SELECT t.trader_id, 
               u.firstname || ' ' || u.lastname as owner_name, 
               s.name as shop_name, 
               t.status,
               (SELECT ROUND(AVG(r.rating), 1) 
                FROM REVIEW r 
                JOIN PRODUCT p ON r.product_id = p.product_id 
                WHERE p.shop_id = s.shop_id) as avg_rating
        FROM TRADER t
        JOIN HUDDER_USER u ON t.user_id = u.user_id
        JOIN SHOP s ON u.user_id = s.user_id
        WHERE t.status = 'Active'
        ORDER BY avg_rating ASC;

BEGIN
    HTP.P('<style>
        .admin-wrap { font-family: "Segoe UI", sans-serif; max-width: 1200px; margin: auto; }
        .sect-header { background: #2d5a3f; color: white; padding: 12px 20px; margin-top: 30px; border-radius: 6px 6px 0 0; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; }
        .trader-card { border: 1px solid #e0e0e0; border-top: none; padding: 20px; display: flex; justify-content: space-between; align-items: center; background: #ffffff; transition: 0.3s; }
        .trader-card:hover { background: #f9f9f9; box-shadow: inset 4px 0 0 #2d5a3f; }
        .info-box h4 { margin: 0 0 8px 0; font-size: 1.25em; }
        .trader-link { color: #2d5a3f; text-decoration: underline; font-weight: bold; cursor: pointer; transition: 0.2s; }
        .trader-link:hover { color: #1a3324; text-decoration: none; }
        .info-box p { margin: 3px 0; color: #555; font-size: 0.95em; }
        .tag-pending { background: #fff3e0; color: #ef6c00; font-size: 0.65em; padding: 3px 8px; border-radius: 10px; margin-left: 10px; vertical-align: middle; border: 1px solid #ffe0b2;}
        .rating-chip { background: #ffc107; color: #000; padding: 4px 10px; border-radius: 4px; font-weight: bold; }
        .bad-rating-alert { background: #fff5f5 !important; border-left: 6px solid #dc3545 !important; }
        .btn-act { padding: 10px 18px; border-radius: 5px; cursor: pointer; color: white !important; font-weight: 600; text-decoration: none !important; display: inline-block; transition: 0.2s; font-size: 0.9em; border: none; }
        .btn-act:hover { opacity: 0.8; transform: translateY(-1px); }
        .b-acc { background: #28a745; margin-right: 8px; }
        .b-dec { background: #dc3545; }
        .b-blk { background: #343a40; }
    </style>');

    HTP.P('<div class="admin-wrap">');

    -- SECTION 1: PENDING
    HTP.P('<div class="sect-header">Pending Trader Requests</div>');

    FOR r IN c_pending LOOP
        v_modal_url := APEX_UTIL.PREPARE_URL('f?p='||:APP_ID||':4:'||:APP_SESSION||'::::P4_TRADER_ID:'||r.trader_id);

        HTP.P('<div class="trader-card">');
        HTP.P('<div class="info-box">');
        HTP.P('<h4><a href="' || v_modal_url || '" class="trader-link">' || r.owner_name || '</a> <span class="tag-pending">PENDING APPROVAL</span></h4>');
        HTP.P('<p><strong>Shop:</strong> ' || r.shop_name || ' | <strong>Email:</strong> ' || r.email || '</p>');
        HTP.P('</div>');
        HTP.P('<div class="actions">');
        HTP.P('<button class="btn-act b-acc" onclick="approveTrader(' || r.trader_id || ', ''approve'')">ACCEPT</button>');
        HTP.P('<button class="btn-act b-dec" onclick="approveTrader(' || r.trader_id || ', ''reject'')">DECLINE</button>');
        HTP.P('</div></div>');
    END LOOP;

    IF c_pending%ROWCOUNT = 0 THEN
        HTP.P('<div class="trader-card"><div class="info-box"><p>No pending trader requests.</p></div></div>');
    END IF;

    -- SECTION 2: ACTIVE
    HTP.P('<div class="sect-header" style="margin-top:40px;">Manage Active Traders</div>');

    FOR r IN c_active LOOP
        v_class_name := 'trader-card';
        IF r.avg_rating < 3.0 THEN v_class_name := v_class_name || ' bad-rating-alert'; END IF;

        v_modal_url := APEX_UTIL.PREPARE_URL('f?p='||:APP_ID||':4:'||:APP_SESSION||'::::P4_TRADER_ID:'||r.trader_id);

        HTP.P('<div class="' || v_class_name || '">');
        HTP.P('<div class="info-box">');
        HTP.P('<h4><a href="' || v_modal_url || '" class="trader-link">' || r.owner_name || '</a></h4>');
        HTP.P('<p><strong>Shop:</strong> ' || r.shop_name || ' | <strong>Rating:</strong> <span class="rating-chip">' || NVL(TO_CHAR(r.avg_rating), 'No Reviews') || '</span></p>');
        HTP.P('</div>');
        HTP.P('<div class="actions">');
        HTP.P('<button class="btn-act b-blk" onclick="blockTrader(' || r.trader_id || ')">BLOCK TRADER</button>');
        HTP.P('</div></div>');
    END LOOP;

    IF c_active%ROWCOUNT = 0 THEN
        HTP.P('<div class="trader-card"><div class="info-box"><p>No active traders.</p></div></div>');
    END IF;

    HTP.P('</div>');

    -- JAVASCRIPT (FIXED URL + ADMIN OVERRIDE)
    HTP.P('<script>
function approveTrader(traderId, action) {
    if (action === "reject" && !confirm("Decline this trader registration?")) return;
    if (action === "approve" && !confirm("Approve this trader? This will send a welcome email.")) return;

    fetch("http://localhost/Main-Website/api/admin/approve-trader.php", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({trader_id: traderId, action: action, _admin_override: true})
    })
    .then(r => r.json())
    .then(data => {
        alert(data.message);
        location.reload();
    })
    .catch(e => alert("Error: " + e));
}

function blockTrader(traderId) {
    if (!confirm("Block this trader account?")) return;

    fetch("http://localhost/Main-Website/api/admin/approve-trader.php", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({trader_id: traderId, action: "block", _admin_override: true})
    })
    .then(r => r.json())
    .then(data => {
        alert(data.message);
        location.reload();
    })
    .catch(e => alert("Error: " + e));
}
</script>');

END;
