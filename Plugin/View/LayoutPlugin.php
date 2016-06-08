<?php
/**
 * Created by PhpStorm.
 * User: paul
 * Date: 07-06-16
 * Time: 23:14
 */
namespace Ho\TemplateHints\Plugin\View;

use Closure;
use Magento\Framework\App\State as AppState;
use Magento\Framework\View\Layout;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\Exception\LocalizedException;

/**
 * Plugin to wrap all the rendered elements
 * 
 * Class LayoutPlugin
 * @package Ho\TemplateHints\Plugin\View
 */
class LayoutPlugin
{
    /** @var Layout */
    protected $layout;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;

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
     * @var int
     */
    protected $rootPathLengthOffset;


    /**
     * LayoutPlugin constructor.
     *
     * @param AppState $appState
     * @param Logger   $logger
     */
    public function __construct(
        AppState $appState,
        Logger $logger,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList
    ) {
        $this->appState = $appState;
        $this->logger = $logger;
        $this->rootPathLengthOffset = strlen($directoryList->getRoot());

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
        if ($this->appState->getMode() !== AppState::MODE_DEVELOPER) {
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
     * @param \Magento\Framework\View\Element\Template $block
     *
     * @return string
     */
    protected function _getBlockInfo($name)
    {
        $block = $this->layout->getBlock($name);

//        $childNames = $block->getParentBlock()->getChildNames();
//        var_dump($childNames);exit;

        $result = json_encode([
            'name' => addslashes($block->getNameInLayout()),
            'templateFile' => substr($block->getTemplateFile(), $this->rootPathLengthOffset),
            'moduleName' => $block->getModuleName(),
            'class' => addslashes(get_class($block)),
            'cache' => ['keyInfo' => $block->getCacheKeyInfo()],
        ], JSON_UNESCAPED_SLASHES);

        return $result;
    }
}
