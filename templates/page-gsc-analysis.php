<?php
ob_start();
?>
<main id="organic-search">
    <!-- Your GSC Analysis widgets and content go here -->
</main>
<?php
$main_content = ob_get_clean();
include '/dashboard-skeleton.php';