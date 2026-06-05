. "$PSScriptRoot/../secrets.ps1"
$ck = $WC_CK
$cs = $WC_CS
$pair = $ck + ':' + $cs
$base64 = [Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes($pair))
$headers = @{Authorization = 'Basic ' + $base64}

Write-Host "=== ATRIBUTOS ==="
$attrs = Invoke-RestMethod -Uri 'https://worldtechparaguai.com/wp-json/wc/v3/products/attributes?per_page=100' -Headers $headers
$attrs | ForEach-Object { Write-Host "ID: $($_.id) | Nome: $($_.name) | Slug: $($_.slug)" }

Write-Host ""
Write-Host "=== CATEGORIAS ==="
$cats = Invoke-RestMethod -Uri 'https://worldtechparaguai.com/wp-json/wc/v3/products/categories?per_page=100' -Headers $headers
$cats | ForEach-Object { Write-Host "ID: $($_.id) | Nome: $($_.name) | Pai: $($_.parent) | Count: $($_.count)" }
