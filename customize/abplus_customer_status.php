<?php
/**
 * Abplus_customer_status Class
 */
class Abplus_customer_status {

	/*constructor*/
	public function __construct() {

		$this->customer_status = maybe_unserialize( get_option( 'abplus_customer_status' ) ); //optionテーブルに保存済みの会員ランク区分の配列
		$this->customer_status[] = '未ログイン(非会員)'; //末尾要素に未ログインを追加
		$this->customer_status_settings = maybe_unserialize( get_option( 'abplus_customer_status_settings' ) ); //optionテーブルに保存済みの表示項目設定データ

	}

	/**
	 * 会員ランク別表示データが設定されているか確認する関数
	 * @return bool 会員ランク別表示設定データが、あり、かつ配列で、かつ要素数が1つ以上であるか否か
	*/
	public function is_customer_status_settings() {
		if ( $this->customer_status_settings && is_array( $this->customer_status_settings ) && count( $this->customer_status_settings ) > 0 ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * optionテーブルに保存済みの表示設定データから閲覧者の会員ランクに一致する表示データを取り出して配列にする
	 * @param str $data_name 取り扱うデータの名前(category,post_tag,item_code)
	 * @return array $display_data_array 表示するカテゴリー名・タグ又は除外する商品コードの配列
	*/
	public function add_display_data_to_array( $data_name ) {
		$display_data_array = [];
		$current_user_status = abplus_the_member_status( 'return' ); //現在閲覧者の会員ランク区分名

		foreach ( $this->customer_status as $index => $status ) {

			if ( $current_user_status === $status || ( $current_user_status === null && '未ログイン(非会員)' === $status ) ) { //会員ランクが一致したら 4/16usces_the_member_status関数をabplus_the_member_status関数に変更

				if ( isset( $this->customer_status_settings[$index][$data_name] ) && is_array( $this->customer_status_settings[$index][$data_name] ) && count( $this->customer_status_settings[$index][$data_name] ) > 0 ) {
					foreach ( $this->customer_status_settings[$index][$data_name] as $value ) {
						$display_data_array[] = $value; //表示するカテゴリー・タグ又は除外する商品コードを配列に追加
					}
				}
				break;

			}

		} //end foreach ( $customer_status as $index => $status )

		return $display_data_array;
	}

}

/**
 * 会員ランク区分登録,会員ランク別表示項目設定の各ページで入力欄追加・削除ボタンをechoする
 * @param int $i ループのカウンター変数
 * @param int $count ループする配列の要素数
 * @param int $index index番号(会員ランク別表示項目設定ページの場合に渡す)
 * @param string $value データ種類(category,post_tag,item_code)(会員ランク別表示項目設定ページの場合に渡す)
 * @return echo 直接出力するので戻り値なし
*/
function echo_add_del_button( $i, $count, $index = null, $value = null ) {
	echo '<td><button type="button" id="';

	if ( $index === null && $value === null ) { //会員ランク区分登録ページ
		echo 'add_status', $i;
	} elseif ( $index !== null && $value !== null ) { //会員ランク別表示項目設定ページ
		echo 'add_', $index, '_', $value, $i;
	}

	echo '" class="button add_new_field"';
	if ( $i !== $count - 1 && $count !== 0 ) echo ' disabled';
	echo '>入力欄追加</button> <button type="button" id="';

	if ( $index === null && $value === null ) { //会員ランク区分登録ページ
		echo 'del_status', $i;
	} elseif ( $index !== null && $value !== null ) { //会員ランク別表示項目設定ページ
		echo 'del_', $index, '_', $value, $i;
	}

	echo '" class="button del_field"';
	if ( $i === 0 ) echo ' disabled';
	echo '>入力欄削除</button></td>';
}

/**
 * 管理画面に独自の会員ランク区分登録ページを追加
 * file : classes/usceshop.class.php
 * fn : add_pages
*/
add_action( 'usces_action_management_admin_menue', function() {
	add_submenu_page(
		'usces_orderlist', //parent slug
		'会員ランク区分登録', //page_title
		'会員ランク区分登録', //menu_title
		'manage_options',//capability(権限)
		'abplus_customer_status_regist', //menu_slug

		function() { //メニューページを表示する際に実行される関数
			?>
			<div class="wrap">
				<h1 class="wp-heading-inline">会員ランク区分登録</h1>
				<p>※独自の会員ランク区分を登録するページです。</p>
				<p>※未登録の場合はwelcart標準のランク区分(0:通常会員, 1:優良会員, 2:VIP会員, 99:不良会員)が適用されます。</p>
				<p>※1つ以上独自ランク区分を登録すると、welcart標準のランク区分は一切適用されません。</p>
				<form method="post" action="" style="margin-bottom: 1em;" id="poststuff">
					<div class="postbox">
						<table class="form_table inside" style="border-spacing: 2px 10px;">

							<?php $customer_status = maybe_unserialize( get_option( 'abplus_customer_status' ) ); //optionテーブルに保存済みの独自会員ランク区分の配列
							$count = ( isset( $customer_status ) && is_array( $customer_status ) ) ? count( $customer_status ) : 0;
							$i = 0;

							do {

								echo '<tr>
								<td style="font-size: 16px;">ランク区分名称</td>';
								echo '<td style="font-size: 16px;">';

									echo '<input type="text" name="', 'abplus_customer_status[', $i, ']" size="30" value="';
									if ( isset( $customer_status[$i] ) ) echo esc_attr( $customer_status[$i] );
									echo '">';

								echo '</td>';
								echo_add_del_button( $i, $count );
								echo '</tr>';

								$i++;
							} while ( $i < $count ); ?>

						</table>
						<p style="margin-top: 0; padding-left: 12px;">※登録したランク区分を削除する際、「入力欄削除」ボタンが無効の場合は、入力欄を空にした状態で「登録する」ボタンを押してください。</p>
					</div><!--.postbox-->
					<input type="submit" value="登録する" class="button button-primary" style="font-size: 16px;">

				</form>

			</div><!--.wrap-->
			<!--<script src="<?php //echo get_stylesheet_directory_uri(); ?>/add_del_admin_page_input.js" defer></script>-->
			<?php
		} //end function
	); //end add_submenu_page

	if ( isset(  $_POST['abplus_customer_status'] ) ) {
		$_POST['abplus_customer_status'] = array_filter_recursive( $_POST['abplus_customer_status'], null, true ); //空要素削除
		$_POST['abplus_customer_status'] = array_values( $_POST['abplus_customer_status'] ); //index連番の歯抜け修正(array_filter_recursiveでの歯抜け修正は多次元目の配列が対象)
		update_option( 'abplus_customer_status', wp_unslash( $_POST['abplus_customer_status'] ) ); ?>
		<div id="setting-error-settings_updated" class="updated settings-error notice is-dismissible">
			<p><strong>設定を保存しました。</strong></p>
		</div><?php
	}

} );

/**
 * wecartデフォの会員ランク区分を独自登録したランク区分に変更
 * file : plugins/usc-e-shop/classes/usceshop.class.php
 * fn : main
 * #: customize/abplus_admin_customize.php:2
 */
add_action( 'usces_main', function() {
	global $usces;
	$abplus_customer_status = maybe_unserialize( get_option( 'abplus_customer_status' ) );

	if ( $abplus_customer_status && is_array( $abplus_customer_status ) && count( $abplus_customer_status ) > 0 ) {
		update_option( 'usces_customer_status', $abplus_customer_status );
		$usces->member_status = get_option( 'usces_customer_status' );
	}
} );

/**
 * 現在閲覧者の会員ランク区分を取得し、その略文字を返す
 * @return string $cs_status_abbr 会員ランク名の略文字(＝末尾の英数字_部分)
*/
function get_customer_status_abbr() {

	//閲覧者の会員ランク(未会員＝未ログインはnull)を取得
	$customer_status = abplus_the_member_status( 'return' ); //usces_the_member_status関数だと、商品詳細ページでWCEX Widget CartプラグインによるAjax通信直後、独自会員ランク区分を取得できない(welcartデフォの会員ランク区分を返す)

	if ( $customer_status !== null ) { //nullでない(会員なら)
		if ( preg_match( '/[a-zA-Z_0-9]+$/u', $customer_status, $matches ) === 1 ) { //末尾が英数字_なら
			$cs_status_abbr = $matches[0]; //マッチした英数字部分を代入
		} else {
			$cs_status_abbr = '00'; //末尾に英数字がない通常会員は00
		}
	} else { //未会員＝未ログインなら
		$cs_status_abbr = $customer_status; //そのままnullを代入
	}
	return $cs_status_abbr;

}

/**
 * 現在閲覧者の独自会員ランク区分名を取得
 * 商品詳細ページでWCEX Widget CartプラグインによるAjax通信直後、usces_the_member_status関数では独自会員ランク区分を取得できない(welcartデフォの会員ランク区分を返す)ため、絶対に独自会員ランク区分を返すこの関数を作った
 * file : usc-e-shop/functions/template_func.php
 * fn : usces_the_member_statusを改変
*/
function abplus_the_member_status( $out = '' ) {
	global $usces;
	if ( ! $usces->is_member_logged_in() ) return;

	$usces->get_current_member();
	$member = $usces->get_member_info( $usces->current_member['id'] );

	$abplus_customer_status = maybe_unserialize( get_option( 'abplus_customer_status' ) );
	if ( $abplus_customer_status && is_array( $abplus_customer_status ) && count( $abplus_customer_status ) > 0 ) {
		$status_name = $abplus_customer_status[ $member['mem_status'] ]; //(int)$member['mem_status']はindex番号
	} else {
		$status_name = $usces->member_status[ $member['mem_status'] ];
	}

	if ( $out == 'return' ) {
		return $status_name;
	} else {
		echo esc_html( $status_name );
	}
}

/**
 * SKU情報や商品オプション情報をセットする
 * 会員ランク別のSKUが設定されていれば会員ランク別SKUを、なければ(枝番のない)基になるSKUを、セットする
 * file : usc-e-shop/functions/template_func.php
 * fn : usces_the_itemを改変
 * @param string $cs_status_abbr 現在閲覧者の会員ランク区分略文字
 * @return array $org_skus (枝番のない)基になるSKUデータの配列(基になるusces_the_itemはreturnなし)
*/
function abplus_the_item( $cs_status_abbr ) {
	global $post, $usces;

	//$cs_status_abbr= get_customer_status_abbr();
	$skus = $usces->get_skus( $post->ID );
	$org_skus = []; //(枝番のない)基になる正規のSKUデータの配列
	$member_skus = []; //閲覧者の会員ランク区分のSKUデータの配列
	$usces->itemskus = []; //初期化しないとitem-category.phpでは、現在ループ中のpostより前のpostのskuが追加されていく

	//$org_skus,$member_skusの取得作成
	foreach ( $skus as $index => $sku ) {
		$skuCode_ary = explode( '-', $sku['code'] ); //SKUコードを-で分割した文字列の入った配列
		$skuCode_ary_count = count( $skuCode_ary ); //要素数が1：枝番なし, 要素数が2：会員ランクを表す枝番あり
		if ( $index === 0 && $skuCode_ary_count === 1 ) { //先頭のskuで、要素1つなら
			$org_skus = $sku;
		} elseif ( $skuCode_ary_count === 2 && $skuCode_ary[1] === $cs_status_abbr ) { //要素2つで、2つ目の要素(枝番)が会員ランク名の略文字と同じなら
			if ( isset( $sku['advance'] ) && $sku['advance'] ) { //advanceフィールドに値がセットされていれば
				$member_skus = $sku;
			}
		}
	}

	//$member_skusの未入力SKU項目に$org_skusの値を代入
	if ( ! empty( $member_skus ) ) {
		foreach ( $member_skus as $sku_key => $sku_value ) {
			if ( empty( $sku_value ) ) {
				$member_skus[$sku_key] = $org_skus[$sku_key];
			}
		}
	}

	//$uscesのプロパティに値を設定
	if ( empty( $member_skus ) ) { //会員ランク別のSKUがなければ
		$usces->itemskus[] = $org_skus; //(枝番のない)基になるSKUをセット
	} else { //会員ランク別のSKUが設定されていれば
		$usces->itemskus[] = $member_skus; //会員ランク別SKUをセット
	}
	$usces->current_itemsku = -1;
	$usces->itemopts = usces_get_opts( $post->ID, 'sort' );
	$usces->current_itemopt = -1;

	return $org_skus;
}

/**
 * 管理画面に会員ランク別表示項目設定ページを追加
 * file : classes/usceshop.class.php
 * fn : add_pages
*/
add_action( 'usces_action_management_admin_menue', function() { //メニューページとサブメニューページは同時に呼び出され、同じスラッグを使用する必要がある
	add_submenu_page(
		'usces_orderlist', //parent slug
		'会員ランク別表示項目設定', //page_title
		'会員ランク別表示項目設定', //menu_title
		'manage_options',//capability(権限)
		'abplus_customer_status_settings', //menu_slug

		function() { //メニューページを表示する際に実行される関数
			?>
			<div class="wrap">
				<h1 class="wp-heading-inline">会員ランク別表示項目設定</h1>
				<p>※会員ランク（未ログイン＝非会員 含む）別の表示・除外する項目を設定するページです。</p>
				<p>※表示・除外する項目を設定していないランク区分は、すべてのカテゴリー、商品コードが表示されます（＝何も制限がかからない標準の状態です）。</p>
				<p>※「表示するカテゴリー」はサイドバーに表示するカテゴリー名を設定します。</p>
				<p>※「除外する商品コード」はカテゴリー別商品一覧ページから除外する商品コードを設定します。</p>

				<?php if ( isset( $_POST['disp_item_by_status'] ) ) :

					//バリデーション
					$_POST['disp_item_by_status'] = array_filter_recursive( $_POST['disp_item_by_status'], null, true ); //空要素削除、index連番の歯抜け修正
					$message = check_item_code( $_POST['disp_item_by_status'] ); //実在する商品コードか？確認

					if ( ! $message ) : //実在する商品コードなら
						update_option( 'abplus_customer_status_settings', wp_unslash( $_POST['disp_item_by_status'] ) );
						?><div id="setting-error-settings_updated" class="updated settings-error notice is-dismissible">
							<p><strong>設定を保存しました。</strong></p>
						</div><?php
					else : //エラーメッセージあれば(実在しない商品コードが含まれる)
						?><div id="setting-error-settings_updated" class="error settings-error notice is-dismissible"><?php echo $message; ?></div><?php
					endif;

				endif;

				$_abplus_status = new Abplus_customer_status;
				$customer_status_settings = $_abplus_status->customer_status_settings; //optionテーブルに保存済みの表示項目設定データ
				$customer_status = $_abplus_status->customer_status; //optionテーブルに保存済みの会員ランク区分の配列(末尾要素に未ログイン状態追加済み)
				$set_data = [ '表示するカテゴリー' => 'category', /*'タグ' => 'post_tag', */'除外する商品コード' => 'item_code' ]; //フォーム作成用の子ループを回すための配列
				$term_list_obj = get_terms(
					[ 'category'/*, 'post_tag'*/ ],
					array(
						'orderby' => 'term_order',
						'child_of' => get_option( 'usces_item_cat_parent_id' )
					)
				); //selectのoptionに表示する ?>

				<form method="post" action="" style="margin-bottom: 1em;" id="poststuff">

					<?php if ( ! empty( $customer_status ) ) {

						foreach ( $customer_status as $index => $status ) {
							echo '<div class="postbox">';
							echo '<h3 class="hndle" style="font-size: 16px;">', esc_html( $status ), '</h3>';
							echo '<table class="form_table inside">';

							//カテゴリー→タグ→商品コードの順に出力
							foreach ( $set_data as $key => $value ) {
								echo '<tbody>'; //jsの削除ボタンクリック時の処理のためにデータ種類(category,post_tag,item_code)ごとにグループ化する

								$length = ( isset( $customer_status_settings[$index][$value] ) && count( $customer_status_settings[$index][$value] ) > 0 ) ? count( $customer_status_settings[$index][$value] ) : 1;

								//保存済みデータがあればその回数を出力、なければ1回だけ出力(保存前にarray_filter_recursiveして空要素を削除している。データ未入力ならempty配列になるのでforeachできない)
								for ( $i = 0; $i < $length; $i++ ) {
									echo '<tr>
									<td style="font-size: 16px;">', $key, '</td>';
									echo '<td style="font-size: 16px;">';

									if ( $key !== '除外する商品コード' ) { //カテゴリー,タグ

										echo '<select name="', 'disp_item_by_status[', $index, '][', $value, '][', $i, ']" style="width: 100%;">';
										echo '<option value="">― 選択 ―</option>';
										if ( $term_list_obj && is_array( $term_list_obj ) ) {
											$selected_slug = ( isset( $customer_status_settings[$index][$value][$i] ) ) ? $customer_status_settings[$index][$value][$i] : '';
											foreach ( $term_list_obj as $term_oj ) {
												if ( $term_oj->taxonomy === $value ) { //同じtaxonomyのtermだけ表示
													echo '<option value="', $term_oj->slug, '"';
													if ( $selected_slug === $term_oj->slug ) {
														echo ' selected';
													}
													echo '>', $term_oj->name, '</option>';
												}
											}
										}
										echo '</select>';

									} else { //商品コード

										echo '<input type="text" name="', 'disp_item_by_status[', $index, '][', $value, '][', $i, ']" size="30" pattern="^[0-9A-Za-z_-]+$" value="';
										if ( isset( $customer_status_settings[$index][$value][$i] ) ) echo esc_attr( $customer_status_settings[$index][$value][$i] );
										echo '">';

									}

									echo '</td>';
									echo_add_del_button( $i, $length, $index, $value );
									echo '</tr>';
								} //end for

								echo '</tbody>';
							} //end foreach ( $set_data as $key => $value )

							echo '</table>';
							echo '<p style="margin-top: 0; padding-left: 12px; padding-right: 12px;">※入力可能文字：半角英数字(英字は大文字小文字どちらも可), ハイフン-, アンダースコア_</p>';
							echo '</div>';

						} //end foreach ( $customer_status as $index => $status )

					} //end if ( ! empty( $customer_status ) ) ?>

					<input type="submit" value="設定する" class="button button-primary" style="font-size: 16px;">

				</form>

			</div><!--.wrap -->
			<!--<script src="<?php //echo get_stylesheet_directory_uri(); ?>/add_del_admin_page_input.js" defer></script>--><?php
		} //end function
	); //end add_submenu_page

} );

/**
 * Welcartカテゴリーウィジェットに表示する項目を、会員ランク別に設定した内容に変更する
 * file : plugins/usc-e-shop/widgets/usces_category.php
 * fn : widget

 * @param string $cquery wp_list_categories関数の引数
 * @param int $cats_term_id 上記引数のchild_ofに設定されたterm_id
 * @return array $cquery 会員ランク別に表示するカテゴリー等の表示条件を設定し直した配列変数
*/
add_filter( 'usces_filter_welcart_category', function( $cquery, $cats_term_id ) {

	//var_dump( $cquery );
	//var_dump( $cats_term_id );
	//wp_list_categories関数の引数として渡す配列変数を上書き
	$cquery = array(
		'orderby' => 'term_order',
		'hide_empty' => false, //投稿のないカテゴリーも表示
		'title_li' => '',
		'child_of' => $cats_term_id, //管理画面ウィジェットページ、Welcartカテゴリーで設定した親カテゴリーのslug(itemに設定した)のterm_id
	);

	//独自会員ランク区分のオブジェクトを生成
	$_abplus_status = new Abplus_customer_status;

	//独自会員ランク別のカテゴリー表示設定を取得して、wp_list_categories関数の引数に設定する
	if ( $_abplus_status->is_customer_status_settings() ) { //会員ランク別のカテゴリー表示設定があれば

		//閲覧者の会員ランクに応じた表示カテゴリー名を配列で取得
		$categories = $_abplus_status->add_display_data_to_array( 'category' );
		/*ar_dump( $categories );
		var_dump( usces_the_member_status( 'return' ) );
		var_dump( abplus_the_member_status( 'return' ) );*/

		//取得した表示カテゴリー名の配列をwp_list_categories関数の引数の仕様に換える
		if ( count( $categories ) > 0 ) { //表示設定されているカテゴリーが1つ以上あれば

			//表示カテゴリー名の配列の値を表示カテゴリーterm_idに変更
			foreach ( $categories as &$value ) {
				$value = get_category_by_slug( $value )->term_id; //カテゴリー名($value)からそのカテゴリーのobjを取得→term_idを取得して$valueに代入
			}

			//表示カテゴリーterm_idの配列を文字列に換える
			$categories_str = implode( ',', $categories ); //表示するterm_idをコンマ区切り文字列にする

			//wp_list_categories関数の引数$cquery['include']に追加
			$cquery['include'] = $categories_str; //表示するカテゴリーを配列変数に追加

		}

	} //end if ( $_abplus_status->is_customer_status_settings() )

	return $cquery;
}, 10, 2 );

/**
 * 
 * file : wp-includes/taxonomy.php
 * fn : wp_set_object_terms

 * @param 
 * @return 
 */
add_action( 'set_object_terms', function( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
	/*if ( $taxonomy === 'category' ) {
		echo '<script>';
		echo 'console.log('. json_encode( $terms ) .')';
		echo '</script>';
		echo '<script>';
		echo 'console.log('. json_encode( $tt_ids ) .')';
		echo '</script>';
		echo '<script>';
		echo 'console.log('. json_encode( $taxonomy ) .')';
		echo '</script>';
	}*/
	/*if ( $object_id == 14051 && $tt_id == 180 ) {
		$tt_id = 179;
	}*/

}, 10, 6 );

/**
 * 会員ランク別にサイドバーの商品リストの表示内容を変える関数
 * (本番サイトでは結局使っていない)
 * @return echo 商品リスト
*/
function display_according_to_member_status() {

	if ( ! usces_is_login() ) {
		return;
	}

	global $wpdb;
	$categories = []; //表示するカテゴリーを入れる配列
	$tags = []; //表示するタグを入れる配列
	$item_codes = []; //表示する商品コードを入れる配列
	$_abplus_status = new Abplus_customer_status;
	$customer_status_settings = $_abplus_status->customer_status_settings; //optionテーブルに保存済みの表示項目設定データ
	$customer_status = maybe_unserialize( get_option( 'usces_customer_status' ) ); //optionテーブルに保存済みの会員ランク区分の配列(未ログイン状態は含めないのでAbplus_customer_statusクラスのインスタンスから値を代入しない)

	//optionテーブルに保存済みの表示項目のデータをSQL文で使えるように配列に入れる
	foreach ( $customer_status as $index => $status ) {
		if ( usces_the_member_status( 'return' ) === $status ) { //会員ランクが一致したら

			if ( is_array( $customer_status_settings[$index]['category'] ) && count( $customer_status_settings[$index]['category'] ) > 0 ) {
				foreach ( $customer_status_settings[$index]['category'] as $value ) {
					$categories[] = $value; // 'category220', 'category500_child_ch', 'category500_child'
				}
			}
			if ( is_array( $customer_status_settings[$index]['post_tag'] ) && count( $customer_status_settings[$index]['post_tag'] ) > 0 ) {
				foreach ( $customer_status_settings[$index]['post_tag'] as $value ) {
					$tags[] = $value; // 'normal_mem', 'normal_a_mem'
				}
			}
			if ( is_array( $customer_status_settings[$index]['item_code'] ) && count( $customer_status_settings[$index]['item_code'] ) > 0 ) {
				foreach ( $customer_status_settings[$index]['item_code'] as $value ) {
					$item_codes[] = $value; // '28285'
				}
			}
			break;

		} //end if ( usces_the_member_status( 'return' ) === $status )
	} //end foreach ( $customer_status as $index => $status )

	$categories_count = count( $categories );
	$tags_count = count( $tags );
	$item_codes_count = count( $item_codes );
	if ( $categories_count === 0 && $tags_count === 0 && $item_codes_count === 0 ) {
		return; //category,post_tag,_itemCodeのいずれもデータがない場合
	}

	$prefix = $wpdb->prefix;
	$query =
	"SELECT DISTINCT {$prefix}posts.*
	 FROM {$prefix}posts
	 INNER JOIN {$prefix}term_relationships
	 ON {$prefix}posts.ID = {$prefix}term_relationships.object_id
	 INNER JOIN {$prefix}postmeta
	 ON {$prefix}term_relationships.object_id = {$prefix}postmeta.post_id
	 WHERE {$prefix}posts.post_mime_type = %s
	 AND {$prefix}posts.post_status = %s
	 AND (";
	$placeholders = [ 'item', 'publish' ];

	//category,post_tag,_itemCodeのどれか1つはあることを確認済み
	if ( $categories_count > 0 ) {
		foreach ( $categories as $slug ) {
			$term_obj = get_term_by( 'slug', $slug, 'category' );
			if ( $term_obj ) $placeholders[] = $term_obj->term_id;
		}
		$categories_in = substr( str_repeat( ',%d', $categories_count ), 1 ); //1文字目は','なので2文字目(開始位置:1)から末尾まで取得して代入

		$query .= "
		( {$prefix}term_relationships.term_taxonomy_id IN ( {$categories_in} ) )";
	}

	if ( ( $categories_count > 0 && $tags_count > 0 ) || ( $categories_count > 0 && $tags_count === 0 && $item_codes_count > 0 ) ) {
		$query .= "
		OR";
	}

	if ( $tags_count > 0 ) {
		foreach ( $tags as $slug ) {
			$term_obj = get_term_by( 'slug', $slug, 'post_tag' );
			if ( $term_obj ) $placeholders[] = $term_obj->term_id;
		}
		$tags_in = substr( str_repeat( ',%d', $tags_count ), 1 );

		$query .= "
		( {$prefix}term_relationships.term_taxonomy_id IN ( {$tags_in} ) )";
	}

	if ( $tags_count > 0 && $item_codes_count > 0 ) {
		$query .= "
		OR";
	}

	if ( $item_codes_count > 0 ) {
		$placeholders[] = '_itemCode';
		foreach ( $item_codes as $code ) {
			$placeholders[] = $code;
		}
		$item_codes_in = substr( str_repeat( ',%s', $item_codes_count ), 1 );

		$query .= "
		( {$prefix}postmeta.meta_key = %s AND {$prefix}postmeta.meta_value IN ( {$item_codes_in} ) )";
	}

	$query .= "
	)";

	$posts_obj = $wpdb->get_results( $wpdb->prepare( $query, $placeholders ) );

	if ( $posts_obj ) {

		//widget__titleを作成
		/*$categories = explode( ',', $categories ); //delimiter無しでも元の文字列がそのまま要素1つの配列として返される
		$categories = array_map( 'trim', $categories );
		$cat_label = '';
		for ( $i = 0; $i < count( $categories ); $i++ ) {
			$cat_label .= get_term_by( 'slug', $categories[$i], 'category' )->name;
			if ( $i !== count( $categories ) - 1 ) {
				$cat_label .= ',';
			}
		}*/

		//var_dump($posts_obj);
		echo '<div class="p-widget p-widget-sidebar styled_post_list_tab_widget">';
		echo '<h2 class="p-widget__title">', usces_the_member_name( 'return' ), '様へ特別のご案内</h2>';
		echo '<ul class="p-widget-list">';

		foreach ( $posts_obj as $value ) {
			echo '<li class="p-widget-list__item"><a class="p-hover-effect--type1" href="', esc_url( get_permalink( $value->ID ) ), '">'; ?>

				<div class="p-widget-list__item-thumbnail"><?php
					$picposts = get_posts( array(
						'posts_per_page' => 1,
						'name' => get_post_meta( $value->ID, '_itemCode', true ),
						'post_type' => 'attachment',
					) );
					$pictid = ( empty( $picposts ) ) ? 0 : $picposts[0]->ID;
					if ( $pictid ) :
						?><img src="<?php echo wp_get_attachment_url( $pictid ); ?>" alt=""><?php
					else :
						echo '<img src="' . get_template_directory_uri() . '/img/no-image-300x300.gif" alt="no-image">';
					endif;
				?></div>

				<div class="p-widget-list__item-info">
					<div class="p-widget-list__item-info__upper">
						<h3 class="p-widget-list__item-title p-article__title"><?php echo esc_html( $value->post_title ); ?></h3>
						<?php $skus = maybe_unserialize( get_post_meta( $value->ID, '_isku_', false ) );
						if ( $skus ) {
							foreach ( $skus as $skus_v ) {
								echo '<p class="p-widget-list__item-price p-price">¥';
								echo esc_html( number_format( $skus_v['price'] ) ). usces_guid_tax( 'return' );
								echo '</p>';
							}
						} ?>
					</div>
				</div><?php

			echo '</a></li>';
		} //end foreach

		echo '</ul>';
		echo '</div>';

	} //end if ( $posts_obj )

}
