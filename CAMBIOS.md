# Cambios de Prevsoul Core

## 2.28.1
- Looker Studio: fechas como AAAAMMDD y meses como AAAAMM para que se lean como fecha.

## 2.28.0
- Datos → Looker Studio: enlaces privados con clave para Google Sheets (fotos diarias, canales y productos por mes, solo agregados).

## 2.27.0
- Nueva pantalla Prevsoul → Datos: resumen de indicadores (MRR, planes activos, altas y bajas, ventas, pagos sin completar, atenciones), origen de ventas, diccionario de métricas editable y exportación CSV anonimizada con registro de descargas.
- Cada pedido guarda el primer origen del paciente (UTM, gclid, fbclid, referente) y si pasó por la Consulta online.
- Foto diaria automática de indicadores a las 03:00, excluyendo pedidos de prueba.

## 2.26.1
- Corrige el acceso a Prevsoul → Antes de publicar.

## 2.26.0
- Prueba social: datos de demostración (ficticios) que solo se ven mientras el sitio está en construcción; se activan y apagan en Prevsoul → Prueba social.
- Nueva lista Prevsoul → Antes de publicar, con aviso en el admin mientras queden pendientes.

## 2.25.0
- Prueba social (Prevsoul → Prueba social): vendidos reales en tramos (+5, +10, +25…), dientrellas doradas con destellos (B1 en tarjetas, B2 en la ficha), sellos y testimonio destacado con autorización del paciente. Todo automático; solo se puede ocultar.
- Formulario de reseñas: casilla para autorizar el uso como testimonio.

## 2.24.1
- Vista responsive: el selector incluye Atenciones, Packs y Planes.

## 2.24.0
- Nueva pantalla Prevsoul → Vista responsive: ver el sitio en celular chico, iPhone, Android grande, tablet, notebook y escritorio, girar, elegir página o pegar una dirección, y modo «Todas a la vez».

## 2.23.1
- Logo: «prevsoul» un poco más cerca del diente en la cabecera, el pie y las piezas.

## 2.23.0
- Piezas firmadas con el logo nuevo: el cuadrado, la historia y las tarjetas de productos cambian «Prev Soul» a pincel por el diente con escudo y «prevsoul».
- Slide del inicio: el logo va abajo a la izquierda y el botón dice su acción («Contratar plan», «Ver pack», «Agendar» o el texto del anuncio) en azul profundo, en vez de «PREVSOUL».
- Las descargas de la máquina de piezas usan la misma firma y el mismo botón.

## 2.22.0
- Logo nuevo: el diente original con un escudo verde menta y un check sobre la muesca (prevención: diente protegido), en azul profundo #164BA8. «prev» en Plus Jakarta Sans negrita y «soul» en Quicksand, con una separación mínima.
- Aplicado en la cabecera, el pie, el ícono de la pestaña y la página en construcción. Los logos de temporada (Navidad, primavera y verano) se arman sobre el diente con escudo.
- Sobre fondos oscuros el escudo pasa a menta clara.

## 2.21.0
- Ahorro del inicio más fácil de leer: dos barras con el total de los años (con tu plan y sin prevención) y la diferencia rayada en verde con «Ahorras $…». Se mantiene la cifra destacada del ahorro; se quitan las barras año por año.

## 2.20.2
- Gráfico de ahorro: «Sin prevención» pasa del naranjo al rosa Soul profundo (#D9727F); «Con plan» sigue en azul Prevsoul. Combinación validada en contraste y daltonismo.
- Precios redondos sin terminación en 990: Pack Encías $70.000, Pack Niños $40.000, Pack Control $50.000 (sin oferta), Plan Individual $10.000 y Plan Familiar desde $17.500.

## 2.20.1
- Inicio: los carruseles de Atención dental y Packs dentales (y los resultados del buscador) usan el cuadrado de la marca y la frase de cada producto; la etiqueta «Oferta» se mantiene.

## 2.20.0
- Tarjetas de atenciones, packs y planes con el estilo de la marca: arriba un cuadrado con la foto del producto, su palabra escrita a mano y el precio en la pincelada del color de su familia; debajo, la frase de la pieza entre comillas.
- Se aplica en las páginas Atención dental, Packs dentales y Planes dentales, en la tienda y las categorías de WooCommerce y en las tarjetas de planes del inicio.
- La ficha de cada producto mantiene solo la foto.
- Todo sale del recuadro «Pieza Prevsoul» de cada producto.

## 2.19.2
- Plan Familiar: el contador de la ficha parte en 2 integrantes (coincide con «desde $17.480»); precio base del producto ajustado a $17.480.

## 2.19.1
- Slider y piezas: la palabra escrita a mano ya no choca con la frase ni con el botón.

## 2.19.0
- Slider del inicio con el estilo nuevo en todos los anuncios: foto, palabra héroe escrita a mano, frase, pincelada y color por familia. El diseño anterior deja de usarse.
- Anuncios vinculados a un producto toman la pieza del producto (palabra, frase, color y precio); los demás usan el título como frase y el texto como bajada.
- Campos nuevos en cada anuncio: «Palabra héroe» y familia de color (vacío = automático). Se mantienen la etiqueta, el estado en vivo de la sala de espera y el botón con su texto y enlace.
- Vista previa del anuncio en el panel con el estilo nuevo.

## 2.18.1
- Auditor de piezas: acepta palabras héroe de hasta 3 palabras (como «A la vista»).

## 2.18.0
- Máquina de piezas: cada producto arma solo su slide del inicio (1600 × 600), su cuadrado para redes (1080 × 1080) y su historia (1080 × 1920) con el estilo de la marca: foto del producto, palabra héroe escrita a mano, frase, pincelada y color por familia.
- Recuadro «Pieza Prevsoul» en la ficha de cada producto con vista previa en vivo, botones de descarga en PNG y casilla «Mostrar en el slider del inicio».
- Textos del equipo creativo listos para los 13 productos; los productos nuevos del Armador reciben una sugerencia automática según su tipo. `{precio}` pone el precio vigente solo.
- Auditor automático: avisa si aparece «seguro», promesas o superlativos, «demás», frases de más de 12 palabras o falta la foto.
- Panel → Prevsoul → Piezas: galería con las piezas de todos los productos y descarga de todo.
- Slider: los productos marcados se muestran con el nuevo diseño (también si ya tenían anuncio); en el celular la foto va arriba.

## 2.17.0
- Plan Familiar «Prevsoul cuida a quien cuida»: precio por integrante (titular al precio del Plan Individual y cada integrante adicional al % de Armador → Ajustes), contador de integrantes, «Tu control va primero», segundo responsable opcional y bloque «Nosotros nos acordamos por ti».
- Mi cuenta → «Mi plan»: integrantes (titular primero), meta personal de cada uno con sus propias palabras, último control y estado (al día, pronto, por agendar); segundo responsable editable.
- En cada pedido con plan, recuadro «Prevsoul · Plan y controles» para registrar la fecha del último control de cada integrante.
- Recordatorio mensual (uno por familia, a la titular y al segundo responsable) cuando alguien está por agendar.
- Correo de seguimiento cada 6 meses: «¿Tu boca te deja hacer lo que quieres?» (1 a 5); con 1 a 3 invita a la consulta online, con 4 o 5 agradece.
- «Para qué te sirve»: campo nuevo en cada producto, con textos iniciales para los 13 productos, visible en la ficha.
- Consulta online: dos preguntas nuevas (qué te gustaría poder hacer y qué esperas de la conversación) y «¿Qué te lo está impidiendo hoy?»; se guardan en la precita y llegan en el aviso.
- Armador: tarjeta «Plan Familiar y metas» en Rendimiento y tabla de precio por integrante en Armar plan.
- Privacidad: nombres, metas y respuestas quedan solo en el sitio; no se envían a Meta ni a Clarity.

## 2.16.0
- Carrusel de inicio con imagen: si el anuncio está vinculado a un producto y no tiene imagen propia, usa la imagen del producto.
- Nuevo diseño de cada diapositiva: texto a la izquierda y foto cuadrada a la derecha con bordes redondeados y bloque menta detrás; en el celular la foto va arriba.
- Etiqueta de descuento automática sobre la foto cuando el producto está rebajado.

## 2.15.2
- Ficha de producto: botón de compra alineado al borde y etiqueta «¡Oferta!» en menta.

## 2.15.1
- Ficha de producto: precio en tamaño grande y botón de compra alineado cuando el producto se vende de a uno.

## 2.15.0
- La ficha de producto usa ahora el diseño Prevsoul completo: misma cabecera (menú y botón «Consulta online»), pie de página y barra de pestañas en el celular que el resto del sitio.
- Paleta corporativa en la ficha: título azul noche, precio grande, botón de compra azul Prevsoul, «Realiza» en tarjeta azul bruma, imagen con bordes redondeados, ruta de navegación y pestañas en azul y menta.
- Etiqueta de categoría en menta sobre el título.
- Segundo botón «¿Dudas? Consulta online» y tres garantías cortas bajo el botón de compra (editables con el filtro ps_producto_garantias).
- Productos relacionados como tarjetas.

## 2.14.0
- Nueva pantalla Prevsoul → Armador de packs y planes.
- Catálogo UCO: unidades por prestación, valor UCO 2026, factor público editable, rango de precios de mercado 2026 y marca «Dentro / Bajo / Sobre el mercado».
- Armar pack y Armar plan: precio redondeado, pago a la clínica, margen neto, LTV, costo máximo por cliente en Meta, cuota en UF y vista de cómo lo ve el paciente; semáforo verde/rojo de rentabilidad, recomendaciones y advertencia ⚠ PG (precio gancho).
- Oportunidades (en rosado): necesidades del buscador con búsquedas, consultas online y compras, y el pack sugerido para cada una.
- Rendimiento: ventas, ingresos, margen estimado y origen de cada compra (anuncios Meta, redes, buscador, consulta online, directo) desde los pedidos; planes activos y abandono reales; resultados de la Prueba A/B; gasto en Meta ingresado a mano.
- Recomendaciones de precios y Ajustes (comisiones por tipo, factor clínica en planes, pasarela, IVA, UF, redondeo) que se guardan.
- El botón para crear productos queda desactivado por ahora.

## 2.13.0
- Nuevo recuadro «Prueba A/B» en la Versión para redes: arma dos versiones del mismo anuncio con filosofías distintas (Informativa, El amigo que es dentista, Advertencia con salida, Educativa).
- Cada versión trae texto principal, título (máx. 40), descripción, enlace con seguimiento propio (utm_source=meta, utm_campaign=ab-producto, utm_content=ab-a/b-filosofía) y nombre del anuncio, con botón Copiar y revisión de reglas de salud de Meta.
- Paso a paso para crear la prueba en el Administrador de anuncios y leer el resultado en Analytics.

## 2.12.0
- Textos de la «Versión para redes» con la voz de marca «el amigo que es dentista»: fórmula de 5 tiempos (gancho, verdad directa, salida, qué incluye y precio, cierre amable), tuteo, chilenismos suaves y sin emojis.
- 3 versiones por objetivo (Vender, Educar, Recordar) y botón «Otra versión del texto ↻». Educar usa «Mito o verdad» y el «Diccionario de la boca».
- Precio con «Sin letra chica»; si es a cotizar, «Precio a cotizar, sin compromiso». La historia lleva una frase corta según el objetivo.
- Revisión automática ampliada: avisa si un texto da por hecho lo que le pasa a quien lee (con tú o usted) o dice «el más barato».

## 2.11.0
- «Versión para redes» con 9 formatos: Clásico, Precio grande, Dividido, Pregunta, Lista, Marca, Foto arriba, Foto de fondo y Foto en marco. Si el producto tiene imagen, parte en Foto arriba (el diseño de la 2.10.0); si no, en Clásico.
- 8 combinaciones de colores de la paleta v1: Bruma (predeterminada, antes «Claro»), Azul noche, Menta, Azul Prevsoul, Azul profundo, Noche cielo, Menta viva y Blanco. Fondos planos, frase clave en color de acento y precio en píldora.
- Foto: la imagen del producto o una elegida solo para redes desde la biblioteca de medios, con ajuste de encuadre. Formato, colores, foto y encuadre se guardan por producto.
- Se mantienen de la 2.10.0 la foto o el logo de quien lo realiza, el aviso de foto faltante o pequeña y la guía de tamaños. Nuevo recordatorio: foto propia o con permiso, sin «antes y después» ni tratamientos en boca.
- Guía de marca (docs/MARCA.md) con la tabla de formatos y colores para redes, e imágenes de muestra en docs/redes/.

## 2.10.0
- «Versión para redes» con fotografía: el feed (1080×1350) y la historia (1080×1920) usan la imagen del producto, recortada al centro (1080×640 y 1080×1000), con un panel de texto debajo.
- Plantillas nuevas con la paleta de marca: Azul noche, Claro y Menta. La Menta reemplaza a la Amarilla, y los productos que la tenían elegida pasan a Menta.
- Precio en un bloque destacado, botón de agenda y «Lo realiza» con la foto del odontólogo o el logo de la clínica, si existen.
- Sin foto, el espacio se llena con azul bruma y el diente de Prevsoul. La revisión automática avisa si falta la foto o si mide menos de 1080 px de ancho.
- Guía en el cuadro: foto del producto de 1600 × 1200 px (4:3) con lo importante al centro, y foto de quien lo realiza de 400 × 400 px.

## 2.9.0
- Paleta corporativa v1: a los azules de confianza se suma el menta de prevención (#5FD3B8), el mismo de las piezas para redes.
- Portada clara: el carrusel del inicio pasa a fondo azul bruma con título azul noche, precio en verde y botón «Arma tu plan» en azul. La vista previa de anuncios en el escritorio se ve igual.
- Botón «Consulta online» de la cabecera en menta. En las demás secciones oscuras (Prevsoul Live, Consulta online) el botón principal también va en menta.
- Checks de lo incluido en verde menta, estado «sala abierta» en menta y nueva etiqueta de beneficio .ps-chip-incluye (menta suave).

## 2.8.0
- Nuevo cuadro «Versión para redes» al editar cada atención, pack o plan: imágenes para el feed (1080×1350) y las historias (1080×1920) con 3 plantillas, textos para Instagram, Facebook e historias con enlace medible, revisión automática de reglas de salud de Meta y descarga en PNG. La publicación directa (fase 2) queda preparada pero desactivada.

## 2.7.1
- El sitio pasa a https: los enlaces internos guardados con http (anuncios del inicio, contenido y ajustes) se corrigen una sola vez.

## 2.7.0
- Nueva pantalla Prevsoul → Marketing: Google Analytics 4, Microsoft Clarity, Meta Pixel, Google Tag Manager opcional, verificación de dominio de Meta y redes sociales.
- Banner de consentimiento de cookies (Aceptar todas / Solo necesarias / Configurar por medición y marketing), recordado 6 meses y reabrible desde «Preferencias de cookies» en el pie. Nada se carga antes de aceptar.
- Clarity y Pixel no se cargan en precita, consulta online, resultados del buscador, carro (Clarity) ni cuentas; la frase del buscador se borra de la dirección antes de medir. A Meta solo llega el monto de la compra.
- Eventos: ver plan, agregar al carro, iniciar pago, compra, precita, buscador usado, clic en WhatsApp/correo/teléfono y lectura completa de artículos.
- Íconos de Instagram, Facebook y correo en el pie y schema sameAs. Etiqueta de verificación de Meta también en la página «en construcción».

## 2.6.0
- SEO del blog: título (máx. 60) y descripción (máx. 155) con palabra clave por artículo, resumen «En pocas palabras», enlace interno contextual, 3 preguntas frecuentes por artículo, firma «Revisado por Dr. Felipe Alarcón Villarroel, Cirujano Dentista» con fecha de actualización.
- Open Graph y Twitter Card con imagen propia 1200×630 por artículo y para la portada del blog.
- Datos estructurados schema.org: BlogPosting, WebPage con revisor, BreadcrumbList, FAQPage y CollectionPage en la portada.

## 2.5.0
- Nuevo Blog Prevsoul (/blog/): 14 artículos fundamentados en el estudio «La historia de un diente», con fuentes, filtros por etapa (Entender la caries, Comparar caminos, Decidir con información), llamado a planes y consulta online, y «Sigue leyendo».
- Columna «Blog Prevsoul» en el pie de página con 3 artículos destacados.

## 2.4.0
- Planes dentales: nueva sección «¿Para qué un plan si no me duele nada?» con la espiral de un diente en 9 fases (historia natural de la caries y ciclo restaurador), escalera de costos acumulados y cuánto ahorras según dónde se corta. Se puede ver con una muela o con un diente de adelante. Valores del arancel referencial UCO 2026.
- Íconos nuevos del diente en corte (esmalte, dentina y nervio) para cada fase.

## 2.3.2
- Inicio: el bloque de ahorro preventivo pasa a estar justo antes de Planes, con el enlace «Elige tu plan y empieza a ahorrar».

## 2.3.1
- El diente del logo queda más junto a la palabra «prevsoul» (se recortó el aire del dibujo), también en los logos de temporada.

## 2.3.0
- Nuevo logo: diente con un trozo faltante de borde irregular en la corona, para recordar lo que la prevención evita.
- Logos de temporada que cambian solos por fecha (Prevsoul → Logos de temporada): Verano (21 al 30 de noviembre y 7 de enero al 20 de marzo), Navidad (1 de diciembre al 6 de enero) y Primavera (21 al 30 de septiembre, desde 2027). Fechas editables, cada una se puede apagar y hay opción para fijar un logo a mano.
- El logo se usa en la cabecera, el pie de página, la página «Estamos restaurando» y el ícono de la pestaña.

## 2.2.3
- Precios del carro y el pago con punto de miles (el separador decimal estaba vacío).

## 2.2.2
- Precios con punto de miles también en el carro y el pago (el separador decimal era igual al de miles).

## 2.2.1
- Arreglos del carro: «Realiza» ya no aparece dos veces, precios con punto de miles y botón del carro vacío legible.
- Tablero: «1 pedido» en singular y aviso cuando no hay ventas en el periodo.

## 2.2.0
- Nuevo Tablero (Prevsoul → Tablero): ventas, ingreso Prevsoul, consultas online, órdenes por agendar, ventas del periodo, del interés a la compra, clínicas y actividad por perfil. Filtro Hoy / 7 / 30 / 90 días.
- Nuevo backoffice del Buscador (Prevsoul → Buscador): necesidades más buscadas, búsquedas sin resultado con «Asignar» para enseñarle al buscador, intención de compra por necesidad, tags más tocados y exportación CSV. Registra si la búsqueda terminó en consulta o compra, sin datos personales.
- Carro y pago con el diseño Prevsoul: cabecera del sitio, pasos de la compra, tarjetas, botones azules y quién realiza cada tratamiento. Precios con punto de miles ($39.990).
- Paneles de clínicas y dentistas con márgenes laterales.
- Cupones: nueva opción «¿Quién absorbe el descuento?». Por defecto Prevsoul: la clínica recibe sobre el precio de lista. PRUEBA100 queda como cupón de prueba y no genera pago.

## 2.1.0
- El botón de la cabecera ahora dice «Consulta online» (en el celular, «Consulta»), con ícono de video y el punto verde de disponibilidad.
- Nueva página exclusiva «Consulta online» con cómo funciona, la sala de espera y preguntas frecuentes. La dirección antigua /sala-de-espera/ redirige a la nueva.
- En el inicio, la sala de espera completa se reemplaza por un aviso pequeño que lleva a la consulta online.
- Dienteestrella amarillo relleno, como las clásicas 5 estrellas; los vacíos en gris claro.
- Los carruseles de Atención dental y Packs avanzan solos cada 4 segundos, vuelven al inicio y se pausan al pasar el mouse o tocarlos.

## 2.0.1
- Arreglo: la cuenta regresiva de Live y las flechas de los carruseles ahora funcionan.
- Arreglo: las iniciales de los odontólogos se ven sobre los fondos azul oscuro.

## 2.0.0
- Nuevo orden del inicio: slider, buscador, atención dental, sala de espera, packs, Prevsoul Live, planes; al final ahorro, odontólogos y llamado a prevenir. Se guarda una copia del contenido anterior de la página.
- Buscador más humano, con saludo de una odontóloga y dos líneas de tags (Lo que sientes / Tratamientos) editables en Prevsoul → Tags del buscador. Si no reconoce la necesidad, busca por nombre de tratamiento.
- Atención dental y packs en carrusel con foto, ordenados por valoración, con el mejor comentario positivo y quién realiza el tratamiento.
- Nuevo ícono «dienteestrella» para todas las valoraciones del sitio.
- Sala de espera en formato conversación, con panel en vivo de odontólogos conectados y personas esperando.
- Prevsoul Live en formato «cine» con cuenta regresiva, Recordarme y grabaciones anteriores.
- Planes en tabla de comparación con «El más elegido».

## 1.9.0
- El botón del menú vuelve a decir «Prevsoul Live» y lleva un punto verde que late cuando hay transmisión en vivo, o gris cuando no. La pestaña Live del celular muestra el mismo punto.
- Tres anuncios de ejemplo en el carrusel: oferta del Pack Control + Limpieza (rebaja real hasta el 30 de noviembre), Plan Familiar y la próxima transmisión de Prevsoul Live.

## 1.8.0
- Menú superior equilibrado: logo a la izquierda, menú centrado entre el logo y una línea divisoria, y carro y Sala de espera a la derecha.
- Más espacio entre las opciones del menú, letra semi-negrita y la página actual marcada con fondo celeste.
- Carro sin recuadro y logo un poco más grande.
- Nombres cortos en el menú (Atención, Packs, Planes, Live); se pueden editar en Apariencia → Menús.

## 1.7.0
- Prevsoul Live con interruptor de visibilidad (parte oculto): si está oculto se quita del menú, la barra del celular, la portada y el carrusel, y su página lleva al inicio. Administradores y dentistas lo siguen viendo.
- Página Live renovada: video con estado (próxima, EN VIVO, grabación), caja de preguntas con votos, «Recordarme» por correo y enlace a la sala de espera al terminar.
- Cuentas de dentista y Panel del dentista (prevsoul.cl/wp/panel-dentista): programar transmisiones, pegar el enlace de YouTube, iniciar y terminar, y responder u ocultar preguntas.
- Al iniciar una transmisión se avisa a los inscritos (solo si Live está visible).

## 1.6.0
- La caja azul de la portada ahora es un carrusel de anuncios: avanza solo cada 6 segundos, flechas, puntos y deslizar con el dedo en el celular.
- Nueva sección «Anuncios» en el panel: título, texto, etiqueta con color, imagen, producto vinculado, hasta dos botones, fechas desde/hasta y orden, con vista previa en vivo.
- El precio de oferta de un anuncio rebaja el producto de verdad durante sus fechas y lo devuelve a su precio al vencer, desactivar o borrar el anuncio.
- El mensaje de la sala de espera queda cargado como primer anuncio.

## 1.5.1
- Botón «Crear datos de prueba» en la página Prevsoul (odontóloga, clínica, productos asignados, cuenta de clínica y cupón PRUEBA100) y botón para borrarlos.

## 1.5.0
- Nuevo botón «Sala de espera» con concepto de puerta abierta: punto verde que late cuando hay odontólogos disponibles y «Deja tu consulta» cuando no.
- El carro ahora es un ícono con el número de productos.
- El aviso de la portada sigue el mismo estado de la sala.
- En el celular la cabecera cabe en una línea y el logo nunca se junta con los botones.

## 1.4.1
- Columna «Realiza» más legible en la lista de productos.

## 1.4.0
- Cada producto indica quién realiza el tratamiento, con foto del profesional o logo de la clínica.
- Al confirmarse el pago se crea una orden de atención por tratamiento y se avisa por correo al prestador.
- El paciente recibe los pasos a seguir y los datos de contacto para agendar (página de gracias, Mi cuenta y correo).
- Panel de prestadores en prevsoul.cl/panel-prestador: órdenes por agendar, agendadas y realizadas.
- Comisión Prevsoul del 20% por defecto, con porcentaje o monto fijo propio por producto.
- Liquidación: lo que se le debe a cada prestador y registro de pagos.

## 1.3.1
- Enlace «Ver qué cambió en cada versión» en el recuadro Actualizaciones.
- Primera actualización automática de prueba.

## 1.3.0
- Actualizaciones automáticas desde GitHub.
- Modo «Estamos restaurando» para visitantes mientras el sitio está en construcción.
- Botones de formulario visibles con cualquier tema.
- El enlace a la política de privacidad queda dentro de la frase del formulario.
