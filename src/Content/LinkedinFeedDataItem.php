<?php declare(strict_types=1);

namespace sunsetbeat\SuluLinkedinFeed\Content;

use sunsetbeat\SuluLinkedinFeed\Entity\LinkedinFeed;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Component\SmartContent\ItemInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaRepositoryInterface;

class LinkedinFeedDataItem implements ItemInterface
{
    /**
     * @var LinkedinFeed
     */
    // private $entity;
    private $id;
    private $title;
    // private $locale;

    /**
     * LinkedinFeedItem constructor.
     */
    public function __construct(LinkedinFeed $entity)
    {
        $this->entity = $entity;
        $this->id = $entity->getId();
        $this->title = preg_replace('/\s+/', ' ', substr(strip_tags($this->entity->getText()), 0, 50)).' ...';
    }

    /**
     * @Serializer\VirtualProperty
     */
    public function getId()
    {
        return $this->entity->getId();
    }

    /**
     * @Serializer\VirtualProperty
     */
    public function getTitle()
    {
        return preg_replace('/\s+/', ' ', substr(strip_tags($this->entity->getText()), 0, 50)).' ...';
    }

    public function getImage()
    {
        return null;
    }

    /**
     * @return mixed|News
     */
    public function getResource()
    {
        return $this->entity;
    }
}
