<?php
/**
 * @copyright Robert Reimi
 * @license GNU General Public License version 2 or later
 */
defined('_JEXEC') or die;
//JHtml::_('behavior.keepalive');
//JHtml::_('behavior.formvalidation');
//JHtml::_('behavior.tooltip');
//$itemid = JRequest::getInt('Itemid');

// para validar si el usuario cumple para ver esta pagina (status = 1 y asistencia = 1)
require_once JPATH_BASE.'/components/com_talleres/helpers/inscripcion.php';

$ins = TalleresInscripcionHelper::getInscripcionesByUser();
if (empty($ins)){
    header("location: http://".$_SERVER['HTTP_HOST']);
}
?>

<link href="<?php echo JURI::base(true) . '/components/com_talleres/assets/jp/jplayer.blue.monday.css'; ?>" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="<?php echo JURI::base(true) . '/components/com_talleres/assets/jp/jquery.jplayer.min.js'; ?>"></script>

<div class="item-page shadow-container refuerzo">

    <h2 class="article-title shadowed-right">
        <a><?php echo JText::_('COM_TALLERES_INSCRIPCION_REFUERZO_TITLE') ?></a>
    </h2>

        <div id="sm2-container">
            <!-- SM2 flash movie goes here -->
        </div>

        <div>
            <p><strong>Bienvenido a la Familia de Banda G&aacute;strica Virtual, ac&aacute; podr&aacute;s escuchar y descargar los audios que te ayudar&aacute;n en tu proceso de p&eacute;rdida de peso.</strong></p><br/>
            <p>El audio de la ma&ntilde;ana podr&aacute;s escucharlo m&aacute;ximo hasta las 3pm. El audio de la noche puedes escucharlo en las noches antes de irte a dormir, te ayudar&aacute; a relajarte y tener un sue&ntilde;o m&aacute;s reparador.</p><br/>
            <p>Los audios disponibles para escuchar desde nuestro sitio son:</p>
        </div>
        <ul class="playlist">
            <li <?php if (!$this->item->valid) :?>class="current"<?php endif; ?>><a onclick="changeAudio('<?php echo JURI::base(true) ?>/audios/dia.mp3')">CD Día Banda Gástrica Virtual</a></li>
            <li><a onclick="changeAudio('<?php echo JURI::base(true) ?>/audios/noche.mp3')">CD Noche Banda Gástrica Virtual</a></li>
            <?/*<li><a onclick="changeAudio('<?php echo JURI::base(true) ?>/audios/dia2.mp3')">CD Día Re-Implante Banda Gástrica Virtual</a></li>
            <li><a onclick="changeAudio('<?php echo JURI::base(true) ?>/audios/noche2.mp3')">CD Noche Re-Implante Banda Gástrica Virtual</a></li>*/?>
            <?php if ($this->item->valid) :?>
                <li class="current">
                    <a onclick="changeAudio('<?php echo JURI::base() ?>/component/talleres/?view=media')">
                        <?php if ($this->item->nro_refuerzo == 'b') :?>
                            Refuerzo
                        <?php elseif ($this->item->nro_refuerzo == '1') :?>
                            Refuerzo I
                        <?php elseif ($this->item->nro_refuerzo == '2') :?>
                            Refuerzo II
                        <?php elseif ($this->item->nro_refuerzo == '3') :?>
                            Refuerzo III
                        <?php elseif ($this->item->nro_refuerzo == '4') :?>
                            Refuerzo IV
                        <?php endif; ?>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
		<?php if (!$this->item->valid) :?>
			<p>
				Adicionalmente a estos audios, usted puede solicitar un refuerzo. Para
				solicitar un refuerzo escríbanos a través de nuestro <a
					href="<?php echo JURI::base(true) ?>/contacto"><b>formulario de
						contacto</b> </a>.
			</p>
			<!--dl>
	        	<dd class="warning message">El enlace del refuerzo ha expirado
	            	<p>Puede solicitar un nuevo enlace a traves de nuestra sección de <a href="<php echo JURI::base(true) ?>/contacto"><b>contacto</b></a></p>
	        	</dd>
	        </dl-->
		<?php endif; ?>
        <br>
        <div id="jquery_jplayer_1" class="jp-jplayer"></div>
        <div id="jp_container_1" class="jp-audio">
            <?php require_once('player.php'); ?>
        </div>
        <br>

        <div>
            <p>Tambi&eacute;n puede descargar los audios y escucharlos en su Ipod, MP3 Player o donde le sea m&aacute;s c&oacute;modo, para descargarlos haga clic en cada audio:</p>
        </div>
        <ul class="playlist">
            <li><a class="exclude" href="<?php echo JURI::base(true) ?>/component/talleres/?view=download&file=dia.mp3">CD Día Banda Gástrica Virtual</a></li>
            <li><a class="exclude" href="<?php echo JURI::base(true) ?>/component/talleres/?view=download&file=noche.mp3">CD Noche Banda Gástrica Virtual</a></li>
            <?/*<li><a class="exclude" href="<?php echo JURI::base(true) ?>/component/talleres/?view=download&file=dia2.mp3">CD Día Re-Implante Banda Gástrica Virtual</a></li>
            <li><a class="exclude" href="<?php echo JURI::base(true) ?>/component/talleres/?view=download&file=noche2.mp3">CD Noche Re-Implante Banda Gástrica Virtual</a></li>*/?>
        </ul>

        <br>

        <div>
            <p>En el siguiente Manual encontrar&aacute;s el ejemplo de men&uacute; y tu hoja de autocontrol para que te guies las primeras semanas de tratamiento.</p>
        </div>
        <ul class="playlist">
            <li><a class="exclude" href="<?php echo JURI::base(true) ?>/component/talleres/?view=download&file=Manual_BGV.pdf">Manual Banda Gástrica Virtual</a></li>
        </ul>
</div>

<script type="text/javascript">

//<![CDATA[
jQuery(document).ready(function(){

    jQuery("#jquery_jplayer_1").jPlayer({
        ready: function () {
            jQuery(this).jPlayer("setMedia", {
                <?php if ($this->item->valid) :?>
                    mp3:"<?php echo JURI::base() ?>/component/talleres/?view=media"
                <?php else:?>
                    mp3:"<?php echo JURI::base(true) ?>/audios/dia.mp3"
                <?php endif; ?>
            });
        },
        swfPath: "components/com_talleres/assets/jp",
        supplied: "mp3",
        wmode: "window",
        smoothPlayBar: true,
        keyEnabled: true
    });

    jQuery(".refuerzo .playlist li").click(function() {
        jQuery(".refuerzo .playlist li").removeClass('current');
        jQuery(this).addClass('current');
    });

});
//]]>

function changeAudio(file){
    jQuery('#jquery_jplayer_1').jPlayer("setMedia", {
        mp3: file
    });
}

</script>