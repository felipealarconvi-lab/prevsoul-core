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
	return array(
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
		'tpl'   => get_post_meta( $post_id, '_ps_redes_tpl', true ) ? get_post_meta( $post_id, '_ps_redes_tpl', true ) : 'A',
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
		if ( in_array( $tpl, array( 'A', 'B', 'C' ), true ) ) {
			update_post_meta( $post_id, '_ps_redes_tpl', $tpl );
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
			<span class="psr-l">Plantilla</span>
			<div class="psr-seg" data-k="tpl"><button type="button" data-v="A">Azul confianza</button><button type="button" data-v="B">Claro</button><button type="button" data-v="C">Amarillo diente</button></div>
			<input type="hidden" name="ps_redes_tpl" id="psr-tpl" value="<?php echo esc_attr( $d['tpl'] ); ?>">
			<span class="psr-l">Objetivo del texto</span>
			<div class="psr-seg" data-k="goal"><button type="button" data-v="v" class="on">Vender</button><button type="button" data-v="e">Educar</button><button type="button" data-v="r">Recordar</button></div>
			<span class="psr-l"><label for="psr-ttl">Frase principal</label></span>
			<input type="text" id="psr-ttl" name="ps_redes_ttl" maxlength="70" value="<?php echo esc_attr( $d['ttl'] ); ?>">
			<p class="psr-note">La frase y la plantilla se guardan al actualizar el producto.</p>
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
var D=<?php echo wp_json_encode( $d ); ?>,TP=<?php echo wp_json_encode( $tooth ); ?>,st={tpl:D.tpl||'A',goal:'v'};
var $=function(i){return document.getElementById(i)};
var T={A:{bg:['#0B2545','#123A6B','#1D5FD1'],fg:'#fff',mut:'rgba(255,255,255,.82)',ctaBg:'#F5C518',ctaFg:'#0B2545',wm:'rgba(255,255,255,.10)',chip:'rgba(255,255,255,.18)',tooth:'#fff',fill:null},
       B:{bg:['#EAF1FC','#F7FAFE','#ffffff'],fg:'#0B2545',mut:'#4A5B73',ctaBg:'#1D5FD1',ctaFg:'#fff',wm:'rgba(29,95,209,.09)',chip:'rgba(11,37,69,.08)',tooth:'#0B2545',fill:'#fff'},
       C:{bg:['#F5C518','#F5C518','#F2B90C'],fg:'#0B2545',mut:'rgba(11,37,69,.78)',ctaBg:'#0B2545',ctaFg:'#fff',wm:'rgba(255,255,255,.35)',chip:'rgba(11,37,69,.1)',tooth:'#0B2545',fill:'#fff'}};
function utm(src){var u=new URL(D.url);u.searchParams.set('utm_source',src);u.searchParams.set('utm_medium','social');u.searchParams.set('utm_campaign',D.slug);return u.toString()}
function wrap(ctx,text,maxW){var w=String(text).split(/\s+/),lines=[],l='';w.forEach(function(x){var t=l?l+' '+x:x;if(ctx.measureText(t).width>maxW&&l){lines.push(l);l=x}else l=t});if(l)lines.push(l);return lines}
function rr(ctx,x,y,w,h,r){ctx.beginPath();ctx.moveTo(x+r,y);ctx.arcTo(x+w,y,x+w,y+h,r);ctx.arcTo(x+w,y+h,x,y+h,r);ctx.arcTo(x,y+h,x,y,r);ctx.arcTo(x,y,x+w,y,r);ctx.closePath()}
function tooth(ctx,x,y,s,stroke,fill,lw){if(!TP)return;var p=new Path2D(TP);ctx.save();ctx.translate(x,y);ctx.scale(s,s);ctx.translate(-2,-1.4);if(fill){ctx.fillStyle=fill;ctx.fill(p)}ctx.strokeStyle=stroke;ctx.lineWidth=lw/s;ctx.lineCap='round';ctx.lineJoin='round';ctx.stroke(p);ctx.restore()}
function F(w,px){return w+' '+px+'px "Plus Jakarta Sans", -apple-system, "Segoe UI", sans-serif'}
function draw(cv,story){var ctx=cv.getContext('2d'),W=cv.width,H=cv.height,t=T[st.tpl];
 var g=ctx.createLinearGradient(0,0,W*0.6,H);g.addColorStop(0,t.bg[0]);g.addColorStop(.6,t.bg[1]);g.addColorStop(1,t.bg[2]);ctx.fillStyle=g;ctx.fillRect(0,0,W,H);
 tooth(ctx,W-470,story?330:170,26,t.wm,null,40);
 var X=80,y=story?200:90,mw=W-160;
 tooth(ctx,X,y-4,3.4,t.tooth,t.fill,5);ctx.fillStyle=t.fg;ctx.font=F(800,50);ctx.textBaseline='top';ctx.fillText('prevsoul',X+76,y+6);
 var inc=story?D.inc.slice(0,3):D.inc;ctx.font=F(800,story?108:94);var tl=wrap(ctx,$('psr-ttl').value||D.name,mw-40).slice(0,4);
 var lh=story?112:98,listH=inc.length*58,blockH=46+tl.length*lh+26+listH+30+120+30+90+40+74;
 y=Math.max(story?420:300,(story?H-330:H-120)-blockH);
 ctx.fillStyle=t.mut;ctx.font=F(800,32);ctx.fillText(D.tipo.toUpperCase().split('').join(String.fromCharCode(8202)),X,y);y+=56;
 ctx.fillStyle=t.fg;ctx.font=F(800,story?108:94);tl.forEach(function(l){ctx.fillText(l,X,y);y+=lh});y+=22;
 ctx.font=F(500,38);inc.forEach(function(it){ctx.save();ctx.strokeStyle=t.fg;ctx.lineWidth=6;ctx.lineCap='round';ctx.beginPath();ctx.moveTo(X+2,y+22);ctx.lineTo(X+14,y+34);ctx.lineTo(X+36,y+8);ctx.stroke();ctx.restore();ctx.fillStyle=t.fg;var l=wrap(ctx,it,mw-60)[0];ctx.fillText(l,X+58,y);y+=58});y+=26;
 ctx.fillStyle=t.fg;ctx.font=F(800,112);ctx.fillText(D.price,X,y);var pw=ctx.measureText(D.price).width;if(D.per){ctx.fillStyle=t.mut;ctx.font=F(500,38);ctx.fillText(D.per,X+pw+18,y+60)}y+=140;
 var cta=story?'Toca el enlace':'Agenda en prevsoul.cl';ctx.font=F(800,40);var cw=ctx.measureText(cta).width+76;ctx.fillStyle=t.ctaBg;rr(ctx,X,y,cw,92,24);ctx.fill();ctx.fillStyle=t.ctaFg;ctx.fillText(cta,X+38,y+25);y+=126;
 ctx.fillStyle=t.chip;ctx.beginPath();ctx.arc(X+35,y+35,35,0,Math.PI*2);ctx.fill();ctx.fillStyle=t.fg;ctx.font=F(800,28);var iw=ctx.measureText(D.ini).width;ctx.fillText(D.ini,X+35-iw/2,y+20);
 ctx.fillStyle=t.mut;ctx.font=F(500,30);ctx.fillText(wrap(ctx,'Lo realiza: '+D.who,mw-100)[0],X+88,y+19);
 if(story){ctx.fillStyle=t.fg;ctx.font=F(800,36);var s='prevsoul.cl',sw=ctx.measureText(s).width;ctx.fillText(s,(W-sw)/2,H-170);ctx.font=F(800,56);ctx.fillText('⌃',(W-30)/2,H-230)}
 ctx.fillStyle=t.mut;ctx.font=F(500,24);ctx.fillText('Valores referenciales. No reemplaza la evaluación odontológica presencial.',X,H-(story?90:58));}
function texts(){var g=st.goal,inc=D.inc,ttl=$('psr-ttl').value||D.name,pr=D.price+(D.per?' '+D.per:'');
 var hook={v:ttl+' 🦷',e:'¿Sabías que muchos problemas dentales se pueden detectar antes de que duelan? 🦷',r:'Recordatorio amable: tu control dental también es parte de cuidarte 🦷'}[g];
 var ig=hook+'\n\n'+(g==='e'?'En un control preventivo tu odontólogo revisa a tiempo lo que todavía no se nota. En Prevsoul lo incluimos en:\n\n':'')+'✨ '+D.name+'\n'+inc.map(function(x){return '✔️ '+x}).join('\n')+'\n\n💙 '+pr+'\n📍 Lo realiza: '+D.who+'\n\n👉 Agenda desde el enlace en nuestra bio.\n\nValores referenciales. No reemplaza la evaluación odontológica presencial.\n\n#Prevsoul #OdontologíaPreventiva #DentistaDeCabecera #SaludBucal #Chile';
 var fb=hook+'\n\n'+D.name+' por '+pr+'.'+(inc.length?' Incluye:\n'+inc.map(function(x){return '• '+x}).join('\n'):'')+'\n\nLo realiza: '+D.who+'.\n\nAgenda aquí 👉 '+utm('facebook')+'\n\nValores referenciales. No reemplaza la evaluación odontológica presencial.';
 $('psr-ig').value=ig;$('psr-fb').value=fb;$('psr-st').value=pr+' · '+D.name+' · Toca el enlace 👆';$('psr-stu').value=utm('instagram');
 $('psr-nig').textContent=ig.length+' / 2.200';$('psr-nfb').textContent=fb.length+' caracteres';checks()}
function checks(){var txt=($('psr-ig').value+' '+$('psr-fb').value+' '+$('psr-ttl').value).toLowerCase();
 var bad=/(garantiz|100 ?%|sin dolor|cura|te sangran|tienes caries|tus caries|antes y después|resultados? (asegurad|garantiz))/.test(txt);
 var rows=[[!bad,bad?'Revisa la frase: tiene una promesa o alude a una condición personal':'Sin promesas de resultados ni alusiones a condiciones personales'],[true,'Incluye «No reemplaza la evaluación odontológica presencial»'],[D.who!=='Red de odontólogos Prevsoul','Muestra quién realiza'+(D.who==='Red de odontólogos Prevsoul'?' (asígnalo en «Quién realiza el tratamiento»)':'')],[D.inc.length>0,D.inc.length?'Detalle de lo que incluye':'Agrega lo que incluye: una línea por ítem en la descripción'],[D.pub,D.pub?'Enlace con seguimiento listo':'El producto debe estar publicado para que el enlace funcione']];
 $('psr-chk').innerHTML=rows.map(function(r){return '<li><span class="'+(r[0]?'psr-ok':'psr-wa')+'">'+(r[0]?'✓':'!')+'</span><span>'+r[1]+'</span></li>'}).join('')}
function render(){draw($('psr-feed'),false);draw($('psr-story'),true);texts()}
document.querySelectorAll('#psr .psr-seg').forEach(function(sg){var k=sg.dataset.k;[].forEach.call(sg.children,function(b){b.classList.toggle('on',b.dataset.v===st[k])});sg.addEventListener('click',function(e){var b=e.target.closest('button');if(!b)return;st[k]=b.dataset.v;[].forEach.call(sg.children,function(x){x.classList.toggle('on',x===b)});if(k==='tpl')$('psr-tpl').value=b.dataset.v;render()})});
$('psr-ttl').addEventListener('input',render);
document.getElementById('psr').addEventListener('click',function(e){var c=e.target.closest('[data-copy]');if(!c)return;var el=$(c.dataset.copy);el.select();try{navigator.clipboard.writeText(el.value)}catch(x){document.execCommand('copy')}var o=c.textContent;c.textContent='¡Copiado!';setTimeout(function(){c.textContent=o},1500)});
$('psr-dl').addEventListener('click',function(){[['psr-feed','feed'],['psr-story','historia']].forEach(function(p,i){setTimeout(function(){$(p[0]).toBlob(function(b){var a=document.createElement('a');a.href=URL.createObjectURL(b);a.download='prevsoul-'+D.slug+'-'+p[1]+'.png';document.body.appendChild(a);a.click();setTimeout(function(){URL.revokeObjectURL(a.href);a.remove()},500)},'image/png')},i*400)})});
render();
if(document.fonts&&document.fonts.load){Promise.all([document.fonts.load(F(800,90)),document.fonts.load(F(500,38))]).then(render,render)}
})();
</script>
	<?php
}
