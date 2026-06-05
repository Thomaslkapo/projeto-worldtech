# Projeto WorldTech

Automação de precificação de produtos (iPhones e fármacos) com integração WhatsApp, n8n e WooCommerce.

## O que esse projeto faz

- Lê listas de preços de fornecedores recebidas via WhatsApp
- Compara preços entre fornecedores e seleciona o melhor
- Aplica margem de lucro automaticamente (varejo e atacado)
- Atualiza preços dos produtos no site WordPress/WooCommerce
- (futuro) Envia lista atualizada para clientes via WhatsApp

## Tecnologias

- n8n (orquestração do fluxo)
- WooCommerce REST API v3 (WordPress)
- PowerShell (scripts de criação de produtos e aplicação de preços)
- (futuro) Agente IA para parsing das mensagens dos fornecedores

## Estrutura

```
iphones/     Scripts de criação de produtos iPhone 17 (produtos, cores, variações)
pricing/     aplicar_precos.ps1 + json_precos_consolidado.json (formato padronizado)
n8n/         workflow_universal.json (fluxo genérico iPhone + fármaco)
woocommerce/ Scripts de criação do catálogo de fármacos
```

## Configuração das credenciais

As chaves de API **não** ficam no repositório. Para rodar:

```powershell
# 1. Copie o modelo e preencha com suas chaves reais
Copy-Item secrets.example.ps1 secrets.ps1
notepad secrets.ps1

# 2. Rode os scripts (eles leem o secrets.ps1 automaticamente)
powershell -ExecutionPolicy Bypass -File pricing/aplicar_precos.ps1
```

O `secrets.ps1` está no `.gitignore` e nunca é enviado ao GitHub.

## Lógica de precificação

**iPhones — Varejo:** `min(maior_preço + 50, menor_preço + 65)`
**iPhones — Atacado:** variação ≤35 → +15 | ≤50 → +10 | >50 → +5
**Fármacos — Varejo:** `maior_preço + 12`
**Fármacos — Atacado:** `maior_preço + 5`

## Formato do JSON padronizado

```json
{
  "lista_precos": [
    { "modeloSite": "iPhone 17", "atributos": {"Armazenamento": "256GB", "Cor": "Black"}, "preco": 770, "fornecedor": "BEST" }
  ]
}
```

## Status

Em desenvolvimento — fluxo de precificação funcional; parsing automático via IA pendente.
