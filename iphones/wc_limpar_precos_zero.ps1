# Varre todos os produtos variaveis e, para cada variacao com preco 0/vazio,
# ESVAZIA o regular_price (em vez de 0) e marca outofstock.
# Isso impede o WooCommerce de mostrar "$0.00" na faixa de preco do produto.
. "$PSScriptRoot/../secrets.ps1"
$pair = $WC_CK + ':' + $WC_CS
$b64 = [Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes($pair))
$headers = @{ Authorization = 'Basic ' + $b64; 'Content-Type' = 'application/json' }
$base = $WC_BASE

$limpos = 0; $produtosTocados = 0
$page = 1
while ($true) {
    $prods = Invoke-RestMethod -Uri "$base/products?type=variable&per_page=50&page=$page" -Headers $headers
    if (-not $prods -or $prods.Count -eq 0) { break }
    foreach ($p in $prods) {
        $vars = Invoke-RestMethod -Uri "$base/products/$($p.id)/variations?per_page=100" -Headers $headers
        $tocou = $false
        foreach ($v in $vars) {
            $rp = "$($v.regular_price)".Trim()
            if ($rp -eq '' -or $rp -eq '0' -or $rp -eq '0.00') {
                $body = @{ regular_price = ''; sale_price = ''; stock_status = 'outofstock' } | ConvertTo-Json
                Invoke-RestMethod -Uri "$base/products/$($p.id)/variations/$($v.id)" -Method Put -Headers $headers -Body $body | Out-Null
                $limpos++
                $tocou = $true
            }
        }
        if ($tocou) { $produtosTocados++; Write-Host "  limpo: [$($p.id)] $($p.name)" }
    }
    $page++
}
Write-Host "`n=== $limpos variacoes esvaziadas em $produtosTocados produtos ==="
Write-Host "Lembrete: limpar cache WP Rocket."
