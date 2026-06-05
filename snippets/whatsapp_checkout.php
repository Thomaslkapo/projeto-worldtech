<?php
/**
 * WorldTech - Finalizacao de compra via WhatsApp (versao robusta p/ tema Shoptimizer)
 *
 * Substitui o botao "Finalizar compra" do carrinho por um botao que abre o
 * WhatsApp da loja com todos os itens do carrinho na mensagem.
 *
 * COMO INSTALAR (Code Snippets): cole o codigo abaixo (sem a linha <?php),
 * marque "Run snippet everywhere", Salve e Ative. Depois LIMPE O CACHE.
 */

// 1) Adiciona o botao do WhatsApp (prioridade 5 = antes do botao do tema)
add_action( 'woocommerce_proceed_to_checkout', 'worldtech_whatsapp_checkout_button', 5 );
function worldtech_whatsapp_checkout_button() {

    $numero = '595975682071'; // WhatsApp da loja (so digitos, com DDI)

    $msg = "Olá! Quero finalizar meu pedido:\n\n";

    foreach ( WC()->cart->get_cart() as $item ) {
        $nome = get_the_title( $item['product_id'] );
        $qtd  = $item['quantity'];

        $extra = '';
        if ( ! empty( $item['variation'] ) ) {
            $vals = array();
            foreach ( $item['variation'] as $taxonomy => $slug ) {
                if ( $slug === '' ) { continue; }
                $tax = str_replace( 'attribute_', '', $taxonomy );
                if ( taxonomy_exists( $tax ) ) {
                    $term   = get_term_by( 'slug', $slug, $tax );
                    $vals[] = $term ? $term->name : $slug;
                } else {
                    $vals[] = $slug;
                }
            }
            if ( $vals ) { $extra = ' (' . implode( ', ', $vals ) . ')'; }
        }

        $subtotal = wp_strip_all_tags( WC()->cart->get_product_subtotal( $item['data'], $qtd ) );
        $msg .= "• {$qtd}x {$nome}{$extra}: {$subtotal}\n";
    }

    $total = wp_strip_all_tags( WC()->cart->get_cart_total() );
    $msg  .= "\n*Total: {$total}*";

    $link = 'https://wa.me/' . $numero . '?text=' . rawurlencode( $msg );

    echo '<a href="' . esc_url( $link ) . '" target="_blank" rel="noopener nofollow" '
       . 'class="checkout-button button alt wc-forward worldtech-wa-btn">'
       . 'Finalizar pedido pelo WhatsApp</a>';
}

// 2) Tenta remover o botao padrao pelo hook (caso o tema use o padrao)
add_action( 'wp', function () {
    remove_action( 'woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20 );
} );

// 3) Esconde via CSS qualquer botao de checkout que NAO seja o nosso (à prova de tema)
add_action( 'wp_head', function () {
    if ( function_exists( 'is_cart' ) && is_cart() ) {
        echo '<style>
            .wc-proceed-to-checkout a.checkout-button:not(.worldtech-wa-btn),
            .wc-proceed-to-checkout a.cgkit-proceed-to-checkout:not(.worldtech-wa-btn),
            a.checkout-button.alt:not(.worldtech-wa-btn) { display:none !important; }
            a.worldtech-wa-btn {
                display:block !important; text-align:center !important;
                background:#25D366 !important; border-color:#25D366 !important;
                color:#ffffff !important;
            }
        </style>';
    }
} );
