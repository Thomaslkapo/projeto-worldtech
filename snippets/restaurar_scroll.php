<?php
/**
 * WorldTech - Restaura a posicao de rolagem ao voltar (desktop + iOS Safari).
 *
 * Salva a posicao e, ao voltar, recoloca na posicao exata com retry curto.
 * IMPORTANTE: para de insistir assim que o usuario interage (wheel/touch/tecla),
 * para nao "travar" o scroll de quem quer rolar logo.
 *
 * Instalar no Code Snippets (sem a linha <?php), Run everywhere, ativar.
 * PHP puro (sem ?>), JS so com aspas simples e sem '$' => seguro.
 */
add_action( 'wp_footer', function () {
    echo "<script>(function(){if(!('sessionStorage' in window))return;var key='wt_scroll_'+location.pathname+location.search;var st;function save(){try{sessionStorage.setItem(key,String(window.scrollY||window.pageYOffset||0));}catch(e){}}window.addEventListener('scroll',function(){if(st)return;st=setTimeout(function(){save();st=null;},150);},{passive:true});window.addEventListener('pagehide',save);document.addEventListener('visibilitychange',function(){if(document.visibilityState==='hidden')save();});function restore(){var sv=sessionStorage.getItem(key);if(sv===null)return;var target=parseInt(sv,10)||0;if(target<=0)return;var n=0,t;function cleanup(){clearInterval(t);window.removeEventListener('wheel',stop,{passive:true});window.removeEventListener('touchstart',stop,{passive:true});window.removeEventListener('touchmove',stop,{passive:true});window.removeEventListener('keydown',stop);}function stop(){cleanup();}window.addEventListener('wheel',stop,{passive:true});window.addEventListener('touchstart',stop,{passive:true});window.addEventListener('touchmove',stop,{passive:true});window.addEventListener('keydown',stop);t=setInterval(function(){window.scrollTo(0,target);n++;if(Math.abs((window.scrollY||window.pageYOffset||0)-target)<3||n>40){cleanup();}},50);}window.addEventListener('pageshow',function(e){var nav=(performance.getEntriesByType&&performance.getEntriesByType('navigation')[0])||null;var isBack=e.persisted||(nav?nav.type==='back_forward':(performance.navigation&&performance.navigation.type===2));if(isBack){restore();}});})();</script>";
}, 99 );
