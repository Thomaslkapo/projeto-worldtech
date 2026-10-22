# WorldTech — Automação de Precificação via WhatsApp + IA

Sistema de automação que monitora mensagens de fornecedores no WhatsApp, interpreta listas de preços com IA e atualiza automaticamente o e-commerce em tempo real — sem intervenção manual.

---

## O problema que resolve

Lojas de atacado recebem listas de preços de múltiplos fornecedores via WhatsApp todos os dias. Atualizar o site manualmente é lento, sujeito a erros e impossível de escalar.

Este sistema elimina esse processo: o fornecedor manda a lista, o site atualiza sozinho.

---

## Como funciona

```
Fornecedor envia lista de preços via WhatsApp
        ↓
WhatsApp Bridge (Baileys/Node.js) captura a mensagem
        ↓
n8n recebe via webhook e envia ao Gemini AI
        ↓
Gemini interpreta texto livre → JSON estruturado
        ↓
Sistema compara preços entre fornecedores (menor preço vence)
        ↓
WooCommerce REST API atualiza preços, estoque e variações
        ↓
Site atualizado em menos de 1 minuto
```

---

## Funcionalidades

- **Parsing com IA** — Gemini interpreta qualquer formato de lista (texto livre, tabelas, listas informais)
- **Multi-fornecedor** — compara preços de vários fornecedores, sempre aplica o menor
- **Acumulação diária** — listas do dia se complementam; preço mais barato sempre prevalece
- **Varejo e atacado** — regras de margem independentes para cada canal de venda
- **Criação automática de variações** — se uma cor ou configuração nova aparecer na lista, cria no site automaticamente
- **Out-of-stock automático** — produtos que somem das listas ficam indisponíveis no site
- **Fila de mensagens** — processa listas em sequência, sem sobrecarregar a API do Gemini
- **Lembrete automático** — envia WhatsApp para fornecedor que não mandou lista em 2 dias
- **Auto-start** — reinicia automaticamente após reboot do servidor (Windows Task Scheduler)

---

## Stack

| Camada | Tecnologia |
|--------|-----------|
| Automação / orquestração | [n8n](https://n8n.io) |
| IA para parsing | Google Gemini API |
| WhatsApp bridge | [Baileys](https://github.com/WhiskeySockets/Baileys) (Node.js) |
| E-commerce | WooCommerce REST API v3 (WordPress) |
| Scripts auxiliares | PowerShell, Node.js |
| Versionamento | Git / GitHub |

---

## Arquitetura

```
whatsapp-bridge/
  index.js              — Bridge WhatsApp, fila de mensagens, acumulação de preços
  lembrete_fornecedores.ps1 — Lembrete diário para fornecedores inativos

n8n/
  workflow_universal.json — Workflow: webhook → Gemini → WooCommerce

iphones/                — Scripts de criação de catálogo de iPhones
woocommerce/            — Scripts de criação de catálogo de fármacos
pricing/                — Lógica de precificação e margens
snippets/               — Snippets PHP para o tema WordPress
```

---

## Segurança

Todas as credenciais (chaves de API, tokens WooCommerce, etc.) ficam exclusivamente em `secrets.ps1`, que está no `.gitignore` e **nunca** é enviado ao repositório.

---

## Resultado

- Atualização de preços em **menos de 1 minuto** após o fornecedor enviar a lista
- Zero intervenção manual no processo diário
- Suporte a catálogos com **centenas de variações** (modelos, cores, armazenamento, origem)
- Sistema rodando em produção para loja de atacado de eletrônicos e fármacos
