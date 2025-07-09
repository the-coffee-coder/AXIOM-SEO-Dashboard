jQuery(function($){
    let currentDevice = 'both', showingHighlighted = false, highlightedKeywords = [];

    function fetchData() {
    // Try to get client_id from the dropdown first
    let client_id = $('#scc-client-select option:selected').val();

    // If dropdown is blank/null/empty, try to get it from the URL (GET variable)
    if (!client_id) {
        // Function to get query parameter by name
        function getQueryParam(name) {
            let url = window.location.href;
            name = name.replace(/[[]]/g, "\\$&");
            let regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)");
            let results = regex.exec(url);
            if (!results) return null;
            if (!results[2]) return '';
            return decodeURIComponent(results[2].replace(/\+/g, " "));
        }
        client_id = getQueryParam('client_id');
    }

    let date_start = $('#date-range-start').val();
    let date_end = $('#date-range-end').val();
    let search = $('#gsc-keyword-search').val();
    let exclude = $('#gsc-keyword-exclude').val();

    $.post(ajaxurl, {
        action: 'gsc_analysis_data',
        client_id, device: currentDevice,
        date_start, date_end, search, exclude
    }, function(response){
        renderTable(response.data, response.filtered);
        renderWidgets(response.all_stats, response.filtered_stats);
        renderBuckets(response.buckets, response.buckets_high);
    });
}

    function renderTable(rows, filtered) {
    let body = '';
    rows.forEach(row => {
        let highlight = filtered.find(f => f.keyword === row.keyword) ? 'highlighted' : '';
        // Convert CTR decimal (e.g. 0.1234) to percentage (12.34%)
        let ctrPercent = (parseFloat(row.ctr) * 100).toFixed(2);
        body += `<tr class="${highlight}" data-keyword="${row.keyword}">
            <td class="keyword">${row.query}</td>
            <td class="clicks">${row.clicks}</td>
            <td class="impressions">${row.impressions}</td>
            <td class="ctr">${ctrPercent}%</td>
            <td class="position">${parseFloat(row.position).toFixed(2)}</td>
            <td class="search-volume"></td>
        </tr>`;
    });
    $('#gsc-keywords-table tbody').html(body);
}

function renderWidgets(all, filtered){
    // Convert CTR decimal to percentage for widgets
    let allCtrPercent = (all.ctr * 100).toFixed(2);
    let filteredCtrPercent = (filtered.ctr * 100).toFixed(2);
    $('#gsc-widgets-all').html(
        `<div>
            <br><h3>All Keywords</h3>
            <br>${all.unique} keywords
            <br>${all.clicks} clicks
            <br>${all.impressions} impressions
            <br>${allCtrPercent}% CTR
            <br>Pos ${all.position.toFixed(2)}
        </div>`
    );
    $('#gsc-widgets-highlighted').html(
        `<div>
            <br><h3>Highlighted Keywords</h3>
            <br>${filtered.unique} keywords
            <br>${filtered.clicks} clicks
            <br>${filtered.impressions} impressions
            <br>${filteredCtrPercent}% CTR
            <br>Pos ${filtered.position.toFixed(2)}
        </div>`
    );
}
    function renderBuckets(buckets, buckets_high) {
        // ...implement bucket bars with percentage width and colors (see summary below)
    }

    // Device filter
    $('.gsc-device-btn').on('click', function(){
        $('.gsc-device-btn').removeClass('active');
        $(this).addClass('active');
        currentDevice = $(this).data('device');
        fetchData();
    });

    // Date range
//    $('#date-range-start, #date-range-end').on('change', fetchData);
	$('#update-gsc-daterange').on('click', fetchData);

    // Client select
    $('#scc-client-select').on('change', fetchData);

    // Search & reset
    $('#gsc-keyword-search-btn').on('click', fetchData);
    $('#gsc-keyword-reset-btn').on('click', function(){
        $('#gsc-keyword-search, #gsc-keyword-exclude').val('');
        showingHighlighted = false;
        fetchData();
    });

    // Toggle highlighted
    $('#gsc-keyword-toggle-btn').on('click', function() {
        showingHighlighted = !showingHighlighted;
        // toggle table rows to only show highlighted or all
        $('#gsc-keywords-table tbody tr').each(function(){
            if(showingHighlighted && !$(this).hasClass('highlighted')) $(this).hide();
            else $(this).show();
        });
    });

    // Row click highlight/unhighlight
    $('#gsc-keywords-table').on('click', 'tbody tr', function(){
        $(this).toggleClass('highlighted');
        // Optionally update highlighted widget
    });

    // Initial fetch
    fetchData();
});