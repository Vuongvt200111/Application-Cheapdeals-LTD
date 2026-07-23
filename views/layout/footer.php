<?php
global $view;
if (!isset($view)) {
    $view = $GLOBALS['view'] ?? currentView();
}
?>
    </main>
    <footer>CheapDeals.com LTD &mdash; v3 (COMP1807). PHP + MySQL &middot; <?= $view==='mobile' ? 'Mobile' : 'Desktop' ?> interface.</footer>

<?php if ($view === 'mobile'): ?>
  </div><!-- .screen -->
  
  <?php if (!$ME || $ME['role'] === 'user'): ?>
    <?php if (basename($_SERVER['PHP_SELF']) !== 'support.php'): ?>
      <div id="live-chat-bubble" onclick="location.href='support.php'" style="position:absolute; bottom:80px; right:20px; width:52px; height:52px; border-radius:50%; background:linear-gradient(135deg, #00e5ff, #ff2bd6); box-shadow:0 6px 20px rgba(0,229,255,0.4); display:flex; align-items:center; justify-content:center; cursor:pointer; z-index:9999; transition:transform 0.2s;" onmouseover="this.style.transform='scale(1.1)';" onmouseout="this.style.transform='scale(1)';">
        <span style="font-size:24px;">💬</span>
        <span style="position:absolute; top:2px; right:2px; width:10px; height:10px; background:red; color:white; font-size:10px; font-weight:bold; border-radius:50%; padding:0; border:2px solid #070b16; display:inline-block;"></span>
      </div>
      
      <!-- CSS to make it position: fixed on actual mobile devices where min-width is small -->
      <style>
        @media (max-width: 819px) {
          #live-chat-bubble {
            position: fixed !important;
            bottom: 80px !important;
            right: 20px !important;
          }
        }
      </style>
    <?php endif; ?>
  <?php endif; ?>

  <nav class="bottom-nav">
    <?php if (isset($items) && is_array($items)): ?>
      <?php foreach ($items as $it): ?>
        <a class="<?= $it[0]===$cur ? 'active' : '' ?>" href="<?= esc($it[0]) ?>"><span class="ic"><?= $it[2] ?></span><?= esc($it[1]) ?></a>
      <?php endforeach; ?>
    <?php endif; ?>
    <?php if ($ME): ?><a class="out" href="logout.php"><span class="ic">⏻</span>Out</a><?php endif; ?>
  </nav>
  <div id="toast"></div>
</div><!-- .device -->
<?php else: ?>
  <!-- Floating Live Chat Bubble Overlay for Desktop (BUG-015) -->
  <?php if (!$ME || $ME['role'] === 'user'): ?>
    <?php if (basename($_SERVER['PHP_SELF']) !== 'support.php'): ?>
      <div id="live-chat-bubble" onclick="location.href='support.php'" style="position:fixed; bottom:20px; right:20px; width:60px; height:60px; border-radius:50%; background:linear-gradient(135deg, #00e5ff, #ff2bd6); box-shadow:0 8px 24px rgba(0,229,255,0.4); display:flex; align-items:center; justify-content:center; cursor:pointer; z-index:9999; transition:transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 10px 28px rgba(255,43,214,0.6)';" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 8px 24px rgba(0,229,255,0.4)';">
        <span style="font-size:28px;">💬</span>
        <span style="position:absolute; top:2px; right:2px; width:10px; height:10px; background:red; color:white; font-size:10px; font-weight:bold; border-radius:50%; padding:0; border:2px solid #070b16; display:inline-block;"></span>
      </div>
    <?php endif; ?>
  <?php endif; ?>
<?php endif; ?>

<script src="js/main.js?v=<?= time() ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<?php if (!empty($pageScripts)) { foreach ($pageScripts as $s) { echo '<script src="js/' . esc($s) . '?v=' . time() . '"></script>' . "\n"; } } ?>

<script>
function updateChatBadge() {
  const badgeMobile = document.querySelector('#live-chat-bubble span:nth-child(2)');
  const badgeDesktop = document.querySelector('#live-chat-bubble span:nth-child(2)');
  const unreadBadge = document.getElementById('chat-unread-badge');

  fetch('support.php?action=check_unread_count')
    .then(r => r.json())
    .then(data => {
      const count = (data && data.unread_count) ? data.unread_count : 0;
      document.querySelectorAll('#live-chat-bubble span:nth-child(2), #chat-unread-badge').forEach(el => {
        if (!el) return;
        if (count > 0) {
          el.innerText = count;
          el.style.width = 'auto';
          el.style.height = 'auto';
          el.style.padding = '2px 6px';
          el.style.borderRadius = '10px';
          el.style.fontSize = '10px';
          el.style.display = 'inline-block';
        } else {
          el.innerText = '';
          el.style.width = '10px';
          el.style.height = '10px';
          el.style.padding = '0';
          el.style.borderRadius = '50%';
          el.style.display = 'inline-block';
        }
      });
    }).catch(e => {});
}
document.addEventListener('DOMContentLoaded', updateChatBadge);
</script>

</body>
</html>
