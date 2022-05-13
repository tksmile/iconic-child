<?php
/**
 * 対応状況ステータスのデフォ文言を変更。「取り寄せ中」を「準備中」に
 * file : plugins/usc-e-shop/classes/usceshop.class.php
 * fn : main
 * #: customize/abplus_customer_status.php:152
 */
/*add_action( 'usces_main', function() {
	$management_status = maybe_unserialize( get_option( 'usces_management_status' ) );
	$management_status['duringorder'] = '準備中';
	update_option( 'usces_management_status', $management_status );
} );*/

/**
 * 対応状況ステータスのデフォ文言を変更。「取り寄せ中」を「準備中」に
 * WlcOrderListクラス初期化時にusc-e-shop/includes/initial.phpのデフォをなぜか再定義しているので受注リスト操作時にチラホラ「取り寄せ中」が表示される。念のため

 * file : usc-e-shop/classes/orderList2.class.php
 * fn : __construct
 * @param array $management_status 対応状況ステータス名
 * @return array $management_status 対応状況ステータス名
 */
add_filter( 'usces_filter_management_status', function( $management_status ) {
	$management_status['duringorder'] = '準備中';
	return $management_status;
} );


/**
 * 管理画面 受注リストの対応状況ステータス、新規受付の場合、先頭に●を付ける
 * neworderは$management_statusでは定義されていない
 * file : usc-e-shop/includes/orderlist_page.php
 * @param string $p_status ステータス名
 * @return string $p_status ステータス名
 */
add_filter( 'usces_filter_orderlist_process_status', function( $p_status ) {
	if ( $p_status === '新規受付' ) $p_status = '●新規受付';
	return $p_status;
} );

/**
 * すべてのSKUの在庫数、在庫状態を注文されたSKUと同じ値に設定
 * 在庫数が0以下になれば在庫状態を「売り切れ」に変更設定し、管理者に在庫切れ通知メールを送信
 * 受注データ作成直後のusces_action_reg_orderdataフックで実行

 * file : usc-e-shop/functions/filters.php
 * fn : usces_action_reg_orderdata_stocksを改造

 * @param array $args
 * $args = array(
 * 	'cart'=>$cart,
 * 	'entry'=>$entry,
 * 	'order_id'=>$order_id,
 * 	'member_id'=>$member['ID'],
 * 	'payments'=>$set,
 * 	'charging_type'=>$charging_type,
 * 	'results'=>$results
 * );
 * @return
 */
function abplus_action_reg_orderdata_stocks( $args ) {

	global $usces;
	extract( $args ); //要素のkeyを変数名に、値を変数の値にする($cartが出来る)
	$mail_message = ''; //在庫切れ通知メール本文text

	foreach( $cart as $cartrow ) {

		$itemOrderAcceptable = $usces->getItemOrderAcceptable( $cartrow['post_id'] ); //売り切れ時に購入制限（在庫チェック）する0・しない1 の取得
		$default_empty_status = 2; //標準の売り切れ表示statueの2

		//注文されたSKUの在庫数、在庫状態を変更
		$order_sku = urldecode( $cartrow['sku'] ); //注文されたSKUコード
		$zaikonum = $usces->getItemZaikoNum( $cartrow['post_id'], $order_sku ); //在庫数を取得
		//if ( WCUtils::is_blank( $zaikonum ) ) continue; //在庫数が空白(=在庫管理しない)ならスキップ
		$zaikonum = (int)$zaikonum - (int)$cartrow['quantity']; //在庫数から注文数量を引く
		if ( $itemOrderAcceptable != 1 ) { //「売り切れ時に購入制限する」なら
			if ( $zaikonum < 0 ) $zaikonum = 0; //在庫数が負数になれば、在庫数に0を代入
		}
		$usces->updateItemZaikoNum( $cartrow['post_id'], $order_sku, $zaikonum ); //SKUの在庫数を、注文数量を引いた数に更新

		if ( $itemOrderAcceptable != 1 ) { //「売り切れ時に購入制限する」なら
			if ( $zaikonum <= 0 ) { //在庫数が0以下なら

				$usces->updateItemZaiko( $cartrow['post_id'], $order_sku, $default_empty_status ); //SKUの在庫状態を「2売り切れ」に設定

				//在庫切れ通知メール本文に記載する商品コード・商品名・商品編集ページurlのtextを生成
				$mail_message .= esc_html( $usces->getItemCode( $cartrow['post_id'] ) ). ' '. esc_html( $usces->getItemName( $cartrow['post_id'] ) ). "\r\n". site_url(). '/wp-admin/admin.php?page=usces_itemedit&action=edit&post='. $cartrow['post_id']. "\r\n";

			} //end if ( $zaikonum <= 0 )
		}

		//その他のSKUの在庫数、在庫状態を変更
		$skus = $usces->get_skus( $cartrow['post_id'] ); //商品に付いた全skuデータの配列

		foreach ( $skus as $index => $other_sku ) {
			if ( $other_sku['code'] === $order_sku ) continue; //注文されたSKUコードと同じならスキップ
			$usces->updateItemZaikoNum( $cartrow['post_id'], $other_sku['code'], $zaikonum ); //その他のSKUの在庫数を注文されたSKUの在庫数と同じ数に更新

			if ( $itemOrderAcceptable != 1 ) { //「売り切れ時に購入制限する」なら
				if ( $zaikonum <= 0 ) { //在庫数が0以下なら
					$usces->updateItemZaiko( $cartrow['post_id'], $other_sku['code'], $default_empty_status ); //その他のSKUの在庫状態を2売り切れに設定
				}
			}
		}

	} //end foreach( $cart as $cartrow )

	if ( $mail_message ) { //受注商品の中に在庫切れになったモノがあれば

		//サイト管理者のメルアドを取得
		$emails = [];
		$administrators = get_users( array( 'role' => 'administrator' ) ); //権限グループがadministratorに設定されたuserの配列
		if ( $administrators ) { //権限グループがadministratorに設定されたuserがいれば
			foreach ( $administrators as $value ) { //すべてのadministratorのuserを走査
				$emails[] = $value->data->user_email; //administratorのuserのemailを$emails配列に追加
			}
		}

		//サイト管理者に在庫切れ通知メールを送信
		wp_mail( $emails, '商品在庫切れ通知', $mail_message );

	}

}

/**
 * 受注データ作成直後の在庫管理機能をwelcartデフォルトから独自仕様(すべてのSKUの在庫数、在庫状態を連動・共通化)に変更
 * file : usc-e-shop/functions/function.php
 * fn : usces_reg_orderdata ユーザーがカートから注文
 * fn : usces_new_orderdata 管理者が受注データ編集ページ(admin.php?page=usces_ordernew)から注文登録
 */
remove_action( 'usces_action_reg_orderdata', 'usces_action_reg_orderdata_stocks' ); //usc-e-shop/includes/default_filters.phpで設定されたwelcartデフォルトアクションを削除
add_action( 'usces_action_reg_orderdata', 'abplus_action_reg_orderdata_stocks' ); //すべてのSKUの在庫数、在庫状態を連動・共通化する独自関数に変更

/**
 * 受注リストの一括操作で、
 * 「対応状況」を「発送済み」に変換した直後、発送完了メールを送信する
 * 「対応状況」を「準備中」に変換した直後、納品書PDFを出力する
 * 「一括納品書出力」機能を独自に実装
 * 「一括発送完了メール送信」機能を独自に実装

 * file : usc-e-shop/includes/orderlist_page.php
 * @return function|script usces_send_mail|
 */
add_action( 'usces_action_order_list_footer', function() {

	if ( !empty( $_POST ) && isset( $_POST['collective'] ) ) { //一括操作の更新開始ボタンが押されてPOSTされたら

		//common変数定義
		global $wpdb, $usces;
		$order_ids = $_POST['listcheck']; //受注リストにチェックされたorder_idの配列
		$orderTable = $wpdb->prefix. 'usces_order';

		/**
		 * usces_orderテーブルから注文者メルアド、注文者名、order_checkのデータを取得
		 * @param string $orderTable データを取得するテーブル名
		 * @param int $order_id
		 * @return array $order_res usces_orderテーブルから取得した連想配列
		 */
		function col_get_order_res( $orderTable, $order_id ) {
			global $wpdb;

			$query = $wpdb->prepare(
				"SELECT order_email, order_name1, order_name2, order_check
				 FROM $orderTable
				 WHERE ID = %d",
				 $order_id
			);
			$order_res = $wpdb->get_row( $query, ARRAY_A );
			return $order_res;

		}

		/**
		 * チェックされた注文番号の発送完了メールを一括送信
		 * @param array $order_ids 受注リストにチェックされたorder_idの配列
		 * @param string $orderTable col_get_order_res関数の引数に使うために渡す
		 * @return sendmail|update
		 */
		function col_send_completionmail( $order_ids, $orderTable ) {
			global $usces;

			//受注リストにチェックが付いてなければ(jsのconfirmでも確認するが念のため)
			if ( empty( $order_ids ) ) return; //order_idの配列が空なら何もせず終了

			$_POST['mode'] = 'completionMail'; //メール本文を生成するusces_order_confirm_message関数内でメールheader,メールfooterの取得に必要となる変数を設定

			foreach ( $order_ids as $order_id ) { //チェックされたorder_idをループする

				//usces_orderテーブルから注文者メルアド、注文者名、order_checkのデータを取得
				$order_res = col_get_order_res( $orderTable, $order_id );
				//var_dump( $order_res );

				//既に発送完了メールを送信済みの場合はスキップして送信しない
				if ( strpos( $order_res['order_check'], 'completionmail' ) !== false ) { continue; }

				//まだ発送完了メールを送信していない場合は送信する
				$message = usces_order_confirm_message( $order_id ); //メール本文を作成
				$confirm_para = array(
					'to_name' => $order_res['order_name1']. ' '. $order_res['order_name2'],
					'to_address' => $order_res['order_email'],
					'from_name' => get_option( 'blogname' ),
					'from_address' => $usces->options['sender_mail'],
					'return_path' => $usces->options['sender_mail'],
					'subject' => $usces->options['mail_data']['title']['completionmail'], //管理画面で設定されたメールの件名title
					'message' => $message
				);

				$res = usces_send_mail( $confirm_para ); //発送完了メール送信

				if ( $res ) { //発送完了メール送信済みなら

					//usces_orderテーブルのorder_checkフィールドに「発送完了メール送信チェック」値を保存(＝受注データ編集ページのメール・印刷フィールドにチェック)
					col_update_order_check( $order_res['order_check'], 'completionmail', $orderTable, $order_id );

				}

			} //end foreach ( $order_ids as $order_id )

		}

		/**
		 * チェックされた注文番号の納品書PDFを一括でブラウザ別タブで開く
		 * @param array $order_ids 受注リストにチェックされたorder_idの配列
		 * @param string $orderTable col_get_order_res関数の引数に使うために渡す
		 * @return script 納品書PDFを出力
		 */
		function col_output_invoice ( $order_ids, $orderTable ) {
			?>

			<script>
				let _order_id; //jsでのPDF出力の際に使う変数(宣言だけループ外でしておかないと「 Identifier '_order_id' has already been declared」エラーで2回目から出力されない)
				let _is_pdf_open;
				const _admin_url = '<?php echo USCES_ADMIN_URL; ?>'; //
				const _stylesheetUrl = '<?php echo WP_CONTENT_URL; ?>' + '/themes/iconic-child/customize/';
				let ajax;
				//let formdata;
				let _order_check_serial;
				let _send_param;
				const _update_item = 'nohinprint';
				const _orderTable = '<?php echo $orderTable; ?>';
			</script>
			<?php

			foreach ( $order_ids as $order_id ) { //チェックされたorder_idをループする

				//usces_orderテーブルから注文者メルアド、注文者名、order_checkのデータを取得
				$order_res = col_get_order_res( $orderTable, $order_id );
				//var_dump( $order_res );

				//既に納品書PDFを出力済みの場合はスキップして出力しない
				if ( strpos( $order_res['order_check'], 'nohinprint' ) !== false ) { continue; }

				//まだ納品書PDFを出力していない場合は出力する ?>
				<script>
					_order_id = '<?php echo $order_id; ?>'; //現在ループ中のorder_idを代入
					_is_pdf_open = window.open( _admin_url + '?page=usces_orderlist&order_action=pdfout&noheader=true&order_id=' + _order_id + '&type=nohin', '_blank' ); //別ウインドウでPDFファイルを開く。戻り値は、開いたらwindowオブジェクト、開けなかったらnull

					//納品書出力OKならajaxでチェックフィールド更新用データを送信
					if ( _is_pdf_open !== null ) { //戻り値がnullでなければ(＝PDFが開いた)
						_order_check_serial = '<?php echo $order_res['order_check']; ?>';
						ajax = new XMLHttpRequest();
						ajax.open( 'POST', _stylesheetUrl + 'ajax.php', true );
						ajax.responseType = 'json';
						ajax.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded' );
						ajax.addEventListener( 'load', function( event ) {
							//console.log( this.response );
						} );
						/*_send_param = new FormData();
						_send_param.append( 'order_check_serial', _order_check_serial );
						_send_param.append( 'order_id', _order_id );*/
						//_send_param = 'order_check_serial=' + _order_check_serial + '&update_item=' + _update_item + '&orderTable=' + _orderTable + '&order_id=' + _order_id;
						//_send_param = 'order_check_serial=' + _order_check_serial + '&order_id=' + _order_id;
						_send_param = 'order_check_serial=' + _order_check_serial + '&order_id=' + _order_id;
						//console.log( _send_param );
						ajax.send( _send_param );
					}
				</script>
				<!-- <script src="../js/clear-cache.js"></script> -->
				<?php

			} //end foreach ( $order_ids as $order_id )

		}


		/**
		 * ドロップダウンリストの選択に応じた処理を実行
		 */
		if ( $_POST['allchange']['column'] === 'process_status' ) { //「対応状況」が選択されていたら

			if ( isset( $_POST['change'] ) && $_POST['change']['word'] === 'completion' ) { //「発送済み」を選択の場合

				//チェックボックスにチェックがあれば
				if ( isset( $_POST['addfunc'] ) && $_POST['addfunc'] === 'on' ) { //value未設定なので値はon
					col_send_completionmail( $order_ids, $orderTable ); //発送完了メールを送信
				}

			} elseif ( isset( $_POST['change'] ) && $_POST['change']['word'] === 'duringorder' ) { //「準備中」を選択の場合

				//チェックボックスにチェックがあれば
				if ( isset( $_POST['addfunc'] ) && $_POST['addfunc'] === 'on' ) {
					col_output_invoice ( $order_ids, $orderTable ); //納品書PDFを出力
				}

			}

		} elseif ( $_POST['allchange']['column'] === 'invoice' ) { //「一括納品書出力」が選択されていたら

			col_output_invoice ( $order_ids, $orderTable ); //納品書PDFを出力

		} elseif ( $_POST['allchange']['column'] === 'completionmail' ) { //「一括発送完了メール送信」が選択されていたら

			col_send_completionmail( $order_ids, $orderTable ); //発送完了メールを送信

		} //end if ( $_POST['allchange']['column'] === 'process_status' )

	} // end if ( !empty( $_POST ) && isset( $_POST['collective'] ) )

} );

/**
 * allchange[column]に独自項目「一括納品書出力」を追加
 * file :  usc-e-shop/includes/orderlist_page.php
 * @return string
 */
add_filter( 'usces_filter_allchange_column', function() {
	return '<option value="invoice">一括納品書出力</option><option value="completionmail">一括発送完了メール送信</option>';
} );

/**
 * 管理画面の受注リストページのheadにscript出力
 * collective_change(更新開始)ボタンクリック時のconfirm処理
 * file : wp-admin/admin-header.php
 * @param string $hook_suffix 管理画面のページ接尾辞
 * @return echo inline style
 */
add_action( 'admin_enqueue_scripts', function( $hook_suffix ) {
	if ( false !== strpos( $hook_suffix, 'usces_orderlist' ) && !isset( $_GET['order_action'] ) ) { //(個別でない一覧の)受注リストページなら
		/*$handle = 'my_ajax_handle';
		$js_url = get_stylesheet_directory_uri(). '/js/clear-cache.js';
		wp_register_script( $handle, $js_url, [ 'jquery' ], '', true );
		$localize = [
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'is_pdf_open' => 1,
		];
		wp_localize_script( $handle, 'localize', $localize );
		wp_enqueue_script( $handle );*/
		?>
		<script>
			document.addEventListener( 'DOMContentLoaded', function() {

				let _msg = '';
				document.getElementById( 'collective_change' ).addEventListener( 'click', function( event ) {
					let _changeselect = document.getElementById( 'changeselect' );
					if ( _changeselect.value == 'invoice' ) {
						_msg = 'チェックされたデータの納品書PDFファイルを、すべて出力します。よろしいですか？';
					} else if ( _changeselect.value == 'completionmail' ) {
						_msg = 'チェックされたデータの発送完了メールを、すべて送信します。よろしいですか？';
					}
					let _orderlistaction = document.getElementById( 'orderlistaction' );
					if ( _msg != '' ) {
						if ( !window.confirm( _msg ) ) {
							_orderlistaction.value = '';
							return false;
						}
					}
					_orderlistaction.value = 'collective';
				} );

			} );
		</script><?php
	}
} );


/**
 * order_checkフィールドの項目チェックボックスに済みチェックを付けて更新する
 * ajax.phpでも使うためにusces_action_order_list_footerフック処理の外に出したが、なぜかajax.phpではwpdbが使えなかったので、中に戻してもいいかも

 * @param string $order_check_serial usces_orderテーブルから取得した連想配列のkey['order_check']($order_res['order_check'])
 * @param string $update_item 'completionmail' or 'nohinprint'
 * @param string $orderTable updateするテーブル名
 * @param int $order_id
 * @return update usces_orderテーブル
 */
function col_update_order_check( $order_check_serial, $update_item, $orderTable, $order_id ) {
	global $wpdb;

	$checkfield = unserialize( $order_check_serial ); //DBのorder_checkシリアルデータを配列化
	if ( !isset( $checkfield[$update_item] ) ) $checkfield[$update_item] = $update_item; //$checkfield配列にcompletionmailが設定されていなければ(発送完了メール未送信なら)completionmailチェック値を設定する
	$result = $wpdb->update(
		$orderTable, //UPDATE
		array( 'order_check' => serialize( $checkfield ) ), //SET=
		array( 'ID' => $order_id ), //WHERE
		array( '%s' ), //SETの型
		array( '%d' ) //WHEREの型
	); //nohinprintチェック値を設定してシリアル化したデータをorder_checkフィールドに保存

}

/**
 * 受注リストページに独自一括処理完了のメッセージを表示する。
 * 独自一括処理を実行するusces_action_order_list_footerフックのcallback関数内だと上手くいかない。このフックより先に$status,$message変数が定義されるので。
 * ※とりあえずcompletionmail or invoiceでPOSTされたら$statusをsuccessにしているだけ。完了チェックはまだ。
 * ただwelcartは、usces_all_change_order_status関数内で$order_idsのループの最後のupdateがtrueなら$statusがsuccessになる仕様(ループ中で毎回$statusにupdate結果を代入しているがループの度に上書きされているので)

 * file : usc-e-shop/includes/orderlist_page.php
 * @param string $status, $message
 * @return string $status, $message (ともにusces_admin_action_status関数の引数として使われる)
 */
add_filter( 'usces_order_list_action_status', function( $status ) {
	if ( !empty( $_POST ) && isset( $_POST['collective'] ) && isset( $_POST['allchange']['column'] ) && ( $_POST['allchange']['column'] === 'completionmail' || $_POST['allchange']['column'] === 'invoice' ) ) {
		$status = 'success';
	}
	return $status;
} );
add_filter( 'usces_order_list_action_message', function( $message ) {
	if ( !empty( $_POST ) && isset( $_POST['collective'] ) && isset( $_POST['allchange']['column'] ) && ( $_POST['allchange']['column'] === 'completionmail' || $_POST['allchange']['column'] === 'invoice' ) ) {
		$message =  __( 'I completed collective operation.','usces' );
	}
	return $message;
} );

/**
 * 一括操作のドロップダウンリストで
 * 「対応状況」×「発送済み」を選択の場合、発送完了メール送信を選択できるチェックボックスを表示
 * 「対応状況」×「準備中」を選択の場合、納品書PDF出力を選択できるチェックボックスを表示

 * file : usc-e-shop/includes/orderlist_page.php
 * @param WlcOrderList $DT 必要なし
 * @return script
 */
add_action( 'usces_action_orderlist_document_ready_js', function() {

	//jsのコード中にあるhookなのでscriptタグは不要 ?>
	//select#changeselectの選択が変わった時の処理
	let _changeselect = document.getElementById( 'changeselect' ); //select
	_changeselect.addEventListener( 'change', function( event ) {

		//チェックボックスを生成してtd#change_list_tableの子divに挿入する関数を定義
		function createCheckbox() {

			let _changeWord = document.getElementById( 'changefield' ).firstChild; //select
			//console.log( _changeWord.value );

			if ( _changeWord.value === 'completion' || _changeWord.value === 'duringorder' ) { //「発送済み」or「準備中」が選択されたら

				let _addfunc = document.getElementById( 'addfunc' );
				if ( ! _addfunc && !document.getElementById( 'wrap_addfunc' ) ) { //選択された時点でチェックボックスがなければ

					//チェックボックスをラップするlabelを生成して挿入
					_label = document.createElement( 'label' );
					_label.id = 'wrap_addfunc';
					_label.style.border = '1px solid #ccc';
					_label.style.padding = '6px';
					_label.style.marginLeft = '5px';
					_label.innerHTML = ( _changeWord.value === 'completion' ) ? '<span>発送完了メール</span>も同時に<span>送信</span>する' : '<span>納品書PDF</span>も同時に<span>出力</span>する';
					document.getElementById( 'change_list_table' ).firstElementChild.appendChild( _label ); //#change_list_tableの子divの最後にlabel挿入

					//チェックボックスを生成して挿入
					_checkbox = document.createElement( 'input' );
					_checkbox.type = 'checkbox';
					_checkbox.name = 'addfunc';
					_checkbox.id = 'addfunc';
					let _wrap_addfunc = document.getElementById( 'wrap_addfunc' )
					_wrap_addfunc.insertBefore( _checkbox, _wrap_addfunc.firstChild ); //labelの中の先頭Nodeの前にcheckbox挿入

				} else { //選択された時点でチェックボックスがあれば

					//表示する
					_addfunc.style.display = 'inline-block';
					_addfunc.parentElement.style.display = 'inline-block'; //#wrap_addfunc
					let _variableSpans = _addfunc.parentElement.querySelectorAll( 'span' );
					let _duringorderTexts = [ '納品書PDF', '出力' ];
					let _completionTexts = [ '発送完了メール', '送信' ];
					if ( _changeWord.value === 'duringorder' ) {
						if ( _variableSpans ) {
							for ( let i = 0; i < _variableSpans.length; i++ ) {
								_variableSpans[i].innerText = _duringorderTexts[i];
							}
						}
					} else if ( _changeWord.value === 'completion' ) {
						if ( _variableSpans ) {
							for ( let i = 0; i < _variableSpans.length; i++ ) {
								_variableSpans[i].innerText = _completionTexts[i];
							}
						}
					}

				}

			} else { //「発送済み」or「準備中」以外が選択されたら

				//非表示にする
				let _addfunc = document.getElementById( 'addfunc' );
				if ( _addfunc ) {
					_addfunc.style.display = 'none';
				}
				let _wrap_addfunc = document.getElementById( 'wrap_addfunc' );
				if ( _wrap_addfunc ) {
					_wrap_addfunc.style.display = 'none'; //#wrap_addfunc
				}

			} //end if ( ( _options[i].selected && _options[i].value === 'completion' ) )

		} //end function createCheckbox


		//select#changeselectのoption要素の選択が「対応状況」に変わった時の処理
		if ( event.target.value === 'process_status' ) { //changeイベントのターゲットが「対応状況」なら

			//まだ一度もチェックボックスを表示していない(初めて対応状況を選択した場合)
			createCheckbox(); //チェックボックスを出力表示(初回の対応状況選択でデフォルト表示される準備中の時にチェックボックスを表示する)

			//select[name=change[word]]の選択が変わった時に定義した関数を実行
			document.getElementById( 'changefield' ).firstChild.addEventListener( 'change', createCheckbox );

		} else { //「対応状況」以外なら

			//select#changeselectの中だけで選択が変わってる間はチェックボックス非表示
			let _wrap_addfunc = document.getElementById( 'wrap_addfunc' ); //label
			if ( _wrap_addfunc ) { //チェックボックスがあれば(既に表示してその後display:noneにした場合)

				_wrap_addfunc.style.display = 'none';
				_wrap_addfunc.firstElementChild.style.display = 'none'; //input#addfunc

			}

		} //end if ( event.target.value === 'process_status' )

	} );
	<?php
} );

?>