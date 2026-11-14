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

use DL\Gallery\Exceptions\InvalidConfigurationException;
use Neos\Flow\Annotations as Flow;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;
use Neos\FluidAdaptor\Core\ViewHelper\Exception;
use Neos\Media\Domain\Model\Image;
use Neos\Media\Domain\Model\ImageInterface;
use Neos\Media\Domain\Model\ThumbnailConfiguration;
use Neos\Media\Domain\Service\AssetService;
use Neos\Media\Exception\AssetServiceException;
use Neos\Media\Exception\ThumbnailServiceException;

class ImageDataViewHelper extends AbstractViewHelper
{
    protected array $settings = [];

    #[Flow\Inject]
    protected AssetService $assetService;

    protected string $tagName = 'img';

    public function injectSettings(array $settings): void
    {
        $this->settings = $settings;
    }

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('image', ImageInterface::class, 'The image', true);
        $this->registerArgument('width', 'int', 'The image width', false);
        $this->registerArgument('maximumWidth', 'int', 'The image maximumWidth', false);
        $this->registerArgument('height', 'int', 'The image height', false);
        $this->registerArgument('maximumHeight', 'int', 'The image maximumHeight', false);
        $this->registerArgument('allowCropping', 'bool', 'If the image is allowed to be cropped', false);
        $this->registerArgument('allowUpScaling', 'bool', 'If the image is allowed to be scaled up', false);
        $this->registerArgument('theme', 'string', 'The name of a gallery theme', false);
        $this->registerArgument('imageVariant', 'string', 'The name of a defined resolution', false);
        $this->registerArgument('key', 'string', 'The key of the meta data array', false);
    }

    public function render(): mixed
    {
        $image = $this->arguments['image'];
        $width = $this->arguments['width'] ?? null;
        $maximumWidth = $this->arguments['maximumWidth'] ?? null;
        $height = $this->arguments['height'] ?? null;
        $maximumHeight = $this->arguments['maximumHeight'] ?? null;
        $allowCropping = $this->arguments['allowCropping'] ?? false;
        $allowUpScaling = $this->arguments['allowUpScaling'] ?? false;

        if ($this->hasArgument('theme') && $this->hasArgument('imageVariant')) {
            $themeName = $this->arguments['theme'];
            $variantName = $this->arguments['imageVariant'];

            if (!is_string($themeName) || !is_string($variantName)) {
                throw new InvalidConfigurationException('Theme and imageVariant must be strings.', 1715000001);
            }

            $themeSettings = $this->getSettingsForCurrentTheme($themeName);

            if (!isset($themeSettings['imageVariants'][$variantName])) {
                throw new InvalidConfigurationException(sprintf('The theme "%s" has no imageVariant with name "%s" defined.', $themeName, $variantName), 1503035707);
            }

            $imageVariantSettings = $themeSettings['imageVariants'][$variantName];

            $width = $imageVariantSettings['width'] ?? null;
            $maximumWidth = $imageVariantSettings['maximumWidth'] ?? null;
            $height = $imageVariantSettings['height'] ?? null;
            $maximumHeight = $imageVariantSettings['maximumHeight'] ?? null;

            $allowCropping = $imageVariantSettings['allowCropping'] ?? false;
            $allowUpScaling = $imageVariantSettings['allowUpScaling'] ?? false;
        }

        $thumbnailConfiguration = new ThumbnailConfiguration(
            $width !== null ? (int)$width : null,
            $maximumWidth !== null ? (int)$maximumWidth : null,
            $height !== null ? (int)$height : null,
            $maximumHeight !== null ? (int)$maximumHeight : null,
            (bool)$allowCropping,
            (bool)$allowUpScaling
        );

        $imageData = $this->assetService->getThumbnailUriAndSizeForAsset($image, $thumbnailConfiguration);

        assert($image instanceof Image);

        $imageData['title'] = $image->getTitle();
        $imageData['caption'] = $image->getCaption();

        $key = $this->arguments['key'] ?? null;
        if (empty($key) || !is_string($key)) {
            return $imageData;
        }

        return $imageData[$key] ?? null;
    }

    protected function getSettingsForCurrentTheme(string $theme): array
    {
        if (!isset($this->settings['themes'][$theme])) {
            throw new InvalidConfigurationException(sprintf('No theme with name %s was found in settings.', $theme), 1503035486);
        }

        if (!isset($this->settings['themes'][$theme]['themeSettings'])) {
            throw new InvalidConfigurationException(sprintf('The theme %s has no themeSettings defined ', $theme), 1503035487);
        }

        return $this->settings['themes'][$theme]['themeSettings'];
    }
}
