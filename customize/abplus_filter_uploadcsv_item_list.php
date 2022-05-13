<?php
/*独自CF(_abplus_*)のカラム数5列を確保するため、_abplus_*の後にあるSKUデータのカラム番号に+5する
 file : plugins/usc-e-shop/functions/define_function.php
 fn : usces_item_uploadcsv
*/
add_filter( 'usces_filter_uploadcsv_add_item_field_num', function( $add_field_num ) {
	$add_field_num = count( AbplusSpec::$spec_args ); //postmeta用の列数
	return $add_field_num;
} );

/*アップデートモード時($mode=='upd')に削除される既存metaデータを定義する配列に独自CF(_abplus_*)を追加
 file : plugins/usc-e-shop/functions/define_function.php
 fn : usces_item_uploadcsv
*/
add_filter( 'usces_filter_uploadcsv_delete_postmeta', function( $meta_key_table ) {
	$merge_table = [];
	foreach ( AbplusSpec::$spec_args as $spec_ary ) {
		$merge_table[] = '_abplus_'. $spec_ary[0];
	}
	//$meta_key_table = array_merge( $meta_key_table, [ '_abplus_ratio', '_abplus_normal_price', '_abplus_unit_price', '_abplus_quantity', '_abplus_best_before'] );
	$meta_key_table = array_merge( $meta_key_table, $merge_table );
	return $meta_key_table;
} );

/*独自CF(_abplus_*)のデータを保存する
 file : plugins/usc-e-shop/functions/define_function.php
 fn : usces_item_uploadcsv
*/
add_action( 'usces_action_uploadcsv_itemvalue', function( $post_id, $datas ) {
	global $usces;
	$datas[27] = ( $usces->options['system']['csv_encode_type'] == 0 ) ? esc_sql( trim( mb_convert_encoding( $datas[27], 'UTF-8', 'SJIS' ) ) ) : $datas[27] = esc_sql( trim( $datas[27] ) ); //27掛率はマルチバイト文字データなのでエンコードしないと保存されない
	$i = 0;
	foreach ( AbplusSpec::$spec_args as $spec_ary ) {
		update_post_meta( $post_id, '_abplus_'. $spec_ary[0], $datas[27 + $i] );
		$i++;
	}
	/*update_post_meta( $post_id, '_abplus_ratio', $datas[27] );
	update_post_meta( $post_id, '_abplus_normal_price', $datas[28] );
	update_post_meta( $post_id, '_abplus_unit_price', $datas[29] );
	update_post_meta( $post_id, '_abplus_quantity', $datas[30] );
	update_post_meta( $post_id, '_abplus_best_before', $datas[31] );
	update_post_meta( $post_id, '_abplus_case_quant', $datas[32] );*/
}, 10, 2 );

/**
 *file : plugins/usc-e-shop/functions/define_function.php
 *fn : usces_item_uploadcsv
 *@param array $skuvalue
 *@param array $datas
 *@return array $skuvalue
*/
/*add_filter( 'usces_filter_uploadcsv_skuvalue', function( $skuvalue, $datas ) {
	echo '<script>';
	echo 'console.log('. json_encode( $datas ) .')';
	echo '</script>';
	//var_dump( $datas );
	$skuvalue['advance'] = $datas[33];
	return $skuvalue;
}, 10, 2 )*/
?>