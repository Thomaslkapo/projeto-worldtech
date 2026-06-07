# Converte em "produto externo" (botao Consultar preco -> WhatsApp) todos os produtos
# que NAO sejam da Apple (67) nem Farmacia (58) nem suas subcategorias.
. "$PSScriptRoot/../secrets.ps1"
$pair = $WC_CK + ':' + $WC_CS
$b64 = [Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes($pair))
$headers = @{ Authorization = 'Basic ' + $b64; 'Content-Type' = 'application/json; charset=utf-8' }
$base = $WC_BASE
$numero = '595975682071'

# 1. Todas categorias -> mapa id->parent
$cats = @()
for ($pg=1; $pg -le 4; $pg++) {
    $c = Invoke-RestMethod -Uri "$base/products/categories?per_page=100&page=$pg" -Headers $headers
    if (-not $c -or $c.Count -eq 0) { break }
    $cats += $c
}
$parent = @{}
foreach ($c in $cats) { $parent[[int]$c.id] = [int]$c.parent }

function IsNormal($id) {
    $cur = [int]$id
    $guard = 0
    while ($cur -ne 0 -and $guard -lt 20) {
        if ($cur -eq 67 -or $cur -eq 58) { return $true }
        if (-not $parent.ContainsKey($cur)) { break }
        $cur = $parent[$cur]
        $guard++
    }
    return $false
}

# 2. Todos produtos publicados
$prods = @()
for ($pg=1; $pg -le 15; $pg++) {
    $p = Invoke-RestMethod -Uri "$base/products?per_page=100&page=$pg&status=publish" -Headers $headers
    if (-not $p -or $p.Count -eq 0) { break }
    $prods += $p
}
Write-Host "Total de produtos publicados: $($prods.Count)"

# 3. Converter os nao-normais
$conv = 0; $pulou = 0
foreach ($p in $prods) {
    if ($p.type -eq 'external') { $pulou++; continue }
    $ehNormal = $false
    foreach ($cat in $p.categories) { if (IsNormal $cat.id) { $ehNormal = $true; break } }
    if ($ehNormal) { $pulou++; continue }

    $msg = 'Olá! Quero consultar o preço deste produto: ' + $p.name + '. Quanto está custando?'
    $url = 'https://wa.me/' + $numero + '?text=' + [uri]::EscapeDataString($msg)
    $body = @{ type='external'; button_text='Consultar preço'; external_url=$url; regular_price=''; sale_price='' } | ConvertTo-Json
    $bytes = [Text.Encoding]::UTF8.GetBytes($body)
    try {
        $r = Invoke-RestMethod -Uri "$base/products/$($p.id)" -Method Put -Headers $headers -Body $bytes
        Write-Host ("  OK [{0}] {1}" -f $p.id, $p.name)
        $conv++
    } catch {
        Write-Host ("  ERRO [{0}] {1}: {2}" -f $p.id, $p.name, $_.Exception.Message) -ForegroundColor Yellow
    }
}
Write-Host "`n=== $conv convertidos em 'Consultar preço' | $pulou mantidos (Apple/Farmacia ou ja externos) ==="
Write-Host "Lembrete: limpar cache WP Rocket."
