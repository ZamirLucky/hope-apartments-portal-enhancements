<!-- Pagination Controls (Optional) -->
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link btn btn-primary me-2"
                                           href="?page=<?= $page - 1 ?>&search=<?= urlencode($searchTerm) ?>"
                                           aria-label="Previous">
                                            <i class="fas fa-chevron-left"></i> Previous
                                        </a>
                                    </li>
                                <?php endif; ?>
                                <?php if ($start + $perPage < $totalSmartlocks): ?>
                                    <li class="page-item">
                                        <a class="page-link btn btn-primary"
                                           href="?page=<?= $page + 1 ?>&search=<?= urlencode($searchTerm) ?>"
                                           aria-label="Next">
                                            Next <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>