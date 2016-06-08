<?php

namespace Ho\TemplateHints\Block\Hints;

use Magento\Framework\View\Element\AbstractBlock;

/**
 * Class Js
 *
 * @package Ho\TemplateHints\Block\Hints
 */
class Js extends AbstractBlock
{
    /**
     * A repository service for view assets
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $assetRepo;

    /**
     * List of page assets that combines into groups ones having the same properties
     * @var \Magento\Framework\View\Asset\GroupedCollection
     */
    protected $assets;


    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Framework\View\Asset\GroupedCollection $assets,
        array $data = []
    )
    {
        $this->assetRepo = $assetRepo;
        $this->assets = $assets;
        $this->addAssets();

        return parent::__construct($context, $data);
    }


    /**
     * http://devdocs.magento.com/guides/v2.0/architecture/view/page-assets.html#m2devgde-page-assets-api
     */
    public function addAssets()
    {
        $asset = $this->assetRepo->createAsset('Ho_TemplateHints::js/hints.js');
        $this->assets->add('Ho_TemplateHints::js/hints.js', $asset);

        $anotherAsset = $this->assetRepo->createAsset('Ho_TemplateHints::css/hints.css');
        $this->assets->add('cHo_TemplateHints::css/hints.css', $anotherAsset);

        //Adding a remote asset to a collection
        $asset = new \Magento\Framework\View\Asset\Remote('http://example.com/feed.xml', 'xml');
//        $this->assets->add('arbitrary_identifier',$asset);
    }
}
