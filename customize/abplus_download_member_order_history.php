<?php
/**
 * 会員ページの注文履歴tableにcsv,pdfのダウンロードボタン・フォームを設置
 * file : plugins/usc-e-shop/functions/template_func.php
 * fn : usces_member_history

 * @param string $history_member_head table#history_headのhtmlソース
 * @param array $umhs 注文内容データの配列
 * @return string $history_member_head ダウンロードボタン・フォームを追加したhtmlソース
 */
add_filter( 'usces_filter_history_member_head', function( $history_member_head, $umhs ) {
	$history_member_head = str_replace(
		usces_get_deco_order_id( $umhs['ID'] ), //8桁の注文番号(ex:00000007)
		usces_get_deco_order_id( $umhs['ID'] ). '<form action="" method="post" class="well2 m0"><label class="pr10"><input type="radio" name="ftype" value="csv">CSV</label><label class="pr10"><input type="radio" name="ftype" value="pdf">PDF</label><input type="hidden" name="order_id" value="'. $umhs['ID']. '"><input type="submit" class="q_button" value="受注明細ダウンロード"></form>', //hiddenのvalueはdecoする前の(8桁でない素の)order_id
		$history_member_head
	);
	return $history_member_head;
}, 10, 2 );

/**
 * 会員ページ(wc_member_page.php)の注文履歴tableでcsv,pdfをダウンロードする関数
 * postされたファイル種類(csv or pdf)とorder_idからdlファイルを作成してdlさせる

 * file : plugins/usc-e-shop/functions/datalist.php
 * fn : usces_download_product_listを参考にした
 */
function abplus_download_member_product_list() {
	global $wpdb, $usces;
	$order_id = $_REQUEST['order_id']; //hiddenのvalueで送られて来る
	$ext = $_REQUEST['ftype']; //radioのvalueで送られて来る(csv or pdf)

	if ( $ext === 'csv' ) { //CSV

		$table_h = "";
		$table_f = "";
		$tr_h = "";
		$tr_f = "";
		$th_h1 = '"';
		$th_h = ',"';
		$th_h_w8 = ',"';
		$th_h_w16 = ',"';
		$th_h_w24 = ',"';
		$th_h_w36 = ',"';
		$th_f = '"';
		$td_h1 = '"';
		$td_h = ',"';
		$td_h_bgwhite = ',"';
		$td_f = '"';
		$sp = ":";
		$nb = " ";
		$lf = "\n";

	} elseif ( $ext === 'pdf' ) {

		$table_h = '<style>table { border-collapse: collapse; border-spacing: 0; } table tr th, table tr td { border: 1px sold #000; }</style><p>◆ご注文内容◆（注文番号：'. $order_id. '）</p><table cellpadding="8">';
		$table_f = '</table>';
		$tr_h = '<tr style="page-break-inside: avoid;">';
		$tr_h_not_history = '<tr style="background-color: #f2f261; page-break-inside: avoid;">'; //注文履歴にない新規の注文商品の行
		$tr_f = '</tr>';
		$th_h1 = '<th style="page-break-inside: avoid;">';
		$th_h = '<th style="page-break-inside: avoid;">';
		$th_h_w8 = '<th style="width: 8%; page-break-inside: avoid;">';
		$th_h_w16 = '<th style="width: 16%; page-break-inside: avoid;">';
		$th_h_w24 = '<th style="width: 24%; page-break-inside: avoid;">';
		$th_h_w36 = '<th style="width: 36%; page-break-inside: avoid;">';
		$th_f = '</th>';
		$td_h1 = '<td style="page-break-inside: avoid;">';
		$td_h = '<td style="page-break-inside: avoid;">';
		$td_h_bgwhite = '<td style="background-color: #fff;">'; //バーコードを入れるtd
		$td_f = '</td>';
		$sp = ":";
		$nb = " ";
		$lf = '';

		require_once( USCES_PLUGIN_DIR . '/pdf/tcpdf/tcpdf.php' );
		define( 'USCES_PDF_FONT_FILE_NAME', 'msgothic.php' );
		$tcpdf = new TCPDF( "P", "mm", "A4", true, "UTF-8" );

		$tcpdf->setPrintHeader( false ); //PDFのヘッダー部分の線を非表示
		$tcpdf->setPrintFooter( false ); //PDFのフッター部分の線を非表示
		$tcpdf->AddPage(); //ページの追加
		$tcpdf->SetAutoPageBreak( true, 5 ); //自動改ページON, margin-bottom:5mmで改ページ(あくまで目安のようだ)

		//Add font
		$font_ob = new TCPDF_FONTS();
		$font_file_name = USCES_PDF_FONT_FILE_NAME; //Set font file and assign font name. This method is new.
		$font = $font_ob->addTTFfont( USCES_PLUGIN_DIR. '/pdf/tcpdf/fonts/'. $font_file_name );
		$tcpdf->SetFont( $font, '', 12 );

		//pear Image_Barcode2の読み込みとインスタンス生成
		require( STYLESHEETPATH. '/Image/Barcode2.php' );
		$code = new Image_Barcode2();

	} else { //例外
		exit();
	}

	$member_id = $usces->get_member()['ID'];
	$usces_order_table = $wpdb->prefix."usces_order";
	$usces_ordercart_table = $wpdb->prefix."usces_ordercart";
	//$usces_ordercart_meta_table = $wpdb->prefix."usces_ordercart_meta";

	//orderデータ作成
	$query = $wpdb->prepare(
		"SELECT $usces_order_table.order_date, $usces_order_table.order_cart
		 FROM $usces_order_table
		 WHERE mem_id = %d
		 AND ID = %d
		 LIMIT %d",
		$member_id, $order_id, 1
	);
	$rows = $wpdb->get_results( $query, ARRAY_A ); //usces_orderテーブルの注文番号の1行

	//注文履歴データ作成
	$query = $wpdb->prepare(
		"SELECT DISTINCT $usces_ordercart_table.item_code
		 FROM $usces_ordercart_table
		 INNER JOIN $usces_order_table
		 ON $usces_ordercart_table.order_id = $usces_order_table.ID
		 WHERE $usces_order_table.mem_id = %d
		 AND $usces_order_table.order_date < %s",
		 $member_id, $rows[0]['order_date'] //ログイン中の会員ID、かつ、$order_idの注文日時以前
	);
	$history = $wpdb->get_results( $query, ARRAY_A );
	$has_order_history = null; //商品の注文履歴あり・なしを入れる変数


	//タイトル行(th)作成
	$line = $table_h;
	$line .= $tr_h;
	if ( $ext === 'csv' ) {
		$line .= $th_h1. __( 'order date', 'usces' ). $th_f;
		$line .= $th_h. '新規'. $th_f;
		$line .= $th_h. 'JANコード'. $th_f;
	}
	if ( $ext === 'pdf' ) $line .= $th_h_w8. '通常単価'. $th_f;
	$line .= $th_h_w36. __( 'item name', 'usces' ). $th_f;
	if ( $ext === 'csv' ) $line .= $th_h. '通常単価'. $th_f;
	$line .= $th_h_w8. '販売(仕入)単価'. $th_f;
	$line .= $th_h_w8. '個数'. $th_f;
	$line .= $th_h_w16. '総額(税別)'. $th_f;
	if ( $ext === 'pdf' ) $line .= $th_h_w24. 'BarCode'. $th_f;
	$line .= $tr_f. $lf;


	//contents行(td)作成
	//ordercartデータ作成
	$query = $wpdb->prepare(
		"SELECT *
		 FROM $usces_ordercart_table
		 WHERE order_id = %d",
		$order_id
	);
	$cart = $wpdb->get_results( $query, ARRAY_A ); //usces_ordercartテーブルの同じ注文番号の注文商品データ

	//(商品仕様の値を取得するために)AbplusSpecクラスのインスタンス生成
	$Spec = new AbplusSpec();
	$spec_values = [];

	foreach ( $cart as $cart_row ) { //usces_ordercartテーブルに入っている商品をループ

		//注文履歴にitem_codeがあるかチェック
		foreach ( $history as $history_v ) {
			if ( $history_v['item_code'] === $cart_row['item_code'] ) {
				$has_order_history = true; //「注文履歴あり」を代入
				break;
			}
		}
		if ( is_null( $has_order_history ) ) $has_order_history = false; //「注文履歴なし」を代入
		$jan = abplus_get_org_skuCode( $cart_row['sku_code'] ); //(枝番のない)SKUコード

		if ( $ext === 'pdf' && $has_order_history === false ) { //pdfで「注文履歴なし」なら
			$line .= $tr_h_not_history; //背景色付きのtrを表示
		} else {
			$line .= $tr_h;
		}
		if ( $ext === 'csv' ) {
			$line .= $td_h1. $rows[0]['order_date']. $td_f;
			$line .= $td_h;
			$line .= ( $has_order_history === false ) ? '*' : ''; //「注文履歴なし」なら新規*マークを表示、「注文履歴あり」なら何も表示しない
			$line .= $td_f;
			$line .= $td_h. $jan. $td_f; //(枝番のない)SKUコード
		}

		//SKUアドバンスフィールドの値を取得
		$advance_str = '';
		$order_cart_ary = maybe_unserialize( $rows[0]['order_cart'] ); //usces_orderテーブルのシリアライズデータを配列化

		foreach ( $order_cart_ary as $value ) { //配列化したシリアルorder_cartデータを子ループして、親ループ中の商品SKUコードと一致するデータを探す

			//if ( $value['sku'] === $cart_row['sku_code'] ) { //親ループ中の商品SKUコードと一致する配列化したシリアルorder_cartデータがあれば
			if ( explode( '-', $value['sku'] )[0] === explode( '-', $cart_row['sku_code'] )[0] ) { //ハイフンより前の部分のテキストが一致すれば(SKUコード完全一致にすると通常会員で-00振り忘れが漏れて対応できないので)
				$advance_str = $value['advance']; //その2次元目配列の要素['advance']の値を取得代入
				break;
			}

		}

		//商品仕様の値を取得
		if ( $advance_str !== '' ) { //advanceフィールドの値が空でなければ(全6項目未入力でも##×5回のtextが入るので実質的に旧データの場合のエラー対策)
			$spec_values = $Spec->get_values( $advance_str );
		}

		//通常単価,販売(仕入)単価,個数,総額(税別)の値を取得・計算
		$_abplus_normal_price = ( isset( $spec_values['通常単価'] ) ) ? $spec_values['通常単価'] : '';
		$_abplus_unit_price = ( isset( $spec_values['販売単価'] ) ) ? $spec_values['販売単価'] : ''; //販売(仕入)単価
		$sum__quantity = ( isset( $spec_values['商品入数'] ) && !empty( $spec_values['商品入数'] ) ) ? $spec_values['商品入数'] * $cart_row['quantity'] : ''; //個数

		$sum_price = ( $_abplus_unit_price && $sum__quantity ) ? $_abplus_unit_price * $sum__quantity : ''; //総額(税別)

		if ( $ext === 'pdf' ) $line .= $td_h. $_abplus_normal_price. $td_f; //通常単価
		$line .= $td_h. usces_entity_decode( $cart_row['item_name'], $ext ). $td_f;
		if ( $ext === 'csv' ) $line .= $td_h. $_abplus_normal_price. $td_f; //通常単価

		$line .= $td_h. $_abplus_unit_price. $td_f; //販売(仕入)単価
		$line .= $td_h. $sum__quantity. $td_f; //個数
		$line .= $td_h;
		if ( $ext === 'pdf' ) {
			$line .= ( is_numeric( $sum_price ) ) ? number_format( $sum_price ) : $sum_price; //総額(税別)
		} else { //CSV
			$line .= $sum_price; //総額(税別)
		}
		$line .= $td_f;

		if ( $ext === 'pdf' ) {

			//JANバーコード作成
      if ( strlen( $jan ) == 8 ) {
          $codeType = 'ean8';
      } elseif ( strlen( $jan ) == 11 ) {
          $jan = '0'.$jan;
          $codeType = 'upca';
      } elseif ( strlen( $jan ) == 12 ) {
          $codeType = 'upca';
      } elseif ( strlen( $jan ) == 13 ) {
          $codeType = 'ean13';
      } else {
          $codeType = "";
      }
      $bc_filenm = ""; //バーコードのjpgを格納する変数

      $line .= $td_h_bgwhite;
      if ( $codeType <> "" ) {
          // draw   data, type, format, output, height, width, display of value, rotation
          $image = $code->draw( $jan, $codeType, 'jpg', false, 55, 1, true, 0 );
          $bc_filenm = get_stylesheet_directory(). '/Image/barcode_img/barcode_'. $jan. '.jpg';
          imagejpeg( $image, $bc_filenm );
          $line .= '<img src="'. $bc_filenm. '">';
      } else {
          $line .= "&nbsp;";
      }
      $line .= $td_f;

		} //end if ( $ext === 'pdf' )
		$line .= $tr_f.$lf;

		$has_order_history = null; //nullを入れて注文履歴あり・なしをリセット
	} //end foreach ( $cart as $cart_row )

	$line .= $table_f.$lf;

	$filename = "order_{$order_id}.";

	//ヘッダを使ってファイルを強制的にダウンロードさせる
	header( 'Content-Type: application/octet-stream' );
	header( "Content-Disposition: attachment; filename=". $filename. $ext );

	//ファイルを書き出す
	if ( $ext === 'csv' ) {
		mb_http_output( 'pass' );
		print( mb_convert_encoding( $line, 'SJIS-win', "UTF-8" ) );
	} elseif ( $ext === 'pdf' ) {
		$tcpdf->writeHTML( $line );
		$pdfData = $tcpdf->Output( $filename. $ext, 'S' );
		echo $pdfData;
	}
	exit();

}

?>