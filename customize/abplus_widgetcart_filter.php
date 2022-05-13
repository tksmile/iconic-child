<?php
/**
 *カート内の商品について、カテゴリーごとに、商品の種類数、商品入数の合計値を表示する
 *file : plugins/wcex_widget_cart/wcex_widget_cart.php
 *fn : widgetcart_get_cart_row

 *@param int $total_price 商品合計金額
 *@param array $cart カートに入っている商品データの配列
 *@return int $total_price 何も触らず受け取った状態のまま返す
*/
add_filter( 'widgetcart_filter_total_price', function( $total_price, $cart ) {
	/*echo '<pre style="text-align: left;">';
	var_dump($cart);
	echo '</pre>';*/
	$meta = []; //categoryと_abplus_quantity(商品入数)の値を入れる連想配列
	$meta_custom = []; //$metaの要素から作る2次元連想配列(categoryをkeyに、post_id=>'quantity'の値をvalueに)。output表示の基になる配列
	$has_category; //ポストにカテゴリーが付いていればtrue,nullならnormal_price通常単価を使う

	/**
	 * オブジェクトの祖先要素の数を比較して「多い・同じ・少ない」それぞれに応じた数値を返す(usort関数のcallback引数専用)
	 * @param WP_Term $a 比較するカテゴリーのobject
	 * @param WP_Term $b 比較するカテゴリーのobject
	 * @return int 1:$bの方が祖先要素数が多い, 0:$aと$bの祖先要素数が同じ, -1:$aの方が祖先要素数が多い
	 */
	function compare_ancestors_count( $a, $b ) {
		if ( count( $a->ancestors ) === count( $b->ancestors ) ) {
			return 0;
		}
		return ( count( $a->ancestors ) < count( $b->ancestors ) ) ? 1 : -1; //DESC降順でソート
	}

	//(array)$cartのデータから(array)$meta, (array)$meta_custom, (bool)$has_categoryを作成
	foreach ( $cart as $key => $value) {

		$categories = wp_get_post_terms( $value['post_id'], 'category' ); //postに付いてるカテゴリータグをobjの配列$categoriesに代入(付いてなければWP_Errorが返る)

		//$metaのquantity(商品入数)を設定
		if ( isset( $value['advance'] ) && $value['advance'] ) { //advanceフィールドの値があれば(商品仕様の全項目が未入力でも値は'##########'なので、値ありになる)
			$advance_ary = AbplusSpec::convert_array( $value['advance'] ); //区切り文字で分割したstringを要素として配列化
			$meta[$key]['quantity'] = $advance_ary[3]; //advanceの商品入数($advance_aryの4番目の要素)を設定(商品入数の値が未入力の場合は空値が設定される)
		} else { //advanceフィールドの値がなければ
			$meta[$key]['quantity'] = ''; //空値を設定
		}

		//$metaのcategoryを設定
		if ( ! is_wp_error( $categories ) ) { //categoryが付いていれば

			foreach ( $categories as $cat_k => $cat_v ) {
				if ( $cat_v->slug === 'item' ) { //先祖(item)なら
					unset( $categories[$cat_k] ); //削除する
				} else { //先祖(item)の子や孫なら
					$cat_v->ancestors = array_reverse( get_ancestors( $cat_v->term_id, 'category' ) ); //$categoriesの要素(obj)に新たなプロパティとして祖先term_idの配列(先祖側が先頭)を追加
				}
			}

			usort( $categories, 'compare_ancestors_count' ); //$categoriesを祖先要素数の多い順に並べ替える
			/*echo '<pre style="text-align: left;">';
			var_dump($categories);
			echo '</pre>';*/

			if ( count( $categories ) > 0 ) { //並べ替え後の$categoriesに要素があれば
				$meta[$key]['category'] = $categories[0]->name; //最初の要素(祖先要素数が一番多い＝最下層のカテゴリー)
				$has_category = true; //後の条件分岐で使うので「カテゴリーあり」を代入
			} else { //並べ替え後の$categoriesに要素がなければ
				goto no_category; //no_categoryへ強制移動
			}

		} else { //categoryが付いてなければ

			no_category :
			$meta[$key]['category'] = $advance_ary[1]; //2番目の要素(通常単価を使う)
			//$meta[$key]['category'] = get_post_meta( $value['post_id'], '_abplus_normal_price', true ); //通常単価を使う

		} //end if ( ! is_wp_error( $categories ) )

		//$metaのデータから$meta_customを作成
		if ( $meta[$key]['category'] && $meta[$key]['quantity'] ) {
			$meta_custom[] = [
				$meta[$key]['category'] => [
				$value['post_id'] => (int)$meta[$key]['quantity'] * $value['quantity'] //商品入数×cartの数量
				]
			];
		}

	} //end foreach ( $cart as $key => $value)
	/*echo '<pre style="text-align: left;">';
	var_dump($meta);
	var_dump($meta_custom);
	echo '</pre>';*/
	$key_for_check = []; //key：normal_priceの値がループ内で既出か？未出か？をチェックするための配列(追加した配列のkeyを順次入れていく)
	foreach ( $meta_custom as $key => &$value ) { //$keyはindex番号

		if ( in_array( key( $value ), $key_for_check, true ) ) { //key：normal_priceの値が既に親ループで出現済みなら
			unset( $meta_custom[$key] ); //(その配列は既に子ループを回して同じkey：normal_priceの配列に追加結合済みなので)削除する

		} else { //key：normal_priceの値が親ループで初出現なら

			//normal_priceの値(key)が同じ配列を結合する
			foreach ( $meta_custom as $k => $v ) { //子ループを回す($kはindex番号)
				if ( $key !== $k && key( $value ) === key( $v ) ) { //key：normal_priceの値が同じなら
					$value[key( $value )] += $v[key( $v )]; //親ループの配列の要素として子ループの配列の要素を追加
					/*echo '<pre style="text-align: left;">';
					var_dump( $value );
					echo '</pre>';*/
					$key_for_check[] = key( $value ); //同じ値があったnormal_priceの配列
				}
			} //end 子foreach

		} //end else

	} //end 親foreach
	unset( $value );

	/*echo '<pre style="text-align: left;">';
	var_dump($meta_custom);
	var_dump($key_for_check);
	echo '</pre>';*/

	if ( ! empty( $meta_custom ) ) {
		echo '<table class="widgetcart_rows mb10">';
		foreach ( $meta_custom as $key => $value ) {
			echo '<tr><th class="header" style="background-color: #66C2CC; color: #fff;">', key( $value ); //#d90000
			if ( ! $has_category ) echo '円商品'; //ラベルが通常単価の場合は末尾に'円商品'付加
			echo '</th>';
			echo '<th class="header align3 pl10">', count( $value[key( $value )] ), '種 ', array_sum( $value[key( $value )] ), '個</th></tr>';
		}
		echo '</table>';
	}

	return $total_price; //一切触らずに戻す。カテゴリー別に商品の種類数、商品入数の合計値を表示するためだけにこのフィルターフックを利用した
}, 10, 2 );

?>