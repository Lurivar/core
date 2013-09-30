<?php
/*************************************************************************************/
/*                                                                                   */
/*      Thelia	                                                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : info@thelia.net                                                      */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      This program is free software; you can redistribute it and/or modify         */
/*      it under the terms of the GNU General Public License as published by         */
/*      the Free Software Foundation; either version 3 of the License                */
/*                                                                                   */
/*      This program is distributed in the hope that it will be useful,              */
/*      but WITHOUT ANY WARRANTY; without even the implied warranty of               */
/*      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the                */
/*      GNU General Public License for more details.                                 */
/*                                                                                   */
/*      You should have received a copy of the GNU General Public License            */
/*	    along with this program. If not, see <http://www.gnu.org/licenses/>.         */
/*                                                                                   */
/*************************************************************************************/

namespace Thelia\Core\Template\Loop;
use Thelia\Core\Template\Element\BaseI18nLoop;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Core\Event\Document\DocumentEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Type\TypeCollection;
use Thelia\Type\EnumListType;
use Propel\Runtime\ActiveQuery\Criteria;
use Thelia\Model\ConfigQuery;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Type\EnumType;
use Thelia\Log\Tlog;

/**
 * The document loop
 *
 * @author Franck Allimant <franck@cqfdev.fr>
 */
class Document extends BaseI18nLoop
{
    public $timestampable = true;

    /**
     * @var array Possible document sources
     */
    protected $possible_sources = array('category', 'product', 'folder', 'content');

    /**
     * @return \Thelia\Core\Template\Loop\Argument\ArgumentCollection
     */
    protected function getArgDefinitions()
    {
        $collection = new ArgumentCollection(

                Argument::createIntListTypeArgument('id'),
                Argument::createIntListTypeArgument('exclude'),
                new Argument(
                        'order',
                        new TypeCollection(
                                new EnumListType(array('alpha', 'alpha-reverse', 'manual', 'manual-reverse', 'random'))
                        ),
                        'manual'
                ),
                Argument::createIntTypeArgument('lang'),

                Argument::createIntTypeArgument('category'),
                Argument::createIntTypeArgument('product'),
                Argument::createIntTypeArgument('folder'),
                Argument::createIntTypeArgument('content'),

                new Argument(
                        'source',
                        new TypeCollection(
                                new EnumType($this->possible_sources)
                        )
                ),
                Argument::createIntTypeArgument('source_id')
        );

        // Add possible document sources
        foreach ($this->possible_sources as $source) {
            $collection->addArgument(Argument::createIntTypeArgument($source));
        }

        return $collection;
    }

    /**
     * Dynamically create the search query, and set the proper filter and order
     *
     * @param  string        $source    a valid source identifier (@see $possible_sources)
     * @param  int           $object_id the source object ID
     * @return ModelCriteria the propel Query object
     */
    protected function createSearchQuery($source, $object_id)
    {
        $object = ucfirst($source);

        $queryClass   = sprintf("\Thelia\Model\%sDocumentQuery", $object);
        $filterMethod = sprintf("filterBy%sId", $object);

        // xxxDocumentQuery::create()
        $method = new \ReflectionMethod($queryClass, 'create');
        $search = $method->invoke(null); // Static !

        // $query->filterByXXX(id)
        if (! is_null($object_id)) {
            $method = new \ReflectionMethod($queryClass, $filterMethod);
            $method->invoke($search, $object_id);
        }

        $orders  = $this->getOrder();

        // Results ordering
        foreach ($orders as $order) {
            switch ($order) {
                case "alpha":
                    $search->addAscendingOrderByColumn('i18n_TITLE');
                    break;
                case "alpha-reverse":
                    $search->addDescendingOrderByColumn('i18n_TITLE');
                    break;
                case "manual-reverse":
                    $search->orderByPosition(Criteria::DESC);
                    break;
                case "manual":
                    $search->orderByPosition(Criteria::ASC);
                    break;
                case "random":
                    $search->clearOrderByColumns();
                    $search->addAscendingOrderByColumn('RAND()');
                    break(2);
                    break;
            }
        }

        return $search;
    }

    /**
     * Dynamically create the search query, and set the proper filter and order
     *
     * @param  string        $object_type (returned) the a valid source identifier (@see $possible_sources)
     * @param  string        $object_id   (returned) the ID of the source object
     * @return ModelCriteria the propel Query object
     */
    protected function getSearchQuery(&$object_type, &$object_id)
    {
        $search = null;

        // Check form source="product" source_id="123" style arguments
        $source = $this->getSource();

        if (! is_null($source)) {

            $source_id = $this->getSourceId();
            $id = $this->getId();

            // echo "source = ".$this->getSource().", id=".$source_id." - ".$this->getArg('source_id')->getValue()."<br />";

            if (is_null($source_id) && is_null($id)) {
                            throw new \InvalidArgumentException("If 'source' argument is specified, 'id' or 'source_id' argument should be specified");
            }

            $search = $this->createSearchQuery($source, $source_id);

            $object_type = $source;
            $object_id   = $source_id;
        } else {
            // Check for product="id" folder="id", etc. style arguments
            foreach ($this->possible_sources as $source) {

                $argValue = intval($this->getArgValue($source));

                if ($argValue > 0) {

                    $search = $this->createSearchQuery($source, $argValue);

                    $object_type = $source;
                    $object_id   = $argValue;

                    break;
                }
            }
        }

        if ($search == null)
            throw new \InvalidArgumentException(sprintf("Unable to find document source. Valid sources are %s", implode(',', $this->possible_sources)));

        return $search;
    }

    /**
     * @param unknown $pagination
     */
    public function exec(&$pagination)
    {
        // Select the proper query to use, and get the object type
        $object_type = $object_id = null;

        $search = $this->getSearchQuery($object_type, $object_id);

        /* manage translations */
        $locale = $this->configureI18nProcessing($search);

        $id = $this->getId();

        if (! is_null($id)) {
            $search->filterById($id, Criteria::IN);
        }

        $exclude = $this->getExclude();
        if (!is_null($exclude))
            $search->filterById($exclude, Criteria::NOT_IN);

        // Create document processing event
        $event = new DocumentEvent($this->request);

        // echo "sql=".$search->toString();

        $results = $this->search($search, $pagination);

        $loopResult = new LoopResult($results);

        foreach ($results as $result) {

            // Create document processing event
            $event = new DocumentEvent($this->request);

            // Put source document file path
            $source_filepath = sprintf("%s%s/%s/%s",
                THELIA_ROOT,
                ConfigQuery::read('documents_library_path', 'local/media/documents'),
                $object_type,
                $result->getFile()
             );

            $event->setSourceFilepath($source_filepath);
            $event->setCacheSubdirectory($object_type);

            try {
                // Dispatch document processing event
                $this->dispatcher->dispatch(TheliaEvents::DOCUMENT_PROCESS, $event);

                $loopResultRow = new LoopResultRow($loopResult, $result, $this->versionable, $this->timestampable, $this->countable);

                $loopResultRow
                    ->set("ID"                    , $result->getId())
                    ->set("LOCALE"                , $locale)
                    ->set("DOCUMENT_URL"          , $event->getFileUrl())
                    ->set("DOCUMENT_PATH"         , $event->getDocumentPath())
                    ->set("ORIGINAL_DOCUMENT_PATH", $source_filepath)
                    ->set("TITLE"                 , $result->getVirtualColumn('i18n_TITLE'))
                    ->set("CHAPO"                 , $result->getVirtualColumn('i18n_CHAPO'))
                    ->set("DESCRIPTION"           , $result->getVirtualColumn('i18n_DESCRIPTION'))
                    ->set("POSTSCRIPTUM"          , $result->getVirtualColumn('i18n_POSTSCRIPTUM'))
                    ->set("POSITION"              , $result->getPosition())
                    ->set("OBJECT_TYPE"           , $object_type)
                    ->set("OBJECT_ID"             , $object_id)
                ;

                $loopResult->addRow($loopResultRow);
            }
            catch (\Exception $ex) {
                // Ignore the result and log an error
                Tlog::getInstance()->addError("Failed to process document in document loop: ", $this->args);
            }
        }

        return $loopResult;
    }
}
