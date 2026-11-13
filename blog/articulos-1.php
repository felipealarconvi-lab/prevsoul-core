<?php
/**
 * Blog Prevsoul · artículos 1 a 5 (etapa: entender el problema).
 * Base: estudio «Método Mochi · La historia de un diente (Prevsoul)», noviembre 2026.
 *
 * @package Prevsoul_Core
 */

defined( 'ABSPATH' ) || exit;

$f_pitts  = '<a href="https://www.nature.com/articles/nrdp201730" target="_blank" rel="noopener">Pitts NB et al. Dental caries. Nature Reviews Disease Primers, 2017</a>';
$f_minsal = '<a href="https://diprece.minsal.cl/garantias-explicitas-en-salud-auge-o-ges/guias-de-practica-clinica/salud-oral-integral-para-adultos-de-60-anos/descripcion-y-epidemiologia/" target="_blank" rel="noopener">MINSAL. Guía GES Salud Oral Integral 60 años: descripción y epidemiología (ENS 2016–2017)</a>';
$f_leav   = '<a href="https://transparente.med.ucc.edu.ar/wp-content/uploads/2023/03/Ppios-de-la-prevenci%C3%B3n-y-promoci%C3%B3n-en-Odontolog%C3%ADa.-Cuenca-Sala-2005.pdf" target="_blank" rel="noopener">Cuenca Sala E. Principios de la prevención y promoción en Odontología (resume a Leavell y Clark), 2005</a>';
$f_buon   = '<a href="https://operative-dentistry.kglmeridian.com/view/journals/odnt/49/6/article-p655.xml" target="_blank" rel="noopener">Longevity of dental restorations or longevity of teeth: what matters? Operative Dentistry, 2024</a>';

return array(
	array(
		'slug'    => 'la-caries-no-es-un-hoyo',
		'title'   => 'La caries no es un hoyo: es un proceso que puedes frenar',
		'etapa'   => 'Entender',
		'min'     => 4,
		'excerpt' => 'La cavidad que ves es el final de una historia que empezó mucho antes. Entender la caries como proceso cambia lo que puedes hacer.',
		'html'    => '<p>Solemos pensar en la caries como un hoyo en el diente: algo que aparece de un día para otro y que se «arregla» con una tapadura. La ciencia actual la describe de otra manera. La cavidad es la consecuencia; la enfermedad es un proceso que ocurre todos los días en tu boca.</p>
<h2>Qué pasa realmente en tu diente</h2>
<p>Sobre los dientes vive una capa de bacterias llamada biopelícula. Cada vez que comemos azúcar, esas bacterias producen ácidos que le quitan minerales al esmalte. Después, la saliva y el flúor devuelven parte de esos minerales. La caries aparece cuando, durante mucho tiempo, se pierde más mineral del que se recupera.</p>
<p>Por eso los especialistas la definen como una enfermedad dinámica, impulsada por el azúcar y mediada por la biopelícula, que avanza y retrocede según el equilibrio entre factores que la empujan y factores que la frenan.</p>
<h2>Lo que la empuja y lo que la frena</h2>
<ul>
<li><strong>La empujan:</strong> el azúcar frecuente, la higiene insuficiente, poco flúor y la boca seca.</li>
<li><strong>La frenan:</strong> una dieta con menos azúcar entre comidas, el cepillado con pasta con flúor y una saliva sana.</li>
</ul>
<h2>Por qué esto importa</h2>
<p>Si la caries es un proceso, se puede detener antes de que haya un hoyo. Las primeras etapas, cuando el esmalte apenas empieza a perder mineral, pueden detenerse e incluso remineralizarse. Cuando la lesión ya rompió el diente, en cambio, solo queda reparar.</p>
<p>Ese es el centro de la prevención: actuar sobre el proceso, no solo sobre el hoyo. Y para eso hay que mirar la boca antes de que duela.</p>
<h2>En Prevsoul</h2>
<p>Tu odontólogo de cabecera revisa tu boca con regularidad para detectar el proceso en sus primeras etapas y ajustar contigo lo que lo empuja: dieta, higiene y flúor.</p>',
		'fuentes' => array( $f_pitts ),
	),
	array(
		'slug'    => 'la-mancha-blanca',
		'title'   => 'La mancha blanca: la señal que avisa antes de la caries',
		'etapa'   => 'Entender',
		'min'     => 4,
		'excerpt' => 'Antes de la cavidad hay una mancha blanca y opaca en el esmalte. Es la etapa en que la caries todavía se puede detener sin tocar el diente.',
		'html'    => '<p>Antes de que una caries se convierta en cavidad, casi siempre deja una huella: una mancha blanca, opaca, como de tiza, en la superficie del esmalte. Pocas personas la notan, pero es la señal más valiosa de toda la historia de un diente.</p>
<h2>Qué es una mancha blanca</h2>
<p>Es una zona donde el esmalte perdió minerales por debajo de la superficie, aunque por fuera siga entero. Los sistemas actuales de clasificación de caries, como ICDAS, la ubican en las etapas iniciales: el diente ya muestra el proceso, pero todavía no tiene un hoyo.</p>
<h2>Por qué es una buena noticia encontrarla</h2>
<p>Porque en esa etapa la lesión puede detenerse e incluso remineralizarse. No hace falta anestesia, ni fresa, ni tapadura. Basta con cambiar el equilibrio a favor del diente:</p>
<ul>
<li>Cepillado con pasta con flúor, todos los días.</li>
<li>Menos azúcar entre comidas.</li>
<li>Controles para ver si la mancha se detiene o avanza.</li>
<li>Flúor profesional o sellantes cuando el odontólogo lo indique.</li>
</ul>
<h2>Lo que pasa si nadie la ve</h2>
<p>Si el proceso sigue, el esmalte termina rompiéndose y la caries llega a la dentina. Desde ahí, la única opción es retirar tejido y poner una restauración. Es el primer paso de un camino que suele terminar en tratamientos cada vez más grandes. En otro artículo te contamos cómo sigue: la espiral de un diente.</p>
<h2>En Prevsoul</h2>
<p>Los controles de tu plan existen justo para esto: encontrar la mancha blanca a tiempo. Es la fase 0 de la espiral, la única en que cortar el problema no cuesta nada extra.</p>',
		'fuentes' => array( $f_pitts ),
	),
	array(
		'slug'    => 'el-habito-de-dos-minutos',
		'title'   => 'El hábito de dos minutos que más ha bajado la caries en el mundo',
		'etapa'   => 'Entender',
		'min'     => 3,
		'excerpt' => 'No es una tecnología nueva ni un tratamiento caro: cepillarse con pasta con flúor todos los días es la principal razón de la caída de la caries en las últimas décadas.',
		'html'    => '<p>En las últimas décadas la caries bajó en muchos países. La revisión internacional de referencia sobre la enfermedad señala una razón principal: el uso diario de pasta de dientes con flúor. Un hábito simple, barato y al alcance de casi todos.</p>
<h2>Cómo funciona el flúor</h2>
<p>La caries avanza cuando el esmalte pierde más minerales de los que recupera. El flúor inclina esa balanza a favor del diente: ayuda a que el esmalte se remineralice y lo hace más resistente a los ácidos de las bacterias.</p>
<h2>Cómo sacarle el máximo</h2>
<ul>
<li><strong>Dos veces al día</strong>, siendo la de la noche la más importante.</li>
<li><strong>Dos minutos</strong>, pasando por todas las caras de los dientes.</li>
<li><strong>Pasta con flúor</strong>: revisa la concentración en el envase y pregunta a tu odontólogo cuál te conviene según tu edad y tu riesgo.</li>
<li><strong>Escupe sin enjuagar con abundante agua</strong>, para que el flúor siga actuando.</li>
</ul>
<h2>Lo que el cepillo no alcanza</h2>
<p>El cepillado es la base, pero no reemplaza la mirada de un profesional. Entre los dientes, bajo las encías o en una mancha blanca que recién empieza, se necesita un control para saber si el proceso está detenido o avanzando.</p>
<h2>En Prevsoul</h2>
<p>Tu odontólogo de cabecera te ayuda a ajustar este hábito a tu caso y revisa en cada control si está funcionando.</p>',
		'fuentes' => array( $f_pitts ),
	),
	array(
		'slug'    => '6-de-cada-10-chilenos-ha-perdido-dientes',
		'title'   => '6 de cada 10 chilenos ha perdido dientes: cómo no ser parte de la cifra',
		'etapa'   => 'Entender',
		'min'     => 4,
		'excerpt' => 'Según la Encuesta Nacional de Salud, el 62,2% de las personas de 15 años o más tiene pérdida parcial de dientes. La caries es la principal causa.',
		'html'    => '<p>La Encuesta Nacional de Salud 2016–2017 muestra un dato que pocas veces se comenta: el 62,2% de las personas de 15 años o más en Chile ha perdido parte de sus dientes, y un 5% los perdió todos. Entre los 65 y más años, la pérdida parcial llega al 79,8% y la total al 17,6%.</p>
<h2>Por qué se pierden los dientes</h2>
<p>Según el Ministerio de Salud, las dos principales causas son la caries y la enfermedad periodontal, y en los adultos la caries es la primera. La evidencia internacional coincide: la caries y sus consecuencias son la razón principal por la que se pierden dientes.</p>
<h2>No es de un día para otro</h2>
<p>Un diente no se pierde de golpe. Suele recorrer un camino de años: una mancha blanca que nadie vio, una tapadura, un recambio más grande, una endodoncia, una corona y, al final, una extracción. Cada paso es más complejo que el anterior.</p>
<h2>Lo que sí puedes hacer</h2>
<ul>
<li>Controles regulares, aunque no te duela nada.</li>
<li>Detectar y detener la caries en su etapa inicial.</li>
<li>Cuidar las encías tanto como los dientes.</li>
<li>Mantener a un mismo odontólogo que conozca tu historia.</li>
</ul>
<h2>En Prevsoul</h2>
<p>Nuestros planes están diseñados para cortar ese camino al comienzo, con un odontólogo de cabecera que te acompaña año a año.</p>',
		'fuentes' => array( $f_minsal, $f_buon ),
	),
	array(
		'slug'    => 'prevencion-primaria-secundaria-terciaria',
		'title'   => 'Prevención primaria, secundaria y terciaria: qué significan para tu boca',
		'etapa'   => 'Comparar',
		'min'     => 4,
		'excerpt' => 'Un modelo clásico de la salud pública explica en qué momento conviene actuar. Aplicado a la caries, muestra por qué lo más barato es llegar antes.',
		'html'    => '<p>En los años cincuenta y sesenta, los salubristas Leavell y Clark propusieron una idea que sigue vigente: toda enfermedad sigue un curso natural si nadie interviene, y en cada etapa hay una forma distinta de prevenir. Aplicado a tu boca, ese modelo explica por qué el momento importa tanto.</p>
<h2>Los tres niveles</h2>
<ul>
<li><strong>Prevención primaria:</strong> evitar que la enfermedad empiece. En la caries: higiene, flúor, dieta y sellantes.</li>
<li><strong>Prevención secundaria:</strong> detectarla temprano y detenerla. En la caries: encontrar la mancha blanca o la lesión inicial y tratarla sin perder diente.</li>
<li><strong>Prevención terciaria:</strong> limitar el daño cuando ya existe. En la caries: tapaduras, endodoncias y coronas. Al final viene la rehabilitación: prótesis o implantes.</li>
</ul>
<h2>Por qué el nivel define el costo</h2>
<p>Mientras más arriba en la lista se actúa, menos tejido se pierde y menos cuesta. En el nivel primario y en la detección temprana, lo que se invierte son controles y hábitos. En el nivel terciario, cada tratamiento abre la puerta al siguiente, más grande y más caro.</p>
<h2>Un matiz moderno</h2>
<p>El modelo original es lineal, pero hoy sabemos que la caries avanza y retrocede. Eso hace aún más valiosa la prevención secundaria: hay una ventana en que la lesión todavía se puede detener.</p>
<h2>En Prevsoul</h2>
<p>Nuestros planes trabajan en los dos primeros niveles: evitar que la caries empiece y encontrarla cuando aún se puede detener.</p>',
		'fuentes' => array( $f_leav, $f_pitts ),
	),
);
