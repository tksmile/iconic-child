jQuery.fn.splitfield = function() {
	let g_count = 0;
	let first_setval; //先頭の商品仕様データを入れる変数
	let splits = Object.keys( _spec_args_json ).length; //商品仕様項目数

	return this.each( function() { //thisはinput.splitfield
		if ( jQuery( this ).is( ':visible' ) ) { //オートセーブなどのときに重複処理しないように

			let field = jQuery( this ).hide(), // td直下のinput.splitfieldをdisplay:noneする
				//separator = '##', //区切文字abplus_spec_by_member.phpのscriptで読込み済み
				setval = field.val() ? field.val().split( separator ) : [], //input.splitfieldに値(DB保存値)があれば、separatorで分割したstring値を格納した配列を、なければ空配列を、代入
				input = [], //入力用inputをwrapするp.item-sku-smallfieldを格納する配列
				value = '', //eachループ内で、separatorで分割した値を代入する変数
				_splitfieldName = ( this.name === 'newskuadvance' ) ? 'newskuadvance' : ''; //新規SKU追加欄にある本体inputならname属性値を代入(新規追加か否かのflag用)

			if ( g_count === 0 ) { //初回ループなら
				first_setval = setval; //商品仕様データの配列を代入
			}
			//console.log( first_setval );

			//selectのoption部分のhtmlを作成
			let select_option = '<option value="">― 選択 ―</option>';
			for ( let key in _ratio_args_json ) { //オブジェクト_ratio_args_jsonはabplus_spec_by_member.phpのscriptで読込み済み
				if ( _ratio_args_json.hasOwnProperty( key ) ) {
					select_option = select_option + '<option value="' + key + '"';
					if ( _splitfieldName !== 'newskuadvance' && key === setval[0] ) {
						select_option = select_option + ' selected';
					}
					select_option = select_option + '>' + key + '</option>';
				}
			} //end for in

			//独自sku入力欄のhtmlを作成
			let _count = 0;
			jQuery.each( //商品仕様の項目数回繰り返す
				_spec_args_json, //ループする商品仕様データのobj
				function( key, ary ) { //_spec_args_jsonのkey, _spec_args_jsonのvalue(=配列)
					//console.log( first_setval );
					if ( _splitfieldName !== 'newskuadvance' && ary[3] === true ) { //新規SKU追加欄でなく、かつ、入力が必要な項目
						value = ( setval[_count] ) ? setval[_count] : '';
					} else if ( ary[3] === false ) { //基の(先頭の)SKUの値を使う
						value = ( first_setval !== undefined && first_setval[_count] ) ? first_setval[_count] : '';
					}
					let _disabled = ( g_count !== 0 && ary[3] === false ) ? ' disabled' : ''; //先頭以外のSKUで、入力値に先頭SKUの値を使う項目ならdisabledにする

					if ( ary[2] === 'select' ) {
						input.push( '<p class="item-sku-smallfield"><span class="span--before">' + key + '</span><select name="' + ary[0] + '" class="item-sku-smallfield__ctr">' + select_option + '</select><span class="span--after">' + ary[1] + '</span></p>' );
					} else { //number,text
						input.push(  '<p class="item-sku-smallfield"><span class="span--before">' + key + '</span><input name="' + ary[0] + '" class="item-sku-smallfield__ctr" type="' + ary[2] + '" value="' + value + '"' + _disabled + '/><span class="span--after">' + ary[1] + '</span></p>' );
					}

					_count++;
				}
			); //end jQuery.each

			var div = jQuery( '<div>' + input.join( '' ) + '</div>' ).children().each( //this(div)の子要素である入力用input,selectをwrapする各p.item-sku-smallfieldに
				function() { //処理を施す
					let values = []; //input,selectの値を格納する配列
					let _nodeName, _eventType;

					//input,selectどちらにも対応できるように、イベント時の処理に必要なデータを変数化
					if ( jQuery( this ).children().eq( 1 )[0]['nodeName'] === 'INPUT' ) {
						_nodeName = 'input';
						_eventType = 'blur';
					} else if ( jQuery( this ).children().eq( 1 )[0]['nodeName'] === 'SELECT' ) {
						_nodeName = 'select';
						_eventType = 'change';
					}

					jQuery( 'td.item-sku-advance' ).on( _eventType, _nodeName + '.item-sku-smallfield__ctr', function() { //this(p.item-sku-smallfield)の子要素であるinputがフォーカスアウト、selectがchangeした時の処理
						jQuery( this ).parent( 'p.item-sku-smallfield' ).siblings().addBack().each( //this(input,select)の親要素であるpと、その同階層要素(=p.item-sku-smallfield)すべてを加えて(addBack)、各p.item-sku-smallfieldに
							function() { //処理を施す
								if ( values.length >= splits ) { //配列要素数が入力項目数以上なら
									values = []; //配列初期化
								}
								values.push( jQuery( this ).children().eq( 1 ).val() ); //this(p.item-sku-smallfield)の子要素であるinput,selectの値を配列valuesの要素として追加
							}
						);
						field.val( values.join( separator ) ); //配列valuesの各要素値を結合したstringを本体input.spiltfieldの値に代入
					} );

				} //end function
			).end(); //end each
			field.after( div ); //本体input.spiltfieldの直後にdiv(入力欄)を挿入

		} //end if ( jQuery( this ).is( ':visible' ) )
		g_count++;
	} ); //end function end return this.each(thisはinput.splitfield)

}; //end fn.splitfield

jQuery( '.splitfield', '#itemsku' ).splitfield(); //#itemsku内の.splitfieldをsplitする
jQuery( document ).ajaxSuccess( function( event, xhr, settings ) { //welcartのajax通信成功後の処理(jQuery1.9以降documentにしか登録できなくなった)
	if ( xhr.responseJSON['meta_row'] ) { //meta_rowのプロパティはtr.metastuffrow(各SKUごとのデータを表示するtableの親要素)のhtml文。このif条件がないと、定間隔で繰り返されるajaxによるwp-auth-check(ログインチェック)で毎回ajaxSuccessが発火(xhr.responseJSON['wp-auth-check'] === true)する。
		jQuery( '#newsku' ).find( '.splitfield' ).show().next().remove(); //新しいSKUの追加table内で、input.splitfieldが非表示なら表示し、次の要素divを削除する
		jQuery( '#itemsku' ).find( '.splitfield' ).splitfield(); //#itemsku内の.splitfieldを再splitする
	}
} );
