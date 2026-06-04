$ck = 'ck_66b73cbc3243de77ed056d4f8c772772d8b9a6bf'
$cs = 'cs_417935cba714f1c4c6c1c3d34be756ccba69019f'
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
