<?php
/**
 * WorldTech - "Consultar preco" via WhatsApp (tudo que NAO for iPhone nem farmaco)
 *
 * Reusa os seletores ORIGINAIS do produto (Tamanho, Cor, Pulso, Quantidade), apenas
 * troca o botao "Adicionar ao carrinho" por "Consultar preco pelo WhatsApp".
 * Criterio: produto NAO esta em Apple (67) nem Farmacia (58).
 *
 * Instalar no Code Snippets (sem a linha <?php), Run everywhere, ativar, limpar cache.
 */

function worldtech_eh_consultar( $product ) {
    if ( ! $product ) { return false; }
    $id   = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
    $cats = wp_get_post_terms( $id, 'product_cat', array( 'fields' => 'ids' ) );
    if ( is_wp_error( $cats ) || empty( $cats ) ) { return false; }
    $normais = array( 67, 58 ); // Apple (iPhones) e Farmacia
    foreach ( $cats as $cid ) {
        if ( in_array( (int) $cid, $normais, true ) ) { return false; }
        foreach ( get_ancestors( $cid, 'product_cat' ) as $anc ) {
            if ( in_array( (int) $anc, $normais, true ) ) { return false; }
        }
    }
    return true;
}

// 1) Esconde o preco
add_filter( 'woocommerce_get_price_html', function ( $price, $product ) {
    if ( worldtech_eh_consultar( $product ) ) {
        return '<span class="preco-sob-consulta">Preço sob consulta</span>';
    }
    return $price;
}, 100, 2 );

// 2) Bloqueia adicionar ao carrinho (seguranca server-side)
add_filter( 'woocommerce_add_to_cart_validation', function ( $passed, $product_id, $qty = 1 ) {
    $p = wc_get_product( $product_id );
    if ( worldtech_eh_consultar( $p ) ) {
        wc_add_notice( 'Este produto é sob consulta. Fale conosco pelo WhatsApp.', 'error' );
        return false;
    }
    return $passed;
}, 10, 3 );

// 3) Marca a pagina desses produtos (pra esconder o botao original via CSS)
add_filter( 'body_class', function ( $classes ) {
    if ( function_exists( 'is_product' ) && is_product() ) {
        global $product;
        if ( $product && worldtech_eh_consultar( $product ) ) { $classes[] = 'wt-consultar'; }
    }
    return $classes;
} );

// 4) GRADE DA LOJA: botao "Consultar preco" -> pagina do produto
add_filter( 'woocommerce_loop_add_to_cart_link', function ( $html, $product ) {
    if ( worldtech_eh_consultar( $product ) ) {
        $html = '<a href="' . esc_url( $product->get_permalink() ) . '" class="button worldtech-consultar-btn">Consultar preço</a>';
    }
    return $html;
}, 100, 2 );

// 5) PAGINA: poe o botao "Consultar preco" no lugar do "Adicionar ao carrinho",
//    reaproveitando os seletores originais (Tamanho/Cor/Pulso/Quantidade)
add_action( 'woocommerce_after_add_to_cart_button', function () {
    global $product;
    if ( ! worldtech_eh_consultar( $product ) ) { return; }
    $numero = '595975682071';
    $nome   = esc_js( $product->get_name() );
    echo '<button type="button" class="button alt worldtech-consultar-btn worldtech-consultar-go">Consultar preço pelo WhatsApp</button>';
    ?>
    <script>
    (function () {
        var form = document.querySelector('form.cart');
        if (!form) return;
        var go = form.querySelector('.worldtech-consultar-go');
        if (!go) return;
        go.addEventListener('click', function (e) {
            e.preventDefault();
            var qtyEl = form.querySelector('input.qty');
            var qty = qtyEl ? (qtyEl.value || '1') : '1';
            var attrs = [];
            form.querySelectorAll('select[name^="attribute"]').forEach(function (s) {
                if (s.value) {
                    var t = s.options[s.selectedIndex] ? s.options[s.selectedIndex].text : s.value;
                    attrs.push(t);
                }
            });
            var nome = "<?php echo $nome; ?>";
            var msg = "Olá! Quero consultar o preço:\n\n" + qty + "x " + nome;
            if (attrs.length) { msg += " (" + attrs.join(', ') + ")"; }
            msg += "\n\nQuanto está custando?";
            window.open("https://wa.me/<?php echo $numero; ?>?text=" + encodeURIComponent(msg), '_blank');
        });
    })();
    </script>
    <?php
} );

// 6) CSS: esconde o botao original de carrinho e estiliza o de consulta
add_action( 'wp_head', function () {
    echo '<style>
        .wt-consultar .single_add_to_cart_button,
        .wt-consultar .cgkit-sticky-atc,
        .wt-consultar .shoptimizer-sticky-add-to-cart { display:none !important; }
        a.worldtech-consultar-btn, button.worldtech-consultar-btn {
            background:#25D366 !important; border-color:#25D366 !important; color:#fff !important;
            display:inline-block !important; text-align:center !important; cursor:pointer;
        }
        button.worldtech-consultar-go { padding:14px 22px; margin-top:6px; }
    </style>';
} );
