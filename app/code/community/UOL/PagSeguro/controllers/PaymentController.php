<?php

/**
 * Class UOL_PagSeguro_PaymentController
 */
class UOL_PagSeguro_PaymentController extends Mage_Core_Controller_Front_Action
{
    /**
     * @var UOL_PagSeguro_Model_PaymentMethod
     */
    private $payment;

    private $library;

    /**
     * UOL_PagSeguro_PaymentController constructor.
     */
    public function _construct()
    {
        $this->payment = new UOL_PagSeguro_Model_PaymentMethod();
        $this->library = new UOL_PagSeguro_Model_Library();
    }

    public function canceledAction()
    {
        $order = Mage::getModel('sales/order')->load($this->getCheckout()->getLastOrderId());
        $this->canceledStatus($order);

        return $this->loadAndRenderLayout();
    }

    /**
     * @return Mage_Core_Model_Abstract
     */
    private function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Cancel order
     *
     * @param $order
     */
    private function canceledStatus($order)
    {
        $order->cancel();
        $order->save();
    }

    /**
     * @param array $items
     *
     * @param bool  $returnAaJson
     *
     * @return $this
     * @throws Mage_Core_Exception
     */
    private function loadAndRenderLayout(Array $items = [], $returnAaJson = false)
    {
        if ($returnAaJson) {
            $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json', true);
            $this->getResponse()->setBody(json_encode($items));
        } else {
            $this->loadLayout();
            foreach ($items as $k => $item) {
                Mage::register($k, $item);
            }
            $this->renderLayout();
        }

        return $this;
    }

    /**
     * @return UOL_PagSeguro_PaymentController
     * @throws Mage_Core_Exception
     */
    public function defaultAction()
    {
        $link = null;
        
        try {
            /** @var Mage_Sales_Model_Order $order */
            $order = Mage::getModel('sales/order')->load($this->getCheckout()->getLastOrderId());
            $orderData = $order->getData();
            if(empty($orderData)) {
                $this->norouteAction();
                return;
            }
            
            $this->payment->setOrder($order);
            /**
             * @var \PagSeguro\Domains\Requests\DirectPayment\Boleto|\PagSeguro\Domains\Requests\DirectPayment\CreditCard|\PagSeguro\Domains\Requests\DirectPayment\OnlineDebit $payment
             */
            
            $payment = $this->payment->paymentDefault();

            $this->payment->addPagseguroOrders($order);
            $this->payment->clearCheckoutSession($order);
            /**
             * @var \PagSeguro\Domains\Requests\DirectPayment\Boleto|\PagSeguro\Domains\Requests\DirectPayment\CreditCard|\PagSeguro\Domains\Requests\DirectPayment\OnlineDebit $result
             */
            $link = $this->payment->paymentRegister($payment);
            if ($link == false) {
                throw new Exception('Can\'t generate PagSeguro payment url for lightbox checkout.');
            }
            $order->sendNewOrderEmail();
        } catch (Exception $exception) {
            \PagSeguro\Resources\Log\Logger::error($exception);
            Mage::logException($exception);
            $this->canceledStatus($order);
            return Mage_Core_Controller_Varien_Action::_redirect(
                'pagseguro/payment/error',
                ['_secure' => false]
            );
        }

        return $this->loadAndRenderLayout([
            'link' => $link,
        ]);
    }

    /**
     * @return Mage_Core_Controller_Varien_Action|UOL_PagSeguro_PaymentController
     */
    public function directAction()
    {
        $paymentSession = null;
        $order          = null;
        $link           = null;
        $result         = null;
        $redirect       = null;

        try {
            /** @var Mage_Sales_Model_Order $order */
            $order = Mage::getModel('sales/order')->load($this->getCheckout()->getLastOrderId());

            $orderData = $order->getData();
            if (empty($orderData)) {
                $this->norouteAction();
                return;
            }

            $customerPaymentData = Mage::getSingleton('customer/session')->getData();

            $this->payment->setOrder($order);

            /**
             * @var \PagSeguro\Domains\Requests\DirectPayment\Boleto |
             * \PagSeguro\Domains\Requests\DirectPayment\CreditCard |
             * \PagSeguro\Domains\Requests\DirectPayment\OnlineDebit $payment
             */
            $payment = $this->payment->paymentDirect($order->getPayment()->getMethod(), $customerPaymentData);
            $this->payment->addPagseguroOrders($order);
            $this->payment->clearCheckoutSession($order);

            /**
             * @var \PagSeguro\Domains\Requests\DirectPayment\Boleto |
             * \PagSeguro\Domains\Requests\DirectPayment\CreditCard |
             * \PagSeguro\Domains\Requests\DirectPayment\OnlineDebit $result
             */
            $result = $this->payment->paymentRegister($payment);

            if ($result == false) {
                $this->canceledStatus($order);
                if (! $order->getCustomerIsGuest() && Mage::getStoreConfig('payment/pagseguro/checkout_direct_retry') == true) {
                    return Mage_Core_Controller_Varien_Action::_redirect(
                        'pagseguro/payment/retry',
                        ['_secure' => false]
                    );
                } else {
                    return Mage_Core_Controller_Varien_Action::_redirect(
                        'pagseguro/payment/error',
                        ['_secure' => false]
                    );
                }
            }

            $psOrder = \PagSeguro\Services\Transactions\Search\Code::search(
                $this->library->getAccountCredentials(),
                $result->getCode()
            );

            if (Mage::getStoreConfig('payment/pagseguro/checkout_direct_retry') == true &&
                $psOrder->getStatus() == '7') {
                $this->canceledStatus($order);

                return Mage_Core_Controller_Varien_Action::_redirect(
                    'pagseguro/payment/denied',
                    ['_secure' => false]
                );
            }

            if (method_exists($result, 'getPaymentLink') && $result->getPaymentLink()) {
                $link = $result->getPaymentLink();
                $redirect = 'pagseguro/payment/success';
                $redirectParams = ['_secure' => false, '_query' => ['redirect' => $link]];
            } else {
                if (Mage::getStoreConfig('payment/pagseguro_credit_card/url_success') &&
                    Mage::getStoreConfig('payment/pagseguro_credit_card/url_success') != '') {
                    $redirect = Mage::getStoreConfig('payment/pagseguro_credit_card/url_success');
                } else {
                    $redirect = 'pagseguro/payment/success';
                }
                $redirectParams = [];
            }

            $order->sendNewOrderEmail();

        } catch (\Exception $exception) {
            $this->canceledStatus($order);
            return Mage_Core_Controller_Varien_Action::_redirect('pagseguro/payment/error', array('_secure'=> false));
        }

        return  Mage_Core_Controller_Varien_Action::_redirect(
            $redirect,
            $redirectParams
        );
    }

    /**
     * @return UOL_PagSeguro_PaymentController
     */
    public function lightboxAction()
    {
        $code = null;
        try {
            /** @var Mage_Sales_Model_Order $order */
            $order = Mage::getModel('sales/order')->load($this->getCheckout()->getLastOrderId());

            $orderData = $order->getData();
            if(empty($orderData)) {
                $this->norouteAction();
                return;
            }

            $this->payment->setOrder($order);
            /**
             * @var \PagSeguro\Domains\Requests\DirectPayment\Boleto|\PagSeguro\Domains\Requests\DirectPayment\CreditCard|\PagSeguro\Domains\Requests\DirectPayment\OnlineDebit $payment
             */
            $payment = $this->payment->paymentLightbox();
            $this->payment->addPagseguroOrders($order);
            $this->payment->clearCheckoutSession($order);
            /**
             * @var \PagSeguro\Domains\Requests\DirectPayment\Boleto|\PagSeguro\Domains\Requests\DirectPayment\CreditCard|\PagSeguro\Domains\Requests\DirectPayment\OnlineDebit $result
             */
            $code = $this->payment->paymentRegister($payment, true);
            if ($code == false) {
                throw new Exception('Can\'t generate PagSeguro payment code for lightbox checkout.');
            }
            $order->sendNewOrderEmail();
        } catch (Exception $exception) {
            \PagSeguro\Resources\Log\Logger::error($exception);
            Mage::logException($exception);
            $this->canceledStatus($order);
            return Mage_Core_Controller_Varien_Action::_redirect(
                'pagseguro/payment/error',
                ['_secure' => false]
            );
        }

        if ($this->library->getEnvironment() === 'production') {
            $lightboxJs  = 'https://stc.pagseguro.uol.com.br/pagseguro/api/v2/checkout/pagseguro.lightbox.js';
            $lightboxUrl = 'https://pagseguro.uol.com.br/v2/checkout/payment.html?code=';
        } else {
            $lightboxJs  = 'https://stc.sandbox.pagseguro.uol.com.br/pagseguro/api/v2/checkout/pagseguro.lightbox.js';
            $lightboxUrl = 'https://sandbox.pagseguro.uol.com.br/v2/checkout/payment.html?code=';
        }

        return $this->loadAndRenderLayout([
            'code'        => $code,
            'lightboxUrl' => $lightboxUrl,
            'lightboxJs'  => $lightboxJs,
        ]);
    }

    /**
     * Process the request by checkout type
     */
    public function requestAction()
    {
        $order = Mage::getModel('sales/order')->load($this->getCheckout()->getLastOrderId());
        $orderPaymentMethod = $order->getPayment()->getMethod();

        if ($orderPaymentMethod === 'pagseguro_online_debit' || $orderPaymentMethod === 'pagseguro_boleto' ||$orderPaymentMethod === 'pagseguro_credit_card') {
            $this->_redirectUrl(Mage::getUrl('pagseguro/payment/direct'));
        } elseif ($orderPaymentMethod === 'pagseguro_default_lightbox' && $this->payment->getPaymentCheckoutType() === 'PADRAO') {
            $this->_redirectUrl(Mage::getUrl('pagseguro/payment/default'));
        } elseif ($orderPaymentMethod === 'pagseguro_default_lightbox' && $this->payment->getPaymentCheckoutType() === 'LIGHTBOX') {
            $this->_redirectUrl(Mage::getUrl('pagseguro/payment/lightbox'));
        } else {
            \PagSeguro\Resources\Log\Logger::error('Método de pagamento inválido para o PagSeguro');
            return Mage_Core_Controller_Varien_Action::_redirect('pagseguro/payment/error', array('_secure'=> false));
        }
    }

    /**
     * @return UOL_PagSeguro_PaymentController
     * @throws Mage_Core_Exception
     */
    public function successAction()
    {
        return $this->loadAndRenderLayout();
    }

    /**
     * Default payment error screen
     *
     * @return UOL_PagSeguro_PaymentController
     * @throws Mage_Core_Exception
     */
    public function errorAction()
    {
        return $this->loadAndRenderLayout();
    }

    /**
     * @return UOL_PagSeguro_PaymentController
     * @throws Mage_Core_Exception
     */
    public function retryAction()
    {
        return $this->loadAndRenderLayout();
    }

    /**
     * @return UOL_PagSeguro_PaymentController
     * @throws Mage_Core_Exception
     */
    public function deniedAction()
    {
        return $this->loadAndRenderLayout();
    }
            
}
