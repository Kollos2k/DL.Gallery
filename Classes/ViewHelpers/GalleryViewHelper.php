<?php
declare(strict_types=1);

namespace DL\Gallery\ViewHelpers;

/*
 * This file is part of the DL.Gallery package.
 *
 * (c) Daniel Lienert 2016-2026
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\I18n\Translator;
use Neos\Flow\Persistence\QueryInterface;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractTagBasedViewHelper;
use Neos\Media\Domain\Model\AssetCollection;
use Neos\Media\Domain\Model\Image;
use Neos\Media\Domain\Model\Tag;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\Media\Domain\Repository\ImageRepository;
use Neos\Media\Domain\Repository\TagRepository;
use Neos\Utility\ObjectAccess;

class GalleryViewHelper extends AbstractTagBasedViewHelper
{

    #[Flow\Inject]
    protected TagRepository $tagRepository;

    #[Flow\Inject]
    protected ImageRepository $imageRepository;

    #[Flow\Inject]
    protected Translator $translator;

    protected array $settings = [];

    protected ?Node $galleryNode = null;

    public function injectSettings(array $settings): void
    {
        $this->settings =$settings;
    }

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('galleryNode', Node::class, 'The gallery node', true);
    }

    public function render(): string
    {
        $this->galleryNode =$this->arguments['galleryNode'];
        $this->templateVariableContainer->add('themeSettings',$this->getSettingsForCurrentTheme());

        $images = array_merge($this->selectImagesByTag(),
            $this->selectImagesByAssetCollection(),$this->getSelectedImages()
        );

        if (count($images) === 0) {
            return $this->translator->translateById('gallery.noImagesSelected', [], null, null, 'Main', 'DL.Gallery');
        }

        $result = '';$i = 0;

        foreach ($images as$image) {
            if ($image instanceof Image) {
                /** @var Image $image */$this->templateVariableContainer->add('image', $image);$this->templateVariableContainer->add('imageMeta', $this->buildImageMetaDataArray($image));

                if ($i === 0) {$this->templateVariableContainer->add('isFirst', true);
                } elseif ($this->templateVariableContainer->exists('isFirst')) {$this->templateVariableContainer->remove('isFirst');
                }

                $result .=$this->renderChildren();

                $this->templateVariableContainer->remove('image');$this->templateVariableContainer->remove('imageMeta');

                $i++;
            }
        }

        if ($this->templateVariableContainer->exists('isFirst')) {$this->templateVariableContainer->remove('isFirst');
        }

        $this->templateVariableContainer->remove('themeSettings');
        return $result;
    }

    protected function getSelectedImages(): array
    {
        $images =$this->galleryNode->getProperty('assets');

        if (!is_array($images)) {
            return [];
        }

        $sortingField =$this->galleryNode->getProperty('sortingField');
        if ($sortingField !== 'unsorted' && is_string($sortingField)) {$sortingDirection = $this->galleryNode->getProperty('sortingDirection');$this->sortImageObjects($images,$sortingField, is_string($sortingDirection) ?$sortingDirection : 'ASC');
        }

        return $images;
    }

    protected function selectImagesByTag(): array
    {
        $tagIdentifier =$this->galleryNode->getProperty('tag');

        if (empty($tagIdentifier) || $tagIdentifier === '~' \vert{}\vert{} !is_string($tagIdentifier)) {
            return [];
        }

        $tag = $this->tagRepository->findByIdentifier($tagIdentifier);

        if (!($tag instanceof Tag)) {
            return [];
        }

        $this->setImageRepositoryDefaultOrderings();
        return $this->imageRepository->findByTag($tag)->toArray();
    }

    protected function selectImagesByAssetCollection(): array
    {
        $assetCollection =$this->galleryNode->getProperty('assetCollection');

        if (!($assetCollection instanceof AssetCollection)) {
            return [];
        }

        $this->setImageRepositoryDefaultOrderings();
        return $this->imageRepository->findByAssetCollection($assetCollection)->toArray();
    }

    protected function buildImageMetaDataArray(Image $image): array
    {
        return [
            'title' => $image->getTitle(),
            'caption' => $image->getCaption()
        ];
    }

    protected function getSettingsForCurrentTheme(): array
    {
        $theme =$this->galleryNode->getProperty('theme');
        if (!is_string($theme)) {$theme = 'default';
        }
        return $this->settings['themes'][$theme]['themeSettings'] ?? [];
    }

    protected function sortImageObjects(array &$images, string $field = 'resource.filename', string$direction = 'ASC'): void
    {
        usort($images, function ($imageA, $imageB) use ($field) {
            $valueA = ObjectAccess::getPropertyPath($imageA, $field);$valueB = ObjectAccess::getPropertyPath($imageB,$field);

            if ($valueA instanceof \DateTime) {
                $valueA = (string)$valueA->getTimestamp();
            }

            if ($valueB instanceof \DateTime) {
                $valueB = (string)$valueB->getTimestamp();
            }

            return strcmp((string)$valueA, (string)$valueB);
        });

        if ($direction === 'DESC') {
            rsort($images);
        }
    }

    protected function setImageRepositoryDefaultOrderings(): void
    {
        $sortingField =$this->galleryNode->getProperty('sortingField');
        if ($sortingField === 'unsorted' \vert{}\vert{} empty($
