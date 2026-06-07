<?php
/**
 * WorldTech - Restaura a posicao de rolagem ao voltar (desktop + iOS Safari).
 *
 * Salva a posicao continuamente e, ao voltar, recoloca na posicao exata.
 * Usa 'pageshow' (cobre o bfcache do iOS Safari) + retry para Elementor/lazy.
 *
 * Instalar no Code Snippets (sem a linha <?php), Run everywhere, ativar.
 * PHP puro (sem ?>), JS so com aspas simples e sem '$' => seguro.
 */
add_action( 'wp_footer', function () {
    echo "<script>(function(){if(!('sessionStorage' in window))return;var key='wt_scroll_'+location.pathname+location.search;var st;function save(){try{sessionStorage.setItem(key,String(window.scrollY||window.pageYOffset||0));}catch(e){}}window.addEventListener('scroll',function(){if(st)return;st=setTimeout(function(){save();st=null;},150);},{passive:true});window.addEventListener('pagehide',save);document.addEventListener('visibilitychange',function(){if(document.visibilityState==='hidden')save();});function restore(){var sv=sessionStorage.getItem(key);if(sv===null)return;var target=parseInt(sv,10)||0;if(target<=0)return;var n=0;var t=setInterval(function(){window.scrollTo(0,target);n++;if(Math.abs((window.scrollY||window.pageYOffset||0)-target)<3||n>50){clearInterval(t);}},80);}window.addEventListener('pageshow',function(e){var nav=(performance.getEntriesByType&&performance.getEntriesByType('navigation')[0])||null;var isBack=e.persisted||(nav?nav.type==='back_forward':(performance.navigation&&performance.navigation.type===2));if(isBack){restore();}});})();</script>";
}, 99 );
