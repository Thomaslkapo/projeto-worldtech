param([switch]$Apply)
# Remove a dosagem do FINAL do nome dos farmacos (usa a dosagem do atributo id 33).
# Nao renomeia se o novo nome duplicar outro produto.
. "$PSScriptRoot/../secrets.ps1"
$pair = $WC_CK + ':' + $WC_CS
$b64 = [Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes($pair))
$headers = @{ Authorization = 'Basic ' + $b64; 'Content-Type' = 'application/json; charset=utf-8' }
$base = $WC_BASE

# lista de produtos variaveis publicados
$prods = @()
for ($pg=1; $pg -le 15; $pg++) {
    $pp = Invoke-RestMethod -Uri "$base/products?type=variable&per_page=100&page=$pg&status=publish" -Headers $headers
    if (-not $pp -or $pp.Count -eq 0) { break }
    $prods += $pp
}

$farm = @()
foreach ($p in $prods) {
    $full = Invoke-RestMethod -Uri "$base/products/$($p.id)" -Headers $headers
    $dosAttr = $full.attributes | Where-Object { $_.id -eq 33 }
    if (-not $dosAttr -or -not $dosAttr.options) { continue }   # so farmacos com Dosagem
    $novo = $full.name
    foreach ($d in $dosAttr.options) {
        $novo = $novo -replace ('\s*' + [regex]::Escape($d) + '\s*$'), ''
    }
    $novo = ($novo -replace '\s+', ' ').Trim()
    $farm += [pscustomobject]@{ id=$full.id; nome=$full.name; novo=$novo }
}

# detecta nomes novos que duplicariam
$dupes = @($farm | Group-Object novo | Where-Object { $_.Count -gt 1 } | ForEach-Object { $_.Name })

$ren = 0; $pula = 0
foreach ($f in $farm) {
    if ($f.nome -eq $f.novo) { continue }      # ja esta sem dosagem
    if ($dupes -contains $f.novo) {
        Write-Host ("  PULA (duplicaria) [{0}] '{1}'" -f $f.id, $f.nome) -ForegroundColor Yellow
        $pula++
        continue
    }
    Write-Host ("  '{0}' -> '{1}'" -f $f.nome, $f.novo)
    if ($Apply) {
        $body = @{ name = $f.novo } | ConvertTo-Json
        Invoke-RestMethod -Uri "$base/products/$($f.id)" -Method Put -Headers $headers -Body ([Text.Encoding]::UTF8.GetBytes($body)) | Out-Null
    }
    $ren++
}
if ($Apply) { Write-Host "`n=== APLICADO: $ren renomeados | $pula pulados (duplicariam). Limpar cache Hostinger. ===" }
else { Write-Host "`n=== DRY-RUN: $ren a renomear | $pula pulados. Rode com -Apply. ===" }
