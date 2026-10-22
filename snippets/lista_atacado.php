<?php
/**
 * Shortcode: [lista_atacado] + endpoints de taxa do dólar + banner no topo
 */

// ── Taxa do Dólar — endpoint REST ────────────────────────────────────────────
add_action('rest_api_init', function () {
    $secret = 'wt_bridge_2026_secret_xK9mP3nQ';
    register_rest_route('worldtech/v1', '/taxa', [
        [
            'methods'             => 'GET',
            'permission_callback' => '__return_true',
            'callback'            => function ($r) use ($secret) {
                if ($r->get_param('key') !== $secret)
                    return new WP_Error('forbidden', '', ['status' => 403]);
                return ['taxa' => get_option('wt_taxa_dolar',''), 'atualizado' => get_option('wt_taxa_dolar_data','')];
            },
        ],
        [
            'methods'             => 'POST',
            'permission_callback' => '__return_true',
            'callback'            => function ($r) use ($secret) {
                if ($r->get_param('key') !== $secret)
                    return new WP_Error('forbidden', '', ['status' => 403]);
                $taxa = str_replace(',', '.', sanitize_text_field($r->get_param('taxa') ?? ''));
                if (!preg_match('/^\d+\.\d{1,4}$/', $taxa))
                    return new WP_Error('invalid', 'Taxa invalida: '.$taxa, ['status' => 400]);
                $br = number_format((float)$taxa, 2, ',', '.');
                update_option('wt_taxa_dolar', $br, false);
                update_option('wt_taxa_dolar_data', wp_date('d/m H:i'), false);
                return ['success' => true, 'taxa' => $br];
            },
        ],
    ]);
});

// ── Banner taxa no topo ──────────────────────────────────────────────────────
add_action('wp_body_open', function () {
    $taxa = get_option('wt_taxa_dolar', '');
    $data = get_option('wt_taxa_dolar_data', '');
    if (!$taxa) return;
    echo '<div id="wt-taxa-banner">'
       . '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" style="width:15px;height:15px;flex-shrink:0;opacity:.85"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>'
       . '<span style="color:rgba(255,255,255,.75);font-weight:500">Dolar hoje</span>'
       . '<strong style="font-size:15.5px;font-weight:800;letter-spacing:.2px">R$&nbsp;' . esc_html($taxa) . '</strong>'
       . ($data ? '<span style="font-size:11.5px;color:rgba(255,255,255,.55)">&middot;&nbsp;' . esc_html($data) . '</span>' : '')
       . '</div>';
});
add_action('wp_head', function () {
    if (!get_option('wt_taxa_dolar', '')) return;
    echo '<style>#wt-taxa-banner{position:fixed;top:0;left:0;right:0;z-index:999999;background:linear-gradient(90deg,#0843b8 0%,#0f5fd4 55%,#1870e8 100%);color:#fff;display:flex;align-items:center;justify-content:center;gap:9px;padding:0 20px;height:38px;box-sizing:border-box;font-family:system-ui,-apple-system,sans-serif;font-size:13.5px;box-shadow:0 2px 10px rgba(0,0,0,.3)}html{margin-top:38px!important}@media(max-width:600px){#wt-taxa-banner{height:34px;font-size:12.5px}html{margin-top:34px!important}#wt-taxa-banner span:last-child{display:none}}</style>';
});

// ── Shortcode lista de atacado ───────────────────────────────────────────────
add_shortcode('lista_atacado', function () {
    if (!class_exists('WooCommerce')) return '';
    $taxa_dolar = (float) str_replace(',', '.', get_option('wt_taxa_dolar', '0'));

    ob_start();

    // ── Buscar produtos ────────────────────────────────────────────────────
    $produtos = wc_get_products([
        'status'     => 'publish',
        'type'       => ['variable'],
        'limit'      => -1,
        'orderby'    => 'title',
        'order'      => 'ASC',
    ]);

    $iphones  = [];
    $farmacos = [];

    foreach ($produtos as $produto) {
        $nome     = $produto->get_name();
        $eh_iphone = stripos($nome, 'iphone') !== false;

        $variacoes_por_storage = [];
        $variacoes_lista       = [];

        foreach ($produto->get_children() as $var_id) {
            $var = wc_get_product($var_id);
            if (!$var || $var->get_status() !== 'publish') continue;
            if ($var->get_stock_status() !== 'instock')    continue;

            $atacado = get_post_meta($var_id, '_preco_atacado', true);
            if (!$atacado || floatval($atacado) <= 0)      continue;

            if ($eh_iphone) {
                $attrs = $var->get_attributes();
                // Suporta atributos globais (pa_armazenamento) e customizados (armazenamento)
                $storage_val = $attrs['pa_armazenamento'] ?? ($attrs['armazenamento'] ?? 'Único');
                $cor_val     = $attrs['pa_cor']           ?? ($attrs['cor']           ?? '');
                $origem_val  = $attrs['pa_origem']        ?? ($attrs['origem']        ?? '');

                $variacoes_por_storage[$storage_val][] = [
                    'cor'     => $cor_val,
                    'origem'  => $origem_val,
                    'atacado' => floatval($atacado),
                ];
            } else {
                $variacoes_lista[] = [
                    'nome'    => $var->get_name(),
                    'atacado' => floatval($atacado),
                ];
            }
        }

        if ($eh_iphone && !empty($variacoes_por_storage)) {
            uksort($variacoes_por_storage, function ($a, $b) {
                $ordem = ['256GB' => 1, '512GB' => 2, '1TB' => 3, '2TB' => 4];
                return ($ordem[$a] ?? 99) - ($ordem[$b] ?? 99);
            });
            $iphones[] = ['nome' => $nome, 'storages' => $variacoes_por_storage];
        }

        if (!$eh_iphone && !empty($variacoes_lista)) {
            usort($variacoes_lista, fn($a, $b) => $a['atacado'] <=> $b['atacado']);
            $categoria = '';
            $cats = wc_get_product_category_list($produto->get_id(), ',', '', '');
            if ($cats) $categoria = wp_strip_all_tags($cats);
            $farmacos[$categoria][] = [
                'nome'    => $nome,
                'atacado' => $variacoes_lista[0]['atacado'],
            ];
        }
    }

    // Também buscar produtos simples com _preco_atacado
    $simples = wc_get_products([
        'status'  => 'publish',
        'type'    => ['simple'],
        'limit'   => -1,
        'orderby' => 'title',
        'order'   => 'ASC',
    ]);

    foreach ($simples as $produto) {
        if (stripos($produto->get_name(), 'iphone') !== false) continue;
        if ($produto->get_stock_status() !== 'instock')        continue;
        $atacado = get_post_meta($produto->get_id(), '_preco_atacado', true);
        if (!$atacado || floatval($atacado) <= 0)              continue;

        $categoria = '';
        $cats = wc_get_product_category_list($produto->get_id(), ',', '', '');
        if ($cats) $categoria = wp_strip_all_tags($cats);
        $farmacos[$categoria][] = [
            'nome'    => $produto->get_name(),
            'atacado' => floatval($atacado),
        ];
    }

    foreach ($farmacos as &$grupo) {
        usort($grupo, fn($a, $b) => strcmp($a['nome'], $b['nome']));
    }
    ksort($farmacos);

    $total_iphones  = count($iphones);
    $total_farmacos = array_sum(array_map('count', $farmacos));
    $data_atual     = date_i18n('d \d\e F \d\e Y');

    function wt_cor_dot(string $cor): string {
        static $map = [
            // ── iPhone 16 (cores exatas Apple) ──
            'black'            => '#3A3A3C',
            'white'            => '#F2EDE6',
            'pink'             => '#EEB4BC',
            'teal'             => '#3D8A87',
            'ultramarine'      => '#3A50C4',
            // ── iPhone 17 ──
            'blue'             => '#4A8EC2',
            'sky blue'         => '#5AABD6',
            'deep blue'        => '#1B2F5A',
            'cosmic orange'    => '#B85722',
            'silver'           => '#BEC3CC',
            // ── Titanium (Pro / Pro Max) ──
            'black titanium'   => '#2B2825',
            'white titanium'   => '#E5DED0',
            'natural titanium' => '#C4B8A0',
            'desert titanium'  => '#C0965E',
            'blue titanium'    => '#5B7DA3',
            // ── Aliases ──
            'natural'          => '#C4B8A0',
            'desert'           => '#C0965E',
            'orange'           => '#B85722',
            'starlight'        => '#F5F0E8',
            'midnight'         => '#1C2340',
            'yellow'           => '#F0D060',
            'green'            => '#5BA06E',
            'purple'           => '#8B5CF6',
            'red'              => '#DC2626',
        ];
        $key = strtolower(str_replace('-', ' ', trim($cor)));
        $hex = $map[$key] ?? '#aaaaaa';
        $claras = ['#F2EDE6','#F5F0E8','#E5DED0','#C4B8A0'];
        $extra  = in_array(strtoupper($hex), array_map('strtoupper', $claras)) ? 'box-shadow:0 0 0 1.5px #b0b0b0;' : '';
        return "<span class=\"wt-dot\" style=\"background:{$hex};{$extra}\"></span>";
    }

    function wt_preco(float $v): string {
        return '$' . number_format($v, 0, ',', '.');
    }
    function wt_preco_brl(float $usd, float $taxa): string {
        if ($taxa <= 0) return '';
        return 'R$&nbsp;' . number_format($usd * $taxa, 0, ',', '.');
    }

    ?>
    <div class="wt-atacado" id="wt-atacado">

      <div class="wt-hd">
        <div class="wt-eyebrow">WorldTech Paraguay · Atacado</div>
        <h2 class="wt-titulo">Preços para Revendedores</h2>
        <p class="wt-sub">Somente produtos em estoque · Valores em dólares americanos</p>
      </div>

      <div class="wt-aviso">
        <svg class="wt-aviso-ico" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10A8 8 0 1 1 2 10a8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/></svg>
        Preço de atacado válido somente para pedidos com <strong>mínimo 5 unidades por item</strong>. Selecione os produtos, ajuste as quantidades e envie via WhatsApp.
      </div>

      <div class="wt-tabs" role="tablist">
        <button class="wt-tab wt-tab--active" role="tab"
                onclick="wtTab('iphones',this)" aria-selected="true">
          📱 iPhones
          <?php if ($total_iphones): ?>
            <span class="wt-badge"><?php echo $total_iphones; ?></span>
          <?php endif; ?>
        </button>
        <button class="wt-tab" role="tab"
                onclick="wtTab('farmacos',this)" aria-selected="false">
          💊 Fármacos
          <?php if ($total_farmacos): ?>
            <span class="wt-badge"><?php echo $total_farmacos; ?></span>
          <?php endif; ?>
        </button>
      </div>

      <?php /* ── iPHONES ── */ ?>
      <div id="wt-iphones" class="wt-panel wt-panel--active" role="tabpanel">
        <?php if (empty($iphones)): ?>
          <p class="wt-vazio">Nenhum iPhone disponível no momento.</p>
        <?php else: ?>
          <?php foreach ($iphones as $iphone): ?>
            <div class="wt-model">
              <div class="wt-model-hd">
                <span class="wt-model-nome"><?php echo esc_html($iphone['nome']); ?></span>
                <span class="wt-model-tag">Em estoque</span>
              </div>
              <?php foreach ($iphone['storages'] as $storage => $cores): ?>
                <div class="wt-stor">
                  <span class="wt-stor-lbl"><?php echo esc_html($storage); ?></span>
                  <div class="wt-chips">
                    <?php foreach ($cores as $c):
                        $label_parts = array_filter([$c['cor'], $c['origem']]);
                        $label       = implode(' · ', $label_parts);
                        $item_nome   = esc_attr($iphone['nome'] . ' · ' . $storage . ($label ? ' · ' . $label : ''));
                        $item_preco  = esc_attr(wt_preco($c['atacado']));
                    ?>
                      <div class="wt-chip wt-selecionavel"
                           data-nome="<?php echo $item_nome; ?>"
                           data-preco="<?php echo $item_preco; ?>"
                           data-tipo="iphone"
                           onclick="wtToggle(this)"
                           title="Clique para selecionar">
                        <span class="wt-check">✓</span>
                        <?php echo wt_cor_dot($c['cor']); ?>
                        <span class="wt-chip-cor"><?php echo esc_html($label ?: $storage); ?></span>
                        <span class="wt-chip-sep">·</span>
                        <span class="wt-chip-preco-wrap">
                          <span class="wt-chip-preco"><?php echo wt_preco($c['atacado']); ?></span>
                          <?php if ($taxa_dolar > 0): ?>
                          <span class="wt-chip-brl"><?php echo wt_preco_brl($c['atacado'], $taxa_dolar); ?></span>
                          <?php endif; ?>
                        </span>
                        <span class="wt-qty-ctrl" onclick="event.stopPropagation()">
                          <button class="wt-qty-btn" onclick="wtAlterarQtd(this.closest('.wt-chip'),-1)">−</button>
                          <input class="wt-qty-input" type="number" min="1" step="1" value="5"
                                 oninput="wtSetQtdInput(this.closest('.wt-chip'),this)"
                                 onclick="this.select()">
                          <button class="wt-qty-btn" onclick="wtAlterarQtd(this.closest('.wt-chip'),1)">+</button>
                        </span>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <?php /* ── FÁRMACOS ── */ ?>
      <div id="wt-farmacos" class="wt-panel" role="tabpanel">
        <?php if (empty($farmacos)): ?>
          <p class="wt-vazio">Nenhum fármaco disponível no momento.</p>
        <?php else: ?>
          <?php foreach ($farmacos as $cat => $itens): ?>
            <div class="wt-cat">
              <div class="wt-cat-lbl"><?php echo $cat ? esc_html($cat) : 'Outros'; ?></div>
              <div class="wt-tabela">
                <?php foreach ($itens as $it): ?>
                  <div class="wt-row wt-selecionavel"
                       data-nome="<?php echo esc_attr($it['nome']); ?>"
                       data-preco="<?php echo esc_attr(wt_preco($it['atacado'])); ?>"
                       data-tipo="farmaco"
                       onclick="wtToggle(this)"
                       title="Clique para selecionar">
                    <span class="wt-check">✓</span>
                    <span class="wt-row-nome"><?php echo esc_html($it['nome']); ?></span>
                    <span class="wt-row-preco-wrap">
                      <span class="wt-row-preco"><?php echo wt_preco($it['atacado']); ?></span>
                      <?php if ($taxa_dolar > 0): ?>
                      <span class="wt-row-brl"><?php echo wt_preco_brl($it['atacado'], $taxa_dolar); ?></span>
                      <?php endif; ?>
                    </span>
                    <span class="wt-qty-ctrl wt-qty-ctrl--row" onclick="event.stopPropagation()">
                      <button class="wt-qty-btn" onclick="wtAlterarQtd(this.closest('.wt-row'),-1)">−</button>
                      <input class="wt-qty-input" type="number" min="1" step="1" value="5"
                             oninput="wtSetQtdInput(this.closest('.wt-row'),this)"
                             onclick="this.select()">
                      <button class="wt-qty-btn" onclick="wtAlterarQtd(this.closest('.wt-row'),1)">+</button>
                    </span>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="wt-footer">Preços atualizados automaticamente · <?php echo esc_html($data_atual); ?></div>
    </div>

    <!-- ── Painel de pedido (drawer) ── -->
    <div class="wt-drawer-overlay" id="wt-drawer-overlay" onclick="wtFecharDrawer()"></div>
    <div class="wt-drawer" id="wt-drawer">
      <div class="wt-drawer-hd">
        <span class="wt-drawer-titulo">Meu Pedido</span>
        <button class="wt-drawer-fechar" onclick="wtFecharDrawer()" aria-label="Fechar">✕</button>
      </div>
      <div class="wt-drawer-lista" id="wt-drawer-lista">
        <!-- preenchido pelo JS -->
      </div>
      <div class="wt-drawer-footer">
        <div class="wt-drawer-hint" id="wt-drawer-hint"></div>
        <button class="wt-float-btn wt-drawer-enviar" id="wt-drawer-enviar" onclick="wtEnviarWhatsApp()">
          <svg viewBox="0 0 24 24" fill="currentColor" class="wt-wa-ico"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.117.554 4.103 1.523 5.828L.057 23.886a.5.5 0 0 0 .611.612l6.102-1.457A11.945 11.945 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.806 9.806 0 0 1-4.99-1.364l-.357-.213-3.705.885.908-3.638-.233-.374A9.818 9.818 0 0 1 2.182 12C2.182 6.57 6.57 2.182 12 2.182S21.818 6.57 21.818 12 17.43 21.818 12 21.818z"/></svg>
          Solicitar via WhatsApp
        </button>
      </div>
    </div>

    <!-- ── Barra flutuante de pedido ── -->
    <div class="wt-float" id="wt-float">
      <div class="wt-float-info">
        <span class="wt-float-count" id="wt-float-count">0</span>
        <span class="wt-float-label">unid.</span>
        <span class="wt-float-total" id="wt-float-total"></span>
        <span class="wt-float-hint" id="wt-float-hint"></span>
      </div>
      <button class="wt-float-ver" onclick="wtAbrirDrawer()">Ver pedido</button>
      <button class="wt-float-btn" id="wt-float-btn" onclick="wtEnviarWhatsApp()">
        <svg viewBox="0 0 24 24" fill="currentColor" class="wt-wa-ico"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.117.554 4.103 1.523 5.828L.057 23.886a.5.5 0 0 0 .611.612l6.102-1.457A11.945 11.945 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.806 9.806 0 0 1-4.99-1.364l-.357-.213-3.705.885.908-3.638-.233-.374A9.818 9.818 0 0 1 2.182 12C2.182 6.57 6.57 2.182 12 2.182S21.818 6.57 21.818 12 17.43 21.818 12 21.818z"/></svg>
        Enviar
      </button>
    </div>

    <style>
    .wt-atacado{font-family:system-ui,-apple-system,'Segoe UI',sans-serif;max-width:780px;margin:0 auto;padding:8px 0 120px;color:#0d1424;-webkit-font-smoothing:antialiased}
    .wt-hd{margin-bottom:16px}
    .wt-eyebrow{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1.1px;color:#0f5fd4;margin-bottom:6px}
    .wt-titulo{font-size:22px;font-weight:800;letter-spacing:-0.4px;margin:0 0 4px}
    .wt-sub{font-size:13px;color:#5a6782;margin:0}

    /* Aviso mínimo */
    .wt-aviso{display:flex;align-items:flex-start;gap:9px;background:#fffbeb;border:1px solid #f0c040;border-radius:7px;padding:11px 14px;margin-bottom:20px;font-size:13px;color:#6b4f0a;line-height:1.5}
    .wt-aviso strong{color:#3d2c00}
    .wt-aviso-ico{width:16px;height:16px;flex-shrink:0;margin-top:1px;color:#c0860a}

    /* Tabs */
    .wt-tabs{display:flex;gap:0;border-bottom:2px solid #d4dae6;margin-bottom:24px}
    .wt-tab{background:none;border:none;cursor:pointer;padding:10px 20px 10px 0;font-size:14px;font-weight:600;color:#5a6782;border-bottom:2px solid transparent;margin-bottom:-2px;display:inline-flex;align-items:center;gap:7px;transition:color .15s,border-color .15s}
    .wt-tab:hover:not(.wt-tab--active){color:#0d1424}
    .wt-tab--active{color:#0f5fd4;border-bottom-color:#0f5fd4}
    .wt-badge{font-size:11px;font-weight:700;background:#e7eef9;color:#0f5fd4;padding:1px 6px;border-radius:10px}
    .wt-panel{display:none}.wt-panel--active{display:block}
    .wt-vazio{color:#5a6782;font-size:14px;padding:20px 0}

    /* Selecionável */
    .wt-selecionavel{cursor:pointer;user-select:none;transition:background .12s,border-color .12s,box-shadow .12s}
    .wt-check{display:none;font-size:11px;font-weight:800;color:#fff;background:#0f5fd4;width:16px;height:16px;border-radius:50%;align-items:center;justify-content:center;flex-shrink:0;line-height:1}

    /* iPhones */
    .wt-model{background:#fff;border:1px solid #d4dae6;border-left:3px solid #0f5fd4;border-radius:0 6px 6px 0;margin-bottom:14px;overflow:hidden}
    .wt-model-hd{display:flex;align-items:baseline;justify-content:space-between;padding:13px 18px 11px;border-bottom:1px solid #d4dae6}
    .wt-model-nome{font-size:17px;font-weight:800;letter-spacing:-0.3px}
    .wt-model-tag{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.6px;color:#5a6782}
    .wt-stor{display:flex;align-items:center;padding:10px 18px;gap:14px;border-bottom:1px solid #d4dae6}
    .wt-stor:last-child{border-bottom:none}
    .wt-stor-lbl{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:#5a6782;min-width:48px;flex-shrink:0}
    .wt-chips{display:flex;flex-wrap:wrap;gap:7px}
    .wt-chip{display:inline-flex;align-items:center;gap:5px;background:#f1f4f9;border:1px solid #c8d1e0;border-radius:4px;padding:4px 9px 4px 6px;font-size:13px;white-space:nowrap}
    .wt-chip:hover{border-color:#0f5fd4;background:#eef3fb}
    .wt-chip.wt-selecionado{background:#dbeafe;border-color:#0f5fd4;box-shadow:0 0 0 2px #bfdbfe}
    .wt-chip.wt-selecionado .wt-check{display:inline-flex}
    .wt-dot{width:13px;height:13px;border-radius:50%;display:inline-block;flex-shrink:0}
    .wt-chip-cor{color:#0d1424;font-size:12px}
    .wt-chip-sep{color:#c8d1e0;font-size:11px}
    .wt-chip-preco-wrap{display:inline-flex;flex-direction:column;align-items:flex-end;line-height:1.15}
    .wt-chip-preco{font-size:13px;font-weight:700;color:#0a7a4b;font-variant-numeric:tabular-nums}
    .wt-chip-brl{font-size:10px;font-weight:600;color:#5a6782;font-variant-numeric:tabular-nums}

    /* Fármacos */
    .wt-cat{margin-bottom:20px}
    .wt-cat-lbl{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#5a6782;margin-bottom:8px}
    .wt-tabela{background:#fff;border:1px solid #d4dae6;border-radius:6px;overflow:hidden}
    .wt-row{display:flex;align-items:center;padding:9px 16px;gap:10px;border-bottom:1px solid #d4dae6}
    .wt-row:last-child{border-bottom:none}
    .wt-row:nth-child(even){background:#f7f9fc}
    .wt-row:hover{background:#eef3fb}
    .wt-row.wt-selecionado{background:#dbeafe!important;border-left:3px solid #0f5fd4}
    .wt-row.wt-selecionado .wt-check{display:inline-flex}
    .wt-row-nome{flex:1;font-size:13.5px;color:#0d1424}
    .wt-row-preco-wrap{display:flex;flex-direction:column;align-items:flex-end;line-height:1.2}
    .wt-row-preco{font-size:14px;font-weight:700;color:#0a7a4b;font-variant-numeric:tabular-nums;white-space:nowrap}
    .wt-row-brl{font-size:11px;font-weight:600;color:#5a6782;font-variant-numeric:tabular-nums;white-space:nowrap}

    .wt-footer{margin-top:32px;font-size:12px;color:#5a6782;text-align:right}

    /* Barra flutuante */
    .wt-float{position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(100px);z-index:9999;background:#0d1424;color:#fff;border-radius:14px;padding:12px 16px 12px 20px;display:flex;align-items:center;gap:16px;box-shadow:0 8px 32px rgba(0,0,0,.35),0 2px 8px rgba(0,0,0,.2);transition:transform .3s cubic-bezier(.34,1.56,.64,1),opacity .25s;opacity:0;pointer-events:none;white-space:nowrap;min-width:280px}
    .wt-float.wt-float--visivel{transform:translateX(-50%) translateY(0);opacity:1;pointer-events:auto}
    .wt-float-info{display:flex;align-items:baseline;gap:5px;flex:1}
    .wt-float-count{font-size:24px;font-weight:800;line-height:1;color:#fff;font-variant-numeric:tabular-nums}
    .wt-float-label{font-size:13px;color:#a0aec0}
    .wt-float-total{font-size:14px;font-weight:700;color:#fff;margin-left:6px;font-variant-numeric:tabular-nums}
    .wt-float-hint{font-size:12px;color:#f0c040;margin-left:4px}
    .wt-float-btn{display:inline-flex;align-items:center;gap:7px;background:#25d366;color:#fff;border:none;border-radius:9px;padding:9px 16px;font-size:13.5px;font-weight:700;cursor:pointer;transition:background .15s,opacity .15s;white-space:nowrap}
    .wt-float-btn:hover{background:#1ebe5d}
    .wt-float-btn--inativo{background:#4a5568;cursor:not-allowed}
    .wt-float-btn--inativo:hover{background:#4a5568}
    .wt-wa-ico{width:17px;height:17px;flex-shrink:0}

    /* Drawer */
    .wt-drawer-overlay{position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:10000;opacity:0;pointer-events:none;transition:opacity .25s}
    .wt-drawer-overlay.wt-aberto{opacity:1;pointer-events:auto}
    .wt-drawer{position:fixed;top:0;right:0;bottom:0;width:340px;max-width:100vw;background:#fff;z-index:10001;display:flex;flex-direction:column;box-shadow:-4px 0 32px rgba(0,0,0,.18);transform:translateX(100%);transition:transform .3s cubic-bezier(.4,0,.2,1)}
    .wt-drawer.wt-aberto{transform:translateX(0)}
    .wt-drawer-hd{display:flex;align-items:center;justify-content:space-between;padding:18px 20px;border-bottom:1px solid #d4dae6;flex-shrink:0}
    .wt-drawer-titulo{font-size:16px;font-weight:800;color:#0d1424}
    .wt-drawer-fechar{background:none;border:none;cursor:pointer;font-size:18px;color:#5a6782;padding:2px 6px;line-height:1;border-radius:4px}
    .wt-drawer-fechar:hover{background:#f1f4f9;color:#0d1424}
    .wt-drawer-lista{flex:1;overflow-y:auto;padding:12px 20px}
    .wt-drawer-vazio{color:#5a6782;font-size:13px;text-align:center;margin-top:40px}
    .wt-drawer-grupo-lbl{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#5a6782;margin:12px 0 6px}
    .wt-drawer-item{display:flex;align-items:center;gap:10px;padding:8px 10px;background:#f7f9fc;border:1px solid #d4dae6;border-radius:6px;margin-bottom:6px}
    .wt-drawer-item-nome{flex:1;font-size:13px;color:#0d1424;line-height:1.35}
    .wt-drawer-item-preco{font-size:13px;font-weight:700;color:#0a7a4b;white-space:nowrap;font-variant-numeric:tabular-nums}
    .wt-drawer-remover{background:none;border:none;cursor:pointer;color:#9aa3b5;font-size:16px;line-height:1;padding:2px 4px;border-radius:3px;flex-shrink:0}
    .wt-drawer-remover:hover{background:#fee2e2;color:#dc2626}
    .wt-drawer-item-sub{font-size:12px;font-weight:700;color:#0a7a4b;white-space:nowrap;font-variant-numeric:tabular-nums}
    .wt-drawer-item-brl{font-size:10px;font-weight:600;color:#5a6782;white-space:nowrap;font-variant-numeric:tabular-nums}
    .wt-drawer-item-unit{font-size:10px;color:#9aa3b5;white-space:nowrap;display:block;text-align:right}
    .wt-drawer-total{display:flex;justify-content:space-between;align-items:center;padding:10px 10px 4px;border-top:2px solid #d4dae6;margin-top:6px;font-weight:800;font-size:14px;color:#0d1424}
    .wt-drawer-total-val{font-size:16px;color:#0a7a4b;font-variant-numeric:tabular-nums}

    /* Controles de quantidade */
    .wt-qty-ctrl{display:inline-flex;align-items:center;gap:2px;margin-left:5px;border-left:1px solid #c8d1e0;padding-left:5px}
    .wt-chip.wt-selecionado .wt-qty-ctrl{border-left-color:#93c5fd}
    .wt-qty-btn{background:#fff;border:1px solid #c8d1e0;border-radius:3px;width:19px;height:19px;cursor:pointer;font-size:14px;line-height:1;display:inline-flex;align-items:center;justify-content:center;color:#5a6782;font-weight:700;flex-shrink:0;padding:0}
    .wt-chip.wt-selecionado .wt-qty-btn{border-color:#93c5fd;color:#0f5fd4}
    .wt-qty-btn:hover{background:#dbeafe;border-color:#93c5fd;color:#0f5fd4}
    .wt-qty-input{width:40px;border:1px solid #c8d1e0;border-radius:3px;text-align:center;font-size:12px;font-weight:700;color:#5a6782;padding:1px 2px;-moz-appearance:textfield;background:#fff;height:19px;box-sizing:border-box}
    .wt-chip.wt-selecionado .wt-qty-input{border-color:#93c5fd;color:#0f5fd4}
    .wt-qty-input::-webkit-inner-spin-button,.wt-qty-input::-webkit-outer-spin-button{-webkit-appearance:none}
    .wt-qty-input.wt-qty-erro{border-color:#dc2626!important;color:#dc2626!important;background:#fff1f1}
    .wt-qty-ctrl--drawer{display:inline-flex;border-left:none;padding-left:0;margin-left:0}
    .wt-qty-ctrl--row{border-left:1px solid #c8d1e0;padding-left:8px;margin-left:6px}
    .wt-row.wt-selecionado .wt-qty-ctrl--row{border-left-color:#93c5fd}
    .wt-row.wt-selecionado .wt-qty-btn{border-color:#93c5fd;color:#0f5fd4}
    .wt-qty-ctrl--drawer .wt-qty-btn{background:#f1f4f9;border-color:#c8d1e0;color:#0d1424}
    .wt-qty-ctrl--drawer .wt-qty-btn:hover{background:#dbeafe;border-color:#93c5fd;color:#0f5fd4}
    .wt-qty-input--drawer{width:44px;font-size:13px;color:#0d1424;height:21px}
    .wt-drawer-item--aviso{border-color:#fca5a5;background:#fff5f5}
    .wt-item-aviso-tag{font-size:10px;font-weight:700;color:#dc2626;background:#fee2e2;border-radius:3px;padding:1px 5px;white-space:nowrap}
    .wt-drawer-footer{padding:16px 20px;border-top:1px solid #d4dae6;flex-shrink:0}
    .wt-drawer-hint{font-size:12px;color:#c0860a;margin-bottom:10px;min-height:16px}
    .wt-drawer-enviar{width:100%;justify-content:center;font-size:14px;padding:11px 16px}

    /* Barra flutuante — botão Ver pedido */
    .wt-float-ver{background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.25);color:#fff;border-radius:8px;padding:7px 13px;font-size:12.5px;font-weight:600;cursor:pointer;transition:background .15s;white-space:nowrap}
    .wt-float-ver:hover{background:rgba(255,255,255,.22)}

    @media(max-width:600px){
      .wt-model-hd{flex-direction:column;gap:3px}
      .wt-stor{flex-direction:column;align-items:flex-start;gap:8px}
      /* Deixa ~72px à direita para o botão WA fixo do site não sobrepor */
      .wt-float{left:12px;right:80px;transform:translateY(100px);min-width:0;bottom:20px;white-space:normal}
      .wt-float.wt-float--visivel{transform:translateY(0)}
      .wt-float-info{flex-wrap:wrap;gap:3px}
      .wt-float-ver{font-size:11.5px;padding:6px 10px}
      .wt-float-btn{font-size:12px;padding:8px 11px}
      .wt-drawer{width:100%;top:auto;height:80vh;transform:translateY(100%)}
      .wt-drawer.wt-aberto{transform:translateY(0)}
    }
    </style>

    <script>
    (function(){
      var MIN_QTD_IPHONE  = 5;
      var MIN_QTD_FARMACO = 5;
      function minQtd(tipo) { return tipo === 'iphone' ? MIN_QTD_IPHONE : MIN_QTD_FARMACO; }
      var WA_NUM         = '595975682071';
      var selecionados   = [];
      var WT_TAXA        = <?php echo json_encode($taxa_dolar); ?>;
      var EM_IPHONE = String.fromCharCode(0xD83D, 0xDCF1);
      var EM_PILL   = String.fromCharCode(0xD83D, 0xDC8A);

      function parsePx(str) {
        return parseInt(String(str).replace(/[^0-9]/g,'')) || 0;
      }
      function fmtUSD(n) {
        return '$' + n.toString().replace(/\B(?=(\d{3})+(?!\d))/g,'.');
      }
      function fmtBRL(usdNum) {
        if (!WT_TAXA) return '';
        var n = Math.round(usdNum * WT_TAXA);
        return 'R$ ' + n.toString().replace(/\B(?=(\d{3})+(?!\d))/g,'.');
      }
      function wtTotalQtd() {
        return selecionados.reduce(function(s,i){ return s + i.qtd; }, 0);
      }
      function wtTotalValor() {
        return selecionados.reduce(function(s,i){ return s + parsePx(i.preco) * i.qtd; }, 0);
      }
      function wtIphoneAbaixoMin() {
        return selecionados.filter(function(i){ return i.tipo === 'iphone' && i.qtd < MIN_QTD_IPHONE; });
      }
      function wtAbaixoMin() {
        return selecionados.filter(function(i){ return i.qtd < minQtd(i.tipo); });
      }

      function wtSyncChipInput(nome, qtd, tipo) {
        document.querySelectorAll('.wt-selecionavel').forEach(function(el){
          if (el.dataset.nome === nome) {
            var inp = el.querySelector('.wt-qty-input');
            if (inp) {
              inp.value = qtd;
              inp.classList.toggle('wt-qty-erro', qtd < minQtd(tipo));
            }
          }
        });
      }

      window.wtToggle = function(el) {
        var nome  = el.dataset.nome;
        var preco = el.dataset.preco;
        var tipo  = el.dataset.tipo;
        var idx   = selecionados.findIndex(function(i){ return i.nome === nome; });
        if (idx >= 0) {
          selecionados.splice(idx, 1);
          el.classList.remove('wt-selecionado');
          var inp = el.querySelector('.wt-qty-input');
          if (inp) { inp.value = MIN_QTD_IPHONE; inp.classList.remove('wt-qty-erro'); }
        } else {
          var inp = el.querySelector('.wt-qty-input');
          var qtdInicial = minQtd(tipo);
          if (inp) qtdInicial = Math.max(1, parseInt(inp.value) || qtdInicial);
          selecionados.push({nome:nome, preco:preco, tipo:tipo, qtd:qtdInicial});
          el.classList.add('wt-selecionado');
          if (inp) {
            inp.value = qtdInicial;
            inp.classList.toggle('wt-qty-erro', qtdInicial < minQtd(tipo));
          }
        }
        wtAtualizarFloat();
        wtAtualizarDrawer();
      };

      window.wtAlterarQtd = function(chipEl, delta) {
        var nome = chipEl.dataset.nome;
        var tipo = chipEl.dataset.tipo;
        var idx  = selecionados.findIndex(function(i){ return i.nome === nome; });
        if (idx < 0) {
          // Auto-seleciona ao clicar + ou -
          var inp = chipEl.querySelector('.wt-qty-input');
          var qtd = Math.max(1, (parseInt(inp && inp.value) || MIN_QTD_IPHONE) + delta);
          selecionados.push({nome:nome, preco:chipEl.dataset.preco, tipo:tipo, qtd:qtd});
          chipEl.classList.add('wt-selecionado');
          if (inp) { inp.value = qtd; inp.classList.toggle('wt-qty-erro', qtd < minQtd(tipo)); }
          wtAtualizarFloat();
          wtAtualizarDrawer();
          return;
        }
        selecionados[idx].qtd = Math.max(1, selecionados[idx].qtd + delta);
        wtSyncChipInput(nome, selecionados[idx].qtd, tipo);
        wtAtualizarFloat();
        if (document.getElementById('wt-drawer').classList.contains('wt-aberto')) wtAtualizarDrawer();
      };

      window.wtSetQtdInput = function(chipEl, inputEl) {
        var nome = chipEl.dataset.nome;
        var tipo = chipEl.dataset.tipo;
        var v    = Math.max(1, parseInt(inputEl.value) || 1);
        inputEl.value = v;
        var idx = selecionados.findIndex(function(i){ return i.nome === nome; });
        if (idx < 0) {
          // Auto-seleciona ao digitar
          selecionados.push({nome:nome, preco:chipEl.dataset.preco, tipo:tipo, qtd:v});
          chipEl.classList.add('wt-selecionado');
          inputEl.classList.toggle('wt-qty-erro', v < minQtd(tipo));
          wtAtualizarFloat();
          wtAtualizarDrawer();
          return;
        }
        selecionados[idx].qtd = v;
        inputEl.classList.toggle('wt-qty-erro', v < minQtd(tipo));
        wtAtualizarFloat();
        if (document.getElementById('wt-drawer').classList.contains('wt-aberto')) wtAtualizarDrawer();
      };

      window.wtAlterarQtdDrawer = function(nome, delta) {
        var idx = selecionados.findIndex(function(i){ return i.nome === nome; });
        if (idx < 0) return;
        selecionados[idx].qtd = Math.max(1, selecionados[idx].qtd + delta);
        wtSyncChipInput(nome, selecionados[idx].qtd, selecionados[idx].tipo);
        wtAtualizarFloat();
        wtAtualizarDrawer();
      };

      window.wtSetQtdInputDrawer = function(nome, inputEl) {
        var idx = selecionados.findIndex(function(i){ return i.nome === nome; });
        if (idx < 0) return;
        var v = Math.max(1, parseInt(inputEl.value) || 1);
        inputEl.value = v;
        selecionados[idx].qtd = v;
        inputEl.classList.toggle('wt-qty-erro', v < minQtd(selecionados[idx].tipo));
        wtSyncChipInput(nome, v, selecionados[idx].tipo);
        wtAtualizarFloat();
      };

      window.wtRemover = function(nome) {
        var idx = selecionados.findIndex(function(i){ return i.nome === nome; });
        if (idx < 0) return;
        selecionados.splice(idx, 1);
        document.querySelectorAll('.wt-selecionavel').forEach(function(el){
          if (el.dataset.nome === nome) {
            el.classList.remove('wt-selecionado');
            var inp = el.querySelector('.wt-qty-input');
            if (inp) { inp.value = minQtd(el.dataset.tipo); inp.classList.remove('wt-qty-erro'); }
          }
        });
        wtAtualizarFloat();
        wtAtualizarDrawer();
      };

      function wtAtualizarFloat() {
        var total   = wtTotalQtd();
        var n       = selecionados.length;
        var floatEl = document.getElementById('wt-float');
        var countEl = document.getElementById('wt-float-count');
        var hintEl  = document.getElementById('wt-float-hint');
        var btnEl   = document.getElementById('wt-float-btn');

        countEl.textContent = total;

        var totalEl = document.getElementById('wt-float-total');

        if (n === 0) {
          floatEl.classList.remove('wt-float--visivel');
          if (totalEl) totalEl.textContent = '';
          return;
        }
        floatEl.classList.add('wt-float--visivel');
        var totalUSD = wtTotalValor();
        var brlStr = WT_TAXA ? ' / ' + fmtBRL(totalUSD) : '';
        if (totalEl) totalEl.textContent = '· ' + fmtUSD(totalUSD) + brlStr;

        var abaixo = wtAbaixoMin();
        if (abaixo.length > 0) {
          hintEl.textContent = 'Mínimo 5 unidades por item';
          btnEl.classList.add('wt-float-btn--inativo');
        } else {
          hintEl.textContent = '';
          btnEl.classList.remove('wt-float-btn--inativo');
        }
      }

      function wtAtualizarDrawer() {
        var lista     = document.getElementById('wt-drawer-lista');
        var hintEl    = document.getElementById('wt-drawer-hint');
        var enviarBtn = document.getElementById('wt-drawer-enviar');
        var n         = selecionados.length;

        if (n === 0) {
          lista.innerHTML = '<p class="wt-drawer-vazio">Nenhum item selecionado.<br>Clique nos produtos para adicionar.</p>';
          hintEl.textContent = '';
          enviarBtn.classList.add('wt-float-btn--inativo');
          return;
        }

        var iphones  = selecionados.filter(function(i){ return i.tipo === 'iphone'; });
        var farmacos = selecionados.filter(function(i){ return i.tipo === 'farmaco'; });
        var html = '';

        // Aspas simples para evitar quebrar onclick="..." com JSON.stringify
        function jsStr(s) {
          return "'" + String(s).replace(/\\/g,'\\\\').replace(/'/g,"\\'") + "'";
        }

        function qtdCtrl(item) {
          var nn = jsStr(item.nome);
          var abaixo = item.qtd < minQtd(item.tipo);
          var errCls = abaixo ? ' wt-qty-erro' : '';
          return '<span class="wt-qty-ctrl wt-qty-ctrl--drawer">'
            + '<button class="wt-qty-btn" onclick="wtAlterarQtdDrawer(' + nn + ',-1)">−</button>'
            + '<input class="wt-qty-input wt-qty-input--drawer' + errCls + '" type="number" min="1" step="1"'
            + ' value="' + item.qtd + '" oninput="wtSetQtdInputDrawer(' + nn + ',this)" onclick="this.select()">'
            + '<button class="wt-qty-btn" onclick="wtAlterarQtdDrawer(' + nn + ',1)">+</button>'
            + '</span>';
        }

        function itemPrecoEl(item) {
          var usdUnit = parsePx(item.preco);
          var sub = usdUnit * item.qtd;
          if (item.qtd > 1) {
            var brl = WT_TAXA ? '<span class="wt-drawer-item-brl">' + fmtBRL(sub) + '</span>' : '';
            return '<span style="display:flex;flex-direction:column;align-items:flex-end">'
              + '<span class="wt-drawer-item-sub">' + fmtUSD(sub) + '</span>'
              + brl
              + '<span class="wt-drawer-item-unit">' + item.qtd + '×' + item.preco + '</span>'
              + '</span>';
          }
          var brl1 = WT_TAXA ? '<span class="wt-drawer-item-brl">' + fmtBRL(usdUnit) + '</span>' : '';
          return '<span style="display:flex;flex-direction:column;align-items:flex-end">'
            + '<span class="wt-drawer-item-preco">' + item.preco + '</span>'
            + brl1
            + '</span>';
        }

        if (iphones.length > 0) {
          html += '<div class="wt-drawer-grupo-lbl">' + EM_IPHONE + ' iPhones</div>';
          iphones.forEach(function(item){
            var abaixo = item.qtd < MIN_QTD_IPHONE;
            html += '<div class="wt-drawer-item' + (abaixo ? ' wt-drawer-item--aviso' : '') + '">'
              + '<span class="wt-drawer-item-nome">' + item.nome
              + (abaixo ? ' <span class="wt-item-aviso-tag">mín. ' + MIN_QTD_IPHONE + '</span>' : '')
              + '</span>'
              + qtdCtrl(item)
              + itemPrecoEl(item)
              + '<button class="wt-drawer-remover" onclick="wtRemover(' + jsStr(item.nome) + ')" title="Remover">✕</button>'
              + '</div>';
          });
        }
        if (farmacos.length > 0) {
          html += '<div class="wt-drawer-grupo-lbl">' + EM_PILL + ' Fármacos</div>';
          farmacos.forEach(function(item){
            var abaixo = item.qtd < MIN_QTD_FARMACO;
            html += '<div class="wt-drawer-item' + (abaixo ? ' wt-drawer-item--aviso' : '') + '">'
              + '<span class="wt-drawer-item-nome">' + item.nome
              + (abaixo ? ' <span class="wt-item-aviso-tag">mín. ' + MIN_QTD_FARMACO + '</span>' : '')
              + '</span>'
              + qtdCtrl(item)
              + itemPrecoEl(item)
              + '<button class="wt-drawer-remover" onclick="wtRemover(' + jsStr(item.nome) + ')" title="Remover">✕</button>'
              + '</div>';
          });
        }

        // Total geral
        var tvUSD = wtTotalValor();
        html += '<div class="wt-drawer-total">'
          + '<span>Total</span>'
          + '<span style="display:flex;flex-direction:column;align-items:flex-end">'
          + '<span class="wt-drawer-total-val">' + fmtUSD(tvUSD) + '</span>'
          + (WT_TAXA ? '<span style="font-size:12px;font-weight:600;color:#5a6782">' + fmtBRL(tvUSD) + '</span>' : '')
          + '</span>'
          + '</div>';

        lista.innerHTML = html;

        var abaixo = wtAbaixoMin();
        if (abaixo.length > 0) {
          hintEl.textContent = 'Mínimo 5 unidades por item.';
          enviarBtn.classList.add('wt-float-btn--inativo');
        } else {
          hintEl.textContent = '';
          enviarBtn.classList.remove('wt-float-btn--inativo');
        }
      }

      window.wtAbrirDrawer = function() {
        wtAtualizarDrawer();
        document.getElementById('wt-drawer').classList.add('wt-aberto');
        document.getElementById('wt-drawer-overlay').classList.add('wt-aberto');
        document.body.style.overflow = 'hidden';
      };

      window.wtFecharDrawer = function() {
        document.getElementById('wt-drawer').classList.remove('wt-aberto');
        document.getElementById('wt-drawer-overlay').classList.remove('wt-aberto');
        document.body.style.overflow = '';
      };

      window.wtEnviarWhatsApp = function() {
        var abaixo = wtAbaixoMin();
        if (abaixo.length > 0) {
          var msgs = abaixo.map(function(i){
            return '• ' + i.nome + ' (' + i.qtd + '/' + minQtd(i.tipo) + ')';
          }).join('\n');
          alert('Mínimo de 5 unidades por item:\n\n' + msgs);
          return;
        }
        if (selecionados.length === 0) return;

        // Remove surrogate pairs (emoji 4-byte) do nome para evitar caracteres quebrados
        function limparNome(s) {
          return s.replace(/[\uD800-\uDBFF][\uDC00-\uDFFF]/g, '').replace(/\s{2,}/g,' ').trim();
        }

        var iphones  = selecionados.filter(function(i){ return i.tipo === 'iphone'; });
        var farmacos = selecionados.filter(function(i){ return i.tipo === 'farmaco'; });

        var msg = '*Pedido Atacado — WorldTech Paraguay*\n\n';

        if (iphones.length > 0) {
          msg += 'iPhones:\n';
          iphones.forEach(function(i){
            var sub = parsePx(i.preco) * i.qtd;
            var nome = limparNome(i.nome);
            var brl = WT_TAXA ? ' / ' + fmtBRL(sub) : '';
            msg += '• ' + i.qtd + 'x ' + nome + '\n';
            msg += '  ' + i.preco + ' cada  =  *' + fmtUSD(sub) + '*' + brl + '\n';
          });
          msg += '\n';
        }
        if (farmacos.length > 0) {
          msg += 'Fármacos:\n';
          farmacos.forEach(function(i){
            var nome = limparNome(i.nome);
            var sub = parsePx(i.preco) * i.qtd;
            var brl = WT_TAXA ? ' / ' + fmtBRL(sub) : '';
            msg += '• ' + i.qtd + 'x ' + nome + '\n';
            msg += '  ' + i.preco + ' cada  =  *' + fmtUSD(sub) + '*' + brl + '\n';
          });
          msg += '\n';
        }

        var totalFinal = wtTotalValor();
        msg += '*Total: ' + fmtUSD(totalFinal) + (WT_TAXA ? ' / ' + fmtBRL(totalFinal) : '') + '*\n';
        msg += 'Mínimo 5 unidades por item.';

        window.open('https://wa.me/' + WA_NUM + '?text=' + encodeURIComponent(msg), '_blank');
      };

      window.wtTab = function(nome, btn) {
        document.querySelectorAll('#wt-atacado .wt-panel').forEach(function(p){ p.classList.remove('wt-panel--active'); });
        document.querySelectorAll('#wt-atacado .wt-tab').forEach(function(b){ b.classList.remove('wt-tab--active'); b.setAttribute('aria-selected','false'); });
        document.getElementById('wt-' + nome).classList.add('wt-panel--active');
        btn.classList.add('wt-tab--active');
        btn.setAttribute('aria-selected','true');
      };

      // Fechar drawer com ESC
      document.addEventListener('keydown', function(e){ if (e.key === 'Escape') wtFecharDrawer(); });
    })();
    </script>
    <?php

    return ob_get_clean();
});
