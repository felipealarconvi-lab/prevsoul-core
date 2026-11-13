<?php
/**
 * Página «Estamos restaurando» que ven los visitantes mientras el modo en construcción está activo.
 *
 * @package Prevsoul
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$ps_wa = preg_replace( '/\D+/', '', (string) ps_get( 'whatsapp' ) );
if ( '56912345678' === $ps_wa ) {
	$ps_wa = '';
}
?><!DOCTYPE html>
<html lang="es-CL">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Estamos restaurando · Prevsoul</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;700;800&display=swap">
<style>
:root{--navy:#0B2347;--blue:#1D5FD1;--soft:#E8F0FC;--muted:#5A6B85}
*{box-sizing:border-box}
html,body{margin:0;height:100%}
body{font-family:'Plus Jakarta Sans','Segoe UI',system-ui,sans-serif;color:var(--navy);background:#fff;display:flex;flex-direction:column;min-height:100vh}
.top{padding:22px 28px;display:flex;align-items:center;gap:10px;font-weight:800;font-size:22px;letter-spacing:-.02em}
.top svg{width:28px;height:28px}.top .ps-logo-svg:not(.ps-logo-base){width:42px;height:42px;margin:-7px -2px -7px -6px}
main{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:12px 24px 40px}
.art{width:min(300px,72vw);height:auto;margin-bottom:8px}
h1{font-size:clamp(34px,6vw,54px);line-height:1.05;letter-spacing:-.03em;margin:8px 0 12px;font-weight:800}
p{font-size:clamp(16px,2.2vw,19px);color:var(--muted);max-width:520px;margin:0 auto;line-height:1.55}
.bar{width:min(320px,70vw);height:10px;border-radius:99px;background:var(--soft);margin:26px auto 10px;overflow:hidden}
.bar span{display:block;height:100%;width:72%;border-radius:99px;background:var(--blue);animation:load 4s ease-in-out infinite}
.small{font-size:14px;font-weight:700;color:var(--blue)}
.wa{display:inline-flex;margin-top:22px;padding:12px 22px;border-radius:10px;background:var(--blue);color:#fff;font-weight:800;text-decoration:none}
.wa:hover{background:#174CA8}
footer{padding:20px;text-align:center;font-size:13px;color:var(--muted)}
/* animaciones de la ilustración */
.filling{transform-box:fill-box;transform-origin:0% 50%;animation:fill 4s ease-in-out infinite}
.drill{animation:drill 4s ease-in-out infinite}
.bur{animation:buzz .12s linear infinite}
.spark{transform-box:fill-box;transform-origin:center;animation:twinkle 2s ease-in-out infinite}
.spark.s2{animation-delay:.7s}.spark.s3{animation-delay:1.3s}
@keyframes fill{0%,8%{transform:scaleX(0);opacity:0}14%{opacity:1}62%,92%{transform:scaleX(1);opacity:1}100%{transform:scaleX(0);opacity:0}}
@keyframes drill{0%,8%{transform:translate(0,0)}62%{transform:translate(-14px,4px)}92%{transform:translate(-14px,4px)}100%{transform:translate(0,0)}}
@keyframes buzz{0%{transform:translate(0,0)}50%{transform:translate(.8px,-.8px)}100%{transform:translate(0,0)}}
@keyframes twinkle{0%,100%{transform:scale(.4);opacity:0}50%{transform:scale(1);opacity:1}}
@keyframes load{0%{width:28%}60%,92%{width:86%}100%{width:28%}}
@media (prefers-reduced-motion:reduce){.filling,.drill,.bur,.spark,.bar span{animation:none}.filling{transform:scaleX(.6)}.spark{opacity:1;transform:none}}
</style>
</head>
<body>
<header class="top">
	<?php echo function_exists( 'ps_logo_svg' ) ? ps_logo_svg( 'header' ) : ''; // phpcs:ignore ?>
	prevsoul
</header>
<main>
	<svg class="art" viewBox="0 0 260 240" role="img" aria-labelledby="ps-art-t">
		<title id="ps-art-t">Un diente con una caries que está siendo reparada</title>
		<circle cx="124" cy="126" r="104" fill="#E8F0FC"/>
		<!-- diente -->
		<path d="M72 78c0-27 26-36 52-22 26-14 52-5 52 22 0 24-8 38-12 62-5 30-9 64-23 64-11 0-11-38-17-48-6 10-6 48-17 48-14 0-18-34-23-64-4-24-12-38-12-62z" fill="#fff" stroke="#0B2347" stroke-width="6" stroke-linejoin="round"/>
		<path d="M92 66c6-6 16-8 24-4" fill="none" stroke="#C9D8F0" stroke-width="6" stroke-linecap="round"/>
		<!-- carita -->
		<circle cx="106" cy="112" r="5" fill="#0B2347"/>
		<circle cx="142" cy="112" r="5" fill="#0B2347"/>
		<path d="M113 128c6 6 16 6 22 0" fill="none" stroke="#0B2347" stroke-width="5" stroke-linecap="round"/>
		<circle cx="98" cy="124" r="5" fill="#F7B6C2" opacity=".8"/>
		<circle cx="150" cy="124" r="5" fill="#F7B6C2" opacity=".8"/>
		<!-- caries -->
		<path d="M136 66c8-6 22-5 26 4 4 8-2 17-11 19-10 2-21-2-22-10-1-5 2-9 7-13z" fill="#7A4A22"/>
		<path d="M143 72c5-3 12-2 14 3 2 4-2 8-7 9-6 1-11-1-11-5 0-3 1-5 4-7z" fill="#4E2C12"/>
		<!-- tapadura que va cubriendo la caries -->
		<path class="filling" d="M134 64c9-7 25-6 30 4 4 9-2 19-12 21-11 2-23-2-24-11-1-6 2-10 6-14z" fill="#F1F6FF" stroke="#1D5FD1" stroke-width="3.5"/>
		<!-- instrumento -->
		<g class="drill">
			<g class="bur"><path d="M166 66l10-7" stroke="#0B2347" stroke-width="4" stroke-linecap="round"/><circle cx="165" cy="67" r="3.5" fill="#0B2347"/></g>
			<path d="M174 60l14-10 44-30c4-3 9-2 11 2s1 8-3 11l-44 30-14 10z" fill="#B9C6D8" stroke="#0B2347" stroke-width="5" stroke-linejoin="round"/>
			<path d="M196 44l18-12" stroke="#fff" stroke-width="4" stroke-linecap="round" opacity=".8"/>
		</g>
		<!-- brillos -->
		<path class="spark s1" d="M58 58l4 10 10 4-10 4-4 10-4-10-10-4 10-4z" fill="#1D5FD1"/>
		<path class="spark s2" d="M196 128l3 7 7 3-7 3-3 7-3-7-7-3 7-3z" fill="#1D5FD1"/>
		<path class="spark s3" d="M60 170l3 6 6 3-6 3-3 6-3-6-6-3 6-3z" fill="#7FA6E8"/>
	</svg>
	<h1>Estamos restaurando</h1>
	<p>Estamos dejando nuestro sitio como nuevo.</p>
	<div class="bar" aria-hidden="true"><span></span></div>
	<div class="small">Restauración en curso</div>
	<?php if ( $ps_wa ) : ?>
		<a class="wa" href="<?php echo esc_url( 'https://wa.me/' . $ps_wa ); ?>">Escríbenos por WhatsApp</a>
	<?php endif; ?>
</main>
<footer>© <?php echo esc_html( gmdate( 'Y' ) ); ?> Prevsoul</footer>
</body>
</html>
