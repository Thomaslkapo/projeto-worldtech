# MODELO de credenciais. Copie este arquivo para "secrets.ps1" e preencha com suas chaves reais.
# O secrets.ps1 está no .gitignore e NUNCA será enviado ao GitHub.

# WooCommerce REST API (WordPress -> WooCommerce -> Configurações -> Avançado -> REST API)
$global:WC_CK  = 'ck_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'
$global:WC_CS  = 'cs_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'
$global:WC_BASE = 'https://worldtechparaguai.com/wp-json/wc/v3'

# n8n API Key (n8n -> Settings -> API)
$global:N8N_KEY  = 'COLE_AQUI_SUA_API_KEY_DO_N8N'
$global:N8N_BASE = 'http://localhost:5678/api/v1'
