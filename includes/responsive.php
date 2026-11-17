<?php
/**
 * Vista responsive: ver el sitio en celular, tablet y escritorio desde el admin.
 *
 * @package Prevsoul
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'prevsoul', 'Vista responsive', 'Vista responsive', 'manage_options', 'ps-responsive', 'ps_rv_page' );
	},
	21
);

function ps_rv_paginas() {
	$p = array( 'Inicio' => home_url( '/' ) );
	if ( function_exists( 'wc_get_page_permalink' ) ) {
		$p['Tienda'] = wc_get_page_permalink( 'shop' );
		$p['Carrito'] = wc_get_cart_url();
	}
	foreach ( array( 'atenciones', 'packs', 'planes' ) as $slug ) {
		$t = get_term_by( 'slug', $slug, 'product_cat' );
		if ( $t && ! is_wp_error( $t ) ) {
			$p[ $t->name ] = get_term_link( $t );
		}
	}
	foreach ( array( 44 => 'Plan Individual', 45 => 'Plan Familiar', 41 => 'Pack Control' ) as $id => $n ) {
		if ( get_post_status( $id ) === 'publish' ) {
			$p[ $n ] = get_permalink( $id );
		}
	}
	return $p;
}

function ps_rv_page() {
	$pags = ps_rv_paginas();
	?>
	<div class="wrap ps-rv">
		<h1>Vista responsive</h1>
		<p>Mira cómo se ve el sitio en cada pantalla. Puedes navegar y hacer clic dentro de la vista.</p>
		<div class="ps-rv-bar" id="psRvDev"></div>
		<div class="ps-rv-bar">
			<label>Página <select id="psRvPag">
				<?php foreach ( $pags as $n => $u ) : ?>
					<option value="<?php echo esc_url( $u ); ?>"><?php echo esc_html( $n ); ?></option>
				<?php endforeach; ?>
			</select></label>
			<input type="url" id="psRvUrl" placeholder="<?php echo esc_attr( home_url( '/' ) ); ?>…" style="min-width:280px">
			<button class="button" id="psRvIr">Ir</button>
			<button class="button" id="psRvRot"><span class="dashicons dashicons-image-rotate"></span> Girar</button>
			<button class="button" id="psRvRec"><span class="dashicons dashicons-update"></span> Recargar</button>
			<a class="button" id="psRvAbrir" target="_blank" rel="noopener">Abrir en pestaña</a>
			<span id="psRvInfo" class="ps-rv-info"></span>
		</div>
		<div class="ps-rv-stage" id="psRvStage"></div>
	</div>
	<style>
		.ps-rv-bar{display:flex;flex-wrap:wrap;gap:6px;align-items:center;margin:10px 0}
		.ps-rv-bar .button.on{background:#164BA8;border-color:#164BA8;color:#fff}
		.ps-rv-bar .dashicons{vertical-align:-4px;font-size:16px}
		.ps-rv-info{color:#4A5B73;margin-left:6px}
		.ps-rv-stage{background:#EAF1FC;border-radius:12px;padding:20px;display:flex;gap:24px;justify-content:center;align-items:flex-start;overflow:hidden}
		.ps-rv-cell{display:flex;flex-direction:column;align-items:center;gap:6px}
		.ps-rv-cap{font-size:12px;color:#4A5B73}
		.ps-rv-box{position:relative;overflow:hidden}
		.ps-rv-dev{position:absolute;top:0;left:0;background:#fff;border:10px solid #1d2433;border-radius:26px;overflow:hidden;transform-origin:0 0;box-sizing:content-box}
		.ps-rv-dev.pc{border-width:8px;border-radius:8px}
		.ps-rv-dev iframe{display:block;border:0;background:#fff}
	</style>
	<script>
	(function(){
		var D=[
			['Celular chico','smartphone',360,640],['iPhone','smartphone',390,844],['Android grande','smartphone',412,915],
			['Tablet','tablet',768,1024],['Notebook','laptop',1366,768],['Escritorio','desktop',1920,1080],
			['Todas a la vez','screenoptions',0,0]
		];
		var TODAS=[1,3,5];
		var cur=+(localStorage.getItem('psRvDev')||1),rot=false;
		var url=document.getElementById('psRvPag').value;
		var bar=document.getElementById('psRvDev'),st=document.getElementById('psRvStage');
		D.forEach(function(d,i){var b=document.createElement('button');b.className='button';b.type='button';
			b.innerHTML='<span class="dashicons dashicons-'+d[1]+'"></span> '+d[0];
			b.onclick=function(){cur=i;rot=false;try{localStorage.setItem('psRvDev',i)}catch(e){}draw()};bar.appendChild(b);});
		function limpiar(f){try{var doc=f.contentDocument;if(!doc)return;var s=doc.createElement('style');
			s.textContent='#wpadminbar{display:none!important}html{margin-top:0!important}';doc.head.appendChild(s);
			document.getElementById('psRvUrl').value=f.contentWindow.location.href;}catch(e){}}
		function celda(d,availW,maxH){
			var w=rot?d[3]:d[2],h=rot?d[2]:d[3],bw=w>=1000?8:10;
			var s=Math.min(1,availW/(w+bw*2),maxH/(h+bw*2));
			var c=document.createElement('div');c.className='ps-rv-cell';
			var box=document.createElement('div');box.className='ps-rv-box';box.style.width=((w+bw*2)*s)+'px';box.style.height=((h+bw*2)*s)+'px';
			var dv=document.createElement('div');dv.className='ps-rv-dev'+(w>=1000?' pc':'');dv.style.transform='scale('+s+')';
			var f=document.createElement('iframe');f.width=w;f.height=h;f.src=url;f.onload=function(){limpiar(f)};
			dv.appendChild(f);box.appendChild(dv);c.appendChild(box);
			var cap=document.createElement('div');cap.className='ps-rv-cap';cap.textContent=d[0]+' · '+w+' × '+h+' px · '+Math.round(s*100)+' %';c.appendChild(cap);
			return c;
		}
		function draw(){
			[].forEach.call(bar.children,function(b,i){b.classList.toggle('on',i===cur)});
			st.innerHTML='';var W=st.clientWidth-40,H=Math.max(420,window.innerHeight-260);
			var info=document.getElementById('psRvInfo');
			if(D[cur][2]===0){var per=(W-48)/3;TODAS.forEach(function(i){st.appendChild(celda(D[i],per,H))});info.textContent='Celular, tablet y escritorio lado a lado';}
			else{st.appendChild(celda(D[cur],W,H));info.textContent='';}
			document.getElementById('psRvAbrir').href=url;
		}
		document.getElementById('psRvPag').onchange=function(e){url=e.target.value;draw()};
		document.getElementById('psRvIr').onclick=function(){var u=document.getElementById('psRvUrl').value.trim();
			if(u&&u.indexOf(location.origin)===0){url=u;draw()}else if(u){document.getElementById('psRvInfo').textContent='Usa una dirección de este sitio.'}};
		document.getElementById('psRvRot').onclick=function(){rot=!rot;draw()};
		document.getElementById('psRvRec').onclick=draw;
		var t;window.addEventListener('resize',function(){clearTimeout(t);t=setTimeout(draw,200)});
		draw();
	})();
	</script>
	<?php
}
