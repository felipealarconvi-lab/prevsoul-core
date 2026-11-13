<?php
/**
 * Marketing y medición: Google Analytics 4, Microsoft Clarity, Meta Pixel,
 * banner de consentimiento de cookies (Ley 21.719), redes sociales y verificación de dominio.
 *
 * Reglas de privacidad:
 * - Nada se carga antes de que la persona acepte.
 * - Clarity y el Pixel no se cargan en páginas donde alguien escribe información de salud
 *   (precita, consulta online, resultados del buscador) ni en carro/cuenta (Clarity) o cuenta (Pixel).
 * - Nunca se envían textos escritos, datos personales ni nombres de tratamientos a Meta.
 *
 * @package Prevsoul
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ps_mk_defaults() {
	return array(
		'ga4'        => 'G-YZ5HBVL2YP',
		'clarity'    => 'zd380dwgxt',
		'pixel'      => '1816388369408876',
		'gtm'        => '',
		'fbverif'    => 'rd4xv0kl7utj4lwf4iiysilznwfwr6',
		'ig'         => 'https://www.instagram.com/prevsoul.chile/',
		'fb'         => 'https://www.facebook.com/1359973617209357',
		'mail'       => 'contacto@prevsoul.cl',
		'skip_admin' => '1',
	);
}

function ps_mk( $key ) {
	$o = wp_parse_args( get_option( 'ps_marketing', array() ), ps_mk_defaults() );
	return isset( $o[ $key ] ) ? (string) $o[ $key ] : '';
}

/** Redes oficiales para el schema sameAs. */
function ps_mk_same_as() {
	return array_values( array_filter( array( ps_mk( 'ig' ), ps_mk( 'fb' ) ) ) );
}

/** Etiqueta de verificación de dominio de Meta (también se usa en la página «en construcción»). */
function ps_mk_verif_tag() {
	$c = preg_replace( '/[^a-z0-9]/i', '', ps_mk( 'fbverif' ) );
	return $c ? '<meta name="facebook-domain-verification" content="' . esc_attr( $c ) . '" />' . "\n" : '';
}
add_action(
	'wp_head',
	function () {
		echo ps_mk_verif_tag(); // phpcs:ignore
	},
	1
);

/** ¿Página donde la persona escribe información de salud? */
function ps_mk_pagina_sensible() {
	if ( is_page( array( 'consulta-online', 'sala-de-espera', 'panel-dentista', 'panel-prestador' ) ) ) {
		return true;
	}
	if ( is_singular() ) {
		$p = get_post();
		if ( $p && ( has_shortcode( $p->post_content, 'ps_sala_espera' ) || has_shortcode( $p->post_content, 'ps_consulta_online' ) ) ) {
			return true;
		}
	}
	return false;
}

/** Qué herramientas pueden cargarse en esta página. */
function ps_mk_scope() {
	$s = array( 'ga' => true, 'cl' => true, 'px' => true );
	$cuenta = ( function_exists( 'is_account_page' ) && is_account_page() ) || is_page( array( 'panel-dentista', 'panel-prestador' ) );
	if ( $cuenta ) {
		return array( 'ga' => false, 'cl' => false, 'px' => false );
	}
	if ( ps_mk_pagina_sensible() ) {
		$s['cl'] = false;
		$s['px'] = false;
	}
	if ( ( function_exists( 'is_cart' ) && is_cart() ) || ( function_exists( 'is_checkout' ) && is_checkout() ) ) {
		$s['cl'] = false;
	}
	return $s;
}

function ps_mk_activo() {
	if ( is_admin() || wp_doing_ajax() || is_feed() || is_robots() ) {
		return false;
	}
	if ( function_exists( 'ps_construccion_on' ) && ps_construccion_on() && ! ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) ) {
		return false;
	}
	$prueba = isset( $_GET['ps_mk'] ) && 'prueba' === sanitize_key( wp_unslash( $_GET['ps_mk'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
	if ( '1' === ps_mk( 'skip_admin' ) && is_user_logged_in() && current_user_can( 'edit_posts' ) && ! $prueba ) {
		return false;
	}
	return (bool) ( ps_mk( 'ga4' ) || ps_mk( 'clarity' ) || ps_mk( 'pixel' ) || ps_mk( 'gtm' ) );
}

/* ---------- Cola de eventos (WooCommerce) ---------- */
function ps_mk_push( $ev ) {
	if ( function_exists( 'WC' ) && WC()->session ) {
		$q   = (array) WC()->session->get( 'ps_mk_q', array() );
		$q[] = $ev;
		WC()->session->set( 'ps_mk_q', array_slice( $q, -10 ) );
	}
}
add_action(
	'woocommerce_add_to_cart',
	function ( $key, $product_id, $qty, $variation_id = 0 ) {
		$p = wc_get_product( $variation_id ? $variation_id : $product_id );
		if ( ! $p ) {
			return;
		}
		ps_mk_push(
			array(
				'n'     => 'add_to_cart',
				'value' => (float) $p->get_price() * max( 1, (int) $qty ),
				'items' => array( array( 'item_id' => (string) $p->get_id(), 'item_name' => $p->get_name(), 'price' => (float) $p->get_price(), 'quantity' => max( 1, (int) $qty ) ) ),
			)
		);
	},
	10,
	4
);

/** Eventos que corresponden a esta vista. */
function ps_mk_eventos() {
	$ev = array();
	if ( function_exists( 'WC' ) && WC()->session ) {
		$q = (array) WC()->session->get( 'ps_mk_q', array() );
		if ( $q ) {
			$ev = array_merge( $ev, $q );
			WC()->session->set( 'ps_mk_q', array() );
		}
	}
	if ( function_exists( 'is_product' ) && is_product() ) {
		$p = wc_get_product( get_the_ID() );
		if ( $p ) {
			$ev[] = array( 'n' => 'view_item', 'value' => (float) $p->get_price(), 'items' => array( array( 'item_id' => (string) $p->get_id(), 'item_name' => $p->get_name(), 'price' => (float) $p->get_price() ) ) );
		}
	}
	$recibido = function_exists( 'is_order_received_page' ) && is_order_received_page();
	if ( function_exists( 'is_checkout' ) && is_checkout() && ! $recibido && WC()->cart && ! WC()->cart->is_empty() ) {
		$ev[] = array( 'n' => 'begin_checkout', 'value' => (float) WC()->cart->get_total( 'edit' ) );
	}
	if ( $recibido ) {
		global $wp;
		$oid   = isset( $wp->query_vars['order-received'] ) ? absint( $wp->query_vars['order-received'] ) : 0;
		$order = $oid ? wc_get_order( $oid ) : false;
		$key   = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		if ( $order && $key && hash_equals( $order->get_order_key(), $key ) && ! $order->get_meta( '_ps_mk_sent' ) && ! $order->has_status( array( 'failed', 'cancelled' ) ) ) {
			$items = array();
			foreach ( $order->get_items() as $it ) {
				$items[] = array( 'item_id' => (string) $it->get_product_id(), 'item_name' => $it->get_name(), 'price' => (float) $order->get_item_total( $it ), 'quantity' => (int) $it->get_quantity() );
			}
			$ev[] = array( 'n' => 'purchase', 'tid' => (string) $order->get_order_number(), 'value' => (float) $order->get_total(), 'items' => $items );
			$order->update_meta_data( '_ps_mk_sent', time() );
			$order->save();
		}
	}
	if ( isset( $_GET['ps_precita'] ) && 'ok' === sanitize_key( wp_unslash( $_GET['ps_precita'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$ev[] = array( 'n' => 'generate_lead' );
	}
	if ( isset( $_GET['ps_q'] ) && '' !== trim( (string) wp_unslash( $_GET['ps_q'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$ev[] = array( 'n' => 'search_used' );
	}
	return $ev;
}

/* ---------- Estadística anónima del consentimiento ---------- */
function ps_mk_contar() {
	$c = isset( $_POST['c'] ) ? sanitize_key( wp_unslash( $_POST['c'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	if ( in_array( $c, array( 'all', 'nec', 'cfg' ), true ) ) {
		$st = get_option( 'ps_mk_stats', array() );
		$d  = wp_date( 'Y-m-d' );
		$st[ $d ][ $c ] = ( $st[ $d ][ $c ] ?? 0 ) + 1;
		krsort( $st );
		update_option( 'ps_mk_stats', array_slice( $st, 0, 90, true ), false );
	}
	wp_die( '', '', array( 'response' => 204 ) );
}
add_action( 'wp_ajax_ps_ck', 'ps_mk_contar' );
add_action( 'wp_ajax_nopriv_ps_ck', 'ps_mk_contar' );

/* ---------- Banner y carga de herramientas ---------- */
add_action( 'wp_footer', 'ps_mk_footer', 50 );
function ps_mk_footer() {
	if ( ! ps_mk_activo() ) {
		return;
	}
	$priv = get_privacy_policy_url() ? get_privacy_policy_url() : home_url( '/politica-de-privacidad/' );
	$cfg  = array(
		'ga'    => preg_replace( '/[^A-Z0-9-]/i', '', ps_mk( 'ga4' ) ),
		'cl'    => preg_replace( '/[^a-z0-9]/i', '', ps_mk( 'clarity' ) ),
		'px'    => preg_replace( '/[^0-9]/', '', ps_mk( 'pixel' ) ),
		'gtm'   => preg_replace( '/[^A-Z0-9-]/i', '', ps_mk( 'gtm' ) ),
		'scope' => ps_mk_scope(),
		'ev'    => ps_mk_eventos(),
		'ajax'  => admin_url( 'admin-ajax.php' ),
		'https' => is_ssl(),
		'blog'  => is_singular( 'post' ),
	);
	?>
<style id="ps-ck-css">
.ps-ck{position:fixed;left:16px;right:16px;bottom:16px;z-index:9990;max-width:1080px;margin:0 auto;background:#fff;border:1px solid #D7E1EE;border-radius:16px;box-shadow:0 14px 40px rgba(11,37,69,.18);padding:16px 20px;display:grid;grid-template-columns:auto 1fr auto;gap:16px;align-items:center;font-family:"Plus Jakarta Sans",-apple-system,system-ui,sans-serif;color:#0B2545}
.ps-ck[hidden],.ps-ckm[hidden]{display:none !important}
.ps-ck-ico{width:44px;height:44px;border-radius:12px;background:#EAF1FC;display:flex;align-items:center;justify-content:center;font-size:22px}
.ps-ck h2{margin:0 0 4px !important;font-size:16px !important;line-height:1.3;color:#0B2545}
.ps-ck p{margin:0 !important;font-size:13.5px !important;line-height:1.45;color:#4A5B73}
.ps-ck p a{color:#1D5FD1 !important;font-weight:700}
.ps-ck-b{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}
.ps-ck button[type],.ps-ckm button[type]{font:inherit !important;font-weight:800 !important;font-size:14px !important;border-radius:10px !important;padding:10px 16px !important;cursor:pointer;min-height:44px;line-height:1.2 !important;text-transform:none !important;letter-spacing:0 !important;box-shadow:none !important}
.ps-ck .ps-ck-1,.ps-ckm .ps-ck-1{background:#1D5FD1 !important;color:#fff !important;border:2px solid #1D5FD1 !important}
.ps-ck .ps-ck-2,.ps-ckm .ps-ck-2{background:#fff !important;color:#1D5FD1 !important;border:2px solid #1D5FD1 !important}
.ps-ck .ps-ck-3{background:none !important;border:0 !important;color:#4A5B73 !important;text-decoration:underline !important;padding:10px 6px !important}
.ps-ckm{position:fixed;inset:0;z-index:9991;background:rgba(11,37,69,.45);display:flex;align-items:center;justify-content:center;padding:16px;font-family:"Plus Jakarta Sans",-apple-system,system-ui,sans-serif;color:#0B2545}
.ps-ckm-in{background:#fff;border-radius:18px;max-width:520px;width:100%;padding:22px 22px 18px;max-height:100%;overflow:auto}
.ps-ckm-in h2{margin:0 0 6px !important;font-size:18px !important;color:#0B2545}
.ps-ckm-in>p{font-size:13.5px;color:#4A5B73;margin:0 0 12px}
.ps-ck-cat{border:1px solid #D7E1EE;border-radius:12px;padding:12px 14px;margin-bottom:10px}
.ps-ck-cat-h{display:flex;justify-content:space-between;align-items:center;gap:10px}
.ps-ck-cat-h b{font-size:14.5px}
.ps-ck-cat p{margin:6px 0 0;font-size:12.5px;color:#4A5B73}
.ps-ck-cat small{display:block;margin-top:6px;font-size:11.5px;color:#6B7A90}
.ps-ck-fix{font-size:11.5px;font-weight:800;background:#EEF1F5;color:#3D4756;border-radius:99px;padding:2px 9px}
.ps-ck-sw{position:relative;width:40px;height:24px;flex:none;display:inline-block}
.ps-ck-sw input{opacity:0;width:0;height:0;position:absolute}
.ps-ck-sw span{position:absolute;inset:0;background:#c3c4c7;border-radius:99px;transition:.15s;cursor:pointer}
.ps-ck-sw span::after{content:"";position:absolute;width:18px;height:18px;left:3px;top:3px;background:#fff;border-radius:50%;transition:.15s}
.ps-ck-sw input:checked+span{background:#1D5FD1}
.ps-ck-sw input:checked+span::after{transform:translateX(16px)}
.ps-ck-sw input:focus-visible+span{outline:2px solid #0B2545;outline-offset:2px}
.ps-ckm-b{display:flex;gap:8px;justify-content:flex-end;margin-top:12px;flex-wrap:wrap}
@media (max-width:860px){.ps-ck{grid-template-columns:1fr;bottom:84px;left:10px;right:10px;padding:14px 16px}.ps-ck-ico{display:none}.ps-ck-b{justify-content:flex-start}}
</style>
<div class="ps-ck" id="ps-ck" role="dialog" aria-live="polite" aria-labelledby="ps-ck-t" hidden>
	<div class="ps-ck-ico" aria-hidden="true">🦷</div>
	<div>
		<h2 id="ps-ck-t">Cuidamos tus datos como tus dientes</h2>
		<p>Usamos cookies necesarias para que el sitio funcione y, si aceptas, otras para entender qué páginas sirven y mejorar nuestros anuncios. <b>Nunca registramos lo que escribes sobre tu salud.</b> <a href="<?php echo esc_url( $priv ); ?>">Política de privacidad</a></p>
	</div>
	<div class="ps-ck-b">
		<button type="button" class="ps-ck-3" data-ck="cfg">Configurar</button>
		<button type="button" class="ps-ck-2" data-ck="nec">Solo necesarias</button>
		<button type="button" class="ps-ck-1" data-ck="all">Aceptar todas</button>
	</div>
</div>
<div class="ps-ckm" id="ps-ckm" role="dialog" aria-modal="true" aria-labelledby="ps-ckm-t" hidden>
	<div class="ps-ckm-in">
		<h2 id="ps-ckm-t">Preferencias de cookies</h2>
		<p>Elige qué permites. Puedes cambiarlo cuando quieras desde «Preferencias de cookies» al pie de la página.</p>
		<div class="ps-ck-cat"><div class="ps-ck-cat-h"><b>Necesarias</b><span class="ps-ck-fix">Siempre activas</span></div><p>Carro de compra, inicio de sesión, seguridad y recordar esta elección.</p></div>
		<div class="ps-ck-cat"><div class="ps-ck-cat-h"><b><label for="ps-ck-med">Medición</label></b><label class="ps-ck-sw"><input type="checkbox" id="ps-ck-med"><span></span></label></div><p>Cuántas personas visitan cada página y dónde se confunden, para mejorar el sitio.</p><small>Google Analytics · Microsoft Clarity (con todo el texto oculto)</small></div>
		<div class="ps-ck-cat"><div class="ps-ck-cat-h"><b><label for="ps-ck-mkt">Marketing</label></b><label class="ps-ck-sw"><input type="checkbox" id="ps-ck-mkt"><span></span></label></div><p>Saber si un anuncio en Facebook o Instagram trajo a alguien a Prevsoul, para no mostrar anuncios inútiles.</p><small>Meta Pixel</small></div>
		<div class="ps-ckm-b"><button type="button" class="ps-ck-2" data-ck="save">Guardar mi elección</button><button type="button" class="ps-ck-1" data-ck="all">Aceptar todas</button></div>
	</div>
</div>
<script id="ps-mk-js">
(function(){
var C=<?php echo wp_json_encode( $cfg ); ?>,S=C.scope,done={};
var P=['ps_q','ps_t','ps_precita'];
var sens=P.some(function(k){return new RegExp('[?&]'+k+'=').test(location.search)});
if(sens){S.cl=false;S.px=false;try{var u=new URL(location.href);P.forEach(function(k){u.searchParams.delete(k)});history.replaceState(history.state,'',u.pathname+u.search+u.hash)}catch(e){}}
function clean(h){if(!h)return '';try{var u=new URL(h);if(u.origin!==location.origin&&u.origin!=='null')return u.origin+'/';var k=[];u.searchParams.forEach(function(v,n){if(!/^(utm_|gclid$|fbclid$|gbraid$|wbraid$)/.test(n))k.push(n)});k.forEach(function(n){u.searchParams.delete(n)});return u.origin+u.pathname+u.search}catch(e){return ''}}
function rd(){var m=document.cookie.match(/(?:^|; )ps_ck=v1\.([01])([01])/);return m?{med:m[1]==='1',mkt:m[2]==='1'}:null}
function wr(med,mkt){document.cookie='ps_ck=v1.'+(med?1:0)+(mkt?1:0)+';path=/;max-age=15552000;SameSite=Lax'+(C.https?';Secure':'')}
function add(src){var s=document.createElement('script');s.async=true;s.src=src;document.head.appendChild(s)}
function ga(n,p){if(window.gtag&&done.ga)window.gtag('event',n,p||{})}
function fb(n,p){if(window.fbq&&done.px)window.fbq('track',n,p||{})}
function loadGA(){if(done.ga||!C.ga||!S.ga)return;done.ga=1;window.dataLayer=window.dataLayer||[];window.gtag=function(){dataLayer.push(arguments)};gtag('js',new Date());gtag('config',C.ga,{page_location:clean(location.href),page_referrer:clean(document.referrer),allow_google_signals:false,allow_ad_personalization_signals:false});add('https://www.googletagmanager.com/gtag/js?id='+C.ga)}
function loadGTM(){if(done.gtm||!C.gtm||!S.ga)return;done.gtm=1;window.dataLayer=window.dataLayer||[];dataLayer.push({'gtm.start':Date.now(),event:'gtm.js'});add('https://www.googletagmanager.com/gtm.js?id='+C.gtm)}
function loadCL(){if(done.cl||!C.cl||!S.cl)return;done.cl=1;(function(c,l,a,r,i,t,y){c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y)})(window,document,"clarity","script",C.cl);window.clarity('consent')}
function loadPX(){if(done.px||!C.px||!S.px)return;done.px=1;!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('set','autoConfig',false,C.px);fbq('init',C.px);fbq('track','PageView')}
function events(){(C.ev||[]).forEach(function(e){var v=+e.value||0,it=e.items||[];
if(e.n==='view_item'){ga('view_item',{currency:'CLP',value:v,items:it});fb('ViewContent',{currency:'CLP',value:v})}
else if(e.n==='add_to_cart'){ga('add_to_cart',{currency:'CLP',value:v,items:it});fb('AddToCart',{currency:'CLP',value:v})}
else if(e.n==='begin_checkout'){ga('begin_checkout',{currency:'CLP',value:v});fb('InitiateCheckout',{currency:'CLP',value:v})}
else if(e.n==='purchase'){ga('purchase',{transaction_id:e.tid,currency:'CLP',value:v,items:it});fb('Purchase',{currency:'CLP',value:v})}
else if(e.n==='generate_lead'){ga('generate_lead',{});fb('Lead',{})}
else if(e.n==='search_used'){ga('search_used',{})}});C.ev=[]}
function apply(c){if(!c)return;if(c.med){loadGA();loadGTM();loadCL()}if(c.mkt)loadPX();setTimeout(events,0)}
var bn=document.getElementById('ps-ck'),md=document.getElementById('ps-ckm');
function beacon(k){try{var f=new FormData();f.append('action','ps_ck');f.append('c',k);navigator.sendBeacon?navigator.sendBeacon(C.ajax,f):fetch(C.ajax,{method:'POST',body:f,keepalive:true})}catch(e){}}
function choose(med,mkt,k){wr(med,mkt);bn.hidden=true;md.hidden=true;beacon(k);apply({med:med,mkt:mkt})}
document.addEventListener('click',function(e){var t=e.target.closest('[data-ck],[data-ps-ck-open],a[href="#ps-cookies"]');if(!t)return;
if(t.hasAttribute('data-ps-ck-open')||t.getAttribute('href')==='#ps-cookies'){e.preventDefault();var c=rd()||{};document.getElementById('ps-ck-med').checked=!!c.med;document.getElementById('ps-ck-mkt').checked=!!c.mkt;md.hidden=false;return}
var k=t.getAttribute('data-ck');if(k==='all')choose(true,true,'all');else if(k==='nec')choose(false,false,'nec');else if(k==='cfg'){var c2=rd()||{};document.getElementById('ps-ck-med').checked=!!c2.med;document.getElementById('ps-ck-mkt').checked=!!c2.mkt;md.hidden=false}else if(k==='save')choose(document.getElementById('ps-ck-med').checked,document.getElementById('ps-ck-mkt').checked,'cfg')});
md.addEventListener('click',function(e){if(e.target===md)md.hidden=true});
document.addEventListener('keydown',function(e){if(e.key==='Escape'&&!md.hidden)md.hidden=true});
document.addEventListener('click',function(e){var a=e.target.closest('a[href]');if(!a)return;var h=a.getAttribute('href')||'',m=/^https:\/\/(wa\.me|api\.whatsapp\.com)/.test(h)?'whatsapp':/^mailto:/.test(h)?'email':/^tel:/.test(h)?'phone':'';if(m){ga('contact',{method:m});fb('Contact',{})}},true);
if(C.blog){var sent=0;window.addEventListener('scroll',function(){if(sent)return;var el=document.querySelector('.ps-main')||document.body,r=el.getBoundingClientRect();if(r.bottom<=window.innerHeight*1.1){sent=1;ga('blog_read',{})}},{passive:true})}
var c=rd();if(c)apply(c);else bn.hidden=false;
})();
</script>
	<?php
}

/* ---------- Pie de página: redes sociales ---------- */
function ps_mk_social_html() {
	$ico = array(
		'ig'   => array( 'Instagram de Prevsoul', 'M12 7.3A4.7 4.7 0 1 0 16.7 12 4.7 4.7 0 0 0 12 7.3Zm0 7.7a3 3 0 1 1 3-3 3 3 0 0 1-3 3Zm6-7.9a1.1 1.1 0 1 1-1.1-1.1A1.1 1.1 0 0 1 18 7.1ZM21.1 8.2a5.4 5.4 0 0 0-1.5-3.8 5.4 5.4 0 0 0-3.8-1.5C14.3 2.8 9.7 2.8 8.2 2.9a5.4 5.4 0 0 0-3.8 1.5A5.4 5.4 0 0 0 2.9 8.2c-.1 1.5-.1 6.1 0 7.6a5.4 5.4 0 0 0 1.5 3.8 5.4 5.4 0 0 0 3.8 1.5c1.5.1 6.1.1 7.6 0a5.4 5.4 0 0 0 3.8-1.5 5.4 5.4 0 0 0 1.5-3.8c.1-1.5.1-6.1 0-7.6Z' ),
		'fb'   => array( 'Facebook de Prevsoul', 'M14 8.5V6.8c0-.8.2-1.3 1.4-1.3H17V2.6A21 21 0 0 0 14.6 2.5C12.2 2.5 10.6 4 10.6 6.6v1.9H8v3.2h2.6v9.8H14v-9.8h2.6l.4-3.2Z' ),
		'mail' => array( 'Escríbenos un correo', 'M3 5h18v14H3Zm2 2v.5l7 4.5 7-4.5V7Zm14 2.8-7 4.5-7-4.5V17h14Z' ),
	);
	$o = '';
	foreach ( $ico as $k => $d ) {
		$v = ps_mk( $k );
		if ( ! $v ) {
			continue;
		}
		$href = 'mail' === $k ? 'mailto:' . sanitize_email( $v ) : esc_url( $v );
		$ext  = 'mail' === $k ? '' : ' target="_blank" rel="noopener"';
		$o   .= '<a href="' . esc_attr( $href ) . '"' . $ext . ' aria-label="' . esc_attr( $d[0] ) . '"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="' . esc_attr( $d[1] ) . '"/></svg></a>';
	}
	return $o ? '<div class="ps-foot-soc">' . $o . '</div>' : '';
}

/* ---------- Panel: Prevsoul → Marketing ---------- */
add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'prevsoul', 'Marketing y medición', 'Marketing', 'manage_options', 'ps-marketing', 'ps_mk_page' );
	},
	30
);

add_action(
	'admin_post_ps_mk_save',
	function () {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'ps_mk_save' ) ) {
			wp_die( 'No autorizado' );
		}
		$in = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$o  = array(
			'ga4'        => strtoupper( preg_replace( '/[^A-Z0-9-]/i', '', $in['ga4'] ?? '' ) ),
			'clarity'    => strtolower( preg_replace( '/[^a-z0-9]/i', '', $in['clarity'] ?? '' ) ),
			'pixel'      => preg_replace( '/[^0-9]/', '', $in['pixel'] ?? '' ),
			'gtm'        => strtoupper( preg_replace( '/[^A-Z0-9-]/i', '', $in['gtm'] ?? '' ) ),
			'fbverif'    => strtolower( preg_replace( '/[^a-z0-9]/i', '', preg_replace( '/.*content="([^"]+)".*/s', '$1', (string) ( $in['fbverif'] ?? '' ) ) ) ),
			'ig'         => esc_url_raw( $in['ig'] ?? '' ),
			'fb'         => esc_url_raw( $in['fb'] ?? '' ),
			'mail'       => sanitize_email( $in['mail'] ?? '' ),
			'skip_admin' => empty( $in['skip_admin'] ) ? '0' : '1',
		);
		update_option( 'ps_marketing', $o );
		wp_safe_redirect( admin_url( 'admin.php?page=ps-marketing&ok=1' ) );
		exit;
	}
);

function ps_mk_page() {
	$st   = get_option( 'ps_mk_stats', array() );
	$tot  = array( 'all' => 0, 'nec' => 0, 'cfg' => 0 );
	$lim  = wp_date( 'Y-m-d', time() - 30 * DAY_IN_SECONDS );
	foreach ( $st as $d => $r ) {
		if ( $d >= $lim ) {
			foreach ( $tot as $k => $v ) {
				$tot[ $k ] += (int) ( $r[ $k ] ?? 0 );
			}
		}
	}
	$sum  = array_sum( $tot );
	$pct  = function ( $n ) use ( $sum ) {
		return $sum ? round( 100 * $n / $sum ) . '%' : '—';
	};
	$chip = function ( $v, $on = 'Lista', $off = 'Apagado' ) {
		return $v ? '<span class="psmk-chip ok">' . esc_html( $on ) . '</span>' : '<span class="psmk-chip off">' . esc_html( $off ) . '</span>';
	};
	$priv = get_privacy_policy_url();
	$cons = function_exists( 'ps_construccion_on' ) && ps_construccion_on();
	echo function_exists( 'ps_admin_css' ) ? ps_admin_css() : ''; // phpcs:ignore
	?>
<style>
.psmk-g{display:grid;grid-template-columns:minmax(0,1.25fr) minmax(0,1fr);gap:14px}
.psmk-f{display:grid;grid-template-columns:170px 1fr auto;gap:10px;align-items:center;padding:9px 0;border-top:1px solid #EEF1F5}
.psmk-f:first-of-type{border-top:0}.psmk-f label{font-weight:700;font-size:13px}
.psmk-f input[type=text],.psmk-f input[type=url],.psmk-f input[type=email]{width:100%;font-family:ui-monospace,Menlo,monospace;font-size:13px}
.psmk-chip{display:inline-flex;padding:2px 9px;border-radius:99px;font-size:11.5px;font-weight:800;white-space:nowrap}
.psmk-chip.ok{background:#E3F4EA;color:#157347}.psmk-chip.off{background:#EEF1F5;color:#3D4756}.psmk-chip.wait{background:#FFF4DE;color:#8A5300}
.psmk-sc{display:grid;grid-template-columns:1fr 60px 60px 60px;font-size:12.5px;border:1px solid #EEF1F5;border-radius:10px;overflow:hidden}
.psmk-sc>div{padding:7px 9px;border-top:1px solid #EEF1F5}.psmk-sc .h{background:#F6F8FB;font-weight:800;color:#4A5B73;border-top:0;font-size:11.5px}.psmk-sc .c{text-align:center}
.psmk-y{color:#157347;font-weight:800}.psmk-n{color:#B42318;font-weight:800}
.ps-ad-c{margin-bottom:12px}.ps-ad-c code{font-size:11.5px}
@media (max-width:960px){.psmk-g{grid-template-columns:1fr}.psmk-f{grid-template-columns:1fr}}
</style>
<div class="wrap ps-ad">
	<h1>Marketing y medición</h1>
	<p class="ps-ad-note" style="margin:0 0 14px;font-size:13px">Las herramientas solo se cargan si la persona acepta las cookies y nunca en páginas donde alguien escribe información de salud.</p>
	<?php if ( isset( $_GET['ok'] ) ) : // phpcs:ignore ?>
		<div class="notice notice-success is-dismissible"><p>Cambios guardados.</p></div>
	<?php endif; ?>
	<div class="ps-ad-k">
		<div><span>Decisiones · 30 días</span><b><?php echo (int) $sum; ?></b><em class="warn"><?php echo $cons ? 'sitio en construcción' : 'personas que eligieron'; ?></em></div>
		<div><span>Aceptan todo</span><b><?php echo esc_html( $pct( $tot['all'] ) ); ?></b><em><?php echo (int) $tot['all']; ?> visitas</em></div>
		<div><span>Solo necesarias</span><b><?php echo esc_html( $pct( $tot['nec'] ) ); ?></b><em class="warn"><?php echo (int) $tot['nec']; ?> visitas</em></div>
		<div><span>Configuraron</span><b><?php echo esc_html( $pct( $tot['cfg'] ) ); ?></b><em><?php echo (int) $tot['cfg']; ?> visitas</em></div>
	</div>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="ps_mk_save"><?php wp_nonce_field( 'ps_mk_save' ); ?>
	<div class="psmk-g">
	<div>
		<div class="ps-ad-c"><h2>Herramientas</h2><p class="ps-ad-note" style="margin:0 0 8px">Pega el ID; si un campo queda vacío, esa herramienta queda apagada.</p>
			<div class="psmk-f"><label for="ga4">Google Analytics 4</label><input type="text" id="ga4" name="ga4" value="<?php echo esc_attr( ps_mk( 'ga4' ) ); ?>" placeholder="G-XXXXXXXXXX"><?php echo $chip( ps_mk( 'ga4' ) ); // phpcs:ignore ?></div>
			<div class="psmk-f"><label for="clarity">Microsoft Clarity</label><input type="text" id="clarity" name="clarity" value="<?php echo esc_attr( ps_mk( 'clarity' ) ); ?>"><?php echo $chip( ps_mk( 'clarity' ) ); // phpcs:ignore ?></div>
			<div class="psmk-f"><label for="pixel">Meta Pixel</label><input type="text" id="pixel" name="pixel" value="<?php echo esc_attr( ps_mk( 'pixel' ) ); ?>"><?php echo $chip( ps_mk( 'pixel' ) ); // phpcs:ignore ?></div>
			<div class="psmk-f"><label for="gtm">Google Tag Manager</label><input type="text" id="gtm" name="gtm" value="<?php echo esc_attr( ps_mk( 'gtm' ) ); ?>" placeholder="Opcional · GTM-XXXXXXX"><?php echo $chip( ps_mk( 'gtm' ) ); // phpcs:ignore ?></div>
			<div class="psmk-f"><label for="fbverif">Verificación de dominio Meta</label><input type="text" id="fbverif" name="fbverif" value="<?php echo esc_attr( ps_mk( 'fbverif' ) ); ?>" placeholder="Código o etiqueta completa"><?php echo ps_mk( 'fbverif' ) ? '<span class="psmk-chip wait">En el sitio</span>' : '<span class="psmk-chip off">Vacío</span>'; // phpcs:ignore ?></div>
		</div>
		<div class="ps-ad-c"><h2>Redes sociales</h2><p class="ps-ad-note" style="margin:0 0 8px">Aparecen como íconos en el pie de página y le indican a Google cuáles son las redes oficiales (schema <code>sameAs</code>).</p>
			<div class="psmk-f"><label for="ig">Instagram</label><input type="url" id="ig" name="ig" value="<?php echo esc_attr( ps_mk( 'ig' ) ); ?>"><?php echo $chip( ps_mk( 'ig' ), 'Visible', 'Oculto' ); // phpcs:ignore ?></div>
			<div class="psmk-f"><label for="fb">Facebook</label><input type="url" id="fb" name="fb" value="<?php echo esc_attr( ps_mk( 'fb' ) ); ?>"><?php echo $chip( ps_mk( 'fb' ), 'Visible', 'Oculto' ); // phpcs:ignore ?></div>
			<div class="psmk-f"><label for="mail">Correo de contacto</label><input type="email" id="mail" name="mail" value="<?php echo esc_attr( ps_mk( 'mail' ) ); ?>"><?php echo $chip( ps_mk( 'mail' ), 'Visible', 'Oculto' ); // phpcs:ignore ?></div>
		</div>
		<div class="ps-ad-c"><h2>Eventos que se registran</h2><p class="ps-ad-note" style="margin:0 0 8px">Solo acciones. Nunca lo que la persona escribe, su nombre, correo, RUT ni datos de salud. A Meta solo llega el monto, sin nombres de planes ni tratamientos.</p>
			<table><tr><th>Acción</th><th>GA4</th><th>Meta</th></tr>
			<tr><td>Ve un plan</td><td><code>view_item</code></td><td><code>ViewContent</code></td></tr>
			<tr><td>Agrega al carro</td><td><code>add_to_cart</code></td><td><code>AddToCart</code></td></tr>
			<tr><td>Inicia el pago</td><td><code>begin_checkout</code></td><td><code>InitiateCheckout</code></td></tr>
			<tr><td>Compra (CLP)</td><td><code>purchase</code></td><td><code>Purchase</code></td></tr>
			<tr><td>Envía una precita</td><td><code>generate_lead</code></td><td>—</td></tr>
			<tr><td>Usa el buscador</td><td><code>search_used</code></td><td>—</td></tr>
			<tr><td>Clic en WhatsApp / correo / teléfono</td><td><code>contact</code></td><td><code>Contact</code></td></tr>
			<tr><td>Lee un artículo hasta el final</td><td><code>blog_read</code></td><td>—</td></tr></table>
		</div>
	</div>
	<div>
		<div class="ps-ad-c"><h2>Dónde se carga cada herramienta</h2>
			<div class="psmk-sc">
				<div class="h">Página</div><div class="h c">GA4</div><div class="h c">Clarity</div><div class="h c">Pixel</div>
				<div>Inicio, planes, blog, odontólogos</div><div class="c psmk-y">✓</div><div class="c psmk-y">✓</div><div class="c psmk-y">✓</div>
				<div>Resultado del buscador</div><div class="c psmk-y">✓*</div><div class="c psmk-n">✕</div><div class="c psmk-n">✕</div>
				<div>Precita y consulta online</div><div class="c psmk-y">✓*</div><div class="c psmk-n">✕</div><div class="c psmk-n">✕</div>
				<div>Carro y pago</div><div class="c psmk-y">✓</div><div class="c psmk-n">✕</div><div class="c psmk-y">✓</div>
				<div>Mi cuenta y paneles</div><div class="c psmk-n">✕</div><div class="c psmk-n">✕</div><div class="c psmk-n">✕</div>
			</div>
			<p class="ps-ad-note">* Solo la visita y el evento. La frase del buscador se borra de la dirección antes de medir.</p>
		</div>
		<div class="ps-ad-c"><h2>Privacidad</h2>
			<div class="psmk-f" style="grid-template-columns:1fr auto"><label>Pedir consentimiento antes de cargar</label><span class="psmk-chip ok">Siempre</span></div>
			<div class="psmk-f" style="grid-template-columns:1fr auto"><label for="skip_admin">No medir a administradores conectados</label><input type="checkbox" id="skip_admin" name="skip_admin" value="1" <?php checked( '1', ps_mk( 'skip_admin' ) ); ?>></div>
			<div class="psmk-f" style="grid-template-columns:1fr auto"><label>Recordar la decisión por</label><span class="psmk-chip off">6 meses</span></div>
			<div class="psmk-f" style="grid-template-columns:1fr auto"><label>Política de privacidad</label><?php echo $priv ? '<a class="psmk-chip ok" href="' . esc_url( $priv ) . '" target="_blank">Publicada</a>' : '<a class="psmk-chip wait" href="' . esc_url( admin_url( 'options-privacy.php' ) ) . '">Por crear</a>'; // phpcs:ignore ?></div>
			<p class="ps-ad-note">El consentimiento no se puede desactivar: la Ley 21.719 exige que la persona elija antes de que se usen cookies que no son necesarias. Para probar el banner estando conectado, abre el sitio con <code>?ps_mk=prueba</code>.</p>
		</div>
		<?php submit_button( 'Guardar cambios', 'primary', 'submit', false ); ?>
	</div>
	</div>
	</form>
</div>
	<?php
}

/* ---------- 2.7.1: una vez que el sitio usa https, corregir enlaces internos guardados en http ---------- */
add_action(
	'admin_init',
	function () {
		if ( get_option( 'ps_https_v1' ) || 'https' !== wp_parse_url( home_url(), PHP_URL_SCHEME ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		global $wpdb;
		$host = wp_parse_url( home_url(), PHP_URL_HOST );
		$from = 'http://' . $host . '/';
		$to   = 'https://' . $host . '/';
		$like = $wpdb->esc_like( $from ) . '%';
		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = REPLACE(meta_value, %s, %s) WHERE meta_key LIKE %s AND meta_value LIKE %s", $from, $to, $wpdb->esc_like( '_ps_' ) . '%url', $like ) ); // phpcs:ignore
		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, %s, %s) WHERE post_status IN ('publish','draft','private','future') AND post_content LIKE %s", $from, $to, '%' . $wpdb->esc_like( $from ) . '%' ) ); // phpcs:ignore
		$s = get_option( 'ps_settings', array() );
		if ( is_array( $s ) ) {
			foreach ( $s as $k => $v ) {
				if ( is_string( $v ) && 0 === strpos( $v, $from ) ) {
					$s[ $k ] = $to . substr( $v, strlen( $from ) );
				}
			}
			update_option( 'ps_settings', $s );
		}
		update_option( 'ps_https_v1', 1 );
		wp_cache_flush();
	}
);
