<?php
/**
 * WorldTech - "Consultar preco" via WhatsApp para produtos SEM preco
 *
 * Produtos sem preco (que mostram "Preco sob consulta"):
 *  - Grade da loja: botao "Consultar preco"
 *  - Pagina do produto: o botao "Adicionar ao carrinho" vira "Consultar preco" e,
 *    ao clicar, abre o WhatsApp com a QUANTIDADE e a COR/variacao escolhidas.
 * Produtos COM preco (iPhones, farmacos) seguem normais.
 *
 * Instalar no Code Snippets (sem a linha <?php), Run everywhere, ativar, limpar cache.
 */

function worldtech_sem_preco( $product ) {
    if ( ! $product ) { return false; }
    $p = $product->get_price();
    return ( $p === '' || $p === null );
}

function worldtech_consultar_link_simples( $product ) {
    $numero = '595975682071';
    $msg    = "Olá! Gostaria de saber o preço deste produto: " . $product->get_name() . ". Quanto está custando?";
    return 'https://wa.me/' . $numero . '?text=' . rawurlencode( $msg );
}

// 1) GRADE DA LOJA
add_filter( 'woocommerce_loop_add_to_cart_link', function ( $html, $product ) {
    if ( worldtech_sem_preco( $product ) ) {
        if ( $product->is_type( 'variable' ) ) {
            // leva para a pagina do produto (pra escolher cor e quantidade)
            $html = '<a href="' . esc_url( $product->get_permalink() ) . '" class="button worldtech-consultar-btn">Consultar preço</a>';
        } else {
            $html = '<a href="' . esc_attr( worldtech_consultar_link_simples( $product ) ) . '" target="_blank" rel="noopener nofollow" class="button worldtech-consultar-btn">Consultar preço</a>';
        }
    }
    return $html;
}, 10, 2 );

// 2) PAGINA DO PRODUTO: muda o texto do botao
add_filter( 'woocommerce_product_single_add_to_cart_text', function ( $text ) {
    global $product;
    if ( worldtech_sem_preco( $product ) ) { return 'Consultar preço'; }
    return $text;
} );

// 3) PAGINA DO PRODUTO: intercepta o clique e abre o WhatsApp com qtd + cor
add_action( 'woocommerce_after_add_to_cart_button', function () {
    global $product;
    if ( ! worldtech_sem_preco( $product ) ) { return; }
    $numero = '595975682071';
    $nome   = esc_js( $product->get_name() );
    ?>
    <script>
    (function () {
        var form = document.querySelector('form.cart');
        if (!form) return;
        var btn = form.querySelector('.single_add_to_cart_button');
        if (!btn) return;
        btn.classList.remove('disabled', 'wc-variation-selection-needed');
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            var qtyEl = form.querySelector('input.qty');
            var qty = qtyEl ? (qtyEl.value || '1') : '1';
            var attrs = [];
            form.querySelectorAll('select[name^="attribute"]').forEach(function (s) {
                if (s.value) {
                    var txt = s.options[s.selectedIndex] ? s.options[s.selectedIndex].text : s.value;
                    attrs.push(txt);
                }
            });
            var nome = "<?php echo $nome; ?>";
            var msg = "Olá! Quero consultar o preço:\n\n" + qty + "x " + nome;
            if (attrs.length) { msg += " (" + attrs.join(', ') + ")"; }
            msg += "\n\nQuanto está custando?";
            window.open("https://wa.me/<?php echo $numero; ?>?text=" + encodeURIComponent(msg), '_blank');
        }, true);
    })();
    </script>
    <?php
} );

// 4) Estilo do botao (verde WhatsApp)
add_action( 'wp_head', function () {
    echo '<style>
        a.worldtech-consultar-btn, .worldtech-sem-preco .single_add_to_cart_button {
            background:#25D366 !important; border-color:#25D366 !important; color:#fff !important;
        }
    </style>';
} );
