<?php
/* Template Name: Pagina Aste Free Agent */
get_header(); 
?>

<div id="auction-system-container">
    <main id="main" class="site-main">
        <header class="page-header">
            </header>

        <div class="auction-page-layout">
            <div class="player-list-column">
                <h3>Free Agent</h3>
                <div class="filter-container">
                    <label for="role-filter">Filtra per Ruolo:</label>
                    <select id="role-filter">
                        <option value="all">Tutti i ruoli</option>
                        <option value="PG">PG (Playmaker)</option>
                        <option value="SG">SG (Guardia Tiratrice)</option>
                        <option value="SF">SF (Ala Piccola)</option>
                        <option value="PF">PF (Ala Grande)</option>
                        <option value="C">C (Centro)</option>
                    </select>
                </div>
                <ul class="player-list">
                    <?php
                    $auction_players_query = new WP_Query(array('post_type' => 'asta_player','posts_per_page' => -1,'meta_key' => 'asta_ovr','orderby' => 'meta_value_num','order' => 'DESC'));
                    if ($auction_players_query->have_posts()) :
                        while ($auction_players_query->have_posts()) : $auction_players_query->the_post();
                            $player_id = get_the_ID();
                            $ruolo1 = get_post_meta($player_id, 'asta_ruolo_1', true);
                            $ruolo2 = get_post_meta($player_id, 'asta_ruolo_2', true);
                            $ovr = get_post_meta($player_id, 'asta_ovr', true);
                            $is_auction_active = get_post_meta($player_id, '_auction_active', true);
                            $end_timestamp = (int) get_post_meta($player_id, '_auction_end_timestamp', true);
                            $status_class = 'status-not-started';
                            if ($is_auction_active === 'yes') {
                                $status_class = ($end_timestamp > time()) ? 'status-in-progress' : 'status-concluded';
                            }
                            echo '<li class="' . $status_class . '" data-player-id="' . $player_id . '" data-ruolo1="' . esc_attr(strtoupper($ruolo1)) . '" data-ruolo2="' . esc_attr(strtoupper($ruolo2)) . '"><a href="#"><span class="player-name-list">' . get_the_title() . '</span><span class="player-meta-list">' . esc_html($ruolo1) . ' / ' . esc_html($ruolo2) . ' - OVR: ' . esc_html($ovr) . '</span></a></li>';
                        endwhile;
                        wp_reset_postdata();
                    else :
                        echo '<li>Nessun giocatore in asta.</li>';
                    endif;
                    ?>
                </ul>
            </div>
            <div class="auction-detail-column">
                <div id="auction-placeholder"><p>← Seleziona un giocatore dalla lista per vedere i dettagli dell'asta.</p></div>
                <div id="auction-detail-content" style="display: none;"></div>
            </div>
        </div>

        <div id="auction-data-storage" style="display: none;">
            <?php
            if ($auction_players_query->have_posts()) :
                $auction_players_query->rewind_posts();
                while ($auction_players_query->have_posts()) : $auction_players_query->the_post();
                    $player_id = get_the_ID();
                    $player_name = get_the_title();
                    $player_slug = sanitize_title($player_name);
                    $ratings_link = 'https://www.2kratings.com/' . $player_slug;
                    $ruolo1_h = get_post_meta($player_id, 'asta_ruolo_1', true);
                    $ruolo2_h = get_post_meta($player_id, 'asta_ruolo_2', true);
                    $ovr_h = (int)get_post_meta($player_id, 'asta_ovr', true);
                    $is_auction_active = get_post_meta($player_id, '_auction_active', true);
                    $end_timestamp = $is_auction_active === 'yes' ? get_post_meta($player_id, '_auction_end_timestamp', true) : 0;
                    $team_name = $is_auction_active === 'yes' ? get_post_meta($player_id, '_highest_bid_team_name', true) : '';
                    $amount = $is_auction_active === 'yes' ? get_post_meta($player_id, '_highest_bid_amount_per_year', true) : 0;
                    $years = $is_auction_active === 'yes' ? get_post_meta($player_id, '_highest_bid_years', true) : 0;
                    $is_2way_win = $is_auction_active === 'yes' ? get_post_meta($player_id, '_highest_bid_is_2way', true) : false;
                    $two_way_text_win = $is_2way_win ? ' (2-Way Contract)' : '';
                    $total_value = $amount * $years;
            ?>
                <div id="data-player-<?php echo $player_id; ?>">
                    <div class="player-auction-box">
                        <h2 class="player-name"><?php echo esc_html($player_name); ?></h2>
                        <div class="player-meta-detail"><?php echo esc_html($ruolo1_h); ?> / <?php echo esc_html($ruolo2_h); ?> - OVR: <?php echo esc_html($ovr_h); ?></div>
                        <a href="<?php echo esc_url($ratings_link); ?>" class="ratings-link" target="_blank" rel="noopener noreferrer">GUARDA LE STATISTICHE SU 2KRATINGS</a>
                        <div class="auction-status-display">
                             <h4 class="auction-title"><?php echo $is_auction_active === 'yes' ? sprintf('MIGLIOR OFFERTA %s', strtoupper(esc_html($team_name))) : 'ASTA NON IN CORSO'; ?></h4>
                            <?php if ($is_auction_active === 'yes'): ?>
                                <p class="best-offer-text"><?php printf('Contratto offerto: %s x %d anni%s', number_format(floatval($amount), 0, ',', '.'), $years, $two_way_text_win); ?></p>
                                <p class="total-value-text"><?php printf('Per un totale di: %s', number_format(floatval($total_value), 0, ',', '.')); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="countdown-timer" data-end-timestamp="<?php echo esc_attr($end_timestamp); ?>" style="<?php echo $is_auction_active === 'yes' ? 'visibility: visible;' : 'visibility: hidden;'; ?>">Tempo: <span class="time">--:--:--</span></div>
                        <div class="start-auction-prompt" style="<?php echo $is_auction_active === 'yes' ? 'display: none;' : 'display: block;'; ?>"><p>Nessuna offerta. Avvia tu l'asta!</p></div>
                        <?php if (is_user_logged_in()) : ?>
                            <form class="player-bid-form">
                                <input type="text" inputmode="numeric" class="bid-amount-formatted" required placeholder="Es: 1.000.000">
                                <input type="hidden" class="bid-amount-raw" name="amount">
                                <input type="number" name="years" min="1" max="3" value="1" required placeholder="Anni">
                                <input type="hidden" name="player_id" value="<?php echo $player_id; ?>">
                                <input type="hidden" name="action" value="place_player_bid">
                                <?php wp_nonce_field('player_auction_nonce', 'security'); ?>
                                
                                <div class="two-way-contract-option">
                                    <input type="checkbox" id="is_2way_<?php echo $player_id; ?>" name="is_2way">
                                    <label for="is_2way_<?php echo $player_id; ?>">2-Way Contract</label>
                                </div>
                                
                                <button type="submit">Offri</button>
                            </form>
                        <?php else : ?>
                            <p class="login-prompt">Effettua il <a href="<?php echo wp_login_url(get_permalink()); ?>">login</a> per fare un'offerta.</p>
                        <?php endif; ?>
                        <div class="auction-messages"></div>
                        <div class="bid-history">
                            <h4>Storico Offerte</h4>
                            <ul class="history-list">
                                <?php
                                $history = get_post_meta($player_id, '_bid_history', true);
                                if (is_array($history) && !empty($history)) {
                                    foreach (array_reverse($history) as $bid) {
                                        $two_way_text_hist = !empty($bid['is_2way']) ? ' (2-Way Contract)' : '';
                                        printf('<li>%s offre %s per %d anni%s</li>', esc_html($bid['team']), number_format($bid['amount'], 0, ',', '.'), esc_html($bid['years']), $two_way_text_hist);
                                    }
                                } else { echo '<li class="no-bids">Nessuna offerta ancora.</li>'; }
                                ?>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php
                endwhile;
                wp_reset_postdata();
            endif;
            ?>
        </div>
    </main>
</div>

<style>
    #auction-system-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
    #auction-system-container .auction-page-layout { display: flex; gap: 30px; }
    #auction-system-container .player-list-column { flex: 0 0 350px; border-right: 1px solid #ddd; padding-right: 20px; }
    #auction-system-container .auction-detail-column { flex: 1; }
    #auction-system-container ul.player-list { list-style: none; padding: 0; margin: 0; }
    #auction-system-container ul.player-list li a { display: block; padding: 10px 15px; text-decoration: none; border-bottom: 1px solid #eee; transition: background-color 0.2s, color 0.2s; cursor: pointer; }
    #auction-system-container ul.player-list li.status-not-started a { background-color: #ffffff; color: #0073aa; }
    #auction-system-container ul.player-list li.status-in-progress a { background-color: #ffc107; color: #1a1a1a; font-weight: bold; }
    #auction-system-container ul.player-list li.status-concluded a { background-color: #dc3545; color: #ffffff; font-weight: bold; }
    #auction-system-container ul.player-list li a:hover, #auction-system-container ul.player-list li.active a { background-color: #0073aa !important; color: #ffffff !important; }
    #auction-system-container .player-name-list { display: block; font-size: 1.1em; font-weight: bold; }
    #auction-system-container .player-meta-list { display: block; font-size: 0.9em; color: #555; }
    #auction-system-container li.status-in-progress .player-meta-list, #auction-system-container li.status-concluded .player-meta-list { color: inherit; }
    #auction-system-container .player-meta-detail { font-size: 1.1em; color: #555; margin-top: -15px; margin-bottom: 15px; }
    #auction-system-container .filter-container { margin-bottom: 20px; }
    #auction-system-container .filter-container label { margin-right: 10px; font-weight: bold; }
    #auction-system-container .filter-container select { padding: 8px; border-radius: 5px; border: 1px solid #ccc; width: 100%; }
    #auction-system-container #auction-placeholder { text-align: center; padding-top: 50px; color: #888; font-size: 1.2em; }
    #auction-system-container .player-auction-box { text-align: center; padding: 20px; background: #f9f9f9; border-radius: 5px; }
    #auction-system-container .player-name { margin-top: 0; margin-bottom: 5px; }
    #auction-system-container .ratings-link { display: inline-block; margin-bottom: 20px; padding: 8px 16px; background-color: #28a745; color: #fff; text-decoration: none; border-radius: 5px; font-size: 0.85em; font-weight: bold; text-transform: uppercase; transition: background-color 0.2s; }
    #auction-system-container .ratings-link:hover { background-color: #218838; color: #fff; }
    #auction-system-container .auction-title { font-size: 1.1em; color: #333; font-weight: bold; text-transform: uppercase; margin-bottom: 15px; }
    #auction-system-container .best-offer-text, #auction-system-container .total-value-text { font-size: 1em; color: #444; margin: 5px 0; text-align: center; padding-left: 0; }
    #auction-system-container .countdown-timer { margin-top: 15px; font-weight: bold; color: #d54e21; font-size: 1.2em; min-height: 1.5em; }
    #auction-system-container .player-bid-form { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; align-items: center; margin-top: 15px; }
    #auction-system-container .player-bid-form input { padding: 12px; font-size: 1.1em; border: 1px solid #ccc; border-radius: 5px; text-align: right; }
    #auction-system-container .player-bid-form input[type="text"] { width: 150px; }
    #auction-system-container .player-bid-form input[type="number"] { width: 100px; }
    #auction-system-container .player-bid-form button { background: #0073aa; color: white; border: none; padding: 12px 20px; cursor: pointer; font-size: 1.1em; border-radius: 5px; }
    #auction-system-container .two-way-contract-option { width: 100%; display: flex; align-items: center; justify-content: center; margin-top: 10px; gap: 8px; }
    #auction-system-container .two-way-contract-option label { margin: 0; font-weight: bold; color: #555; order: 2; }
    #auction-system-container .two-way-contract-option input { order: 1; }
    #auction-system-container .auction-messages { margin-top: 10px; font-weight: bold; min-height: 1.2em; width: 100%; }
    #auction-system-container .success { color: green; } 
    #auction-system-container .error { color: red; }
    #auction-system-container .bid-history { margin-top: 30px; text-align: left; }
    #auction-system-container .bid-history h4 { text-align: center; margin-bottom: 10px; }
    #auction-system-container .bid-history ul { list-style: none; padding: 0; max-height: 200px; overflow-y: auto; border: 1px solid #eee; border-radius: 5px; background: #fff; }
    #auction-system-container .bid-history li { padding: 8px 12px; border-bottom: 1px solid #eee; font-size: 0.9em; }
    #auction-system-container .bid-history li:last-child { border-bottom: none; }
    #auction-system-container .bid-history li.no-bids { color: #888; text-align: center; }
    @media (max-width: 820px) {
        #auction-system-container .auction-page-layout { display: block; }
        #auction-system-container .player-list-column { border-right: none; padding-right: 0; width: 100%; }
        #auction-system-container .auction-detail-column { display: none; }
        #auction-system-container ul.player-list { border: 1px solid #ddd; border-radius: 5px; overflow: hidden; }
        #auction-system-container .player-list > li { border-bottom: 1px solid #ddd; }
        #auction-system-container .player-list > li:last-child { border-bottom: none; }
        .injected-auction-details { list-style: none; padding: 0; margin: 0; border-top: 2px solid #0073aa; }
    }
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    let currentIntervalId = null;
    function startCountdown(timerElement) { if (currentIntervalId) clearInterval(currentIntervalId); const endTime = timerElement.data('end-timestamp'); if (!endTime || endTime == 0) { timerElement.css('visibility', 'hidden'); return; } timerElement.css('visibility', 'visible'); currentIntervalId = setInterval(function() { const now = Math.floor(Date.now() / 1000); const distance = endTime - now; if (distance < 0) { clearInterval(currentIntervalId); timerElement.find('.time').text("TERMINATA"); timerElement.closest('.player-auction-box').find('.player-bid-form').hide(); const playerId = timerElement.closest('.player-auction-box').find('input[name="player_id"]').val(); if (playerId) { $('.player-list li[data-player-id="' + playerId + '"]').removeClass('status-in-progress').addClass('status-concluded'); } return; } const h = ('0' + Math.floor(distance / 3600)).slice(-2); const m = ('0' + Math.floor((distance % 3600) / 60)).slice(-2); const s = ('0' + Math.floor(distance % 60)).slice(-2); timerElement.find('.time').text(h + ":" + m + ":" + s); }, 1000); }
    function displayAuctionDetails(playerId) { const sourceDataElement = $('#data-player-' + playerId); if (sourceDataElement.length === 0) { return; } const sourceDataHtml = sourceDataElement.html(); const detailContent = $('#auction-detail-content'); detailContent.html(sourceDataHtml); $('#auction-placeholder').hide(); detailContent.show(); const timer = detailContent.find('.countdown-timer'); if (timer.length) { startCountdown(timer); } }
    $('.player-list').on('click', 'li a', function(e) { e.preventDefault(); const listItem = $(this).closest('li'); const playerId = listItem.data('player-id'); if (listItem.hasClass('active') && $(window).width() <= 820) { listItem.removeClass('active'); listItem.next('.injected-auction-details').slideUp(300, function() { $(this).remove(); }); return; } $('.player-list li').removeClass('active'); listItem.addClass('active'); $('.injected-auction-details').slideUp(300, function() { $(this).remove(); }); const sourceHtml = $('#data-player-' + playerId).html(); if ($(window).width() > 820) { const detailContent = $('#auction-detail-content'); detailContent.html(sourceHtml); $('#auction-placeholder').hide(); detailContent.show(); const timer = detailContent.find('.countdown-timer'); if (timer.length) startCountdown(timer); } else { const newDetailPanel = $('<li class="injected-auction-details" style="display: none;"></li>').html(sourceHtml); listItem.after(newDetailPanel); newDetailPanel.slideDown(400, function() { const timer = $(this).find('.countdown-timer'); if (timer.length) startCountdown(timer); }); } });
    $('#auction-system-container').on('input', '.bid-amount-formatted', function() { let rawValue = $(this).val().replace(/[^0-9]/g, ''); $(this).closest('form').find('.bid-amount-raw').val(rawValue); let formattedValue = rawValue.replace(/\B(?=(\d{3})+(?!\d))/g, "."); $(this).val(formattedValue); });
    $('#role-filter').on('change', function() { const selectedRole = $(this).val().toUpperCase(); const playerListItems = $('.player-list > li'); $('#auction-detail-content').hide(); $('#auction-placeholder').show(); playerListItems.removeClass('active'); $('.injected-auction-details').remove(); if (selectedRole === 'ALL') { playerListItems.slideDown(200); } else { playerListItems.each(function() { const playerItem = $(this); const ruolo1 = playerItem.data('ruolo1').toUpperCase(); const ruolo2 = playerItem.data('ruolo2').toUpperCase(); if (ruolo1 === selectedRole || ruolo2 === selectedRole) { playerItem.slideDown(200); } else { playerItem.slideUp(200); } }); } });
    $('#auction-system-container').on('submit', '.player-bid-form', function(e) { e.preventDefault(); const form = $(this); const auctionBox = form.closest('.player-auction-box'); const messagesDiv = auctionBox.find('.auction-messages'); const playerId = form.find('input[name="player_id"]').val(); messagesDiv.html('Invio...').removeClass('success error'); $.ajax({ type: 'POST', url: '<?php echo admin_url('admin-ajax.php'); ?>', data: form.serialize(), dataType: 'json', success: function(response) { if (response.success) { messagesDiv.html(response.data.message).addClass('success').removeClass('error'); const hiddenDataContainer = $('#data-player-' + playerId); hiddenDataContainer.find('.auction-title').html(response.data.display_info.title); hiddenDataContainer.find('.best-offer-text').html(response.data.display_info.contract); hiddenDataContainer.find('.total-value-text').html(response.data.display_info.total); hiddenDataContainer.find('.active-auction-info').show(); hiddenDataContainer.find('.countdown-timer').attr('data-end-timestamp', response.data.new_end_timestamp); hiddenDataContainer.find('.start-auction-prompt').hide(); hiddenDataContainer.find('.history-list .no-bids').remove(); hiddenDataContainer.find('.history-list').prepend(response.data.new_history_item); auctionBox.html(hiddenDataContainer.find('.player-auction-box').html()); startCountdown(auctionBox.find('.countdown-timer')); const playerLi = $('.player-list li[data-player-id="' + playerId + '"]'); playerLi.removeClass('status-not-started status-concluded').addClass('status-in-progress'); } else { messagesDiv.html(response.data.message).addClass('error').removeClass('success'); } }, error: function() { messagesDiv.html('Errore di comunicazione.').addClass('error').removeClass('success'); } }); });
});
</script>

<?php
get_footer();
?>