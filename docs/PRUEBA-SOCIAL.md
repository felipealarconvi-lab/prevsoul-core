# Prueba social (aprobada el 17-11-2026, se activa al final de la configuración)

Decisión del usuario: todo en **automático**, con datos reales. Se instala cuando el resto del sistema esté configurado.

## Menú
- Prevsoul → Prueba social: tabla con todos los productos + caja en la ficha de cada producto.
- Se muestra en: tarjetas cuadradas (inicio y catálogo) y ficha del producto.

## Vendidos
- Cuenta ventas reales (pedidos completados) y redondea hacia abajo a tramos: +5, +10, +25, +50, +100, +250, +500, +1000.
- Con menos de 5 ventas no se muestra.
- Texto: «+25 contratados» (planes) / «+25 vendidos» (packs y atenciones).
- Opción Ocultar por producto. Sin modo manual hacia arriba (nunca más que lo real).

## Dientrellas (valoración)
- 5 dientes dorados que se llenan por partes según el promedio de reseñas reales; texto «4,8 · 12 reseñas».
- Sin reseñas no se muestran.
- Diente: path `M6 3.2c1.8-.9 3.4.2 6 .2s4.2-1.1 6-.2c2.2 1.1 2.3 4.6 1.4 7.6-.9 3-1 8.3-2.9 10.1-1.2 1.1-2.1-.3-2.4-2.3-.3-2.1-.8-4.6-2.1-4.6s-1.8 2.5-2.1 4.6c-.3 2-1.2 3.4-2.4 2.3C5.6 19.1 5.5 13.8 4.6 10.8 3.7 7.8 3.8 4.3 6 3.2z`, viewBox `-2 -4 32 30`.
- Dorado: degradado #F7D35C → #D49A1F; vacío #E8E2D2. Destellos solo en dientes llenos.
- Destello: estrella de 4 puntas, relleno #FFD54A / #F2C94C, borde #B8800F 0,35, centro blanco; titila (escala .7→1.1, giro 20°, 2,2 s, desfase por diente).
- **B1 en tarjetas** (4 destellos: (3.5,3.5,r3.4,c) (25.5,3,r3.8,c) (27,17.5,r2.6) (1.5,15.5,r2.2)).
- **B2 en la ficha** (5 destellos: (3,3,r4.2,c) (26,2.5,r4.8,c) (28,17.5,r3.2,c) (0.5,15.5,r2.8,c) (14,-2.5,r2.2)). c = centro blanco.
- Respetar prefers-reduced-motion (sin animación).

## Sellos
Ninguno · Más contratado · Nuevo · Favorito de las familias · Recomendado. Píldora azul profundo #164BA8, texto blanco.

## Testimonio destacado
Elegir una reseña real que el paciente autorizó publicar (casilla de autorización en el formulario de reseña). Cita en cursiva con borde izquierdo dorado #E3B23C, nombre y comuna.
