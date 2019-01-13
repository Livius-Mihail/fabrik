<?php
/**
 * Fabrik Open Archive Model
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Component\Fabrik\Site\Model;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Fabrik\Component\Fabrik\Administrator\Model\FabModel;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

/**
 * Fabrik Open Archive Model
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       4.0
 */
class OaiModel extends FabSiteModel
{
	/**
	 * Delimiter used in unique resource identifier
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	private $delimiter = ':';

	/**
	 * @var \DOMDocument
	 *
	 * @since 4.0
	 */
	public $dom;

	/**
	 * @var ListModel
	 *
	 * @since 4.0
	 */
	private $listModel;

	/**
	 * Single getRecord record
	 *
	 * @var array
	 *
	 * @since 4.0
	 */
	private $record = array();

	/**
	 * OaModel constructor.
	 *
	 * @param array $config
	 *
	 * @throws \Exception
	 *
	 * @since 4.0
	 */
	public function __construct(array $config)
	{
		$this->dom                     = new \DOMDocument('1.0', 'utf-8');
		$this->dom->preserveWhiteSpace = false;
		$this->dom->formatOutput       = true;
		parent::__construct($config);
	}

	/**
	 * Set the list model
	 *
	 * @param ListModel $listModel
	 *
	 * @since 4.0
	 */
	public function setListModel(ListModel $listModel)
	{
		$this->listModel = $listModel;
	}

	/**
	 * Build the document header section
	 *
	 * @return \DOMNode
	 *
	 * @since 4.0
	 */
	public function root()
	{
		$oai = $this->dom->createElement('OAI-PMH');

		$oai->setAttributeNS(
			'http://www.w3.org/2000/xmlns/',
			'xmlns',
			'http://www.openarchives.org/OAI/2.0/'
		);

		$oai->setAttributeNS(
			'http://www.w3.org/2000/xmlns/',
			'xmlns:dcterms',
			'http://purl.org/dc/terms/'
		);

		$oai->setAttributeNS('http://www.w3.org/2000/xmlns/',
			'xmlns:dcmitype',
			'http://purl.org/dc/dcmitype/');

		$oai->setAttributeNS(
			'http://www.w3.org/2000/xmlns/',
			'xmlns:xsi',
			'http://www.w3.org/2001/XMLSchema-instance'
		);

		$oai->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'schemaLocation', 'http://www.openarchives.org/OAI/2.0/ http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd');

		return $oai;
	}

	/**
	 * Map a set of node attributes to a node.
	 *
	 * @param \DOMNode $node
	 * @param array    $attributes
	 *
	 * @since 4.0
	 */
	public function nodeAttributes($node, $attributes)
	{
		foreach ($attributes as $key => $value)
		{
			$attribute        = $this->dom->createAttribute($key);
			$attribute->value = $value;
			$node->appendChild($attribute);
		}
	}

	/**
	 * Create response date
	 *
	 * @return \DOMElement
	 *
	 * @since 4.0
	 */
	public function responseDate()
	{
		return $this->dom->createElement('responseDate', date('c'));
	}

	/**
	 * Get OAI Base URL
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	private function baseUrl()
	{
		$uri = Uri::getInstance()->toString(array('scheme', 'host', 'path')) . '?option=com_fabrik&controller=oai&format=oai';

		return htmlspecialchars($uri, ENT_COMPAT, 'UTF-8');
	}

	/**
	 * Create the request DOM element
	 *
	 * @return \DOMElement
	 *
	 * @since 4.0
	 */
	public function requestElement()
	{
		$node = $this->dom->createElement('request', $this->baseUrl());

		return $node;
	}

	/**
	 * @param \DOMElement $node
	 *
	 * @since 4.0
	 */
	private function addNameSpace(&$node)
	{
		$node->setAttributeNS(
			'http://www.w3.org/2000/xmlns/',
			'xmlns',
			'http://www.openarchives.org/OAI/2.0/'
		);

		$node->setAttributeNS(
			'http://www.w3.org/2000/xmlns/',
			'xmlns:xsi',
			'http://www.w3.org/2001/XMLSchema-instance'
		);

		$node->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance',
			'schemaLocation', 'http://www.openarchives.org/OAI/2.0/ http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd');

	}

	public function getOAIElements()
	{
		$params = $this->listModel->getParams();

		return json_decode($params->get('open_archive_elements'));
	}

	/**
	 * @param \DOMElement $node
	 *
	 * @since 4.0
	 */
	public function appendChild($node)
	{
		$this->dom->appendChild($node);
	}

	/**
	 * @param      $name
	 * @param null $value
	 *
	 * @return \DOMElement
	 *
	 * @since 4.0
	 */
	public function createElement($name, $value = null)
	{
		return $this->dom->createElement($name, $value);
	}

	/**
	 * Generate the repository identifier
	 *
	 * @return \DOMElement
	 *
	 * @since 4.0
	 */
	private function repositoryIdentifierElement()
	{
		$repositoryIdentifier = $this->repositoryIdentifier();

		return $this->dom->createElement('repositoryIdentifier', $repositoryIdentifier);
	}

	/**
	 * @return string
	 *
	 * @since 4.0
	 */
	public function repositoryIdentifier()
	{
		$config = ComponentHelper::getParams('com_fabrik');

		return $config->get('oai_repository_identifier',
			Uri::getInstance()->toString(array('host')));
	}

	/**
	 * Generate the Error XML response
	 *
	 * @param array $err error and code values.
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public function generateError($err)
	{
		$root = $this->root();
		$root->appendChild($this->responseDate());
		$root->appendChild($this->requestElement());
		$error = $this->dom->createElement('error', $err['msg']);
		$this->nodeAttributes($error, array('code' => $err['code']));
		$root->appendChild($error);
		$this->dom->appendChild($root);

		return $this->dom->saveXML();
	}

	/**
	 * Check a record identifier
	 *
	 *  oai:site.com:{listid}/{recordid}
	 *
	 * @param string $identifier E.g. 'oai:fabrik.rocks:17/1'
	 *
	 * @return bool
	 *
	 * @since 4.0
	 */
	public function checkIdentifier($identifier)
	{
		$record = $this->getListRowIdFromIdentifier($identifier);

		if (!$record)
		{
			return false;
		}

		$listId = $record[0];
		$rowId  = $record[1];

		/** @var ListModel $listModel */
		$listModel = FabModel::getInstance(ListModel::class);
		$listModel->setId($listId);
		$formModel = $listModel->getFormModel();
		$formModel->setRowId($rowId);
		$row = $formModel->getData();

		if (!array_key_exists('__pk_val', $row))
		{
			return false;
		}

		return true;
	}

	/**
	 * Get the list and row id array data from the record identifier string
	 *
	 * @param   string $identifier
	 *
	 * @return array|bool
	 *
	 * @since 4.0
	 */
	public function getListRowIdFromIdentifier($identifier)
	{
		$prefix = 'oai:' . $this->repositoryIdentifier() . ':';

		if (!strstr($identifier, $prefix))
		{
			return false;
		}

		$identifier = str_replace($prefix, '', $identifier);

		return explode('/', $identifier);
	}

	/**
	 * // <resumptionToken completeListSize="13239" cursor="0">T3hz</resumptionToken>
	 *
	 * @since 4.0
	 */
	public function resumptionToken($total, $filter)
	{
		$limitStart  = $this->listModel->limitStart;
		$limitLength = $this->listModel->limitLength;
		$listId      = $this->listModel->getId();
		$value       = 'limitstart' . $listId . '=' . ($limitStart + $limitLength);
		$dateEl      = $this->dateElName();

		$range = $filter[$dateEl]['value'];
		$value .= '&from=' . $range[0] . '&until=' . $range[1];
		$token = $this->dom->createElement('resumptionToken', urlencode($value));
		$this->nodeAttributes($token, array('completeListSize' => $total, 'cursor' => $limitStart));

		return $token;
	}

	/**
	 * Get the (raw) full element name for the date field.
	 *
	 * @param boolean $raw Append _raw to the element name
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public function dateElName($raw = true)
	{
		$formModel = $this->listModel->getFormModel();
		$suffix    = $raw ? '_raw' : '';
		$params    = $this->listModel->getParams();

		return $formModel->getElement($params->get('open_archive_timestamp'), true)
				->getFullName(true, false) . $suffix;
	}

	public function supportMetaDataPrefix($prefix)
	{
		return $prefix === 'oai_dc';
	}

	/**
	 * Create the Identity XML response
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public function identity()
	{
		/** @var CMSApplication $app */
		$app    = Factory::getApplication();
		$config = $app->getConfig();

		$root = $this->root();
		$root->appendChild($this->responseDate());
		//$root->appendChild($this->requestElement());
		$request = $this->requestElement();
		$this->nodeAttributes($request, array('verb' => 'Identify'));
		$root->appendChild($request);
		$identify = $this->dom->createElement('Identify');
		$identify->appendChild($this->dom->createElement('repositoryName', $config->get('sitename')));
		$identify->appendChild($this->dom->createElement('baseURL', $this->baseUrl()));
		$identify->appendChild($this->dom->createElement('protocolVersion', '2.0'));
		$identify->appendChild($this->dom->createElement('adminEmail', $config->get('mailfrom')));
		$identify->appendChild($this->dom->createElement('earliestDatestamp', '1970-01-01T00:00:00Z'));
		$identify->appendChild($this->dom->createElement('deletedRecord', 'no'));
		$identify->appendChild($this->dom->createElement('granularity', 'YYYY-MM-DD'));

		$desc       = $this->dom->createElement('description');
		$identifier = $this->dom->createElement('oai-identifier');
		//$this->addNameSpace($identifier);

		$identifier->setAttributeNS(
			'http://www.w3.org/2000/xmlns/',
			'xmlns',
			'http://www.openarchives.org/OAI/2.0/oai-identifier'
		);

		$identifier->setAttributeNS(
			'http://www.w3.org/2000/xmlns/',
			'xmlns:xsi',
			'http://www.w3.org/2001/XMLSchema-instance'
		);

		$identifier->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance',
			'schemaLocation', 'http://www.openarchives.org/OAI/2.0/oai-identifier http://www.openarchives.org/OAI/2.0/oai-identifier.xsd');

		$identifier->appendChild($this->dom->createElement('scheme', 'oai'));

		$identifier->appendChild($this->repositoryIdentifierElement());
		$identifier->appendChild($this->dom->createElement('delimiter', $this->delimiter));
		$sample = 'oai:' . $this->repositoryIdentifier() . ':4/255';
		$identifier->appendChild($this->dom->createElement('sampleIdentifier', $sample));
		$desc->appendChild($identifier);
		$identify->appendChild($desc);
		$root->appendChild($identify);
		$this->dom->appendChild($root);

		return $this->dom->saveXML();
	}

	/**
	 * Build the row's <oai_dc:dc> node (Dublin core date)
	 * Setting the attribute name space
	 *
	 * @return \DOMNode
	 *
	 * @since 4.0
	 */
	public function rowOaiDc()
	{
		$oaiDc = $this->createElement('oai_dc:dc');

		$oaiDc->setAttributeNS(
			'http://www.w3.org/2000/xmlns/',
			'xmlns:oai_dc',
			'http://www.openarchives.org/OAI/2.0/oai_dc/'
		);

		$oaiDc->setAttributeNS(
			'http://www.w3.org/2000/xmlns/',
			'xmlns:dc',
			'http://purl.org/dc/elements/1.1/'
		);

		/*$oaiDc->setAttributeNS(
			'http://www.w3.org/2000/xmlns/',
			'xmlns:dcterms',
			'http://purl.org/dc/terms/'
		);*/

		$oaiDc->setAttributeNS(
			'http://www.w3.org/2000/xmlns/',
			'xmlns:xsi',
			'http://www.w3.org/2001/XMLSchema-instance'
		);

		$oaiDc->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'schemaLocation',
			'http://www.openarchives.org/OAI/2.0/oai_dc/ http://www.openarchives.org/OAI/2.0/oai_dc.xsd');

		return $oaiDc;
	}

	/**
	 * @param array $record
	 *
	 * @since 4.0
	 */
	public function setRecord($record = array())
	{
		$this->record = $record;
	}

	/**
	 * @return \DOMElement
	 *
	 * @since 4.0
	 */
	public function getRecordHeader()
	{
		$dateEl = $this->dateElName();

		if (!array_key_exists('__pk_val', $this->record))
		{
			throw new \UnexpectedValueException('__pk_val value not defined');
		}

		if (!array_key_exists($dateEl, $this->record))
		{
			throw new \UnexpectedValueException('date timestamp element not defined');
		}

		$header = $this->dom->createElement('header');

		$header->appendChild($this->dom->createElement('identifier', $this->recordIdentifier()));

		$timestamp = new \DateTime($this->record[$dateEl]);
		$header->appendChild($this->dom->createElement('datestamp', $timestamp->format('Y-m-d')));

		return $header;
	}

	/**
	 * @return string
	 *
	 * @since 4.0
	 */
	private function recordIdentifier()
	{
		$prefix = 'oai:' . $this->repositoryIdentifier() . ':';
		$prefix .= $this->listModel->getId();
		$prefix .= '/' . $this->record['__pk_val'];

		return $prefix;
	}

	/**
	 * Get an individual record
	 *
	 * @return \DOMDocument
	 *
	 * @since 4.0
	 */
	public function getRecord()
	{
		$root     = $this->root();
		$header   = $this->getRecordHeader();
		$metaData = $this->getRecordMetaData();
		$root->appendChild($this->responseDate());
		$request = $this->requestElement();
		$this->nodeAttributes($request, array('verb'           => 'GetRecord', 'identifier' => $this->recordIdentifier(),
		                                      'metadataPrefix' => 'oai_dc'));
		$root->appendChild($request);

		$getRecord = $this->dom->createElement('GetRecord');
		$record    = $this->dom->createElement('record');

		$record->appendChild($header);
		$record->appendChild($metaData);

		$getRecord->appendChild($record);
		$root->appendChild($getRecord);
		$this->dom->appendChild($root);

		return $this->dom;
	}

	/**
	 * @return \DOMElement
	 *
	 * @since 4.0
	 */
	public function getRecordMetaData()
	{
		$metaData = $this->dom->createElement('metadata');
		$row      = $this->rowOaiDc();

		$this->dcRow($row, $this->record);
		$metaData->appendChild($row);

		return $metaData;
	}

	/**
	 * @param \DOMElement &$node To append Dublin Core data to
	 * @param object       $row  Data
	 *
	 * @since 4.0
	 */
	public function dcRow(&$node, $row)
	{
		if (is_array($row))
		{
			$row = ArrayHelper::toObject($row);
		}

		$elements  = $this->getOAIElements();
		$formModel = $this->listModel->getFormModel();
		$i         = 0;

		foreach ($elements->dublin_core_element as $elementId)
		{
			$elementKey = $formModel->getElement($elementId, true)->getFullName(true, false);

			if ($elements->raw[$i] === '1')
			{
				$elementKey .= '_raw';
			}

			$nodeValue = isset($row->$elementKey) ? $row->$elementKey : '';
			$nodeType  = strtolower($elements->dublin_core_type[$i]);
			$nodeType  = str_replace('dc.', 'dc:', $nodeType);

			if (!is_string($nodeValue))
			{
				$nodeValue = json_encode($nodeValue);
			}

			$nodeValue = html_entity_decode($nodeValue, null, "UTF-8");
			$child     = $this->createElement($nodeType, $nodeValue);
			$node->appendChild($child);

			$i++;
		}
	}

	/**
	 * Get the list id from the setName
	 *
	 * @param string $setName
	 *
	 * @return mixed
	 *
	 * @since 4.0
	 */
	public function listIdFromSetName($setName)
	{
		$db    = $this->_db;
		$query = $db->getQuery(true);
		$query->select('id')->from('#__fabrik_lists')
			->where('params LIKE \'%"open_archive_set_spec":"' . $setName . '"%\'')
			->where('params LIKE \'%"open_archive_active":"1"%\'');
		$db->setQuery($query);

		return $db->loadResult();
	}

	/**
	 * Create OAI listSet data
	 *
	 * @return \DOMDocument
	 *
	 * @since 4.0
	 */
	public function listSets()
	{
		$root = $this->root();
		$root->appendChild($this->responseDate());
		$request = $this->requestElement();
		$this->nodeAttributes($request, array('verb' => 'ListSets'));
		$root->appendChild($request);
		$listSet = $this->dom->createElement('ListSets');
		$db      = $this->_db;
		$query   = $db->getQuery(true);
		$query->select('id, label, params')->from('#__fabrik_lists');
		$db->setQuery($query);
		$lists = $db->loadObjectList();

		foreach ($lists as $list)
		{
			$params = new Registry($list->params);

			if ((bool) $params->get('open_archive_active', 0))
			{
				$set = $this->dom->createElement('set');
				$set->appendChild($this->dom->createElement('setSpec', $params->get('open_archive_set_spec')));
				$set->appendChild($this->dom->createElement('setName', $list->label));
				$listSet->appendChild($set);
			}
		}

		$root->appendChild($listSet);
		$this->dom->appendChild($root);

		return $this->dom;
	}

	/**
	 * List the meta data formats - currently we only support dublin core.
	 *
	 * @param string $identifier
	 *
	 * @return \DOMDocument
	 *
	 * @since 4.0
	 */
	public function listMetaDataFormats($identifier = '')
	{
		$root = $this->root();
		$root->appendChild($this->responseDate());
		$request = $this->requestElement();
		$this->nodeAttributes($request, array('verb' => 'ListMetadataFormats', 'identifier' => $identifier));
		$root->appendChild($request);
		$list   = $this->dom->createElement('ListMetadataFormats');
		$format = $this->dom->createElement('metadataFormat');
		$format->appendChild($this->dom->createElement('metadataPrefix', 'oai_dc'));
		$format->appendChild($this->dom->createElement('schema', 'http://www.openarchives.org/OAI/2.0/oai_dc.xsd'));
		$format->appendChild($this->dom->createElement('metadataNamespace', 'http://www.openarchives.org/OAI/2.0/oai_dc/'));
		$list->appendChild($format);
		$root->appendChild($list);
		$this->dom->appendChild($root);

		return $this->dom;
	}
}