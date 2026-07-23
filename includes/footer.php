    </main>
    <footer>CheapDeals.com LTD &mdash; v3 (COMP1807). PHP + MySQL &middot; <?= $view==='mobile' ? 'Mobile' : 'Desktop' ?> interface.
<!-- Floating Live Support Chat Bubble Widget -->
<a id="floating-chat-bubble" href="support.php" title="Live Support Chat" style="position:fixed;bottom:24px;right:24px;z-index:9999;width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,var(--cyan),#0072ff);color:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 6px 20px rgba(0,242,254,0.4);font-size:24px;text-decoration:none;transition:transform 0.3s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
  💬
</a>

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
