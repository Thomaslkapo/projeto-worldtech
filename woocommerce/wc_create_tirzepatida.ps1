$ck = 'ck_66b73cbc3243de77ed056d4f8c772772d8b9a6bf'
$cs = 'cs_417935cba714f1c4c6c1c3d34be756ccba69019f'
$pair = $ck + ':' + $cs
$base64 = [Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes($pair))
$headers = @{ Authorization = 'Basic ' + $base64; 'Content-Type' = 'application/json' }
$base = 'https://worldtechparaguai.com/wp-json/wc/v3'

$produtos = Get-Content 'c:/Users/Usuario/Downloads/tirzepatida_produtos.json' -Raw | ConvertFrom-Json

foreach ($p in $produtos) {
    Write-Host "`nCriando: $($p.name)"

    $cats = $p.categories | ForEach-Object { @{ id = [int]$_ } }
    $aprs = $p.variations | ForEach-Object { $_.apresentacao }

    $body = @{
        name              = $p.name
        type              = 'variable'
        status            = 'publish'
        short_description = $p.short_description
        description       = $p.description
        categories        = $cats
        attributes        = @(
            @{ id = 32; visible = $true; variation = $true; options = $aprs }
            @{ id = 33; visible = $true; variation = $true; options = @('15mg') }
        )
    } | ConvertTo-Json -Depth 10

    $product = Invoke-RestMethod -Uri "$base/products" -Method Post -Headers $headers -Body $body
    Write-Host "  Produto ID: $($product.id)"

    foreach ($v in $p.variations) {
        $varBody = @{
            status        = 'publish'
            regular_price = $v.varejo.ToString()
            attributes    = @(
                @{ id = 32; option = $v.apresentacao }
                @{ id = 33; option = '15mg' }
            )
            meta_data     = @(
                @{ key = 'preco_atacado'; value = $v.atacado.ToString() }
            )
        } | ConvertTo-Json -Depth 10

        $var = Invoke-RestMethod -Uri "$base/products/$($product.id)/variations" -Method Post -Headers $headers -Body $varBody
        Write-Host "  Variacao: $($v.apresentacao) 15mg | Varejo: `$$($v.varejo) | Atacado: `$$($v.atacado) | ID: $($var.id)"
    }
}

Write-Host "`n========================================="
Write-Host "Todos os produtos Tirzepatida criados!"
Write-Host "========================================="
