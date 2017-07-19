<?php

/**
 * Class UOL_PagSeguro_Model_Library
 */
class UOL_PagSeguro_Model_Library
{
    /**
     * UOL_PagSeguro_Model_Library constructor.
     */
    public function __construct()
    {
        define("SHIPPING_TYPE", 3);
        define("SHIPPING_COST", 0.00);
        define("CURRENCY", "BRL");
        \PagSeguro\Library::initialize();
        \PagSeguro\Library::cmsVersion()->setName('PagSeguro')->setRelease(Mage::getConfig()->getModuleConfig("UOL_PagSeguro")->version);
        \PagSeguro\Library::moduleVersion()->setName('Magento')->setRelease(Mage::getVersion());
        \PagSeguro\Configuration\Configure::setCharset(Mage::getStoreConfig('payment/pagseguro/charset'));
        $this->setCharset();
        $this->setEnvironment();
        $this->setLog();
    }

    /**
     *
     */
    private function setCharset()
    {
        \PagSeguro\Configuration\Configure::setCharset(Mage::getStoreConfig('payment/pagseguro/charset'));
    }

    /**
     *
     */
    private function setEnvironment()
    {
        \PagSeguro\Configuration\Configure::setEnvironment(Mage::getStoreConfig('payment/pagseguro/environment'));
    }

    /**
     *
     */
    private function setLog()
    {
        if (Mage::getStoreConfig('payment/pagseguro/log')) {
            \PagSeguro\Configuration\Configure::setLog(true,
                Mage::getBaseDir().Mage::getStoreConfig('payment/pagseguro/log_file'));
        } else {
            \PagSeguro\Configuration\Configure::setLog(false, null);
        }
    }

    /**
     * @return \PagSeguro\Domains\AccountCredentials
     */
    public function getAccountCredentials()
    {
        \PagSeguro\Configuration\Configure::setAccountCredentials(
            Mage::getStoreConfig('payment/pagseguro/email'),
            Mage::getStoreConfig('payment/pagseguro/token')
        );

        return \PagSeguro\Configuration\Configure::getAccountCredentials();
    }

    /**
     * @return mixed
     */
    public function getCharset()
    {
        return Mage::getStoreConfig('payment/pagseguro/environment');
    }

    /**
     * @return mixed
     */
    public function getEnvironment()
    {
        return Mage::getStoreConfig('payment/pagseguro/environment');
    }

    /**
     * @return mixed
     */
    public function getLog()
    {
        return Mage::getStoreConfig('payment/pagseguro/log');
    }

    /**
     * @return mixed
     */
    public function getPaymentCheckoutType()
    {
        return Mage::getStoreConfig('payment/pagseguro/checkout');
    }
}