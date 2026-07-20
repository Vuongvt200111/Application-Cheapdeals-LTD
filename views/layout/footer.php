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
<?php endif; ?>
<script src="js/main.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<?php if (!empty($pageScripts)) { foreach ($pageScripts as $s) { echo '<script src="js/' . esc($s) . '"></script>' . "\n"; } } ?>
<?php if (!$ME || $ME['role'] === 'user'): ?>
  <!-- Floating Live Chat Bubble Overlay (BUG-015) -->
  <div id="live-chat-bubble" onclick="location.href='support.php'" style="position:fixed; bottom:20px; right:20px; width:60px; height:60px; border-radius:50%; background:linear-gradient(135deg, #00e5ff, #ff2bd6); box-shadow:0 8px 24px rgba(0,229,255,0.4); display:flex; align-items:center; justify-content:center; cursor:pointer; z-index:9999; transition:transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 10px 28px rgba(255,43,214,0.6)';" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 8px 24px rgba(0,229,255,0.4)';">
    <span style="font-size:28px;">💬</span>
    <span style="position:absolute; top:-2px; right:-2px; background:red; color:white; font-size:10px; font-weight:bold; border-radius:50%; padding:3px 7px; border:2px solid #070b16;">1</span>
  </div>
  
  <style>
    @media (min-width: 820px) {
      .view-mobile #live-chat-bubble {
        position: absolute !important;
        bottom: 80px !important;
        right: 20px !important;
      }
    }
    <?php if (basename($_SERVER['PHP_SELF']) === 'support.php'): ?>
      #live-chat-bubble { display: none !important; }
    <?php endif; ?>
  </style>
<?php endif; ?>
</body>
</html>
