# Pre-seleciona a variacao padrao de cada produto variavel, pra o preco aparecer ao abrir.
# iPhones 17: cor signature + 256GB. Demais variaveis (SWAP + farmacos): 1a variacao com preco.
. "$PSScriptRoot/../secrets.ps1"
$pair = $WC_CK + ':' + $WC_CS
$b64 = [Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes($pair))
$headers = @{ Authorization = 'Basic ' + $b64; 'Content-Type' = 'application/json; charset=utf-8' }
$base = $WC_BASE

function Put-Default($prodId, $attrs) {
    $body = @{ default_attributes = $attrs } | ConvertTo-Json -Depth 5
    $bytes = [Text.Encoding]::UTF8.GetBytes($body)
    return Invoke-RestMethod -Uri "$base/products/$prodId" -Method Put -Headers $headers -Body $bytes
}

# 1) iPhones 17 - cores signature
$iph17 = @(
    @{ id=10737; nome='iPhone 17';         arm='256GB'; cor='Lavender' }
    @{ id=10743; nome='iPhone 17E';        arm='256GB'; cor='Black' }
    @{ id=10746; nome='iPhone 17 Air';     arm='256GB'; cor='Sky Blue' }
    @{ id=10751; nome='iPhone 17 Pro';     arm='256GB'; cor='Cosmic Orange' }
    @{ id=10758; nome='iPhone 17 Pro Max'; arm='256GB'; cor='Cosmic Orange' }
)
$jaFeitos = @{}
foreach ($p in $iph17) {
    $r = Put-Default $p.id @( @{id=9;option=$p.arm}, @{id=11;option=$p.cor} )
    $da = ($r.default_attributes | ForEach-Object { $_.option }) -join '/'
    Write-Host ("OK  {0,-18} -> {1}" -f $p.nome, $da)
    $jaFeitos[$p.id] = $true
}

# 2) Demais produtos variaveis: primeira variacao COM preco
$prods = @()
for ($pg=1; $pg -le 15; $pg++) {
    $pp = Invoke-RestMethod -Uri "$base/products?type=variable&per_page=100&page=$pg&status=publish" -Headers $headers
    if (-not $pp -or $pp.Count -eq 0) { break }
    $prods += $pp
}

$ok = 0; $semvar = 0
foreach ($p in $prods) {
    if ($jaFeitos.ContainsKey($p.id)) { continue }
    $vars = Invoke-RestMethod -Uri "$base/products/$($p.id)/variations?per_page=100" -Headers $headers
    if (-not $vars -or $vars.Count -eq 0) { $semvar++; continue }
    # primeira com preco
    $alvo = $vars | Where-Object { $_.regular_price -and "$($_.regular_price)".Trim() -ne '' -and "$($_.regular_price)".Trim() -ne '0' } | Select-Object -First 1
    if (-not $alvo) { $alvo = $vars[0] }
    $attrs = @()
    foreach ($a in $alvo.attributes) { $attrs += @{ id = $a.id; option = $a.option } }
    if ($attrs.Count -eq 0) { continue }
    try {
        Put-Default $p.id $attrs | Out-Null
        $ok++
    } catch {
        Write-Host ("  ERRO [{0}] {1}: {2}" -f $p.id, $p.name, $_.Exception.Message)
    }
}
Write-Host "`n=== iPhones 17: 5 com cor signature | Demais variaveis: $ok pre-selecionados | $semvar sem variacao ==="
Write-Host "Limpar cache pelo painel da Hostinger."
