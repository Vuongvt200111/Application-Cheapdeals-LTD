<?php
$cur = 'support.php'; 
$pageTitle = 'Live Support Chat';
$pageScripts = ['support.js'];
require __DIR__ . '/../layout/header.php';
?>

<h2 class="title">💬 Live Support Chat</h2>
<p class="lead">Connect directly with a Customer Service Representative (CSR).</p>

<!-- Connection Status Banner (BUG-014) -->
<div class="offer-banner" style="background: rgba(0, 229, 255, 0.08); border-color: var(--brand); padding: 10px; margin-bottom: 15px; border-radius: 8px;">
    <div>🟢 <strong>Session Status:</strong> Connected (Session ID: <?= session_id() ?>)</div>
</div>

<div class="panel" style="max-width:640px; margin-top: 15px;">
    <!-- Chat log window -->
    <div class="chat" style="height:350px; overflow-y:auto; padding:10px; border:1px solid var(--line); border-radius:8px; background:var(--bg);">
        <?php if (!$msgs): ?>
            <p class="lead" style="margin:10px">Chat session established. Send a message to start! 👋</p>
        <?php endif; ?>
        
        <?php foreach ($msgs as $m): ?>
            <?php
            $isProductLink = (strpos($m['message'], '[Liên kết sản phẩm]:') !== false);
            $displayMsg = $m['message'];
            if ($isProductLink) {
                $displayMsg = str_replace('[Liên kết sản phẩm]:', '📦 *Product Inquiry:* ', $m['message']);
            }
            ?>
            <div class="bubble <?= $m['sender'] === 'user' ? 'user' : 'staff' ?>" 
                 style="margin-bottom:12px; padding:10px; border-radius:12px; max-width:75%; <?= $m['sender'] === 'user' ? 'margin-left:auto; background:#007bff; color:#fff;' : 'background:#e9ecef; color:#000;' ?>; <?= $isProductLink ? 'border: 1px dashed rgba(255,255,255,0.5); background:#1e7e34; color:#fff;' : '' ?>">
                
                <div class="who" style="font-size:11px; font-weight:bold; opacity:0.8;">
                    <?= $m['sender'] === 'user' ? 'You' : 'CSR Representative' ?>
                </div>
                
                <div class="msg-text" style="margin-top:4px; white-space: pre-wrap;">
                    <?= esc($displayMsg) ?>
                </div>
                
                <div class="time" style="font-size:9px; text-align:right; opacity:0.6; margin-top:4px;">
                    <?= esc(substr($m['created_at'], 11, 5)) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Message input form -->
    <form method="post" class="chat-input" style="display:flex; flex-direction:column; gap:8px; margin-top:12px;">
        <input type="hidden" name="act" value="send">

        <!-- Attached package inquiry info (FR13) -->
        <?php if (!empty($inquiryPackage)): ?>
            <div class="product-inquiry-card" style="display:flex; gap:12px; align-items:center; background:var(--card); border:1px solid #ffd591; padding:10px; border-radius:8px; position:relative;">
                <input type="hidden" name="attached_package" value="<?= esc($inquiryPackage['name'] . ' [' . $inquiryPackage['category'] . '] - Price: ' . gbp($inquiryPackage['price'])) ?>">
                
                <!-- Product icon placeholder -->
                <div style="width:45px; height:45px; background:#e3fafc; color:#0c8599; border-radius:6px; display:flex; align-items:center; justify-content:center; font-weight:bold; font-size:18px;">📦</div>
                
                <div style="flex:1;">
                    <div style="font-size:11px; color:#e67e22; font-weight:bold; text-align:left;">Linked Inquiry Product:</div>
                    <div style="font-weight:bold; font-size:14px; color:var(--ink); text-align:left;"><?= esc($inquiryPackage['name']) ?></div>
                    <div style="font-size:12px; color:var(--muted); text-align:left;"><?= esc($inquiryPackage['category']) ?> &middot; <strong style="color:#2b8a3e;"><?= gbp($inquiryPackage['price']) ?>/mo</strong></div>
                </div>
                
                <a href="support.php" style="text-decoration:none; color:var(--muted); font-weight:bold; font-size:18px; padding:2px 8px; position:absolute; top:4px; right:4px;">&times;</a>
            </div>
        <?php endif; ?>

        <!-- Text input and button -->
        <div style="display:flex; gap:8px; width:100%;">
            <input name="message" placeholder="<?= !empty($inquiryPackage) ? 'Type your inquiry message about this package...' : 'Type support message...' ?>" style="flex:1; padding:10px; border:1px solid var(--line); border-radius:6px; background:var(--card); color:var(--ink);" required autocomplete="off">
            <button class="btn" type="submit">Send</button>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
