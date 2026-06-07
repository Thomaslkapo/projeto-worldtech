# Corrige o encoding do button_text e external_url dos produtos externos.
# Constroi os acentos por code point (à prova de encoding do terminal).
. "$PSScriptRoot/../secrets.ps1"
$pair = $WC_CK + ':' + $WC_CS
$b64 = [Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes($pair))
$headers = @{ Authorization = 'Basic ' + $b64; 'Content-Type' = 'application/json; charset=utf-8' }
$base = $WC_BASE
$numero = '595975682071'

$cc = [char]0x00E7  # c-cedilha
$aa = [char]0x00E1  # a-agudo
$btn = 'Consultar pre' + $cc + 'o'   # "Consultar preço"

# buscar todos os externos
$externos = @()
for ($pg=1; $pg -le 10; $pg++) {
    $p = Invoke-RestMethod -Uri "$base/products?type=external&per_page=100&page=$pg" -Headers $headers
    if (-not $p -or $p.Count -eq 0) { break }
    $externos += $p
}
Write-Host "Externos encontrados: $($externos.Count)"

$ok = 0
foreach ($p in $externos) {
    $nome = $p.name
    $msg = 'Ol' + $aa + '! Quero consultar o pre' + $cc + 'o deste produto: ' + $nome + '. Quanto est' + $aa + ' custando?'
    $url = 'https://wa.me/' + $numero + '?text=' + [uri]::EscapeDataString($msg)
    $body = @{ button_text = $btn; external_url = $url } | ConvertTo-Json
    $bytes = [Text.Encoding]::UTF8.GetBytes($body)
    try {
        $r = Invoke-RestMethod -Uri "$base/products/$($p.id)" -Method Put -Headers $headers -Body $bytes
        $ok++
    } catch {
        Write-Host "  ERRO [$($p.id)] $($p.name): $($_.Exception.Message)"
    }
}
Write-Host "=== $ok botoes corrigidos para 'Consultar preço' ==="
Write-Host "Limpar cache WP Rocket."
