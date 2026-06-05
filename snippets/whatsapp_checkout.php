<?php
/**
 * WorldTech - Finalizacao de compra via WhatsApp
 *
 * Substitui o botao "Finalizar compra" da pagina do carrinho por um botao
 * que abre o WhatsApp da loja com todos os itens do carrinho na mensagem.
 *
 * COMO INSTALAR (plugin Code Snippets):
 *   1. wp-admin -> Snippets -> Add New
 *   2. Titulo: "WhatsApp Checkout"
 *   3. Cole o codigo ABAIXO (sem a linha <?php do topo)
 *   4. Marque "Run snippet everywhere"
 *   5. Save Changes and Activate
 */

// 1) Remove o botao padrao "Finalizar compra"
add_action( 'wp', function () {
    remove_action( 'woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20 );
} );

// 2) Adiciona o botao do WhatsApp com o carrinho na mensagem
add_action( 'woocommerce_proceed_to_checkout', 'worldtech_whatsapp_checkout_button', 20 );
function worldtech_whatsapp_checkout_button() {

    $numero = '595975682071'; // WhatsApp da loja (so digitos, com DDI)

    $msg = "Olá! Quero finalizar meu pedido:\n\n";

    foreach ( WC()->cart->get_cart() as $item ) {

        $nome = get_the_title( $item['product_id'] );
        $qtd  = $item['quantity'];

        // atributos da variacao (Cor, Armazenamento) com nomes legiveis
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
            if ( $vals ) {
                $extra = ' (' . implode( ', ', $vals ) . ')';
            }
        }

        // subtotal do item, sem HTML
        $subtotal = wp_strip_all_tags( WC()->cart->get_product_subtotal( $item['data'], $qtd ) );

        $msg .= "• {$qtd}x {$nome}{$extra}: {$subtotal}\n";
    }

    $total = wp_strip_all_tags( WC()->cart->get_cart_total() );
    $msg  .= "\n*Total: {$total}*";

    $link = 'https://wa.me/' . $numero . '?text=' . rawurlencode( $msg );

    echo '<a href="' . esc_url( $link ) . '" target="_blank" rel="noopener nofollow" '
       . 'class="checkout-button button alt wc-forward" '
       . 'style="display:block;text-align:center;background:#25D366;border-color:#25D366;color:#ffffff;">'
       . 'Finalizar pedido pelo WhatsApp</a>';
}
