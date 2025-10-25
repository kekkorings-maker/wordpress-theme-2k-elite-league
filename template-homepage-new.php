<?php
/**
 * This is a WordPress template for a new homepage that displays NBA-style game results from SportsPress.
 * Template Name: Homepage New
 */

get_header();

// Load custom CSS
wp_enqueue_style('homepage-new-style', get_template_directory_uri() . '/css/homepage-new.css');

// Query SportsPress events ordered by date.
$args = array(
    'post_type' => 'sp_event',
    'posts_per_page' => -1,
    'orderby' => 'event_date',
    'order' => 'ASC'
);
$events = new WP_Query($args);

if ($events->have_posts()) :
    echo '<nav><ul>';
    echo '<li><a href="/homepage">HOMEPAGE</a></li>';
    echo '<li><a href="/regolamento">REGOLAMENTO</a></li>';
    echo '<li><a href="/roster">ROSTER</a></li>';
    echo '<li><a href="/risultati">RISULTATI</a></li>';
    echo '<li><a href="/classifiche">CLASSIFICHE</a></li>';
    echo '<li><a href="/trade">TRADE</a></li>';
    echo '<li><a href="/record-storico">RECORD STORICO</a></li>';
    echo '<li><a href="/agenda-gm">AGENDA GM</a></li>';
    echo '<li><a href="/login">LOGIN</a></li>';
    echo '<li><a href="/inserisci-partita">INSERISCI PARTITA</a></li>';
    echo '<li><a href="/aste-free-agent">ASTE FREE AGENT</a></li>';
    echo '</ul></nav>';

    echo '<div class="game-results">';
    while ($events->have_posts()) : $events->the_post();
        $date = get_post_meta(get_the_ID(), 'event_date', true);
        $team1_logo = get_post_meta(get_the_ID(), 'team1_logo', true);
        $team2_logo = get_post_meta(get_the_ID(), 'team2_logo', true);
        $team1_name = get_post_meta(get_the_ID(), 'team1_name', true);
        $team2_name = get_post_meta(get_the_ID(), 'team2_name', true);
        $team1_score = get_post_meta(get_the_ID(), 'team1_score', true);
        $team2_score = get_post_meta(get_the_ID(), 'team2_score', true);
        $winner = ($team1_score > $team2_score) ? 'Team 1' : 'Team 2';
        
        // Display game card
        echo '<div class="game-card">';
        echo '<div class="date-marker">' . esc_html($date) . '</div>';
        echo '<div class="final-status">FINAL</div>';
        echo '<div class="team-logos"><img src="' . esc_url($team1_logo) . '" alt="' . esc_attr($team1_name) . ' logo"><img src="' . esc_url($team2_logo) . '" alt="' . esc_attr($team2_name) . ' logo"></div>';
        echo '<div class="team-names">' . esc_html($team1_name) . ' vs ' . esc_html($team2_name) . '</div>';
        echo '<div class="scores">' . esc_html($team1_score) . ' - ' . esc_html($team2_score) . '</div>';
        echo '<div class="winner-indicator">' . esc_html($winner) . ' wins!</div>';
        echo '<a href="' . esc_url(get_permalink()) . '">BOX SCORE</a>';
        echo '</div>';
    endwhile;
    echo '</div>';
    wp_reset_postdata();
else :
    echo '<p>No games found.</p>';
endif;

get_footer();
?>