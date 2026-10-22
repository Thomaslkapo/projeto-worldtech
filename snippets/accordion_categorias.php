<?php
/**
 * Accordion na lista de categorias (widget "Categorias de produtos").
 *
 * - Recolhe TODAS as subcategorias por padrao (so as raiz aparecem).
 * - Clicar no NOME de uma categoria-pai expande/recolhe SO aquela (nao navega).
 * - Dentro de cada pai aberto aparece um "Ver todos" que leva a pagina da categoria.
 * - Categorias-folha (sem filhos) navegam normal.
 * - Se voce estiver numa pagina de categoria, o ramo atual abre sozinho.
 *
 * Como usar: cole o miolo (de add_action ate }, 99 );) no plugin "Code Snippets"
 * (Run everywhere), ou o arquivo todo no functions.php do tema filho.
 */
add_action( 'wp_footer', function () {
    if ( is_admin() ) {
        return;
    }

    $css = <<<'CSS'
.widget_product_categories ul.children{display:none;}
.widget_product_categories li.cat-parent.wt-open > ul.children{display:flex;flex-direction:column;}
.widget_product_categories li.cat-parent > a{cursor:pointer;}
/* ordem dos iPhones: mais NOVO primeiro (17,16,15,14,13). "Ver todos" sempre no topo */
.widget_product_categories ul.children > li{order:50;}
.widget_product_categories ul.children > li.wt-vertodos{order:0;}
.widget_product_categories ul.children > li.cat-item-418{order:1;} /* iPhone 17 (Lacrados) */
.widget_product_categories ul.children > li.cat-item-498{order:2;} /* iPhone 16 (Lacrados) */
.widget_product_categories ul.children > li.cat-item-497{order:3;} /* iPhone 15 (Lacrados) */
.widget_product_categories ul.children > li.cat-item-434{order:2;} /* SWAP 16 */
.widget_product_categories ul.children > li.cat-item-430{order:3;} /* SWAP 15 */
.widget_product_categories ul.children > li.cat-item-426{order:4;} /* SWAP 14 */
.widget_product_categories ul.children > li.cat-item-423{order:5;} /* SWAP 13 */
.widget_product_categories .wt-acc-toggle{
    display:inline-flex;align-items:center;justify-content:center;
    width:1.4em;height:1.4em;margin-right:.25em;cursor:pointer;
    vertical-align:middle;user-select:none;flex:0 0 auto;
}
.widget_product_categories .wt-acc-toggle:before{
    content:'\25B8';color:#1E3A5F;font-size:.9em;line-height:1;
    transition:transform .15s ease;
}
.widget_product_categories li.cat-parent.wt-open > .wt-acc-toggle:before{
    transform:rotate(90deg);
}
.widget_product_categories li.wt-vertodos > a{font-style:italic;opacity:.85;}
/* Ordem das categorias raiz: Celulares (15) 1o, Farmacia (58) 2o, resto depois */
.widget_product_categories ul.product-categories{display:flex;flex-direction:column;}
.widget_product_categories ul.product-categories > li{order:5;}
.widget_product_categories ul.product-categories > li.cat-item-15{order:1;}
.widget_product_categories ul.product-categories > li.cat-item-58{order:2;}
/* Categoria selecionada/hover em navy (tema usava verde #409900) */
.widget_product_categories li.current-cat > a{color:#1E3A5F !important;font-weight:600;}
.widget_product_categories a:hover,
.widget_product_categories a:focus{color:#1E3A5F !important;}
/* Esconde os filtros de atributo (Armazenamento / Cor) da barra lateral */
.widget_layered_nav,
.woocommerce-widget-layered-nav,
.wc-block-attribute-filter,
.widget_layered_nav_filters{display:none !important;}
/* Esconde o widget "Melhores produtos" (best sellers / top rated) */
.widget_products,
.widget_top_rated_products,
.wc-block-product-best-sellers,
.wc-block-product-top-rated{display:none !important;}
/* Filtro de preco em navy (era verde): barra preenchida, bolinhas e texto */
.widget_price_filter .ui-slider .ui-slider-range{background-color:#1E3A5F !important;}
.widget_price_filter .ui-slider .ui-slider-handle{background-color:#1E3A5F !important;border-color:#1E3A5F !important;}
.widget_price_filter .price_slider_amount .price_label,
.widget_price_filter .price_label{color:#1E3A5F !important;}
/* Caixas Min/Max acima da barra. Os inputs NATIVOS ficam escondidos (guardam o numero
   cru pro slider/filtro); mostramos caixas proprias .wt-box (formatadas, sem name). */
.widget_price_filter .price_slider_wrapper{display:flex;flex-direction:column;}
.widget_price_filter .price_slider_amount{order:1;display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin-bottom:14px;}
.widget_price_filter .price_slider{order:2;}
.widget_price_filter .price_slider_amount #min_price,
.widget_price_filter .price_slider_amount #max_price{display:none !important;}
.widget_price_filter .price_slider_amount .wt-box{
    flex:1 1 42%;width:42%;min-width:0;
    padding:8px 9px;border:1px solid #cfd4da;border-radius:6px;font-size:16px;color:#1a1a1a;background:#fff;
}
.widget_price_filter .price_slider_amount .wt-box::placeholder{font-size:12px;opacity:.6;}
.widget_price_filter .price_slider_amount .price_label{display:none !important;}
.widget_price_filter .price_slider_amount .button{order:3;flex:1 1 100%;}
/* valores ao vivo (min na esquerda, max na direita) abaixo da barra */
.widget_price_filter .price_slider_wrapper .wt-preco-range{
    order:3;display:flex;justify-content:space-between;align-items:center;
    margin-top:7px;font-size:11px;font-weight:700;color:#1E3A5F;
}
CSS;

    $js = <<<'JS'
(function(){
  function init(){
    var roots = document.querySelectorAll('.widget_product_categories ul.product-categories');
    if(!roots.length){return;}
    roots.forEach(function(root){
      root.querySelectorAll('li.cat-parent').forEach(function(li){
        if(li.classList.contains('wt-acc')){return;}
        li.classList.add('wt-acc');
        var link = li.querySelector(':scope > a');
        var sub  = li.querySelector(':scope > ul.children');
        var t=document.createElement('span');
        t.className='wt-acc-toggle';
        t.setAttribute('role','button');
        t.setAttribute('aria-label','Expandir/recolher');
        li.insertBefore(t, li.firstChild);
        if(sub && link){
          var liAll=document.createElement('li');
          liAll.className='cat-item wt-vertodos';
          var a=document.createElement('a');
          a.href=link.getAttribute('href');
          a.textContent='Ver todos';
          liAll.appendChild(a);
          sub.insertBefore(liAll, sub.firstChild);
        }
        function toggle(e){e.preventDefault();e.stopPropagation();li.classList.toggle('wt-open');}
        t.addEventListener('click', toggle);
        if(link){link.addEventListener('click', toggle);}
      });
      var cur=root.querySelector('.current-cat');
      if(cur){
        var n=cur;
        while(n && n!==root){
          if(n.tagName==='LI' && n.classList.contains('cat-parent')){n.classList.add('wt-open');}
          n=n.parentNode;
        }
      }
    });
  }
  function fmtMilhar(s){
    s=(s||'').replace(/[^0-9]/g,'');               // so digitos
    return s.replace(/\B(?=(\d{3})+(?!\d))/g,'.'); // ponto de milhar
  }
  function precoFilter(){
    document.querySelectorAll('.widget_price_filter .price_slider_wrapper').forEach(function(wrap){
      if(wrap.getAttribute('data-wt-preco')){return;}
      var amount = wrap.querySelector('.price_slider_amount');
      var label  = wrap.querySelector('.price_label');
      var nMin   = wrap.querySelector('#min_price') || document.getElementById('min_price');
      var nMax   = wrap.querySelector('#max_price') || document.getElementById('max_price');
      if(!amount || !nMin || !nMax){return;}
      wrap.setAttribute('data-wt-preco','1');
      // caixas visiveis (sem name -> nao sao enviadas; quem envia sao os nativos crus)
      function mk(ph){
        var i=document.createElement('input');
        i.type='text'; i.className='wt-box';
        i.setAttribute('inputmode','numeric'); i.setAttribute('placeholder',ph);
        return i;
      }
      var bMin=mk('Preço mínimo'), bMax=mk('Preço máximo');
      amount.insertBefore(bMax, amount.firstChild);
      amount.insertBefore(bMin, amount.firstChild);
      function bind(box, nat){
        box.addEventListener('input', function(){
          var raw=box.value.replace(/[^0-9]/g,'');
          nat.value=raw;              // numero cru pro WooCommerce (slider/filtro)
          box.value=fmtMilhar(raw);   // bonito pro usuario
        });
      }
      bind(bMin,nMin); bind(bMax,nMax);
      // valores ao vivo abaixo da barra
      var range=document.createElement('div'); range.className='wt-preco-range';
      var lo=document.createElement('span'), hi=document.createElement('span');
      range.appendChild(lo); range.appendChild(hi);
      wrap.appendChild(range);
      function sync(){
        bMin.value=fmtMilhar(nMin.value);  // arrastar tb formata as caixas
        bMax.value=fmtMilhar(nMax.value);
        if(label){
          var f=label.querySelector('.from'), t=label.querySelector('.to');
          lo.textContent=f?f.textContent:'';
          hi.textContent=t?t.textContent:'';
        }
      }
      sync();
      if(label){ new MutationObserver(sync).observe(label, {childList:true,subtree:true,characterData:true}); }
    });
  }
  function buscaProdutos(){
    // faz a busca da barra lateral filtrar PRODUTOS (resultado bonito da loja, nao a pagina generica)
    document.querySelectorAll('.widget_search form, .wp-block-search form').forEach(function(f){
      if(!f.querySelector('[name="s"]')){return;}
      if(f.querySelector('[name="post_type"]')){return;}
      var i=document.createElement('input');
      i.type='hidden'; i.name='post_type'; i.value='product';
      f.appendChild(i);
    });
  }
  function boot(){init();precoFilter();buscaProdutos();}
  if(document.readyState!=='loading'){boot();}
  else{document.addEventListener('DOMContentLoaded', boot);}
})();
JS;

    echo '<style id="wt-acc-css">' . $css . '</style>';
    echo '<script id="wt-acc-js">' . $js . '</script>';
}, 99 );
