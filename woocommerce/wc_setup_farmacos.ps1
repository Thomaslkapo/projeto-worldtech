$ck = 'ck_66b73cbc3243de77ed056d4f8c772772d8b9a6bf'
$cs = 'cs_417935cba714f1c4c6c1c3d34be756ccba69019f'
$pair = $ck + ':' + $cs
$base64 = [Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes($pair))
$headers = @{ Authorization = 'Basic ' + $base64; 'Content-Type' = 'application/json' }
$base = 'https://worldtechparaguai.com/wp-json/wc/v3'

function WC-Post($endpoint, $body) {
    return Invoke-RestMethod -Uri "$base$endpoint" -Method Post -Headers $headers -Body ($body | ConvertTo-Json -Depth 5)
}
function WC-Put($endpoint, $body) {
    return Invoke-RestMethod -Uri "$base$endpoint" -Method Put -Headers $headers -Body ($body | ConvertTo-Json -Depth 5)
}

# ── 1. ATRIBUTO DOSAGEM ──────────────────────────────────────────────────────
Write-Host "`n[1/4] Criando atributo Dosagem..."
$dosAttr = WC-Post '/products/attributes' @{ name = 'Dosagem'; slug = 'dosagem'; type = 'select'; order_by = 'name' }
$dosId = $dosAttr.id
Write-Host "  Dosagem criado: ID $dosId"

$dosagens = @('5mg','10mg','15mg','30mg','40mg','50mg','60mg','70mg','80mg','90mg','100mg','120mg','500mg')
foreach ($d in $dosagens) {
    $t = WC-Post "/products/attributes/$dosId/terms" @{ name = $d }
    Write-Host "  Termo: $($t.name) (ID $($t.id))"
}

# ── 2. CATEGORIAS PAI sob Farmacia (ID 58) ───────────────────────────────────
Write-Host "`n[2/4] Criando categorias pai..."

$catTirzep = WC-Post '/products/categories' @{ name = 'Tirzepatida'; parent = 58; description = 'Produtos à base de Tirzepatida para emagrecimento' }
Write-Host "  Tirzepatida: ID $($catTirzep.id)"

$catReta = WC-Post '/products/categories' @{ name = 'Retatrutide'; parent = 58; description = 'Produtos à base de Retatrutide para emagrecimento' }
Write-Host "  Retatrutide: ID $($catReta.id)"

$catPept = WC-Post '/products/categories' @{ name = 'Peptideos'; parent = 58; description = 'Peptídeos e compostos bioativos' }
Write-Host "  Peptideos: ID $($catPept.id)"

$catBotox = WC-Post '/products/categories' @{ name = 'Botox e Estetica'; parent = 58; description = 'Toxina botulínica e preenchedores faciais' }
Write-Host "  Botox e Estetica: ID $($catBotox.id)"

# ── 3. SUBCATEGORIAS DE MARCA ────────────────────────────────────────────────
Write-Host "`n[3/4] Criando subcategorias de marca..."

# TIRZEPATIDA
Write-Host "  >> Tirzepatida"
$tirzepBrands = @('TIRZEC','LIPOLAND','GLUCONEX','TIRZEDRAL','SLIMEX','SYNEDICA')
foreach ($b in $tirzepBrands) {
    $c = WC-Post '/products/categories' @{ name = $b; parent = $catTirzep.id }
    Write-Host "     $b`: ID $($c.id)"
}
# Reparentar existentes
WC-Put '/products/categories/235' @{ parent = $catTirzep.id } | Out-Null
Write-Host "     LIPOLESS reparentado -> Tirzepatida"
WC-Put '/products/categories/236' @{ parent = $catTirzep.id } | Out-Null
Write-Host "     T.G reparentado -> Tirzepatida"

# RETATRUTIDE
Write-Host "  >> Retatrutide"
$retaBrands = @('NEXUS','USA PEPTIDES','GEN HEALTH','VELTRANE','MEDPLUS','OXYGEN','THERA','BIONEXIS')
foreach ($b in $retaBrands) {
    $c = WC-Post '/products/categories' @{ name = $b; parent = $catReta.id }
    Write-Host "     $b`: ID $($c.id)"
}
# Reparentar existentes
WC-Put '/products/categories/238' @{ parent = $catReta.id; name = 'ALLUVI' } | Out-Null
Write-Host "     ALLUVI reparentado -> Retatrutide"
WC-Put '/products/categories/237' @{ parent = $catReta.id; name = 'SYNEDICA' } | Out-Null
Write-Host "     SYNEDICA reparentado -> Retatrutide"

# PEPTIDEOS
Write-Host "  >> Peptideos"
$peptBrands = @('BIONEXIS','NEOPEPTIDES','MEDPLUS','NEXUS','USA PEPTIDES','CELLGENIC','BIOGENESIS','ALLUVI','GEN HEALTH','DRAGON ELITE','THERA')
foreach ($b in $peptBrands) {
    $c = WC-Post '/products/categories' @{ name = $b; parent = $catPept.id }
    Write-Host "     $b`: ID $($c.id)"
}

# BOTOX
Write-Host "  >> Botox e Estetica"
$botoxBrands = @('DYSPORT','HUTOX','ISRADERM','STUNMEDICAL','QHBIO')
foreach ($b in $botoxBrands) {
    $c = WC-Post '/products/categories' @{ name = $b; parent = $catBotox.id }
    Write-Host "     $b`: ID $($c.id)"
}

# ── 4. RESUMO ────────────────────────────────────────────────────────────────
Write-Host "`n[4/4] Concluido!"
Write-Host "  Atributo Dosagem ID: $dosId"
Write-Host "  Categoria Tirzepatida ID: $($catTirzep.id)"
Write-Host "  Categoria Retatrutide ID: $($catReta.id)"
Write-Host "  Categoria Peptideos ID: $($catPept.id)"
Write-Host "  Categoria Botox e Estetica ID: $($catBotox.id)"
