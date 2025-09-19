<?php declare(strict_types=1);

namespace sunsetbeat\SuluLinkedinFeed\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Mime\MimeTypes;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Sulu\Component\Media\SystemCollections\SystemCollectionManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;

use sunsetbeat\SuluLinkedinFeed\Entity\LinkedinFeed;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;


// the name of the command is what users type after "php bin/console"
#[AsCommand(
    name: 'sunsetbeat:import-social-media-feed',
    description: 'Import Feeds.',
    hidden: false
)]
class ImportSuluLinkedinFeedCommand extends Command
{

    private $import_interfaces_allowed = ['all','linkedin_feed','linkedin_images'];

    private $output_divider_info = '<info>============================================================</>';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private HttpClientInterface $client,
        private SystemCollectionManagerInterface $systemCollectionManager,
        private MediaManagerInterface $mediaManager,
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;

        $this->print([
            '',
            $this->output_divider_info,
            '<info>Import start</>',
            $this->output_divider_info,
            '',
        ]);

        
        if (in_array($input->getOption('interface'), $this->import_interfaces_allowed)) {
            $this->import_interfaces = $input->getOption('interface');
        } else {
            $this->import_interfaces = $this->import_interfaces_allowed[0];
        }

        if (in_array($this->import_interfaces, ['all', 'linkedin_feed'])) {
            $response = $this->client->request('GET', 'https://api.linkedin.com/rest/posts?q='.$_ENV['SUNSETBEAT_SULU_LINKEDIN_FEED_TYPE'].'&'.$_ENV['SUNSETBEAT_SULU_LINKEDIN_FEED_TYPE'].'=urn%3Ali%3Aorganization%3A'.$_ENV['SUNSETBEAT_SULU_LINKEDIN_FEED_TYPE_ID'], [
                'headers' => [
                    'Authorization' => 'Bearer ' . $_ENV['SUNSETBEAT_SULU_LINKEDIN_FEED_ACCESSTOKEN'],
                    'X-Restli-Protocol-Version' => '2.0.0',
                    'LinkedIn-Version' => '202508',
                ],
                'query' => [
                    'count' => $_ENV['SUNSETBEAT_SULU_LINKEDIN_FEED_AMOUNT'],
                    'sortBy' => 'LAST_MODIFIED',
                ],
            ]);

            $feed = null;
            if ($response) {
                $feed = json_decode($response->getContent(false));
                if (isset($feed->elements)) {
                    foreach ($feed->elements as $key => $element) {
                        
                        $created = (new \DateTimeImmutable())->setTimestamp((int) ($element->createdAt / 1000));
                        $changed = (new \DateTimeImmutable())->setTimestamp((int) ($element->lastModifiedAt / 1000));
                        
                        $active = false;
                        if (!isset($element->distribution->targetEntities)) {
                            $active = true;
                        } else {
                            foreach ($element->distribution->targetEntities[0]->interfaceLocales as $value) {
                                if ($value->country == 'DE') {
                                    $active = true;
                                    break;
                                }
                            }
                        }
                        if ($active == false || isset($element->reshareContext->parent))
                            continue;

                        // Get existing entry if exists
                        $linkedin_feed = $this->entityManager->getRepository(LinkedinFeed::class)->findOneBy([
                            'import_id' => $element->id,
                        ]);

                        // Create new entry if not exists
                        if (is_null($linkedin_feed)) {
                            $linkedin_feed = new LinkedinFeed();
                            $linkedin_feed->setImportId($element->id);
                            if ($element->visibility == 'PUBLIC' && $element->lifecycleState == 'PUBLISHED') {
                                $linkedin_feed->setEnabled(true);
                            }
                            $linkedin_feed->setManualUpdate(true);
                        } else {
                            if ($linkedin_feed->getLastChange() != $changed) {
                                $linkedin_feed->setManualUpdate(true);
                            }
                        }


                        $linkedin_feed->setAuthor($element->author);
                        $linkedin_feed->setText(nl2br($this->parseLinkedinHashtags($this->parseLinkedinPersons($this->parseLinkedinMentions($element->commentary)))));
                        $linkedin_feed->setDump(json_encode($element));
                        
                        $linkedin_feed->setCreated($created);

                        $linkedin_feed->setLastChange($changed);

                        $this->entityManager->persist($linkedin_feed);
                        $this->entityManager->flush();
                        $this->entityManager->clear();
                    }
                } else {
                    $this->print([
                        $this->output_divider_info,
                        '<info>Error Data</>',
                        '<info>Error: '.$feed->message.'</>',
                        $this->output_divider_info,
                        '',
                    ]);
                }
            }
        }
        if (in_array($this->import_interfaces, ['all', 'linkedin_images'])) {

            $qb = $this->entityManager->createQueryBuilder();

            $qb->select('l')
                ->from(LinkedinFeed::class, 'l')
                ->where('l.manual_update = :manual_update')
                ->setParameter('manual_update', true);
            $linkedinFeeds = $qb->getQuery()->toIterable();

            foreach ($linkedinFeeds as $key_feed => $feed) {

                if (count($feed->getImageGallery()) <= 0 || $feed->getManualUpdate() == true) {
                    $element = json_decode($feed->getDump());
                    
                    if (isset($element->content->multiImage)) {
                        foreach ($feed->getImageGallery() as $image) {
                            $feed->removeImageGallery($image);
                        }

                        $image_url_string = '';
                        foreach ($element->content->multiImage->images as $key => $image) {
                            $image_url_string .= ($image_url_string != '' ? ',' : '').urlencode($image->id);
                        }
                        $response = $this->client->request('GET', 'https://api.linkedin.com/rest/images?ids=List('.$image_url_string.')', [
                            'headers' => [
                                'Authorization' => 'Bearer ' . $_ENV['SUNSETBEAT_SULU_LINKEDIN_FEED_ACCESSTOKEN'],
                                'X-Restli-Protocol-Version' => '2.0.0',
                                'LinkedIn-Version' => '202508',
                            ],
                        ]);
                        $images = json_decode($response->getContent(false));
                        if (isset($images->results)) {
                            foreach ($element->content->multiImage->images as $key => $image) {
                                $images_entry = $images->results->{$image->id} ?? null;

                                if (isset($images_entry->downloadUrl)) {
                                    $image_download = $this->downloadImage($images_entry->downloadUrl, str_replace(':','_',$image->id), $image->altText);
                                    $feed->addImageGallery($image_download);
                                    $feed->setManualUpdate(false);
                                }
                            }
                        } else {
                            $this->print([
                                $this->output_divider_info,
                                '<info>Error Multi ID: '.$feed->getId().'</>',
                                '<info>Error: '.$images->message.'</>',
                                $this->output_divider_info,
                                '',
                            ]);
                        }
                    } elseif (isset($element->content->media)) {
                        $response = $this->client->request('GET', 'https://api.linkedin.com/rest/images/'.urlencode($element->content->media->id), [
                            'headers' => [
                                'Authorization' => 'Bearer ' . $_ENV['SUNSETBEAT_SULU_LINKEDIN_FEED_ACCESSTOKEN'],
                                'X-Restli-Protocol-Version' => '2.0.0',
                                'LinkedIn-Version' => '202508',
                            ],
                        ]);
                        $media_feed = json_decode($response->getContent(false));
                        if (isset($media_feed->downloadUrl)) {
                            foreach ($feed->getImageGallery() as $image) {
                                $feed->removeImageGallery($image);
                            }

                            $image_download = $this->downloadImage($media_feed->downloadUrl, str_replace(':','_',$element->content->media->id), $element->content->media->altText??'');
                            $feed->addImageGallery($image_download);
                            $feed->setManualUpdate(false);
                        } else {
                            $this->print([
                                $this->output_divider_info,
                                '<info>Error Single ID: '.$feed->getId().'</>',
                                '<info>Error: '.$images->message.'</>',
                                $this->output_divider_info,
                                '',
                            ]);
                        }
                    }
                }
                
                $this->entityManager->persist($feed);
                $this->entityManager->flush();
                $this->entityManager->clear();
            }        
        }

        $this->print([
            $this->output_divider_info,
            '<info>Import successfully</>',
            '<info></>',
            '<info>FINISHED</>',
            $this->output_divider_info,
            '',
        ]);
        return Command::SUCCESS;

    }

    /**
     * Command configurations
     *
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Import feeds from social media.')
            ->setHelp('This command allows you to import feeds from social media.')
            ->addOption(
                'interface', // this is the name that users must type to pass this option (e.g. --iterations=5)
                null, // this is the optional shortcut of the option name, which usually is just a letter (e.g. `i`, so users pass it as `-i`); use it for commonly used options or options with long names
                InputOption::VALUE_OPTIONAL, // this is the type of option (e.g. requires a value, can be passed more than once, etc.)
                'Allowed Import-Types ('.implode(',',$this->import_interfaces_allowed).')', // the option description displayed when showing the command help
                'all' // the default value of the option (for those which allow to pass values)
            )
        ;
    }

    /**
     * Output to console
     *
     * Example:
     *   Input:  ['','']
     */
    private function print(array $data) : void {
        $this->output->writeln($data);
    }

    /**
     * Convert LinkedIn hashtag tokens into HTML links
     *
     * Example:
     *   Input:  "Check {hashtag|\#|Gourmetreise} now"
     *   Output: "Check <a href="https://www.linkedin.com/feed/hashtag/gourmetreise/" target="_blank">#Gourmetreise</a> now"
     */
    function parseLinkedinHashtags(string $text): string
    {
        // Look for tokens like {hashtag|\#|Gourmetreise}
        return preg_replace_callback(
            '/\{hashtag\|\\\\#\|([^}]+)\}/',
            function ($matches) {
                // $matches[1] = "Gourmetreise"

                $tag = $matches[1];

                // Build LinkedIn hashtag URL (LinkedIn uses lowercase in URLs)
                $url = 'https://www.linkedin.com/feed/hashtag/' . strtolower($tag) . '/';

                // Return safe HTML link
                return '<a href="' . htmlspecialchars($url) . '" target="_blank">#' . htmlspecialchars($tag) . '</a>';
            },
            $text
        );
    }

    /**
     * Convert LinkedIn organization mentions into HTML links.
     *
     * Example:
     *   Input:  "@[Falstaff](urn:li:organization:2263391)\u{200B}"
     *   Output: '<a href="https://www.linkedin.com/company/2263391/" target="_blank">Falstaff</a>'
     */
    function parseLinkedinMentions(string $text): string
    {
        // Look for tokens like {hashtag|\#|Gourmetreise}
        return preg_replace_callback(
        '/@\[(.+?)\]\(urn:li:(person|organization):(\d+)\)(?:\x{200B})?/u',
        function ($m) {
            $name = $m[1];
            $type = $m[2]; // 'person' oder 'organization'
            $id   = $m[3];

            // einfache, verlässliche URL-Pattern:
            $url = $type === 'person'
                 ? "https://www.linkedin.com/in/{$id}/"
                 : "https://www.linkedin.com/company/{$id}/";

            return '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8')
                 . '" target="_blank" rel="noopener noreferrer">' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</a>';
        },
        $text
        );
    }

    /**
     * Parse LinkedIn mentions in text into HTML links.
     *
     * Supports:
     *   @[Name](urn:li:organization:12345)
     *   @[Name](urn:li:person:x6qQRn0dwb)
     */
    function parseLinkedinPersons(string $text): string
    {
        return preg_replace_callback(
            '/@\[(.+?)\]\(urn:li:(person|organization):([^)]+)\)/',
            function ($matches) {
                $name = $matches[1];       // e.g. "Konstantinos Georgosopoulos"
                $type = $matches[2];       // "person" or "organization"
                $id   = $matches[3];       // e.g. "x6qQRn0dwb" or "2263391"

                $url = $type === 'person'
                    ? "https://www.linkedin.com/in/{$id}/"
                    : "https://www.linkedin.com/company/{$id}/";

                return '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8')
                    . '" target="_blank" rel="noopener noreferrer">' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</a>';
            },
            $text
        );
    }

    private function downloadImage($link, $name, $altText = '') {
        $path = dirname($_ENV['SYMFONY_DOTENV_PATH']);
        $saveTo = $path.'/public/sunsetbeat_tmp_files/'.$name;
        $collection = $this->systemCollectionManager->getSystemCollection('sunsetbeat.images_linkedin');

        if (!file_exists($path.'/public/sunsetbeat_tmp_files')) {
            mkdir($path.'/public/sunsetbeat_tmp_files', 0777, true);
        }
        $imageData = file_get_contents($link);
        file_put_contents($saveTo, $imageData);
        $mimeTypes = new MimeTypes();
        $mimeType = $mimeTypes->guessMimeType($saveTo);

        $extensions = $mimeTypes->getExtensions($mimeType);
        if (!empty($extensions)) {
            $extension = $extensions[0];
            $newPath = $saveTo . '.' . $extension;
            if (!file_exists($newPath)) {
                rename($saveTo, $newPath);
            }
            $saveTo = $newPath;
        }        

        $image_chk = $this->entityManager->getRepository(MediaInterface::class)->findMedia([
            'collection' => $collection,
            'search' => basename($saveTo),
        ]);

        $image = false;
        if (isset($image_chk) && is_array($image_chk) && count($image_chk) == 1) {
            $image = $image_chk[0];
        }
        if (!$image) {

            $uploadedFile = new UploadedFile($saveTo, basename($saveTo));
            $collection = $this->systemCollectionManager->getSystemCollection('sunsetbeat.images_linkedin');
            $media = $this->mediaManager->save(
                $uploadedFile,
                [
                    'locale' => 'de',
                    'collection' => $collection,
                    'title' => basename($saveTo),
                    'description' => $altText,
                    'copyright' => '',
                    'credits' => '',
                    'version' => $collection,
                ],
                1
            );
            $image = $this->entityManager->getRepository(MediaInterface::class)->find($media->getId());
        }
        unlink($saveTo);

        return $image;
    }

}
