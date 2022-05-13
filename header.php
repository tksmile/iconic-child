<?php
global $dp_options;
if ( ! $dp_options ) $dp_options = get_design_plus_option();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head <?php if ( $dp_options['use_ogp'] ) { echo 'prefix="og: http://ogp.me/ns# fb: http://ogp.me/ns/fb#"'; } ?>>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="description" content="<?php seo_description(); ?>">
<meta name="viewport" content="width=device-width">
<?php if ( $dp_options['use_ogp'] ) { ogp(); } ?>
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php
if ( $dp_options['use_load_icon'] ) :
?>
<div id="site_loader_overlay">
	<div id="site_loader_animation" class="c-load--<?php echo esc_attr( $dp_options['load_icon'] ); ?>">
<?php
	if ( 'type3' === $dp_options['load_icon'] ) :
?>
		<i></i><i></i><i></i><i></i>
<?php
	endif;
?>
	</div>
</div>
<?php
endif;
?>
<header id="js-header" class="l-header">
<?php
if ( get_bloginfo( 'description' ) || is_welcart_active() ) :
?>
	<div class="p-header__top">
		<div class="p-header__top__inner l-inner">
<?php
	if ( get_bloginfo( 'description' ) ) :
?>
			<div class="p-header-description"><?php echo bloginfo( 'description' ); ?></div>
<?php
	endif;
	if ( is_welcart_active() ) :
?>
			<ul class="p-header__welcart-nav">
<?php
		if ( usces_is_membersystem_state() ) :
			if ( usces_is_login() ) :
?>
				<li><a href="<?php echo esc_attr( USCES_MEMBER_URL ); ?>"><?php echo esc_html( get_welcart_member_page_original_title() ); ?></a></li>
				<li class="p-header__welcart-nav__logout"><a href="<?php echo esc_attr( USCES_LOGOUT_URL ); ?>"><?php _e( 'Logout', 'tcd-w' ); ?></a></li>
				<li class="p-header__welcart-nav__member"><a href="<?php echo esc_attr( USCES_MEMBER_URL ); ?>"><?php
				global $usces;
				$usces->get_current_member(); //現在のメンバーを取得
				$store_name = usces_get_custom_field_value( 'member', 'store', $usces->current_member['id'], 'return' ); //店舗名
				$print_name = ( $store_name ) ? $store_name : $usces->current_member['name']; //表示名は、店舗名があれば店舗名を、なければ氏名を
				printf( __( 'Hello %s', 'tcd-w' ), esc_html( $print_name ) ); ?></a></li>
<?php
			else :
?>
				<li><a href="<?php echo esc_attr( USCES_NEWMEMBER_URL ); ?>"><?php _e( 'Registration', 'tcd-w' ); ?></a></li>
				<li class="p-header__welcart-nav__login"><a href="<?php echo esc_attr( USCES_LOGIN_URL ); ?>"><?php _e( 'Login', 'tcd-w' ); ?></a></li>
<?php
            endif;
        endif;
?>
                <li class="p-header__welcart-nav__cart"><a id="js-header-cart" href="<?php echo esc_attr( USCES_CART_URL ); ?>"><?php _e( 'Cart', 'tcd-w' ); ?><span class="p-header__welcart-nav__badge"><?php echo ( $totalquantity = usces_totalquantity_in_cart() ) ? $totalquantity : ''; ?></span></a></li>
            </ul>
<?php
    endif;
?>
		</div>
	</div>
<?php
endif;
?>
	<div class="l-header__bar p-header__bar">
		<div class="p-header__bar__inner l-inner">
<?php
$logotag = is_front_page() ? 'h1' : 'div';
if ( 'yes' == $dp_options['use_header_logo_image'] && $image = wp_get_attachment_image_src( $dp_options['header_logo_image'], 'full' ) ) :
?>
			<<?php echo $logotag; ?> class="p-logo p-header__logo<?php if ( $dp_options['header_logo_image_retina'] ) { echo ' p-header__logo--retina'; } ?>">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><img src="<?php echo esc_attr( $image[0] ); ?>" alt="<?php bloginfo( 'name' ); ?>"<?php if ( $dp_options['header_logo_image_retina'] ) echo ' width="' . floor( $image[1] / 2 ) . '"'; ?>></a>
			</<?php echo $logotag; ?>>
<?php
else :
?>
			<<?php echo $logotag; ?> class="p-logo p-header__logo p-header__logo--text">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a>
			</<?php echo $logotag; ?>>
<?php
endif;

if ( 'yes' == $dp_options['use_header_logo_image_mobile'] && $image = wp_get_attachment_image_src( $dp_options['header_logo_image_mobile'], 'full' ) ) :
?>
			<div class="p-logo p-header__logo--mobile<?php if ( $dp_options['header_logo_image_mobile_retina'] ) echo ' p-header__logo--retina'; ?>">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><img src="<?php echo esc_attr( $image[0] ); ?>" alt="<?php bloginfo( 'name' ); ?>"<?php if ( $dp_options['header_logo_image_mobile_retina'] ) echo ' width="' . floor( $image[1] / 2 ) . '"'; ?>></a>
			</div>
<?php
else :
?>
			<div class="p-logo p-header__logo--mobile p-header__logo--text">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a>
			</div>
<?php
endif;
if ( is_welcart_active() ) :
?>
			<a href="<?php echo esc_attr( USCES_CART_URL ); ?>" id="js-header-cart--mobile" class="p-cart-button c-cart-button"><span class="p-header__welcart-nav__badge"><?php echo ( $totalquantity = usces_totalquantity_in_cart() ) ? $totalquantity : ''; ?></span></a>
<?php
	endif;
?>
			<a href="#" id="js-menu-button" class="p-menu-button c-menu-button"></a>
<?php
if ( function_exists( 'usces_remove_filter' ) ) :
	usces_remove_filter();
endif;

if ( has_nav_menu( 'global' ) ) :
	$nav = wp_nav_menu( array(
		'container' => 'nav',
		'container_class' => 'p-global-nav__container',
		'menu_class' => 'p-global-nav',
		'menu_id' => 'js-global-nav',
		'theme_location' => 'global',
		'link_after' => '<span></span>',
		'echo' => 0
	) );

	$nav_insert_bottom = '';

	if ( is_welcart_active() ) :
		if ( usces_is_membersystem_state() ) :
			if ( usces_is_login() ) :
				$nav_insert_bottom .= '<li class="p-global-nav__item-welcart--mobile"><a href="' . esc_attr( USCES_MEMBER_URL ) . '">' . esc_html( get_welcart_member_page_original_title() ) . '</a></li>' . "\n";
				$nav_insert_bottom .= '<li class="p-global-nav__item-welcart--mobile"><a href="' . esc_attr( USCES_LOGOUT_URL ) . '">' . __( 'Logout', 'tcd-w' ) . '</a></li>' . "\n";
			else :
				$nav_insert_bottom .= '<li class="p-global-nav__item-welcart--mobile"><a href="' . esc_attr( USCES_NEWMEMBER_URL ) . '">' . __( 'Registration', 'tcd-w' ) . '</a></li>' . "\n";
				$nav_insert_bottom .= '<li class="p-global-nav__item-welcart--mobile"><a href="' . esc_attr( USCES_LOGIN_URL ) . '">' . __( 'Login', 'tcd-w' ) . '</a></li>' . "\n";
			endif;
		endif;
	endif;

	if ( $dp_options['show_header_search_mobile'] ) :
		$nav_insert_bottom .= '<li class="p-header-search--mobile">';
		$nav_insert_bottom .= '<form action="' . esc_url( home_url( '/' ) ) . '" method="get">';
		$nav_insert_bottom .= '<input type="text" name="s" value="' . esc_attr( get_query_var( 's' ) ) . '" class="p-header-search__input" placeholder="SEARCH">';
		$nav_insert_bottom .= '<input type="submit" value="&#xe915;" class="p-header-search__submit">';
		$nav_insert_bottom .= '</form>';
		$nav_insert_bottom .= "</li>\n";
	endif;

	if ( $nav_insert_bottom ) :
		$nav = str_replace( '</ul></nav>', $nav_insert_bottom . '</ul></nav>', $nav );
	endif;

	echo $nav . "\n";
endif;

if ( $dp_options['show_header_search'] ) :
?>
			<div class="p-header-search">
				<form action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get">
					<input type="text" name="s" value="<?php echo esc_attr( get_query_var( 's' ) ); ?>" class="p-header-search__input" placeholder="SEARCH">
				</form>
				<a href="#" id="js-search-button" class="p-search-button c-search-button"></a>
			</div>
<?php
endif;
?>
		</div>
	</div>
<?php
if ( is_front_page() && get_bloginfo( 'description' ) ) :
?>
	<div class="p-header__bottom">
		<div class="p-header__bottom__inner l-inner">
			<div class="p-header-description"><?php bloginfo( 'description' ); ?></div>
		</div>
	</div>
<?php
endif;

get_template_part( 'template-parts/megamenu' );
get_template_part( 'template-parts/header-view-cart' );

if ( function_exists( 'usces_reset_filter' ) ) :
	usces_reset_filter();
endif;

?>
</header>
