<?php declare(strict_types=1);

namespace sunsetbeat\SuluLinkedinFeed\Entity;

use sunsetbeat\SuluLinkedinFeed\Repository\LinkedinFeedRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Component\Persistence\Model\AuditableInterface;
use Sulu\Component\Persistence\Model\AuditableTrait;

#[ORM\Entity(repositoryClass: LinkedinFeedRepository::class)]
#[ORM\Table(name: 'sunsetbeat_linkedin_feeds')]
class LinkedinFeed implements AuditableInterface
{
    final public const RESOURCE_KEY = 'linkedin_feed';
    final public const FORM_KEY = 'linkedin_feed';
    final public const LIST_KEY = 'linkedin_feed_list';
    final public const SECURITY_CONTEXT = 'sulu.secure.linkedin_feed';

    use AuditableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $import_id = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $author = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $text = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $dump = null;

    #[ORM\ManyToMany(targetEntity: "Sulu\Bundle\MediaBundle\Entity\MediaInterface", inversedBy: "linkedin_feed")]
    #[ORM\JoinTable(name: "sunsetbeat_linkedin_feed_image_gallery_images")]
    #[ORM\JoinColumn(name: "media_id", referencedColumnName: "id", onDelete: "CASCADE")]
    #[ORM\InverseJoinColumn(name: "image_gallery_id", referencedColumnName: "id", onDelete: "CASCADE")]
    private $image_gallery;

    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: "tags")]
    #[ORM\JoinTable(name: "sunsetbeat_linkedin_feed_tags")]
    #[ORM\JoinColumn(name: "sunsetbeat_linkedin_feed_id", referencedColumnName: "id", onDelete: "CASCADE")]
    #[ORM\InverseJoinColumn(name: "tag_id", referencedColumnName: "id", onDelete: "CASCADE")]
    private $tags;


    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $enabled = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $manual_update = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private $last_change;


    public function __construct()
    {
        $this->image_gallery = new ArrayCollection();
        $this->tags = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getImportId(): ?string { return $this->import_id; }
    public function setImportId(?string $import_id): self { $this->import_id = $import_id; return $this; }
    public function getAuthor(): ?string { return $this->author; }
    public function setAuthor(?string $author): self { $this->author = $author; return $this; }
    public function getText(): ?string { return $this->text; }
    public function setText(?string $text): self { $this->text = $text; return $this; }
    public function getDump(): ?string { return $this->dump; }
    public function setDump(?string $dump): self { $this->dump = $dump; return $this; }
    
    public function getManualUpdate(): ?bool { return $this->manual_update; }
    public function setManualUpdate(?bool $manual_update): self { $this->manual_update = $manual_update; return $this; }
    public function getLastChange() { return $this->last_change; }
    public function setLastChange(?\DateTimeImmutable $last_change): self { $this->last_change = $last_change; return $this; }

    public function addImageGallery(MediaInterface $image_gallery): self { if (!$this->image_gallery->contains($image_gallery)) { $this->image_gallery[] = $image_gallery; } return $this; }
    public function removeImageGallery(MediaInterface $image_gallery): self { $this->image_gallery->removeElement($image_gallery); return $this; }
    public function getImageGallery(): Collection { if ($this->image_gallery === null) { $this->image_gallery = new ArrayCollection(); } return $this->image_gallery; }

    public function getTags(): Collection { if ($this->tags === null) { $this->tags = new ArrayCollection(); } return $this->tags; }
    public function addTag(Tag $tag): void { if (!$this->tags->contains($tag)) { $this->tags[] = $tag; } }
    public function removeTag(Tag $tag): void { $this->tags->removeElement($tag); }

}
