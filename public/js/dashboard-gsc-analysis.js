jQuery(function($){
    let currentDevice = 'both', showingHighlighted = false, highlightedKeywords = [];

    function fetchData() {
        let client_id = $('#scc-client-select').val();
        let date_start = $('#date-range-start').val();
        let date_end = $('#date-range-end').val();
        let search = $('#gsc-keyword-search').val();
        let exclude = $('#gsc-keyword-exclude').val();
		console.log(date_start + ' - ' date_end);
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
            body += `<tr class="${highlight}" data-keyword="${row.keyword}">
                <td>${row.keyword}</td>
                <td>${row.clicks}</td>
                <td>${row.impressions}</td>
                <td>${parseFloat(row.ctr).toFixed(2)}%</td>
                <td>${parseFloat(row.position).toFixed(2)}</td>
                <td></td>
            </tr>`;
        });
        $('#gsc-keywords-table tbody').html(body);
    }
    function renderWidgets(all, filtered){
        $('#gsc-widgets-all').html(
            `<div>All: ${all.unique} keywords, ${all.clicks} clicks, ${all.impressions} impressions, ${all.ctr.toFixed(2)}% CTR, Pos ${all.position.toFixed(2)}</div>`
        );
        $('#gsc-widgets-highlighted').html(
            `<div>Highlighted: ${filtered.unique} keywords, ${filtered.clicks} clicks, ${filtered.impressions} impressions, ${filtered.ctr.toFixed(2)}% CTR, Pos ${filtered.position.toFixed(2)}</div>`
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
    $('#date-range-start, #date-range-end').on('change', fetchData);

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
    $('#gsc-keywords-table').on('click', 'tr', function(){
        $(this).toggleClass('highlighted');
        // Optionally update highlighted widget
    });

    // Initial fetch
    fetchData();
});