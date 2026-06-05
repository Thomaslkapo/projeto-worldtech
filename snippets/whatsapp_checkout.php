<?php
/**
 * WorldTech - Finalizacao de compra via WhatsApp (cobre carrinho + side cart Shoptimizer)
 *
 * - Troca o botao "Finalizar compra" pelo botao do WhatsApp na PAGINA do carrinho
 * - Troca tambem no MINI-CART / side cart (o "Your Cart" lateral do Shoptimizer)
 * - Rede de seguranca: bloqueia /checkout e manda pro carrinho (loja nao vende online)
 *
 * Instalar no Code Snippets (sem a linha <?php), "Run everywhere", Salvar/Ativar, limpar cache.
 */

// Gera o link do WhatsApp com o conteudo atual do carrinho
function worldtech_whatsapp_link() {
    if ( ! function_exists( 'WC' ) || ! WC()->cart ) { return '#'; }

    $numero = '595975682071'; // WhatsApp da loja (so digitos, com DDI)
    $simbolo = html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' );
    $msg    = "Olá! Quero finalizar meu pedido:\n\n";

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

        $subtotal = $simbolo . number_format( (float) $item['line_total'], 2 );
        $msg     .= "• {$qtd}x {$nome}{$extra}: {$subtotal}\n";
    }

    $total = $simbolo . number_format( (float) WC()->cart->get_total( 'edit' ), 2 );
    $msg  .= "\n*Total: {$total}*";

    return 'https://wa.me/' . $numero . '?text=' . rawurlencode( $msg );
}

// Botao na PAGINA do carrinho
add_action( 'woocommerce_proceed_to_checkout', function () {
    echo '<a href="' . esc_attr( worldtech_whatsapp_link() ) . '" target="_blank" rel="noopener nofollow" '
       . 'class="checkout-button button alt wc-forward worldtech-wa-btn">Finalizar pedido pelo WhatsApp</a>';
}, 5 );

// Botao no MINI-CART / side cart
add_action( 'woocommerce_widget_shopping_cart_buttons', function () {
    echo '<a href="' . esc_attr( worldtech_whatsapp_link() ) . '" target="_blank" rel="noopener nofollow" '
       . 'class="button checkout wc-forward worldtech-wa-btn">Finalizar pelo WhatsApp</a>';
}, 30 );

// Remove os botoes padrao de checkout (pagina + mini-cart)
add_action( 'wp', function () {
    remove_action( 'woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20 );
    remove_action( 'woocommerce_widget_shopping_cart_buttons', 'woocommerce_widget_shopping_cart_proceed_to_checkout', 20 );
} );

// Esconde via CSS qualquer botao de checkout que NAO seja o nosso (carrinho + side cart)
add_action( 'wp_head', function () {
    echo '<style>
        .wc-proceed-to-checkout a.checkout-button:not(.worldtech-wa-btn),
        .woocommerce-mini-cart__buttons a.checkout:not(.worldtech-wa-btn),
        .widget_shopping_cart a.checkout:not(.worldtech-wa-btn),
        a.checkout-button.alt:not(.worldtech-wa-btn) { display:none !important; }
        a.worldtech-wa-btn {
            display:flex !important; align-items:center !important; justify-content:center !important;
            gap:8px; text-align:center !important; line-height:1.2 !important;
            background:#25D366 !important; border-color:#25D366 !important; color:#ffffff !important;
        }
        a.worldtech-wa-btn::before { margin:0 !important; align-self:center !important; }
    </style>';
} );

// Rede de seguranca: se cair no /checkout por qualquer caminho, volta pro carrinho
add_action( 'template_redirect', function () {
    if ( function_exists( 'is_checkout' ) && is_checkout()
        && ! is_wc_endpoint_url( 'order-received' )
        && ! is_wc_endpoint_url( 'order-pay' ) ) {
        wp_safe_redirect( wc_get_cart_url() );
        exit;
    }
} );
