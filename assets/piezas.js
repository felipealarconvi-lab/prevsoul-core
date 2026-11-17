/* Prevsoul · Máquina de piezas: dibuja en canvas las piezas de cada producto
 * (cuadrado 1080, historia 1080×1920, slide 1600×600) con el estilo de la marca. */
(function () {
	'use strict';
	var C = { celeste: '#7BA7D9', rosa: '#EDA0A8', lima: '#CFDC82', periwinkle: '#4E5BA6', oliva: '#ABB672' };
	var NOCHE = '#0B1A2E', BOTON = '#496298';
	var TXT_SLIDE = { celeste: NOCHE, lima: NOCHE, rosa: '#fff', periwinkle: '#fff', oliva: '#fff' };
	var FMT = { cuadrado: [1080, 1080], historia: [1080, 1920], slide: [1600, 600] };
	var imgCache = {};

	function fonts() {
		if (!document.fonts || !document.fonts.load) { return Promise.resolve(); }
		return Promise.all([
			document.fonts.load('700 40px Quicksand'),
			document.fonts.load('80px "Caveat Brush"'),
			document.fonts.load('30px "Homemade Apple"'),
			document.fonts.load('20px "Space Mono"')
		]).catch(function () {});
	}
	function loadImg(url) {
		if (!url) { return Promise.resolve(null); }
		if (!imgCache[url]) {
			imgCache[url] = new Promise(function (ok) {
				var i = new Image();
				i.onload = function () { ok(i); };
				i.onerror = function () { ok(null); };
				i.src = url;
			});
		}
		return imgCache[url];
	}
	function seed(s) {
		var h = 7;
		for (var i = 0; i < s.length; i++) { h = (h * 31 + s.charCodeAt(i)) % 100003; }
		return function () { h = (h * 9301 + 49297) % 233280; return h / 233280; };
	}
	function cover(x, img, dx, dy, dw, dh, fy) {
		var r = Math.max(dw / img.width, dh / img.height), sw = dw / r, sh = dh / r;
		var sx = (img.width - sw) / 2, sy = Math.max(0, Math.min(img.height - sh, (img.height - sh) * fy));
		x.drawImage(img, sx, sy, sw, sh, dx, dy, dw, dh);
	}
	function font(x, f) { x.font = f; }
	function fit(x, text, f, size, max) {
		while (size > 10) { font(x, f.replace('#', size)); if (x.measureText(text).width <= max) { break; } size -= 4; }
		return size;
	}
	function wrap(x, text, max) {
		var w = text.split(/\s+/), lines = [], cur = '';
		w.forEach(function (p) {
			var t = cur ? cur + ' ' + p : p;
			if (x.measureText(t).width > max && cur) { lines.push(cur); cur = p; } else { cur = t; }
		});
		if (cur) { lines.push(cur); }
		return lines;
	}
	/* Pincelada de pincel seco: banda inclinada con bordes irregulares y vetas. */
	function brush(x, cx, cy, w, h, ang, color, key) {
		var r = seed(key || 'p');
		x.save(); x.translate(cx, cy); x.rotate(ang * Math.PI / 180); x.fillStyle = color;
		x.beginPath(); x.moveTo(-w / 2, -h / 2);
		var i, n = 18;
		for (i = 0; i <= n; i++) { x.lineTo(-w / 2 + w * i / n, -h / 2 + (r() - .5) * h * .12); }
		for (i = 0; i <= 8; i++) { x.lineTo(w / 2 + (r() * .9 - .1) * h * .45, -h / 2 + h * i / 8); }
		for (i = n; i >= 0; i--) { x.lineTo(-w / 2 + w * i / n, h / 2 + (r() - .5) * h * .12); }
		for (i = 8; i >= 0; i--) { x.lineTo(-w / 2 - (r() * .7) * h * .35, -h / 2 + h * i / 8); }
		x.closePath(); x.fill();
		x.globalAlpha = .25; x.fillStyle = '#ffffff';
		for (i = 0; i < 5; i++) { x.fillRect(-w / 2 + r() * w * .5, -h / 2 + r() * h, w * (.3 + r() * .5), Math.max(1, h * .02)); }
		x.restore();
	}
	function sp(x, v) { if ('letterSpacing' in x) { x.letterSpacing = v; } }

	function drawVertical(x, d, img, W, H) {
		var col = C[d.familia] || C.rosa, story = H > W;
		if (img) { cover(x, img, 0, 0, W, H, .3); } else { x.fillStyle = col; x.fillRect(0, 0, W, H); }
		var g = x.createLinearGradient(0, H * (story ? .5 : .4), 0, H);
		g.addColorStop(0, 'rgba(11,26,46,0)'); g.addColorStop(.42, 'rgba(11,26,46,.9)'); g.addColorStop(1, 'rgba(11,26,46,.97)');
		x.fillStyle = g; x.fillRect(0, 0, W, H);
		x.textAlign = 'center'; x.textBaseline = 'alphabetic';
		var y = H * (story ? .67 : .615);
		font(x, '700 ' + Math.round(W * .046) + 'px Quicksand'); x.fillStyle = d.familia === 'periwinkle' ? '#A9B4F0' : col;
		x.fillText(d.nombre, W / 2, y);
		var hs = fit(x, d.heroe, '#px "Caveat Brush"', Math.round(W * (story ? .23 : .2)), W * .86);
		x.fillStyle = '#fff'; font(x, hs + 'px "Caveat Brush"');
		y += hs * .86; x.fillText(d.heroe, W / 2, y);
		if (d.pincel) {
			var ps = Math.round(W * .038); font(x, ps + 'px "Homemade Apple"');
			var tw = Math.min(W * .8, x.measureText(d.pincel).width), py = y + W * .085;
			brush(x, W / 2, py, tw + W * .14, W * .095, -3, col, d.pincel);
			x.save(); x.translate(W / 2, py); x.rotate(-3 * Math.PI / 180);
			x.fillStyle = d.familia === 'periwinkle' ? '#fff' : NOCHE; x.textBaseline = 'middle';
			x.fillText(d.pincel, 0, ps * .12, W * .8); x.restore(); y = py + W * .05;
		}
		if (story && d.frase) {
			font(x, '500 ' + Math.round(W * .04) + 'px Quicksand'); x.fillStyle = '#fff';
			wrap(x, '“' + d.frase + '”', W * .8).slice(0, 3).forEach(function (l, k) { x.fillText(l, W / 2, y + W * .09 + k * W * .052); });
		}
		font(x, Math.round(W * .048) + 'px "Caveat Brush"'); x.textAlign = 'right'; x.fillStyle = '#fff';
		x.fillText('Prev Soul', W - W * .05, H - W * .04);
	}
	function drawSlide(x, d, img, W, H) {
		var col = C[d.familia] || C.rosa, tc = TXT_SLIDE[d.familia] || '#fff';
		x.fillStyle = col; x.fillRect(0, 0, W, H);
		var px = W * .58;
		if (img) {
			cover(x, img, px, 0, W - px, H, .3);
			var g = x.createLinearGradient(px, 0, px + 160, 0);
			g.addColorStop(0, col); g.addColorStop(1, 'rgba(255,255,255,0)');
			x.fillStyle = g; x.fillRect(px - 1, 0, 162, H);
		}
		x.textAlign = 'left'; x.textBaseline = 'alphabetic';
		font(x, '700 44px Quicksand'); x.fillStyle = tc;
		var ls = wrap(x, '“' + d.frase + '”', 560).slice(0, 3), y = 110;
		ls.forEach(function (l) { x.fillText(l, 90, y); y += 56; });
		font(x, '20px "Space Mono"'); sp(x, '6px'); x.fillText(d.nombre.toLowerCase(), 92, y + 8); sp(x, '0px');
		if (d.pincel) {
			font(x, '700 34px Quicksand');
			var tw = Math.min(520, x.measureText(d.pincel).width), cy = y + 82;
			brush(x, 90 + (tw + 70) / 2, cy, tw + 70, 66, -2, d.familia === 'periwinkle' ? '#3F3F71' : C.periwinkle, d.pincel);
			x.save(); x.translate(90 + (tw + 70) / 2, cy); x.rotate(-2 * Math.PI / 180);
			x.textAlign = 'center'; x.textBaseline = 'middle'; x.fillStyle = '#fff'; x.fillText(d.pincel, 0, 2, 520); x.restore();
		}
		var hs = 210, hx = W * .37;
		hs = fit(x, d.heroe, '#px "Caveat Brush"', hs, W - hx - 330);
		font(x, hs + 'px "Caveat Brush"');
		x.save(); x.shadowColor = 'rgba(11,26,46,.18)'; x.shadowOffsetY = 4; x.fillStyle = '#fff';
		x.fillText(d.heroe, hx, H * .78); x.restore();
		var bw = 250, bh = 76, bx = W - 50 - bw, by = H - 50 - bh;
		x.fillStyle = BOTON; x.beginPath();
		if (x.roundRect) { x.roundRect(bx, by, bw, bh, [18, 18, 0, 18]); } else { x.rect(bx, by, bw, bh); }
		x.fill(); font(x, '700 24px Quicksand'); sp(x, '5px'); x.fillStyle = '#fff'; x.textAlign = 'center'; x.textBaseline = 'middle';
		x.fillText('PREVSOUL', bx + bw / 2 + 3, by + bh / 2 + 1); sp(x, '0px');
	}
	function draw(canvas, fmt, d) {
		var s = FMT[fmt]; canvas.width = s[0]; canvas.height = s[1];
		return fonts().then(function () { return loadImg(d.img); }).then(function (img) {
			var x = canvas.getContext('2d'); x.clearRect(0, 0, s[0], s[1]);
			if (fmt === 'slide') { drawSlide(x, d, img, s[0], s[1]); } else { drawVertical(x, d, img, s[0], s[1]); }
			return canvas;
		});
	}
	function slug(s) { return (s || 'pieza').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, ''); }
	function download(d, fmt) {
		var c = document.createElement('canvas');
		return draw(c, fmt, d).then(function () {
			return new Promise(function (ok) {
				c.toBlob(function (b) {
					var a = document.createElement('a'); a.href = URL.createObjectURL(b);
					a.download = 'prevsoul-' + slug(d.nombre) + '-' + fmt + '.png';
					document.body.appendChild(a); a.click(); a.remove();
					setTimeout(function () { URL.revokeObjectURL(a.href); ok(); }, 400);
				}, 'image/png');
			});
		});
	}
	function downloadAll(d) {
		return ['cuadrado', 'historia', 'slide'].reduce(function (p, f) { return p.then(function () { return download(d, f); }); }, Promise.resolve());
	}
	/* Auditor clínico automático: reglas de la marca sobre los textos. */
	function audit(d) {
		var out = [], all = [d.heroe, d.frase, d.pincel].join(' ');
		if (/\bsegur[oa]s?\b/i.test(all)) { out.push('Usa «plan», no «seguro».'); }
		if (/\b(el|la|los|las)\s+(m[aá]s|mejor(es)?)\b|\bgarantiz|\bcura(r|mos)?\b|100\s?%|\bsin dolor\b|\bnunca m[aá]s\b|\bel m[aá]s barato\b/i.test(all)) { out.push('Evita promesas clínicas y superlativos absolutos.'); }
		if (/\bdem[aá]s\b/i.test(all)) { out.push('¿Quisiste decir «de más»?'); }
		if ((d.frase || '').trim().split(/\s+/).length > 12) { out.push('La frase pasa de 12 palabras.'); }
		if ((d.heroe || '').trim().split(/\s+/).length > 2) { out.push('La palabra héroe debe tener 1 o 2 palabras.'); }
		if (!(d.heroe || '').trim()) { out.push('Falta la palabra héroe.'); }
		if (/\s{2,}|\s$|^\s/.test(d.heroe + '|' + d.frase + '|' + d.pincel)) { out.push('Hay espacios de más.'); }
		if (!d.img) { out.push('El producto no tiene imagen: sube una foto de personas, sin texto.'); }
		return out;
	}
	function auditHtml(list) {
		return list.length ? '<ul class="ps-pz-warn">' + list.map(function (w) { return '<li>' + w.replace(/</g, '&lt;') + '</li>'; }).join('') + '</ul>' : '<p class="ps-pz-ok">Auditor: sin alertas.</p>';
	}

	/* Ficha del producto */
	function mountEditor(root) {
		var base = JSON.parse(root.getAttribute('data-pz')), t = null;
		var f = function (n) { return root.querySelector('[name="ps_pz[' + n + ']"]'); };
		function data() {
			var fam = root.querySelector('[name="ps_pz[familia]"]:checked');
			var pin = f('pincel').value.replace('{precio}', base.precio || '').trim();
			return { nombre: base.nombre, img: base.img, heroe: f('heroe').value.trim(), frase: f('frase').value.trim(), pincel: pin, familia: fam ? fam.value : base.familia };
		}
		function render() {
			var d = data();
			root.querySelectorAll('canvas[data-fmt]').forEach(function (c) { draw(c, c.getAttribute('data-fmt'), d); });
			root.querySelector('.ps-pz-audit').innerHTML = auditHtml(audit(d));
		}
		root.addEventListener('input', function () { clearTimeout(t); t = setTimeout(render, 180); });
		root.addEventListener('change', render);
		root.addEventListener('click', function (e) {
			var b = e.target.closest('[data-dl]'); if (!b) { return; }
			e.preventDefault(); var d = data();
			(b.getAttribute('data-dl') === 'todo' ? downloadAll(d) : download(d, b.getAttribute('data-dl')));
		});
		render();
	}
	/* Galería de piezas */
	function mountGallery(root) {
		var list = JSON.parse(root.getAttribute('data-pzs'));
		list.forEach(function (d, k) {
			var card = root.querySelector('[data-k="' + k + '"]');
			card.querySelectorAll('canvas[data-fmt]').forEach(function (c) { draw(c, c.getAttribute('data-fmt'), d); });
			card.querySelector('.ps-pz-audit').innerHTML = auditHtml(audit(d));
			card.addEventListener('click', function (e) {
				var b = e.target.closest('[data-dl]'); if (!b) { return; }
				e.preventDefault(); (b.getAttribute('data-dl') === 'todo' ? downloadAll(d) : download(d, b.getAttribute('data-dl')));
			});
		});
		var all = document.getElementById('ps-pz-all');
		if (all) {
			all.addEventListener('click', function (e) {
				e.preventDefault(); all.disabled = true;
				list.reduce(function (p, d) { return p.then(function () { return downloadAll(d); }); }, Promise.resolve()).then(function () { all.disabled = false; });
			});
		}
	}
	window.PSPZ = { draw: draw, audit: audit, download: download };
	function boot() {
		document.querySelectorAll('.ps-pz-editor').forEach(mountEditor);
		document.querySelectorAll('.ps-pz-gallery').forEach(mountGallery);
	}
	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', boot); } else { boot(); }
})();
