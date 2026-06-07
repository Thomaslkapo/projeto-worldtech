# Pre-seleciona SEMPRE a variacao MAIS BARATA em todo produto variavel.
# Desempate (mesmo menor preco): nos iPhones 17 usa a cor signature; senao a primeira.
. "$PSScriptRoot/../secrets.ps1"
$pair = $WC_CK + ':' + $WC_CS
$b64 = [Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes($pair))
$headers = @{ Authorization = 'Basic ' + $b64; 'Content-Type' = 'application/json; charset=utf-8' }
$base = $WC_BASE

# cor signature por id de produto (desempate)
$signature = @{
    10737 = 'lavender'
    10743 = 'black'
    10746 = 'sky blue'
    10751 = 'cosmic orange'
    10758 = 'cosmic orange'
}

function Put-Default($prodId, $attrs) {
    $body = @{ default_attributes = $attrs } | ConvertTo-Json -Depth 5
    $bytes = [Text.Encoding]::UTF8.GetBytes($body)
    return Invoke-RestMethod -Uri "$base/products/$prodId" -Method Put -Headers $headers -Body $bytes
}

$prods = @()
for ($pg=1; $pg -le 15; $pg++) {
    $pp = Invoke-RestMethod -Uri "$base/products?type=variable&per_page=100&page=$pg&status=publish" -Headers $headers
    if (-not $pp -or $pp.Count -eq 0) { break }
    $prods += $pp
}
Write-Host "Produtos variaveis: $($prods.Count)"

$ok = 0
foreach ($p in $prods) {
    $vars = Invoke-RestMethod -Uri "$base/products/$($p.id)/variations?per_page=100" -Headers $headers
    if (-not $vars -or $vars.Count -eq 0) { continue }

    # variacoes com preco valido
    $comPreco = $vars | Where-Object { $_.regular_price -and "$($_.regular_price)".Trim() -ne '' -and "$($_.regular_price)".Trim() -ne '0' }

    if (-not $comPreco) {
        $escolhida = $vars[0]
    } else {
        $menor = ($comPreco | ForEach-Object { [double]$_.regular_price } | Measure-Object -Minimum).Minimum
        $candidatas = @($comPreco | Where-Object { [double]$_.regular_price -eq $menor })
        $escolhida = $null
        # desempate por cor signature (iPhones 17)
        if ($signature.ContainsKey([int]$p.id)) {
            $sig = $signature[[int]$p.id]
            $escolhida = $candidatas | Where-Object {
                ($_.attributes | Where-Object { $_.name -eq 'Cor' -and "$($_.option)".ToLower().Trim() -eq $sig }).Count -gt 0
            } | Select-Object -First 1
        }
        if (-not $escolhida) { $escolhida = $candidatas[0] }
    }

    $attrs = @()
    foreach ($a in $escolhida.attributes) { $attrs += @{ id = $a.id; option = $a.option } }
    if ($attrs.Count -eq 0) { continue }
    try { Put-Default $p.id $attrs | Out-Null; $ok++ }
    catch { Write-Host ("ERRO [{0}] {1}: {2}" -f $p.id, $p.name, $_.Exception.Message) }
}
Write-Host "=== $ok produtos com a variacao mais barata pre-selecionada ==="
Write-Host "Limpar cache pela Hostinger."
