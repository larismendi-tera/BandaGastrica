<?php
defined('_JEXEC') or die;
JHtml::addIncludePath(JPATH_COMPONENT.'/helpers/html');
JHtml::_('behavior.tooltip');
JHtml::_('script','system/multiselect.js', false, true);
$user = JFactory::getUser();
$userId = $user->get('id');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));
?>
<div class="talleres-manager">
    <form action="<?php echo JRoute::_('index.php?option=com_talleres&view=inscripciones'); ?>"
          method="post" name="adminForm" id="adminForm">
        <fieldset id="filter-bar">
            <div class="filter-search fltlft">
                <label class="filter-search-lbl" for="filter_search"><?php echo JText::_('JSEARCH_FILTER_LABEL'); ?> </label>
                <input type="text" name="filter_search" id="filter_search" value="<?php echo $this->escape($this->state->get('filter.search')); ?>" title="<?php echo JText::_('COM_JOOMPROSUBS_SEARCH_IN_TITLE'); ?>" />
                <button type="submit"><?php echo JText::_('JSEARCH_FILTER_SUBMIT');?></button>

                <button type="button" onclick="document.id('filter_search').value='';this.form.submit();">
                    <?php echo JText::_('JSEARCH_FILTER_CLEAR'); ?>
                </button>
            </div>
            <div class="filter-select fltrt">

<!--                <select name="filter_published" class="inputbox" onchange="this.form.submit()">-->
<!--                    <option value="">--><?php //echo JText::_('JOPTION_SELECT_PUBLISHED');?><!--</option>-->
<!--                    --><?php //echo JHtml::_('select.options', JHtml::_('jgrid.publishedOptions'), 'value', 'text', $this->state->get('filter.state'), true);?>
<!--                </select>-->

                <select name="filter_status" class="inputbox" onchange="this.form.submit()">
                    <option value=""><?php echo JText::_('COM_TALLERES_FILTER_INSCRIPCION_STATUS');?></option>
                    <?php echo JHtml::_('select.options', TalleresHelper::getInscripcionStatuses(), 'value', 'text', $this->state->get('filter.status'), true);?>
                </select>

                <select name="filter_taller_id" class="inputbox" onchange="this.form.submit()">
                    <option value=""><?php echo JText::_('COM_TALLERES_FILTER_TALLER_ID');?></option>
                    <?php echo JHtml::_('select.options', $this->taller_list, 'value', 'text', $this->state->get('filter.taller_id'));?>
                </select>

<!--                <select name="filter_access" class="inputbox" onchange="this.form.submit()">-->
<!--                    <option value="">--><?php //echo JText::_('JOPTION_SELECT_ACCESS');?><!--</option>-->
<!--                    --><?php //echo JHtml::_('select.options', JHtml::_('access.assetgroups'), 'value', 'text', $this->state->get('filter.access'));?>
<!--                </select>-->

            </div>
        </fieldset>
        <div class="clr"> </div>

        <table class="adminlist">
            <thead>
            <tr>
                <th style="width: 1%;">
                    <input type="checkbox" name="checkall-toggle" value="" onclick="checkAll(this)" />
                </th>
                <th style="width: 10%;" class="title">
                    <?php echo JHtml::_('grid.sort', 'COM_INSCRIPCION_FIELD_NOMBRE', 'a.nombre', $listDirn, $listOrder); ?>
                </th>
                <th style="width: 5%;">
                    <?php echo JHtml::_('grid.sort','COM_INSCRIPCION_FIELD_CI', 'a.ci', $listDirn, $listOrder); ?>
                </th>
                <th style="width: 10%;" class="title">
                    <?php echo JHtml::_('grid.sort', 'COM_INSCRIPCION_FIELD_EMAIL', 'a.correo', $listDirn, $listOrder); ?>
                </th>

                <th style="width: 6%;">
                    <?php echo JHtml::_('grid.sort', 'COM_INSCRIPCION_FIELD_TELEFONO','a.telefono', $listDirn, $listOrder); ?>
                </th>

                <th style="width: 8%;">
                    <?php echo JHtml::_('grid.sort','COM_INSCRIPCION_FIELD_CIUDAD', 'a.ciudad', $listDirn, $listOrder); ?>
                </th>
                <th style="width: 7%;">
                    <?php echo JHtml::_('grid.sort','COM_INSCRIPCION_FIELD_FORMA_PAGO','a.forma_pago', $listDirn, $listOrder); ?>
                </th>
                <th style="width: 7%;">
                    <?php echo JHtml::_('grid.sort','COM_INSCRIPCION_FIELD_NUMERO_TRANSACCION','a.num_transaccion', $listDirn, $listOrder); ?>
                </th>
                <th style="width: 5%;">
                    <?php echo JHtml::_('grid.sort', 'COM_INSCRIPCION_FIELD_MONTO', 'a.monto', $listDirn, $listOrder); ?>
                </th>
                <th style="width: 10%;">
                    <?php echo JHtml::_('grid.sort', 'COM_TALLERES_FIELD_TITLE', 'a.taller_id', $listDirn, $listOrder); ?>
                </th>
                <th style="width: 5%;">
                    <?php echo JHtml::_('grid.sort', 'COM_INSCRIPCION_FIELD_CONFIRMADO', 'a.status', $listDirn, $listOrder); ?>
                </th>
                <th style="width: 5%;">
                    <?php echo JHtml::_('grid.sort', 'COM_ASISTENCIA_FIELD_CONFIRMADA', 'a.asistencia', $listDirn, $listOrder); ?>
                </th>
                <th style="width: 5%;" class="nowrap">
                    <?php echo JText::_('COM_TALLERES_FIELD_OPTIONS'); ?>
                </th>
            </tr>
            </thead>

            <tfoot>
            <tr>
                <td colspan="13">
                    <?php echo $this->pagination->getListFooter(); ?>
                </td>
            </tr>
            </tfoot>
            <tbody>
            <?php foreach ($this->items as $i => $item) :
                $ordering = ($listOrder == 'a.ordering');
                //$item->cat_link = JRoute::_('index.php?option=com_categories&extension=com_talleres&task=edit&type=other&cid[]='. $item->catid);
                $canCreate = $user->authorise('core.create','com_talleres');
                $canEdit = $user->authorise('core.edit','com_talleres');
                $canCheckin = $user->authorise('core.manage', 'com_checkin') || $item->checked_out==$user->get('id') || $item->checked_out==0;
                $canChange = $user->authorise('core.edit.state','com_talleres') && $canCheckin;
                ?>
            <tr class="row<?php echo $i % 2; ?>">
                <td class="center">
                    <?php echo JHtml::_('grid.id', $i, $item->id); ?>
                </td>
                <td>
<!--                    --><?php //if ($item->checked_out) : ?>
<!--                    --><?php //echo JHtml::_('jgrid.checkedout', $i,$item->editor, $item->checked_out_time, 'talleres.', $canCheckin); ?>
<!--                    --><?php //endif; ?>
                    <?php if ($canEdit) : ?>
                    <a href="<?php echo JRoute::_('index.php?option=com_talleres&task=inscripcion.edit&id='.(int) $item->id); ?>">
                        <?php echo $this->escape($item->nombre); ?></a>
                    <?php else : ?>
                    <?php echo $this->escape($item->nombre); ?>
                    <?php endif; ?>
                </td>
                <td class="center">
                    <?php echo $this->escape($item->ci); ?>
                </td>
                <td class="center">
                    <?php echo $this->escape($item->correo); ?>
                </td>

                <td class="center">
                    <?php echo $this->escape($item->telefono); ?>
                </td>

                <td class="center">
                    <?php echo $this->escape($item->ciudad); ?>
                </td>
                <td class="center">
                    <?php echo TalleresHelper::getMetodoPago($item->forma_pago); ?>
                </td>
                <td class="center">
                    <?php echo $this->escape($item->num_transaccion);?>
                </td>
                <td class="center">
                    <?php echo $this->escape($item->monto);?>
                </td>
                <td class="center">
                    <?php echo $this->escape($item->taller_nombre);  ?>
                </td>
                <td class="center">
                    <?php if ($item->status == 0): ?>
                    <a title="Publish Item" onclick="return listItemTask('cb<?php echo $i;?>','inscripciones.inscripcion_confirm')" href="javascript:void(0);" class="jgrid"><span class="state unpublish"><span class="text">Unpublished</span></span></a>
                    <?php else : ?>
                    <a title="Publish Item" onclick="return listItemTask('cb<?php echo $i;?>','inscripciones.inscripcion_pending')" href="javascript:void(0);" class="jgrid"><span class="state publish"><span class="text">Unpublished</span></span></a>
                    <?php endif ?>
                </td>
                <td class="center">
                    <?php if ($item->asistencia == 0): ?>
                    <a title="Publish Item" onclick="return listItemTask('cb<?php echo $i;?>','inscripciones.asistencia_confirm')" href="javascript:void(0);" class="jgrid"><span class="state unpublish"><span class="text">Unpublished</span></span></a>
                    <?php else : ?>
                    <a title="Publish Item" onclick="return listItemTask('cb<?php echo $i;?>','inscripciones.asistencia_pending')" href="javascript:void(0);" class="jgrid"><span class="state publish"><span class="text">Unpublished</span></span></a>
                    <?php endif ?>
                </td>
                <td>
                    <a href="index.php?option=com_talleres&view=seguimientos&inscripcion_id=<?php echo $item->id ?>"><?php echo JText::_('COM_TALLERES_FIELD_SHOW_SEGUIMIENTOS') ?></a>
                </td>
<!--                <td class="center">-->
<!--                    --><?php //echo (int) $item->id; ?>
<!--                </td>-->
            </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div>
            <input type="hidden" name="task" value="" />
            <input type="hidden" name="boxchecked" value="0" />
            <input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
            <input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
            <?php echo JHtml::_('form.token'); ?>
        </div>
    </form>
</div>
