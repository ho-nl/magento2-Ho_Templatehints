<?php

namespace Ho\Templatehints\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\State as AppState;

class Config extends AbstractHelper {

    /**
     * @var AppState
     */
    private $appState;


    public function __construct(Context $context, AppState $appState)
    {
        parent::__construct($context);
        $this->appState = $appState;
    }


    /**
     * Check if the hints can be displayed, depends on the developer mode and if the url parameter is present.
     *
     * @return bool
     */
    public function isHintEnabled()
    {
        $isDeveloperMode = $this->appState->getMode() === AppState::MODE_DEVELOPER;
        $isParamPresent = $this->_request->getParam('ath', false) === '1';
        return $isDeveloperMode && $isParamPresent;
    }
}
