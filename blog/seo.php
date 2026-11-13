<?php
/**
 * SEO del Blog Prevsoul: palabra clave, título SEO (≤ 60), meta descripción (≤ 155),
 * preguntas frecuentes y enlaces internos por artículo.
 *
 * @package Prevsoul_Core
 */

defined( 'ABSPATH' ) || exit;

return array(
	'la-caries-no-es-un-hoyo'                  => array(
		'kw'   => 'qué es la caries',
		'st'   => 'Qué es la caries y cómo frenarla a tiempo | Prevsoul',
		'md'   => 'Qué es la caries: no es solo un hoyo, es un proceso que avanza y retrocede. Entiende cómo funciona y cómo frenarla antes de necesitar una tapadura.',
		'faq'  => array(
			array( '¿Qué es la caries dental?', 'Es una enfermedad causada por bacterias que, al consumir azúcar, producen ácidos que desmineralizan el diente. La cavidad es su consecuencia final.' ),
			array( '¿La caries se puede revertir?', 'En sus etapas iniciales, cuando el esmalte aún no se rompe, el proceso puede detenerse e incluso remineralizarse con flúor, higiene y cambios en la dieta.' ),
			array( '¿Cómo saber si tengo caries?', 'Las etapas iniciales no duelen. Un control odontológico regular es la forma más segura de detectarla a tiempo.' ),
		),
		'link' => array( 'la-mancha-blanca', 'la mancha blanca en el esmalte' ),
	),
	'la-mancha-blanca'                          => array(
		'kw'   => 'mancha blanca en los dientes',
		'st'   => 'Mancha blanca en los dientes: la primera señal de caries',
		'md'   => '¿Tienes una mancha blanca en los dientes? Puede ser la primera señal de caries y la única etapa en que se detiene sin tocar el diente. Qué hacer.',
		'faq'  => array(
			array( '¿Por qué salen manchas blancas en los dientes?', 'Una causa frecuente es la pérdida de minerales del esmalte por la acción de las bacterias: es la etapa inicial de la caries. También pueden deberse a otras condiciones, por eso conviene que las evalúe un odontólogo.' ),
			array( '¿La mancha blanca se puede quitar?', 'Cuando es una caries inicial, puede detenerse e incluso remineralizarse con flúor, buena higiene y controles.' ),
			array( '¿Una mancha blanca necesita tapadura?', 'Normalmente no. En esa etapa se maneja sin fresa ni anestesia, controlando su evolución.' ),
		),
		'link' => array( 'la-espiral-de-un-diente', 'la espiral de un diente' ),
	),
	'el-habito-de-dos-minutos'                  => array(
		'kw'   => 'pasta dental con flúor',
		'st'   => 'Pasta dental con flúor: el hábito que más baja la caries',
		'md'   => 'Cepillarse con pasta dental con flúor es la principal razón de la caída mundial de la caries. Cómo hacerlo bien: frecuencia, tiempo y qué pasta elegir.',
		'faq'  => array(
			array( '¿Cuántas veces al día hay que cepillarse los dientes?', 'Se recomienda al menos dos veces al día con pasta con flúor, siendo el cepillado nocturno el más importante.' ),
			array( '¿Hay que enjuagarse después de cepillarse?', 'Se recomienda escupir el exceso sin enjuagar con abundante agua, para que el flúor siga actuando.' ),
			array( '¿Qué pasta dental con flúor debo usar?', 'Depende de tu edad y tu riesgo de caries. Revisa la concentración en el envase y consulta a tu odontólogo.' ),
		),
		'link' => array( 'la-caries-no-es-un-hoyo', 'cómo funciona la caries' ),
	),
	'6-de-cada-10-chilenos-ha-perdido-dientes' => array(
		'kw'   => 'pérdida de dientes en Chile',
		'st'   => 'Pérdida de dientes en Chile: 6 de cada 10 personas',
		'md'   => 'Según la Encuesta Nacional de Salud, el 62,2% de los chilenos de 15 años o más ha perdido dientes. La caries es la principal causa. Cómo evitarlo.',
		'faq'  => array(
			array( '¿Cuál es la principal causa de pérdida de dientes en Chile?', 'Según el Ministerio de Salud, la caries es la principal causa de pérdida de dientes en adultos, seguida por la enfermedad periodontal.' ),
			array( '¿Cuántos chilenos han perdido dientes?', 'La Encuesta Nacional de Salud 2016–2017 registró 62,2% de pérdida parcial y 5% de pérdida total de dientes en personas de 15 años o más.' ),
			array( '¿Cómo evitar perder dientes?', 'Con controles regulares, detección temprana de la caries, cuidado de las encías y continuidad con un mismo odontólogo.' ),
		),
		'link' => array( 'tu-primera-tapadura-no-es-el-final', 'el ciclo de las tapaduras' ),
	),
	'prevencion-primaria-secundaria-terciaria' => array(
		'kw'   => 'niveles de prevención en odontología',
		'st'   => 'Niveles de prevención en odontología, explicados',
		'md'   => 'Prevención primaria, secundaria y terciaria en odontología: qué significa cada nivel en la caries y por qué actuar antes cuesta mucho menos.',
		'faq'  => array(
			array( '¿Qué es la prevención primaria en odontología?', 'Son las acciones que evitan que la enfermedad empiece: higiene, flúor, dieta y sellantes.' ),
			array( '¿Qué es la prevención secundaria?', 'Es detectar la enfermedad temprano y detenerla, como encontrar una caries inicial antes de que forme una cavidad.' ),
			array( '¿Qué es la prevención terciaria?', 'Es limitar el daño cuando la enfermedad ya avanzó: tapaduras, endodoncias y coronas.' ),
		),
		'link' => array( 'la-mancha-blanca', 'detectar la caries en su etapa inicial' ),
	),
	'tu-primera-tapadura-no-es-el-final'       => array(
		'kw'   => 'cuánto dura una tapadura',
		'st'   => '¿Cuánto dura una tapadura? El ciclo que nadie te explica',
		'md'   => 'Una tapadura no dura para siempre: al cambiarla, la cavidad crece y el diente se debilita. Así funciona el ciclo restaurador y cómo frenarlo.',
		'faq'  => array(
			array( '¿Cuánto dura una tapadura?', 'Depende del material, del tamaño, del profesional y sobre todo de los hábitos y el riesgo de cada persona. Ninguna dura para siempre.' ),
			array( '¿Por qué al cambiar una tapadura queda más grande?', 'Al retirar la restauración antigua se pierde algo de diente sano, y la caries del borde obliga a ampliar la cavidad.' ),
			array( '¿Cómo hacer que una tapadura dure más?', 'Con buena higiene, menos azúcar, controles regulares y reparándola en vez de reemplazarla cuando es posible.' ),
		),
		'link' => array( 'reparar-o-reemplazar-una-tapadura', 'reparar en vez de reemplazar' ),
	),
	'la-espiral-de-un-diente'                   => array(
		'kw'   => 'etapas de la caries',
		'st'   => 'Etapas de la caries: de mancha blanca a implante',
		'md'   => 'Las 9 etapas de la caries, desde la mancha blanca hasta el implante: qué pasa en cada una, cuánto cuesta y dónde se puede cortar la espiral.',
		'faq'  => array(
			array( '¿Cuáles son las etapas de la caries?', 'Empieza como una mancha blanca en el esmalte, avanza a la dentina, puede llegar al nervio y, si no se trata, terminar en la pérdida del diente.' ),
			array( '¿Qué es la espiral de muerte del diente?', 'Es el ciclo en que cada tratamiento lleva a otro más grande: tapadura, recambio, endodoncia, corona, extracción e implante.' ),
			array( '¿En qué etapa se puede detener la caries sin tratamiento?', 'En la etapa inicial, la mancha blanca, el proceso puede detenerse sin tocar el diente.' ),
		),
		'link' => array( 'cuanto-cuesta-perder-una-muela-en-chile', 'lo que cuesta cada fase' ),
	),
	'hay-que-cambiar-esa-tapadura'             => array(
		'kw'   => 'cambiar tapadura',
		'st'   => '¿Hay que cambiar tu tapadura? Lo que dicen los estudios',
		'md'   => '¿Te dijeron que hay que cambiar una tapadura? Los estudios muestran grandes diferencias entre dentistas. Qué preguntar antes de decidir.',
		'faq'  => array(
			array( '¿Cuándo hay que cambiar una tapadura?', 'Cuando tiene caries extensa debajo, está fracturada o ya no sella el diente. Un borde manchado no siempre significa que haya que cambiarla.' ),
			array( '¿Por qué dos dentistas opinan distinto sobre una tapadura?', 'Los criterios para decidir si una restauración falló varían mucho entre profesionales, y sin la historia del diente es difícil saber si un defecto es nuevo.' ),
			array( '¿Se puede reparar una tapadura en vez de cambiarla?', 'En muchos casos sí: se arregla solo la parte dañada y se conserva diente sano.' ),
		),
		'link' => array( 'odontologo-de-cabecera', 'tener un odontólogo de cabecera' ),
	),
	'reparar-o-reemplazar-una-tapadura'        => array(
		'kw'   => 'reparar tapadura',
		'st'   => 'Reparar una tapadura o reemplazarla: qué conviene',
		'md'   => 'Reparar una tapadura en vez de reemplazarla conserva diente sano y alarga su vida. Cuándo se puede reparar y cuándo hay que cambiarla.',
		'faq'  => array(
			array( '¿Se puede reparar una tapadura?', 'Sí, cuando el daño es limitado: un borde desgastado, una pequeña fractura o una zona acotada con caries.' ),
			array( '¿Qué ventajas tiene reparar una tapadura?', 'Conserva diente sano, protege el nervio y frena el ciclo de recambios que debilita el diente.' ),
			array( '¿Cuándo no se puede reparar?', 'Cuando la restauración está muy dañada, hay caries extensa debajo o el diente perdió demasiada estructura.' ),
		),
		'link' => array( 'tu-primera-tapadura-no-es-el-final', 'el ciclo restaurador' ),
	),
	'implante-o-salvar-el-diente'              => array(
		'kw'   => 'implante o endodoncia',
		'st'   => 'Implante o endodoncia: qué dice la evidencia',
		'md'   => '¿Implante o endodoncia? Conservar el diente propio suele ser más costo-efectivo. Lo que dicen los estudios y cuándo conviene cada opción.',
		'faq'  => array(
			array( '¿Es mejor un implante o una endodoncia?', 'Bien indicados, ambos tienen tasas de sobrevida parecidas. Conservar el diente con endodoncia suele ser más costo-efectivo cuando es posible.' ),
			array( '¿Cuándo se necesita un implante?', 'Cuando el diente ya no se puede salvar, por ejemplo por una fractura o una destrucción extensa.' ),
			array( '¿Cuánto cuesta un implante dental en Chile?', 'Según el arancel referencial UCO 2026, extracción, instalación, conexión, pilar y corona suman más de 1,7 millones de pesos, sin incluir el implante ni el laboratorio.' ),
		),
		'link' => array( 'cuanto-cuesta-perder-una-muela-en-chile', 'cuánto cuesta perder una muela' ),
	),
	'cuanto-cuesta-perder-una-muela-en-chile'  => array(
		'kw'   => 'cuánto cuesta un implante dental en Chile',
		'st'   => '¿Cuánto cuesta perder una muela en Chile? La cuenta',
		'md'   => 'Cuánto cuesta un implante dental en Chile y todo lo que viene antes: sumamos fase a fase con el arancel UCO 2026. El total supera los 2,9 millones.',
		'faq'  => array(
			array( '¿Cuánto cuesta un implante dental en Chile?', 'Con el arancel referencial UCO 2026, la extracción, el implante, la conexión, el pilar y la corona suman $1.745.250, sin contar el valor del implante, los aditamentos ni el laboratorio.' ),
			array( '¿Cuánto cuesta una endodoncia de molar?', 'El arancel referencial UCO 2026 la valora en $322.200 para un molar inferior vital.' ),
			array( '¿Cuánto se ahorra con prevención?', 'Si la caries se detecta en la etapa de mancha blanca, se evitan todos los tratamientos de la espiral: hasta $2.993.775 en el caso de una muela.' ),
		),
		'link' => array( 'la-espiral-de-un-diente', 'las 9 etapas de la caries' ),
	),
	'odontologo-de-cabecera'                    => array(
		'kw'   => 'odontólogo de cabecera',
		'st'   => 'Odontólogo de cabecera: por qué protege tus dientes',
		'md'   => 'Tener un odontólogo de cabecera frena el ciclo de tratamientos: la evidencia muestra que cambiar de dentista lo acelera. Qué ganas con la continuidad.',
		'faq'  => array(
			array( '¿Qué es un odontólogo de cabecera?', 'Es un profesional que conoce tu historia, sigue tu boca en el tiempo y te acompaña en la prevención y en las decisiones de tratamiento.' ),
			array( '¿Por qué conviene ir siempre al mismo dentista?', 'Porque puede comparar control a control y decidir con más información, evitando tratamientos innecesarios.' ),
			array( '¿Cada cuánto hay que ir a control?', 'Depende de tu riesgo. Tu odontólogo de cabecera define la frecuencia adecuada para ti.' ),
		),
		'link' => array( 'hay-que-cambiar-esa-tapadura', 'por qué dos dentistas pueden opinar distinto' ),
	),
	'tu-diente-tiene-un-precio-de-por-vida'    => array(
		'kw'   => 'costo de tratamientos dentales',
		'st'   => 'Costo de tratamientos dentales: el precio de un diente',
		'md'   => 'El costo real de los tratamientos dentales es el de todos los que vienen después del primero. Cómo calcular el precio de un diente en toda su vida.',
		'faq'  => array(
			array( '¿Por qué los tratamientos dentales se vuelven más caros?', 'Porque cada reintervención suele ser más extensa que la anterior: una tapadura más grande, una endodoncia, una corona.' ),
			array( '¿Cómo se calcula el costo de un diente a lo largo de la vida?', 'Sumando el primer tratamiento, los que vienen después y lo que cuesta reemplazar el diente si se pierde.' ),
			array( '¿Cuál es la forma más barata de cuidar los dientes?', 'Evitar que empiece el ciclo de tratamientos, con prevención y detección temprana.' ),
		),
		'link' => array( 'cuanto-cuesta-perder-una-muela-en-chile', 'el ejemplo en pesos de una muela' ),
	),
	'no-queremos-asustarte'                     => array(
		'kw'   => 'prevención dental',
		'st'   => 'Prevención dental sin miedo: lo que sí puedes hacer',
		'md'   => 'Prevención dental sin asustar: por qué en Prevsoul mostramos los riesgos junto con lo que sí puedes hacer. La caries es prevenible.',
		'faq'  => array(
			array( '¿La caries se puede prevenir?', 'Sí. Con cepillado con flúor, menos azúcar entre comidas y controles regulares, la mayoría de las caries se pueden evitar o detener a tiempo.' ),
			array( '¿Qué incluye la prevención dental?', 'Higiene, flúor, dieta, sellantes cuando corresponde y controles para detectar problemas en sus primeras etapas.' ),
			array( '¿Cómo hablo con un odontólogo sin ir a la consulta?', 'En Prevsoul puedes hablar gratis con un odontólogo en la consulta online.' ),
		),
		'link' => array( 'el-habito-de-dos-minutos', 'el hábito que más baja la caries' ),
	),
);
