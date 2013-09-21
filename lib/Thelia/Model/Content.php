<?php

namespace Thelia\Model;

use Propel\Runtime\Propel;
use Thelia\Core\Event\Content\ContentEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Model\Base\Content as BaseContent;
use Thelia\Model\Map\ContentTableMap;
use Thelia\Tools\URL;
use Propel\Runtime\Connection\ConnectionInterface;

class Content extends BaseContent
{
    use \Thelia\Model\Tools\ModelEventDispatcherTrait;

    use \Thelia\Model\Tools\PositionManagementTrait;

    use \Thelia\Model\Tools\UrlRewritingTrait;

    /**
     * {@inheritDoc}
     */
    protected function getRewrittenUrlViewName() {
        return 'content';
    }

    /**
     * Calculate next position relative to our parent
     */
    protected function addCriteriaToPositionQuery($query) {

        // TODO: Find the default folder for this content,
        // and generate the position relative to this folder
    }

    public function getDefaultFolderId()
    {
        // Find default folder
        $default_folder = ContentFolderQuery::create()
            ->filterByContentId($this->getId())
            ->filterByDefaultFolder(true)
            ->findOne();

        return $default_folder == null ? 0 : $default_folder->getFolderId();
    }

    public function create($defaultFolderId)
    {
        $con = Propel::getWriteConnection(ContentTableMap::DATABASE_NAME);

        $con->beginTransaction();

        $this->dispatchEvent(TheliaEvents::BEFORE_CREATECONTENT, new ContentEvent($this));

        try {
            $this->save($con);

            $cf = new ContentFolder();
            $cf->setContentId($this->getId())
                ->setFolderId($defaultFolderId)
                ->setDefaultFolder(1)
                ->save($con);

            $this->setPosition($this->getNextPosition())->save($con);

            $con->commit();
        } catch(\Exception $ex) {

            $con->rollback();

            throw $ex;
        }
    }


    /**
     * {@inheritDoc}
     */
    public function preInsert(ConnectionInterface $con = null)
    {
        $this->setPosition($this->getNextPosition());

        $this->dispatchEvent(TheliaEvents::BEFORE_CREATECONTENT, new ContentEvent($this));

        return true;
    }

    public function postInsert(ConnectionInterface $con = null)
    {
        $this->dispatchEvent(TheliaEvents::AFTER_CREATECONTENT, new ContentEvent($this));
    }

    public function preUpdate(ConnectionInterface $con = null)
    {
        $this->dispatchEvent(TheliaEvents::BEFORE_UPDATECONTENT, new ContentEvent($this));

        return true;
    }

    public function postUpdate(ConnectionInterface $con = null)
    {
        $this->dispatchEvent(TheliaEvents::AFTER_UPDATECONTENT, new ContentEvent($this));
    }

    public function preDelete(ConnectionInterface $con = null)
    {
        $this->dispatchEvent(TheliaEvents::BEFORE_DELETECONTENT, new ContentEvent($this));

        return true;
    }

    public function postDelete(ConnectionInterface $con = null)
    {
        $this->dispatchEvent(TheliaEvents::AFTER_DELETECONTENT, new ContentEvent($this));
    }
}
