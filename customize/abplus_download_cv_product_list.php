<?php
/**
 * 受注リストに独自仕様の受注明細csvダウンロードボタンを設置
 * file : plugins/usc-e-shop/includes/orderlist_page.php
 * @param array $list_option = get_option( 'usces_orderlist_option' )引数不要では？
 * @return echo
*/
add_action( 'usces_action_dl_list_table', function( $list_option ) {
	echo '<label style="display: none;"><input type="radio" name="dl_type" value="csv" checked> </label><input type="submit" name="abplus_dl" id="dl_productlist_abplus" class="button button-primary" value="アブカン受注明細CSVダウンロード" />';
	//var_dump( $_POST );
} );

/**
 *「アブカン受注明細CSVダウンロード」ボタンのクリックをトリガーにダウンロードを実行する関数
 * @return
*/
function abplus_download_product_list() {
	if ( ! ( isset( $_POST['abplus_dl'] ) && $_POST['abplus_dl'] === 'アブカン受注明細CSVダウンロード' ) ) { //アブカン受注明細CSVダウンロード以外のボタンがクリックされたら
		return; //何もせず終了
	}

	global $wpdb, $usces;
	$order_ids = ( isset( $_POST['listcheck'] ) ) ? $_POST['listcheck'] : []; //チェックされたorder_idの配列(=dl対象の注文)
	$order_id_count = count( $order_ids );
	if ( $order_id_count === 0 ) { //チェックが無ければ ?>
		<script>
			window.alert( 'ダウンロードする注文番号をチェックしてください。' );
		</script><?php //この関数が実行されるのはinitアクションフックなので、html出力だと真っ白画面にテキストになる
		return;
	}
	$dl_csv = ( isset( $_POST['dl_type'] ) ) ? $_POST['dl_type'] : ''; //display:noneのradioボタンのvalueで送られて来る
/*echo('<pre style="margin-left: 180px;">');
var_dump($order_ids);
var_dump($dl_csv);
echo('</pre>');*/

	if ( $dl_csv === 'csv' ) {
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
		$sp = ":";
		$nb = " ";
		$lf = "\n";
	} else { //例外
		exit();
	}

	$usces_order_table = $wpdb->prefix.'usces_order';
	$usces_ordercart_table = $wpdb->prefix.'usces_ordercart';
	$usces_ordermeta_table = $wpdb->prefix.'usces_order_meta';
	$wp_postmeta_table = $wpdb->prefix.'postmeta';

	$in = substr( str_repeat( ',%d', $order_id_count ), 1 ); //IN句に入れる変数(1文字目は','なので2文字目(開始位置:1)から末尾まで取得して代入)
	$_code = 'code';
	$_advance = 'advance';
	$_semicolon = "\";";
	$_double_quotation = "\"";
	$_advance_delim = AbplusSpec::$delimiter; //advanceフィールドの区切り文字

	$placeholders = array(
		'cscs_company', //WHERE $usces_ordermeta_table.meta_key =
		'cscs_store', //AND order_meta_csstore.meta_key =

		//AS sku_code1
		$_code, //CONCATの第1引数
		$_semicolon, //CONCATの第2引数
		$_code, //CHAR_LENGTHの引数
		$_double_quotation, //SUBSTRING_INDEXの第2引数
		$_double_quotation, //(外側の)SUBSTRING_INDEXの第2引数

		//AS meta_value_quant
		$_advance, //CONCATの第1引数
		$_semicolon, //CONCATの第2引数
		$_advance, //CHAR_LENGTHの引数
		$_double_quotation, //SUBSTRING_INDEXの第2引数
		$_double_quotation, //SUBSTRING_INDEXの第2引数
		$_advance_delim, //SUBSTRING_INDEXの第2引数
		$_advance_delim, //SUBSTRING_INDEXの第2引数

		//AS sku_code2
		$_code, //CONCATの第1引数
		$_semicolon, //CONCATの第2引数
		$_code, //CHAR_LENGTHの引数
		$_double_quotation, //SUBSTRING_INDEXの第2引数
		$_double_quotation, //(外側の)SUBSTRING_INDEXの第2引数

		//AS meta_value_unit_price
		$_advance, //CONCATの第1引数
		$_semicolon, //CONCATの第2引数
		$_advance, //CHAR_LENGTHの引数
		$_double_quotation, //SUBSTRING_INDEXの第2引数
		$_double_quotation, //SUBSTRING_INDEXの第2引数
		$_advance_delim, //SUBSTRING_INDEXの第2引数
		$_advance_delim, //SUBSTRING_INDEXの第2引数

		'_isku_', //WHERE $wp_postmeta_table.meta_key =
		'_isku_' //AND postmeta_unit_price.meta_key =
	);
	$placeholders = array_merge( $placeholders, $order_ids );

	//orderデータ作成
	$query = $wpdb->prepare(
		"SELECT
		 DATE( $usces_order_table.order_date ) AS o_date,
		 DATE( DATE_ADD( $usces_order_table.order_date, INTERVAL 3 DAY ) ) AS o_date_3days_later,
		 $usces_ordercart_table.order_id,
		 $usces_ordercart_table.item_code,
		 meta_value_quant,
		 $usces_ordercart_table.quantity,
		 meta_value_unit_price,
		 $usces_ordercart_table.price,
		 $usces_order_table.order_payment_name,
		 $usces_order_table.order_shipping_charge,
		 $usces_order_table.order_cod_fee,
		 meta_value_company,
		 meta_value_store,
		 $usces_order_table.order_delivery,
		 $usces_order_table.order_delivery_date,
		 $usces_order_table.order_delivery_time,
		 $usces_order_table.order_note

		 FROM $usces_ordercart_table
		 INNER JOIN $usces_order_table
		 ON $usces_ordercart_table.order_id = $usces_order_table.ID

		 LEFT JOIN (
			SELECT $usces_ordermeta_table.order_id, $usces_ordermeta_table.meta_value AS meta_value_company, order_meta_csstore.meta_value AS meta_value_store
			FROM $usces_ordermeta_table
			INNER JOIN $usces_ordermeta_table AS order_meta_csstore
			ON $usces_ordermeta_table.order_id = order_meta_csstore.order_id
			WHERE $usces_ordermeta_table.meta_key = %s
			AND order_meta_csstore.meta_key = %s
		 ) AS order_meta_cscs
		 ON $usces_order_table.ID = order_meta_cscs.order_id

		 LEFT JOIN (
			SELECT
				$wp_postmeta_table.post_id,

				SUBSTRING_INDEX(
					SUBSTRING_INDEX(
						SUBSTRING( $wp_postmeta_table.meta_value, ( INSTR( $wp_postmeta_table.meta_value, CONCAT( %s, %s ) ) + CHAR_LENGTH( %s ) + 1 ) ),
					%s, 2 ),
				%s, -1 ) AS sku_code1,

				SUBSTRING_INDEX(
					SUBSTRING_INDEX(
						SUBSTRING_INDEX(
							SUBSTRING_INDEX(
								SUBSTRING( $wp_postmeta_table.meta_value, ( INSTR( $wp_postmeta_table.meta_value, CONCAT( %s, %s ) ) + CHAR_LENGTH( %s ) + 1 ) ),
							%s, 2 ),
						%s, -1 ),
					%s, 4 ),
				%s, -1 ) AS meta_value_quant,

				SUBSTRING_INDEX(
					SUBSTRING_INDEX(
						SUBSTRING( postmeta_unit_price.meta_value, ( INSTR( postmeta_unit_price.meta_value, CONCAT( %s, %s ) ) + CHAR_LENGTH( %s ) + 1 ) ),
					%s, 2 ),
				%s, -1 ) AS sku_code2,

				SUBSTRING_INDEX(
					SUBSTRING_INDEX(
						SUBSTRING_INDEX(
							SUBSTRING_INDEX(
								SUBSTRING( postmeta_unit_price.meta_value, ( INSTR( postmeta_unit_price.meta_value, CONCAT( %s, %s ) ) + CHAR_LENGTH( %s ) + 1 ) ),
							%s, 2 ),
						%s, -1 ),
					%s, 3 ),
				%s, -1 ) AS meta_value_unit_price

			FROM $wp_postmeta_table
			INNER JOIN $wp_postmeta_table AS postmeta_unit_price
			ON $wp_postmeta_table.post_id = postmeta_unit_price.post_id
			WHERE $wp_postmeta_table.meta_key = %s
			AND postmeta_unit_price.meta_key = %s
		 ) AS postmeta_quant
		 ON $usces_ordercart_table.post_id = postmeta_quant.post_id

		 WHERE $usces_ordercart_table.order_id IN ( {$in} )
		 AND $usces_ordercart_table.sku_code = postmeta_quant.sku_code1
		 AND $usces_ordercart_table.sku_code = postmeta_quant.sku_code2",
		 $placeholders
	);

	$rows = $wpdb->get_results( $query, ARRAY_A );
	/*echo('<pre style="margin-left: 180px;">');
	var_dump($rows);
	echo('</pre>');*/

	//タイトル行(th)作成
	$line = $table_h;
	$line .= $tr_h;
	$line .= $th_h1. '区切り'. $th_f;
	$line .= $th_h. '受注日付'. $th_f;
	$line .= $th_h. '3日後日付'. $th_f;
	$line .= $th_h. '*注文番号'. $th_f;
	$line .= $th_h. '商品コード'. $th_f;
	$line .= $th_h. '*入数'. $th_f;
	$line .= $th_h. '*注文数'. $th_f;
	$line .= $th_h. '*総注文数'. $th_f;
	$line .= $th_h. '*一個単価'. $th_f;
	$line .= $th_h. '*単価'. $th_f;
	$line .= $th_h. '*商品合計'. $th_f;
	$line .= $th_h. '送金方法'. $th_f;
	$line .= $th_h. '支払いコード'. $th_f;
	$line .= $th_h. '送料'. $th_f;
	$line .= $th_h. '代引き料'. $th_f;
	$line .= $th_h. '会社名'. $th_f;
	$line .= $th_h. '店舗名'. $th_f;
	$line .= $th_h. '郵便番号'. $th_f;
	$line .= $th_h. '住所'. $th_f;
	$line .= $th_h. '電話'. $th_f;
	$line .= $th_h. '機種'. $th_f;
	$line .= $th_h. '配達希望日'. $th_f;
	$line .= $th_h. '配達時間'. $th_f;
	$line .= $th_h. '連絡事項'. $th_f;
	$line .= $th_h. '固定値0'. $th_f;
	$line .= $tr_f. $lf;

	//contents行(td)作成
	$prev_order_id = null; //order_idの区切りチェック用変数
	foreach ( $rows as $row_ary ) {
		$line .= $tr_h;
		$line .= $td_h1;
		if ( $prev_order_id === null || $prev_order_id !== $row_ary['order_id'] ) $line .= '*'; //初回ループ、又は、1つ前のループのorder_idと現在ループのorder_idが異なれば、区切り記号'*'を表示
		$line .= $td_f;
		$line .= $td_h. $row_ary['o_date']. $td_f; //受注日付
		$line .= $td_h. $row_ary['o_date_3days_later']. $td_f; //3日後日付

		$line .= $td_h. $row_ary['order_id']. $td_f; //注文番号
		$line .= $td_h. $row_ary['item_code']. $td_f; //商品コード
		$line .= $td_h. $row_ary['meta_value_quant']. $td_f; //入数(商品仕様)
		$line .= $td_h. $row_ary['quantity']. $td_f; //注文数

		if ( is_numeric( $row_ary['meta_value_quant'] ) ) { //商品入数に数値型の値があれば
			$line .= $td_h. $row_ary['meta_value_quant'] * $row_ary['quantity']. $td_f; //総注文数(=入数×注文数)
		} else { //商品入数に値がなければ(type="number"なので数値以外入らない)
			$line .= $td_h. ''. $td_f; //空文字を入れる
		}

		$line .= $td_h. $row_ary['meta_value_unit_price']. $td_f; //一個単価(商品仕様)
		$line .= $td_h. $row_ary['price']. $td_f; //単価
		$line .= $td_h. $row_ary['quantity'] * $row_ary['price']. $td_f; //商品合計(=注文数×単価)
		$line .= $td_h. $row_ary['order_payment_name']. $td_f; //送金方法
		$line .= $td_h;
		if ( $row_ary['order_payment_name'] === '月末締/翌月末払い' ) {
			$line .= 0; //支払いコード
		} elseif ( $row_ary['order_payment_name'] === '代引き' ) {
			$line .= 3; //支払いコード
		}
		$line .= $td_f;
		$line .= $td_h;
		if ( $prev_order_id === null || $prev_order_id !== $row_ary['order_id'] ) {
			$line .= $row_ary['order_shipping_charge']; //送料
		} else {
			$line .= 0; //同一注文番号の初回以外は0を表示
		}
		$line .= $td_f;
		$line .= $td_h. $row_ary['order_cod_fee']. $td_f; //代引き料
		$line .= $td_h. $row_ary['meta_value_company']. $td_f; //会社名
		$line .= $td_h. $row_ary['meta_value_store']. $td_f; //店舗名
		$order_delivery = maybe_unserialize( $row_ary['order_delivery'] );
		$line .= $td_h. $order_delivery['zipcode']. $td_f; //郵便番号
		$line .= $td_h. $order_delivery['address1']. $order_delivery['address2']. $order_delivery['address3']. $td_f; //住所
		$line .= $td_h. $order_delivery['tel']. $td_f; //電話

		$infos = [];
		$value = $usces->get_order_meta_value( 'extra_info', $row_ary['order_id'] ); //ループ中の注文のIPアドレス,ユーザーエージェント情報を取得
		if ( $value ) {
			$infos = unserialize( $value ); //シリアライズデータを配列データに
		}

		$line .= $td_h;
		if ( ! abplus_is_mobile( $infos['USER_AGENT'] ) ) { //端末がモバイルでないなら
			$line .= 'PC'; //機種
		} else { //モバイルなら
			$line .= 'MOBILE'; //機種
		}
		$line .= $td_f;
		$line .= $td_h. $row_ary['order_delivery_date']. $td_f; //配達希望日
		$line .= $td_h. $row_ary['order_delivery_time']. $td_f; //配達時間
		$line .= $td_h. $row_ary['order_note']. $td_f; //連絡事項
		$line .= $td_h. '0'. $td_f; //固定値0
		$line .= $tr_f. $lf;

		$prev_order_id = $row_ary['order_id']; //order_idの区切りチェック用変数に値代入
	}

	$filename = "conversion_list.";

	//ヘッダを使ってファイルを強制的にダウンロードさせる
	header( 'Content-Type: application/octet-stream' );
	header( "Content-Disposition: attachment; filename=". $filename. 'csv' );

	//ファイルを書き出す
	mb_http_output( 'pass' );
	print( mb_convert_encoding( $line, 'SJIS-win', "UTF-8" ) );
	exit();

}

/**
 * ダウンロード関数を実行する
*/
add_action( 'init', function() {
	if ( ! is_admin() ) {
		return;
	}
	if ( $_SERVER['QUERY_STRING'] === 'page=usces_orderlist' && isset( $_POST['dl_type'] ) ) { //urlのクエリ情報が'page=usces_orderlist'(Welcart Management受注リスト ページ)、かつ、「アブカン受注明細CSVダウンロード」ボタンがクリックされて、display:noneのラジオボタン(name="dl_type")の値が送信・セットされていれば、
		abplus_download_product_list(); //ダウンロードする
	}
} );

/**
 * CSVファイルの「機種」列にPC or MOBILEどちらの値を入れるか？判定する
 * file : wp-includes/vars.php
 * fn : wp_is_mobileを改造(引数を渡せるようにしただけ、それ以外は同じ)

 * @param string $extra_info ユーザーエージェント情報
 * @return bool 渡されたユーザーエージェント情報がモバイルか否(PC)か
 */
function abplus_is_mobile( $extra_info ) {
	if ( empty( $extra_info ) ) {
		$is_mobile = false;
	} elseif ( strpos( $extra_info, 'Mobile' ) !== false // Many mobile devices (all iPhone, iPad, etc.)
		|| strpos( $extra_info, 'Android' ) !== false
		|| strpos( $extra_info, 'Silk/' ) !== false
		|| strpos( $extra_info, 'Kindle' ) !== false
		|| strpos( $extra_info, 'BlackBerry' ) !== false
		|| strpos( $extra_info, 'Opera Mini' ) !== false
		|| strpos( $extra_info, 'Opera Mobi' ) !== false ) {
			$is_mobile = true;
	} else {
		$is_mobile = false;
	}
	return $is_mobile;
}

?>