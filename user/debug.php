<?php
// ============================================================
// debug_update.php  — place this in your /actions/ folder
// Visit: yoursite.com/actions/debug_update.php
// DELETE THIS FILE after you fix the issue!
// ============================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug: update_stock.php</title>
    <style>
        body { font-family: monospace; padding: 30px; background: #1e1e1e; color: #d4d4d4; }
        h2   { color: #569cd6; }
        .ok  { color: #4ec9b0; font-weight: bold; }
        .err { color: #f44747; font-weight: bold; }
        .warn{ color: #dcdcaa; }
        .box { background: #252526; border: 1px solid #444; border-radius: 6px; padding: 16px; margin: 14px 0; }
        pre  { margin: 0; white-space: pre-wrap; word-break: break-all; }
        button { padding: 10px 22px; background: #0e639c; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; margin-top: 10px; }
        button:hover { background: #1177bb; }
        #result { margin-top: 20px; }
    </style>
</head>
<body>

<h2>🔧 Debug: update_stock.php</h2>

<?php
// ── STEP 1: Session ──────────────────────────────────────────
echo '<div class="box"><b>STEP 1 — Session</b><br>';
session_start();
if (isset($_SESSION['user_id'])) {
    echo '<span class="ok">✓ Session active</span> — user_id = ' . $_SESSION['user_id'];
    echo ', role = ' . ($_SESSION['role'] ?? '(not set)');
} else {
    echo '<span class="err">✗ No session! You are not logged in.</span><br>';
    echo '<span class="warn">→ Login first, then come back to this page.</span>';
}
echo '</div>';

// ── STEP 2: DB Connection ────────────────────────────────────
echo '<div class="box"><b>STEP 2 — Database Connection</b><br>';
// Try to find conn.php automatically
$conn_paths = [
    '../auth/conn.php',
    '../../auth/conn.php',
    '../conn.php',
    '../config/conn.php',
    '../includes/conn.php',
];
$conn_found = false;
foreach ($conn_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $conn_found = true;
        echo '<span class="ok">✓ conn.php found</span> at: ' . $path . '<br>';
        break;
    }
}
if (!$conn_found) {
    echo '<span class="err">✗ conn.php NOT found!</span> Tried:<br>';
    foreach ($conn_paths as $p) echo '&nbsp;&nbsp;' . $p . '<br>';
} else {
    if (isset($conn) && $conn instanceof mysqli) {
        if ($conn->connect_error) {
            echo '<span class="err">✗ DB connect error: ' . $conn->connect_error . '</span>';
        } else {
            echo '<span class="ok">✓ DB connected</span> — Host: ' . $conn->host_info;
        }
    } else {
        echo '<span class="warn">⚠ $conn exists but is not a mysqli object. Type: ' . gettype($conn ?? null) . '</span>';
    }
}
echo '</div>';

// ── STEP 3: Check products table & column name ───────────────
if ($conn_found && isset($conn) && !$conn->connect_error) {
    echo '<div class="box"><b>STEP 3 — Products Table Columns</b><br>';
    $r = $conn->query("DESCRIBE products");
    if ($r) {
        $cols = [];
        while ($row = $r->fetch_assoc()) { $cols[] = $row['Field']; }
        echo 'Columns: <span class="warn">' . implode(', ', $cols) . '</span><br>';

        $hasPK = in_array('id', $cols) ? '<span class="ok">✓ id column EXISTS</span>' : '<span class="err">✗ id column MISSING</span>';
        $hasPK2 = in_array('product_id', $cols) ? ' | <span class="warn">also has product_id column</span>' : '';
        echo $hasPK . $hasPK2 . '<br>';

        $hasQty = in_array('quantity', $cols) ? '<span class="ok">✓ quantity column EXISTS</span>' : '<span class="err">✗ quantity column MISSING</span>';
        echo $hasQty;
    } else {
        echo '<span class="err">✗ Could not read products table: ' . $conn->error . '</span>';
    }
    echo '</div>';

    // ── STEP 4: Check stock_update_history table ─────────────
    echo '<div class="box"><b>STEP 4 — stock_update_history Table</b><br>';
    $r2 = $conn->query("DESCRIBE stock_update_history");
    if ($r2) {
        $cols2 = [];
        while ($row = $r2->fetch_assoc()) { $cols2[] = $row['Field']; }
        echo 'Columns: <span class="warn">' . implode(', ', $cols2) . '</span><br>';
        echo (in_array('history_id', $cols2) ? '<span class="ok">✓ history_id exists</span>' : '<span class="err">✗ history_id MISSING</span>') . '<br>';
        echo (in_array('product_id', $cols2) ? '<span class="ok">✓ product_id exists</span>' : '<span class="err">✗ product_id MISSING</span>') . '<br>';
        echo (in_array('staff_id',   $cols2) ? '<span class="ok">✓ staff_id exists</span>'   : '<span class="err">✗ staff_id MISSING</span>');
    } else {
        echo '<span class="err">✗ Table does not exist yet! Run the SQL from create_stock_history_table.sql first.</span>';
    }
    echo '</div>';

    // ── STEP 5: Check users table PK ─────────────────────────
    echo '<div class="box"><b>STEP 5 — Users Table PK</b><br>';
    $r3 = $conn->query("DESCRIBE users");
    if ($r3) {
        $cols3 = [];
        while ($row = $r3->fetch_assoc()) { $cols3[] = $row['Field']; }
        echo 'Columns: <span class="warn">' . implode(', ', $cols3) . '</span><br>';
        echo (in_array('id', $cols3)      ? '<span class="ok">✓ id column</span>'      : '<span class="err">✗ id MISSING</span>') . ' | ';
        echo (in_array('user_id', $cols3) ? '<span class="warn">also has user_id</span>' : 'no user_id column');
    } else {
        echo '<span class="err">✗ Could not read users table.</span>';
    }
    echo '</div>';
}

// ── STEP 6: Check file path to update_stock.php ──────────────
echo '<div class="box"><b>STEP 6 — File Path Check</b><br>';
$target = __DIR__ . '/update_stock.php';
echo 'Looking for update_stock.php at: <span class="warn">' . $target . '</span><br>';
if (file_exists($target)) {
    echo '<span class="ok">✓ File EXISTS</span>';
} else {
    echo '<span class="err">✗ File NOT FOUND at that path!</span><br>';
    echo '<span class="warn">→ Make sure update_stock.php is in the same /actions/ folder as this debug file.</span>';
}
echo '</div>';
?>

<!-- STEP 7: Live AJAX test -->
<div class="box">
    <b>STEP 7 — Live AJAX Test (simulates the real button click)</b><br><br>
    <button onclick="runTest()">▶ Run Live Test NOW</button>
    <div id="result"></div>
</div>

<script>
function runTest() {
    const out = document.getElementById('result');
    out.innerHTML = '<span style="color:#dcdcaa;">Sending request...</span>';

    const fd = new FormData();
    fd.append('product_id',      '1');   // Test with product ID 1
    fd.append('quantity_change', '1');   // Add 1 unit
    fd.append('note',            'debug test - safe to ignore');

    fetch('update_stock.php', { method: 'POST', body: fd })
        .then(res => {
            out.innerHTML += '<br><span style="color:#4ec9b0;">✓ Got HTTP response</span> — Status: <b>' + res.status + ' ' + res.statusText + '</b>';
            return res.text(); // Use .text() first so we see RAW output even if broken JSON
        })
        .then(raw => {
            out.innerHTML += '<br><b>Raw response from server:</b><br><pre style="background:#111;padding:10px;border-radius:4px;color:#ce9178;">' + escapeHtml(raw) + '</pre>';
            try {
                const data = JSON.parse(raw);
                out.innerHTML += '<br><span style="color:#4ec9b0;">✓ Valid JSON</span><br>';
                out.innerHTML += 'status: <b>' + data.status + '</b><br>';
                out.innerHTML += 'message: <b>' + data.message + '</b>';
                if (data.status === 'success') {
                    out.innerHTML += '<br><br><span style="color:#4ec9b0; font-size:1.1rem;">🎉 Everything works! The fix is complete.</span>';
                    // Undo the test increment
                    const fd2 = new FormData();
                    fd2.append('product_id',      '1');
                    fd2.append('quantity_change', '-1');
                    fd2.append('note',            'debug test rollback');
                    fetch('update_stock.php', { method:'POST', body: fd2 });
                }
            } catch(e) {
                out.innerHTML += '<br><span style="color:#f44747;">✗ INVALID JSON — PHP is printing an error before the JSON output.</span><br>';
                out.innerHTML += '<span style="color:#dcdcaa;">→ Look at the raw response above. The PHP error will tell you exactly what to fix.</span>';
            }
        })
        .catch(err => {
            out.innerHTML += '<br><span style="color:#f44747;">✗ FETCH FAILED: ' + err.message + '</span><br>';
            out.innerHTML += '<span style="color:#dcdcaa;">→ This usually means update_stock.php does not exist at that path, or the server returned a 404/500 with no body.</span>';
        });
}

function escapeHtml(s) {
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
</script>
</body>
</html>