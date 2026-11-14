<?php
declare(strict_types=1);

namespace DL\Gallery\DataSources;

/*
 * This file is part of the DL.Gallery package.
 *
 * (c) Daniel Lienert 2016-2026
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Media\Domain\Model\Tag;
use Neos\Media\Domain\Repository\TagRepository;
use Neos\Neos\Service\DataSource\AbstractDataSource;
use Neos\Flow\Annotations as Flow;

class TagDataSource extends AbstractDataSource
{

    /**
     * @var string
     */
    static protected $identifier = 'dl-gallery-tags';

    #[Flow\Inject]
    protected PersistenceManagerInterface $persistenceManager;

    #[Flow\Inject]
    protected TagRepository $tagRepository;

    /**
     * @param Node|null $node
     * @param array $arguments
     * @return array
     */
    public function getData(?Node $node = null, array $arguments = []): array
    {
        $tagCollection = $this->tagRepository->findAll();
        $tags = [];
        $tags['']['label'] = '-';

        foreach ($tagCollection as $tag) {
            /** @var Tag $tag */
            $tags[$this->persistenceManager->getIdentifierByObject($tag)] = $tag;
        }

        return $tags;
    }
}
