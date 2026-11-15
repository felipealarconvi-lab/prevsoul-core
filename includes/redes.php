<?php
/**
 * Versión para redes: al editar una atención, pack o plan (producto de WooCommerce),
 * genera imágenes para el feed (1080×1350) y las historias (1080×1920) de Instagram/Facebook
 * y textos listos para copiar, con enlaces medibles (UTM) y revisión de reglas de salud de Meta.
 *
 * Fase 1: descargar y programar en Meta Business Suite. Fase 2 (publicar directo) queda desactivada.
 *
 * @package Prevsoul
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'add_meta_boxes_product',
	function () {
		add_meta_box( 'ps_redes_box', 'Versión para redes · Instagram y Facebook', 'ps_redes_box', 'product', 'normal', 'default' );
	}
);

add_action(
	'admin_enqueue_scripts',
	function ( $hook ) {
		if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) && 'product' === get_post_type() ) {
			wp_enqueue_style( 'ps-redes-font', 'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;700;800&display=swap', array(), null ); // phpcs:ignore
		}
	}
);

/** Datos del producto que alimentan las piezas. */
function ps_redes_data( $post_id ) {
	$p = function_exists( 'wc_get_product' ) ? wc_get_product( $post_id ) : null;
	if ( ! $p ) {
		return null;
	}
	$cats = wp_get_post_terms( $post_id, 'product_cat', array( 'fields' => 'slugs' ) );
	$all  = array_merge( (array) $cats, function_exists( 'ps_parent_slugs' ) ? ps_parent_slugs( $p ) : array() );
	$tipo = 'Atención dental';
	if ( in_array( 'planes-dentales', $all, true ) ) {
		$tipo = 'Plan dental';
	} elseif ( in_array( 'packs-dentales', $all, true ) ) {
		$tipo = 'Pack dental';
	}
	$items = array_values( array_filter( array_map( 'trim', explode( "\n", wp_strip_all_tags( $p->get_description() ) ) ) ) );
	$items = array_map(
		function ( $s ) {
			return ltrim( $s, "-•*✓✔ \t" );
		},
		array_slice( $items, 0, 4 )
	);
	$price = $p->get_price();
	$fmt   = ( '' !== $price && null !== $price ) ? '$' . number_format( (float) $price, 0, ',', '.' ) : 'Cotizar';
	$pid   = function_exists( 'ps_product_prestador' ) ? ps_product_prestador( $post_id ) : 0;
	$who   = $pid ? get_the_title( $pid ) : 'Red de odontólogos Prevsoul';
	$ini   = '';
	foreach ( preg_split( '/\s+/', preg_replace( '/^(Dr\.?|Dra\.?|Clínica)\s+/iu', '', $who ) ) as $w ) {
		if ( $w && mb_strlen( $ini ) < 2 ) {
			$ini .= mb_strtoupper( mb_substr( $w, 0, 1 ) );
		}
	}
	$short = trim( wp_strip_all_tags( $p->get_short_description() ) );
	$ttl   = get_post_meta( $post_id, '_ps_redes_ttl', true );
	if ( ! $ttl ) {
		$ttl = ( $short && mb_strlen( $short ) <= 60 ) ? $short : $p->get_name();
	}
	$feat   = (int) $p->get_image_id();
	$img_id = (int) get_post_meta( $post_id, '_ps_redes_img', true );
	$fy     = get_post_meta( $post_id, '_ps_redes_fy', true );
	$use    = $img_id ? $img_id : $feat;
	$meta   = $use ? wp_get_attachment_metadata( $use ) : array();
	$av     = '';
	if ( $pid ) {
		$logo = (int) get_post_meta( $pid, '_ps_logo', true );
		if ( 'ps_clinica' === get_post_type( $pid ) && $logo ) {
			$av = wp_get_attachment_image_url( $logo, 'thumbnail' );
		} elseif ( has_post_thumbnail( $pid ) ) {
			$av = get_the_post_thumbnail_url( $pid, 'thumbnail' );
		}
	}
	$lay = get_post_meta( $post_id, '_ps_redes_lay', true );
	return array(
		'imgw'  => isset( $meta['width'] ) ? (int) $meta['width'] : 0,
		'av'    => $av ? (string) $av : '',
		'feat'  => $feat ? (string) wp_get_attachment_image_url( $feat, 'full' ) : '',
		'img'   => $img_id ? (string) wp_get_attachment_image_url( $img_id, 'full' ) : '',
		'imgId' => $img_id,
		'fy'    => '' === $fy ? 50 : max( 0, min( 100, (int) $fy ) ),
		'name'  => $p->get_name(),
		'tipo'  => $tipo,
		'price' => $fmt,
		'per'   => 'Plan dental' === $tipo && 'Cotizar' !== $fmt ? '/ mes' : '',
		'inc'   => $items,
		'who'   => $who,
		'ini'   => $ini ? $ini : 'PS',
		'slug'  => $p->get_slug(),
		'url'   => get_permalink( $post_id ),
		'ttl'   => $ttl,
		'tpl'   => get_post_meta( $post_id, '_ps_redes_tpl', true ) ? get_post_meta( $post_id, '_ps_redes_tpl', true ) : 'B',
		'lay'   => $lay ? $lay : ( $feat ? 'fa' : 'c' ),
		'pub'   => 'publish' === get_post_status( $post_id ),
		'biz'   => '1703574367793337',
	);
}

add_action(
	'save_post_product',
	function ( $post_id ) {
		if ( ! isset( $_POST['ps_redes_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ps_redes_nonce'] ) ), 'ps_redes' ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( isset( $_POST['ps_redes_ttl'] ) ) {
			update_post_meta( $post_id, '_ps_redes_ttl', sanitize_text_field( wp_unslash( $_POST['ps_redes_ttl'] ) ) );
		}
		$tpl = isset( $_POST['ps_redes_tpl'] ) ? strtoupper( sanitize_key( wp_unslash( $_POST['ps_redes_tpl'] ) ) ) : '';
		if ( in_array( $tpl, array( 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H' ), true ) ) {
			update_post_meta( $post_id, '_ps_redes_tpl', $tpl );
		}
		$lay = isset( $_POST['ps_redes_lay'] ) ? sanitize_key( wp_unslash( $_POST['ps_redes_lay'] ) ) : '';
		if ( in_array( $lay, array( 'c', 'p', 'd', 'q', 'l', 'm', 'fa', 'ff', 'fm' ), true ) ) {
			update_post_meta( $post_id, '_ps_redes_lay', $lay );
		}
		if ( isset( $_POST['ps_redes_img'] ) ) {
			$img = absint( wp_unslash( $_POST['ps_redes_img'] ) );
			if ( $img && wp_attachment_is_image( $img ) ) {
				update_post_meta( $post_id, '_ps_redes_img', $img );
			} else {
				delete_post_meta( $post_id, '_ps_redes_img' );
			}
		}
		if ( isset( $_POST['ps_redes_fy'] ) ) {
			update_post_meta( $post_id, '_ps_redes_fy', max( 0, min( 100, absint( wp_unslash( $_POST['ps_redes_fy'] ) ) ) ) );
		}
	}
);

function ps_redes_box( $post ) {
	$d = ps_redes_data( $post->ID );
	if ( ! $d ) {
		echo '<p>Guarda el producto para generar su versión para redes.</p>';
		return;
	}
	$tooth = function_exists( 'ps_logo_arte' ) ? ps_logo_arte()['tooth'] : '';
	if ( function_exists( 'wp_enqueue_media' ) ) {
		wp_enqueue_media();
	}
	wp_nonce_field( 'ps_redes', 'ps_redes_nonce' );
	?>
<style>
#ps_redes_box .inside{margin:0;padding:0}
.psr{display:grid;grid-template-columns:240px minmax(0,1fr) 340px;gap:14px;padding:14px;font-family:"Plus Jakarta Sans",-apple-system,system-ui,sans-serif;color:#0B2545;background:#F6F8FB}
.psr-c{background:#fff;border:1px solid #D7E1EE;border-radius:12px;padding:12px 14px;margin-bottom:10px}
.psr-c h4{margin:0 0 8px;font-size:13px;font-weight:800}
.psr-l{display:block;font-size:11.5px;font-weight:700;color:#4A5B73;margin:8px 0 4px}
.psr-seg{display:flex;flex-wrap:wrap;gap:5px}
.psr-seg button{border:1px solid #D7E1EE;background:#fff;border-radius:99px;padding:5px 11px;font:inherit;font-size:12px;font-weight:700;color:#33415C;cursor:pointer}
.psr-seg button.on{background:#0B2545;color:#fff;border-color:#0B2545}
.psr input[type=text],.psr textarea{width:100%;font:inherit;font-size:12.5px}
.psr textarea{height:170px;line-height:1.45}
.psr-data{font-size:12px;color:#4A5B73;line-height:1.55}.psr-data b{color:#0B2545}
.psr-foto{display:flex;gap:8px;align-items:center;margin:2px 0 6px}.psr-foto img,.psr-foto .psr-ph{width:54px;height:54px;border-radius:8px;object-fit:cover;background:#EAF1FC;border:1px solid #D7E1EE;flex:none}
.psr-foto .psr-ph{display:flex;align-items:center;justify-content:center;font-size:10px;color:#4A5B73;text-align:center;line-height:1.2}
.psr-foto div{display:flex;flex-direction:column;gap:4px}.psr-range{width:100%}
.psr-stage{display:flex;gap:14px;justify-content:center;flex-wrap:wrap;background:#fff;border:1px solid #D7E1EE;border-radius:12px;padding:14px}
.psr-stage figure{margin:0;display:flex;flex-direction:column;align-items:center;gap:6px}
.psr-stage canvas{border-radius:6px;box-shadow:0 6px 18px rgba(11,37,69,.15);display:block}
.psr-stage figcaption{font-size:11px;color:#4A5B73;font-weight:700}
.psr-row{display:flex;gap:6px;flex-wrap:wrap;align-items:center;margin-top:6px}
.psr-n{font-size:11px;color:#4A5B73;margin-left:auto}
.psr-chk{list-style:none;margin:0;padding:0;font-size:12px}
.psr-chk li{display:flex;gap:7px;padding:4px 0;border-top:1px solid #EEF1F5}.psr-chk li:first-child{border-top:0}
.psr-ok{color:#157347;font-weight:800}.psr-wa{color:#8A5300;font-weight:800}
.psr-note{font-size:11.5px;color:#4A5B73;background:#F6F8FB;border-radius:8px;padding:8px 10px;margin-top:8px}
.psr-ph2{border-style:dashed}.psr-badge{font-size:10.5px;font-weight:800;background:#FFF4DE;color:#8A5300;border-radius:99px;padding:2px 8px;margin-left:6px}
@media (max-width:1400px){.psr{grid-template-columns:1fr 1fr}.psr-mid{grid-column:1/-1;order:-1}}
@media (max-width:900px){.psr{grid-template-columns:1fr}}
</style>
<div class="psr" id="psr">
	<div>
		<div class="psr-c"><h4>Datos tomados de la web</h4>
			<div class="psr-data"><b><?php echo esc_html( $d['name'] ); ?></b><br><?php echo esc_html( $d['tipo'] . ' · ' . $d['price'] . ( $d['per'] ? ' ' . $d['per'] : '' ) ); ?><br>Incluye: <?php echo (int) count( $d['inc'] ); ?> ítems <span title="Una línea por ítem en la descripción del producto">ⓘ</span><br>Realiza: <?php echo esc_html( $d['who'] ); ?></div>
			<?php if ( ! $d['pub'] ) : ?><p class="psr-note">El producto aún no está publicado: el enlace funcionará cuando lo publiques.</p><?php endif; ?>
		</div>
		<div class="psr-c"><h4>Diseño</h4>
			<span class="psr-l">Formato</span>
			<div class="psr-seg" data-k="lay"><button type="button" data-v="c">Clásico</button><button type="button" data-v="p">Precio grande</button><button type="button" data-v="d">Dividido</button><button type="button" data-v="q">Pregunta</button><button type="button" data-v="l">Lista</button><button type="button" data-v="m">Marca</button><button type="button" data-v="fa">Foto arriba</button><button type="button" data-v="ff">Foto de fondo</button><button type="button" data-v="fm">Foto en marco</button></div>
			<div id="psr-fotobox">
			<span class="psr-l">Foto</span>
			<div class="psr-foto"><span id="psr-thumb"></span><div><button type="button" class="button" id="psr-pick">Elegir foto</button><button type="button" class="button-link" id="psr-unpick">Usar la foto del producto</button></div></div>
			<input type="hidden" name="ps_redes_img" id="psr-img" value="<?php echo (int) $d['imgId']; ?>">
			<p class="psr-note">Foto ideal: 1600 × 1200 px (4:3), con lo importante al centro. La foto de quien lo realiza (o el logo de la clínica): 400 × 400 px.</p>
			<span class="psr-l"><label for="psr-fy">Encuadre (arriba ↔ abajo)</label></span>
			<input type="range" class="psr-range" id="psr-fy" name="ps_redes_fy" min="0" max="100" value="<?php echo (int) $d['fy']; ?>">
			</div>
			<input type="hidden" name="ps_redes_lay" id="psr-lay" value="<?php echo esc_attr( $d['lay'] ); ?>">
			<span class="psr-l">Colores</span>
			<div class="psr-seg" data-k="tpl"><button type="button" data-v="B">Bruma</button><button type="button" data-v="A">Azul noche</button><button type="button" data-v="C">Menta</button><button type="button" data-v="D">Azul Prevsoul</button><button type="button" data-v="E">Azul profundo</button><button type="button" data-v="F">Noche cielo</button><button type="button" data-v="G">Menta viva</button><button type="button" data-v="H">Blanco</button></div>
			<input type="hidden" name="ps_redes_tpl" id="psr-tpl" value="<?php echo esc_attr( $d['tpl'] ); ?>">
			<span class="psr-l">Objetivo del texto</span>
			<div class="psr-seg" data-k="goal"><button type="button" data-v="v" class="on">Vender</button><button type="button" data-v="e">Educar</button><button type="button" data-v="r">Recordar</button></div>
			<span class="psr-l"><label for="psr-ttl">Frase principal</label></span>
			<input type="text" id="psr-ttl" name="ps_redes_ttl" maxlength="70" value="<?php echo esc_attr( $d['ttl'] ); ?>">
			<p class="psr-note">La frase, el formato, los colores y la foto se guardan al actualizar el producto.</p>
		</div>
		<div class="psr-c"><h4>Revisión automática</h4><ul class="psr-chk" id="psr-chk"></ul>
			<p class="psr-note">Reglas de salud en Meta: sin promesas de resultados, sin «antes y después», sin aludir a condiciones personales.</p></div>
	</div>
	<div class="psr-mid">
		<div class="psr-stage">
			<figure><canvas id="psr-feed" width="1080" height="1350" style="width:300px;height:375px"></canvas><figcaption>Feed · 1080 × 1350</figcaption></figure>
			<figure><canvas id="psr-story" width="1080" height="1920" style="width:211px;height:375px"></canvas><figcaption>Historia · 1080 × 1920</figcaption></figure>
		</div>
		<div class="psr-c" style="margin-top:10px"><h4>Fase 1 · Descargar y programar</h4>
			<div class="psr-row"><button type="button" class="button button-primary" id="psr-dl">Descargar imágenes</button><a class="button" target="_blank" rel="noopener" href="<?php echo esc_url( 'https://business.facebook.com/latest/content_calendar?business_id=' . $d['biz'] ); ?>">Abrir Planificador de Meta ↗</a></div>
			<p class="psr-note">Cada texto lleva su enlace con seguimiento (utm_source=instagram / facebook): en Analytics verás cuántas visitas y ventas trajo cada red.</p>
		</div>
		<div class="psr-c psr-ph2"><h4>Fase 2 · Publicar desde WordPress <span class="psr-badge">Requiere app de Meta</span></h4>
			<div class="psr-row"><button type="button" class="button" disabled>Publicar ahora en Instagram y Facebook</button><button type="button" class="button" disabled>Programar…</button></div>
		</div>
	</div>
	<div>
		<div class="psr-c"><h4>📸 Instagram</h4><textarea id="psr-ig" aria-label="Texto para Instagram"></textarea><div class="psr-row"><button type="button" class="button" data-copy="psr-ig">Copiar texto</button><span class="psr-n" id="psr-nig"></span></div></div>
		<div class="psr-c"><h4>👍 Facebook</h4><textarea id="psr-fb" aria-label="Texto para Facebook"></textarea><div class="psr-row"><button type="button" class="button" data-copy="psr-fb">Copiar texto</button><span class="psr-n" id="psr-nfb"></span></div></div>
		<div class="psr-c"><h4>Historia · texto corto y enlace</h4><input type="text" id="psr-st" aria-label="Texto para historia"><input type="text" id="psr-stu" readonly style="margin-top:6px;color:#4A5B73" aria-label="Enlace para sticker"><div class="psr-row"><button type="button" class="button" data-copy="psr-stu">Copiar enlace</button></div></div>
	</div>
</div>
<script>
(function(){
var D=<?php echo wp_json_encode( $d ); ?>,TP=<?php echo wp_json_encode( $tooth ); ?>,st={tpl:D.tpl||'B',lay:D.lay||'c',goal:'v'};
var $=function(i){return document.getElementById(i)};
var IMG=null,IMGU='',AV=null;if(D.av){var av=new Image();av.onload=function(){AV=av;render()};av.src=D.av}function setImg(u){IMGU=u||'';var th=$('psr-thumb');th.innerHTML=IMGU?'<img alt="" src="'+IMGU+'">':'<span class="psr-ph">Sin foto</span>';$('psr-unpick').style.display=(+$('psr-img').value&&D.feat)?'':'none';if(!IMGU){IMG=null;render();return}var im=new Image();im.onload=function(){IMG=im;render()};im.onerror=function(){IMG=null;render()};im.src=IMGU}
function cover(ctx,x,y,w,h,r){ctx.save();if(r){rr(ctx,x,y,w,h,r);ctx.clip()}else{ctx.beginPath();ctx.rect(x,y,w,h);ctx.clip()}if(IMG){var s=Math.max(w/IMG.width,h/IMG.height),iw=IMG.width*s,ih=IMG.height*s,fy=(+$('psr-fy').value)/100;ctx.drawImage(IMG,x+(w-iw)/2,y+(h-ih)*fy,iw,ih)}else{ctx.fillStyle='#D7E1EE';ctx.fillRect(x,y,w,h);tooth(ctx,x+w/2-110,y+h/2-150,9,'#4A5B73',null,8);ctx.fillStyle='#4A5B73';ctx.font=F(700,36);var m='Elige una foto',mw2=ctx.measureText(m).width;ctx.fillText(m,x+(w-mw2)/2,y+h/2+90)}ctx.restore()}
function badge(ctx,X,y){ctx.fillStyle='#FFFFFF';rr(ctx,X-22,y-22,350,96,48);ctx.fill();tooth(ctx,X,y-4,3.4,'#0B2545','#FFFFFF',5);ctx.fillStyle='#0B2545';ctx.font=F(800,50);ctx.fillText('prevsoul',X+76,y+6)}
var T={A:{bg:'#0B2545',fg:'#FFFFFF',key:'#5FD3B8',kick:'#7CC4F2',mut:'rgba(255,255,255,.72)',card:'#FFFFFF',cardFg:'#0B2545',chk:'#167A64',pill:'#5FD3B8',pillFg:'#0B2545',wm:'rgba(255,255,255,.07)',chip:'rgba(255,255,255,.14)',tooth:'#FFFFFF',fill:null},
       B:{bg:'#EAF1FC',fg:'#0B2545',key:'#1D5FD1',kick:'#167A64',mut:'#4A5B73',card:'#FFFFFF',cardFg:'#0B2545',chk:'#167A64',pill:'#1D5FD1',pillFg:'#FFFFFF',wm:'rgba(29,95,209,.08)',chip:'#D7E1EE',tooth:'#0B2545',fill:'#FFFFFF'},
       C:{bg:'#E6F7F2',fg:'#0B2545',key:'#167A64',kick:'#1D5FD1',mut:'#4A5B73',card:'#FFFFFF',cardFg:'#0B2545',chk:'#167A64',pill:'#0B2545',pillFg:'#5FD3B8',wm:'rgba(22,122,100,.09)',chip:'#CDEFE5',tooth:'#0B2545',fill:'#FFFFFF'},
       D:{bg:'#1D5FD1',fg:'#FFFFFF',key:'#5FD3B8',kick:'#EAF1FC',mut:'rgba(255,255,255,.8)',card:'#FFFFFF',cardFg:'#0B2545',chk:'#167A64',pill:'#0B2545',pillFg:'#5FD3B8',wm:'rgba(255,255,255,.09)',chip:'rgba(255,255,255,.18)',tooth:'#FFFFFF',fill:null,wp:'#1D5FD1',wpf:'#FFFFFF'},
       E:{bg:'#164BA8',fg:'#FFFFFF',key:'#7CC4F2',kick:'#5FD3B8',mut:'rgba(255,255,255,.78)',card:'#FFFFFF',cardFg:'#0B2545',chk:'#167A64',pill:'#FFFFFF',pillFg:'#164BA8',wm:'rgba(255,255,255,.08)',chip:'rgba(255,255,255,.16)',tooth:'#FFFFFF',fill:null,wp:'#164BA8',wpf:'#FFFFFF'},
       F:{bg:'#0B2545',fg:'#FFFFFF',key:'#7CC4F2',kick:'#5FD3B8',mut:'rgba(255,255,255,.72)',card:'#FFFFFF',cardFg:'#0B2545',chk:'#167A64',pill:'#7CC4F2',pillFg:'#0B2545',wm:'rgba(124,196,242,.09)',chip:'rgba(255,255,255,.14)',tooth:'#FFFFFF',fill:null,wp:'#0B2545',wpf:'#7CC4F2'},
       G:{bg:'#5FD3B8',fg:'#0B2545',key:'#164BA8',kick:'#0B2545',mut:'rgba(11,37,69,.78)',card:'#FFFFFF',cardFg:'#0B2545',chk:'#167A64',pill:'#0B2545',pillFg:'#FFFFFF',wm:'rgba(11,37,69,.07)',chip:'rgba(255,255,255,.45)',tooth:'#0B2545',fill:'#FFFFFF',wp:'#0B2545',wpf:'#5FD3B8'},
       H:{bg:'#FFFFFF',fg:'#0B2545',key:'#167A64',kick:'#1D5FD1',mut:'#4A5B73',card:'#EAF1FC',cardFg:'#0B2545',chk:'#167A64',pill:'#0B2545',pillFg:'#5FD3B8',wm:'rgba(11,37,69,.05)',chip:'#EAF1FC',tooth:'#0B2545',fill:'#FFFFFF',wp:'#0B2545',wpf:'#5FD3B8'}};
function utm(src){var u=new URL(D.url);u.searchParams.set('utm_source',src);u.searchParams.set('utm_medium','social');u.searchParams.set('utm_campaign',D.slug);return u.toString()}
function wrap(ctx,text,maxW){var w=String(text).split(/\s+/),lines=[],l='';w.forEach(function(x){var t=l?l+' '+x:x;if(ctx.measureText(t).width>maxW&&l){lines.push(l);l=x}else l=t});if(l)lines.push(l);return lines}
function rr(ctx,x,y,w,h,r){ctx.beginPath();ctx.moveTo(x+r,y);ctx.arcTo(x+w,y,x+w,y+h,r);ctx.arcTo(x+w,y+h,x,y+h,r);ctx.arcTo(x,y+h,x,y,r);ctx.arcTo(x,y,x+w,y,r);ctx.closePath()}
function tooth(ctx,x,y,s,stroke,fill,lw){if(!TP)return;var p=new Path2D(TP);ctx.save();ctx.translate(x,y);ctx.scale(s,s);ctx.translate(-2,-1.4);if(fill){ctx.fillStyle=fill;ctx.fill(p)}ctx.strokeStyle=stroke;ctx.lineWidth=lw/s;ctx.lineCap='round';ctx.lineJoin='round';ctx.stroke(p);ctx.restore()}
function F(w,px){return w+' '+px+'px "Plus Jakarta Sans", -apple-system, "Segoe UI", sans-serif'}
/* Divide la frase: lo que va después de la coma (o las 3 últimas palabras) es la frase clave en color de acento. */
function split(t){var i=t.indexOf(',');if(i>0&&i<t.length-3)return [t.slice(0,i+1),t.slice(i+1).trim()];var w=t.split(/\s+/);if(w.length<4)return ['',t];return [w.slice(0,-3).join(' '),w.slice(-3).join(' ')]}
function words(parts){var w=[];parts[0].split(/\s+/).filter(Boolean).forEach(function(x){w.push([x,0])});parts[1].split(/\s+/).filter(Boolean).forEach(function(x){w.push([x,1])});return w}
function tLines(ctx,parts,sz,mw,max){ctx.font=F(800,sz);var sp=ctx.measureText(' ').width,lines=[],cur=[],cw=0;words(parts).forEach(function(w){var ww=ctx.measureText(w[0]).width;if(cur.length&&cw+sp+ww>mw-20){lines.push(cur);cur=[];cw=0}cur.push(w);cw+=(cur.length>1?sp:0)+ww});if(cur.length)lines.push(cur);return lines.slice(0,max||4)}
function tDraw(ctx,lines,X,y,sz,fg,key,center,W){ctx.font=F(800,sz);var sp=ctx.measureText(' ').width,lh=sz*1.06;lines.forEach(function(l){var lw=0;l.forEach(function(w,i){lw+=ctx.measureText(w[0]).width+(i?sp:0)});var x=center?(W-lw)/2:X;l.forEach(function(w){ctx.fillStyle=w[1]?key:fg;ctx.fillText(w[0],x,y);x+=ctx.measureText(w[0]).width+sp});y+=lh});return y}
function chk(ctx,x,y,col){ctx.save();ctx.strokeStyle=col;ctx.lineWidth=6;ctx.lineCap='round';ctx.lineJoin='round';ctx.beginPath();ctx.moveTo(x,y+13);ctx.lineTo(x+13,y+26);ctx.lineTo(x+36,y);ctx.stroke();ctx.restore()}
function kick(ctx,txt,X,y,col,center,W){ctx.fillStyle=col;ctx.font=F(800,30);var s=txt.toUpperCase().split('').join(String.fromCharCode(8202));ctx.fillText(s,center?(W-ctx.measureText(s).width)/2:X,y)}
function logo(ctx,t,X,y,fg){tooth(ctx,X,y-4,3.4,t.tooth===null?fg:(fg||t.tooth),t.fill,5);ctx.fillStyle=fg||t.fg;ctx.font=F(800,50);ctx.textBaseline='top';ctx.fillText('prevsoul',X+76,y+6)}
function card(ctx,t,X,y,mw,rows){var h=56+rows.length*62;ctx.fillStyle=t.card;rr(ctx,X,y,mw,h,28);ctx.fill();var cy=y+30;ctx.font=F(500,36);rows.forEach(function(r){chk(ctx,X+44,cy+9,t.chk);ctx.fillStyle=t.cardFg;ctx.fillText(r,X+106,cy+2);cy+=62});return y+h}
function pill(ctx,t,X,y,mw,story,pc,pf){var pr=D.price+(D.per?' '+D.per:''),cta=story?'Toca el enlace':'Agenda en prevsoul.cl';ctx.font=F(800,58);var pw=ctx.measureText(pr).width;ctx.font=F(700,34);var cw=ctx.measureText(cta).width,bw=Math.min(mw,pw+cw+150);ctx.fillStyle=pc||t.pill;rr(ctx,X,y,bw,108,54);ctx.fill();ctx.fillStyle=pf||t.pillFg;ctx.font=F(800,58);ctx.fillText(pr,X+44,y+22);ctx.font=F(700,34);ctx.globalAlpha=.9;ctx.fillText('·  '+cta,X+44+pw+28,y+38);ctx.globalAlpha=1;return y+108}
function ctaPill(ctx,t,X,y,story,center,W){var cta=story?'Toca el enlace':'Agenda en prevsoul.cl';ctx.font=F(800,38);var w=ctx.measureText(cta).width+100,x=center?(W-w)/2:X;ctx.fillStyle=t.pill;rr(ctx,x,y,w,96,48);ctx.fill();ctx.fillStyle=t.pillFg;ctx.fillText(cta,x+50,y+28);return y+96}
function who(ctx,t,X,y,mw,mut,fg,chip){ctx.fillStyle=chip||t.chip;ctx.beginPath();ctx.arc(X+33,y+33,33,0,Math.PI*2);ctx.fill();if(AV){ctx.save();ctx.beginPath();ctx.arc(X+33,y+33,33,0,Math.PI*2);ctx.clip();var s=Math.max(66/AV.width,66/AV.height);ctx.drawImage(AV,X+33-AV.width*s/2,y+33-AV.height*s/2,AV.width*s,AV.height*s);ctx.restore()}else{ctx.fillStyle=fg||t.fg;ctx.font=F(800,26);var iw=ctx.measureText(D.ini).width;ctx.fillText(D.ini,X+33-iw/2,y+19)}ctx.fillStyle=mut||t.mut;ctx.font=F(500,30);ctx.fillText(wrap(ctx,'Lo realiza: '+D.who,mw-100)[0],X+84,y+17);return y+66}
function legal(ctx,col,X,H,story){ctx.fillStyle=col;ctx.font=F(500,24);ctx.fillText('Valores referenciales. No reemplaza la evaluación odontológica presencial.',X,H-(story?90:58))}
function site(ctx,col,W,H){ctx.fillStyle=col;ctx.font=F(800,36);var s='prevsoul.cl';ctx.fillText(s,(W-ctx.measureText(s).width)/2,H-170)}
function draw(cv,story){var ctx=cv.getContext('2d'),W=cv.width,H=cv.height,t=T[st.tpl],L=st.lay,X=80,mw=W-160;
 ctx.textBaseline='top';ctx.fillStyle=t.bg;ctx.fillRect(0,0,W,H);
 var ttl=$('psr-ttl').value||D.name,inc=story?D.inc.slice(0,3):D.inc,ctx2;
 ctx.font=F(500,36);var rows=inc.map(function(it){return wrap(ctx,it,mw-150)[0]});
 var bottom=story?H-300:H-110;
 if(L==='p'){ /* Precio grande */
  tooth(ctx,W-430,story?300:150,24,t.wm,null,40);logo(ctx,t,X,story?190:84);
  var pr=D.price,per=D.per,psz=story?230:200,ls=tLines(ctx,split(ttl),64,mw,3);rows=rows.slice(0,3);
  var h=52+ls.length*68+30+psz+(per?50:0)+40+(rows.length?56+rows.length*62+40:0)+96+40+66;
  var y=Math.max(story?400:240,bottom-h);
  kick(ctx,D.tipo,X,y,t.kick);y+=52;y=tDraw(ctx,ls,X,y,64,t.fg,t.fg)+30;
  ctx.fillStyle=t.key;ctx.font=F(800,psz);ctx.fillText(pr,X-8,y);y+=psz;
  if(per){ctx.fillStyle=t.mut;ctx.font=F(600,36);ctx.fillText(per,X,y);y+=50}y+=40;
  if(rows.length)y=card(ctx,t,X,y,mw,rows)+40;
  y=ctaPill(ctx,t,X,y,story)+40;who(ctx,t,X,y,mw);
  if(story)site(ctx,t.fg,W,H);legal(ctx,t.mut,X,H,story);return}
 if(L==='d'){ /* Dividido */
  var top=story?960:600;ctx.fillStyle=st.tpl==='H'?'#EAF1FC':'#FFFFFF';ctx.fillRect(0,top,W,H-top);
  tooth(ctx,W-470,story?230:60,22,t.wm,null,40);logo(ctx,t,X,story?190:84);
  var ls2=tLines(ctx,split(ttl),story?96:80,mw,3),lh=(story?96:80)*1.06,y2=top-70-ls2.length*lh;
  kick(ctx,D.tipo,X,y2-52,t.kick);tDraw(ctx,ls2,X,y2,story?96:80,t.fg,t.key);
  var rr2=rows.slice(0,story?3:4),hb=rr2.length*64+26+108+40+66,ab=(story?H-300:H-110)-top,y=top+Math.max(56,(ab-hb)/2);ctx.font=F(500,36);rr2.forEach(function(r){chk(ctx,X+4,y+9,'#167A64');ctx.fillStyle='#0B2545';ctx.fillText(r,X+66,y+2);y+=64});y+=26;
  var pc=t.wp||(st.tpl==='B'?'#1D5FD1':(st.tpl==='C'?'#167A64':'#0B2545')),pf=t.wpf||(st.tpl==='A'?'#5FD3B8':'#FFFFFF');
  y=pill(ctx,t,X,y,mw,story,pc,pf)+40;who(ctx,t,X,y,mw,'#4A5B73','#0B2545','#EAF1FC');
  if(story)site(ctx,'#0B2545',W,H);legal(ctx,'#4A5B73',X,H,story);return}
 if(L==='fa'||L==='ff'||L==='fm'){ /* Formatos con foto */
  var ts2=story?88:72,lf=tLines(ctx,split(ttl),ts2,mw,3),lh2=ts2*1.06,hb2=52+lf.length*lh2+34+108+36+66,y;
  if(L==='fa'){var ph=story?1020:640;cover(ctx,0,0,W,ph);badge(ctx,X,story?190:84);
   var ab2=(story?H-300:H-110)-ph;y=ph+Math.max(48,(ab2-hb2)/2);}
  else if(L==='ff'){cover(ctx,0,0,W,H);badge(ctx,X,story?190:84);var m=48,ch=hb2+96+40,cy=(story?H-250:H-m)-ch;ctx.fillStyle=t.bg;rr(ctx,m,cy,W-2*m,ch,40);ctx.fill();y=cy+48;
   kick(ctx,D.tipo,X,y,t.kick);y+=52;y=tDraw(ctx,lf,X,y,ts2,t.fg,t.key)+34;y=pill(ctx,t,X,y,mw,story)+36;who(ctx,t,X,y,mw);
   ctx.fillStyle=t.mut;ctx.font=F(500,22);ctx.fillText('Valores referenciales. No reemplaza la evaluación odontológica presencial.',X,cy+ch-48);
   if(story){ctx.fillStyle='#FFFFFF';rr(ctx,(W-300)/2,H-200,300,76,38);ctx.fill();ctx.fillStyle='#0B2545';ctx.font=F(800,34);var s2='prevsoul.cl';ctx.fillText(s2,(W-ctx.measureText(s2).width)/2,H-180)}return}
  else{logo(ctx,t,X,story?190:84);y=story?330:200;
   kick(ctx,D.tipo,X,y,t.kick);y+=52;y=tDraw(ctx,lf,X,y,ts2,t.fg,t.key)+36;var fh2=(story?H-300:H-110)-y-(108+36+66+40);cover(ctx,X,y,mw,fh2,36);y+=fh2+40;
   y=pill(ctx,t,X,y,mw,story)+36;who(ctx,t,X,y,mw);if(story)site(ctx,t.fg,W,H);legal(ctx,t.mut,X,H,story);return}
  kick(ctx,D.tipo,X,y,t.kick);y+=52;y=tDraw(ctx,lf,X,y,ts2,t.fg,t.key)+34;y=pill(ctx,t,X,y,mw,story)+36;who(ctx,t,X,y,mw);
  if(story)site(ctx,t.fg,W,H);legal(ctx,t.mut,X,H,story);return}
 if(L==='q'){ /* Pregunta */
  tooth(ctx,W-430,story?300:150,24,t.wm,null,40);logo(ctx,t,X,story?190:84);
  var q=ttl.indexOf('?')>=0?ttl:'¿Cuándo fue tu último control dental?',qs=story?104:92,lq=tLines(ctx,split(q),qs,mw,4);
  var ans='Un control preventivo revisa a tiempo lo que todavía no se nota.';ctx.font=F(500,40);var la=wrap(ctx,ans,mw-40);
  var h=52+lq.length*qs*1.06+40+la.length*54+50+130+40+66;var y=Math.max(story?420:250,bottom-h);
  kick(ctx,'¿Sabías que?',X,y,t.kick);y+=52;y=tDraw(ctx,lq,X,y,qs,t.fg,t.key)+40;
  ctx.fillStyle=t.mut;ctx.font=F(500,40);la.forEach(function(l){ctx.fillText(l,X,y);y+=54});y+=50;
  ctx.fillStyle=t.card;rr(ctx,X,y,mw,130,28);ctx.fill();ctx.fillStyle=t.cardFg;ctx.font=F(700,34);ctx.fillText(wrap(ctx,D.name,mw-360)[0],X+40,y+24);ctx.fillStyle='#4A5B73';ctx.font=F(500,28);ctx.fillText(story?'Toca el enlace':'Agenda en prevsoul.cl',X+40,y+74);
  var pr2=D.price+(D.per?' '+D.per:'');ctx.font=F(800,50);var pw2=ctx.measureText(pr2).width;ctx.fillStyle=t.wp||t.pill;rr(ctx,X+mw-pw2-80,y+21,pw2+56,88,44);ctx.fill();ctx.fillStyle=t.wpf||t.pillFg;ctx.fillText(pr2,X+mw-pw2-52,y+38);y+=170;
  who(ctx,t,X,y,mw);if(story)site(ctx,t.fg,W,H);legal(ctx,t.mut,X,H,story);return}
 if(L==='l'){ /* Lista */
  logo(ctx,t,X,story?190:84);var it=(story?D.inc.slice(0,4):D.inc.slice(0,4));ctx.font=F(600,40);var lr=it.map(function(x){return wrap(ctx,x,mw-150).slice(0,2)});
  var ls3=tLines(ctx,split(ttl),story?84:70,mw,3),tl=(story?84:70)*1.06,rh=lr.reduce(function(a,r){return a+r.length*50+56},0);
  var h=52+ls3.length*tl+40+rh+40+108+40+66;var y=Math.max(story?400:220,bottom-h);
  kick(ctx,'Qué incluye',X,y,t.kick);y+=52;y=tDraw(ctx,ls3,X,y,story?84:70,t.fg,t.key)+40;
  lr.forEach(function(r,i){ctx.fillStyle=t.chip;ctx.fillRect(X,y,mw,3);y+=28;ctx.fillStyle=t.key;ctx.font=F(800,40);ctx.fillText(('0'+(i+1)).slice(-2),X,y);ctx.fillStyle=t.fg;ctx.font=F(600,40);r.forEach(function(l,j){ctx.fillText(l,X+110,y+j*50)});y+=r.length*50+28});
  y+=40;y=pill(ctx,t,X,y,mw,story)+40;who(ctx,t,X,y,mw);if(story)site(ctx,t.fg,W,H);legal(ctx,t.mut,X,H,story);return}
 if(L==='m'){ /* Marca */
  var ts=story?17:13,tw=24*ts,ty=story?330:150;tooth(ctx,(W-tw*0.9)/2+40,ty,ts,t.key,t.fill,12);
  var ms=story?92:76,lm=tLines(ctx,split(ttl),ms,mw,3);var y=ty+26*ts+(story?120:40);
  y=tDraw(ctx,lm,X,y,ms,t.fg,t.key,true,W)+30;ctx.fillStyle=t.mut;ctx.font=F(600,36);var sub=D.name+' · '+D.price+(D.per?' '+D.per:'');sub=wrap(ctx,sub,mw)[0];ctx.fillText(sub,(W-ctx.measureText(sub).width)/2,y);y+=80;
  ctaPill(ctx,t,X,y,story,true,W);
  ctx.fillStyle=t.fg;ctx.font=F(800,40);var s='prevsoul';tooth(ctx,(W-ctx.measureText(s).width)/2-50,(story?H-260:H-170)-4,2.6,t.tooth,t.fill,4);ctx.fillText(s,(W-ctx.measureText(s).width)/2+16,story?H-260:H-170);
  legal(ctx,t.mut,X,H,story);return}
 /* Clásico */
 tooth(ctx,W-430,story?300:150,24,t.wm,null,40);logo(ctx,t,X,story?190:84);
 var tsz=story?100:86,lh=tsz*1.06,lines=tLines(ctx,split(ttl),tsz,mw,4);
 var cardH=inc.length?(56+rows.length*62):0,blockH=50+lines.length*lh+34+cardH+(cardH?40:0)+118+40+70+24;
 var y=Math.max(story?400:250,bottom-blockH);
 kick(ctx,D.tipo,X,y,t.kick);y+=52;y=tDraw(ctx,lines,X,y,tsz,t.fg,t.key)+30;
 if(cardH)y=card(ctx,t,X,y,mw,rows)+40;
 y=pill(ctx,t,X,y,mw,story)+40;who(ctx,t,X,y,mw);
 if(story)site(ctx,t.fg,W,H);legal(ctx,t.mut,X,H,story);}
function texts(){var g=st.goal,inc=D.inc,ttl=$('psr-ttl').value||D.name,pr=D.price+(D.per?' '+D.per:'');
 var hook={v:ttl+' 🦷',e:'¿Sabías que muchos problemas dentales se pueden detectar antes de que duelan? 🦷',r:'Recordatorio amable: tu control dental también es parte de cuidarte 🦷'}[g];
 var ig=hook+'\n\n'+(g==='e'?'En un control preventivo tu odontólogo revisa a tiempo lo que todavía no se nota. En Prevsoul lo incluimos en:\n\n':'')+'✨ '+D.name+'\n'+inc.map(function(x){return '✔️ '+x}).join('\n')+'\n\n💙 '+pr+'\n📍 Lo realiza: '+D.who+'\n\n👉 Agenda desde el enlace en nuestra bio.\n\nValores referenciales. No reemplaza la evaluación odontológica presencial.\n\n#Prevsoul #OdontologíaPreventiva #DentistaDeCabecera #SaludBucal #Chile';
 var fb=hook+'\n\n'+D.name+' por '+pr+'.'+(inc.length?' Incluye:\n'+inc.map(function(x){return '• '+x}).join('\n'):'')+'\n\nLo realiza: '+D.who+'.\n\nAgenda aquí 👉 '+utm('facebook')+'\n\nValores referenciales. No reemplaza la evaluación odontológica presencial.';
 $('psr-ig').value=ig;$('psr-fb').value=fb;$('psr-st').value=pr+' · '+D.name+' · Toca el enlace 👆';$('psr-stu').value=utm('instagram');
 $('psr-nig').textContent=ig.length+' / 2.200';$('psr-nfb').textContent=fb.length+' caracteres';checks()}
function checks(){var txt=($('psr-ig').value+' '+$('psr-fb').value+' '+$('psr-ttl').value).toLowerCase();
 var bad=/(garantiz|100 ?%|sin dolor|cura|te sangran|tienes caries|tus caries|antes y después|resultados? (asegurad|garantiz))/.test(txt);
 var rows=[[!bad,bad?'Revisa la frase: tiene una promesa o alude a una condición personal':'Sin promesas de resultados ni alusiones a condiciones personales'],[true,'Incluye «No reemplaza la evaluación odontológica presencial»'],[D.who!=='Red de odontólogos Prevsoul','Muestra quién realiza'+(D.who==='Red de odontólogos Prevsoul'?' (asígnalo en «Quién realiza el tratamiento»)':'')],[D.inc.length>0,D.inc.length?'Detalle de lo que incluye':'Agrega lo que incluye: una línea por ítem en la descripción'],[D.pub,D.pub?'Enlace con seguimiento listo':'El producto debe estar publicado para que el enlace funcione']];if(st.lay.charAt(0)==='f'){var big=IMG&&IMG.naturalWidth>=1080;rows.push([!!big,!IMG?'Elige una foto: la imagen del producto u otra de la biblioteca (1600 × 1200 px)':(big?'Foto lista':'La foto es pequeña: súbela de al menos 1600 × 1200 px')]);rows.push([true,'Foto propia o con permiso; sin «antes y después» ni tratamientos en boca'])}
 $('psr-chk').innerHTML=rows.map(function(r){return '<li><span class="'+(r[0]?'psr-ok':'psr-wa')+'">'+(r[0]?'✓':'!')+'</span><span>'+r[1]+'</span></li>'}).join('')}
function render(){$('psr-fotobox').style.display=st.lay.charAt(0)==='f'?'':'none';draw($('psr-feed'),false);draw($('psr-story'),true);texts()}
document.querySelectorAll('#psr .psr-seg').forEach(function(sg){var k=sg.dataset.k;[].forEach.call(sg.children,function(b){b.classList.toggle('on',b.dataset.v===st[k])});sg.addEventListener('click',function(e){var b=e.target.closest('button');if(!b)return;st[k]=b.dataset.v;[].forEach.call(sg.children,function(x){x.classList.toggle('on',x===b)});if(k==='tpl')$('psr-tpl').value=b.dataset.v;if(k==='lay')$('psr-lay').value=b.dataset.v;render()})});
$('psr-ttl').addEventListener('input',render);$('psr-fy').addEventListener('input',render);
$('psr-pick').addEventListener('click',function(){if(!window.wp||!wp.media)return;var fr=wp.media({title:'Elige una foto para redes',library:{type:'image'},multiple:false,button:{text:'Usar esta foto'}});fr.on('select',function(){var a=fr.state().get('selection').first().toJSON();$('psr-img').value=a.id;setImg(a.url)});fr.open()});
$('psr-unpick').addEventListener('click',function(){$('psr-img').value=0;setImg(D.feat)});
document.getElementById('psr').addEventListener('click',function(e){var c=e.target.closest('[data-copy]');if(!c)return;var el=$(c.dataset.copy);el.select();try{navigator.clipboard.writeText(el.value)}catch(x){document.execCommand('copy')}var o=c.textContent;c.textContent='¡Copiado!';setTimeout(function(){c.textContent=o},1500)});
$('psr-dl').addEventListener('click',function(){[['psr-feed','feed'],['psr-story','historia']].forEach(function(p,i){setTimeout(function(){$(p[0]).toBlob(function(b){var a=document.createElement('a');a.href=URL.createObjectURL(b);a.download='prevsoul-'+D.slug+'-'+p[1]+'.png';document.body.appendChild(a);a.click();setTimeout(function(){URL.revokeObjectURL(a.href);a.remove()},500)},'image/png')},i*400)})});
setImg(D.img||D.feat);
if(document.fonts&&document.fonts.load){Promise.all([document.fonts.load(F(800,90)),document.fonts.load(F(500,38))]).then(render,render)}
})();
</script>
	<?php
}
