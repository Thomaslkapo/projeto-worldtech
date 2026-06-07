<?php
/**
 * WorldTech - "Consultar preco" via WhatsApp (tudo que NAO for iPhone nem farmaco)
 *
 * Reusa os seletores ORIGINAIS do produto, troca o botao de carrinho por
 * "Consultar preco pelo WhatsApp". Criterio: NAO esta em Apple(67) nem Farmacia(58).
 *
 * IMPORTANTE: codigo 100% PHP (sem ?> no meio) para o plugin Code Snippets nao quebrar.
 * Instalar no Code Snippets (sem a linha <?php do topo), Run everywhere, ativar, limpar cache.
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

// 5) PAGINA: botao "Consultar preco" no lugar do carrinho, reusando os seletores originais
add_action( 'woocommerce_after_add_to_cart_button', function () {
    global $product;
    if ( ! worldtech_eh_consultar( $product ) ) { return; }
    $numero  = '595975682071';
    $nome_js = wp_json_encode( $product->get_name() );

    $out  = '<button type="button" class="button alt worldtech-consultar-btn worldtech-consultar-go">Consultar preço pelo WhatsApp</button>';
    $out .= '<script>(function(){';
    $out .= "var form=document.querySelector('form.cart'); if(!form)return;";
    $out .= "var go=form.querySelector('.worldtech-consultar-go'); if(!go)return;";
    $out .= "go.addEventListener('click',function(e){e.preventDefault();";
    $out .= "var q=form.querySelector('input.qty'); var qty=q?(q.value||'1'):'1';";
    $out .= "var attrs=[]; form.querySelectorAll(\"select[name^='attribute']\").forEach(function(s){if(s.value){var t=s.options[s.selectedIndex]?s.options[s.selectedIndex].text:s.value;attrs.push(t);}});";
    $out .= "var nome={$nome_js};";
    $out .= "var nl=String.fromCharCode(10);";
    $out .= "var msg='Olá! Quero consultar o preço:'+nl+nl+qty+'x '+nome;";
    $out .= "if(attrs.length){msg+=' ('+attrs.join(', ')+')';}";
    $out .= "msg+=nl+nl+'Quanto está custando?';";
    $out .= "window.open('https://wa.me/{$numero}?text='+encodeURIComponent(msg),'_blank');";
    $out .= '});})();</script>';
    echo $out;
} );

// 6) CSS: esconde o botao original de carrinho e estiliza o de consulta
add_action( 'wp_head', function () {
    echo '<style>'
       . '.wt-consultar .single_add_to_cart_button,'
       . '.wt-consultar .cgkit-sticky-atc,'
       . '.wt-consultar .shoptimizer-sticky-add-to-cart { display:none !important; }'
       . 'a.worldtech-consultar-btn, button.worldtech-consultar-btn {'
       . 'background:#25D366 !important; border-color:#25D366 !important; color:#fff !important;'
       . 'display:inline-block !important; text-align:center !important; cursor:pointer; }'
       . 'button.worldtech-consultar-go { padding:14px 22px; margin-top:6px; }'
       . '</style>';
} );
