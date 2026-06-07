<?php
/**
 * WorldTech - Restaura a posicao de rolagem ao voltar (desktop + iOS Safari) - v3.
 *
 * Melhorias contra inconsistencia:
 *  - salva a posicao TAMBEM no clique de qualquer link (captura o momento exato de sair)
 *  - janela de protecao (~400ms) onde o gesto de voltar NAO cancela a restauracao
 *  - cancela so com movimento real (touchmove/wheel/teclas), nao com o toque do swipe
 *  - retry mais longo p/ Elementor/imagens demorarem a montar a altura
 *
 * Code Snippets (sem <?php), Run everywhere, ativar. PHP puro, JS aspas simples, sem '$'.
 */
add_action( 'wp_footer', function () {
    echo "<script>(function(){if(!('sessionStorage' in window))return;var key='wt_scroll_'+location.pathname+location.search;var st;function save(){try{sessionStorage.setItem(key,String(window.scrollY||window.pageYOffset||0));}catch(e){}}window.addEventListener('scroll',function(){if(st)return;st=setTimeout(function(){save();st=null;},120);},{passive:true});window.addEventListener('pagehide',save);document.addEventListener('visibilitychange',function(){if(document.visibilityState==='hidden')save();});document.addEventListener('click',function(e){var a=e.target&&e.target.closest?e.target.closest('a'):null;if(a)save();},true);function restore(){var sv=sessionStorage.getItem(key);if(sv===null)return;var target=parseInt(sv,10)||0;if(target<=0)return;var n=0,t,protect=true;setTimeout(function(){protect=false;},400);function cleanup(){clearInterval(t);window.removeEventListener('wheel',us,{passive:true});window.removeEventListener('touchmove',us,{passive:true});window.removeEventListener('keydown',us);}function us(){if(protect)return;cleanup();}window.addEventListener('wheel',us,{passive:true});window.addEventListener('touchmove',us,{passive:true});window.addEventListener('keydown',us);t=setInterval(function(){window.scrollTo(0,target);n++;var cur=window.scrollY||window.pageYOffset||0;if((Math.abs(cur-target)<3&&n>5)||n>80){cleanup();}},50);}window.addEventListener('pageshow',function(e){var nav=(performance.getEntriesByType&&performance.getEntriesByType('navigation')[0])||null;var isBack=e.persisted||(nav?nav.type==='back_forward':(performance.navigation&&performance.navigation.type===2));if(isBack){restore();}});})();</script>";
}, 99 );
