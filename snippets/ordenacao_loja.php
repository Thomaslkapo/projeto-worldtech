<?php
/**
 * Remove opcoes de ordenacao sem utilidade nesta loja.
 * - "Popularidade" e "Media de classificacao" dependem de vendas online / avaliacoes,
 *   que a WorldTech nao tem (venda via WhatsApp, sem reviews) -> ordenam por nada.
 * Mantem: Ordenacao padrao, Mais recente, Preco (asc/desc).
 *
 * Cole o miolo (de add_filter ate }, 20 );) no Code Snippets (Run everywhere).
 */
add_filter( 'woocommerce_catalog_orderby', function ( $options ) {
    unset( $options['popularity'] ); // Ordenar por popularidade
    unset( $options['rating'] );     // Ordenar por media de classificacao
    // unset( $options['date'] );    // descomente p/ tirar tambem "Mais recente"
    return $options;
}, 20 );
