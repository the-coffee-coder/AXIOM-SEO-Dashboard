jQuery(function($){
  // Use just the origin (protocol + host) as the base
  const baseUrl = window.location.origin; // e.g. https://dashboard.axiomad.com

  // Rewrite all nav links with data-slug to include client_id
  function rewriteNavLinks(clientId) {
    $('#seo-nav a[data-slug]').each(function(){
      const slug = $(this).data('slug');
      // If a client is selected, include the query; otherwise link to tool root
      const href = clientId
        ? `${baseUrl}/${slug}?client_id=${encodeURIComponent(clientId)}`
        : `${baseUrl}/${slug}`;
      $(this).attr('href', href);
    });
  }

  // Master updater that triggers each widget’s AJAX call
  function updateWidgets(clientId, site) {
    updateGSC(clientId, site);
//     updateGA4(site);
    // updateAds(site);
    // updateMoz(site);
    // future widgets: add calls here
  }

  // GSC widget loader
  function updateGSC(clientId, site) {
    const url = `${SCC_API.root}gsc-summary?client_id=${clientId}&site=${encodeURIComponent(site)}`;
    fetch(url, { headers:{ 'X-WP-Nonce': SCC_API.nonce } })
      .then(res => {
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.json();
      })
      .then(payload => {
        const cur = payload.current;
        const chg = payload.change;
        const arrow = (isUp, pct) => `
          <span style="font-weight:bold;color:${isUp?'green':'red'};">
            ${isUp?'↑':'↓'} (${Math.abs(pct)}%)
          </span>`;
        $('#gsc_overview').html(`
          <h3>Google Search Console</h3>
          <p>(Last 30 Days vs. Prior 30)</p>
          <ul>
            <li><strong>Impressions:</strong> ${cur.impressions.toLocaleString()} ${arrow(chg.impressions_is_up, chg.impressions_diff_pct)}</li>
            <li><strong>Clicks:</strong>      ${cur.clicks.toLocaleString()} ${arrow(chg.clicks_is_up, chg.clicks_diff_pct)}</li>
            <li><strong>Avg. CTR:</strong>    ${cur.ctr}% ${arrow(chg.ctr_is_up, chg.ctr_diff_pct)}</li>
            <li><strong>Avg. Pos.:</strong>   ${cur.position} ${arrow(chg.position_is_up, chg.position_diff_pct)}</li>
          </ul>
        `);
      })
      .catch(err => {
        console.error('Error loading GSC data:', err);
        $('#gsc_overview').html('<p>Error loading GSC data.</p>');
      });
  }

  // Placeholder loaders
  function updateGA4(site) { /* … */ }
  function updateAds(site) { /* … */ }
  function updateMoz(site) { /* … */ }

  // When the client selection changes
  $('#scc-client-select').on('change', function(){
    const clientId = $(this).val();
    const site     = $(this).find('option:selected').data('site');
    if ( ! clientId ) {
      $('.widget-container').html('Please select a client…');
      rewriteNavLinks('');
      return;
    }
    updateWidgets(clientId, site);
    rewriteNavLinks(clientId);
  });

  // On page load: if a client_id query parameter is present, pre-select & fire
  const params = new URLSearchParams(window.location.search);
  const initialId   = params.get('client_id');
  if ( initialId ) {
    const $opt = $('#scc-client-select option[value="'+initialId+'"]');
    const site    = $opt.data('site');
    if ( site ) {
      $('#scc-client-select').val(initialId);
      updateWidgets(initialId, site);
      rewriteNavLinks(initialId);
    }
  }
});
