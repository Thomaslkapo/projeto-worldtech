# Consolida os grupos de farmacos da mesma formula em dosagens diferentes.
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

$grupos = @(
    @{ baseId=10609; juntarId=10607; nome='CELLGENIC GHK-CU' }
    @{ baseId=10605; juntarId=10603; nome='GEN HEALTH GHK-CU' }
    @{ baseId=10575; juntarId=10573; nome='NEXUS GHK-CU' }
)

foreach ($g in $grupos) {
    Write-Host "`n=== Consolidando: $($g.nome) ==="
    $baseP   = Invoke-RestMethod -Uri "$base/products/$($g.baseId)" -Headers $headers
    $juntarP = Invoke-RestMethod -Uri "$base/products/$($g.juntarId)" -Headers $headers

    # uniao das opcoes de Apresentacao (32) e Dosagem (33)
    $apres = @()
    $doses = @()
    foreach ($pp in @($baseP, $juntarP)) {
        foreach ($a in $pp.attributes) {
            if ($a.id -eq 32) { $apres += $a.options }
            if ($a.id -eq 33) { $doses += $a.options }
        }
    }
    $apres = @($apres | Select-Object -Unique)
    $doses = @($doses | Select-Object -Unique)

    # atualiza o produto base: nome + atributos unidos
    Send 'Put' "/products/$($g.baseId)" @{
        name = $g.nome
        attributes = @(
            @{ id=32; visible=$true; variation=$true; options=$apres }
            @{ id=33; visible=$true; variation=$true; options=$doses }
        )
    } | Out-Null
    Write-Host "  base renomeada p/ '$($g.nome)' | apres=[$($apres -join ',')] doses=[$($doses -join ',')]"

    # adiciona as variacoes do produto a juntar na base
    $varsJ = Invoke-RestMethod -Uri "$base/products/$($g.juntarId)/variations?per_page=50" -Headers $headers
    foreach ($v in $varsJ) {
        $attrs = @()
        foreach ($a in $v.attributes) { $attrs += @{ id=$a.id; option=$a.option } }
        $nv = Send 'Post' "/products/$($g.baseId)/variations" @{
            regular_price = "$($v.regular_price)"; status='publish'; attributes=$attrs
        }
        $ad = ($v.attributes | ForEach-Object { $_.option }) -join '/'
        Write-Host "    + $ad = `$$($v.regular_price)"
    }

    # desativa o produto juntado
    Send 'Put' "/products/$($g.juntarId)" @{ status='draft' } | Out-Null
    Write-Host "  produto antigo $($g.juntarId) -> rascunho"
}
Write-Host "`n=== Consolidacao concluida. Rode preselecionar_mais_barata.ps1 e limpe o cache. ==="
