<?php
ob_start();
?>
<main id="seo-widgets">
    <div id="gsc_overview" class="widget-container"></div>
    <div id="ga4_overview" class="widget-container"></div>
    <div id="ads_overview" class="widget-container"></div>
    <div id="moz_overview" class="widget-container"></div>
</main>
<?php
$main_content = ob_get_clean();
include '/dashboard-skeleton.php';