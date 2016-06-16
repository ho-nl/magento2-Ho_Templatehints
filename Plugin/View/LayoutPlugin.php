<?php
/**
 * Copyright (c) 2016 H&O E-commerce specialisten B.V. (http://www.h-o.nl/)
 * See LICENSE.txt for license details.
 */
namespace Ho\Templatehints\Plugin\View;

use Closure;
use Ho\Templatehints\Helper\Config as HintConfig;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\View\Layout;
use Magento\Framework\View\Layout\Data\Structure;

/**
 * When a block is rendered
 *
 * Class LayoutPlugin
 * @package Ho\Templatehints\Plugin\View
 */
class LayoutPlugin
{

    /**
     * Layout model
     *
     * @var Layout
     */
    protected $layout;

    /**
     * Layout structure model
     *
     * @var Structure
     */
    protected $structure;

    /**
     * Magento directory listing
     *
     * @var DirectoryList
     */
    private $directoryList;


    /**
     * LayoutPlugin constructor.
     *
     * @param DirectoryList $directoryList
     * @param HintConfig    $hintConfig
     * @param Structure     $structure
     * @param Layout        $layout
     */
    public function __construct(
        DirectoryList $directoryList,
        HintConfig $hintConfig,
        Structure $structure,
        Layout $layout
    ) {
        $this->hintConfig = $hintConfig;
        $this->directoryList = $directoryList;
        $this->structure = $structure;
        $this->layout = $layout;
    }


    /**
     * @param Layout  $layout
     * @param Closure $proceed
     * @param string  $name
     * @param bool    $useCache
     *
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundRenderElement(Layout $layout, Closure $proceed, $name, $useCache = false)
    {
        $result = $proceed($name, $useCache);
        if ($this->hintConfig->isHintEnabled() === false) {
            return $result;
        }
        return $this->_docorateElement($result, $name);
    }


    /**
     * @param string $result
     * @param string $name
     *
     * @return string
     */
    protected function _docorateElement($result, $name)
    {
        if (! $result) {
            $result = '<div style="display:none;"></div>';
        }

        if ($this->layout->isUiComponent($name)) {
            $result = $this->decorateOuterElement(
                $result,
                [
                    'data-ho-hinttype' => 'ui-container',
                    'data-ho-hintdata' => $this->_getBlockInfo($name)
                ]
            );
        } elseif ($this->layout->isBlock($name)) {
            $result = $this->decorateOuterElement(
                $result,
                [
                    'data-ho-hinttype' => 'block',
                    'data-ho-hintdata' => $this->_getBlockInfo($name)
                ]
            );
        } elseif ($this->layout->isContainer($name)) {
            $result = $this->decorateOuterElement(
                $result,
                [
                    'data-ho-hinttype' => 'container',
                    'data-ho-hintdata' => $this->_getContainerInfo($name)
                ]
            );
        }
        return $result;
    }




    /**
     * @param string $html
     * @param array $attributes
     *
     * @return string
     */
    public function decorateOuterElement($html, $attributes)
    {

        if (!$html) {
            return $html;
        }
//        $document = new \DOMDocument();
//        $document->loadHTML($html);

//        $elements = $document->getElementsByTagName('body');
//        var_dump($html, $document->saveHTML());exit;
//        var_dump($document);exit;

        $htmlAttr = [];
        foreach ($attributes as $key => $value) {
            $htmlAttr[] = sprintf('%s="%s"', $key, htmlspecialchars($value));
        }
        $htmlAttr = implode(' ', $htmlAttr);

        $html = preg_replace(
            '/(<\b[^><]*)>/i',
            '$1'.($htmlAttr ? ' '.$htmlAttr : '').'>',
            $html,
            1
        );
        return $html;
    }


    /**
     * @param $name
     * @return string
     */
    protected function _getContainerInfo($name)
    {
        $element = $this->structure->getElement($name);

        $result = json_encode(array_filter([
            'name' => addslashes($name),
            'children' => isset($element['children']) ? array_values($element['children']) : null,
            'parent' => isset($containerInfo['parent']) ? $containerInfo['parent'] : null
        ]), JSON_UNESCAPED_SLASHES);

        return $result;
    }


    /**
     * Returns the blockInfo as a json encoded array
     *
     * @todo alias, cache lifetime, cached, not cached.
     *
     * @param $name
     * @return string
     */
    protected function _getBlockInfo($name)
    {
        /** @var \Magento\Framework\View\Element\AbstractBlock $block */
        $block = $this->layout->getBlock($name);

//        $childNames = $block->getParentBlock()->getChildNames();
//        var_dump($childNames);exit;

        $result = json_encode([
            'name' => addslashes($block->getNameInLayout()),
            'templateFile' => $this->_getBlockTemplatePath($block),
            'moduleName' => $block->getModuleName(),
            'class' => addslashes(get_class($block)),
            'cache' => ['keyInfo' => $block->getCacheKeyInfo()],
        ], JSON_UNESCAPED_SLASHES);

        return $result;
    }


    /**
     * @param $block
     *
     * @return null
     */
    protected function _getBlockTemplatePath(\Magento\Framework\View\Element\AbstractBlock $block)
    {
        if (! $block instanceof \Magento\Framework\View\Element\Template) {
            return null;
        }

        return substr($block->getTemplateFile(), strlen($this->directoryList->getRoot()));
    }
}
