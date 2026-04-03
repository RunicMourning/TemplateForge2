<?php
// Redirects old ?view=users bookmarks to the new settings sub-page
header('Location: index.php?view=settings&section=users');
exit;
