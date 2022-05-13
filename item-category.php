<?php
$dp_options = get_design_plus_option();

if ( is_post_type_archive( $dp_options['news_slug'] ) ) :
	get_template_part( 'archive-news' );
	return;
endif;

$active_sidebar = get_active_sidebar();
get_header();
?>
<main class="l-main">
	<?php
	get_template_part( 'template-parts/page-header' );
	get_template_part( 'template-parts/breadcrumb' );

	if ( $active_sidebar ) : ?>
		<div class="l-inner l-2columns">
			<div class="l-primary"><?php
	else : ?>
		<div class="l-inner"><?php
	endif;

	if ( have_posts() ) :
	?>
		<div class="p-item-archive">

			<?php global $usces;// var_dump( $usces->get_skus( $post->ID ) );
			$cs_status_abbr= get_customer_status_abbr();

			while ( have_posts() ) :
				the_post();
				$usces_is_item = false;
				if ( function_exists( 'usces_the_item' ) ) :
					//usces_the_item();
					$org_skus = abplus_the_item( $cs_status_abbr );
					$usces_is_item = usces_is_item() && usces_have_skus();
				endif; ?>

				<article class="p-item-archive__item">
					<a class="p-hover-effect--<?php echo esc_attr( $dp_options['hover_type'] ); ?>" style="height: auto; padding-bottom: 0;" href="<?php the_permalink(); ?>">
						<div class="p-item-archive__item-thumbnail p-hover-effect__image">
							<div class="p-item-archive__item-thumbnail__inner js-object-fit-cover">
								<?php
								echo "\t\t\t\t\t\t\t\t";
								if ( $usces_is_item && usces_the_itemImageURL( 0, 'return' ) ) :
									usces_the_itemImage( 0, 500, 500 );
								elseif ( has_post_thumbnail() ) :
									the_post_thumbnail( 'size2' );
								else :
									echo '<img src="' . get_template_directory_uri() . '/img/no-image-500x500.gif" alt="">';
								endif;
								if ( $usces_is_item && ! usces_have_zaiko_anyone() ) :
									echo '<div class="p-article__thumbnail-soldout u-visible-sm"><span class="p-article__soldout">'. __( 'Sold Out', 'tcd-w' ) . '</span></div>';
								endif;
								echo "\n"; ?>
							</div>
						</div>
					</a>

					<div class="p-item-archive__item-info">
						<a href="<?php the_permalink(); ?>" style="padding-top: 0; padding-bottom: 0;">

							<h2 class="p-item-archive__item-title p-article-item__title p-article__title"><?php echo mb_strimwidth( strip_tags( get_the_title() ), 0, is_mobile() ? 82 : 108, '...' ); ?></h2>

							<?php
							if ( $usces_is_item ) :

								echo "\t\t\t\t\t\t\t";
								echo '<p class="p-item-archive__item-price p-article__price">¥'. number_format( (int)$usces->itemsku['price'] ). usces_guid_tax( 'return' );
								if ( ! usces_have_zaiko_anyone() ) :
									echo '<span class="p-item-archive__item-soldout p-article__soldout u-hidden-sm">'. __( 'Sold Out', 'tcd-w' ) . '</span>';
								endif;
								echo '</p>';

								if ( $dp_options['show_date_item'] || $dp_options['show_category_item'] ) :
									echo "\t\t\t\t\t\t\t";
									echo '<p class="p-item-archive__item-meta p-article__meta">';
									if ( $dp_options['show_date_item'] ) :
										echo '<time class="p-article__date" datetime="' . get_the_time( 'Y-m-d' ) . '">' . get_the_time( 'Y.m.d' ) . '</time>';
									endif;
									if ( $dp_options['show_category_item'] ) :
										$categories = array( get_welcart_category() );
										if ( $categories && ! is_wp_error( $categories ) ) :
											echo '<span class="p-article__category" data-url="' . get_category_link( $categories[0] ) . '">' . esc_html( $categories[0]->name ) . '</span>';
										endif;
									endif;
									echo "</p>\n";
								endif;

								//商品仕様を表示
								echo_abplus_spec_by_member( $post, $org_skus['code'], $usces->itemsku['advance'] );

							else : //itemでない場合

								if ( $dp_options['show_date'] || ( ! is_category() && $dp_options['show_category'] && has_category() ) ) :
									echo "\t\t\t\t\t\t\t";
									echo '<p class="p-item-archive__item-meta p-article__meta">';
									if ( $dp_options['show_date'] ) :
										echo '<time class="p-article__date" datetime="' . get_the_time( 'Y-m-d' ) . '">' . get_the_time( 'Y.m.d' ) . '</time>';
									endif;
									if ( ! is_category() && $dp_options['show_category'] && has_category() ) :
										$categories = get_the_category();
										if ( $categories && ! is_wp_error( $categories ) ) :
											echo '<span class="p-article__category" data-url="' . get_category_link( $categories[0] ) . '">' . esc_html( $categories[0]->name ) . '</span>';
										endif;
									endif;
									echo "</p>\n";
								endif;

							endif; //end if ( $usces_is_item && $sku_price_by_member ) ?>

						</a>
						<div class="p-item-archive__item__a">

							<?php //ここから カートに追加機能を表示
							//global $usces;
							//$sku = ( is_array($post->_isku_) ) ? $post->_isku_['code'] : '';
							$sku = $usces->itemsku['code'];
							$options = false;
							$force = false;
							//if( empty($value) )
							$value = 'カートに追加';//__('Add To Cart', 'usces');
							//$skus = $usces->get_skus( $post->ID, 'code' );
							$zaikonum = $usces->itemsku['stocknum'];
							$zaiko = $usces->itemsku['stock'];
							$gptekiyo = $usces->itemsku['gp'];
							$skuPrice = $usces->itemsku['price'];
							$enc_sku = urlencode( $sku );
							$_advance = $usces->itemsku['advance'];
							//$usces->itemopts = usces_get_opts($post->ID, 'sort');
							//$usces->current_itemopt = -1;
							//$usces->itemsku = $skus[$sku];

							$html = "<form action=\"" . USCES_CART_URL . "\" method=\"post\" name=\"" . $post->ID."-". $enc_sku . "\" class=\"pt20\">\n";

							if ( usces_have_zaiko() ) {

								$html .= '<div class="u-center">';
								$html .= '<div class="mb5 pt10 pb5 p-header__bar">'. $zaikonum. usces_the_itemSkuUnit( 'return' ). ' まで注文可</div>'; //在庫数を表示

								$html .= str_replace( '>', ' style="border: 1px solid #ddd; line-height: 29px; padding: 2px 4px 2px 8px; width: 60px;">', usces_the_itemQuant('return') );
								$html .= usces_the_itemSkuUnit( 'return' );
								$html .= "</div><!-- .u-center -->\n";

								if ( usces_is_login() ) {

									$html .= "<input name=\"zaikonum[{$post->ID}][{$enc_sku}]\" type=\"hidden\" id=\"zaikonum[{$post->ID}][{$enc_sku}]\" value=\"{$zaikonum}\" />\n";
									$html .= "<input name=\"zaiko[{$post->ID}][{$enc_sku}]\" type=\"hidden\" id=\"zaiko[{$post->ID}][{$enc_sku}]\" value=\"{$zaiko}\" />\n";
									$html .= "<input name=\"gptekiyo[{$post->ID}][{$enc_sku}]\" type=\"hidden\" id=\"gptekiyo[{$post->ID}][{$enc_sku}]\" value=\"{$gptekiyo}\" />\n";
									$html .= "<input name=\"skuPrice[{$post->ID}][{$enc_sku}]\" type=\"hidden\" id=\"skuPrice[{$post->ID}][{$enc_sku}]\" value=\"{$skuPrice}\" />\n";

									//(削除予定)advanceフィールドの値をhiddenで送信
									//$html .= "<input name=\"advance[{$post->ID}][{$enc_sku}]\" type=\"hidden\" id=\"advance[{$post->ID}][{$enc_sku}]\" value=\"{$_advance}\" />\n";

									if ( true === $options && usces_is_options() ) {
										while ( usces_have_options() ) {
											$html .= '<div class="itemopt_row">' . usces_the_itemOption( usces_getItemOptName(), '#default#', 'return' ) . "</div>\n";
										}
									}

									//$html .= "<a name=\"cart_button\" style=\"padding: 5px 0 0;\"></a>";
									$html .= '<p class="p-entry-item__cart-button">';
									$html .= "<input name=\"inCart[{$post->ID}][{$enc_sku}]\" type=\"submit\" id=\"inCart[{$post->ID}][{$enc_sku}]\" class=\"skubutton p-button u-center mb20\" value=\"{$value}\" " . /*apply_filters('usces_filter_direct_intocart_button', NULL, $post->ID, $sku, $force, $options) */"onclick=\"return uscesCart.intoCart( '{$post->ID}', '{$enc_sku}' )\"". " style=\"min-width: auto; display: block;\"/>"; /*onclickにitem-category.php用にカスタマイズしているuscesCart.intoCartを直接設定*/
									$html .= '</p>';

									$html .= "<input name=\"usces_referer\" type=\"hidden\" value=\"" . esc_url( $_SERVER['REQUEST_URI'] ) . "\" />\n";
									if ( $force )
										$html .= "<input name=\"usces_force\" type=\"hidden\" value=\"incart\" />\n";
									//$html = apply_filters('usces_filter_single_item_inform', $html);

									$html .= "</form>";
									$html .= '<div class="direct_error_message">' . usces_singleitem_error_message( $post->ID, $sku, 'return' ) . '</div>'."\n";

									echo $html;
									//ここまで カートに追加機能を表示

								} else { //未ログインなら

									usces_loginout();

								} //end if ( usces_is_login() )

							} else { //在庫が0なければ

								echo '<p class="p-entry-item__cart-soldout mb20">'. __( 'SOLD OUT', 'tcd-w' ). '</p>';

							} //end if ( usces_have_zaiko() ) ?>

						</div>
					</div><!-- .p-item-archive__item-info -->

				</article><!-- .p-item-archive__item -->

			<?php
			endwhile; ?>

		</div><!-- .p-item-archive -->

		<?php
		$paginate_current = max( 1, get_query_var( 'paged' ) );
		$paginate_links = paginate_links( array(
			'current' => $paginate_current,
			'next_text' => '&#xe910;',
			'prev_text' => '&#xe90f;',
			'total' => $wp_query->max_num_pages,
			'type' => 'array',
		) );
		if ( $paginate_links ) : ?>

			<ul class="p-pager">
				<li class="p-pager__item p-pager__num u-hidden-xs"><span><?php echo max( 1, get_query_var( 'paged' ) ) . ' / ' . $wp_query->max_num_pages; ?></span></li>

				<?php
				if ( 1 < $paginate_current ): ?>
					<li class="p-pager__item"><a class="first page-numbers" href="<?php echo esc_attr( get_pagenum_link( 1 ) ); ?>">First</a></li>
				<?php
				endif;

				foreach ( $paginate_links as $paginate_link ) : ?>
					<li class="p-pager__item<?php if ( strpos( $paginate_link, 'current' ) ) echo ' p-pager__item--current'; ?>"><?php echo $paginate_link; ?></li>
				<?php
				endforeach;

				if ( $paginate_current < $wp_query->max_num_pages ): ?>
					<li class="p-pager__item"><a class="last page-numbers" href="<?php echo esc_attr( get_pagenum_link( $wp_query->max_num_pages ) ); ?>">Last</a></li>
				<?php
				endif; ?>
			</ul>

		<?php
		endif;

	else : //if ( have_posts() ) ?>

		<p class="no_post"><?php _e( 'There is no registered post.', 'tcd-w' ); ?></p>

	<?php
	endif; //end if ( have_posts() )

	if ( $active_sidebar ) : ?>
			</div>
			<?php get_sidebar(); ?>
		</div>
	<?php
	else : ?>
		</div>
	<?php
	endif; ?>

</main>
<?php get_footer(); ?>
