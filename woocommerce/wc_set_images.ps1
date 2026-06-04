$ck = 'ck_66b73cbc3243de77ed056d4f8c772772d8b9a6bf'
$cs = 'cs_417935cba714f1c4c6c1c3d34be756ccba69019f'
$pair = $ck + ':' + $cs
$base64 = [Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes($pair))
$headers = @{ Authorization = 'Basic ' + $base64; 'Content-Type' = 'application/json' }
$base = 'https://worldtechparaguai.com/wp-json/wc/v3'

function Set-Image($productId, $imgUrl) {
    $body = @{ images = @(@{ src = $imgUrl }) } | ConvertTo-Json -Depth 5
    $result = Invoke-RestMethod -Uri "$base/products/$productId" -Method Put -Headers $headers -Body $body
    Write-Host "  OK: ID $productId -> imagem setada"
}

# ── IMAGENS POR PRODUTO ──────────────────────────────────────────────────────

$IMG_TIRZEC    = 'https://bucket-prod.us-ord-10.linodeobjects.com/site/media/fotos/produtos/thumbs/big/5a01f1c83f4e2a95bac26eeebb9834d7015f2275.webp'
$IMG_LIPOLESS  = 'https://peptideosdobrasil.com.br/cdn/shop/files/lipoless-tirzepatida-15-mg-0-6-ml-md-com-4-doses-eticos-peptideos-623_800x.webp?v=1775170704'
$IMG_LIPOLAND  = 'https://bucket-prod.us-ord-10.linodeobjects.com/site/media/fotos/produtos/thumbs/big/e0b10ed9715913ea38964aa83d0c89c8c1817154.webp'
$IMG_TIRZEDRAL = 'https://bucket-prod.us-ord-10.linodeobjects.com/site/media/fotos/produtos/thumbs/big/c6db7935d4ac81a1a7728f7b669c4b15b43b9eff.webp'
$IMG_SLIMEX    = 'https://bucket-prod.us-ord-10.linodeobjects.com/site/media/fotos/produtos/thumbs/big/c3d722d336356a87dcb4905b609055dc013eb980.webp'
$IMG_SYNEDICA  = 'https://bucket-prod.us-ord-10.linodeobjects.com/site/media/fotos/modelos/retatrutide_synedica_40mg_194335_6542bfb7-90dd-467c-88fe-19d2c17d36c2.webp'
$IMG_BIONEXIS  = 'https://bucket-prod.us-ord-10.linodeobjects.com/site/media/fotos/produtos/thumbs/big/d55cfea54b9dbbad5376deaa333baf6b16f77a8d.webp'
$IMG_MOUNJARO  = 'https://drogariasp.vteximg.com.br/arquivos/ids/1248612-1000-1000/887455---MOUNJARO-5MG-SOLUCAO-INJETAVEL--SUBCUTANEO-4-SERINGA-PREENCHIDA-0-5ML--4-CANETA-APLICADORA-1.jpg?v=638868992706230000'
$IMG_DYSPORT   = 'https://domarket.com.br/wp-content/uploads/2024/12/TOXINA-1.png'

# TIRZEPATIDA
Write-Host "`n[TIRZEPATIDA]"
Set-Image 10465 $IMG_TIRZEC     # TIRZEC
Set-Image 10469 $IMG_LIPOLESS   # LIPOLESS
Set-Image 10472 $IMG_LIPOLAND   # LIPOLAND
Set-Image 10475 $IMG_TIRZEC     # T.G (sem imagem especifica - usa TIRZEC)
Set-Image 10477 $IMG_TIRZEC     # GLUCONEX (sem imagem especifica - usa TIRZEC)
Set-Image 10479 $IMG_TIRZEDRAL  # TIRZEDRAL
Set-Image 10482 $IMG_SLIMEX     # SLIMEX

# RETATRUTIDE
Write-Host "`n[RETATRUTIDE]"
Set-Image 10485 $IMG_SYNEDICA   # NEXUS
Set-Image 10487 $IMG_SYNEDICA   # ALLUVI
Set-Image 10489 $IMG_SYNEDICA   # SYNEDICA Branca
Set-Image 10491 $IMG_SYNEDICA   # SYNEDICA Verde
Set-Image 10493 $IMG_SYNEDICA   # USA PEPTIDES Reta
Set-Image 10495 $IMG_SYNEDICA   # GEN HEALTH Reta
Set-Image 10498 $IMG_SYNEDICA   # VELTRANE Reta
Set-Image 10502 $IMG_SYNEDICA   # MEDPLUS Reta
Set-Image 10504 $IMG_SYNEDICA   # OXYGEN RETAGEN
Set-Image 10506 $IMG_SYNEDICA   # THERA Reta
Set-Image 10508 $IMG_SYNEDICA   # BIONEXIS Reta

# MOUNJARO
Write-Host "`n[MOUNJARO]"
Set-Image 10510 $IMG_MOUNJARO

# BOTOX E ESTETICA
Write-Host "`n[BOTOX E ESTETICA]"
Set-Image 10513 $IMG_DYSPORT    # DYSPORT
Set-Image 10514 $IMG_DYSPORT    # HUTOX
Set-Image 10515 $IMG_DYSPORT    # ISRADERM 100IU
Set-Image 10516 $IMG_DYSPORT    # ISRADERM 150IU
Set-Image 10517 $IMG_DYSPORT    # STUNMEDICAL Hard
Set-Image 10518 $IMG_DYSPORT    # STUNMEDICAL Soft
Set-Image 10519 $IMG_DYSPORT    # QHBIO LINE DERM

# PEPTIDEOS BIONEXIS
Write-Host "`n[PEPTIDEOS - BIONEXIS]"
Set-Image 10520 $IMG_BIONEXIS   # GHK-CU 50mg
Set-Image 10523 $IMG_BIONEXIS   # GHK-CU 100mg
Set-Image 10526 $IMG_BIONEXIS   # GLOW
Set-Image 10529 $IMG_BIONEXIS   # KLOW
Set-Image 10532 $IMG_BIONEXIS   # SLU-PP-332
Set-Image 10535 $IMG_BIONEXIS   # BPC-157
Set-Image 10537 $IMG_BIONEXIS   # AOD-9604
Set-Image 10539 $IMG_BIONEXIS   # TB-500
Set-Image 10541 $IMG_BIONEXIS   # SS-31
Set-Image 10543 $IMG_BIONEXIS   # TESAMORELIN
Set-Image 10545 $IMG_BIONEXIS   # MOTS-C
Set-Image 10547 $IMG_BIONEXIS   # PT-141
Set-Image 10549 $IMG_BIONEXIS   # EPITHALON

# PEPTIDEOS NEOPEPTIDES
Write-Host "`n[PEPTIDEOS - NEOPEPTIDES]"
Set-Image 10551 $IMG_BIONEXIS   # FRAG 176-191
Set-Image 10553 $IMG_BIONEXIS   # GLOW
Set-Image 10555 $IMG_BIONEXIS   # SLUPP-332
Set-Image 10557 $IMG_BIONEXIS   # TB500+BPC157

# PEPTIDEOS MEDPLUS
Write-Host "`n[PEPTIDEOS - MEDPLUS]"
Set-Image 10559 $IMG_BIONEXIS   # AOD-9604
Set-Image 10561 $IMG_BIONEXIS   # BPC-157
Set-Image 10563 $IMG_BIONEXIS   # KPV
Set-Image 10565 $IMG_BIONEXIS   # MOTS-C
Set-Image 10567 $IMG_BIONEXIS   # SS-31
Set-Image 10569 $IMG_BIONEXIS   # TB-500
Set-Image 10571 $IMG_BIONEXIS   # SERMORELIM

# PEPTIDEOS OUTROS
Write-Host "`n[PEPTIDEOS - OUTROS]"
Set-Image 10573 $IMG_BIONEXIS   # NEXUS GHK-CU 50mg
Set-Image 10575 $IMG_BIONEXIS   # NEXUS GHK-CU 100mg
Set-Image 10577 $IMG_BIONEXIS   # NEXUS GLOW
Set-Image 10579 $IMG_BIONEXIS   # NEXUS BPC-157
Set-Image 10581 $IMG_BIONEXIS   # NEXUS TB-500
Set-Image 10583 $IMG_BIONEXIS   # NEXUS KLOW
Set-Image 10585 $IMG_BIONEXIS   # USA PEPTIDES GHK-CU
Set-Image 10587 $IMG_BIONEXIS   # USA PEPTIDES CJC+IPA
Set-Image 10589 $IMG_BIONEXIS   # USA PEPTIDES EPITHALON
Set-Image 10591 $IMG_BIONEXIS   # USA PEPTIDES MOTS-C
Set-Image 10593 $IMG_BIONEXIS   # USA PEPTIDES NAD+
Set-Image 10595 $IMG_BIONEXIS   # USA PEPTIDES SS-31
Set-Image 10597 $IMG_BIONEXIS   # USA PEPTIDES SLUPP-332
Set-Image 10599 $IMG_BIONEXIS   # ALLUVI GLOW GHK-CU
Set-Image 10601 $IMG_BIONEXIS   # ALLUVI BPC+TB500
Set-Image 10603 $IMG_BIONEXIS   # GEN HEALTH GHK-CU 50mg
Set-Image 10605 $IMG_BIONEXIS   # GEN HEALTH GHK-CU 100mg
Set-Image 10607 $IMG_BIONEXIS   # CELLGENIC GHK-CU 50mg
Set-Image 10609 $IMG_BIONEXIS   # CELLGENIC GHK-CU 100mg
Set-Image 10611 $IMG_BIONEXIS   # CELLGENIC GLOW
Set-Image 10613 $IMG_BIONEXIS   # CELLGENIC KLOW
Set-Image 10615 $IMG_BIONEXIS   # BIOGENESIS GHK-CU
Set-Image 10617 $IMG_BIONEXIS   # BIOGENESIS SLUPP-332
Set-Image 10619 $IMG_BIONEXIS   # BIOGENESIS GLOW
Set-Image 10621 $IMG_BIONEXIS   # BIOGENESIS PT-141
Set-Image 10623 $IMG_BIONEXIS   # BIOGENESIS TESAMORELIN
Set-Image 10625 $IMG_BIONEXIS   # DRAGON ELITE GHK-CU
Set-Image 10627 $IMG_BIONEXIS   # DRAGON ELITE SLUPP-332

Write-Host "`n=== Todas as imagens setadas! ==="
