<?php
/** Közös admin lábléc. Opcionális: $adminScript — oldalspecifikus JS (nyers kód). */
?>
  </div>
</main>

<script src="../assets/app.js?v=8" defer></script>
<?php if (!empty($adminScript)): ?>
<script>
<?= $adminScript ?>
</script>
<?php endif; ?>
</body>
</html>
