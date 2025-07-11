jQuery(function($){

let currentDevice = 'both', showingHighlighted = false, highlightedKeywords = new Set();
let currentRows = [];
let currentFiltered = [];
let sortState = { column: null, asc: true };

// "lastSearchTerm" tracks the most recent keyword search
let lastSearchTerm = '';

// Sorting logic as its own function
function getSortedRows(rows) {
    if (!sortState.column) return rows.slice(); // No sorting, return as-is

    let sortKey = sortState.column;
    let sortedRows = rows.slice();
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
    return sortedRows;
}

function fetchData(highlightBySearch = false) {
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
    let search = $('#gsc-keyword-search').val(); // used only for search highlighting
    let exclude = $('#gsc-keyword-exclude').val();

    // Save last search term for highlighting
    lastSearchTerm = (highlightBySearch && search) ? search.trim().toLowerCase() : '';

    $.post(ajaxurl, {
        action: 'gsc_analysis_data',
        client_id, device: currentDevice,
        date_start, date_end, search, exclude
    }, function(response){
        currentRows = response.data.slice();
        currentFiltered = response.filtered.slice();

        // Sort data
        let rowsToRender = getSortedRows(currentRows);

        // Build set of keywords to highlight only for non-empty keyword search
        let highlightSet = new Set();
        if (highlightBySearch && lastSearchTerm !== '') {
            rowsToRender.forEach(row => {
                // Only highlight if keyword matches search (not exclude)
                if (row.keyword && row.keyword.toLowerCase().includes(lastSearchTerm)) {
                    highlightSet.add(row.keyword);
                }
            });
        }

        renderTable(rowsToRender, highlightSet);

        renderWidgets(response.all_stats, response.filtered_stats);
        renderBuckets(response.buckets, response.buckets_high);
    });
}

// Main renderTable function
function renderTable(rows, searchHighlightSet = new Set()) {
    let body = '';
    rows.forEach((row, idx) => {
        let keywordVal = row.keyword || row.query; // Support both 'keyword' and 'query'
        let isClicked = highlightedKeywords.has(keywordVal);

        // Highlight on search if the row matches the lastSearchTerm
        let isSearchMatch = false;
        if (lastSearchTerm !== '') {
            isSearchMatch = keywordVal && keywordVal.toLowerCase().includes(lastSearchTerm);
        }

        let highlight = (isSearchMatch || isClicked) ? 'highlighted' : '';
        let ctrPercent = (parseFloat(row.ctr) * 100).toFixed(2);
        body += `<tr class="${highlight}" data-keyword="${keywordVal}">
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
        const $th = $(`#gsc-keywords-table th[data-sort="${sortState.column}"]`);
        $th.addClass(sortState.asc ? 'sorted-asc' : 'sorted-desc');
    }
}

// Sort click handler
$('#gsc-keywords-table').on('click', 'th[data-sort]', function() {
    let sortKey = $(this).data('sort');
    if (!sortKey) return;

    if (sortState.column === sortKey) {
        sortState.asc = !sortState.asc;
    } else {
        sortState.column = sortKey;
        sortState.asc = true;
    }

    let sortedRows = getSortedRows(currentRows);

    // The current highlight set should match what was last rendered (search or click)
    let highlightSet = new Set();
    if (lastSearchTerm !== '') {
        sortedRows.forEach(row => {
            if (row.keyword && row.keyword.toLowerCase().includes(lastSearchTerm)) {
                highlightSet.add(row.keyword);
            }
        });
    }
    renderTable(sortedRows, highlightSet);

    // Remove all sort classes, then add to the sorted column
    $('#gsc-keywords-table th[data-sort]')
      .removeClass('sorted-asc sorted-desc');
    let $th = $(`#gsc-keywords-table th[data-sort="${sortState.column}"]`);
    $th.addClass(sortState.asc ? 'sorted-asc' : 'sorted-desc');
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
    lastSearchTerm = '';
    fetchData(false);
});

// Date range
$('#update-gsc-daterange').on('click', function(){
    lastSearchTerm = '';
    fetchData(false);
});

// Client select
$('#scc-client-select').on('change', function(){
    lastSearchTerm = '';
    fetchData(false);
});

// Search button handler
$('#gsc-keyword-search-btn').on('click', function() {
    lastSearchTerm = $('#gsc-keyword-search').val().trim().toLowerCase();
    fetchData(true);
});

// Reset
$('#gsc-keyword-reset-btn').on('click', function(){
    $('#gsc-keyword-search, #gsc-keyword-exclude').val('');
    showingHighlighted = false;
    highlightedKeywords.clear(); // clear all highlights on reset
    lastSearchTerm = '';
    fetchData(false);
});

// Toggle highlighted
$('#gsc-keyword-toggle-btn').on('click', function(){
    showingHighlighted = !showingHighlighted;
    $('#gsc-keywords-table tbody tr').each(function(){
        if(showingHighlighted && !$(this).hasClass('highlighted')) $(this).hide();
        else $(this).show();
    });
    // Toggle button text
    if (showingHighlighted) {
        $(this).text('Show All Keywords');
    } else {
        $(this).text('Show Only Highlighted');
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