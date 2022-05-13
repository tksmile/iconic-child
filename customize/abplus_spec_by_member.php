<?php
/**
 * skuに独自項目名を追加
 * file : usc-e-shop/functions/item_post.php
 * fn : list_item_sku_meta(skuがある場合の出力関数),item_sku_meta_form(sku新規追加の場合の出力関数)
 * @return string 項目名を表すth
 */
add_filter( 'usces_filter_sku_meta_form_advance_title', function() {
	return '<th colspan="2">商品仕様</th>'; //項目を追加
} );

/**
 * table#newsku(新しいSKUの追加)に独自項目の入力フィールドを追加
 * file : usc-e-shop/functions/item_post.php
 * fn : item_sku_meta_form
 * @return string 項目の入力フィールドを表すtd
 */
add_filter( 'usces_filter_sku_meta_form_advance_field', function() {

	$ratio_args = AbplusSpec::$ratio_styles;
	$ratio_args_json = json_encode( $ratio_args ); //jsで扱えるようにjson形式にする
	$spec_args = AbplusSpec::$spec_args;
	$spec_args_json = json_encode( $spec_args );
	?>

	<script>
		var _ratio_args_json = JSON.parse( '<?php echo $ratio_args_json; ?>' ); //配列として読み込むので''で括らない
		var _spec_args_json = JSON.parse( '<?php echo $spec_args_json; ?>' );
		var separator = '<?php echo AbplusSpec::$delimiter; ?>'; //advanceフィールドの区切り文字
	</script>
	<!--<script src="<?php //echo get_stylesheet_directory_uri(); ?>/js/sku_meta_form_advance_field.js" defer></script>-->
	<style type="text/css">
		.item-sku-price { width: 20%; }
		.item-sku-zaikonum { width: 16%; }
		.item-sku-advance { padding: 0; }
		.item-sku-smallfield { background-color: #F1F1F1; }
		.item-sku-smallfield:first-child { margin-top: 0; }
		.span--before { display: inline-block; width: 25%; font-weight: bold; }
		.span--after { display: inline-block; width: 19%; }
		.item-sku-smallfield__ctr { display: inline-block; width: 55%; font-size: 1em; }
		.skuname.metaboxfield, .item-sku-price .skuprice.metaboxfield, .skuzaiko.metaboxfield { background-color: #ffffc7 }
	</style>
	<td colspan="2" class="item-sku-advance">
		<input name="newskuadvance" type="text" id="newskuadvance" class="newskuadvance metaboxfield splitfield" /></td>
	<?php

} );

/**
 * 初期状態のsku入力フィールド
 * file : usc-e-shop/functions/item_post.php
 * fn : _list_item_sku_meta_row
 * @return string td
 */
add_filter( 'usces_filter_sku_meta_row_advance', 'add_new_sku_meta_row_advance', 10, 2 ); //フィールドを追加
function add_new_sku_meta_row_advance( $default_field, $sku ) {
	var_dump( $sku );
	$metaname = 'itemsku['. $sku["meta_id"]. '][skuadvance]';
	return '<td colspan="2" class="item-sku-advance"><input name="'. $metaname. '" type="text" id="'. $metaname. '" class="newskuadvance metaboxfield splitfield" value="'. $sku["advance"]. '"/></td>';
}

/**
 *file : usc-e-shop/functions/item_post.php
 *fn : add_item_sku_meta
 *@param array $value sku値の配列
 *@return array $value newskuadvanceの値を追加したsku値の配列
*/
add_filter( 'usces_filter_add_item_sku_meta_value', function( $value ) {
	$skuadvance = isset( $_POST['newskuadvance'] ) ? $_POST['newskuadvance'] : '';
	$value['advance'] = $skuadvance;
	return $value;
} ); //新規項目を作成


/**
 *$_POST['skuadvance']値をデータベース保存用のカラムadvanceに代入して渡す
 *file : usc-e-shop/functions/item_post.php
 *fn : up_item_sku_meta
 *@param array $value skuデータが入った配列(advance値はまだ入ってない)
 *@return array $value (advance値を追加した)skuデータが入った配列
*/
add_filter( 'usces_filter_up_item_sku_meta_value', function( $value ) {
	$skuadvance = isset( $_POST['skuadvance'] ) ? $_POST['skuadvance'] : '';
	$value['advance'] = $skuadvance;
	return $value;
} );

/**
 *データベースに保存するsku値の配列にadvanceフィールドのデータを追加して保存する
 *file : usc-e-shop/functions/item_post.php
 *fn : item_save_metadata
 *@param array $skus データベースに保存するsku値の配列
 *@param int $meta_id
 *@return array $skus advanceフィールドのデータを追加したsku値の配列
*/
add_filter( 'usces_filter_item_save_sku_metadata', function( $skus, $meta_id ) {
	$skuadvance = ( isset( $_POST['itemsku'][$meta_id]['skuadvance'] ) ) ? $_POST['itemsku'][$meta_id]['skuadvance'] : '';
	$skus['advance'] = $skuadvance;
	return $skus;
}, 10, 2 );

/**
 *基になる枝番なしのSKUコード、会員ランク別の売価、会員ランク別の商品仕様データを取得
 *@param string $cs_status_abbr 会員ランク名の略文字()
 *@return array array( $org_sku_code, $sku_price_by_member, $sku_spec_by_member )
   *@return string $org_sku_code 基になる枝番なしのSKUコード
   *@return string $sku_price_by_member 会員ランク別の売価
   *@return string $sku_spec_by_member 会員ランク別の商品仕様データ
*/
function abplus_get_sku_by_member( $cs_status_abbr ) {
	global $post, $usces;
	$skus = $usces->get_skus( $post->ID ); //商品に付いた全skuデータの配列
	$org_sku_code = ''; //(枝番のない)基になる正規のSKUコード
	$sku_price_by_member = '';
	$sku_spec_by_member = ''; //(string)会員ランク別の商品仕様データ

	foreach ( $skus as $index => $sku ) {
		$skuCode_ary = explode( '-', $sku['code'] ); //SKUコードを-で分割した文字列の入った配列
		$skuCode_ary_count = count( $skuCode_ary ); //要素数が、1：枝番なし,2：会員ランクを表す枝番あり
		if ( $index === 0 && $skuCode_ary_count === 1 ) { //先頭のskuで、要素1つなら
			$org_sku_code = $sku['code']; //基になる正規のSKUコード
		} elseif ( $skuCode_ary_count === 2 && $skuCode_ary[1] === $cs_status_abbr ) { //要素2つで、2つ目の要素(枝番)が会員ランク名の略文字と同じなら
			if ( isset( $sku['advance'] ) && $sku['advance'] ) { //advanceフィールドに値がセットされていれば
				$sku_price_by_member = $sku['price']; //会員ランク別の売価
				$sku_spec_by_member = $sku['advance']; //会員ランク別の商品仕様データ
			}
		}
	}

	return array( $org_sku_code, $sku_price_by_member, $sku_spec_by_member );
}

/**
 * 会員ランク別に設定された商品仕様情報を会員ランク別に表示する(wc_item_single.php,item-category.phpで使うことを想定)

 * @param object $post 投稿情報
 * @param string $org_sku_code 基になる枝番なしのSKUコード
 * @param string $sku_spec_by_member 会員ランク別の商品仕様データ
 * @return echo
 */
function echo_abplus_spec_by_member( $post, $org_sku_code, $sku_spec_by_member ) {

	$isCategory_css = ( is_category() ) ? ' is_category' : ''; //category.phpならline-height調整用style名を変数に

	echo '<dl class="pt20 product-spec-box', $isCategory_css, '">';
	echo '<div class="clearfix">';
	echo '<dt class="u-left">[商品コード]</dt>';
	echo '<dd class="wp-smiley">&nbsp;', $post->_itemCode, '</dd>';
	echo '</div>';
	if ( empty( $isCategory_css ) ) { //line-height調整用style名が空(wc_item_single.php)なら
		echo '<div class="clearfix">';
		echo '<dt class="u-left">[商品名]</dt>';
		echo '<dd class="wp-smiley">&nbsp;', $post->_itemName, '</dd>';
		echo '</div>';
	}

	//$metabox['args']の商品仕様データ配列の最後尾にvalueを追加
	/*$index_num_of_val = ''; //商品仕様データの値が入る要素のindex番号
	if ( $sku_spec_by_member ) {
		$sku_spec_by_member_ary = explode( '##', $sku_spec_by_member ); //(array)会員ランク別の商品仕様データ
		$i = 0;
		foreach ( $metabox['args'] as $key => $value ) {
			if ( $i === 0 ) $index_num_of_val = count( $value ); //index番号取得は初回だけでOK
			if ( isset( $sku_spec_by_member_ary[$i] ) ) {
				$metabox['args'][$key][] = $sku_spec_by_member_ary[$i];
			}
			$i++;
		}
	}*/

	$Spec = new AbplusSpec();
	$spec_args = AbplusSpec::$spec_args;
	$spec_values = $Spec->get_values( $sku_spec_by_member );
	$ratio_style_args = AbplusSpec::$ratio_styles;

	foreach ( $spec_args as $key => $value ) {
		//var_dump( $value );
		if ( isset( $spec_values[$key] ) && $spec_values[$key] ) : //値が入力されてる場合だけ表示 ?>
			<div class="clearfix<?php
				if ( $key === '掛率' ) :
					echo ' ratio ratio--', $ratio_style_args[$spec_values[$key]]; ?>">
					<dt class="u-left fa-thumbs-o-up"></dt>
					<dd class="wp-smiley ratio__body">&nbsp;<?php echo esc_html( $spec_values[$key]. $value[1] ); ?></dd><?php
				else : //掛率以外
					if ( $key === '商品入数' ) :
						echo ' p-header__bar mb5 pb5';
					endif; ?>">
					<dt class="u-left">[<?php echo $key; ?>]</dt>
					<dd class="wp-smiley">&nbsp;<?php echo esc_html( $spec_values[$key]. $value[1] );
					?></dd><?php
				endif; ?>
			</div><?php
		endif;

	} //end foreach

	echo '<div class="clearfix">';
	echo '<dt class="u-left">[JANコード]</dt>';
	echo '<dd class="wp-smiley">&nbsp;', esc_html( $org_sku_code ), '</dd>';
	echo '</div>';
	echo '</dl>';

}

?>