<?php
$end_date   = date('Y-m-d');
$start_date = date('Y-m-d', strtotime('-29 days')); // includes today

ob_start();
?>
<main id="organic-search">
  <!-- Date Range Filter -->
  <div id="date-range-container" style="margin-bottom: 20px;">
      <label for="date-range" style="font-weight: 600; margin-right: 8px;">Date Range:</label>
      <input type="date" id="date-range-start" name="date-range-start" value="<?php echo esc_attr($start_date); ?>" style="margin-right: 4px;">
      to
      <input type="date" id="date-range-end" name="date-range-end" value="<?php echo esc_attr($end_date); ?>" style="margin-left: 4px;">
    <button id="update-gsc-daterange">Update</button>
  </div>
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
  <div id="gsc-widget-contianer" style="margin-bottom: 1em;">
    <div id="gsc-widgets-all" class="gsc-widget-row"></div>
    <div id="gsc-widgets-buckets" class="gsc-widget-row"></div>
    <div id="gsc-widgets-highlighted" class="gsc-widget-row"></div>
    <div id="gsc-widgets-buckets-highlighted" class="gsc-widget-row"></div>
  </div>
  <!-- Data Table -->
  <div id="gsc-table-container">
    <table id="gsc-keywords-table" class="wp-list-table widefat fixed striped">
      <thead>
	  <tr>
		<th class="row-number">#</th>
		<th data-sort="keyword">Keyword
		  <span class="sort-indicator">
			<span class="arrow arrow-up"></span>
			<span class="arrow arrow-down"></span>
		  </span>
		</th>
		<th data-sort="clicks">Clicks
		  <span class="sort-indicator">
			<span class="arrow arrow-up"></span>
			<span class="arrow arrow-down"></span>
		  </span>
		</th>
		<th data-sort="impressions">Impressions
		  <span class="sort-indicator">
			<span class="arrow arrow-up"></span>
			<span class="arrow arrow-down"></span>
		  </span>
		</th>
		<th data-sort="ctr">Avg. CTR
		  <span class="sort-indicator">
			<span class="arrow arrow-up"></span>
			<span class="arrow arrow-down"></span>
		  </span>
		</th>
		<th data-sort="position">Avg. Position
		  <span class="sort-indicator">
			<span class="arrow arrow-up"></span>
			<span class="arrow arrow-down"></span>
		  </span>
		</th>
		<th data-sort="search_volume">Search Volume
		  <span class="sort-indicator">
			<span class="arrow arrow-up"></span>
			<span class="arrow arrow-down"></span>
		  </span>
		</th>
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