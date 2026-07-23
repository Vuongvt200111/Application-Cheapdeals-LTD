    </main>
    <footer>CheapDeals.com LTD &mdash; v3 (COMP1807). PHP + MySQL &middot; <?= $view==='mobile' ? 'Mobile' : 'Desktop' ?> interface.

<!-- Floating Live Support Chat Bubble Widget (Image 4) -->
<a id="floating-chat-bubble" href="support.php" title="Live Support Chat" style="position:fixed;bottom:24px;right:24px;z-index:9999;width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,var(--cyan),#0072ff);color:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 6px 20px rgba(0,242,254,0.4);font-size:24px;text-decoration:none;transition:transform 0.3s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
  💬
  <span id="chat-unread-badge" style="position:absolute;top:2px;right:2px;min-width:12px;height:12px;border-radius:10px;background:#ff3547;border:2px solid #111;font-size:10px;font-weight:bold;color:#fff;display:flex;align-items:center;justify-content:center;padding:0 3px;"></span>
</a>
<script>
// Check Staff unread messages -> show exact number, default small red dot, reset on reply
function updateChatBadge() {
  const badge = document.getElementById('chat-unread-badge');
  if (!badge) return;
  fetch('support.php?action=check_unread_count')
    .then(r => r.json())
    .then(data => {
      if (data && data.unread_count > 0) {
        badge.innerText = data.unread_count;
        badge.style.minWidth = '18px';
        badge.style.height = '18px';
      } else {
        badge.innerText = '';
        badge.style.minWidth = '12px';
        badge.style.height = '12px';
      }
    }).catch(e => {});
}
document.addEventListener('DOMContentLoaded', updateChatBadge);
</script>


</footer>
<?php if ($view === 'mobile'): ?>
  </div><!-- .screen -->
  <nav class="bottom-nav">
    <?php foreach ($items as $it): ?>
      <a class="<?= $it[0]===$cur ? 'active' : '' ?>" href="<?= esc($it[0]) ?>"><span class="ic"><?= $it[2] ?></span><?= esc($it[1]) ?></a>
    <?php endforeach; ?>
    <?php if ($ME): ?><a class="out" href="logout.php"><span class="ic">⏻</span>Out</a><?php endif; ?>
  </nav>
  <div id="toast"></div>
</div><!-- .device -->
<?php endif; ?>
<script src="js/main.js"></script>
<?php if (!empty($pageScripts)) { foreach ($pageScripts as $s) { echo '<script src="js/' . esc($s) . '"></script>' . "\n"; } } ?>
</body>
</html>
