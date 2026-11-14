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
use Neos\Media\Domain\Model\Image;

class ImageViewHelper extends \Neos\Media\ViewHelpers\ImageViewHelper
{
    protected array $settings = [];

    public function injectSettings(array $settings): void
    {
        $this->settings = $settings;
    }

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerTagAttribute('theme', 'string', 'The name of a gallery theme', false);
        $this->registerTagAttribute('imageVariant', 'string', 'The name of a defined resolution', false);
        $this->overrideArgument('alt', 'string', 'The alt attribute', false);
    }

    public function render(): string
    {
        $theme = $this->hasArgument('theme') ? $this->arguments['theme'] : null;
        $imageVariant = $this->hasArgument('imageVariant') ? $this->arguments['imageVariant'] : null;

        if (is_string($theme) && is_string($imageVariant)) {
            $themeSettings = $this->getSettingsForCurrentTheme($theme);

            if (!isset($themeSettings['imageVariants'][$imageVariant])) {
                throw new InvalidConfigurationException(sprintf('The theme "%s" has no imageVariant with name "%s" defined.', $theme, $imageVariant), 1503035707);
            }

            $imageVariantSettings = $themeSettings['imageVariants'][$imageVariant];

            $this->arguments['width'] = $imageVariantSettings['width'] ?? null;
            $this->arguments['maximumWidth'] = $imageVariantSettings['maximumWidth'] ?? null;
            $this->arguments['height'] = $imageVariantSettings['height'] ?? null;
            $this->arguments['maximumHeight'] = $imageVariantSettings['maximumHeight'] ?? null;

            $this->arguments['allowCropping'] = $imageVariantSettings['allowCropping'] ?? false;
            $this->arguments['allowUpScaling'] = $imageVariantSettings['allowUpScaling'] ?? false;

            if ($this->tag->hasAttribute('theme')) {
                $this->tag->removeAttribute('theme');
            }
            if ($this->tag->hasAttribute('imageVariant')) {
                $this->tag->removeAttribute('imageVariant');
            }
        }

        $image = $this->arguments['image'] ?? null;
        if ($image instanceof Image) {
            $title = $image->getTitle();
            $caption = $image->getCaption();

            $altText = !empty($caption) ? $caption : $title;

            $this->tag->addAttributes([
                'title' => $title,
                'alt' => $altText
            ]);

            $this->arguments['alt'] = $this->tag->getAttribute('alt');
        }

        return parent::render();
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
