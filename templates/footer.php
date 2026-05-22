<?php
/**
 * templates/footer.php — Footer + JS loader
 * Set $extra_js = [BASE_URL . '/assets/js/page.js'] sebelum include
 */
$base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
?>
  </main>
</div><!-- /.page-wrap -->

<footer class="footer">
  <div class="container">
    <div class="footer__inner">
      <span class="footer__brand">EventRes</span>
      <span style="color:var(--gray-500)">© <?= date('Y') ?> Event Reservation · All rights reserved</span>
      <div style="display:flex;gap:1.5rem;">
        <a href="#" style="color:var(--gray-500);font-size:0.83rem;transition:color 150ms" onmouseenter="this.style.color='#60A5FA'" onmouseleave="this.style.color=''">Privacy</a>
        <a href="#" style="color:var(--gray-500);font-size:0.83rem;transition:color 150ms" onmouseenter="this.style.color='#60A5FA'" onmouseleave="this.style.color=''">Terms</a>
        <a href="#" style="color:var(--gray-500);font-size:0.83rem;transition:color 150ms" onmouseenter="this.style.color='#60A5FA'" onmouseleave="this.style.color=''">Contact</a>
      </div>
    </div>
  </div>
</footer>

<script src="<?= $base ?>/assets/js/app.js" defer></script>
<?php if (!empty($extra_js)): foreach ($extra_js as $js): ?>
  <script src="<?= htmlspecialchars($js) ?>" defer></script>
<?php endforeach; endif; ?>
</body>
</html>
