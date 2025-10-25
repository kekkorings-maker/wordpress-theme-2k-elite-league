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
function convert_salary_to_number_tm( $salary_string ) {
    if ( empty( $salary_string ) || ! is_string( $salary_string ) ) return 0;
    $cleaned = str_replace( '.', '', $salary_string );
    if ( ! is_numeric( $cleaned ) ) return 0;
    return floatval( $cleaned );
}

// 1. Recuperiamo tutti i team
$all_teams = get_posts(array(
    'post_type' => 'sp_team', 
    'posts_per_page' => -1, 
    'orderby' => 'post_title', 
    'order' => 'ASC'
));

// 2. Recuperiamo TUTTI i giocatori (senza filtri nella query)
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
    
    $teams_data[$team->ID] = array(
        'id' => $team->ID,
        'name' => $team->post_title,
        'logo' => get_the_post_thumbnail_url($team->ID, 'thumbnail'),
        'bonus' => $bonus,
        'malus' => $malus,
        'players' => array(),
        'current_salary_total' => 0
    );
}

// 4. Associamo i giocatori SOLO alla loro squadra attuale
foreach ($all_players as $player) {
    $current_team_id = null;
    
    // Prima prova sp_current_team che sappiamo essere l'array corretto
    $sp_current_teams = get_post_meta($player->ID, 'sp_current_team', false);
    
    if (!empty($sp_current_teams) && is_array($sp_current_teams)) {
        // Trova il primo valore non-zero nell'array
        foreach ($sp_current_teams as $team_id) {
            if (!empty($team_id) && $team_id != 0) {
                $current_team_id = $team_id;
                break;
            }
        }
    }
    
    // Se non trovato, prova il metodo get_post_meta con true (valore singolo)
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
        'salary' => $salary
    );
    
    $teams_data[$current_team_id]['current_salary_total'] += $salary;
}

// 5. I giocatori tagliati sono già inclusi nei malus, quindi non li aggiungiamo qui

?>

<style>
#trade-machine-app { 
    max-width: 1400px; 
    margin: 20px auto; 
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
} 

.tm-selectors { 
    display: flex; 
    flex-wrap: wrap; 
    gap: 10px; 
    margin-bottom: 20px; 
    align-items: center; 
} 

.tm-selectors select { 
    padding: 8px 12px; 
    font-size: 16px; 
    border: 2px solid #ddd;
    border-radius: 6px;
} 

.tm-columns { 
    display: grid; 
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); 
    gap: 15px; 
} 

.tm-column { 
    border: 1px solid #ddd; 
    border-radius: 8px; 
    background: #f9f9f9; 
    overflow: hidden;
} 

.tm-team-header { 
    padding: 15px; 
    color: #fff; 
    display: flex; 
    justify-content: space-between; 
    align-items: center;
    background: linear-gradient(135deg, #333, #555);
} 

.tm-team-header h3 { 
    margin: 0; 
    font-size: 18px; 
} 

.tm-team-header img { 
    max-height: 40px;
    border-radius: 4px;
} 

.tm-financials { 
    background: #2c3e50; 
    color: #fff; 
    padding: 10px 15px; 
    font-size: 13px; 
    font-weight: 500;
} 

.tm-roster { 
    background: #fff;
    /* Rimosso max-height e overflow per mostrare tutto il roster */
} 

.tm-player { 
    display: flex; 
    align-items: center; 
    padding: 10px 15px; 
    border-bottom: 1px solid #eee; 
    cursor: pointer; 
    transition: all 0.2s ease;
} 

.tm-player:hover { 
    background-color: #e3f2fd; 
    transform: translateX(2px);
} 

.tm-player:last-child {
    border-bottom: none;
}

.tm-player img { 
    width: 40px;
    height: 40px;
    border-radius: 50%; 
    margin-right: 12px;
    object-fit: cover;
    border: 2px solid #eee;
} 

.tm-player-info { 
    flex-grow: 1; 
} 

.tm-player-name { 
    font-weight: 600; 
    font-size: 14px;
    margin-bottom: 2px;
} 

.tm-player-salary { 
    font-size: 12px; 
    color: #666; 
    font-weight: 500;
} 

.trade-valid { 
    background: linear-gradient(135deg, #27ae60, #2ecc71);
    color: white; 
} 

.trade-invalid { 
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white; 
} 

.trade-popup { 
    position: absolute; 
    background: white; 
    border: 1px solid #ddd; 
    border-radius: 8px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.15); 
    padding: 5px; 
    z-index: 1000;
    min-width: 150px;
} 

.trade-popup div { 
    padding: 10px 12px; 
    cursor: pointer;
    border-radius: 4px;
    font-size: 14px;
    transition: background 0.2s;
} 

.trade-popup div:hover { 
    background: #f8f9fa; 
} 

#trade-validator { 
    text-align: center; 
    margin: 30px 0; 
} 

#trade-validator button { 
    font-size: 18px; 
    padding: 12px 30px; 
    cursor: pointer;
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    transition: all 0.2s;
} 

#trade-validator button:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
}

#trade-validation-result { 
    margin-top: 20px; 
    font-size: 18px; 
    font-weight: bold; 
    padding: 15px; 
    border-radius: 8px;
    transition: all 0.3s;
}
</style>

<div id="trade-machine-app">
    <h1 style="text-align: center; margin-bottom: 30px; color: #2c3e50;">2KELITE TRADE MACHINE</h1>
    
    <div class="tm-selectors"></div>
    
    <div id="trade-validator">
        <button id="try-trade-btn">Verifica Trade</button>
        <div id="trade-validation-result"></div>
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
    let tradedPlayers = [];

    function formatCurrency(num) { 
        return '$' + new Intl.NumberFormat('en-US').format(num); 
    }

    function getTeamColors(teamName) {
        return teamColorMap[teamName] || { primary: '#34495e', secondary: '#2c3e50' };
    }

    function recalculateFinances(teamId) { 
        const teamData = selectedTeams[teamId]; 
        let newPlayersTotalSalary = 0; 
        
        // Calcola stipendi giocatori originali
        teamData.players.forEach(p => { 
            newPlayersTotalSalary += p.salary; 
        }); 
        
        // Aggiungi stipendi giocatori acquisiti
        if (tradedPlayers[teamId]) {
            tradedPlayers[teamId].forEach(p => {
                newPlayersTotalSalary += p.salary;
            });
        }
        
        // Calcolo corretto: solo stipendi giocatori + malus - bonus
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
        
        // Prima sezione: Giocatori acquisiti (se presenti)
        if (tradedPlayers[teamId] && tradedPlayers[teamId].length > 0) {
            const acquiringSection = `
                <div style="background: #d32f2f; color: white; padding: 10px 15px; font-weight: bold;">
                    Acquisisce ${tradedPlayers[teamId].length} Giocatore${tradedPlayers[teamId].length > 1 ? 'i' : ''}
                </div>
            `;
            rosterDiv.append(acquiringSection);
            
            tradedPlayers[teamId].forEach(player => {
                const playerHtml = `
                    <div class="tm-player" style="background-color: #ffebee;" data-player-id="${player.id}" data-team-id="${player.fromTeam}" data-current-team="${teamId}" data-is-traded="true">
                        <img src="${player.photo || 'https://via.placeholder.com/40x40/ddd/999?text=?'}" alt="${player.name}">
                        <div class="tm-player-info">
                            <div class="tm-player-name">${player.name}</div>
                            <div class="tm-player-salary">${formatCurrency(player.salary)}</div>
                        </div>
                        <div style="font-size: 12px; color: #666; font-style: italic;">
                            Da ${selectedTeams[player.fromTeam].name}
                        </div>
                    </div>
                `;
                rosterDiv.append(playerHtml);
            });
        }
        
        // Seconda sezione: Roster originale della squadra
        if (teamData.players.length > 0) {
            const rosterSection = `
                <div style="background: #34495e; color: white; padding: 10px 15px; font-weight: bold;">
                    ${teamData.name} Roster
                </div>
            `;
            rosterDiv.append(rosterSection);
            
            teamData.players.sort((a, b) => b.salary - a.salary).forEach(player => { 
                const playerHtml = `
                    <div class="tm-player" data-player-id="${player.id}" data-team-id="${teamId}">
                        <img src="${player.photo || 'https://via.placeholder.com/40x40/ddd/999?text=?'}" alt="${player.name}">
                        <div class="tm-player-info">
                            <div class="tm-player-name">${player.name}</div>
                            <div class="tm-player-salary">${formatCurrency(player.salary)}</div>
                        </div>
                    </div>
                `; 
                rosterDiv.append(playerHtml); 
            });
        }
        
        // Se non ci sono giocatori né acquisiti né originali
        if (teamData.players.length === 0 && (!tradedPlayers[teamId] || tradedPlayers[teamId].length === 0)) {
            rosterDiv.append('<div style="padding: 20px; text-align: center; color: #999;">Nessun giocatore</div>');
        }
    }

    function renderTradedPlayersSection() {
        // Non serve più questa funzione separata, tutto è integrato nei roster
        return;
    }

    function renderTeamColumn(teamId, selectorIndex) { 
        const teamData = allData[teamId]; 
        selectedTeams[teamId] = JSON.parse(JSON.stringify(teamData)); 
        
        const totalWithBonusMalus = teamData.current_salary_total - teamData.bonus + teamData.malus; 
        const remainingSpace = salaryCap - totalWithBonusMalus; 
        selectedTeams[teamId].newRemainingSpace = remainingSpace; 
        
        const teamColors = getTeamColors(teamData.name);
        
        const columnHtml = `
            <div class="tm-column" id="team-col-${teamId}">
                <div class="tm-team-header" style="background: linear-gradient(135deg, ${teamColors.primary}, ${teamColors.secondary})">
                    <h3>${teamData.name}</h3>
                    <img src="${teamData.logo || 'https://via.placeholder.com/40x40/fff/999?text=LOGO'}" alt="${teamData.name}">
                </div>
                <div class="tm-financials">
                    <div>Stipendi Totali: ${formatCurrency(teamData.current_salary_total)}</div>
                    <div>Spazio Rimanente: <strong>${formatCurrency(remainingSpace)}</strong></div>
                </div>
                <div class="tm-roster"></div>
            </div>
        `; 
        
        $('.tm-columns').append(columnHtml); 
        renderRoster(teamId); 
    }

    function createSelectors() { 
        for (let i = 1; i <= maxTeams; i++) { 
            const select = $(`<select id="team-selector-${i}" data-index="${i}">
                <option value="">-- Seleziona Squadra ${i} --</option>
            </select>`); 
            
            for (const teamId in allData) { 
                select.append(`<option value="${teamId}">${allData[teamId].name}</option>`); 
            } 
            selectorContainer.append(select); 
        } 
    }

    // Event handlers
    selectorContainer.on('change', 'select', function() { 
        const teamId = $(this).val(); 
        const index = $(this).data('index'); 
        const oldTeamId = $(this).data('old-val'); 
        
        if (oldTeamId) { 
            $(`#team-col-${oldTeamId}`).remove(); 
            delete selectedTeams[oldTeamId]; 
        } 
        
        if (teamId) { 
            renderTeamColumn(teamId, index); 
            $(this).data('old-val', teamId); 
        } 
    });

    $('.tm-columns').on('click', '.tm-player', function(e) { 
        const playerId = $(this).data('player-id'); 
        const isTraded = $(this).data('is-traded');
        
        // GESTIONE DEI GIOCATORI ACQUISITI - RITORNO ALLA SQUADRA ORIGINALE
        if (isTraded) {
            const currentTeamId = $(this).data('current-team');
            const originalTeamId = $(this).data('team-id');
            
            // Trova il giocatore nella lista degli acquisiti
            let playerToReturn = null;
            if (tradedPlayers[currentTeamId]) {
                const playerIndex = tradedPlayers[currentTeamId].findIndex(p => p.id == playerId);
                if (playerIndex !== -1) {
                    playerToReturn = tradedPlayers[currentTeamId][playerIndex];
                    tradedPlayers[currentTeamId].splice(playerIndex, 1);
                }
            }
            
            // Aggiungi il giocatore di nuovo alla squadra originale
            if (playerToReturn && selectedTeams[originalTeamId]) {
                selectedTeams[originalTeamId].players.push({
                    id: playerToReturn.id,
                    name: playerToReturn.name,
                    salary: playerToReturn.salary,
                    photo: playerToReturn.photo
                });
            }
            
            // Aggiorna i render
            renderRoster(currentTeamId);
            renderRoster(originalTeamId);
            Object.keys(selectedTeams).forEach(id => recalculateFinances(id));
            return;
        }
        
        // GESTIONE DEI GIOCATORI ORIGINALI - TRADE VERSO ALTRA SQUADRA
        const originTeamId = $(this).data('team-id'); 
        const involvedTeamIds = Object.keys(selectedTeams).filter(id => id != originTeamId); 
        
        if (involvedTeamIds.length === 0) {
            alert('Seleziona almeno due squadre per fare un trade!');
            return;
        }
        
        const tradePlayer = (targetTeamId) => { 
            let playerToMove; 
            selectedTeams[originTeamId].players = selectedTeams[originTeamId].players.filter(p => { 
                if (p.id == playerId) { 
                    playerToMove = p; 
                    return false; 
                } 
                return true; 
            }); 
            
            // Inizializza l'array per la squadra di destinazione se non esiste
            if (!tradedPlayers[targetTeamId]) {
                tradedPlayers[targetTeamId] = [];
            }
            
            // Aggiungi il giocatore alla sezione "Acquiring" della squadra di destinazione
            tradedPlayers[targetTeamId].push({
                id: playerToMove.id,
                name: playerToMove.name,
                salary: playerToMove.salary,
                photo: playerToMove.photo,
                fromTeam: originTeamId
            });
            
            renderRoster(originTeamId); 
            renderRoster(targetTeamId);
            Object.keys(selectedTeams).forEach(id => recalculateFinances(id)); 
        }; 
        
        if (involvedTeamIds.length === 1) { 
            tradePlayer(involvedTeamIds[0]); 
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
                tradePlayer(targetTeamId); 
                popup.remove(); 
            }); 
        } 
    });

    $('#try-trade-btn').on('click', function() { 
        let allTradesValid = true; 
        let resultText = "Trade Valida!"; 
        
        if (Object.keys(selectedTeams).length < 2) { 
            resultText = "Seleziona almeno due squadre."; 
            allTradesValid = false; 
        } else { 
            for(const teamId in selectedTeams) { 
                const teamData = selectedTeams[teamId];
                
                // Conta giocatori totali: roster + acquisiti
                let totalPlayers = teamData.players.length;
                if (tradedPlayers[teamId]) {
                    totalPlayers += tradedPlayers[teamId].length;
                }
                
                // Controlla limite roster (massimo 15 giocatori)
                if (totalPlayers > 25) {
                    allTradesValid = false;
                    resultText = `Trade Non Valida: ${teamData.name} avrebbe ${totalPlayers} giocatori (massimo 15 consentito)`;
                    break;
                }
                
                // Controlla salary cap
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
    });

    // Inizializza
    createSelectors();

    // Chiudi popup quando si clicca fuori
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.trade-popup, .tm-player').length) {
            $('.trade-popup').remove();
        }
    });
});
</script>

<?php
get_footer();
?>