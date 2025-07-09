jQuery(function($){
	
let currentDevice = 'both', showingHighlighted = false, highlightedKeywords = [];
let currentRows = [];
let currentFiltered = [];
let sortState = { column: null, asc: true };
	
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
        currentRows = response.data.slice();
        currentFiltered = response.filtered.slice();
        renderTable(currentRows, currentFiltered);
        renderWidgets(response.all_stats, response.filtered_stats);
        renderBuckets(response.buckets, response.buckets_high);
    });
}

function renderTable(rows, filtered) {
    currentRows = rows.slice();
    let body = '';
    rows.forEach((row, idx) => {
        let highlight = filtered.find(f => f.keyword === row.keyword) ? 'highlighted' : '';
        let ctrPercent = (parseFloat(row.ctr) * 100).toFixed(2);
        body += `<tr class="${highlight}" data-keyword="${row.keyword}">
            <td class="row-number">${idx + 1}</td>
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

$('#gsc-keywords-table').on('click', 'th[data-sort]', function() {
    let sortKey = $(this).data('sort');
    if (!sortKey) return;

    if (sortState.column === sortKey) {
        sortState.asc = !sortState.asc;
    } else {
        sortState.column = sortKey;
        sortState.asc = true;
    }

    let sortedRows = currentRows.slice();
    sortedRows.sort((a, b) => {
        let vA = a[sortKey];
        let vB = b[sortKey];

        if (['clicks', 'impressions', 'ctr', 'position', 'search_volume'].includes(sortKey)) {
            vA = parseFloat(vA) || 0;
            vB = parseFloat(vB) || 0;
        } else if (typeof vA === 'string' && typeof vB === 'string') {
            vA = vA.toLowerCase();
            vB = vB.toLowerCase();
        }

        if (vA < vB) return sortState.asc ? -1 : 1;
        if (vA > vB) return sortState.asc ? 1 : -1;
        return 0;
    });

    // Use currentFiltered for the second argument
    renderTable(sortedRows, currentFiltered);
	
	// Clear all indicators
	$('#gsc-keywords-table th[data-sort] .sort-indicator').text('');

	// Set indicator on the sorted column
	let indicator = sortState.asc ? '▲' : '▼';
	$(`#gsc-keywords-table th[data-sort="${sortState.column}"] .sort-indicator`).text(indicator);
});

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