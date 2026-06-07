<?php
/**
 * WorldTech - "Consultar preco" via WhatsApp para tudo que NAO for iPhone nem farmaco
 *
 * Criterio por categoria: produtos que NAO estao em Apple (67) nem Farmacia (58)
 * (ex: Caixas de som, Samsung, Xiaomi, Fones, Smart Watch, Perfumes) viram "consultar":
 *  - Preco escondido ("Preco sob consulta")
 *  - SEM adicionar ao carrinho (bloqueado server-side, nao da pra ver o preco)
 *  - Botao "Consultar preco" -> WhatsApp com a quantidade e a cor escolhidas
 *
 * Instalar no Code Snippets (sem a linha <?php), Run everywhere, ativar, limpar cache.
 */

// Detecta produto "consultar": NAO esta em Apple(67) nem Farmacia(58) nem filhas
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

// 2) Bloqueia adicionar ao carrinho (server-side, mesmo que burlem o JS)
add_filter( 'woocommerce_is_purchasable', function ( $purchasable, $product ) {
    if ( worldtech_eh_consultar( $product ) ) { return false; }
    return $purchasable;
}, 100, 2 );

// 3) GRADE DA LOJA: botao "Consultar preco" levando a pagina do produto
add_filter( 'woocommerce_loop_add_to_cart_link', function ( $html, $product ) {
    if ( worldtech_eh_consultar( $product ) ) {
        $html = '<a href="' . esc_url( $product->get_permalink() ) . '" class="button worldtech-consultar-btn">Consultar preço</a>';
    }
    return $html;
}, 100, 2 );

// 4) PAGINA DO PRODUTO: como nao e "purchasable", o WooCommerce esconde o botao de
//    carrinho. Renderizamos nosso proprio bloco (cor + quantidade + botao WhatsApp).
add_action( 'woocommerce_single_product_summary', function () {
    global $product;
    if ( ! worldtech_eh_consultar( $product ) ) { return; }
    $numero = '595975682071';
    $nome   = esc_js( $product->get_name() );

    echo '<form class="worldtech-consultar-form" onsubmit="return false;">';

    // Atributos (cor, etc.) como selects, se for variavel
    if ( $product->is_type( 'variable' ) ) {
        foreach ( $product->get_variation_attributes() as $attr_name => $options ) {
            $tax_label = wc_attribute_label( $attr_name );
            echo '<p class="wt-attr"><label>' . esc_html( $tax_label ) . ': </label><select class="wt-attr-select">';
            echo '<option value="">Escolha</option>';
            foreach ( $options as $opt ) {
                $label = $opt;
                if ( taxonomy_exists( $attr_name ) ) {
                    $term = get_term_by( 'slug', $opt, $attr_name );
                    if ( $term ) { $label = $term->name; }
                }
                echo '<option value="' . esc_attr( $label ) . '">' . esc_html( $label ) . '</option>';
            }
            echo '</select></p>';
        }
    }

    echo '<p class="wt-qty"><label>Quantidade: </label><input type="number" min="1" value="1" class="wt-qty-input" /></p>';
    echo '<a href="#" class="button alt worldtech-consultar-btn worldtech-consultar-go">Consultar preço pelo WhatsApp</a>';
    echo '</form>';
    ?>
    <script>
    (function () {
        var box = document.currentScript.previousElementSibling;
        if (!box || !box.classList.contains('worldtech-consultar-form')) {
            box = document.querySelector('.worldtech-consultar-form');
        }
        if (!box) return;
        var go = box.querySelector('.worldtech-consultar-go');
        go.addEventListener('click', function (e) {
            e.preventDefault();
            var qtyEl = box.querySelector('.wt-qty-input');
            var qty = qtyEl ? (qtyEl.value || '1') : '1';
            var attrs = [];
            box.querySelectorAll('.wt-attr-select').forEach(function (s) {
                if (s.value) { attrs.push(s.value); }
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
}, 31 );

// 5) Estilo
add_action( 'wp_head', function () {
    echo '<style>
        a.worldtech-consultar-btn {
            background:#25D366 !important; border-color:#25D366 !important; color:#fff !important;
            display:inline-block !important; text-align:center !important;
        }
        .worldtech-consultar-form .wt-attr-select,
        .worldtech-consultar-form .wt-qty-input { padding:6px; margin:4px 0; }
        .worldtech-consultar-form .worldtech-consultar-go { margin-top:10px; padding:14px 22px; }
    </style>';
} );
