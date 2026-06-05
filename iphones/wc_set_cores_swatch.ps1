# Preenche o swatch de cor (primary_color do plugin Woo Variation Swatches)
# de cada termo do atributo Cor (pa_cor, id 11), com os hex reais dos iPhones.
. "$PSScriptRoot/../secrets.ps1"
$pair = $WC_CK + ':' + $WC_CS
$b64 = [Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes($pair))
$headers = @{ Authorization = 'Basic ' + $b64; 'Content-Type' = 'application/json' }
$base = "$WC_BASE/products/attributes/11/terms"

# id do termo -> hex (cores Apple reais aproximadas)
$cores = @(
    @{ id=80;  nome='Black';         hex='#1C1C1E' }
    @{ id=84;  nome='White';         hex='#F5F5F0' }
    @{ id=83;  nome='Blue';          hex='#AEC6DE' }
    @{ id=127; nome='Dark Blue';     hex='#1F3A5F' }
    @{ id=329; nome='Desert';        hex='#C7B299' }
    @{ id=92;  nome='Gold';          hex='#E8D9B5' }
    @{ id=86;  nome='Graphite';      hex='#54524F' }
    @{ id=81;  nome='Gray';          hex='#8E8E93' }
    @{ id=82;  nome='Green';         hex='#B5C7A8' }
    @{ id=244; nome='Jet Black';     hex='#0A0A0A' }
    @{ id=85;  nome='Lavender';      hex='#D8CCE6' }
    @{ id=78;  nome='Light Blue';    hex='#C5DCE8' }
    @{ id=138; nome='Light Green';   hex='#C9DCC0' }
    @{ id=87;  nome='Light Grey';    hex='#D6D6D6' }
    @{ id=88;  nome='Light Olive';   hex='#B5B58A' }
    @{ id=79;  nome='Light Violet';  hex='#CFC2E0' }
    @{ id=91;  nome='Lilac';         hex='#D4C4E0' }
    @{ id=248; nome='Midnight';      hex='#1F2A3A' }
    @{ id=317; nome='Natural';       hex='#C2BCB2' }
    @{ id=96;  nome='Orange';        hex='#E8893E' }
    @{ id=89;  nome='Pink';          hex='#F2C4CE' }
    @{ id=93;  nome='Purple';        hex='#9B8AB5' }
    @{ id=104; nome='Red';           hex='#BA0C2F' }
    @{ id=240; nome='Rose Gold';     hex='#E0BFB8' }
    @{ id=94;  nome='Silver';        hex='#E3E4E6' }
    @{ id=245; nome='Space Gray';    hex='#4E4E50' }
    @{ id=249; nome='Starlight';     hex='#F3EFE7' }
    @{ id=95;  nome='Yellow';        hex='#F2DE78' }
    @{ id=103; nome='Camoflage';     hex='#78866B' }
    @{ id=406; nome='Cosmic Orange'; hex='#D5532B' }
    @{ id=407; nome='Deep Blue';     hex='#2B3F5C' }
    @{ id=408; nome='Sky Blue';      hex='#BFD4E8' }
    @{ id=409; nome='Sage';          hex='#C3CDB5' }
    @{ id=410; nome='Teal';          hex='#3E6B6E' }
    @{ id=411; nome='Ultramarine';   hex='#4A4FB5' }
)

$ok = 0; $fail = 0
foreach ($c in $cores) {
    $body = @{ woo_variation_swatches = @{ primary_color = $c.hex } } | ConvertTo-Json
    try {
        $r = Invoke-RestMethod -Uri "$base/$($c.id)" -Method Put -Headers $headers -Body $body
        Write-Host ("OK  {0,-16} -> {1}" -f $c.nome, $r.woo_variation_swatches.primary_color)
        $ok++
    } catch {
        Write-Host ("FALHA {0}: {1}" -f $c.nome, $_.Exception.Message)
        $fail++
    }
}
Write-Host "`n=== $ok cores setadas | $fail falhas ==="
Write-Host "Lembrete: limpar cache WP Rocket para aparecer no site."
