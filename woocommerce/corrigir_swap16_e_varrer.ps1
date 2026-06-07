# Corrige o iPhone SWAP 16 (variacoes sem atributos) e varre todos os variaveis
# procurando o mesmo bug (atributo com variation=false).
. "$PSScriptRoot/../secrets.ps1"
$pair = $WC_CK + ':' + $WC_CS
$b64 = [Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes($pair))
$headers = @{ Authorization = 'Basic ' + $b64; 'Content-Type' = 'application/json; charset=utf-8' }
$base = $WC_BASE

function Send($method, $ep, $obj) {
    $body = $obj | ConvertTo-Json -Depth 8
    return Invoke-RestMethod -Uri "$base$ep" -Method $method -Headers $headers -Body ([Text.Encoding]::UTF8.GetBytes($body))
}

# === 1) Corrige o SWAP 16 (9561) ===
Write-Host "=== Corrigindo iPhone SWAP 16 ==="
Send 'Put' '/products/9561' @{
    attributes = @(
        @{ id=9;  visible=$true; variation=$true; options=@('128GB') }
        @{ id=11; visible=$true; variation=$true; options=@('Black','Blue','Pink','White','Teal') }
    )
} | Out-Null
$fix = @(
    @{ var=9571;  cor='Black' }
    @{ var=9572;  cor='Blue' }
    @{ var=9573;  cor='Pink' }
    @{ var=10771; cor='White' }
    @{ var=10772; cor='Teal' }
)
foreach ($f in $fix) {
    $r = Send 'Put' "/products/9561/variations/$($f.var)" @{ attributes=@( @{id=9;option='128GB'}, @{id=11;option=$f.cor} ) }
    $a = ($r.attributes | ForEach-Object { $_.option }) -join '/'
    Write-Host "  var $($f.var) -> [$a]"
}

# === 2) Varre TODOS os variaveis procurando o bug (variation=false) ===
Write-Host "`n=== Varrendo todos os produtos variaveis (bug variation=false) ==="
$prods = @()
for ($pg=1; $pg -le 15; $pg++) {
    $pp = Invoke-RestMethod -Uri "$base/products?type=variable&per_page=100&page=$pg&status=publish" -Headers $headers
    if (-not $pp -or $pp.Count -eq 0) { break }
    $prods += $pp
}
$bug = 0
foreach ($p in $prods) {
    if ($p.id -eq 9561) { continue }
    $full = Invoke-RestMethod -Uri "$base/products/$($p.id)" -Headers $headers
    $temAttr = ($full.attributes | Where-Object { $_.variation -eq $true }).Count -gt 0
    $semAttr = $full.attributes.Count -gt 0 -and -not $temAttr
    if ($semAttr) {
        Write-Host "  BUG: [$($full.id)] $($full.name) (nenhum atributo com variation=true)" -ForegroundColor Yellow
        $bug++
    }
}
Write-Host "`n=== SWAP 16 corrigido. Outros com bug: $bug. Rodar preselecionar_mais_barata depois. ==="
