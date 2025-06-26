<?php
ob_start();
?>
<main id="organic-search">
  <!-- Device Filter Buttons -->
  <div id="gsc-device-filters" style="margin-bottom: 1em;">
    <button class="gsc-device-btn active" data-device="both">Both</button>
    <button class="gsc-device-btn" data-device="mobile">Mobile</button>
    <button class="gsc-device-btn" data-device="desktop">Desktop</button>
  </div>
  <!-- Keyword Search & Controls -->
  <div id="gsc-keyword-controls" style="margin-bottom: 1em;">
    <input type="text" id="gsc-keyword-search" placeholder="Search keywords..." style="margin-right:0.5em;">
    <input type="text" id="gsc-keyword-exclude" placeholder="Exclude keywords..." style="margin-right:0.5em;">
    <button id="gsc-keyword-search-btn">Search</button>
    <button id="gsc-keyword-reset-btn">Reset</button>
    <button id="gsc-keyword-toggle-btn">Show Only Highlighted</button>
  </div>
  <!-- Widgets -->
  <div id="gsc-widgets-all" class="gsc-widget-row"></div>
  <div id="gsc-widgets-buckets" class="gsc-widget-row"></div>
  <div id="gsc-widgets-highlighted" class="gsc-widget-row"></div>
  <div id="gsc-widgets-buckets-highlighted" class="gsc-widget-row"></div>
  <!-- Data Table -->
  <div id="gsc-table-container">
    <table id="gsc-keywords-table" class="wp-list-table widefat fixed striped">
      <thead>
        <tr>
          <th data-sort="keyword">Keyword</th>
          <th data-sort="clicks">Clicks</th>
          <th data-sort="impressions">Impressions</th>
          <th data-sort="ctr">CTR</th>
          <th data-sort="position">Position</th>
          <th data-sort="search_volume">Search Volume</th>
        </tr>
      </thead>
      <tbody>
        <!-- Populated by AJAX -->
      </tbody>
    </table>
  </div>
</main>
<?php
$main_content = ob_get_clean();
include __DIR__ . '/dashboard-skeleton.php';