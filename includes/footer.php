    </main>
    <footer>CheapDeals.com LTD &mdash; v3 (COMP1807). PHP + MySQL &middot; <?= $view==='mobile' ? 'Mobile' : 'Desktop' ?> interface.</footer>
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
