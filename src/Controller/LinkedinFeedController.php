<?php declare(strict_types=1);

namespace sunsetbeat\SuluLinkedinFeed\Controller;

use sunsetbeat\SuluLinkedinFeed\Common\DoctrineListRepresentationFactory;
use sunsetbeat\SuluLinkedinFeed\Entity\LinkedinFeed;
use sunsetbeat\SuluLinkedinFeed\Repository\LinkedinFeedRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Sulu\Component\Security\SecuredControllerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @phpstan-type LinkedinFeedData array{
 *     id: int|null,
 *     enabled: bool,
 *     title: string,
 *     link: string,
 * }
 */
class LinkedinFeedController extends AbstractController implements SecuredControllerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private readonly DoctrineListRepresentationFactory $doctrineListRepresentationFactory,
        private TagManagerInterface $tagManager,
        private Security $security,
    ) {
        $this->linkedin_feedRepository = $this->entityManager->getRepository(LinkedinFeed::class);
    }

    #[Route(path: '/admin/api/sunsetbeat/linkedin_feed/{id}', methods: ['GET'], name: 'sunsetbeat.get_linkedin_feed')]
    public function getAction(int $id, Request $request): Response
    {
        $linkedin_feed = $this->load($id, $request);
        if (!$linkedin_feed instanceof LinkedinFeed) {
            throw new NotFoundHttpException();
        }

        return $this->json($this->getDataForEntity($linkedin_feed));
    }

    #[Route(path: '/admin/api/sunsetbeat/linkedin_feed/{id}', methods: ['PUT'], name: 'sunsetbeat.put_linkedin_feed')]
    public function putAction(int $id, Request $request): Response
    {
        $linkedin_feed = $this->load($id, $request);
        if (!$linkedin_feed instanceof LinkedinFeed) {
            throw new NotFoundHttpException();
        }

        /** @var LinkedinFeedData $data */
        $data = $request->toArray();
        $this->mapDataToEntity($data, $linkedin_feed);
        $this->save($linkedin_feed);

        return $this->json($this->getDataForEntity($linkedin_feed));
    }

    #[Route(path: '/admin/api/sunsetbeat/linkedin_feed', methods: ['POST'], name: 'sunsetbeat.post_linkedin_feed')]
    public function postAction(Request $request): Response
    {
        $linkedin_feed = $this->create($request);

        /** @var LinkedinFeedData $data */
        $data = $request->toArray();
        $this->mapDataToEntity($data, $linkedin_feed);
        $this->save($linkedin_feed);

        return $this->json($this->getDataForEntity($linkedin_feed), 201);
    }

    #[Route(path: '/admin/api/sunsetbeat/linkedin_feed/{id}', methods: ['POST'], name: 'sunsetbeat.post_linkedin_feed_trigger')]
    public function postTriggerAction(int $id, Request $request): Response
    {
        $linkedin_feed = $this->linkedin_feedRepository->findById($id, (string) $this->getLocale($request));
        if (!$linkedin_feed instanceof LinkedinFeed) {
            throw new NotFoundHttpException();
        }

        switch ($request->query->get('action')) {
            case 'enable':
                $linkedin_feed->setEnabled(true);
                break;
            case 'disable':
                $linkedin_feed->setEnabled(false);
                break;
        }

        $this->linkedin_feedRepository->save($linkedin_feed);

        return $this->json($this->getDataForEntity($linkedin_feed));
    }

    #[Route(path: '/admin/api/sunsetbeat/linkedin_feed/{id}', methods: ['DELETE'], name: 'sunsetbeat.delete_linkedin_feed')]
    public function deleteAction(int $id): Response
    {
        $this->remove($id);

        return $this->json(null, 204);
    }

    #[Route(path: '/admin/api/sunsetbeat/linkedin_feed', methods: ['GET'], name: 'sunsetbeat.get_linkedin_feed_list')]
    public function getListAction(Request $request): Response
    {
        $listRepresentation = $this->doctrineListRepresentationFactory->createDoctrineListRepresentation(
            LinkedinFeed::RESOURCE_KEY,
            [],
            [],
        );

        return $this->json($listRepresentation->toArray());
    }

    /**
     * @return LinkedinFeedData $data
     */
    protected function getDataForEntity(LinkedinFeed $entity): array
    {
        $getImageGallery = [];
        foreach ($entity->getImageGallery() as $key => $value) {
            $getImageGallery[] = [
                'id' => $value->getId(),
            ];
        }

        $tagsIds = [];
        $tags = $entity->getTags();
        foreach ($tags as $tag) {
            if (!in_array($tag->getName(), $tagsIds)) {
                $tagsIds[] = $tag->getName();
            }
        }

        
        return [
            'id' => $entity->getId(),
            'enabled' => $entity->isEnabled(),
            'text' => $entity->getText() ?? '',
            'manual_update' => $entity->getManualUpdate() ?? '',
            'image_gallery' => $getImageGallery,
            'tags' => $tagsIds,
        ];
    }

    /**
     * @param LinkedinFeedData $data
     */
    protected function mapDataToEntity(array $data, LinkedinFeed $entity): void
    {
        $entity->setManualUpdate($data['manual_update']);

        $tags = $entity->getTags();
        foreach ($tags as $tag) {
            if (!in_array($tag->getName(), $data['tags'])) {
                $entity->removeTag($tag);
            }
        }

        if (isset($data['tags']) && is_array($data['tags']) && count($data['tags']) > 0) {
            foreach ($data['tags'] as $tagname) {
                $this->tagManager->findOrCreateByName($tagname, $this->security->getUser()->getId());
 
                $tag = $this->entityManager->getRepository(Tag::class)->findOneBy([
                    // 'id' => $request->query->get('contactId'),
                    'name' => $tagname,
                ]);
                // TODO: TYPE Tag ....
                $entity->addTag($tag);
            }
        }

    }

    protected function load(int $id, Request $request): ?LinkedinFeed
    {
        return $this->linkedin_feedRepository->findById($id, (string) $this->getLocale($request));
    }

    protected function create(Request $request): LinkedinFeed
    {
        return $this->linkedin_feedRepository->create((string) $this->getLocale($request));
    }

    protected function save(LinkedinFeed $entity): void
    {
        $this->linkedin_feedRepository->save($entity);
    }

    protected function remove(int $id): void
    {
        $this->linkedin_feedRepository->remove($id);
    }

    public function getLocale(Request $request): ?string
    {
        return $request->query->has('locale') ? (string) $request->query->get('locale') : null;
    }

    public function export($properties, $format = null)
    {
        dd($properties);
        $data = [];
        foreach ($properties as $key => $property) {
            $value = $property;
            if (\is_bool($value)) {
                $value = (int) $value;
            }

            $data[$key] = [
                'name' => $key,
                'value' => $value,
                'type' => '',
            ];
        }

        return $data;
    }

    public function getSecurityContext(): string
    {
        return LinkedinFeed::SECURITY_CONTEXT;
    }

}
