<?php
/**
 * WorldTech - Restaura a posicao de rolagem ao voltar (botao "voltar" do navegador).
 *
 * Salva continuamente a posicao de scroll de cada pagina no sessionStorage e,
 * QUANDO o usuario volta (navegacao back/forward), recoloca na posicao exata.
 * Faz retry por alguns segundos para funcionar mesmo com Elementor/lazy/imagens.
 *
 * Instalar no Code Snippets (sem a linha <?php), Run everywhere, ativar.
 * Codigo 100% PHP puro (sem ?>), JS so com aspas simples e sem '$' => seguro.
 */
add_action( 'wp_footer', function () {
    echo "<script>(function(){if(!('sessionStorage' in window))return;var key='wt_scroll_'+location.pathname+location.search;var st;function save(){try{sessionStorage.setItem(key,String(window.scrollY||window.pageYOffset||0));}catch(e){}}window.addEventListener('scroll',function(){if(st)return;st=setTimeout(function(){save();st=null;},200);},{passive:true});window.addEventListener('pagehide',save);window.addEventListener('beforeunload',save);var nav=(performance.getEntriesByType&&performance.getEntriesByType('navigation')[0])||null;var isBack=nav?nav.type==='back_forward':(performance.navigation&&performance.navigation.type===2);if(isBack){var sv=sessionStorage.getItem(key);if(sv!==null){var target=parseInt(sv,10)||0;if(target>0){if('scrollRestoration' in history)history.scrollRestoration='manual';var n=0;var t=setInterval(function(){window.scrollTo(0,target);n++;if(Math.abs((window.scrollY||window.pageYOffset||0)-target)<3||n>50){clearInterval(t);}},80);}}}})();</script>";
}, 99 );
