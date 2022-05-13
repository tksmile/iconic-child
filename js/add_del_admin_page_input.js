/*入力欄追加・削除ボタンがクリックされた時に入力欄を追加・削除するscript
 customize/abplus_customer_status.phpから読み込まれる*/

document.addEventListener( 'DOMContentLoaded', function() {

	/*name="hoge[0]"→name="hoge[1]"にする関数(連番を+1加算)*/
	const replaceIndexNum = function( elementWithIndexNum ) { //name="hoge[\d]"を持つ要素を渡す
		let num = elementWithIndexNum.name.match( /\[(\d+)\]$/ ); //[連番]にマッチした部分を格納した配列を作成(2個目の要素(num[1])に(\d)でグループ化された数字だけが入る)
		num = ( num[1] - 0 ) + 1; //連番数値[(\d)]に1を加算
		num = num.toString(); //+1した数値を文字列型に
		elementWithIndexNum.name = elementWithIndexNum.name.replace( /\[(\d+)\]$/, '[' + num + ']' ); //[連番]を+1した値に置換
		let ary = [num, elementWithIndexNum]; //stringと要素、2つの戻り値を入れる配列
		return ary;
	};

	/*入力欄削除ボタン クリック時に実行する処理の関数*/
	const delFieldListener = function() {
		let _tr = this.parentElement.parentElement; //this(削除ボタン)の親の親の要素(tr)

		if ( this.id === _tr.parentElement.lastElementChild.lastElementChild.children[1].id ) { //クリックした削除ボタンが最後のランク区分に含まれるボタンなら(trの親(tbody)の最後の子要素(tr)の最後の子要素(td)の2番目の子要素のid属性値と同じ)
			_tr.previousElementSibling.children[2].children[0].disabled = false; //1つ前のランク区分の追加ボタンを有効に
		}

		_tr.parentElement.removeChild( _tr ); //this(削除ボタン)の親の親の要素(tr)を削除

	};

	/*入力欄追加ボタン クリック時に実行する処理の関数*/
	const addNewFieldListener = function() {

		//inputの祖父フィールド(tr)をクローンする
		let _clone_field = this.parentElement.parentElement.cloneNode( true ); //クリックされたボタンの親(td)の親(tr)を子要素(input)も含めてクローン
		let _input_of_clone_field = _clone_field.children[1].children[0]; //trの2番目の子(td)の1番目の子(input)

		//クローンを加工する
		let ary = replaceIndexNum( _input_of_clone_field ); //inputのname属性を加工した値を入れた配列

		_input_of_clone_field = ary[1]; //name属性値の[連番]に+1加算

		_add_button_id = _clone_field.children[2].children[0].id; //追加ボタンのid属性値
		//_clone_field.children[2].children[0].id = _add_button_id + ary[0];
		_add_button_id = _add_button_id.replace( /(\d+)$/, ary[0] ); //末尾の連番数値を+1加算した数値に置換
		_clone_field.children[2].children[0].id = _add_button_id; //連番数値を+1加算したid属性値をクローンに代入

		_del_button_id = _clone_field.children[2].children[1].id; //削除ボタンのid属性値
		//_clone_field.children[2].children[1].id = _del_button_id + ary[0];
		_del_button_id = _del_button_id.replace( /(\d+)$/, ary[0] ); //末尾の連番数値を+1加算した数値に置換
		_clone_field.children[2].children[1].id = _del_button_id; //連番数値を+1加算したid属性値をクローンに代入

		_input_of_clone_field.value = ''; //子要素(input)のvalue値を空にする
		_clone_field.children[2].children[1].disabled = false; //削除ボタン有効
		if ( _add_button_id.indexOf( 'add_status' === 0 ) ) { //独自会員ランク区分登録ページなら
			this.disabled = true; //(クリックした)追加ボタン無効
		}

		//クローンを挿入する
		this.parentElement.parentElement.parentElement.insertBefore( _clone_field, this.parentElement.parentElement.nextElementSibling ); //this(追加ボタン)の親(td)の親(tr)の親(table)に、処理を施したクローンを挿入。場所はthis(追加ボタン)の親(td)の親(tr)の次の兄弟(tr)の前

		//動的に追加された追加・削除ボタンにイベントリスナー設定
		document.getElementById( _add_button_id ).addEventListener( 'click', addNewFieldListener ); //addNewFieldボタンにリスナー設定
		document.getElementById( _del_button_id ).addEventListener( 'click', delFieldListener ); //delFieldボタンにリスナー設定

	};

	/*入力欄追加ボタン*/
	let _add_new_field = document.querySelectorAll( '#poststuff .add_new_field' ); //追加ボタンの静的NodeList
	for ( let i = 0; i < _add_new_field.length; i++ ) {
		_add_new_field[i].addEventListener( 'click', addNewFieldListener );
	}

	/*入力欄削除ボタン*/
	let _del_field = document.querySelectorAll( '#poststuff .del_field:not([disabled])' ); //クリック可能な追加ボタンの静的NodeList
	for ( let i = 0; i < _del_field.length; i++ ) {
		_del_field[i].addEventListener( 'click', delFieldListener );
	}

} ); //end addEventListener( 'DOMContentLoaded', function()
