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
use Neos\Neos\Service\DataSource\AbstractDataSource;
use Neos\Flow\Annotations as Flow;

class ThemeDataSource extends AbstractDataSource
{

    /**
     * @var string
     */
    static protected $identifier = 'dl-gallery-themes';

    protected array $settings = [];

    public function injectSettings(array $settings): void
    {
        $this->settings = $settings;
    }

    /**
     * @param Node|null $node
     * @param array $arguments
     * @return array
     */
    public function getData(?Node $node = null, array $arguments = []): array
    {
        $themes = [];
        $configuredThemes = $this->settings['themes'] ?? [];

        foreach ($configuredThemes as $key => $theme) {
            $label = $theme['label'] ?? $key;
            $themes[$key]['label'] = $label;
        }

        return $themes;
    }
}
