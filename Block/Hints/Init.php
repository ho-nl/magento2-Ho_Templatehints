<?php

namespace Ho\Templatehints\Block\Hints;

use Ho\Templatehints\Helper\Config;
use Magento\Framework\View\Asset\GroupedCollection as AssetCollection;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Context;

/**
 * 
 *
 * @package Ho\Templatehints\Block\Hints
 */
class Init extends AbstractBlock
{
    /**
     * A repository service for view assets
     * @var AssetRepository
     */
    protected $assetRepository;

    /**
     * List of page assets that combines into groups ones having the same properties
     * @var AssetCollection
     */
    protected $assetCollection;


    /**
     * @param Context         $context
     * @param AssetRepository $assetRepository
     * @param AssetCollection $assetCollection
     * @param Config          $config
     * @param array           $data
     */
    public function __construct(
        Context $context,
        AssetRepository $assetRepository,
        AssetCollection $assetCollection,
        Config $config,
        array $data = []
    ) {
        if ($config->isHintEnabled()) {
            $this->assetRepository = $assetRepository;
            $this->assetCollection = $assetCollection;
            $this->addAssets();
        }

        return parent::__construct($context, $data);
    }


    /**
     * Add assets to the header required for the initialisation of the scripts
     *
     * @todo figure out how to include .less files instead of .css files for easier syntax.
     * http://devdocs.magento.com/guides/v2.0/architecture/view/page-assets.html#m2devgde-page-assets-api
     */
    public function addAssets()
    {
        $js = $this->assetRepository->createAsset('Ho_Templatehints::js/hints.js');
        $this->assetCollection->add('Ho_Templatehints::js/hints.js', $js);

        $css = $this->assetRepository->createAsset('Ho_Templatehints::css/hints.css');
        $this->assetCollection->add('cHo_Templatehints::css/hints.css', $css);
    }
}
