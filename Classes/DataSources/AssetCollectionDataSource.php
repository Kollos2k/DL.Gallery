<?php
declare(strict_types=1);

namespace DL\Gallery\DataSources;

/*
 * This file is part of the DL.Gallery package.
 *
 * (c) Daniel Lienert and others 2016-2026
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Media\Domain\Model\AssetCollection;
use Neos\Media\Domain\Repository\AssetCollectionRepository;
use Neos\Neos\Service\DataSource\AbstractDataSource;
use Neos\Flow\Annotations as Flow;
use Neos\Utility\TypeHandling;

class AssetCollectionDataSource extends AbstractDataSource
{

    /**
     * @var string
     */
    static protected $identifier = 'dl-gallery-assetcollections';

    #[Flow\Inject]
    protected PersistenceManagerInterface $persistenceManager;

    #[Flow\Inject]
    protected AssetCollectionRepository $assetCollectionRepository;

    /**
     * @param Node|null $node
     * @param array $arguments
     * @return array
     */
    public function getData(?Node $node = null, array $arguments = []): array
    {
        $options = [['label' => '-', 'value' => '']];
        $assetCollections = $this->assetCollectionRepository->findAll();
        
        foreach ($assetCollections as $assetCollection) {
            /** @var AssetCollection $assetCollection */
            $options[] = [
                'label' => $assetCollection->getTitle(),
                'value' => [
                    '__identity' => $this->persistenceManager->getIdentifierByObject($assetCollection),
                    '__type' => TypeHandling::getTypeForValue($assetCollection)
                ]
            ];
        }

        return $options;
    }
}
