<?php
/* Template Name: Storico Trade */
get_header(); ?>

<div class="storico-trade-page">
  <h1>📜 Storico Trade Confermate</h1>

  <!-- Filtro squadra -->
  <form method="get" class="trade-filter-form">
    <label for="squadra">Filtra per squadra:</label>
    <select name="squadra" id="squadra" onchange="this.form.submit()">
      <option value="">Tutte le squadre</option>
      <?php
      $teams = get_posts(['post_type' => 'sp_team', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC']);
      foreach ($teams as $team) {
        $selected = (isset($_GET['squadra']) && $_GET['squadra'] == $team->ID) ? 'selected' : '';
        echo '<option value="' . esc_attr($team->ID) . '" ' . $selected . '>' . esc_html($team->post_title) . '</option>';
      }
      ?>
    </select>
  </form>

  <!-- Lista trade -->
  <style>
    @media (max-width: 768px) {
      .trade-list {
        flex-direction: column;
      }
      .trade-box {
        flex: 1 1 100% !important;
      }
    }
  </style>

  <div class="trade-list" style="display: flex; flex-wrap: wrap; gap: 20px;">
    <?php
    $args = [
      'post_type' => 'trade_proposal',
      'posts_per_page' => -1,
      'meta_query' => [
        [
          'key' => 'stato_trade',
          'value' => 'CONFERMATA',
          'compare' => '='
        ]
      ],
      'orderby' => 'date',
      'order' => 'DESC'
    ];

    $query = new WP_Query($args);
    if ($query->have_posts()) :
      while ($query->have_posts()) : $query->the_post();
        $teams = get_field('involved_teams');
        $match_squadra = true;

        if (isset($_GET['squadra']) && $_GET['squadra']) {
          $match_squadra = false;
          foreach ($teams as $team) {
            if ((int)$team['team_name'] == (int)$_GET['squadra']) {
              $match_squadra = true;
              break;
            }
          }
        }

        if ($match_squadra):
          echo '<div class="trade-box" style="flex: 1 1 calc(50% - 20px); border: 1px solid #ddd; background: #f9f9f9; border-radius: 8px; padding: 20px; box-sizing: border-box;">';

          // Titolo con squadre coinvolte
          $team_nomi = [];
          foreach ($teams as $t) {
            $team_nomi[] = get_the_title($t['team_name']);
          }
          echo '<h2 style="margin-bottom: 4px;">🔁 Trade: ' . esc_html(implode(' – ', $team_nomi)) . '</h2>';
          echo '<p style="font-size: 14px; color: #666; margin-top: 0; margin-bottom: 15px;">📅 ' . get_the_date('d M Y') . '</p>';

          echo '<div class="trade-teams">';
          foreach ($teams as $team) {
            echo '<div class="team-block" style="margin-bottom: 20px;">';
            $team_id = $team['team_name'];
            $team_name = get_the_title($team_id);
            $team_logo = get_the_post_thumbnail_url($team_id, 'thumbnail');

            echo '<div style="display: flex; align-items: center; gap: 12px; flex-wrap: nowrap;">';
            if ($team_logo) {
              echo '<img src="' . esc_url($team_logo) . '" alt="' . esc_attr($team_name) . '" style="height: 48px; width: auto; border-radius: 4px;">';
            }
            echo '<h3 style="text-transform: uppercase; font-weight: bold; font-size: 18px; color: #00456d; margin: 0;">' . esc_html($team_name) . ' ha ceduto:</h3>';
            echo '</div>';

            // Giocatori
            if (!empty($team['players_out'])) {
              echo '<p><strong>Giocatori:</strong></p><ul style="margin-top: 4px; margin-bottom: 12px; padding-left: 18px;">';
              foreach ($team['players_out'] as $p) {
                echo '<li>🏀 ' . esc_html(get_the_title($p['player'])) . '</li>';
              }
              echo '</ul>';
            }

            // Pick
            if (!empty($team['picks_out'])) {
              echo '<p><strong>Pick:</strong></p><ul style="margin-top: 4px; padding-left: 18px;">';
              foreach ($team['picks_out'] as $pick) {
                echo '<li>🎯 ' . esc_html($pick['pick']) . '</li>';
              }
              echo '</ul>';
            }

            echo '</div>'; // team-block
          }
          echo '</div>'; // trade-teams
          echo '</div>'; // trade-box
        endif;
      endwhile;
      wp_reset_postdata();
    else:
      echo '<p>Nessuna trade confermata trovata.</p>';
    endif;
    ?>
  </div>
</div>

<?php get_footer(); ?>