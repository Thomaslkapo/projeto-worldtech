<?php
/**
 * WorldTech Bridge — endpoint seguro para atualizações remotas de snippets
 * e funcionalidades do site (taxa do dólar, etc.)
 * Fica em mu-plugins/ para carregar automaticamente sem ativar plugin.
 */

define('WT_SECRET', 'wt_bridge_2026_secret_xK9mP3nQ');

add_action('rest_api_init', function () {

    // ── /snippet — listar / criar / atualizar ────────────────────────────────
    register_rest_route('worldtech/v1', '/snippet', [
        'methods'             => ['GET', 'POST'],
        'permission_callback' => '__return_true',
        'callback'            => function (WP_REST_Request $req) {
            if ($req->get_param('key') !== WT_SECRET)
                return new WP_Error('forbidden', 'Forbidden', ['status' => 403]);

            global $wpdb;
            $table = $wpdb->prefix . 'snippets';

            // GET — listar snippets
            if ($req->get_method() === 'GET') {
                $rows = $wpdb->get_results("SELECT id, name, active FROM $table ORDER BY id");
                return rest_ensure_response($rows);
            }

            // POST — criar (sem id ou id=0) ou atualizar (id > 0)
            $id   = intval($req->get_param('id') ?? 0);
            $code = $req->get_param('code');
            if ($code === null)
                return new WP_Error('bad_request', 'code obrigatorio', ['status' => 400]);

            if ($id > 0) {
                // Atualizar existente
                $updated = $wpdb->update($table, ['code' => $code], ['id' => $id]);
                delete_transient('cs_snippet_' . $id);
                return rest_ensure_response(['ok' => true, 'rows_updated' => $updated, 'id' => $id]);
            } else {
                // Criar novo
                $name   = sanitize_text_field($req->get_param('name') ?? 'Snippet ' . time());
                $active = intval($req->get_param('active') ?? 1);
                $scope  = $req->get_param('scope') ?? 'global';
                $wpdb->insert($table, [
                    'name'        => $name,
                    'description' => '',
                    'code'        => $code,
                    'tags'        => '',
                    'scope'       => $scope,
                    'active'      => $active,
                    'priority'    => 10,
                    'modified'    => current_time('mysql'),
                ]);
                $new_id = $wpdb->insert_id;
                return rest_ensure_response(['ok' => true, 'id' => $new_id, 'created' => true]);
            }
        },
    ]);

    // ── /zerar-atacado — zera _preco_atacado e stock de todas as variações ─────
    register_rest_route('worldtech/v1', '/zerar-atacado', [
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => function (WP_REST_Request $r) {
            if ($r->get_param('key') !== WT_SECRET)
                return new WP_Error('forbidden', '', ['status' => 403]);
            global $wpdb;
            // Buscar todas as variações que têm _preco_atacado > 0
            $ids = $wpdb->get_col("
                SELECT DISTINCT post_id FROM {$wpdb->postmeta}
                WHERE meta_key = '_preco_atacado' AND meta_value != '' AND meta_value > 0
            ");
            if (empty($ids)) return ['success' => true, 'cleared' => 0];
            $in = implode(',', array_map('intval', $ids));
            // Zera preço atacado
            $wpdb->query("UPDATE {$wpdb->postmeta} SET meta_value = '' WHERE meta_key = '_preco_atacado' AND post_id IN ($in)");
            // Marca fora de estoque
            $wpdb->query("UPDATE {$wpdb->postmeta} SET meta_value = 'outofstock' WHERE meta_key = '_stock_status' AND post_id IN ($in)");
            // Limpa cache WC
            foreach ($ids as $id) wc_delete_product_transients($id);
            return ['success' => true, 'cleared' => count($ids)];
        },
    ]);

    // ── /taxa — ler / salvar taxa do dólar ───────────────────────────────────
    register_rest_route('worldtech/v1', '/taxa', [
        [
            'methods'             => 'GET',
            'permission_callback' => '__return_true',
            'callback'            => function (WP_REST_Request $r) {
                if ($r->get_param('key') !== WT_SECRET)
                    return new WP_Error('forbidden', '', ['status' => 403]);
                return [
                    'taxa'       => get_option('wt_taxa_dolar', ''),
                    'atualizado' => get_option('wt_taxa_dolar_data', ''),
                ];
            },
        ],
        [
            'methods'             => 'POST',
            'permission_callback' => '__return_true',
            'callback'            => function (WP_REST_Request $r) {
                if ($r->get_param('key') !== WT_SECRET)
                    return new WP_Error('forbidden', '', ['status' => 403]);
                $taxa = sanitize_text_field($r->get_param('taxa') ?? '');
                $taxa = str_replace(',', '.', $taxa);
                if (!preg_match('/^\d+\.\d{1,4}$/', $taxa))
                    return new WP_Error('invalid', 'Taxa invalida: ' . $taxa, ['status' => 400]);
                $taxa_br = number_format((float) $taxa, 2, ',', '.');
                update_option('wt_taxa_dolar', $taxa_br, false);
                update_option('wt_taxa_dolar_data', wp_date('d/m H:i'), false);
                return ['success' => true, 'taxa' => $taxa_br];
            },
        ],
    ]);
});

// ── Banner taxa do dólar no topo do site ─────────────────────────────────────
add_action('wp_body_open', function () {
    $taxa = get_option('wt_taxa_dolar', '');
    $data = get_option('wt_taxa_dolar_data', '');
    if (!$taxa) return;
    echo '<div id="wt-taxa-banner">'
       . '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" style="width:15px;height:15px;flex-shrink:0;opacity:.85"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>'
       . '<span style="color:rgba(255,255,255,.75);font-weight:500">Dolar hoje</span>'
       . '<strong style="font-size:15.5px;font-weight:800;letter-spacing:.2px">R$ ' . esc_html($taxa) . '</strong>'
       . ($data ? '<span style="font-size:11.5px;color:rgba(255,255,255,.55)">· ' . esc_html($data) . '</span>' : '')
       . '</div>';
});

add_action('wp_head', function () {
    if (!get_option('wt_taxa_dolar', '')) return;
    echo '<style>'
       . '#wt-taxa-banner{position:fixed;top:0;left:0;right:0;z-index:999999;background:linear-gradient(90deg,#0843b8 0%,#0f5fd4 55%,#1870e8 100%);color:#fff;display:flex;align-items:center;justify-content:center;gap:9px;padding:0 20px;height:38px;box-sizing:border-box;font-family:system-ui,-apple-system,sans-serif;font-size:13.5px;box-shadow:0 2px 10px rgba(0,0,0,.3)}'
       . 'html{margin-top:38px!important}'
       . '@media(max-width:600px){#wt-taxa-banner{height:34px;font-size:12.5px}html{margin-top:34px!important}#wt-taxa-banner span:last-child{display:none}}'
       . '</style>';
});
