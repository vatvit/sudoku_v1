<?php

class BootstrapConsole extends Zend_Application_Bootstrap_Bootstrap
{

    protected function _initAutoload()
    {
        $this->getApplication()->getAutoloader()->registerNamespace('My');
    }

}
