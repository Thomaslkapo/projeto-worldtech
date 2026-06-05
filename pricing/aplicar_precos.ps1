# Aplica as fórmulas de precificação sobre os preços brutos dos fornecedores
# e atualiza as variações no WooCommerce.
#
# Uso:  copie secrets.example.ps1 -> secrets.ps1 e preencha as chaves, depois:
#       powershell -ExecutionPolicy Bypass -File pricing/aplicar_precos.ps1
#
# Entrada: pricing/json_precos_consolidado.json  (formato do JSON padronizado)

. "$PSScriptRoot/../secrets.ps1"

$pair = $WC_CK + ':' + $WC_CS
$base64 = [Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes($pair))
$headers = @{ Authorization = 'Basic ' + $base64; 'Content-Type' = 'application/json' }
$base = $WC_BASE

# ── Carregar dados brutos dos fornecedores ────────────────────────────────────
$dados = (Get-Content "$PSScriptRoot/json_precos_consolidado.json" -Raw | ConvertFrom-Json).lista_precos

# ── Agrupar por produto + atributos e aplicar formula ─────────────────────────
$grupos = @{}
foreach ($item in $dados) {
    $attrStr = ($item.atributos.PSObject.Properties | Sort-Object Name | ForEach-Object { "$($_.Name)=$($_.Value)" }) -join '|'
    $chave = "$($item.modeloSite)||$attrStr"
    if (-not $grupos.ContainsKey($chave)) {
        $grupos[$chave] = [ordered]@{
            modeloSite = $item.modeloSite
            atributos  = $item.atributos
            precos     = @()
        }
    }
    if ($item.preco -gt 0) { $grupos[$chave].precos += [double]$item.preco }
}

# ── Calcular preco final por grupo ────────────────────────────────────────────
# iPhone:  varejo = min(maior+50, menor+65); atacado em degraus pela variacao
# Farmaco: varejo = maior+12; atacado = maior+5
$calculados = @()
foreach ($g in $grupos.Values) {
    if ($g.precos.Count -eq 0) { continue }
    $menor = ($g.precos | Measure-Object -Minimum).Minimum
    $maior = ($g.precos | Measure-Object -Maximum).Maximum
    $variacao = $maior - $menor

    if ($g.modeloSite.ToLower().Contains('iphone')) {
        $preco = [Math]::Min($maior + 50, $menor + 65)
        if     ($variacao -le 35) { $atacado = $maior + 15 }
        elseif ($variacao -le 50) { $atacado = $maior + 10 }
        else                      { $atacado = $maior + 5 }
    } else {
        $preco   = $maior + 12
        $atacado = $maior + 5
    }

    $calculados += [ordered]@{
        modeloSite = $g.modeloSite
        atributos  = $g.atributos
        preco      = [int]$preco
        atacado    = [int]$atacado
        menor      = [int]$menor
        maior      = [int]$maior
        nForn      = $g.precos.Count
    }
}

Write-Host "=== $($calculados.Count) combinacoes produto/variacao calculadas ===`n"

function Norm($s) { return ($s -replace '\s+',' ').Trim().ToLower() }
function NormAttr($s) { return ($s -replace '\s+','').ToUpper() }

$cacheProd = @{}
$cacheVars = @{}

function Get-ProductId($modeloSite) {
    if ($cacheProd.ContainsKey($modeloSite)) { return $cacheProd[$modeloSite] }
    $search = [uri]::EscapeDataString($modeloSite)
    $lista = Invoke-RestMethod -Uri "$base/products?per_page=100&search=$search" -Headers $headers
    $n0 = Norm $modeloSite
    $wPM = $n0.Contains('pro max'); $wP = (-not $wPM) -and $n0.Contains('pro')
    $wAir = $n0.Contains('air'); $wE = $n0 -match '\d+e\b'; $wPL = $n0.Contains('plus')
    $best = $null; $pts = -1
    foreach ($p in $lista) {
        $n1 = Norm $p.name
        $hPM = $n1.Contains('pro max'); $hP = (-not $hPM) -and $n1.Contains('pro')
        $hAir = $n1.Contains('air'); $hE = $n1 -match '\d+e\b'; $hPL = $n1.Contains('plus')
        if ($hPM -and -not $wPM) { continue }
        if ($hP -and -not $wP) { continue }
        if ($hAir -and -not $wAir) { continue }
        if ($hPL -and -not $wPL) { continue }
        if ($wPM -and -not $hPM) { continue }
        if ($wP -and -not $hP) { continue }
        if ($wAir -and -not $hAir) { continue }
        if ($wE -and -not $hE) { continue }
        if ((-not $wE) -and $hE) { continue }
        $p2 = 0
        foreach ($w in $n0.Split(' ')) { if ($n1.Contains($w)) { $p2++ } }
        if ($p2 -gt $pts) { $pts = $p2; $best = $p }
    }
    $id = if ($best) { $best.id } else { $null }
    $cacheProd[$modeloSite] = $id
    if ($best) { Write-Host "  [PRODUTO] '$modeloSite' -> [$($best.id)] $($best.name)" }
    else { Write-Host "  [!] PRODUTO NAO ENCONTRADO: '$modeloSite'" -ForegroundColor Yellow }
    return $id
}

function Get-Variations($paiId) {
    if ($cacheVars.ContainsKey($paiId)) { return $cacheVars[$paiId] }
    $vars = Invoke-RestMethod -Uri "$base/products/$paiId/variations?per_page=100" -Headers $headers
    $cacheVars[$paiId] = $vars
    return $vars
}

$okCount = 0; $failCount = 0
foreach ($c in $calculados) {
    $paiId = Get-ProductId $c.modeloSite
    if (-not $paiId) { $failCount++; continue }

    $vars = Get-Variations $paiId

    $alvo = $null
    foreach ($v in $vars) {
        $todosOk = $true
        foreach ($prop in $c.atributos.PSObject.Properties) {
            $valWanted = NormAttr $prop.Value
            $achou = $false
            foreach ($a in $v.attributes) {
                if ((NormAttr $a.option) -eq $valWanted) { $achou = $true; break }
            }
            if (-not $achou) { $todosOk = $false; break }
        }
        if ($todosOk) { $alvo = $v; break }
    }

    $attrDesc = ($c.atributos.PSObject.Properties | ForEach-Object { $_.Value }) -join '/'
    if (-not $alvo) {
        Write-Host "    [!] variacao nao encontrada: $($c.modeloSite) [$attrDesc]" -ForegroundColor Yellow
        $failCount++
        continue
    }

    $body = @{
        regular_price = $c.preco.ToString()
        stock_status  = 'instock'
        meta_data     = @(@{ key = '_preco_atacado'; value = $c.atacado.ToString() })
    } | ConvertTo-Json -Depth 5

    Invoke-RestMethod -Uri "$base/products/$paiId/variations/$($alvo.id)" -Method Put -Headers $headers -Body $body | Out-Null
    Write-Host "    OK $($c.modeloSite) [$attrDesc] -> varejo `$$($c.preco) | atacado `$$($c.atacado)  (min $($c.menor)/max $($c.maior), $($c.nForn) forn)"
    $okCount++
}

Write-Host "`n=== CONCLUIDO: $okCount atualizados | $failCount falharam ==="
Write-Host "Lembrete: limpar cache do WP Rocket para refletir no site."
