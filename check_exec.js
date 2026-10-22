const http = require('http');
const fs   = require('fs');

const ps      = fs.readFileSync('C:/Users/Usuario/Downloads/Projeto do Claude Code/secrets.ps1','utf8');
const N8N_KEY = (ps.match(/N8N_KEY\s*=\s*'([^']+)'/) || [])[1];
const WF_ID   = 'QDLTmb2WEd5WUzVU';

function n8nReq(path){
  return new Promise((res,rej)=>{
    const r=http.request({hostname:'localhost',port:5678,path,method:'GET',
      headers:{'X-N8N-API-KEY':N8N_KEY}},
      resp=>{let d='';resp.on('data',c=>d+=c);resp.on('end',()=>{try{res(JSON.parse(d));}catch(e){res(d);}});});
    r.on('error',rej);r.end();
  });
}

function getItems(rd,n){ return (rd[n]||[]).flatMap(r=>r?.data?.main?.[0]||[]); }

async function main(){
  const execs = await n8nReq('/api/v1/executions?workflowId='+WF_ID+'&limit=5');
  for(const ex of (execs.data||[])){
    const dt = new Date(ex.startedAt).toLocaleString('pt-BR',{timeZone:'America/Asuncion'});
    console.log(`\n=== Exec #${ex.id} (${ex.status}) — ${dt} ===`);

    const exec = await n8nReq('/api/v1/executions/'+ex.id+'?includeData=true');
    const rd = exec.data?.resultData?.runData || {};

    if(exec.data?.resultData?.error){
      console.log('  ❌ ERRO GERAL:', exec.data.resultData.error.message);
    }

    for(const [nodeName, runs] of Object.entries(rd)){
      for(const run of (runs||[])){
        if(run.error) console.log(`  ❌ ERRO em "${nodeName}": ${run.error.message}`);
      }
    }

    const gItems = getItems(rd,'Extrair JSON Gemini').filter(i=>i.json?.modeloSite);
    const pvItems = getItems(rd,'Processar Variacoes');
    const instock  = pvItems.filter(i=>i.json?.acao==='instock');
    const outstock = pvItems.filter(i=>i.json?.acao==='outofstock');
    const criar    = pvItems.filter(i=>i.json?.acao==='criar');
    const avItems  = getItems(rd,'Atualizar Variacao');
    const cvItems  = getItems(rd,'Criar Variacao');

    console.log(`  📦 Gemini extraiu: ${gItems.length} produtos`);
    if(pvItems.length) console.log(`  🔄 Processados: ${instock.length} atualizados | ${outstock.length} out-of-stock | ${criar.length} criados`);
    if(avItems.length||cvItems.length) console.log(`  ✅ WooCommerce: ${avItems.length} atualizados, ${cvItems.length} criados`);

    // Produtos que não foram encontrados no WC (skip por falta de match)
    const skip = pvItems.filter(i=>i.json?.acao==='skip' && i.json?.motivo);
    if(skip.length){
      console.log(`  ⚠️  Não encontrados no site (${skip.length}):`);
      skip.slice(0,10).forEach(i=>console.log(`      - ${i.json.pai_nome || i.json.modeloSite}`));
      if(skip.length>10) console.log(`      ... e mais ${skip.length-10}`);
    }
  }
}
main().catch(e=>console.error('ERRO:',e.message));
