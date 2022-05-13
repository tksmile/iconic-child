<?php
if ( ! defined( 'USCES_VERSION' ) ) {
	return;
}

/**
 * 親テーマのstyle.cssと商品一覧・詳細ページ用のinline styleを読み込む
 */
add_action( 'wp_enqueue_scripts', function() {
	wp_enqueue_style( 'iconic-style', get_template_directory_uri(). '/style.css', array() );
	if ( is_singular( 'post' ) || is_category() ) {
		wp_add_inline_style(
			'iconic-style',
			'.product-spec-box { display: flex; flex-direction: column; }
			.product-spec-box.is_category { line-height: 1.6; }
			.fa-thumbs-o-up::before { font-family: "design_plus"; content: "\f164"; }
			.ratio .fa-thumbs-o-up::before { font-size: 21px; line-height: 1.5; padding-left: 5px; padding-right: 5px; }
			.ratio { color: #fff; order: -1; }
			.ratio--blue { background-color: #1D2089; }
			.ratio--red { background-color: #CC141B; }
			.ratio--orange { background-color: #8A1D21; }
			.ratio--green { background-color: #218A1D; }
			.p-item-archive__item-info .ratio__body { line-height: 2.4; }
			.widget_welcart_calendar table { width: 96%; float: none; }
			.usces_calendar caption { font-size: 120%; }
			.usces_calendar td { line-height: 2.4; }
			.widget_welcart_calendar .businessday { background-color: #fddde6; }
			.p-entry-item__mainimage img { max-width: 400px; }
			.p-item-archive__item__a { padding-left: 30px; padding-right: 30px; }
			.p-item-archive__item-info .p-entry-item__cart-button { margin-top: 5px; }
			a.usces_login_a { display: block; background-color: #ccc; color: #fff; margin-top: 20px; padding: 10px; text-align: center; }
			.p-item-archive__item-info a.usces_login_a { margin-bottom: 20px; }
			.widget_welcart_category .current-cat > a { background-color: rgba(0, 151, 204, 0.15); }
			div#wdgctToCheckout a { background-image: none; background-color: #CC3600; width: 50%; height: auto; padding-top: 4px; padding-bottom: 4px; }
			div#wdgctToCheckout a::before { content: "\e93a"; font-family: "design_plus"; font-size: 1.2em; margin-right: 6px; vertical-align: -3%; }
			div#wdgctToCheckout a:hover { background-color: #CC7066; color: #fff; text-decoration: none; }
			td.widgetcart_trush { min-width: 12px; }
			.widgetcart_rows a.cart_trush img { width: 100%; }'
		);
	}
} );

/**
 * 管理画面の特定ページで独自jsを読み込ませる
 * @param string $hook hook_suffix(管理画面のページ識別子)
 */
add_action( 'admin_enqueue_scripts', function( $hook ) {
	//var_dump( $hook );
	global $post;
	//var_dump( wp_get_post_terms( $post->ID, 'category' ) );

	//if ( strpos( $hook, 'usces_iteme' ) !== false && isset( $_REQUEST['action'] ) ) { //商品編集ページ, 新規商品ページなら
	if ( ( $hook === 'welcart-shop_page_usces_itemedit' && isset( $_REQUEST['action'] ) ) || $hook === 'welcart-shop_page_usces_itemnew' ) { //商品編集ページ, 新規商品ページなら
		wp_enqueue_script( 'sku_meta_form_advance', get_stylesheet_directory_uri(). '/js/sku_meta_form_advance_field.js', array( 'jquery-core' ), false, true );
	} elseif ( $hook === 'welcart-management_page_abplus_customer_status_regist' || $hook === 'welcart-management_page_abplus_customer_status_settings' ) { //会員ランク区分登録ページ, 会員ランク別表示項目設定ページなら
		wp_enqueue_script( 'add_del_admin_page_input', get_stylesheet_directory_uri(). '/js/add_del_admin_page_input.js', array(), false, true );
	}

} );


//$hook_suffixを画面に表示
/*add_action( 'admin_notices', 'wps_print_admin_pagehook' );
function wps_print_admin_pagehook() {
	global $hook_suffix;
	if ( !current_user_can( 'manage_options' ) )
		return;
	echo <<< EOS
<div class="error">hook_suffix = <b style="color: red;">{ $hook_suffix }</b></div>
EOS;
}*/

/**
 *
 *file : wp-admin/admin-header.php
 *@param string $hook_suffix 管理画面のページ接尾辞
 *@return echo inline style
*/
/*add_action( 'admin_enqueue_scripts', function( $hook_suffix ) {
	if ( false !== strpos( $hook_suffix, 'usces_item' ) && isset( $_REQUEST['action'] ) ) { //商品編集ページ,新規商品ページなら、必須SKU入力項目の背景に色を付ける
	echo <<<EOS
<style type="text/css"><!--
.skuname.metaboxfield, .item-sku-price .skuprice.metaboxfield, .skuzaiko.metaboxfield { background-color: #ffffc7 };
--></style>
EOS;
	}
} );*/


/*独自の商品仕様(通常単価,販売単価,商品入数,賞味期限)を定義・設定・保存・出力する*/
require_once 'customize/abplus_spec.php';

/*会員ランク別の独自商品仕様を入力・保存・出力*/
require_once 'customize/abplus_spec_by_member.php';

/*独自商品仕様のカスタムフィールドを含めた商品リスト(商品マスター)をCSVファイルでダウンロードできるようにする*/
require_once 'customize/abplus_download_item_list.php';

/*独自仕様の受注明細リストCSVファイルをダウンロードする*/
require_once 'customize/abplus_download_cv_product_list.php';

/*独自商品仕様のカスタムフィールドを含めた商品リスト(商品マスター)をCSVファイルでアップロードできるようにする*/
//require_once 'customize/abplus_filter_uploadcsv_item_list.php'; //カスタムフィールドにより商品仕様を管理(フックによるカスタマイズ)
require_once 'customize/abplus_uploadcsv_item_list.php'; //会員ランク別にSKUを作成して商品仕様を管理

/*カート内の商品をカテゴリーごとに、商品の種類数、商品入数の合計値を表示する*/
require_once 'customize/abplus_widgetcart_filter.php';

/*独自会員ランク区分を登録、会員ランク区分別に表示するカテゴリー、除外する商品コードを設定
 カテゴリーウィジェットに表示するリストを会員ランク別に設定した内容に変更する*/
require_once 'customize/abplus_customer_status.php';

/*会員マイページの注文履歴tableにcsv,pdfのダウンロードフォームを設置する*/
require_once 'customize/abplus_download_member_order_history.php';

/*item-category.phpをカスタマイズ
表示する商品を会員ランク別に変更、並び順変更、カート処理に必要なjsをfooterに出力*/
require_once 'customize/abplus_customize_item_category.php';

/*受注データ・受注リストの各種カスタマイズ
受注リストの文言変更、在庫管理機能を独自仕様(すべてのSKUの在庫数、在庫状態を連動・共通化)に変更*/
require_once 'customize/abplus_orderDataList_customize.php';
require_once 'customize/ajax.php';

/*会員ランクに対応したカスタム分類を設定*/
//require_once 'customize/abplus_custom_taxonomy.php';

/**
 * 注文データ関連の各種PDFファイルの生成を担当するファイルをデフォルト仕様から変更する
 * usc-e-shop/includes/order_print.php → iconic-child/inc/order_print.php

 * file : usc-e-shop/classes/usceshop.class.php
 * fn : main
 * @return url
 */
add_filter( 'usces_filter_orderpdf_path', function() {
	return STYLESHEETPATH. '/inc/order_print.php';
} );


/**
 *会員マイページの電話番号入力欄をreadonlyに
 *file : usc-e-shop/functions/template_func.php
 *fn : uesces_addressform
 *@param string $formtag 住所入力フォームのhtml
 *@param string $type 関数を実行するpage
 *@return string $formtag readonly属性を追加した住所入力フォームのhtml
*/
add_filter( 'usces_filter_apply_addressform', function( $formtag, $type ) {
	if ( $type === 'member' && $_GET === array( 'page_id' => USCES_MEMBER_NUMBER ) ) { //memberページで、かつ、会員情報編集ページ(クエリ情報にusces_page=newmemberがない)なら
		$formtag = str_replace( 'id="tel"', 'id="tel" readonly', $formtag );
	}
	return $formtag;
}, 10, 2 );

/**
 * 配列がindex配列か？チェックする関数
 */
function is_index_array( $post_ary ) {
	foreach ( $post_ary as $k => $v) {
		if ( preg_match( '/^\d+$/u', $k ) == 0 ) { //keyが数字(Index配列)以外なら
			return false;
			break;
		}
	}
	return true; //すべてのkeyがpreg_match関数の戻り値1(＝index配列)ならtrueを返す
}

/**
 * 多次元配列の、1.空要素を削除、2.index配列ならindex番号ふり直し、をして歯抜けを直す関数
 * $_POSTデータを送信直前に処理する
 */
function array_filter_recursive( $array, $callback=null, $unset_empty_array=false ) {

	//コールバック関数が未指定の場合
	if ( !$callback ) {
		//コールバック関数を指定する
		$callback = function( $value ) {
			return !empty( $value );
		};
	}

	foreach ( array_keys( $array ) as $key ) {

		if ( is_array( $array[$key] ) ) {

			//多次元配列の場合は再帰処理を行う
			$array[$key] = array_filter_recursive( $array[$key], $callback, $unset_empty_array );

			//$unset_empty_arrayがtrueかつ配列が空なら削除
			if ( $unset_empty_array && empty( $array[$key] ) ) {
				unset( $array[$key] );
			}

			//index配列ならindex番号をふり直して歯抜け直す
			if ( isset( $array[$key] ) && is_index_array( $array[$key] ) === true ) { //Index配列なら(issetチェックは直前のunsetで$array[$key]が既に無い可能性があるから)
				$array[$key] = array_values( $array[$key] ); //indexをふり直す
			} //end if ( function( $post_ary )  )

		//指定したコールバック関数をコールし、戻り値がfalseなら削除
		} else if ( !call_user_func( $callback, $array[$key] ) ) {
			unset( $array[$key] );

		} //end if ( is_array( $array[$key] ) )

	} //end foreach ( array_keys( $array ) as $key )

	return $array;
}

/*postされた商品コードが存在するか？ チェックして、なければメッセージを返す関数*/
function check_item_code( $array ) {
	$message = '';
	foreach ( $array as $value ) {
		if ( ! empty( $value['item_code'] ) ) {

			//_itemCodeのmeta_valueをすべて入れた配列を作成
			if ( ! isset( $item_code_ary ) ) { //セットされていなければ(＝初回だけ)
				global $wpdb;
				$item_code_ary = $wpdb->get_results(
					$wpdb->prepare(
					"SELECT meta_value
					 FROM {$wpdb->prefix}postmeta
					 WHERE meta_key = %s",
					'_itemCode'
					),
					ARRAY_A
				);
			}

			foreach ( $value['item_code'] as $code_v ) {
				foreach ( $item_code_ary as $ary_v ) { //_itemCodeデータ配列を走査する
					if ( $code_v === $ary_v['meta_value'] ) { //一致する商品コードがあれば
						$is_item_code = $code_v; //一致した商品コードを確認用変数に代入
						break;
					}
				}
				if ( ( !isset( $is_item_code ) ) || ( isset( $is_item_code ) && $is_item_code !== $code_v ) ) { //確認用変数がセットされていない(一致する商品コードが1つも見つかっていない段階)、又は、確認用変数がセットされていて、その変数に入っているコードと走査対象のコードが違う(1つでも一致する商品コードが見つかった段階)
					$message .= '<p><strong>入力した値'. $code_v. 'は存在しない商品コードです。</strong></p>';
				}
			}

		} //end if ( ! empty( $value['item_code'] ) )
	}
	return $message;
}


/**
 * 枝番のない基になる正規のSKUコードを取得する
 * @param string $skuCode skuコード
 * @return string $org_sku_code 枝番なしのskuコード
 */
function abplus_get_org_skuCode( $skuCode ) {
	$skuCode_ary = explode( '-', $skuCode ); //SKUコードを-で分割した文字列の入った配列
	$org_sku_code = $skuCode_ary[0]; //基になる正規のSKUコード(枝番なしの場合は要素1で元のSKUコードがそのまま入るのでOK)
	return $org_sku_code;
}

/**
 * カートテーブルでの商品表示名を変更
 * file : usc-e-shop/functions/template_func.php
 * fn : usces_member_history, usces_get_cart_rows, usces_get_confirm_rows
 */
add_filter( 'usces_filter_cart_item_name', 'abplus_change_cartItemName', 10, 2 );

/**
 * 受注メール、PDFでの商品表示名を変更
 * file : usc-e-shop/functions/function.php, usc-e-shop/includes/order_print.php(iconic-child/inc/order_print.php)
 * fn : usces_send_ordermail, usces_order_confirm_message, usces_pdf_out
 */
add_filter( 'usces_filter_cart_item_name_nl', 'abplus_change_cartItemName', 10, 2 );

/**
 * カートテーブル、受注メールでの商品表示名を変更
 * SKUコードを枝番なしに、商品コード・商品名・SKUコードの3つを表示

 * @param string $cartItemName 商品の表示名
 * @param array $args カートに入っている商品データの配列
 * @return string $cartItemName 変更した商品の表示名
*/
function abplus_change_cartItemName( $cartItemName, $args ) {
	global $usces;
	$cartItemName = $usces->getItemCode( $args['post_id'] ). ' '; //商品コード
	$cartItemName .= $usces->getItemName( $args['post_id'] ). ' '; //商品名
	$cartItemName .= abplus_get_org_skuCode( $args['sku'] ); //SKUコード

	$Spec = new AbplusSpec();
	$traces = debug_backtrace(); //呼び出し元を取得
	foreach ( $traces as $index_num => $trace ) {

		//出力対象がメール本文なら商品仕様項目を出力
		if ( $index_num !== 0 && isset( $trace['function'] ) && ( $trace['function'] === 'usces_send_ordermail' || $trace['function'] === 'usces_order_confirm_message' ) ) { //この関数abplus_change_cartItemNameの呼び出し元フックがある関数がusces_send_ordermailかusces_order_confirm_messageなら
			//var_dump( $args );
			//区切り文字で分割したstringを要素として配列化
			if ( $trace['function'] === 'usces_send_ordermail' ) {
				$spec_values = $Spec->get_values( $args['cart_row']['advance'] );
			} else { //usces_order_confirm_message関数内では
				$spec_values = $Spec->get_values( $args['cart_row']['advance'][0] ); //['advance']より1次元下の階層の配列にadvanceのテキスト値が保存されているので対応変える
			}
			$cartItemName .= "\r\n". '通常単価 : '. $spec_values['通常単価']. ', 販売単価 : '. $spec_values['販売単価']. ', 商品入数 : '. $spec_values['商品入数'];
			break;
		}

	}

	return $cartItemName;
}

/**
 * 商品をカートに入れた時にadvanceフィールドの値も一緒に入れてusces_orderテーブルのorder_cartフィールドに保存されるようにする
 * file : usc-e-shop/classes/cart.class.php
 * fn : inCart

 * @param string $serial post_idとskuCodeのシリアルデータ。$_SESSION['usces_cart']のkeyになる(ex:a:1:{i:7261;a:1:{s:16:"4903015534507-00";i:0;}})
*/
add_action( 'usces_action_after_inCart', function( $serial ) {
	global $usces;
	$ids = array_keys( $_POST['inCart'] );
	$post_id = (int)$ids[0];
	$skus = array_keys( $_POST['inCart'][$post_id] );
	$sku_code = $skus[0];

	//$_SESSIONにadvanceフィールドの値を設定
	$_SESSION['usces_cart'][$serial]['advance'] = $usces->getItemSkuAdvance( $post_id, $sku_code );
} );

/**
 * Utility
 * (不完全な商品がカートに入って削除できない場合などに)カート内の商品を全削除
*/
function my_delete_cart() {
	global $usces; ?>
	<form action="" method="post">
		<button  type="submit" name='remove' class='delbuttn'>カートの全削除</button>
	</form>
	<?php
	if ( isset($_POST['remove'] ) ):
		$usces->cart->clear_cart(); //カートの全削除関数
		$_POST = array(); //リセット
	endif;
}

?>
