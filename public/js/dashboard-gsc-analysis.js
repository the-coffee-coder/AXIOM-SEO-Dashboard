jQuery(function($){
	
let currentDevice = 'both',
    showingHighlighted = false,
    highlightedKeywords = new Set(),
    sortState = { column: null, asc: true },
    lastSearchTerm = '';

function getSortedRows(rows) {
    if (!sortState.column) return rows.slice();
    let sortKey = sortState.column;
    let sortedRows = rows.slice();
    sortedRows.sort((a, b) => {
        let vA = a[sortKey], vB = b[sortKey];
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
    return sortedRows;
}

// Always call fetchData with highlightSet = empty unless doing a search
function fetchData() {
    let client_id = $('#scc-client-select option:selected').val();
    if (!client_id) {
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

        let sortedRows = getSortedRows(currentRows);

        // Only highlight if performing a keyword search
        let highlightSet = new Set();
        if (lastSearchTerm) {
            sortedRows.forEach(row => {
                if (row.keyword && row.keyword.toLowerCase().includes(lastSearchTerm)) {
                    highlightSet.add(row.keyword);
                }
            });
        }
        renderTable(sortedRows, highlightSet);

        renderWidgets(response.all_stats, response.filtered_stats);
        renderBuckets(response.buckets, response.buckets_high);
    });
}

// Render table, highlighting only as needed
function renderTable(rows, highlightSet = new Set()) {
    let body = '';
    rows.forEach((row, idx) => {
        let isSearchHighlight = highlightSet.has(row.keyword);
        let isClickedHighlight = highlightedKeywords.has(row.keyword);
        let highlight = (isSearchHighlight || isClickedHighlight) ? 'highlighted' : '';
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

    // Update sort indicators
    $('#gsc-keywords-table th[data-sort]').removeClass('sorted-asc sorted-desc');
    if (sortState.column) {
        $(`#gsc-keywords-table th[data-sort="${sortState.column}"]`)
            .addClass(sortState.asc ? 'sorted-asc' : 'sorted-desc');
    }
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
	
// Row click toggles highlight
$('#gsc-keywords-table').on('click', 'tbody tr', function(){
    const keyword = $(this).data('keyword');
    if (highlightedKeywords.has(keyword)) {
        highlightedKeywords.delete(keyword);
        $(this).removeClass('highlighted');
    } else {
        highlightedKeywords.add(keyword);
        $(this).addClass('highlighted');
    }
});
	
// Handle search
$('#gsc-keyword-search-btn').on('click', function() {
    lastSearchTerm = $('#gsc-keyword-search').val().trim().toLowerCase();
    let highlightSet = new Set();
    if (lastSearchTerm) {
        currentRows.forEach(row => {
            if (row.keyword && row.keyword.toLowerCase().includes(lastSearchTerm)) {
                highlightSet.add(row.keyword);
            }
        });
    }
    let sortedRows = getSortedRows(currentRows);
    renderTable(sortedRows, highlightSet);
});
	
// Device filter
$('.gsc-device-btn').on('click', function(){
    $('.gsc-device-btn').removeClass('active');
    $(this).addClass('active');
    currentDevice = $(this).data('device');
    lastSearchTerm = '';
    fetchData();
});

// Date range
$('#update-gsc-daterange').on('click', function() {
    lastSearchTerm = '';
    fetchData();
});

// Client select
$('#scc-client-select').on('change', function() {
    lastSearchTerm = '';
    fetchData();
});

// Search & reset
$('#gsc-keyword-reset-btn').on('click', function(){
    $('#gsc-keyword-search, #gsc-keyword-exclude').val('');
    showingHighlighted = false;
    highlightedKeywords.clear();
    lastSearchTerm = '';
    fetchData();
});

// Toggle highlighted (optional if you use this)
$('#gsc-keyword-toggle-btn').on('click', function() {
    showingHighlighted = !showingHighlighted;
    $('#gsc-keywords-table tbody tr').each(function(){
        if(showingHighlighted && !$(this).hasClass('highlighted')) $(this).hide();
        else $(this).show();
    });
});

    // Row click toggles highlight
	$('#gsc-keywords-table').on('click', 'tbody tr', function(){
		const keyword = $(this).data('keyword');
		if (highlightedKeywords.has(keyword)) {
			highlightedKeywords.delete(keyword);
			$(this).removeClass('highlighted');
		} else {
			highlightedKeywords.add(keyword);
			$(this).addClass('highlighted');
		}
	});

    // Row click highlight/unhighlight
    $('#gsc-keywords-table').on('click', 'tbody tr', function(){
		const keyword = $(this).data('keyword');
		if (highlightedKeywords.has(keyword)) {
			highlightedKeywords.delete(keyword);
			$(this).removeClass('highlighted');
		} else {
			highlightedKeywords.add(keyword);
			$(this).addClass('highlighted');
		}
	});

    // Initial fetch
    fetchData(false);
});