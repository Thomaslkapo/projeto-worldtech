param([switch]$Apply)
# Faz match das imagens (biblioteca) com os produtos de farmacos.
# Imagem COM marca -> so no produto daquela marca+formula.
# Imagem SEM marca -> nos produtos da mesma formula que ainda NAO tem imagem.
. "$PSScriptRoot/../secrets.ps1"
$pair = $WC_CK + ':' + $WC_CS
$b64 = [Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes($pair))
$headers = @{ Authorization = 'Basic ' + $b64; 'Content-Type' = 'application/json; charset=utf-8' }
$base = $WC_BASE

$marcas = @('BIONEXIS','BIOGENESIS','CELLGENIC','GEN HEALTH','NEXUS','ALLUVI','USA PEPTIDES','MEDPLUS','NEOPEPTIDES','DRAGON ELITE','OXYGEN','THERA','VELTRANE','SYNEDICA','MOUNJARO','GLUCONEX','LIPOLAND','LIPOLESS','TIRZEDRAL','TIRZEC','SLIMEX')

function Get-Marca($texto) {
    $u = ' ' + $texto.ToUpper() + ' '
    foreach ($m in $marcas) { if ($u -match ('\b' + [regex]::Escape($m) + '\b')) { return $m } }
    return $null
}
function Norm-Formula($texto) {
    $s = ' ' + $texto.ToUpper() + ' '
    $s = $s -replace 'IMAGES \(\d+\)', ' '
    foreach ($m in $marcas) { $s = $s -replace ('\b' + [regex]::Escape($m) + '\b'), ' ' }
    $s = $s -replace '\b\d+\s*(MG|ML|MCG|UI|IU|U)\b', ' '
    $s = $s -replace '[^A-Z0-9\+\-]', ' '
    $s = $s -replace '\s+', ' '
    return $s.Trim()
}

# imagens de farmaco que o usuario subiu (id -> titulo)
$imgs = @(
    @{id=10863; t='BIONEXIS GLOW'}
    @{id=10821; t='BIOGENESIS TESAMORELIN 10mg'}
    @{id=10819; t='BIOGENESIS GLOW 70mg'}
    @{id=10818; t='BIOGENESIS PT-141 10mg'}
    @{id=10817; t='BIOGENESIS GHK-CU 100mg'}
    @{id=10816; t='ALLUVI Retatrutide 40mg'}
    @{id=10815; t='ALLUVI BPC-157 + TB-500'}
    @{id=10875; t='BIONEXIS TESAMORELIN'}
    @{id=10879; t='SLU-PP-332'}
    @{id=10877; t='SS-31'}
    @{id=10876; t='TB-500'}
    @{id=10873; t='Retatrutide'}
    @{id=10872; t='PT-141'}
    @{id=10868; t='MOTS-C'}
    @{id=10866; t='KLOW'}
    @{id=10862; t='EPITHALON'}
    @{id=10861; t='BPC-157'}
    @{id=10822; t='AOD-9604 5mg'}
)

# carrega produtos variaveis publicados
$prods = @()
for ($pg=1; $pg -le 15; $pg++) {
    $pp = Invoke-RestMethod -Uri "$base/products?type=variable&per_page=100&page=$pg&status=publish" -Headers $headers
    if (-not $pp -or $pp.Count -eq 0) { break }
    $prods += $pp
}
# usa todos os variaveis (o match por marca/formula naturalmente so pega farmacos)
$farm = $prods
foreach ($p in $farm) {
    $p | Add-Member -NotePropertyName mk -NotePropertyValue (Get-Marca $p.name) -Force
    $p | Add-Member -NotePropertyName fm -NotePropertyValue (Norm-Formula $p.name) -Force
    $p | Add-Member -NotePropertyName temImg -NotePropertyValue ($p.images.Count -gt 0) -Force
}

$setados = @{}
function Aplicar($prodId, $imgId, $motivo) {
    if ($Apply) {
        $body = @{ images = @(@{ id=$imgId }) } | ConvertTo-Json -Depth 5
        Invoke-RestMethod -Uri "$base/products/$prodId" -Method Put -Headers $headers -Body ([Text.Encoding]::UTF8.GetBytes($body)) | Out-Null
    }
    $setados[$prodId] = $true
}

Write-Host "===== FASE 1: imagens COM marca (match exato) ====="
foreach ($im in $imgs) {
    $mk = Get-Marca $im.t
    if (-not $mk) { continue }
    $fm = Norm-Formula $im.t
    $alvo = $farm | Where-Object { $_.mk -eq $mk -and $_.fm -eq $fm } | Select-Object -First 1
    if ($alvo) {
        Write-Host ("  IMG '{0}' -> [{1}] {2}" -f $im.t, $alvo.id, $alvo.name)
        Aplicar $alvo.id $im.id 'marca'
    } else {
        Write-Host ("  IMG '{0}' (marca {1}, formula '{2}') -> SEM PRODUTO CORRESPONDENTE" -f $im.t, $mk, $fm) -ForegroundColor Yellow
    }
}

Write-Host "`n===== FASE 2: imagens SEM marca (por formula, so nos sem imagem) ====="
foreach ($im in $imgs) {
    $mk = Get-Marca $im.t
    if ($mk) { continue }
    $fm = Norm-Formula $im.t
    $alvos = $farm | Where-Object { $_.fm -eq $fm -and -not $setados.ContainsKey($_.id) }
    if ($alvos.Count -eq 0) { Write-Host ("  IMG '{0}' (formula '{1}') -> nenhum produto sem imagem" -f $im.t, $fm); continue }
    foreach ($a in $alvos) {
        Write-Host ("  IMG '{0}' -> [{1}] {2}" -f $im.t, $a.id, $a.name)
        Aplicar $a.id $im.id 'formula'
    }
}

if ($Apply) { Write-Host "`n=== APLICADO. Limpar cache pela Hostinger. ===" }
else { Write-Host "`n=== DRY-RUN (nada aplicado). Rode com -Apply para aplicar. ===" }
