. "$PSScriptRoot/../secrets.ps1"
$ck = $WC_CK
$cs = $WC_CS
$pair = $ck + ':' + $cs
$base64 = [Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes($pair))
$headers = @{Authorization = 'Basic ' + $base64}

Write-Host "=== TERMOS: Apresentacao (ID 32) ==="
$terms = Invoke-RestMethod -Uri 'https://worldtechparaguai.com/wp-json/wc/v3/products/attributes/32/terms?per_page=100' -Headers $headers
$terms | ForEach-Object { Write-Host "ID: $($_.id) | Nome: $($_.name) | Slug: $($_.slug)" }
