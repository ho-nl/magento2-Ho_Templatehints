<?php
/**
 * Copyright (c) 2016 H&O E-commerce specialisten B.V. (http://www.h-o.nl/)
 * See LICENSE.txt for license details.
 */
namespace Ho\Templatehints\Plugin\View;

use Closure;
use Ho\Templatehints\Helper\Config;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\State as AppState;
use Magento\Framework\View\Layout;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\RequestInterface;

/**
 * Plugin to wrap all the rendered elements
 * 
 * Class LayoutPlugin
 * @package Ho\Templatehints\Plugin\View
 */
class LayoutPlugin
{
    /** @var Layout */
    protected $layout;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Layout structure model
     *
     * @var Layout\Data\Structure
     */
    protected $structure;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var AppState
     */
    protected $appState;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var DirectoryList
     */
    private $directoryList;


    /**
     * LayoutPlugin constructor.
     *
     * @param Logger           $logger
     * @param RequestInterface $request
     * @param DirectoryList    $directoryList
     * @param AppState         $appState
     * @param Config           $config
     *
     * @internal param AppState $appState
     */
    public function __construct(
        Logger $logger,
        RequestInterface $request,
        DirectoryList $directoryList,
        AppState $appState,
        Config $config
    ) {
        $this->request = $request;
        $this->logger = $logger;
        $this->config = $config;
        $this->appState = $appState;
        $this->directoryList = $directoryList;
    }


    /**
     * @param Layout $layout
     * @param Closure                       $procede
     * @param                                $name
     *
     * @return string
     * @throws \Exception
     */
    public function aroundRenderNonCachedElement(Layout $layout, Closure $procede, $name)
    {
        if ($this->config->isHintEnabled() === false) {
            return $procede($name);
        }
        $this->layout = $layout;


        $result = '';
        try {
            $result = $this->_renderElement($layout, $name);
        } catch (\Exception $e) {
            if ($this->appState->getMode() === AppState::MODE_DEVELOPER) {
                throw $e;
            }
            $message = ($e instanceof LocalizedException) ? $e->getLogMessage() : $e->getMessage();
            $this->logger->critical($message);
        }
        return $result;
    }


    /**
     * @param Layout $layout
     * @param        $name
     *
     * @return string
     */
    protected function _renderElement(Layout $layout, $name)
    {
        $reflectionClass = new \ReflectionClass(Layout::CLASS);

        $structure = $reflectionClass->getProperty('structure');
        $structure->setAccessible(true);
        $this->structure = $structure->getValue($layout);

        $renderUiComponent = $reflectionClass->getMethod('_renderUiComponent');
        $renderUiComponent->setAccessible(true);

        $renderBlock = $reflectionClass->getMethod('_renderBlock');
        $renderBlock->setAccessible(true);

        $renderContainer = $reflectionClass->getMethod('_renderContainer');
        $renderContainer->setAccessible(true);

        if ($layout->isUiComponent($name)) {
            $result = $renderUiComponent->invoke($layout, $name);
            $result = $this->_decorateOuterElement($result,
                [
                    'data-ho-hinttype' => 'ui-container',
                    'data-ho-hintdata' => $this->_getBlockInfo($name)
                ]
            );
            return $result;
        } elseif ($layout->isBlock($name)) {
            $result = $renderBlock->invoke($layout, $name);
            $result = $this->_decorateOuterElement(
                $result,
                [
                    'data-ho-hinttype' => 'block',
                    'data-ho-hintdata' => $this->_getBlockInfo($name)
                ]
            );
            return $result;
        } elseif($layout->isContainer($name)) {
            $result = $renderContainer->invoke($layout, $name);
            $result = $this->_decorateOuterElement(
                $result,
                [
                    'data-ho-hinttype' => 'container',
                    'data-ho-hintdata' => $this->_getContainerInfo($name)
                ]
            );
            return $result;
        }
    }




    /**
     * @param string $html
     * @param array $attributes
     *
     * @return string
     * @internal param string $blockHtml
     */
    protected function _decorateOuterElement($html, $attributes)
    {
        if (!$html) {
            return $html;
        }

        $htmlAttr = [];
        foreach ($attributes as $key => $value) {
            $htmlAttr[] = sprintf('%s=\'%s\'', $key, $value);
        }
        $htmlAttr = implode(' ', $htmlAttr);

        $html = preg_replace(
            '/(<\b[^><]*)>/i',
            '$1 '.$htmlAttr.'>',
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
     * @todo alias
     *       cache lifetime, cached, not cached.
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
