# PILOTO: consolida BIONEXIS GHK-CU 50mg + 100mg num produto unico
# (Apresentacao x Dosagem como variacoes). Base = produto 10523 (100mg).
. "$PSScriptRoot/../secrets.ps1"
$pair = $WC_CK + ':' + $WC_CS
$b64 = [Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes($pair))
$headers = @{ Authorization = 'Basic ' + $b64; 'Content-Type' = 'application/json; charset=utf-8' }
$base = $WC_BASE

function Send($method, $ep, $obj) {
    $body = $obj | ConvertTo-Json -Depth 8
    $bytes = [Text.Encoding]::UTF8.GetBytes($body)
    return Invoke-RestMethod -Uri "$base$ep" -Method $method -Headers $headers -Body $bytes
}

# 1) Produto base 10523: renomeia e passa a ter as 2 dosagens
$r = Send 'Put' '/products/10523' @{
    name = 'BIONEXIS GHK-CU'
    attributes = @(
        @{ id=32; visible=$true; variation=$true; options=@('Ampola','Caneta') }
        @{ id=33; visible=$true; variation=$true; options=@('50mg','100mg') }
    )
}
Write-Host "Produto base atualizado: $($r.name) | dosagens: $(($r.attributes | Where-Object {$_.id -eq 33}).options -join ',')"

# 2) Adiciona as variacoes de 50mg (que estavam no produto 10520)
$nv1 = Send 'Post' '/products/10523/variations' @{
    regular_price='59'; status='publish'
    attributes=@( @{id=32;option='Ampola'}, @{id=33;option='50mg'} )
}
Write-Host "  + Ampola/50mg `$59 (var $($nv1.id))"
$nv2 = Send 'Post' '/products/10523/variations' @{
    regular_price='137'; status='publish'
    attributes=@( @{id=32;option='Caneta'}, @{id=33;option='50mg'} )
}
Write-Host "  + Caneta/50mg `$137 (var $($nv2.id))"

# 3) Desativa o produto 50mg antigo (rascunho - reversivel, some da loja)
$r2 = Send 'Put' '/products/10520' @{ status='draft' }
Write-Host "Produto antigo 'GHK-CU 50mg' (10520) -> status: $($r2.status)"

# 4) Default = variacao mais barata (Ampola/50mg)
$r3 = Send 'Put' '/products/10523' @{ default_attributes=@( @{id=32;option='Ampola'}, @{id=33;option='50mg'} ) }
Write-Host "Default: $(($r3.default_attributes | ForEach-Object {$_.option}) -join '/')"

Write-Host "`n=== Piloto concluido. Conferir variacoes do produto consolidado: ==="
$vars = Invoke-RestMethod -Uri "$base/products/10523/variations?per_page=50" -Headers $headers
$vars | ForEach-Object { $a=($_.attributes | ForEach-Object {$_.option}) -join '/'; Write-Host "  $a = `$$($_.regular_price)" }
Write-Host "Limpar cache pela Hostinger."
