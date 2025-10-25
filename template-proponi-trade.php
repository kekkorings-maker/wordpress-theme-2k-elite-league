<?php
/**
 * Template Name: Pagina Proposta Trade
 *
 * Questo template contiene la logica corretta per mostrare e processare il form di ACF.
 */

// Eseguiamo la funzione fondamentale PRIMA di qualsiasi altra cosa.
if ( function_exists('acf_form_head') ) {
    acf_form_head();
}

// Ora carichiamo l'header del sito.
get_header(); 
?>

<div id="primary" class="content-area content-area-no-sidebar">
    <main id="main" class="site-main" role="main">
        <div class="entry-content">

            <h1 class="entry-title"><?php the_title(); ?></h1>

            <?php if ( is_user_logged_in() ) : ?>

                <?php 
                // Impostazioni del form
                $id_gruppo_campi = 2090;
                $id_categoria_trade = get_cat_ID('Trade');

                if ( !$id_categoria_trade ) {
                    echo '<p style="color:red;"><strong>ERRORE CRITICO:</strong> La categoria "Trade" non esiste. Per favore, creala da Articoli > Categorie.</p>';
                } else {
                    acf_form(array(
                        'post_id'       => 'new_post',
                        'new_post'      => array(
                            'post_type'     => 'post',
                            'post_status'   => 'publish', 
                            'post_category' => array( $id_categoria_trade ),
                        ),
                        'post_title'    => true,
                        'field_groups'  => array( $id_gruppo_campi ),
                        'submit_value'  => 'Invia Proposta',
                        'updated_message' => 'Proposta inviata con successo! Ora puoi vederla nell\'elenco in bacheca.',
                    ));
                }
                ?>

            <?php else: ?>
                <p>Devi effettuare il login per proporre una trade.</p>
            <?php endif; ?>

        </div></main></div><?php get_footer(); ?>