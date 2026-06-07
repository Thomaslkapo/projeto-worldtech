<?php
/**
 * WorldTech - Botao "Consultar preco" via WhatsApp para produtos SEM preco
 *
 * Produtos sem preco (que mostram "Preco sob consulta") ganham um botao que leva
 * ao WhatsApp da loja perguntando o preco daquele produto.
 * Produtos COM preco (iPhones, farmacos) mantem o botao normal.
 *
 * Instalar no Code Snippets (sem a linha <?php), Run everywhere, ativar, limpar cache.
 */

// Gera o link do WhatsApp perguntando o preco do produto
function worldtech_consultar_link( $product ) {
    $numero = '595975682071';
    $nome   = $product->get_name();
    $msg    = "Olá! Gostaria de saber o preço deste produto: " . $nome . ". Quanto está custando?";
    return 'https://wa.me/' . $numero . '?text=' . rawurlencode( $msg );
}

// Considera "sob consulta" quando nao tem preco definido
function worldtech_sem_preco( $product ) {
    if ( ! $product ) { return false; }
    $preco = $product->get_price();
    return ( $preco === '' || $preco === null );
}

// 1) GRADE DA LOJA: troca o botao de carrinho/opcoes por "Consultar preco"
add_filter( 'woocommerce_loop_add_to_cart_link', function ( $html, $product ) {
    if ( worldtech_sem_preco( $product ) ) {
        $link = worldtech_consultar_link( $product );
        $html = '<a href="' . esc_attr( $link ) . '" target="_blank" rel="noopener nofollow" '
              . 'class="button worldtech-consultar-btn">Consultar preço</a>';
    }
    return $html;
}, 10, 2 );

// 2) PAGINA DO PRODUTO: remove o "adicionar ao carrinho" e mostra "Consultar preco"
add_action( 'woocommerce_single_product_summary', function () {
    global $product;
    if ( worldtech_sem_preco( $product ) ) {
        remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
        $link = worldtech_consultar_link( $product );
        echo '<a href="' . esc_attr( $link ) . '" target="_blank" rel="noopener nofollow" '
           . 'class="button alt worldtech-consultar-btn">Consultar preço pelo WhatsApp</a>';
    }
}, 25 );

// 3) Estilo do botao (verde WhatsApp)
add_action( 'wp_head', function () {
    echo '<style>
        a.worldtech-consultar-btn {
            background:#25D366 !important; border-color:#25D366 !important; color:#fff !important;
            display:inline-flex !important; align-items:center !important; justify-content:center !important;
            text-align:center !important;
        }
    </style>';
} );
