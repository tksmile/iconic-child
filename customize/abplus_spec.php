<?php
/**
 * AbplusSpec Class
 */
class AbplusSpec {

	public static $spec_args = array(
		'掛率' => ['ratio', '', 'select', true ], //0=>name属性,1=>単位,2=>type属性,3=>入力値が必要か？(true) 基の(先頭の)SKUの値を使うか？(false)
		'通常単価' => ['normal_price', '円', 'number', true ],
		'販売単価' => ['unit_price', '円', 'number', true ],
		'商品入数' => ['quantity', '個入り', 'number', false ],
		'賞味期限' => ['best_before', '', 'text', false ],
		'ケース入数' => ['case_quant', '', 'text', false ]
	);

	public $spec_values;
	public static $delimiter = '##'; //SKUのadvanceフィールド内での区切文字

	public static $ratio_styles = array(
		'4.0掛特価' => 'orange',
		'4.5掛特価' => 'orange',
		'4.8掛特価' => 'orange',
		'5.0掛特価' => 'red',
		'5.3掛特価' => 'red',
		'5.5掛特価' => 'red',
		'6.0掛' => 'blue',
		'6.5掛' => 'blue',
		'定番商品' => 'blue',
		'限定商品' => 'green'
	);

	/*constructor*/
	public function __construct() {
	}

	/**
	 * 商品仕様の値(＝SKUのadvanceフィールドの値)を文字列から配列に変換する
	 * @param string $advance_str SKUのadvanceフィールドの値
	 * @return array $advance_ary 区切り文字で分割して配列化したadvanceフィールドの値
	 */
	public static function convert_array( $advance_str ) {

		$advance_ary = explode( self::$delimiter, $advance_str ); //区切り文字で分割して要素として配列化
		return $advance_ary;

	}

	/**
	 * 商品に設定された独自仕様の値を取得
	 * @param string $advance_str SKUのadvanceフィールドの値
	 * @return array $spec_values {仕様名:値}の連想配列
	 */
	public function get_values( $advance_str ) {

		$advance_ary = self::convert_array( $advance_str ); //自クラス内で使う場合はself::で呼び出す
		$spec_values = []; //商品仕様の{仕様名:値}を入れる連想配列
		$i = 0;
		foreach ( self::$spec_args as $key => $ary ) {
			if ( isset( $advance_ary[$i] ) ) { //値が設定されていれば
				$spec_values[$key] = $advance_ary[$i]; //仕様名=>値の要素を入れ足す
			} else {
				$spec_values[$key] = '';
			}
			$i++;
		}
		return $spec_values;

	}

}


/*商品仕様カスタムフィールドで扱うデータを定義する配列作成*/
/*function get_abplus_spec_args() {
	$abplus_spec_args = array( //$metabox['args']
		'掛率' => ['ratio', '', 'select', true ], //0=>name属性,1=>単位,2=>type属性,3=>入力値が必要か？(true) 基の(先頭の)SKUの値を使うか？(false)
		'通常単価' => ['normal_price', '円', 'number', true ],
		'販売単価' => ['unit_price', '円', 'number', true ],
		'商品入数' => ['quantity', '個入り', 'number', false ],
		'賞味期限' => ['best_before', '', 'text', false ],
		'ケース入数' => ['case_quant', '', 'text', false ]
	);
	return $abplus_spec_args;
}

function get_abplus_ratio_args() {
	$ratio_style_args = array(
		'4.0掛特価' => 'orange',
		'4.5掛特価' => 'orange',
		'4.8掛特価' => 'orange',
		'5.0掛特価' => 'red',
		'5.3掛特価' => 'red',
		'5.5掛特価' => 'red',
		'6.0掛' => 'blue',
		'6.5掛' => 'blue',
		'定番商品' => 'blue',
		'限定商品' => 'green'
	);
	return $ratio_style_args;
}*/

/*商品仕様のカスタムフィールド入力メタボックスを定義*/
/*add_action( 'admin_menu', function() {
	$args = AbplusSpec::$spec_args;
	add_meta_box(
		'spec_fields', //表示する入力ボックス(div)のid属性値
		'商品仕様', //titleラベル
		'insert_spec_cf', //入力エリアのHTMLを出力する関数名
		'post', //表示する投稿タイプ
		'advanced', //表示位置
		'high',
		$args //$metabox['args']
	);
} );*/

/*商品仕様カスタムフィールドの値を出力(管理画面では入力メタボックスを,wc_item_single.phpではdlを)*/
function insert_spec_cf( $post, $metabox ) {

	$ratio_style_args = AbplusSpec::$ratio_styles;

	if ( is_admin() ) { //管理画面なら

		echo '<table class="iteminfo_table">';
		wp_nonce_field( 'spec_action','spec_nonce' ); //nonce(トークン)を設定してtype=hiddenで出力
		foreach ( $metabox['args'] as $key => $value ) {
			${'abplus_'. $value[0]} = ( get_post_meta( $post->ID, "_abplus_{$value[0]}", true ) ); ?>

			<tr>
				<th><?php echo $key; ?></th>
				<td><?php

					if ( $value[2] === 'select' ) : //$value[2]はinputのtype属性

						?><select class="" name="_abplus_<?php echo $value[0]; ?>">
							<option value="">選択してください。</option><?php
							foreach ( $ratio_style_args as $ratio_style_args_k => $ratio_style_args_v ) :
								?><option value="<?php echo $ratio_style_args_k; ?>"<?php if ( $abplus_ratio && $abplus_ratio === $ratio_style_args_k ) echo ' selected'; ?>><?php echo $ratio_style_args_k; ?></option><?php
							endforeach; ?>
						</select><?php

					else : //number,text
						?><input type="<?php echo $value[2]; ?>" name="_abplus_<?php echo $value[0]; ?>" value="<?php if ( ${'abplus_'. $value[0]} ) echo ${'abplus_'. $value[0]}; ?>" size="20" <?php if ( $value[0] === 'best_before' ) echo 'placeholder="2021.9.30"'; ?>/><?php
					endif; echo $value[1];

				?></td>
			</tr><?php

		} //end foreach ( $metabox['args'] as $key => $value )
		echo '</table>';

	} else { //wc_item_single.php or category.phpなら

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

		foreach ( $metabox['args'] as $key => $value ) {
			${'abplus_'. $value[0]} = ( get_post_meta( $post->ID, "_abplus_{$value[0]}", true ) );

			if ( ${'abplus_'. $value[0]} ) : //データが入力されてる場合だけ表示 ?>
				<div class="clearfix<?php
					if ( $key === '掛率' ) :
						echo ' ratio ratio--', $ratio_style_args[${'abplus_'. $value[0]}]; ?>">
						<dt class="u-left fa-thumbs-o-up"></dt>
						<dd class="wp-smiley ratio__body">&nbsp;<?php echo ${'abplus_'. $value[0]}, $value[1]; ?></dd><?php
					else :
						?>">
						<dt class="u-left">[<?php echo $key; ?>]</dt>
						<dd class="wp-smiley">&nbsp;<?php echo ${'abplus_'. $value[0]}, $value[1];
						?></dd><?php
					endif; ?>
				</div><?php
			endif;

		} //end foreach

		echo '<div class="clearfix">';
		echo '<dt class="u-left">[JANコード]</dt>';
		echo '<dd class="wp-smiley">&nbsp;', esc_html( usces_the_itemSku ( 'return' ) ), '</dd>';
		echo '</div>';
		echo '</dl>';

	} //end if ( is_admin() ) else
}

/*商品仕様カスタムフィールドの値を保存*/
/*add_action( 'save_post', function( $post_id ) {

	$spec_nonce = ( isset( $_POST['spec_nonce'] ) ) ? $_POST['spec_nonce'] : null; //nonceを取得
	if ( ! wp_verify_nonce( $spec_nonce, 'spec_action' ) ) { //
		return;
	}

	$abplus_spec_args = AbplusSpec::$spec_args;
	foreach ( $abplus_spec_args as $key => $value ) {
		if ( isset( $_POST["_abplus_{$value[0]}"] ) ) {
			${'_abplus_'. $value[0]} = trim( $_POST["_abplus_{$value[0]}"] );
			update_post_meta( $post_id, "_abplus_{$value[0]}", ${'_abplus_'. $value[0]} );
		} else { //未入力の場合
			delete_post_meta( $post_id, "_abplus_{$value[0]}" ); //値を削除
		}
	}

} );*/

?>