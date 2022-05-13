<?php
/**
 * file : usc-e-shop/functions/template_func.php
 * fn : usces_loginout
 * @return string aリンクの文字列
*/
add_filter( 'usces_filter_loginlink_label', function() {
	if ( usces_is_item() || is_category() ) {
		return '注文するために<br class="u-hidden-xs">ログインする';
	}
} );

/**
 * item-category.phpのmain_queryをカスタマイズ
 * ID昇順でソート
 * 会員ランク別に設定した商品コードを除外
*/
add_action( 'pre_get_posts', function( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( $query->is_category() ) { //商品一覧ページなら

		//独自会員ランク区分のオブジェクトを生成
		$_abplus_status = new Abplus_customer_status;

		if ( $_abplus_status->is_customer_status_settings() ) {

			//除外する商品コードの配列を取得
			$item_codes = $_abplus_status->add_display_data_to_array( 'item_code' );

			//除外商品を除くクエリーを設定
			if ( count( $item_codes ) > 0 ) { //除外設定されている商品コードが1つ以上あれば
				$query->set( 'meta_query',
					array(
						'relation' => 'AND',
						array(
							'key' => '_itemCode',
							'value' => $item_codes, //除外する商品コードの配列
							'compare' => 'NOT IN', //除外
						)
					)
				);
			}

		} //end if ( $_abplus_status->is_customer_status_settings() )

		//商品を並び替える
		$query->set( 'orderby', array(
			'menu_order' => 'DESC',
			'date' => 'ASC',
			'ID' => 'ASC'
			)
		);

	} //end if ( $query->is_category() )
} );

/**
 * 商品一覧ページでカートに入れた際に、在庫チェック等の入力値をバリデーションするjsをfooterに出力する
 * file :wp-includes/script-loader.php
 * fn :wp_print_footer_scripts
 * @return script 出力
*/
add_action( 'wp_print_footer_scripts', function() {

	if ( is_category() ) { ?>

		<script>
		(function($) {
		uscesCart = {

			intoCart : function (post_id, sku) {

				//ここから通常の(item_single.phpと同じ)uscesCart.intoCartメソッドでバリデート
				var zaikonum = $("[id='zaikonum["+post_id+"]["+sku+"]']").val();
				var zaiko = $("[id='zaiko["+post_id+"]["+sku+"]']").val();
				if( <?php echo apply_filters( 'usces_intoCart_zaiko_check_js', "( uscesL10n.itemOrderAcceptable != '1' && zaiko != '0' && zaiko != '1' ) || ( uscesL10n.itemOrderAcceptable != '1' && parseInt(zaikonum) == 0 )" ); ?> ){
					alert('<?php _e('temporaly out of stock now', 'usces'); ?>');
					return false;
				}

				var mes = '';
				if( $("[id='quant["+post_id+"]["+sku+"]']").length ){
					var quant = $("[id='quant["+post_id+"]["+sku+"]']").val();
					if( quant == '0' || quant == '' || !(uscesCart.isNum(quant))){
						mes += "<?php _e('enter the correct amount', 'usces'); ?>\n";
					}
					var checknum = '';
					var checkmode = '';
					if( parseInt(uscesL10n.itemRestriction) <= parseInt(zaikonum) && uscesL10n.itemRestriction != '' && uscesL10n.itemRestriction != '0' && zaikonum != '' ) {
						checknum = uscesL10n.itemRestriction;
						checkmode ='rest';
					} else if( uscesL10n.itemOrderAcceptable != '1' && parseInt(uscesL10n.itemRestriction) > parseInt(zaikonum) && uscesL10n.itemRestriction != '' && uscesL10n.itemRestriction != '0' && zaikonum != '' ) {
						checknum = zaikonum;
						checkmode ='zaiko';
					} else if( uscesL10n.itemOrderAcceptable != '1' && (uscesL10n.itemRestriction == '' || uscesL10n.itemRestriction == '0') && zaikonum != '' ) {
						checknum = zaikonum;
						checkmode ='zaiko';
					} else if( uscesL10n.itemRestriction != '' && uscesL10n.itemRestriction != '0' && ( zaikonum == '' || zaikonum == '0' || parseInt(uscesL10n.itemRestriction) > parseInt(zaikonum) ) ) {
						checknum = uscesL10n.itemRestriction;
						checkmode ='rest';
					}

					if( parseInt(quant) > parseInt(checknum) && checknum != '' ){
						if(checkmode == 'rest'){
							mes += <?php _e("'This article is limited by '+checknum+' at a time.'", 'usces'); ?>+"\n";
						}else{
							mes += <?php _e("'Stock is remainder '+checknum+'.'", 'usces'); ?>+"\n";
						}
					}
				}
				for(i=0; i<uscesL10n.key_opts.length; i++){
					if( uscesL10n.opt_esse[i] == '1' ){
						var skuob = $("[id='itemOption["+post_id+"]["+sku+"]["+uscesL10n.key_opts[i]+"]']");
						var itemOption = "itemOption["+post_id+"]["+sku+"]["+uscesL10n.key_opts[i]+"]";
						var opt_obj_radio = $(":radio[name*='"+itemOption+"']");
						var opt_obj_checkbox = $(":checkbox[name*='"+itemOption+"']:checked");

						if( uscesL10n.opt_means[i] == '3' ){

							if( !opt_obj_radio.is(':checked') ){
								mes += uscesL10n.mes_opts[i]+"\n";
							}

						}else if( uscesL10n.opt_means[i] == '4' ){

							if( !opt_obj_checkbox.length ){
								mes += uscesL10n.mes_opts[i]+"\n";
							}

						}else{

							if( skuob.length ){
								if( uscesL10n.opt_means[i] == 0 && skuob.val() == '#NONE#' ){
									mes += uscesL10n.mes_opts[i]+"\n";
								}else if( uscesL10n.opt_means[i] == 1 && ( skuob.val() == '' || skuob.val() == '#NONE#' ) ){
									mes += uscesL10n.mes_opts[i]+"\n";
								}else if( uscesL10n.opt_means[i] >= 2 && skuob.val() == '' ){
									mes += uscesL10n.mes_opts[i]+"\n";
								}
							}
						}
					}
				}

				<?php //apply_filters( 'usces_filter_inCart_js_check', $item->ID ); //Unavailable ?>
				<?php //do_action( 'usces_action_inCart_js_check', $item->ID ); ?>

				if( mes != '' ){
					alert( mes );
					return false;
				}/*else{
					return true;
				}*/
				//ここまで通常の(item_single.phpと同じ)uscesCart.intoCartメソッドでバリデート

				//ここからwidgetcart.intoCartメソッドでajax通信
				$("#wgct_alert").removeClass("completion_box delete_box");
				$("#wgct_alert").addClass("update_box");
				$('#wgct_alert').css(wcwc_cssObj);
				var loading = '<img src="'+uscesL10n.widgetcartUrl+'/images/loading.gif" /><br />'+uscesL10n.widgetcartMes03+'<br />'+uscesL10n.widgetcartMes04;
				$('#wgct_alert').html(loading);

				var quant = 'quant['+post_id+']['+sku+']';
				var inCart = 'inCart['+post_id+']['+sku+']';
				var skuPrice = 'skuPrice['+post_id+']['+sku+']';

				var itemOption = 'itemOption['+post_id+']['+sku+']';
				var opt_obj_text = $(":text[name*='"+itemOption+"']");
				var opt_obj_textarea = $("textarea[name*='"+itemOption+"']");
				var opt_obj_select = $("select[name*='"+itemOption+"']");
				var opt_obj_radio = $(":radio[name*='"+itemOption+"']:checked");
				var opt_obj_checkbox = $(":checkbox[name*='"+itemOption+"']:checked");

				var s = widgetcart.settings;
				s.data = "widgetcart_ajax=1";
				s.data += "&widgetcart_action=intoCart";
				$.each( opt_obj_text, function( opt, element ){
					var idname = $(this).attr("id");
					if( idname.indexOf("regular") != -1 ) {
					} else {
						s.data += "&" + itemOption + encodeURIComponent($(element).attr("name").substr( itemOption.length )) + "=" + encodeURIComponent($(element).val());
					}
				});
				$.each( opt_obj_textarea, function( opt, element ){
					var idname = $(this).attr("id");
					if( idname.indexOf("regular") != -1 ) {
					} else {
						s.data += "&" + itemOption + encodeURIComponent($(element).attr("name").substr( itemOption.length )) + "=" + encodeURIComponent($(element).val());
					}
				});
				$.each( opt_obj_select, function( opt, element ){
					var idname = $(this).attr("id");
					if( idname.indexOf("regular") != -1 ) {
					} else {
						s.data += "&" + itemOption + encodeURIComponent($(element).attr("name").substr( itemOption.length )) + "=" + encodeURIComponent($(element).val());
					}
				});
				$.each( opt_obj_radio, function( opt, element ){
					var idname = $(this).attr("id");
					if( idname.indexOf("regular") != -1 ) {
					} else {
						s.data += "&" + itemOption + encodeURIComponent($(element).attr("name").substr( itemOption.length )) + "=" + encodeURIComponent($(element).val());
					}
				});
				$.each( opt_obj_checkbox, function( opt, element ){
					var idname = $(this).attr("id");
					if( idname.indexOf("regular") != -1 ) {
					} else {
						s.data += "&" + itemOption + encodeURIComponent($(element).attr("name").substr( itemOption.length )) + "=" + encodeURIComponent($(element).val());
					}
				});
				if( $("select[name='"+quant+"']").length ){
					 s.data += "&" + quant + "=" + $("select[name='"+quant+"'] option:selected").val();
				}else{
					if( 0 === $("input[name='"+quant+"']").length ){
						s.data += "&" + quant + "=1";
					}else{
						s.data += "&" + quant + "=" + $("input[name='"+quant+"']").val();
					}
				}
				s.data += "&" + inCart + "=" + $("input[name='"+inCart+"']").val();
				s.data += "&" + skuPrice + "=" + $("input[name='"+skuPrice+"']").val();
				$.ajax( s ).done(function(data, dataType){
					$("#wgct_row").html( data );
					$("#wgct_alert").removeClass("update_box delete_box");
					$("#wgct_alert").addClass("completion_box");
					$('#wgct_alert').html(uscesL10n.widgetcartMes01);
					$('#wgct_alert').fadeOut(uscesL10n.widgetcart_fout);
				}).fail(function(msg){
					console.log(msg);
				})
				return false;
				//ここまでwidgetcart.intoCartメソッドでajax通信

			}, //end intoCart : function (post_id, sku)

			isNum : function (num) {
				if (num.match(/[^0-9]/g)) {
					return false;
				}
				return true;
			}
		};
		})(jQuery);
		</script><?php

	} //end if ( is_category() )

} );

?>