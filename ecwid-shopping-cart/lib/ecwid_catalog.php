<?php

function show_ecwid($params) {
    $store_id = $params['store_id'];
    if (empty($store_id)) {
        $store_id = '1003'; //demo mode
    }

    $list_of_views = $params['list_of_views'];

    if (is_array($list_of_views))
        foreach ($list_of_views as $k => $v) {
            if (!in_array($v, array('list', 'grid', 'table')))
                unset($list_of_views[$k]);
        }

    if ((!is_array($list_of_views)) || empty($list_of_views)) {
        $list_of_views = array('list', 'grid', 'table');
    }

    $ecwid_pb_categoriesperrow = $params['ecwid_pb_categoriesperrow'];
    if (empty($ecwid_pb_categoriesperrow)) {
        $ecwid_pb_categoriesperrow = 3;
    }
    $ecwid_pb_productspercolumn_grid = $params['ecwid_pb_productspercolumn_grid'];
    if (empty($ecwid_pb_productspercolumn_grid)) {
        $ecwid_pb_productspercolumn_grid = 3;
    }
    $ecwid_pb_productsperrow_grid = $params['ecwid_pb_productsperrow_grid'];
    if (empty($ecwid_pb_productsperrow_grid)) {
        $ecwid_pb_productsperrow_grid = 3;
    }
    $ecwid_pb_productsperpage_list = $params['ecwid_pb_productsperpage_list'];
    if (empty($ecwid_pb_productsperpage_list)) {
        $ecwid_pb_productsperpage_list = 10;
    }
    $ecwid_pb_productsperpage_table = $params['ecwid_pb_productsperpage_table'];
    if (empty($ecwid_pb_productsperpage_table)) {
        $ecwid_pb_productsperpage_table = 20;
    }
    $ecwid_pb_defaultview = $params['ecwid_pb_defaultview'];
    if (empty($ecwid_pb_defaultview) || !in_array($ecwid_pb_defaultview, $list_of_views)) {
        $ecwid_pb_defaultview = 'grid';
    }
    $ecwid_pb_searchview = $params['ecwid_pb_searchview'];
    if (empty($ecwid_pb_searchview) || !in_array($ecwid_pb_searchview, $list_of_views)) {
        $ecwid_pb_searchview = 'list';
    }
    $ecwid_enable_html_mode = $params['ecwid_enable_html_mode'];
    if (empty($ecwid_enable_html_mode)) {
        $ecwid_enable_html_mode = false;
    }

    $ecwid_com = "app.ecwid.com";


    $ecwid_default_category_id = $params['ecwid_default_category_id'];

    $ecwid_show_seo_catalog = $params['ecwid_show_seo_catalog'];
    if (empty($ecwid_show_seo_catalog)) {
        $ecwid_show_seo_catalog = false;
    }
    $ecwid_seo_for_yandex = !empty($params['ecwid_seo_for_yandex']);

    $ecwid_mobile_catalog_link = $params['ecwid_mobile_catalog_link'];
    if (empty($ecwid_mobile_catalog_link)) {
        $ecwid_mobile_catalog_link = "http://$ecwid_com/jsp/$store_id/catalog";
    }

    $html_catalog = '';
    if ($ecwid_show_seo_catalog) {
        global $wp_query;
        $ecwid_product_id = intval($wp_query->query_vars['ecwid_product_id']);
        $ecwid_category_id = intval($wp_query->query_vars['ecwid_category_id']);
        if (!empty($ecwid_product_id)) {
            $ecwid_open_product = '<script> if (!document.location.hash) document.location.hash = "ecwid:mode=product&product=' . intval($ecwid_product_id) . '";</script>';
        } elseif (!empty($ecwid_category_id)) {
            $ecwid_default_category_id = intval($ecwid_category_id);
        }
        $html_catalog = show_ecwid_catalog($store_id);
    }

    if (empty($html_catalog)) {
        $html_catalog = "Your browser does not support JavaScript.<a href=\"{$ecwid_mobile_catalog_link}\">HTML version of this store</a>";
    }


    if (empty($ecwid_default_category_id)) {
        $ecwid_default_category_str = '';
    } else {
        $ecwid_default_category_str = ',"defaultCategoryId=' . $ecwid_default_category_id . '"';
    }

    $ecwid_is_secure_page = $params['ecwid_is_secure_page'];
    if (empty($ecwid_is_secure_page)) {
        $ecwid_is_secure_page = false;
    }

    $protocol = "http";
    if ($ecwid_is_secure_page) {
        $protocol = "https";
    }

    if ($ecwid_seo_for_yandex) {
        $html_catalog = <<<EOT
<div id="ecwid-inline-catalog">$html_catalog</div>
<script>document.getElementById('ecwid-inline-catalog').style.display='none';</script>
EOT;
    } else {
        $html_catalog = <<<EOT
<noscript>$html_catalog</noscript>
EOT;
    }

    $integration_code = <<<EOT
<div>
<script type="text/javascript" src="//$ecwid_com/script.js?$store_id"></script>
<script type="text/javascript"> xProductBrowser("categoriesPerRow=$ecwid_pb_categoriesperrow","views=grid($ecwid_pb_productspercolumn_grid,$ecwid_pb_productsperrow_grid) list($ecwid_pb_productsperpage_list) table($ecwid_pb_productsperpage_table)","categoryView=$ecwid_pb_defaultview","searchView=$ecwid_pb_searchview","style="$ecwid_default_category_str);</script>
</div>
$html_catalog
$ecwid_open_product
EOT;

    return $integration_code;
}

function show_ecwid_catalog($ecwid_store_id) {
    include_once "ecwid_product_api.php";
    $ecwid_store_id = intval($ecwid_store_id);
    $api = new EcwidProductApi($ecwid_store_id);
    global $wp_query;
    $ecwid_category_id = intval($wp_query->query_vars['ecwid_category_id']);
    $ecwid_product_id = intval($wp_query->query_vars['ecwid_product_id']);
    static $ecwid_cat_prod_data;
    if (empty($ecwid_cat_prod_data)) {
        $ecwid_cat_prod_data = ecwid_get_mixed_data();
    }
    if (!empty($ecwid_product_id)) {
        $product = $ecwid_cat_prod_data["p"];
    } else {
        $categories = $ecwid_cat_prod_data["c"];
        $products = $ecwid_cat_prod_data["p"];
    }
    $profile = $ecwid_cat_prod_data["pf"];
    $html = '';



    if (is_array($product)) {
        $html = "<div class='hproduct'>";
        $html .= "<h2 class='ecwid_catalog_product_name fn'>" . htmlentities($product["name"], ENT_COMPAT, 'UTF-8') . "</h2>";
        if (!empty($product['thumbnailUrl'])) {
            $html .= "<div class='ecwid_catalog_product_image photo'><img src='" . $product["thumbnailUrl"] . "' alt='" . htmlentities($product["sku"], ENT_COMPAT, 'UTF-8') . " " . htmlentities($product["name"], ENT_COMPAT, 'UTF-8') . "'/></div>";
        }
        $html .= "<div class='ecwid_catalog_product_price price'>Price: " . $product["price"] . "&nbsp;" . $profile["currency"] . "</div>";
        $html .= "<div class='ecwid_catalog_product_description description'>" . $product["description"] . "</div>";
        $html .= "</div>";
    } else {
        if (is_array($categories)) {
            foreach ($categories as $category) {
                $category_url = ecwid_internal_construct_url($category["url"], array("ecwid_category_id" => $category["id"]), $api);
                $category_name = $category["name"];
                $html .= "<div class='ecwid_catalog_category_name'><a href='" . htmlspecialchars($category_url) . "'>" . $category_name . "</a><br /></div>";
            }
        }

        if (is_array($products)) {
            foreach ($products as $product) {
                $product_url = ecwid_internal_construct_url($product["url"], array("ecwid_product_id" => $product["id"]), $api);
                $product_name = $product["name"];
                $product_price = $product["price"] . "&nbsp;" . $profile["currency"];
                $html .= "<div>";
                $html .= "<span class='ecwid_product_name'><a href='" . htmlspecialchars($product_url) . "'>" . $product_name . "</a></span>";
                $html .= "&nbsp;&nbsp;<span class='ecwid_product_price'>" . $product_price . "</span>";
                $html .= "</div>";
            }
        }
    }
    return $html;
}

function ecwid_is_api_enabled($ecwid_store_id) {
    $ecwid_store_id = intval($ecwid_store_id);
    $api = new EcwidProductApi($ecwid_store_id);
    return $api->is_api_enabled();
}

function ecwid_zerolen() {
    foreach (func_get_args() as $arg) {
        if (strlen($arg) == 0)
            return true;
    }
    return false;
}

function ecwid_get_request_uri() {
    static $request_uri = null;

    if (is_null($request_uri)) {
        if (isset($_SERVER['REQUEST_URI'])) {
            $request_uri = $_SERVER['REQUEST_URI'];
            return $request_uri;
        }
        if (isset($_SERVER['HTTP_X_ORIGINAL_URL'])) {
            $request_uri = $_SERVER['HTTP_X_ORIGINAL_URL'];
            return $request_uri;
        } else if (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
            $request_uri = $_SERVER['HTTP_X_REWRITE_URL'];
            return $request_uri;
        }

        if (isset($_SERVER['PATH_INFO']) && !ecwid_zerolen($_SERVER['PATH_INFO'])) {
            if ($_SERVER['PATH_INFO'] == $_SERVER['PHP_SELF']) {
                $request_uri = $_SERVER['PHP_SELF'];
            } else {
                $request_uri = $_SERVER['PHP_SELF'] . $_SERVER['PATH_INFO'];
            }
        } else {
            $request_uri = $_SERVER['PHP_SELF'];
        }
        # Append query string
        if (isset($_SERVER['argv']) && isset($_SERVER['argv'][0]) && !ecwid_zerolen($_SERVER['argv'][0])) {
            $request_uri .= '?' . $_SERVER['argv'][0];
        } else if (isset($_SERVER['QUERY_STRING']) && !ecwid_zerolen($_SERVER['QUERY_STRING'])) {
            $request_uri .= '?' . $_SERVER['QUERY_STRING'];
        }
    }
    return $request_uri;
}

function ecwid_get_path_ending_index($path, $ending_part) {
    $part_pos = strpos($path, $ending_part);
    return strrpos($path, '/', $part_pos * -1) + 1;
}

function ecwid_convert_to_code($text) {
    $search = Array("!" => "%21",
        "\"" => "%22",
        "#" => "%23",
        "&" => "%26",
        "'" => "%27",
        "*" => "%2a",
        "," => "%2c",
        ":" => "%3a",
        ";" => "%3b",
        "<" => "%3c",
        ">" => "%3e",
        "?" => "%3f",
        "[" => "%5b",
        "]" => "%5d",
        "^" => "%5e",
        "`" => "%60",
        "{" => "%7b",
        "|" => "%7c",
        "}" => "%7d",
        " " => "-");

    $res_text = str_replace("%", "%25", $text);
    for ($i = 0; $i < strlen($res_text); $i++) {
        if (isset($search[$res_text[$i]])) {
            $res_text = str_replace($res_text[$i], $search[$res_text[$i]], $res_text);
        }
    }
    return $res_text;
}

function ecwid_get_smth_name_by_id($id, $smth_code) {
    static $ecwid_cat_prod_data;
    if (empty($ecwid_cat_prod_data)) {
        $ecwid_cat_prod_data = ecwid_get_mixed_data();
    }
    if (isset($ecwid_cat_prod_data[$smth_code]['name'])) {
        return $ecwid_cat_prod_data[$smth_code]['name'];
    }
    foreach ($ecwid_cat_prod_data[$smth_code] as $val) {
        if ($val['id'] == $id) {
            return $val['name'];
        }
    }
}

function ecwid_internal_construct_url($url_with_anchor, $additional_get_params, $api) {
    $request_uri = parse_url(ecwid_get_request_uri());
    $base_url = get_permalink(get_option("ecwid_store_page_id"));
    global $wp_query;
    // extract anchor
    $url_fragments = parse_url($url_with_anchor);
    $anchor = $url_fragments["fragment"];
    // get params
    $get_params = Array('ecwid_category_id' => $wp_query->query_vars['ecwid_category_id'],
        'ecwid_product_id' => $wp_query->query_vars['ecwid_category_id']);
    unset($get_params["ecwid_category_id"]);
    unset($get_params["ecwid_product_id"]);
    $get_params = array_merge($get_params, $additional_get_params);
    if (get_option('permalink_structure') != '') {
        $base_url = get_permalink(get_option("ecwid_store_page_id")) . '/';
        if (count($get_params) > 0) {
            if (isset($get_params['ecwid_product_id'])) {
                $base_url .= ecwid_convert_to_code(ecwid_get_smth_name_by_id($get_params['ecwid_product_id'], 'p')) . "-p-" . $get_params['ecwid_product_id'];
            } else {
                $base_url .= ecwid_convert_to_code(ecwid_get_smth_name_by_id($get_params['ecwid_category_id'], 'c')) . "-c-" . $get_params['ecwid_category_id'];
            }
        }
        // add url anchor (if needed)
        if ($anchor != "") {
            $base_url .= "#" . $anchor;
        }
    } else {
        //default path constructor
        $page_id = get_option('ecwid_store_page_id');
        $get_params['page_id'] = $page_id;
        if (count($get_params) > 0) {
            $base_url .= (strpos($base_url, '?') === false) ? "?" : "&";
            $is_first = true;
            foreach ($get_params as $key => $value) {
                if (!$is_first) {
                    $base_url .= "&";
                }
                $base_url .= $key . "=" . $value;
                $is_first = false;
            }
        }
    }
    return $base_url;
}

?>
