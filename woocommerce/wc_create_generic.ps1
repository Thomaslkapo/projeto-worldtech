param([string]$JsonFile)

$ck = 'ck_66b73cbc3243de77ed056d4f8c772772d8b9a6bf'
$cs = 'cs_417935cba714f1c4c6c1c3d34be756ccba69019f'
$pair = $ck + ':' + $cs
$base64 = [Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes($pair))
$headers = @{ Authorization = 'Basic ' + $base64; 'Content-Type' = 'application/json' }
$base = 'https://worldtechparaguai.com/wp-json/wc/v3'

$produtos = Get-Content $JsonFile -Raw -Encoding UTF8 | ConvertFrom-Json

foreach ($p in $produtos) {

    # Produto simples
    if ($p.type -eq 'simple') {
        Write-Host "`nCriando simples: $($p.name)"
        $cats = $p.categories | ForEach-Object { @{ id = [int]$_ } }
        $body = @{
            name              = $p.name
            type              = 'simple'
            status            = 'publish'
            regular_price     = $p.regular_price.ToString()
            short_description = $p.short_description
            description       = $p.description
            categories        = $cats
            meta_data         = @(@{ key = 'preco_atacado'; value = $p.atacado.ToString() })
        } | ConvertTo-Json -Depth 10
        $product = Invoke-RestMethod -Uri "$base/products" -Method Post -Headers $headers -Body $body
        Write-Host "  ID: $($product.id) | Preco: `$$($p.regular_price)"
        continue
    }

    # Produto variavel
    Write-Host "`nCriando variavel: $($p.name)"
    $cats   = $p.categories | ForEach-Object { @{ id = [int]$_ } }
    $aprs   = ($p.variations | ForEach-Object { $_.apresentacao } | Select-Object -Unique)
    $doses  = ($p.variations | ForEach-Object { $_.dosagem } | Select-Object -Unique)

    $attrs = @(@{ id = 32; visible = $true; variation = $true; options = @($aprs) })
    if ($doses.Count -gt 0) {
        $attrs += @{ id = 33; visible = $true; variation = $true; options = @($doses) }
    }

    $body = @{
        name              = $p.name
        type              = 'variable'
        status            = 'publish'
        short_description = $p.short_description
        description       = $p.description
        categories        = $cats
        attributes        = $attrs
    } | ConvertTo-Json -Depth 10

    $product = Invoke-RestMethod -Uri "$base/products" -Method Post -Headers $headers -Body $body
    Write-Host "  Produto ID: $($product.id)"

    foreach ($v in $p.variations) {
        $varAttrs = @(@{ id = 32; option = $v.apresentacao })
        if ($v.dosagem) { $varAttrs += @{ id = 33; option = $v.dosagem } }

        $varBody = @{
            status        = 'publish'
            regular_price = $v.varejo.ToString()
            attributes    = $varAttrs
            meta_data     = @(@{ key = 'preco_atacado'; value = $v.atacado.ToString() })
        } | ConvertTo-Json -Depth 10

        $var = Invoke-RestMethod -Uri "$base/products/$($product.id)/variations" -Method Post -Headers $headers -Body $varBody
        $dosLabel = if ($v.dosagem) { "$($v.dosagem) " } else { "" }
        Write-Host "  Variacao: $($v.apresentacao) $dosLabel| Varejo: `$$($v.varejo) | Atacado: `$$($v.atacado) | ID: $($var.id)"
    }
}

Write-Host "`n=== Concluido: $JsonFile ==="
