# DRY-RUN: identifica grupos de farmacos da mesma formula em dosagens diferentes.
# Nao modifica nada - so lista os candidatos a consolidar.
. "$PSScriptRoot/../secrets.ps1"
$pair = $WC_CK + ':' + $WC_CS
$b64 = [Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes($pair))
$headers = @{ Authorization = 'Basic ' + $b64 }
$base = $WC_BASE

# todos os produtos variaveis publicados
$prods = @()
for ($pg=1; $pg -le 15; $pg++) {
    $pp = Invoke-RestMethod -Uri "$base/products?type=variable&per_page=100&page=$pg&status=publish" -Headers $headers
    if (-not $pp -or $pp.Count -eq 0) { break }
    $prods += $pp
}

$grupos = @{}
foreach ($p in $prods) {
    $dosAttr = $p.attributes | Where-Object { $_.id -eq 33 }   # Dosagem
    if (-not $dosAttr -or -not $dosAttr.options) { continue }   # so farmacos com dosagem
    $dose = ($dosAttr.options | Select-Object -First 1)
    # remove a dosagem do final do nome -> base
    $nomeBase = $p.name
    $nomeBase = ($nomeBase -replace ('\s*' + [regex]::Escape($dose) + '\s*$'), '').Trim()
    $nomeBase = ($nomeBase -replace '\s+',' ').Trim()
    if (-not $grupos.ContainsKey($nomeBase)) { $grupos[$nomeBase] = @() }
    $grupos[$nomeBase] += [pscustomobject]@{ id=$p.id; nome=$p.name; dose=$dose }
}

Write-Host "=== GRUPOS COM MAIS DE UMA DOSAGEM (candidatos a consolidar) ==="
$n = 0
foreach ($k in ($grupos.Keys | Sort-Object)) {
    if ($grupos[$k].Count -gt 1) {
        $n++
        Write-Host "`n[$k]"
        $grupos[$k] | ForEach-Object { Write-Host "    id=$($_.id) | $($_.nome) | dose=$($_.dose)" }
    }
}
Write-Host "`n=== $n grupos consolidaveis | $($prods.Count) produtos variaveis no total ==="
