# Marca Prevsoul — Paleta corporativa v1

Prevención odontológica a tu medida. Corporativa pero amable: los azules transmiten confianza y el menta transmite prevención, salud y acción.

Aprobada el 15-nov-2026 y aplicada al sitio en la versión 2.9.0 (bloque «Paleta corporativa v1» al final de `assets/prevsoul.css`).

## Colores

| Nombre | Hex | Variable CSS | Uso |
|---|---|---|---|
| Azul noche | #0B2545 | `--ps-ink` | Color principal. Títulos, texto y fondos oscuros de anuncios |
| Azul Prevsoul | #1D5FD1 | `--ps-accent` | Botones y enlaces sobre fondo claro, con texto blanco |
| Azul profundo | #164BA8 | `--ps-accent-ink` | Hover de botones y enlaces |
| Azul bruma | #EAF1FC | `--ps-soft` | Portada del inicio, tarjetas y secciones suaves |
| Fondo | #F4F7FB | `--ps-bg` | Fondo general del sitio |
| Línea | #D7E1EE | `--ps-line` | Bordes y separadores |
| Menta | #5FD3B8 | `--ps-mint` | Acento de prevención: botón «Consulta online», precios y llamados a la acción sobre azul noche |
| Menta profunda | #167A64 | `--ps-mint-ink` | Verde sobre blanco: checks, precio de la portada |
| Menta suave | #E6F7F2 | `--ps-mint-soft` | Etiqueta de beneficio (`.ps-chip-incluye`) |
| Azul cielo | #7CC4F2 | `--ps-sky` | Acento secundario sobre azul noche |
| Gris pizarra | #4A5B73 | `--ps-muted` | Texto secundario |

Los colores funcionales (éxito #157347, error #B42318, estrella #A65F00 y oferta #FFD166) se usan solo para estados y etiquetas puntuales.

## Reglas

- Menta y azul cielo nunca van como texto sobre blanco. Para verde sobre blanco se usa menta profunda.
- Azul Prevsoul no va sobre azul noche (contraste 2,6:1).
- Cada pieza lleva como máximo dos acentos: menta y uno de los azules.
- **Sitio:** portada clara (azul bruma) con título azul noche, precio en menta profunda y botón azul. El botón «Consulta online» de la cabecera va en menta. En las secciones oscuras, el botón principal va en menta con texto azul noche.
- **Redes y anuncios:** el estilo principal es **Bruma** (ver «Estilos para redes»). Los otros dos estilos quedan guardados para usarlos cuando se necesiten.
- **Tipografía:** Plus Jakarta Sans, en 800 para títulos y entre 400 y 600 para texto.
- **Formas:** 14 px de radio en las tarjetas del sitio, 28 px en las tarjetas de anuncios y botones tipo píldora. Íconos de línea. Sin emojis ni degradados.

## Estilos para redes

En el panel «Versión para redes» de cada producto se eligen por separado el **formato** y los **colores**: 9 × 8 = 72 diseños. Ninguno se elimina; las imágenes de muestra están en `docs/redes/`.

### Formatos

| Formato | Para qué |
|---|---|
| Clásico | Promoción general |
| Precio grande | Ofertas, lanzamientos y precio de entrada |
| Dividido | Frase arriba en color y el detalle abajo sobre blanco |
| Pregunta | Contenido educativo («¿Cuándo fue tu último control dental?») |
| Lista | Explicar qué incluye, con ítems numerados |
| Marca | Presencia de marca, poco texto |
| Foto arriba | Foto del producto con panel de texto debajo (inicial si hay foto) |
| Foto de fondo | Foto a toda la pieza con tarjeta de color abajo |
| Foto en marco | Foto en recuadro redondeado sobre el color |

Fotos: propias o con permiso, 1600 × 1200 px (4:3) con lo importante al centro; nunca «antes y después» ni tratamientos en boca.

### Colores

| Colores | Fondo | Frase clave | Precio | Uso sugerido |
|---|---|---|---|---|
| **Bruma** (predeterminado) | Azul bruma #EAF1FC | Azul Prevsoul | Píldora azul, texto blanco | Día a día |
| Azul noche | Azul noche #0B2545 | Menta | Píldora menta, texto azul noche | Lanzamientos |
| Menta | Menta suave #E6F7F2 | Menta profunda | Píldora azul noche, texto menta | Familias y temporada |
| Azul Prevsoul | Azul Prevsoul #1D5FD1 | Menta | Píldora azul noche, texto menta | Piezas muy de marca |
| Azul profundo | Azul profundo #164BA8 | Azul cielo | Píldora blanca, texto azul profundo | Planes, tono institucional |
| Noche cielo | Azul noche #0B2545 | Azul cielo | Píldora azul cielo, texto azul noche | Alternativa suave a Azul noche |
| Menta viva | Menta #5FD3B8 | Azul profundo | Píldora azul noche, texto blanco | Verano y piezas llamativas |
| Blanco | Blanco #FFFFFF | Menta profunda | Píldora azul noche, texto menta | Aspecto limpio y clínico |

Todas usan tarjeta blanca (azul bruma en Blanco) con checks en menta profunda. Se descartó el fondo menta profunda: ninguna frase clave de la paleta se leía bien sobre él.

## Formatos de anuncios

- Post para el feed: 1080×1080 (o 1080×1350 en «Versión para redes»)
- Historia: 1080×1920
- Banner para Meta o la web: 1200×628

## Logo definitivo (19-nov-2026, versión 2.40.0)

- Símbolo: diente de contorno azul profundo (#164BA8) con el escudo de prevención en menta (#1F9D74 sobre claro, #5FD3B8 sobre oscuro) que cubre la caries, con check blanco.
- Palabra: «prev» en Plus Jakarta Sans 800 y «soul» en Quicksand 700, ambas en azul profundo (en blanco sobre fondos oscuros).
- Unión (opción A): la palabra se acerca al escudo hasta casi tocarlo. En el archivo maestro el texto empieza en x = 23,6 de un lienzo de 116,4 × 30.
- Archivos: logo a color, una tinta (azul noche) y blanco; símbolo solo en las mismas tres versiones.
