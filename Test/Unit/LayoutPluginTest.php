<?php
/**
 * Copyright (c) 2016 H&O E-commerce specialisten B.V. (http://www.h-o.nl/)
 * See LICENSE.txt for license details.
 */
namespace Ho\Templatehints\Test;

use Ho\Templatehints\Plugin\View\LayoutPlugin;
use Magento\TestFramework\Interception\PluginList;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class LayoutPluginTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var ObjectManagerHelper
     */
    protected $objectManager;

    /**
     * @var LayoutPlugin
     */
    protected $plugin;


    protected function setUp()
    {
        $this->objectManager = new ObjectManagerHelper($this);
        $this->plugin = $this->objectManager->getObject(LayoutPlugin::class);
    }

    public function testDecorateOuterElementInput()
    {
        $result = $this->plugin->decorateOuterElement('<div></div>', []);
        $this->assertEquals('<div></div>', $result);

        $result = $this->plugin->decorateOuterElement('<div></div>', ['class' => 'yo']);
        $this->assertEquals('<div class="yo"></div>', $result);
        
        $result = $this->plugin->decorateOuterElement('<div></div>', ['data-attr' => '"']);
        $this->assertEquals('<div data-attr="&quot;"></div>', $result);
    }


    public function testDecorateOuterElementMultipleElements()
    {
        $result = $this->plugin->decorateOuterElement('<div><div></div></div>', ['class' => 'myclass']);
        $this->assertEquals('<div class="myclass"><div></div></div>', $result);

//        $result = $this->plugin->decorateOuterElement('<div></div><div></div>', ['class' => 'myclass']);
//        $this->assertEquals('<div class="myclass"><div></div></div>', $result);
    }


    public function testFilterEscapeEncode()
    {
        $this->assertEquals('[]', LayoutPlugin::filterEscapeEncode(['elem' => null]));

        $this->assertEquals('[]', LayoutPlugin::filterEscapeEncode(['elem' => ['subelem' => null]]));
        $this->assertEquals('[]', LayoutPlugin::filterEscapeEncode(['elem' => ['subelem' => ['subsub' => null]]]));

        $this->assertEquals(
            '{"elem":{"subElem":"bla"}}',
            LayoutPlugin::filterEscapeEncode(['elem' => ['subElem' => 'bla']])
        );

        $this->assertEquals(
            '{"elem":"My\\\\\\\\Class"}',
            LayoutPlugin::filterEscapeEncode(['elem' => 'My\Class'])
        );

        $this->assertEquals(
            '{"elem":{"subElem":["bla","My\\\\\\\\Class"]}}',
            LayoutPlugin::filterEscapeEncode(['elem' => ['subElem' => ['bla', 'My\Class']]])
        );
    }
}
