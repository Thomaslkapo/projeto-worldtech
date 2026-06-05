# Cria os produtos iPhone 17 (17, 17E, 17 Air, 17 Pro, 17 Pro Max) no WooCommerce,
# com cores e armazenamentos novos, e variações (armazenamento x cor).
# Produtos lacrados (sem "SWAP" no nome — diferente dos SWAP 13-16).
#
# Uso: copie secrets.example.ps1 -> secrets.ps1, depois:
#      powershell -ExecutionPolicy Bypass -File iphones/wc_create_iphone17.ps1

. "$PSScriptRoot/../secrets.ps1"

$pair = $WC_CK + ':' + $WC_CS
$base64 = [Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes($pair))
$headers = @{ Authorization = 'Basic ' + $base64; 'Content-Type' = 'application/json' }
$base = $WC_BASE

function WC-Post($ep, $body) { return Invoke-RestMethod -Uri "$base$ep" -Method Post -Headers $headers -Body ($body | ConvertTo-Json -Depth 10) }

# Atributos: id 9 = Armazenamento, id 11 = Cor. Categoria pai iPhone = 67, geral = 15.

# ── 1. NOVAS CORES (atributo pa_cor, id 11) ───────────────────────────────────
$novasCores = @('Cosmic Orange','Deep Blue','Sky Blue','Sage','Teal','Ultramarine')
foreach ($c in $novasCores) {
    $t = WC-Post '/products/attributes/11/terms' @{ name = $c }
    Write-Host "Cor: $c -> ID $($t.id)"
}

# ── 2. ARMAZENAMENTO 2TB (atributo id 9) ──────────────────────────────────────
$t2tb = WC-Post '/products/attributes/9/terms' @{ name = '2TB' }
Write-Host "Armazenamento: 2TB -> ID $($t2tb.id)"

# ── 3. CATEGORIAS ─────────────────────────────────────────────────────────────
$catMap = @{}
@('iPhone 17','iPhone 17E','iPhone 17 Air','iPhone 17 Pro','iPhone 17 Pro Max') | ForEach-Object {
    $c = WC-Post '/products/categories' @{ name = $_; parent = 67 }
    $catMap[$_] = $c.id
    Write-Host "Categoria: $_ -> ID $($c.id)"
}

# ── 4. PRODUTOS + VARIAÇÕES ───────────────────────────────────────────────────
function Criar-iPhone($nome, $catId, $storages, $cores, $descShort, $desc) {
    Write-Host "`nCriando: $nome"
    $product = WC-Post '/products' @{
        name = $nome; type = 'variable'; status = 'publish'
        short_description = $descShort; description = $desc
        categories = @(@{id=67}, @{id=15}, @{id=$catId})
        attributes = @(
            @{ id = 9;  visible = $true; variation = $true; options = @($storages) }
            @{ id = 11; visible = $true; variation = $true; options = @($cores) }
        )
    }
    Write-Host "  Produto ID: $($product.id)"
    foreach ($storage in $storages) {
        foreach ($cor in $cores) {
            $var = WC-Post "/products/$($product.id)/variations" @{
                status = 'publish'; regular_price = '0'
                attributes = @(@{ id = 9; option = $storage }, @{ id = 11; option = $cor })
            }
            Write-Host "  Var: $storage $cor -> ID $($var.id)"
        }
    }
}

Criar-iPhone 'iPhone 17' $catMap['iPhone 17'] @('256GB') @('Black','Blue','White','Lavender','Sage') `
    'iPhone 17 256GB original LLA/USA importado do Paraguai.' '<p>O <strong>iPhone 17 256GB</strong> importado do Paraguai na versao original LLA/USA.</p>'

Criar-iPhone 'iPhone 17E' $catMap['iPhone 17E'] @('256GB') @('Black','White') `
    'iPhone 17E 256GB original LLA/USA importado do Paraguai.' '<p>O <strong>iPhone 17E 256GB</strong> importado do Paraguai na versao original LLA/USA.</p>'

Criar-iPhone 'iPhone 17 Air' $catMap['iPhone 17 Air'] @('256GB') @('Black','White','Sky Blue','Gold') `
    'iPhone 17 Air 256GB original LLA/USA importado do Paraguai.' '<p>O <strong>iPhone 17 Air 256GB</strong>, o mais fino da Apple, importado do Paraguai na versao original LLA/USA.</p>'

Criar-iPhone 'iPhone 17 Pro' $catMap['iPhone 17 Pro'] @('256GB','512GB') @('Cosmic Orange','Deep Blue','Silver') `
    'iPhone 17 Pro 256GB e 512GB original LLA/USA importado do Paraguai.' '<p>O <strong>iPhone 17 Pro</strong> importado do Paraguai na versao original LLA/USA.</p>'

Criar-iPhone 'iPhone 17 Pro Max' $catMap['iPhone 17 Pro Max'] @('256GB','512GB','1TB','2TB') @('Cosmic Orange','Deep Blue','Silver') `
    'iPhone 17 Pro Max original LLA/USA importado do Paraguai.' '<p>O <strong>iPhone 17 Pro Max</strong>, topo de linha, importado do Paraguai na versao original LLA/USA.</p>'

Write-Host "`n=== Concluido ==="
