<?php
/**
 * ajax送信された$_POSTデータを変数に代入
 */
$order_check_serial = ( isset( $_POST['order_check_serial'] ) ) ? $_POST['order_check_serial'] : '';
$update_item = ( isset( $_POST['update_item'] ) ) ? isset( $_POST['update_item'] ) : 'nohinprint';
//$orderTable = ( isset( $_POST['orderTable'] ) ) ? $_POST['orderTable'] : '';
$order_id = ( isset( $_POST['order_id'] ) ) ? $_POST['order_id'] : '';
//header( 'Content-type:application/json; charset=utf8' );
//echo $order_id, $order_check_serial; //, $update_item, $orderTable, $order_id;

/**
 * order_checkフィールドの更新データ作成
 */
$checkfield = unserialize( $order_check_serial ); //DBのorder_checkシリアルデータを配列化
if ( !isset( $checkfield[$update_item] ) ) $checkfield[$update_item] = $update_item; //$checkfield配列に$update_itemが設定されていなければ(納品書未発行なら)nohinprintlチェック値を設定する(これで他のチェック項目には影響しない)
$serial_checkfield = serialize( $checkfield ); //配列化したデータをDB保存形式のシリアルに戻す


/**
 * データベースを更新
 * $wpdbがなぜか使えないためPDOで接続・更新する
 * Fatal error: Uncaught Error: Call to a member function update() on null in
 * ($wpdbのインスタンスが無い、と言われる)
 */
$dsn = 'mysql:dbname=local; host=localhost:10022; charset=utf8'; //port:10022が無いと接続できない | mysql:dbname=adweaver_abdata; host=mysql41a.xserver.jp;
$user = 'root'; //adweaver_abplus
$password = 'root'; //gs112k820

try {

	$dbh = new PDO( $dsn, $user, $password );

	$stmt = $dbh->prepare(
		"UPDATE abplus_usces_order
		 SET order_check = :order_check
		 WHERE ID = :order_id"
	);
	$stmt->execute(
		array(
			':order_check' => $serial_checkfield, //'a:1:{s:10:"nohinprint";s:10:"nohinprint";}',
			':order_id' => $order_id, //1274
		)
	);

} catch ( Exception $e ) {
	die ( "エラーが発生しました。: {$e->getMessage()}" );
}

/*global $wpdb;

$result = $wpdb->update(
	'abplus_usces_order', //UPDATE
	array( 'order_check' => serialize( $checkfield ) ), //SET=
	array( 'ID' => $order_id ), //WHERE
	array( '%s' ), //SETの型
	array( '%d' ) //WHEREの型
); //nohinprintチェック値を設定してシリアル化したデータをorder_checkフィールドに保存*/
