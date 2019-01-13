<?php
/**
 * Fabrik Inline Edit Model
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Component\Fabrik\Site\Model;

// No direct access
use Fabrik\Helpers\Html;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Fabrik\Component\Fabrik\Administrator\Model\FabModel;

defined('_JEXEC') or die('Restricted access');

/**
 * Fabrik Form Model
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       4.0
 */
class FormInlineEditModel extends FabSiteModel
{
	/**
	 * @var FormModel
	 *
	 * @since 4.0
	 */
	protected $formModel;

	/**
	 * Render the inline edit interface
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function render()
	{
		$this->formModel = FabModel::getInstance(FormModel::class);
		$input = $this->app->input;

		// Need to render() with all element ids in case canEditRow plugins etc. use the row data.
		$elids = $input->get('elementid', array(), 'array');
		$input->set('elementid', null);

		$this->formModel->render();

		// Set back to original input so we only show the requested elements
		$input->set('elementid', $elids);
		$this->groups = $this->formModel->getGroupView();

		// Main trigger element's id
		$elementId = $input->getInt('elid');

		$html = $this->inlineEditMarkUp();
		echo implode("\n", $html);

		$srcs = array();
		$repeatCounter = 0;
		$elementIds = (array) $input->get('elementid', array(), 'array');
		$eCounter = 0;
		$onLoad = array();
		$onLoad[] = "Fabrik.inlineedit_$elementId = {'elements': {}};";

		foreach ($elementIds as $id)
		{
			$elementModel = $this->formModel->getElement($id, true);
			$elementModel->getElement();
			$elementModel->setEditable(true);
			$elementModel->formJavascriptClass($srcs);
			$elementJS = $elementModel->elementJavascript($repeatCounter);
			$onLoad[] = 'var o = new ' . $elementJS[0] . '("' . $elementJS[1] . '",' . json_encode($elementJS[2]) . ');';

			if ($eCounter === 0)
			{
				$onLoad[] = "o.select();";
				$onLoad[] = "o.focus();";
				$onLoad[] = "Fabrik.inlineedit_$elementId.token = '" . Session::getFormToken() . "';";
			}

			$eCounter++;
			$onLoad[] = "Fabrik.inlineedit_$elementId.elements[$id] = o";
		}

		$onLoad[] = "Fabrik.fireEvent('fabrik.list.inlineedit.setData');";
		Html::script($srcs, implode("\n", $onLoad));
	}

	/**
	 * Create markup for bootstrap inline editor
	 *
	 * @return  array
	 *
	 * @since 4.0
	 */
	protected function inlineEditMarkUp()
	{
		// @TODO JLayout this
		$input = $this->app->input;
		$html = array();
		$html[] = '<div class="modal">';
		$html[] = ' <div class="modal-header"><h3>' . Text::_('COM_FABRIK_EDIT') . '</h3></div>';
		$html[] = '<div class="modal-body">';
		$html[] = '<form>';

		foreach ($this->groups as $group)
		{
			foreach ($group->elements as $element)
			{
				$html[] = '<div class="control-group fabrikElementContainer ' . $element->id . '">';
				$html[] = '<label>' . $element->label . '</label>';
				$html[] = '<div class="fabrikElement">';
				$html[] = $element->element;
				$html[] = '</div>';
				$html[] = '</div>';
			}
		}

		$html[] = '</form>';
		$html[] = '</div>';
		$thisTmpl = isset($this->tmpl) ? $this->tmpl : '';

		if ($input->getBool('inlinesave') || $input->getBool('inlinecancel'))
		{
			$html[] = '<div class="modal-footer">';

			if ($input->getBool('inlinecancel') == true)
			{
				$html[] = '<a href="#" class="btn inline-cancel">';
				$html[] = Html::image('delete.png', 'list', $thisTmpl, array('alt' => Text::_('COM_FABRIK_CANCEL')));
				$html[] = '<span>' . Text::_('COM_FABRIK_CANCEL') . '</span></a>';
			}

			if ($input->getBool('inlinesave') == true)
			{
				$html[] = '<a href="#" class="btn btn-primary inline-save">';
				$html[] = Html::image('save.png', 'list', $thisTmpl, array('alt' => Text::_('COM_FABRIK_SAVE')));
				$html[] = '<span>' . Text::_('COM_FABRIK_SAVE') . '</span></a>';
			}

			$html[] = '</div>';
		}

		$html[] = '</div>';

		return $html;
	}

	/**
	 * Set form model
	 *
	 * @param   FormModel  $model  Front end form model
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function setFormModel(FormModel $model)
	{
		$this->formModel = $model;
	}

	/**
	 * Inline edit show the edited element
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public function showResults()
	{
		$input = $this->app->input;
		$listModel = $this->formModel->getListModel();
		$listId = $listModel->getId();
		$listModel->clearCalculations();
		$listModel->doCalculations();
		$elementId = $input->getInt('elid');

		if ($elementId === 0)
		{
			return;
		}

		$elementModel = $this->formModel->getElement($elementId, true);

		if (!$elementModel)
		{
			return;
		}

		$rowId = $input->get('rowid');
		$listModel->setId($listId);

		// If the inline edit stored a element join we need to reset back the table
		$listModel->clearTable();
		$listModel->getTable();
		$data = $listModel->getRow($rowId);

		// For a change in the element which means its no longer shown in the list due to pre-filter. We may want to remove the row from the list as well?
		if (!is_object($data))
		{
			$data = new \stdClass;
		}

		$key = $input->get('element');
		$html = '';
		$html .= $elementModel->renderListData($data->$key, $data);
		$listRef = 'list_' . $input->get('listref');
		$doCalcs = "\nFabrik.blocks['" . $listRef . "'].updateCals(" . json_encode($listModel->getCalculations()) . ")";
		$html .= '<script type="text/javascript">';
		$html .= $doCalcs;
		$html .= "</script>\n";

		return $html;
	}
}