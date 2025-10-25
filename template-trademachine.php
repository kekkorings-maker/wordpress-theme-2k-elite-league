<?php

/**
 * Template Name: Trade Machine
 */

get_header();

// --- CONFIGURAZIONE ---
$salary_cap_limit = 100000000;
$current_season_salary_field = '20252026';
$current_season_cut_cost_field = 'cut_cost_2025_26';
$current_season_id = 59;

// Mappa colori NBA
$team_color_map = array(
    'Atlanta Hawks'          => array('primary' => '#e03a3e', 'secondary' => '#e8e8e8'),
    'Boston Celtics'         => array('primary' => '#008248', 'secondary' => '#e8e8e8'),
    'Brooklyn Nets'          => array('primary' => '#000000', 'secondary' => '#e8e8e8'),
    'Charlotte Hornets'      => array('primary' => '#036f88', 'secondary' => '#e8e8e8'),
    'Chicago Bulls'          => array('primary' => '#ce1141', 'secondary' => '#000000'),
    'Cleveland Cavaliers'    => array('primary' => '#bb945b', 'secondary' => '#000000'),
    'Dallas Mavericks'       => array('primary' => '#007dc5', 'secondary' => '#f4f4f4'),
    'Denver Nuggets'         => array('primary' => '#0d213e', 'secondary' => '#FFC627'),
    'Detroit Pistons'        => array('primary' => '#dd0031', 'secondary' => '#003DA6'),
    'Golden State Warriors'  => array('primary' => '#1d428a', 'secondary' => '#FDB927'),
    'Houston Rockets'        => array('primary' => '#000000', 'secondary' => '#e8e8e8'),
    'Indiana Pacers'         => array('primary' => '#fdbb30', 'secondary' => '#002D62'),
    'Los Angeles Clippers'   => array('primary' => '#0a2240', 'secondary' => '#e8e8e8'),
    'Los Angeles Lakers'     => array('primary' => '#552583', 'secondary' => '#FDB927'),
    'Memphis Grizzlies'      => array('primary' => '#5d76a9', 'secondary' => '#12173F'),
    'Miami Heat'             => array('primary' => '#98002e', 'secondary' => '#e8e8e8'),
    'Milwaukee Bucks'        => array('primary' => '#00471b', 'secondary' => '#EEE1C6'),
    'Minnesota Timberwolves' => array('primary' => '#0c2340', 'secondary' => '#e8e8e8'),
    'New Orleans Pelicans'   => array('primary' => '#002b5c', 'secondary' => '#e8e8e8'),
    'New York Knicks'        => array('primary' => '#ff671b', 'secondary' => '#026BB5'),
    'Oklahoma City Thunder'  => array('primary' => '#007ac1', 'secondary' => '#FEBB30'),
    'Orlando Magic'          => array('primary' => '#0b77bd', 'secondary' => '#000000'),
    'Philadelphia 76ers'     => array('primary' => '#006bb5', 'secondary' => '#f4f4f4'),
    'Phoenix Suns'           => array('primary' => '#000000', 'secondary' => '#E56020'),
    'Portland Trail Blazers' => array('primary' => '#cf0a2c', 'secondary' => '#000000'),
    'Sacramento Kings'       => array('primary' => '#5a2d81', 'secondary' => '#e8e8e8'),
    'San Antonio Spurs'      => array('primary' => '#000000', 'secondary' => '#e8e8e8'),
    'Toronto Raptors'        => array('primary' => '#bd1b21', 'secondary' => '#000000'),
    'Utah Jazz'              => array('primary' => '#147020', 'secondary' => '#f9a01b'),
    'Washington Wizards'     => array('primary' => '#cf0a2c', 'secondary' => '#0D2240')
);
// --- FINE CONFIGURAZIONE ---

// Funzione helper per convertire stipendi in numeri
function convert_salary_to_number_tm($salary_string) {
    if (empty($salary_string) || !is_string($salary_string)) return 0;
    $cleaned = str_replace('.', '', $salary_string);
    if (!is_numeric($cleaned)) return 0;
    return floatval($cleaned);
}

// 1. Recuperiamo tutti i team
$all_teams = get_posts(array(
    'post_type' => 'sp_team',
    'posts_per_page' => -1,
    'orderby' => 'post_title',
    'order' => 'ASC'
));

// 2. Recuperiamo TUTTI i giocatori
$all_players = get_posts(array(
    'post_type' => 'sp_player',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'orderby' => 'post_title',
    'order' => 'ASC'
));

// 3. Inizializziamo la struttura dati dei team
$teams_data = array();
foreach ($all_teams as $team) {
    $bonus = convert_salary_to_number_tm(get_field('bonus_' . $current_season_salary_field, $team->ID));
    $malus = convert_salary_to_number_tm(get_field('malus_' . $current_season_salary_field, $team->ID));
    
    // Leggiamo le pick, il logo e convertiamo l'ID del team in nome
    $picks_data = array();
    if (have_rows('future_picks', $team->ID)) {
        $pick_index = 0;
        while (have_rows('future_picks', $team->ID)) {
            the_row();
            
            $year = get_sub_field('year');
            $round = get_sub_field('round');
            $origin_team_id = get_sub_field('origin_team');
            $origin_team_logo = get_sub_field('origin_team_logo');

            if ($year && $round) {
                $round_text = $round;
                if ($round == 1) $round_text = '1st';
                elseif ($round == 2) $round_text = '2nd';
                
                $description = $year . ' ' . $round_text . ' Round Pick';
                
                if ($origin_team_id) {
                    $origin_team_name = get_the_title($origin_team_id);
                    $description .= ' (from ' . $origin_team_name . ')';
                }

                $picks_data[] = array(
                    'id'          => 'pick_' . $team->ID . '_' . $pick_index++,
                    'description' => $description,
                    'type'        => 'pick',
                    'logo'        => $origin_team_logo
                );
            }
        }
    }
    
    $teams_data[$team->ID] = array(
        'id' => $team->ID,
        'name' => $team->post_title,
        'logo' => get_the_post_thumbnail_url($team->ID, 'thumbnail'),
        'bonus' => $bonus,
        'malus' => $malus,
        'players' => array(),
        'picks' => $picks_data,
        'current_salary_total' => 0
    );
}

// 4. Associamo i giocatori alla loro squadra attuale
foreach ($all_players as $player) {
    $current_team_id = null;
    $sp_current_teams = get_post_meta($player->ID, 'sp_current_team', false);
    
    if (!empty($sp_current_teams) && is_array($sp_current_teams)) {
        foreach ($sp_current_teams as $team_id) {
            if (!empty($team_id) && $team_id != 0) {
                $current_team_id = $team_id;
                break;
            }
        }
    }
    
    if (empty($current_team_id)) {
        $single_team = get_post_meta($player->ID, 'sp_current_team', true);
        if (!empty($single_team) && $single_team != 0) {
            $current_team_id = $single_team;
        }
    }
    
    if (empty($current_team_id) || !isset($teams_data[$current_team_id])) {
        continue;
    }
    
    $salary = convert_salary_to_number_tm(get_field($current_season_salary_field, $player->ID));
    
    $teams_data[$current_team_id]['players'][] = array(
        'id' => $player->ID,
        'name' => $player->post_title,
        'photo' => get_the_post_thumbnail_url($player->ID, 'thumbnail'),
        'salary' => $salary,
        'type' => 'player'
    );
    
    $teams_data[$current_team_id]['current_salary_total'] += $salary;
}

?>

<style>
#trade-machine-app { max-width: 1400px; margin: 20px auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
.tm-selectors { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; align-items: center; }
.tm-selectors select { padding: 8px 12px; font-size: 16px; border: 2px solid #ddd; border-radius: 6px; }
.tm-columns { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 15px; }
.tm-column { border: 1px solid #ddd; border-radius: 8px; background: #f9f9f9; overflow: hidden; }
.tm-team-header { padding: 15px; color: #fff; display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #333, #555); }
.tm-team-header h3 { margin: 0; font-size: 18px; }
.tm-team-header img { max-height: 40px; border-radius: 4px; }
.tm-financials { background: #2c3e50; color: #fff; padding: 10px 15px; font-size: 13px; font-weight: 500; }
.tm-roster { background: #fff; }
.tm-tradable-asset { display: flex; align-items: center; padding: 10px 15px; border-bottom: 1px solid #eee; cursor: pointer; transition: all 0.2s ease; }
.tm-tradable-asset:hover { background-color: #e3f2fd; transform: translateX(2px); }
.tm-tradable-asset:last-child { border-bottom: none; }
.tm-player-img { width: 40px; height: 40px; border-radius: 50%; margin-right: 12px; object-fit: cover; border: 2px solid #eee; }
.tm-pick-icon { font-size: 24px; width: 40px; text-align: center; margin-right: 12px; color: #555; }
.tm-asset-info { flex-grow: 1; }
.tm-asset-name { font-weight: 600; font-size: 14px; margin-bottom: 2px; }
.tm-player-salary { font-size: 12px; color: #666; font-weight: 500; }
.trade-valid { background: linear-gradient(135deg, #27ae60, #2ecc71); color: white; }
.trade-invalid { background: linear-gradient(135deg, #e74c3c, #c0392b); color: white; }
.trade-popup { position: absolute; background: white; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 8px 25px rgba(0,0,0,0.15); padding: 5px; z-index: 1000; min-width: 150px; }
.trade-popup div { padding: 10px 12px; cursor: pointer; border-radius: 4px; font-size: 14px; transition: background 0.2s; }
.trade-popup div:hover { background: #f8f9fa; }
#trade-validator { text-align: center; margin: 30px 0; }
#trade-validator button { font-size: 18px; padding: 12px 30px; cursor: pointer; background: linear-gradient(135deg, #3498db, #2980b9); color: white; border: none; border-radius: 6px; font-weight: 600; transition: all 0.2s; }
#trade-validator button:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3); }
#trade-validation-result { margin-top: 20px; font-size: 18px; font-weight: bold; padding: 15px; border-radius: 8px; transition: all 0.3s; }
.tm-roster-section-header { background: #34495e; color: white; padding: 10px 15px; font-weight: bold; }
.tm-roster-section-header.acquiring { background: #d32f2f; }
.tm-roster-section-header.picks { background: #16a085; }
</style>

<div id="trade-machine-app">
    <h1 style="text-align: center; margin-bottom: 30px; color: #2c3e50;">2KELITE TRADE MACHINE</h1>
    
    <div class="tm-selectors"></div>
    
    <div id="trade-validator">
        <button id="try-trade-btn">Verifica Trade</button>
        <div id="trade-validation-result"></div>
    </div>

    <div id="trade-summary-container" style="display: none; margin-top: 30px; text-align: center;">
        <h3 style="color: #27ae60;">🎉 Proposta Pronta! 🎉</h3>
        <textarea id="trade-summary-text" readonly style="width: 100%; max-width: 600px; height: 200px; margin: 0 auto 10px; padding: 10px; font-family: monospace; border: 1px solid #ddd; border-radius: 6px;"></textarea>
        <button id="copy-trade-summary-btn" style="font-size: 16px; padding: 10px 20px; cursor: pointer; background: #3498db; color: white; border: none; border-radius: 6px; font-weight: 600;">Copia Testo 📋</button>
    </div>
    
    <div class="tm-columns"></div>
</div>
<script>
jQuery(document).ready(function($) {
    const selectorContainer = $('.tm-selectors');
    const allData = <?php echo json_encode($teams_data); ?>;
    const salaryCap = <?php echo $salary_cap_limit; ?>;
    const teamColorMap = <?php echo json_encode($team_color_map); ?>;
    const maxTeams = 4;
    let selectedTeams = {};
    let tradedAssets = {};

    function formatCurrency(num) { 
        return '$' + new Intl.NumberFormat('en-US').format(num); 
    }

    function getTeamColors(teamName) {
        return teamColorMap[teamName] || { primary: '#34495e', secondary: '#2c3e50' };
    }

    function recalculateFinances(teamId) { 
        const teamData = selectedTeams[teamId]; 
        let newPlayersTotalSalary = 0; 
        teamData.players.forEach(p => { newPlayersTotalSalary += p.salary; }); 
        if (tradedAssets[teamId]) {
            tradedAssets[teamId].forEach(asset => {
                if (asset.type === 'player') {
                    newPlayersTotalSalary += asset.salary;
                }
            });
        }
        const totalWithBonusMalus = newPlayersTotalSalary - teamData.bonus + teamData.malus; 
        const remainingSpace = salaryCap - totalWithBonusMalus; 
        const financialsDiv = $(`#team-col-${teamId} .tm-financials`); 
        financialsDiv.html(`
            <div>Stipendi Totali: ${formatCurrency(newPlayersTotalSalary)}</div>
            <div>Spazio Rimanente: <strong>${formatCurrency(remainingSpace)}</strong></div>
        `);
        teamData.newRemainingSpace = remainingSpace; 
    }

    function renderRoster(teamId) { 
        const teamData = selectedTeams[teamId]; 
        const rosterDiv = $(`#team-col-${teamId} .tm-roster`); 
        rosterDiv.empty(); 
        
        if (tradedAssets[teamId] && tradedAssets[teamId].length > 0) {
            const acquiringCount = tradedAssets[teamId].length;
            rosterDiv.append(`<div class="tm-roster-section-header acquiring">Acquisisce ${acquiringCount} Asset${acquiringCount > 1 ? 's' : ''}</div>`);
            
            tradedAssets[teamId].forEach(asset => {
                let assetHtml = '';
                if (asset.type === 'player') {
                    assetHtml = `
                        <div class="tm-tradable-asset" style="background-color: #ffebee;" data-asset-id="${asset.id}" data-asset-type="player" data-from-team="${asset.fromTeam}" data-current-team="${teamId}" data-is-traded="true">
                            <img class="tm-player-img" src="${asset.photo || 'https://via.placeholder.com/40x40/ddd/999?text=?'}" alt="${asset.name}">
                            <div class="tm-asset-info">
                                <div class="tm-asset-name">${asset.name}</div>
                                <div class="tm-player-salary">${formatCurrency(asset.salary)}</div>
                            </div>
                            <div style="font-size: 12px; color: #666; font-style: italic;">Da ${allData[asset.fromTeam].name}</div>
                        </div>`;
                } else if (asset.type === 'pick') {
                    assetHtml = `
                        <div class="tm-tradable-asset" style="background-color: #e0f2f1;" data-asset-id="${asset.id}" data-asset-type="pick" data-from-team="${asset.fromTeam}" data-current-team="${teamId}" data-is-traded="true">
                            ${asset.logo 
                                ? `<img class="tm-player-img" src="${asset.logo}" alt="Pick Logo">` 
                                : `<span class="tm-pick-icon">📄</span>`
                            }
                            <div class="tm-asset-info">
                                <div class="tm-asset-name">${asset.description}</div>
                            </div>
                            <div style="font-size: 12px; color: #666; font-style: italic;">Da ${allData[asset.fromTeam].name}</div>
                        </div>`;
                }
                rosterDiv.append(assetHtml);
            });
        }
        
        if (teamData.players.length > 0) {
            rosterDiv.append(`<div class="tm-roster-section-header">${teamData.name} Roster</div>`);
            teamData.players.sort((a, b) => b.salary - a.salary).forEach(player => { 
                rosterDiv.append(`
                    <div class="tm-tradable-asset" data-asset-id="${player.id}" data-asset-type="player" data-team-id="${teamId}">
                        <img class="tm-player-img" src="${player.photo || 'https://via.placeholder.com/40x40/ddd/999?text=?'}" alt="${player.name}">
                        <div class="tm-asset-info">
                            <div class="tm-asset-name">${player.name}</div>
                            <div class="tm-player-salary">${formatCurrency(player.salary)}</div>
                        </div>
                    </div>`); 
            });
        }

        if (teamData.picks.length > 0) {
            rosterDiv.append(`<div class="tm-roster-section-header picks">Draft Picks</div>`);
            teamData.picks.forEach(pick => {
                rosterDiv.append(`
                    <div class="tm-tradable-asset" data-asset-id="${pick.id}" data-asset-type="pick" data-team-id="${teamId}">
                        ${pick.logo 
                            ? `<img class="tm-player-img" src="${pick.logo}" alt="Pick Logo">` 
                            : `<span class="tm-pick-icon">📄</span>`
                        }
                        <div class="tm-asset-info">
                            <div class="tm-asset-name">${pick.description}</div>
                        </div>
                    </div>`);
            });
        }
    }

    function renderTeamColumn(teamId) { 
        const teamData = allData[teamId]; 
        selectedTeams[teamId] = JSON.parse(JSON.stringify(teamData)); 
        const totalWithBonusMalus = teamData.current_salary_total - teamData.bonus + teamData.malus; 
        const remainingSpace = salaryCap - totalWithBonusMalus; 
        selectedTeams[teamId].newRemainingSpace = remainingSpace; 
        const teamColors = getTeamColors(teamData.name);
        const columnHtml = `<div class="tm-column" id="team-col-${teamId}"><div class="tm-team-header" style="background: linear-gradient(135deg, ${teamColors.primary}, ${teamColors.secondary})"><h3>${teamData.name}</h3><img src="${teamData.logo || 'https://via.placeholder.com/40x40/fff/999?text=LOGO'}" alt="${teamData.name}"></div><div class="tm-financials"><div>Stipendi Totali: ${formatCurrency(teamData.current_salary_total)}</div><div>Spazio Rimanente: <strong>${formatCurrency(remainingSpace)}</strong></div></div><div class="tm-roster"></div></div>`; 
        $('.tm-columns').append(columnHtml); 
        renderRoster(teamId); 
    }

    function createSelectors() { 
        for (let i = 1; i <= maxTeams; i++) { 
            const select = $(`<select id="team-selector-${i}" data-index="${i}"><option value="">-- Seleziona Squadra ${i} --</option></select>`); 
            for (const teamId in allData) { select.append(`<option value="${teamId}">${allData[teamId].name}</option>`); } 
            selectorContainer.append(select); 
        } 
    }

    function generateTradeSummary() {
        let summaryText = "Ciao, ho una proposta di trade: 🤝\n\n";

        for (const receivingTeamId in tradedAssets) {
            const receivedAssets = tradedAssets[receivingTeamId];

            if (receivedAssets.length > 0) {
                const teamName = allData[receivingTeamId].name;
                summaryText += `*${teamName}* riceve: ✅\n`;

                receivedAssets.forEach(asset => {
                    const assetName = asset.type === 'player' ? asset.name : asset.description;
                    summaryText += `    - ${assetName}\n`;
                });

                summaryText += "\n";
            }
        }
        
        return summaryText;
    }

    selectorContainer.on('change', 'select', function() { 
        const teamId = $(this).val(); 
        const oldTeamId = $(this).data('old-val'); 
        if (oldTeamId) { $(`#team-col-${oldTeamId}`).remove(); delete selectedTeams[oldTeamId]; delete tradedAssets[oldTeamId];} 
        if (teamId) { renderTeamColumn(teamId); $(this).data('old-val', teamId); } 
    });

    $('.tm-columns').on('click', '.tm-tradable-asset', function(e) { 
        const assetId = $(this).data('asset-id'); 
        const assetType = $(this).data('asset-type');
        const isTraded = $(this).data('is-traded');
        
        if (isTraded) {
            const currentTeamId = $(this).data('current-team');
            const originalTeamId = $(this).data('from-team');
            
            let assetToReturn = null;
            if (tradedAssets[currentTeamId]) {
                const assetIndex = tradedAssets[currentTeamId].findIndex(a => a.id == assetId);
                if (assetIndex !== -1) {
                    assetToReturn = tradedAssets[currentTeamId][assetIndex];
                    tradedAssets[currentTeamId].splice(assetIndex, 1);
                }
            }
            
            if (assetToReturn && selectedTeams[originalTeamId]) {
                const targetArray = assetType === 'player' ? 'players' : 'picks';
                selectedTeams[originalTeamId][targetArray].push(assetToReturn);
            }
            
            Object.keys(selectedTeams).forEach(id => {
                renderRoster(id);
                recalculateFinances(id);
            });
            return;
        }
        
        const originTeamId = $(this).data('team-id'); 
        const involvedTeamIds = Object.keys(selectedTeams).filter(id => id != originTeamId); 
        
        if (involvedTeamIds.length === 0) {
            alert('Seleziona almeno due squadre per fare un trade!');
            return;
        }
        
        const tradeAsset = (targetTeamId) => { 
            let assetToMove;
            const sourceArrayKey = assetType === 'player' ? 'players' : 'picks';
            
            selectedTeams[originTeamId][sourceArrayKey] = selectedTeams[originTeamId][sourceArrayKey].filter(a => { 
                if (a.id == assetId) { 
                    assetToMove = a; 
                    return false; 
                } 
                return true; 
            }); 
            
            if (!tradedAssets[targetTeamId]) {
                tradedAssets[targetTeamId] = [];
            }
            
            assetToMove.fromTeam = originTeamId;
            tradedAssets[targetTeamId].push(assetToMove);
            
            renderRoster(originTeamId); 
            renderRoster(targetTeamId);
            Object.keys(selectedTeams).forEach(id => recalculateFinances(id)); 
        }; 
        
        if (involvedTeamIds.length === 1) { 
            tradeAsset(involvedTeamIds[0]); 
        } else { 
            $('.trade-popup').remove(); 
            const popup = $('<div class="trade-popup"></div>'); 
            involvedTeamIds.forEach(id => { 
                popup.append(`<div data-target-id="${id}">Trade to: ${selectedTeams[id].name}</div>`); 
            }); 
            $('body').append(popup); 
            popup.css({ top: e.pageY + 5, left: e.pageX + 5 }); 
            
            popup.on('click', 'div', function() { 
                const targetTeamId = $(this).data('target-id'); 
                tradeAsset(targetTeamId); 
                popup.remove(); 
            }); 
        } 
    });

    $('#try-trade-btn').on('click', function() { 
        let allTradesValid = true; 
        let resultText = "Trade Valida!"; 
        const summaryContainer = $('#trade-summary-container');
        
        if (Object.keys(selectedTeams).length < 2) { 
            resultText = "Seleziona almeno due squadre."; 
            allTradesValid = false; 
        } else { 
            for(const teamId in selectedTeams) { 
                const teamData = selectedTeams[teamId];
                let totalPlayers = teamData.players.length;
                if (tradedAssets[teamId]) {
                    totalPlayers += tradedAssets[teamId].filter(a => a.type === 'player').length;
                }
                
                if (totalPlayers > 25) { 
                    allTradesValid = false; 
                    resultText = `Trade Non Valida: ${teamData.name} avrebbe ${totalPlayers} giocatori (massimo 25 consentito)`; 
                    break; 
                }
                
                if (teamData.newRemainingSpace < 0) { 
                    allTradesValid = false; 
                    resultText = `Trade Non Valida: ${teamData.name} sfora il Salary Cap di ${formatCurrency(Math.abs(teamData.newRemainingSpace))}`; 
                    break; 
                } 
            } 
        } 
        
        const resultDiv = $('#trade-validation-result'); 
        resultDiv.text(resultText); 
        resultDiv.removeClass('trade-valid trade-invalid').addClass(allTradesValid ? 'trade-valid' : 'trade-invalid'); 

        if (allTradesValid) {
            const summaryText = generateTradeSummary();
            $('#trade-summary-text').val(summaryText);
            summaryContainer.slideDown(); 
        } else {
            summaryContainer.slideUp(); 
        }
    });

    $('#copy-trade-summary-btn').on('click', function() {
        const textToCopy = $('#trade-summary-text').val();
        const button = $(this);

        navigator.clipboard.writeText(textToCopy).then(function() {
            const originalText = button.html();
            button.html('Copiato! ✅').css('background', '#27ae60');
            
            setTimeout(function() {
                button.html(originalText).css('background', '#3498db');
            }, 2000); 

        }, function(err) {
            alert('Errore: Impossibile copiare il testo.');
            console.error('Errore nel copiare il testo: ', err);
        });
    });

    createSelectors();
    $(document).on('click', function(e) { if (!$(e.target).closest('.trade-popup, .tm-tradable-asset').length) { $('.trade-popup').remove(); } });
});
</script>

<?php
get_footer();
?>