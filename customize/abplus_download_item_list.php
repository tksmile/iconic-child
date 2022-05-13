<?php
/**
 * 商品データCSVダウンロードの際、「カスタムフィールド項目」選択時に実行する関数
 * file : usc-e-shop/functions/define_function.php
 * fn : usces_download_item_meta_listを改変
 * 独自meta_key：_abplus_*の値をダウンロード対象に追加
*/
function abplus_download_item_meta_list( $rows, $usces_opt_item ) {
	global $usces;

	$meta_keys = array( 'post_id_meta' => 'null', 'item_code' => 'null', 'item_name' => 'null' );
	foreach( $rows as $row ){

		$metas = get_post_meta( $row['ID'] );
		foreach( $metas as $key => $value ) {
			if ( strpos( $key, 'tab_spec' ) !== false ) { //接頭語tab_specを含むCFなら
				unset( $metas[$key] ); //削除する
				continue;
			}
			if ( '_' !== substr( $key, 0, 1 ) || substr( $key, 0, 8 ) === '_abplus_' ) { //独自meta_kyeを追加(「1文字目に'_'があるkey除く」という条件にorする)
				$meta_keys[$key] = 'null';
			}
		}
	}

	$data = array();
	foreach( $rows as $r => $row ){

		$new = $meta_keys;
		$new['post_id_meta'] = $row['ID'];
		$new['item_code'] = $row['item_code'];
		$new['item_name'] = $row['item_name'];
		$metas = get_post_meta( $row['ID'] );
		foreach( $metas as $key => $values ) {
			if ( strpos( $key, 'tab_spec' ) !== false ) { //接頭語tab_specを含むCFなら
				unset( $metas[$key] ); //削除する
				continue;
			}
			if ( '_' !== substr( $key, 0, 1 ) || substr( $key, 0, 8 ) === '_abplus_' ) { //独自meta_kyeを追加(「1文字目に'_'があるkey除く」という条件にorする)
				$new[$key] = $values[0];
			}
		}
		$data[$r] = $new;
	}



	$ext = 'csv';
	$h = '"';
	$f = '",';
	$lf = "\n";

	$line = '';
	foreach( $meta_keys as $label => $lv  ) {
		$line .= $h . $label . $f;
	}
	$line = trim( $line, ',' );
	$line .= $lf;

	//==========================================================================
	mb_http_output('pass');
	set_time_limit(0);
	header("Content-Type: application/octet-stream");
	header("Content-Disposition: attachment; filename=usces_item_meta.".$ext);
	@ob_end_flush();
	flush();

	if( $usces->options['system']['csv_encode_type'] == 0 ) {
		$line = mb_convert_encoding( $line, apply_filters( 'usces_filter_output_csv_encode', 'SJIS-win'), "UTF-8" );
	}
	print($line);

	foreach( $data as $d ) {
		$line = '';

		foreach( $d as $dv  ) {
			$line .= $h . str_replace( '"', '""', $dv ) . $f;
		}
		$line = trim( $line, ',' );
		$line .= $lf;

		if( $usces->options['system']['csv_encode_type'] == 0 ) {
			$line = mb_convert_encoding( $line, apply_filters( 'usces_filter_output_csv_encode', 'SJIS-win'), "UTF-8" );
		}

		print($line);

		if( ob_get_contents() ) {
			ob_flush();
			flush();
		}
	}

	exit();
}

/**
 * 商品データをCSVダウンロードする関数
 * !function_existsのwelcart関数を改変して上書き
 * file : usc-e-shop/functions/define_function.php
 * 「カスタムフィールド項目」が選択された時に実行する関数を独自関数に変更
 * 独自カスタムフィールドの「商品仕様」(_abplus_*)をCSVでの取扱対象に含める
 * 接頭語tab_specを含むカスタムフィールド値をCSVでの取扱対象外に
*/
function usces_download_item_list() {
	require_once( USCES_PLUGIN_DIR."/classes/itemList.class.php" );
	global $wpdb, $usces;

	$ext = 'csv';
	$table_h = "";
	$table_f = "";
	$tr_h = "";
	$tr_f = "";
	$th_h1 = '"';
	$th_h = ',"';
	$th_f = '"';
	$td_h1 = '"';
	$td_h = ',"';
	$td_f = '"';
	$sp = ";";
	$eq = "=";
	$lf = "\n";
	$end = '';

	//==========================================================================

	$usces_opt_item = get_option('usces_opt_item');
	if( !is_array($usces_opt_item) ) {
		$usces_opt_item = array();
	}
	$usces_opt_item['chk_header'] = ( isset($_REQUEST['chk_header']) ) ? 1 : 0;
	$usces_opt_item['ftype_item'] = $ext;
	update_option('usces_opt_item', $usces_opt_item);

	//==========================================================================

	$tableName = $wpdb->posts;
	$arr_column = array(
				__('item code', 'usces') => 'item_code',
				__('item name', 'usces') => 'item_name',
				__('SKU code', 'usces') => 'sku_key',
				__('selling price', 'usces') => 'price',
				__('stock', 'usces') => 'zaiko_num',
				__('stock status', 'usces') => 'zaiko',
				__('Categories', 'usces') => 'category',
				__('display status', 'usces') => 'display_status');

	$_REQUEST['searchIn'] = "searchIn";
	$DT = new dataList($tableName, $arr_column);
	$DT->pageLimit = 'off';
	$DT->exportMode = true;
	$res = $DT->MakeTable();
	$rows = $DT->rows;

	//==========================================================================

	$results = apply_filters( 'usces_filter_item_downloadcsv_mode', array(), $rows, $usces_opt_item );
	if( !empty( $results ) ) {
		extract( $results );

	} elseif( isset( $_REQUEST['mode'] ) && 'stock' == $_REQUEST['mode'] ) {
		$results = usces_download_item_stock_list( $rows, $usces_opt_item );
		if( !empty( $results ) ) {
			extract( $results );
		}

	} elseif( isset( $_REQUEST['mode'] ) && 'sku' == $_REQUEST['mode'] ) {
		$results = usces_download_item_sku_list( $rows, $usces_opt_item );
		if( !empty( $results ) ) {
			extract( $results );
		}

	//「カスタムフィールド項目」が選択された時
	} elseif( isset( $_REQUEST['mode'] ) && 'meta' == $_REQUEST['mode'] ) {
		$results = abplus_download_item_meta_list( $rows, $usces_opt_item ); //usces_download_item_meta_listから独自関数に差替え
		if( !empty( $results ) ) {
			extract( $results );
		}

	} else { //全項目が選択された時

		$line = $table_h;
		if ( $usces_opt_item['chk_header'] == 1 ) {
			$line .= $tr_h;
			$line .= $th_h1.'Post ID'.$th_f;
			$line .= $th_h.__('Post Author', 'usces').$th_f;
			$line .= $th_h.__('explanation', 'usces').$th_f;
			$line .= $th_h.__('Title', 'usces').$th_f;
			$line .= $th_h.__('excerpt', 'usces').$th_f;
			$line .= $th_h.__('display status', 'usces').$th_f;
			$line .= $th_h.__('Comment Status', 'usces').$th_f;
			$line .= $th_h.__('Post Password', 'usces').$th_f;
			$line .= $th_h.__('Post Name', 'usces').$th_f;
			$line .= $th_h.__('Publish date', 'usces').$th_f;

			$line .= $th_h.__('item code', 'usces').$th_f;
			$line .= $th_h.__('item name', 'usces').$th_f;
			$line .= $th_h.__('Limited amount for purchase', 'usces').$th_f;
			$line .= $th_h.__('Percentage of points', 'usces').$th_f;
			$line .= $th_h.__('Business package discount', 'usces').'1-'.__('num', 'usces').$th_f.$th_h.__('Business package discount', 'usces').'1-'.__('rate', 'usces').$th_f;
			$line .= $th_h.__('Business package discount', 'usces').'2-'.__('num', 'usces').$th_f.$th_h.__('Business package discount', 'usces').'2-'.__('rate', 'usces').$th_f;
			$line .= $th_h.__('Business package discount', 'usces').'3-'.__('num', 'usces').$th_f.$th_h.__('Business package discount', 'usces').'3-'.__('rate', 'usces').$th_f;
			$line .= $th_h.__('estimated shipping date', 'usces').$th_f;
			$line .= $th_h.__('shipping option', 'usces').$th_f;
			$line .= $th_h.__('Shipping', 'usces').$th_f;
			$line .= $th_h.__('Postage individual charging', 'usces').$th_f;

			$line .= $th_h.__('Categories', 'usces').$th_f;
			$line .= $th_h.__('tag', 'usces').$th_f;
			$line .= $th_h.__('Custom Field', 'usces').$th_f;

			//Custom Fields '_abplus_*'を独自追加
			$abplus_spec_args = AbplusSpec::$spec_args;
			foreach ( $abplus_spec_args as $spec_arg_v ) {
				$line .= $th_h. '_abplus_'. $spec_arg_v[0]. $th_f;
			}

			$line .= apply_filters( 'usces_filter_downloadcsv_itemheader', '' );
			$line = apply_filters( 'usces_filter_downloadcsv_add_itemheader', $line );

			$line .= $th_h.__('SKU code', 'usces').$th_f;
			$line .= $th_h.__('SKU display name ', 'usces').$th_f;
			$line .= $th_h.__('normal price', 'usces').$th_f;
			$line .= $th_h.__('Sale price', 'usces').$th_f;
			$line .= $th_h.__('stock', 'usces').$th_f;
			$line .= $th_h.__('stock status', 'usces').$th_f;
			$line .= $th_h.__('unit', 'usces').$th_f;

			//advanceフィールドの商品仕様を独自追加
			foreach ( $abplus_spec_args as $spec_key => $spec_ary ) {
				$line .= $th_h. $spec_key. $th_f;
			}
			//$line .= $th_h. '商品仕様'. $th_f;

			$line .= $th_h. 'menu_order'. $th_f; //順序を独自追加

			$line .= $th_h.__('Apply business package', 'usces').$th_f;
			if( usces_is_reduced_taxrate() ) {
				$line .= $th_h.__( 'Applicable tax rate', 'usces' ).$th_f;
			}
			$line .= apply_filters( 'usces_filter_downloadcsv_header', '' );
			$line = apply_filters( 'usces_filter_downloadcsv_add_header', $line );
			$line .= $th_h.__('option name', 'usces').$th_f.$th_h.__('Field type', 'usces').$th_f.$th_h.__('Required', 'usces').$th_f.$th_h.__('selected amount', 'usces').$th_f;
			$line .= $tr_f.$lf;
		}

		//==========================================================================

		mb_http_output('pass');
		set_time_limit(3600);
		header("Content-Type: application/octet-stream");
		header("Content-Disposition: attachment; filename=usces_item_list.".$ext);
		@ob_end_flush();
		flush();
		$category_format_slug = ( isset($usces->options['system']['csv_category_format']) && 1 == $usces->options['system']['csv_category_format'] ) ? true : false;
		$item_custom_fields = usces_get_item_custom_fields();

		foreach( (array)$rows as $row ) {
			$post_id = $row['ID'];
			$post = get_post($post_id);

			//Post Data
			$line_item  = $td_h1.$post->ID.$td_f;
			$line_item .= $td_h.$post->post_author.$td_f;
			$line_item .= $td_h.usces_entity_decode($post->post_content, $ext).$td_f;
			$line_item .= $td_h.usces_entity_decode($post->post_title, $ext).$td_f;
			$line_item .= $td_h.usces_entity_decode($post->post_excerpt, $ext).$td_f;
			$line_item .= $td_h.$post->post_status.$td_f;
			$line_item .= $td_h.$post->comment_status.$td_f;
			$line_item .= $td_h.$post->post_password.$td_f;
			$line_item .= $td_h.urldecode($post->post_name).$td_f;
			$line_item .= $td_h.$post->post_date.$td_f;

			//Item Meta
			$line_item .= $td_h.$usces->getItemCode($post_id).$td_f;
			$line_item .= $td_h.usces_entity_decode($usces->getItemName($post_id), $ext).$td_f;
			$line_item .= $td_h.$usces->getItemRestriction($post_id).$td_f;
			$line_item .= $td_h.$usces->getItemPointrate($post_id).$td_f;
			$line_item .= $td_h.$usces->getItemGpNum1($post_id).$td_f.$td_h.$usces->getItemGpDis1($post_id).$td_f;
			$line_item .= $td_h.$usces->getItemGpNum2($post_id).$td_f.$td_h.$usces->getItemGpDis2($post_id).$td_f;
			$line_item .= $td_h.$usces->getItemGpNum3($post_id).$td_f.$td_h.$usces->getItemGpDis3($post_id).$td_f;
			$line_item .= $td_h.$usces->getItemShipping($post_id).$td_f;

			$delivery_method = '';
			$itemDeliveryMethod = $usces->getItemDeliveryMethod($post_id);
			foreach( (array)$itemDeliveryMethod as $k => $v ) {
				$delivery_method .= $v.$sp;
			}
			$delivery_method = rtrim($delivery_method, $sp);
			$line_item .= $td_h.$delivery_method.$td_f;
			$line_item .= $td_h.$usces->getItemShippingCharge($post_id).$td_f;
			$line_item .= $td_h.$usces->getItemIndividualSCharge($post_id).$td_f;

			//Categories
			$category = '';
			$cat_ids = wp_get_post_categories($post_id);
			if( !empty($cat_ids) ) {
				if( $category_format_slug ) {
					foreach( $cat_ids as $id ) {
						$cat = get_category( $id );
						$category .= $cat->slug.$sp;
					}
				} else {
					foreach( $cat_ids as $id ) {
						$category .= $id.$sp;
					}
				}
				$category = rtrim($category, $sp);
			}
			$line_item .= $td_h.$category.$td_f;

			//Tags
			$tag = '';
			$tags_ob = wp_get_object_terms($post_id, 'post_tag');
			foreach( $tags_ob as $ob ) {
				$tag .= $ob->name.$sp;
			}
			$tag = rtrim($tag, $sp);
			$line_item .= $td_h.$tag.$td_f;

			//Custom Fields
			$icfield = '';
			if( $item_custom_fields && is_array($item_custom_fields) && 0 < count($item_custom_fields) ) {
				foreach( $item_custom_fields as $key ) {
					$values = get_post_meta( $post_id, $key, true );
					if( is_array($values) ) {
						foreach( $values as $value ) {
							$icfield .= usces_entity_decode($key, $ext).$eq.usces_entity_decode($value, $ext).$sp;
						}
					} else {
						$icfield .= usces_entity_decode($key, $ext).$eq.usces_entity_decode($values, $ext).$sp;
					}
				}
			}
			$cfield = '';
			$custom_fields = $usces->get_post_user_custom($post_id);
			if( $custom_fields && is_array($custom_fields) && 0 < count($custom_fields) ) {
				foreach( $custom_fields as $cfkey => $cfvalues ) {

					//独自条件追加
					if ( strpos( $cfkey, 'tab_spec' ) !== false ) { //接頭語tab_specを含むCFなら
						unset( $custom_fields[$cfkey] ); //削除する
						continue;
					}

					if( is_array($cfvalues) ) {
						foreach( $cfvalues as $value ) {
							$cfield .= usces_entity_decode($cfkey, $ext).$eq.usces_entity_decode($value, $ext).$sp;
						}
					} else {
						$cfield .= usces_entity_decode($cfkey, $ext).$eq.usces_entity_decode($cfvalues, $ext).$sp;
					}
				}
				$cfield = rtrim($cfield, $sp);
			}
			$line_item .= $td_h.$icfield.$cfield.$td_f;

			//Custom Fields '_abplus_*'を独自追加
			foreach ( $abplus_spec_args as $spec_arg_v ) {
				$line_item .= $td_h. ''. $td_f; //以前のCFは使わないので値は空に(これらのCF列がある前提でEXCELマクロを組んでいるので列だけ残す)
			}

			$line_item .= apply_filters( 'usces_filter_downloadcsv_itemvalue', '', $post_id );
			$line_item = apply_filters( 'usces_filter_downloadcsv_add_itemvalue', $line_item, $post_id, $post );

			//Item Options
			$line_options = '';
			$option_meta = usces_get_opts( $post_id, 'sort' );
			foreach( $option_meta as $option_value ) {
				$value = '';
				if( is_array($option_value['value']) ) {
					foreach( $option_value['value'] as $k => $v ) {
						$v = usces_change_line_break( $v );
						$values = explode("\n", $v);
						foreach( $values as $val ) {
							$value .= $val.$sp;
						}
					}
					$value = rtrim($value, $sp);
				} else {
					$value = usces_change_line_break( $option_value['value'] );
					$value = str_replace("\n", ';', $value);
				}
				$line_options .= $td_h.usces_entity_decode($option_value['name'], $ext).$td_f;
				$line_options .= $td_h.$option_value['means'].$td_f;
				$line_options .= $td_h.$option_value['essential'].$td_f;
				$line_options .= $td_h.usces_entity_decode($value, $ext).$td_f;
			}

			//SKU
			$sku_meta = $usces->get_skus( $post_id, 'sort' );
			foreach( $sku_meta as $sku_value ) {
				$line_sku  = $td_h.$sku_value['code'].$td_f;
				$line_sku .= $td_h.usces_entity_decode($sku_value['name'], $ext).$td_f;
				$line_sku .= $td_h.usces_crform($sku_value['cprice'], false, false, 'return', false).$td_f;
				$line_sku .= $td_h.usces_crform($sku_value['price'], false, false, 'return', false).$td_f;
				$line_sku .= $td_h.$sku_value['stocknum'].$td_f;
				$line_sku .= $td_h.$sku_value['stock'].$td_f;
				$line_sku .= $td_h.usces_entity_decode($sku_value['unit'], $ext).$td_f;

				//advanceフィールドの商品仕様を独自追加
				if ( isset( $sku_value['advance'] ) && $sku_value['advance'] ) { //商品仕様フィールドに値があれば(全6項目未入力でも##×5回のtextが入るので実質的に旧データの場合のエラー対策)
					$spec_value_ary = AbplusSpec::convert_array( $sku_value['advance'] ); //区切り文字で分割したstringを要素として配列化
				} else { //商品仕様の値がなければ(旧データの場合)
					$spec_value_ary = AbplusSpec::convert_array( '##########' );
				}

				//商品仕様の値の入った列を追加
				foreach ( $spec_value_ary as $spec_val ) { //要素は[商品仕様項目名:その値]
					$line_sku .= $td_h. $spec_val. $td_f;
				}

				//順序を独自追加
				$line_sku .= $td_h. $post->menu_order. $td_f;

				$line_sku .= $td_h.$sku_value['gp'].$td_f;
				if( usces_is_reduced_taxrate() ) {
					$line_sku .= $td_h.usces_csv_get_sku_applicable_taxrate( $sku_value ).$td_f;
				}
				$line_sku .= apply_filters( 'usces_filter_downloadcsv_skuvalue', '', $sku_value );
				$line_sku = apply_filters( 'usces_filter_downloadcsv_add_skuvalue', $line_sku, $sku_value );
				$line .= $tr_h.$line_item.$line_sku.$line_options.$tr_f.$lf;
			}
			if( $usces->options['system']['csv_encode_type'] == 0 ) {
				$line = mb_convert_encoding( $line, apply_filters( 'usces_filter_output_csv_encode', 'SJIS-win'), "UTF-8" );
			}
			print($line);

			if( ob_get_contents() ) {
				ob_flush();
				flush();
			}
			$line = '';
			wp_cache_flush();
		}
		$end = $table_f;
	}

	//==========================================================================

	if( $usces->options['system']['csv_encode_type'] == 0 ) {
		$end = mb_convert_encoding( $end, apply_filters( 'usces_filter_output_csv_encode', 'SJIS-win'), "UTF-8" );
	}
	print($end);
	unset($rows, $DT, $line, $line_item, $line_options, $line_sku);
	exit();
}

?>