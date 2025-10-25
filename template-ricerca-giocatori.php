<?php
/* Template Name: Pagina Ricerca Giocatori */
get_header(); 
?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<div id="player-search-container">
    <main id="main" class="site-main">
        <div class="search-layout">
            <div class="filters-column">
                <h3>Filtri di Ricerca</h3>
                <form id="player-filters-form">
                    <div class="filter-group">
                        <label for="filter_ruolo">Ruolo</label>
                        <select id="filter_ruolo" name="ruolo[]" multiple="multiple" style="width:100%;">
                            <?php
                            $roles = get_terms(['taxonomy' => 'sp_position', 'hide_empty' => false]);
                            if (!is_wp_error($roles)) {
                                foreach ($roles as $role) {
                                    echo '<option value="' . esc_attr($role->slug) . '">' . esc_html($role->name) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="filter-group"><label>Età</label><div class="range-inputs"><input type="number" name="eta_min" placeholder="Min" min="16" max="50"><input type="number" name="eta_max" placeholder="Max" min="16" max="50"></div></div>
                    <div class="filter-group"><label>Overall (OVR)</label><div class="range-inputs"><input type="number" name="ovr_min" placeholder="Min" min="1" max="99"><input type="number" name="ovr_max" placeholder="Max" min="1" max="99"></div></div>
                    <div class="filter-group"><label>Stipendio</label><div class="range-inputs"><input type="number" name="stipendio_min" placeholder="Min" step="100000"><input type="number" name="stipendio_max" placeholder="Max" step="100000"></div></div>
                    
                    <input type="hidden" name="action" value="filter_players">
                    <input type="hidden" name="orderby" id="orderby_input" value="ovr">
                    <?php wp_nonce_field('player_search_nonce', 'security'); ?>
                </form>
            </div>

            <div class="results-column">
                <div class="sorting-container">
                    <span>Ordina per:</span>
                    <button class="sort-button active" data-sort="ovr">Overall</button>
                    <button class="sort-button" data-sort="eta">Età</button>
                </div>
                <div id="player-results-container">
                    <div class="loader-wrapper"><div class="loader"></div></div>
                </div>
            </div>
        </div>
    </main>
</div>

<style>
    #player-search-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
    .search-layout { display: flex; flex-wrap: wrap; gap: 40px; }
    .filters-column { flex: 1; min-width: 280px; background: #f9f9f9; padding: 20px; border-radius: 8px; align-self: flex-start; }
    .results-column { flex: 3; min-width: 300px; }
    .filter-group { margin-bottom: 20px; }
    .filter-group label { font-weight: bold; display: block; margin-bottom: 8px; }
    .filter-group select, .filter-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
    .range-inputs { display: flex; gap: 10px; }
    .select2-container--default .select2-selection--multiple { border: 1px solid #ddd !important; border-radius: 5px !important; cursor: text; min-height: 48px; }
    .select2-selection__rendered::after { content: ''; clear: both; display: table; }
    .select2-container--default .select2-selection--multiple .select2-selection__choice { background-color: #0073aa !important; color: white !important; border: none !important; border-radius: 4px !important; padding: 2px 8px 2px 18px !important; margin: 4px 0 0 5px !important; position: relative; }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove { color: rgba(255,255,255,0.8) !important; font-weight: bold; position: absolute; left: 6px; top: 50%; transform: translateY(-50%); }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover { color: white !important; }
    .sorting-container { margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
    .sorting-container span { font-weight: bold; }
    .sort-button { background-color: #f0f0f0; border: 1px solid #ccc; padding: 8px 15px; border-radius: 5px; cursor: pointer; }
    .sort-button.active { background-color: #0073aa; color: white; border-color: #0073aa; }
    #player-results-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; position: relative; }
    a.player-card-link { text-decoration: none; color: inherit; display: block; transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out; }
    a.player-card-link:hover { transform: translateY(-5px); }
    .player-card { display: flex; align-items: center; gap: 15px; border: 1px solid #ddd; border-radius: 8px; padding: 15px; background: #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.05); height: 100%; }
    a.player-card-link:hover .player-card { border-color: #0073aa; }
    .player-photo img { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; }
    .player-details { flex: 1; }
    .player-card h4 { margin: 0 0 5px 0; font-size: 1.2em; color: #333; }
    .player-card p { margin: 4px 0; font-size: 0.9em; color: #555; }
    .player-card p strong { color: #1a1a1a; }
    .no-results { grid-column: 1 / -1; text-align: center; font-size: 1.2em; color: #888; }
    .loader-wrapper { display: none; position: absolute; top: 100px; left: 0; width: 100%; text-align: center; }
    .loader { display: inline-block; border: 8px solid #f3f3f3; border-radius: 50%; border-top: 8px solid #3498db; width: 60px; height: 60px; animation: spin 1s linear infinite; }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    $('#filter_ruolo').select2({
        placeholder: 'Seleziona uno o più ruoli',
        width: '100%'
    });

    let searchTimeout;
    function performSearch() {
        const form = $('#player-filters-form');
        const resultsContainer = $('#player-results-container');
        $.ajax({
            type: 'POST',
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            data: form.serialize(),
            beforeSend: function() {
                resultsContainer.html('<div class="loader-wrapper" style="display: block;"><div class="loader"></div></div>');
                resultsContainer.css('min-height', '200px');
            },
            success: function(response) {
                resultsContainer.html(response);
            },
            error: function() {
                resultsContainer.html('<p class="no-results">Si è verificato un errore.</p>');
            },
        });
    }
    
    performSearch();

    $('#player-filters-form').on('change keyup', 'input, select', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(performSearch, 500);
    });
    
    $('.sort-button').on('click', function() {
        const sortBy = $(this).data('sort');
        if ($(this).hasClass('active')) return;
        $('.sort-button').removeClass('active');
        $(this).addClass('active');
        $('#orderby_input').val(sortBy);
        performSearch();
    });
});
</script>

<?php
get_footer();
?>