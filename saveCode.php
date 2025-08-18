<?php
$smartlockAuthPerPage     = $smartlockAuthPerPage     ?? 25;
$currentPage  = $currentPage  ?? 1;
$totalRows   = $totalRows   ?? count($smartLockAuths);
$totalPages  = (int)ceil($totalRows / $smartlockAuthPerPage);
$base        = $_GET; unset($base['currentPage']);
$makeUrl     = fn($p) => '?' . http_build_query($base + ['currentPage' => $p]);
?>

<?php if ($totalPages > 1): ?>
  <div class="mt-2 d-flex gap-2">
    <a class="btn btn-secondary <?= $currentPage===1 ? 'disabled' : '' ?>" 
       href="<?= $currentPage>1 ? htmlspecialchars($makeUrl($currentPage-1)) : '#' ?>">
      Previous
    </a>
    <a class="btn btn-secondary <?= $currentPage===$totalPages ? 'disabled' : '' ?>" 
       href="<?= $currentPage<$totalPages ? htmlspecialchars($makeUrl($currentPage+1)) : '#' ?>">
      Next
    </a>
  </div>
<?php endif; ?>